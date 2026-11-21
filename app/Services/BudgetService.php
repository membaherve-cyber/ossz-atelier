<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\CarbonInterface;

/**
 * OSSZ ATELIER — budget service (Finance & Comptabilité spec v1.3 §8).
 *
 * Actuals are always DERIVED from the accounting records for the
 * budget's period (payments for revenue, approved/paid expenses per
 * category for charges), so the planned-vs-real comparison can never
 * go stale and forecasts stay distinct from the costs actually
 * recorded (§8).
 */
final class BudgetService
{
    /**
     * Compare a budget's planned lines with the actuals of its period.
     *
     * @return array{
     *     revenue: array{planned: float, actual: float, variance: float},
     *     expense_total: array{planned: float, actual: float, variance: float},
     *     lines: list<array<string, mixed>>,
     *     alerts: list<string>
     * }
     */
    public static function comparison(Budget $budget): array
    {
        $from = $budget->period_start->startOfDay();
        $to = $budget->period_end->endOfDay();

        // Revenue actual: customer payments collected in the period.
        $revenueActual = (float) Payment::query()
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');

        // Expense actuals per category (approved + paid count as costs).
        $expenses = Expense::query()
            ->with('category:id,name,group')
            ->whereIn('status', Expense::COST_STATUSES)
            ->whereBetween('date_incurred', [$from, $to])
            ->get();

        $actualByCategory = [];
        $expenseActualTotal = 0.0;

        foreach ($expenses as $expense) {
            $categoryId = $expense->expense_category_id;
            $actualByCategory[$categoryId] = ($actualByCategory[$categoryId] ?? 0.0) + (float) $expense->amount;
            $expenseActualTotal += (float) $expense->amount;
        }

        $lines = [];
        $revenuePlanned = 0.0;
        $expensePlannedTotal = 0.0;
        $alerts = [];

        foreach ($budget->lines as $line) {
            if ($line->kind === 'REVENUE') {
                $revenuePlanned += (float) $line->planned;
                continue; // revenue shown globally
            }

            if ($line->expense_category_id === null) {
                // Global expense line — shown against the total actual.
                $expensePlannedTotal += (float) $line->planned;
                $lines[] = [
                    'line' => $line,
                    'category' => null,
                    'planned' => (float) $line->planned,
                    'actual' => round($expenseActualTotal, 2),
                ];
                continue;
            }

            $actual = round($actualByCategory[$line->expense_category_id] ?? 0.0, 2);
            $expensePlannedTotal += (float) $line->planned;

            $lines[] = [
                'line' => $line,
                'category' => $line->expenseCategory,
                'planned' => (float) $line->planned,
                'actual' => $actual,
            ];

            // §8 alerte de dépassement.
            if ($actual > (float) $line->planned && $budget->status === 'ACTIVE') {
                $alerts[] = __('bud.overrunAlert', [
                    'category' => $line->expenseCategory?->name ?? $line->note ?? '—',
                    'planned' => Money::format((float) $line->planned),
                    'actual' => Money::format($actual),
                ]);
            }
        }

        return [
            'revenue' => [
                'planned' => round($revenuePlanned, 2),
                'actual' => round($revenueActual, 2),
                'variance' => round($revenueActual - $revenuePlanned, 2),
            ],
            'expense_total' => [
                'planned' => round($expensePlannedTotal, 2),
                'actual' => round($expenseActualTotal, 2),
                'variance' => round($expenseActualTotal - $expensePlannedTotal, 2),
            ],
            'lines' => $lines,
            'alerts' => $alerts,
        ];
    }

    /**
     * Whether any ACTIVE budget covers the given date — used to flag
     * overruns at expense recording time (§8).
     */
    public static function activeBudgetFor(CarbonInterface $date): ?Budget
    {
        return Budget::query()
            ->where('status', 'ACTIVE')
            ->where('period_start', '<=', $date->toDateString())
            ->where('period_end', '>=', $date->toDateString())
            ->orderByDesc('period_end')
            ->first();
    }
}
