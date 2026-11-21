<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\StaffAdvance;
use App\Services\TreasuryService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * OSSZ ATELIER — accounting aggregation engine (enhancement spec §5, §12).
 *
 * Every figure is computed from actual database records; nothing is
 * sampled or estimated. Core calculation rules (spec §5.1):
 *
 * - Revenue               = Σ order total_value of REGISTERED orders (order date in range).
 * - Payments received     = Σ payments.amount (payment date in range).
 * - Outstanding balances  = Σ max(0, order value − paid) over REGISTERED orders (as of now).
 * - Expenses              = Σ expenses with status APPROVED or PAID (date incurred in range).
 * - Salaries              = Σ payroll_items.net_pay of APPROVED/PARTIALLY_PAID/PAID payrolls
 *                           (period start in range). Advance recoveries are NOT extra costs.
 * - Net operating result  = revenue − expenses − salaries (accrual basis).
 * - Cash movement         = payments received − PAID expenses − PAID salary
 *                           lines − advance disbursements (cash basis —
 *                           APPROVED-but-unpaid expenses and PENDING salary
 *                           lines never move cash).
 *
 * Incentives paid enter exactly once through their auto-booked expense
 * (source INCENTIVE_RECORD); recovered advances never create expenses.
 */
final class AccountingService
{
    /** Dashboard date presets (spec §5). */
    public const PERIOD_PRESETS = ['today', 'week', 'month', 'last_month', 'year', 'all', 'custom'];

    /**
     * Resolve the selected period into an inclusive [from, to] range.
     *
     * @return array{from: CarbonImmutable, to: CarbonImmutable, preset: string}
     */
    public static function resolvePeriod(?string $preset, ?string $from = null, ?string $to = null): array
    {
        $preset = in_array($preset, self::PERIOD_PRESETS, true) ? $preset : 'month';
        $today = CarbonImmutable::today();

        return match ($preset) {
            'today' => ['from' => $today, 'to' => $today->endOfDay(), 'preset' => $preset],
            'week' => ['from' => $today->startOfWeek(), 'to' => $today->endOfWeek(), 'preset' => $preset],
            'month' => ['from' => $today->startOfMonth(), 'to' => $today->endOfMonth(), 'preset' => $preset],
            'last_month' => ['from' => $today->startOfMonth()->subMonth(), 'to' => $today->startOfMonth()->subDay()->endOfDay(), 'preset' => $preset],
            'year' => ['from' => $today->startOfYear(), 'to' => $today->endOfYear(), 'preset' => $preset],
            'custom' => [
                'from' => self::parseDate($from) ?? $today->startOfYear(),
                'to' => (self::parseDate($to) ?? $today)->endOfDay(),
                'preset' => $preset,
            ],
            default => ['from' => CarbonImmutable::create(2000, 1, 1), 'to' => $today->endOfDay(), 'preset' => 'all'],
        };
    }

    /**
     * Full financial dashboard payload (spec §5).
     *
     * @return array<string, mixed>
     */
    public static function dashboard(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $committed = Expense::COST_STATUSES;

        // ---- Flow metrics over the selected range -------------------------
        $revenue = (float) Order::query()
            ->where('status', 'REGISTERED')
            ->whereBetween('order_date', [$from, $to])
            ->sum('total_value');

        $paymentsReceived = (float) Payment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $expenses = (float) Expense::query()
            ->whereIn('status', $committed)
            ->whereBetween('date_incurred', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $salaries = (float) PayrollItem::query()
            ->whereIn('payment_status', ['PENDING', 'PAID'])
            ->whereHas('payroll', fn ($q) => $q->whereIn('status', ['APPROVED', 'PARTIALLY_PAID', 'PAID'])
                ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()]))
            ->sum('net_pay');

        // NOTE: array_merge, NOT "array union +" — `+` keeps only the left
        // side's keys, which would silently drop FULLY_RECOVERED.
        $advancesDisbursed = (float) StaffAdvance::query()
            ->whereIn('status', array_merge(StaffAdvance::DISBURSED_STATUSES, ['FULLY_RECOVERED']))
            ->whereBetween('date_issued', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        // ---- Cash-basis variants for the cash-movement card ---------------
        // date_paid is authoritative when present; PAID rows recorded
        // without one (manual PAID entry) fall back to date_incurred.
        $paidExpenses = (float) Expense::query()
            ->where('status', 'PAID')
            ->whereBetween(DB::raw('COALESCE(date_paid, date_incurred)'), [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $paidSalaries = (float) PayrollItem::query()
            ->where('payment_status', 'PAID')
            ->whereHas('payroll', fn ($q) => $q->whereIn('status', ['APPROVED', 'PARTIALLY_PAID', 'PAID'])
                ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()]))
            ->sum('net_pay');

        // ---- As-of-now balances --------------------------------------------
        $outstandingBalances = (float) Order::query()
            ->where('status', 'REGISTERED')
            ->whereNotNull('total_value')
            ->get(['id', 'total_value'])
            ->sum(fn (Order $order) => $order->outstandingBalance());

        $advancesOutstanding = (float) StaffAdvance::query()
            ->whereIn('status', StaffAdvance::DISBURSED_STATUSES)
            ->get(['amount', 'amount_recovered'])
            ->sum(fn (StaffAdvance $a) => $a->outstanding());

        // ---- Fixed current-month cards --------------------------------------
        $monthStart = CarbonImmutable::today()->startOfMonth();
        $monthEnd = CarbonImmutable::today()->endOfMonth();

        $monthRevenue = (float) Order::query()
            ->where('status', 'REGISTERED')
            ->whereBetween('order_date', [$monthStart, $monthEnd])
            ->sum('total_value');

        $monthExpenses = (float) Expense::query()
            ->whereIn('status', $committed)
            ->whereBetween('date_incurred', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        // ---- Series & breakdowns --------------------------------------------
        $series = self::monthlySeries($from, $to);
        $categoryBreakdown = self::expenseCategoryBreakdown($from, $to);

        // Finance module v1.3 §4.4 — treasury snapshot (all accounts,
        // derived balances) + supplier debts (§4.6).
        $treasury = TreasuryService::overview();
        $supplierDebts = (float) Purchase::query()
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
            ->selectRaw('COALESCE(SUM(total_amount), 0) - COALESCE(SUM(amount_paid), 0) AS due')
            ->value('due');

        return [
            'revenue' => $revenue,
            'paymentsReceived' => $paymentsReceived,
            'outstandingBalances' => $outstandingBalances,
            'expenses' => $expenses,
            'salaries' => $salaries,
            'advancesOutstanding' => $advancesOutstanding,
            'advancesDisbursed' => $advancesDisbursed,
            'netResult' => $revenue - $expenses - $salaries,
            'cashMovement' => $paymentsReceived - $paidExpenses - $paidSalaries - $advancesDisbursed,
            'monthRevenue' => $monthRevenue,
            'monthExpenses' => $monthExpenses,
            'series' => $series,
            'categoryBreakdown' => $categoryBreakdown,
            'treasuryTotal' => $treasury['total'],
            'treasuryAccounts' => $treasury['accounts'],
            'supplierDebts' => round($supplierDebts, 2),
        ];
    }

    /**
     * Monthly buckets between from..to with revenue / payments / expenses /
     * salaries / net per month (chart data for spec §5).
     *
     * @return list<array{key: string, label: string, revenue: float, payments: float, expenses: float, salaries: float, net: float}>
     */
    public static function monthlySeries(CarbonImmutable $from, CarbonImmutable $to, int $maxBuckets = 24): array
    {
        $committed = Expense::COST_STATUSES;

        $revenueRows = Order::query()
            ->where('status', 'REGISTERED')
            ->whereBetween('order_date', [$from, $to])
            ->whereNotNull('total_value')
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') AS ym, SUM(total_value) AS total")
            ->groupBy('ym')->pluck('total', 'ym');

        $paymentRows = Payment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total")
            ->groupBy('ym')->pluck('total', 'ym');

        $expenseRows = Expense::query()
            ->whereIn('status', $committed)
            ->whereBetween('date_incurred', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_FORMAT(date_incurred, '%Y-%m') AS ym, SUM(amount) AS total")
            ->groupBy('ym')->pluck('total', 'ym');

        $salaryRows = PayrollItem::query()
            ->whereIn('payment_status', ['PENDING', 'PAID'])
            ->whereHas('payroll', fn ($q) => $q->whereIn('status', ['APPROVED', 'PARTIALLY_PAID', 'PAID'])
                ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()]))
            ->selectRaw("DATE_FORMAT((SELECT period_start FROM payrolls WHERE payrolls.id = payroll_items.payroll_id), '%Y-%m') AS ym, SUM(net_pay) AS total")
            ->groupBy('ym')->pluck('total', 'ym');

        $buckets = [];
        $cursor = $from->startOfMonth();
        $end = $to->startOfMonth();

        // Very long ranges (e.g. "all time") are clamped to the LAST
        // $maxBuckets months so the charts always cover recent activity
        // instead of bucketing from the range start decades ago.
        $windowStart = $end->subMonths($maxBuckets - 1);
        if ($cursor < $windowStart) {
            $cursor = $windowStart;
        }

        while ($cursor <= $end && count($buckets) < $maxBuckets) {
            $key = $cursor->format('Y-m');
            $revenue = (float) ($revenueRows[$key] ?? 0);
            $expenses = (float) ($expenseRows[$key] ?? 0);
            $salaries = (float) ($salaryRows[$key] ?? 0);

            $buckets[] = [
                'key' => $key,
                'label' => $cursor->locale(app()->getLocale())->translatedFormat('M Y'),
                'revenue' => $revenue,
                'payments' => (float) ($paymentRows[$key] ?? 0),
                'expenses' => $expenses,
                'salaries' => $salaries,
                'net' => $revenue - $expenses - $salaries,
            ];

            $cursor = $cursor->addMonth();
        }

        return $buckets;
    }

    /**
     * Committed expenses grouped by category (chart data).
     *
     * @return list<array{name: string, group: string, amount: float, color: string}>
     */
    public static function expenseCategoryBreakdown(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $committed = Expense::COST_STATUSES;

        $rows = Expense::query()
            ->whereIn('expenses.status', $committed)
            ->whereBetween('expenses.date_incurred', [$from->toDateString(), $to->toDateString()])
            ->join('expense_categories AS c', 'c.id', '=', 'expenses.expense_category_id')
            ->groupBy('c.id', 'c.name', 'c.group')
            ->orderByDesc('total')
            ->get(['c.name', 'c.group', DB::raw('SUM(expenses.amount) AS total')]);

        $palette = ['#7c5cbf', '#0f766e', '#b45309', '#be123c', '#1d4ed8', '#4d7c0f', '#a16207', '#9333ea', '#0e7490', '#c2410c'];
        $colors = [];

        return $rows->map(function ($row, int $i) use (&$colors, $palette) {
            $color = $palette[$i % count($palette)];

            return [
                'name' => $row->name,
                'group' => $row->group,
                'amount' => (float) $row->total,
                'color' => $color,
            ];
        })->all();
    }

    /**
     * Order-level profitability rows (spec §11/§12.12) for every REGISTERED
     * order with a recorded value.
     *
     * @return list<array<string, mixed>>
     */
    public static function orderProfitability(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $query = Order::query()
            ->where('status', 'REGISTERED')
            ->whereNotNull('total_value')
            ->with(['customer'])
            ->orderByDesc('order_date');

        if ($from !== null && $to !== null) {
            $query->whereBetween('order_date', [$from, $to]);
        }

        $rows = [];

        foreach ($query->get() as $order) {
            $costs = $order->costBreakdown();

            $rows[] = [
                'order' => $order,
                'value' => (float) $order->total_value,
                'paid' => $order->paidTotal(),
                'outstanding' => $order->outstandingBalance(),
                'materials' => $costs['materials'],
                'transport' => $costs['transport'],
                'other' => $costs['other'],
                'labour' => $costs['labour'],
                'totalCost' => $costs['total'],
                'contribution' => (float) $order->total_value - $costs['total'],
            ];
        }

        return $rows;
    }

    /**
     * Parse a Y-m-d date string leniently.
     */
    private static function parseDate(?string $value): ?CarbonImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($value));
        } catch (\Exception) {
            return null;
        }
    }
}
