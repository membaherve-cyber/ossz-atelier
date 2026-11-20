<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyReportSetting;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductionTask;
use App\Models\User;
use App\Support\Atelier;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Assembles the daily business-health report payload (production +
 * financial + staff + alerts) for the automated e-mail.
 *
 * Every figure is computed from recorded database rows only — nothing
 * sampled or estimated. Financial aggregates reuse AccountingService
 * (spec §5.1 rules: revenue ≠ cash received; committed expenses only).
 */
final class DailyReportService
{
    /**
     * Build the full report for "today" in the configured business
     * timezone, honouring the section toggles from the settings row.
     *
     * @return array<string, mixed>
     */
    public function build(DailyReportSetting $settings): array
    {
        $tz = $settings->timezone();
        $now = CarbonImmutable::now($tz);
        $today = $now->startOfDay();
        $todayStr = $today->toDateString();
        $monthStart = $today->startOfMonth();

        // ---- Headline "today" numbers -----------------------------------
        $todayFigures = [
            'ordersRegistered' => (int) Order::query()
                ->whereBetween('order_date', [$todayStr, $todayStr])
                ->count(),
            'piecesCompleted' => (int) ProductionTask::query()
                ->where('status', 'DELIVERED')
                ->whereDate('updated_at', $todayStr)
                ->count(),
            'paymentsIn' => (float) Payment::query()
                ->whereBetween('payment_date', [$todayStr, $todayStr])
                ->sum('amount'),
            'paymentsInCount' => (int) Payment::query()
                ->whereBetween('payment_date', [$todayStr, $todayStr])
                ->count(),
            'expensesOut' => (float) Expense::query()
                ->whereIn('status', Expense::COST_STATUSES)
                ->whereBetween('date_incurred', [$todayStr, $todayStr])
                ->sum('amount'),
            'expensesOutCount' => (int) Expense::query()
                ->whereIn('status', Expense::COST_STATUSES)
                ->whereBetween('date_incurred', [$todayStr, $todayStr])
                ->count(),
        ];

        $report = [
            'generatedAt' => $now,
            'reportDate' => $today,
            'timezone' => $tz->getName(),
            'currency' => 'XAF',
            'today' => $todayFigures,
            'production' => $settings->includes('production') ? $this->productionSnapshot($now) : null,
            'financial' => $settings->includes('financial') ? $this->financialSnapshot($monthStart, $now, $todayFigures) : null,
            'staff' => $settings->includes('staff') ? $this->staffWorkload($today) : null,
            'alerts' => $settings->includes('alerts') ? $this->attentionList($now) : null,
        ];

        return $report;
    }

    /**
     * Live production board counts + 30-day on-time delivery rate.
     *
     * @return array<string, mixed>
     */
    private function productionSnapshot(CarbonInterface $now): array
    {
        $statusCounts = ProductionTask::query()
            ->groupBy('status')
            ->selectRaw('status, count(*) as aggregate')
            ->pluck('aggregate', 'status');

        $count = static fn (string $status): int => (int) ($statusCounts[$status] ?? 0);

        $terminal = Atelier::terminalStatuses();

        // Orders still in production = REGISTERED orders with at least one
        // non-terminal task (mirrors the order board semantics).
        $ordersInProduction = (int) Order::query()
            ->where('status', 'REGISTERED')
            ->whereHas('tasks', fn ($q) => $q->whereNotIn('status', $terminal))
            ->count();

        // On-time delivery over the last 30 days (delivered pieces whose
        // deadline was met; pieces without a deadline always count).
        $deliveredWindow = ProductionTask::query()
            ->where('status', 'DELIVERED')
            ->whereBetween('updated_at', [
                $now->subDays(30)->startOfDay(),
                $now->endOfDay(),
            ])
            ->get(['deadline', 'updated_at']);

        $delivered30 = $deliveredWindow->count();
        $onTime30 = $deliveredWindow
            ->filter(static fn (ProductionTask $task): bool => $task->deadline === null
                || CarbonImmutable::instance($task->deadline)->greaterThanOrEqualTo($task->updated_at))
            ->count();

        return [
            'ordersInProduction' => $ordersInProduction,
            'activeTasks' => (int) ProductionTask::query()->whereNotIn('status', $terminal)->count(),
            'unassigned' => $count('UNASSIGNED'),
            'assigned' => $count('ASSIGNED'),
            'ongoing' => $count('ONGOING'),
            'changesRequested' => $count('CHANGES_REQUESTED'),
            'awaitingInspection' => $count('AWAITING_INSPECTION'),
            'readyForPickup' => $count('READY_FOR_PICKUP'),
            'blocked' => $count('BLOCKED'),
            'overdue' => (int) ProductionTask::query()
                ->whereNotNull('deadline')
                ->where('deadline', '<', $now)
                ->whereNotIn('status', $terminal)
                ->count(),
            'onTimeRate30d' => $delivered30 === 0 ? null : (int) round($onTime30 * 100 / $delivered30),
            'delivered30d' => $delivered30,
        ];
    }

    /**
     * Month-to-date financial snapshot (AccountingService rules) plus the
     * today flows for the cash card.
     *
     * @return array<string, mixed>
     */
    private function financialSnapshot(CarbonImmutable $monthStart, CarbonInterface $now, array $todayFigures): array
    {
        // AccountingService::dashboard computes: revenue (registered order
        // value), payments received, committed expenses, payroll, advances,
        // outstanding balances + cash movement for the period (spec §5.1).
        $dash = AccountingService::dashboard($monthStart, CarbonImmutable::instance($now));

        // Top expense categories month-to-date (top 5).
        $categories = array_slice(
            AccountingService::expenseCategoryBreakdown($monthStart, CarbonImmutable::instance($now)),
            0,
            5,
        );

        return [
            'revenueMtd' => $dash['revenue'],
            'paymentsMtd' => $dash['paymentsReceived'],
            'expensesMtd' => $dash['expenses'],
            'salariesMtd' => $dash['salaries'],
            'advancesOutstanding' => $dash['advancesOutstanding'],
            'outstandingBalances' => $dash['outstandingBalances'],
            'netResultMtd' => $dash['netResult'],
            'cashMovementMtd' => $dash['cashMovement'],
            'expenseCategories' => $categories,
            'today' => $todayFigures,
        ];
    }

    /**
     * Active-staff workload table (active / overdue / completed today).
     *
     * @return list<array<string, mixed>>
     */
    private function staffWorkload(CarbonInterface $today): array
    {
        $terminal = Atelier::terminalStatuses();

        return User::query()
            ->where('role', 'STAFF')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static function (User $staff) use ($terminal, $today): array {
                $base = ProductionTask::query()
                    ->where('assigned_staff_id', $staff->id)
                    ->whereNotIn('status', $terminal);

                return [
                    'name' => $staff->name,
                    'active' => (clone $base)->count(),
                    'overdue' => (clone $base)
                        ->whereNotNull('deadline')
                        ->where('deadline', '<', $today)
                        ->count(),
                    'completedToday' => ProductionTask::query()
                        ->where('assigned_staff_id', $staff->id)
                        ->where('status', 'DELIVERED')
                        ->whereDate('updated_at', $today->toDateString())
                        ->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Attention list: overdue pieces (top 8), blocked tasks, orders due
     * within the next 48 hours that are not fully delivered.
     *
     * @return array<string, mixed>
     */
    private function attentionList(CarbonInterface $now): array
    {
        $terminal = Atelier::terminalStatuses();

        $overdueTasks = ProductionTask::query()
            ->with(['order.customer', 'assignedStaff'])
            ->whereNotNull('deadline')
            ->where('deadline', '<', $now)
            ->whereNotIn('status', $terminal)
            ->orderBy('deadline')
            ->limit(8)
            ->get()
            ->map(static fn (ProductionTask $task): array => [
                'piece' => $task->garment_piece,
                'orderNumber' => $task->order?->order_number ?? '—',
                'customer' => $task->order?->customer?->name ?? '—',
                'staff' => $task->assignedStaff?->name ?? '—',
                'progress' => (int) $task->progress,
                'daysOverdue' => max(1, (int) ceil(abs($now->diffInSeconds($task->deadline)) / 86400)),
            ])
            ->all();

        $blockedTasks = ProductionTask::query()
            ->with(['order', 'assignedStaff'])
            ->where('status', 'BLOCKED')
            ->orderBy('updated_at')
            ->limit(5)
            ->get()
            ->map(static fn (ProductionTask $task): array => [
                'piece' => $task->garment_piece,
                'orderNumber' => $task->order?->order_number ?? '—',
                'staff' => $task->assignedStaff?->name ?? '—',
            ])
            ->all();

        // Orders with a delivery deadline within 48h that still have live
        // production tasks.
        $dueSoonOrders = Order::query()
            ->with('customer')
            ->where('status', 'REGISTERED')
            ->whereNotNull('expected_delivery_date')
            ->whereBetween('expected_delivery_date', [$now, $now->addDays(2)])
            ->whereHas('tasks', fn ($q) => $q->whereNotIn('status', $terminal))
            ->orderBy('expected_delivery_date')
            ->limit(6)
            ->get()
            ->map(static fn (Order $order): array => [
                'orderNumber' => $order->order_number,
                'customer' => $order->customer?->name ?? '—',
                'phone' => $order->customer?->phone ?? '—',
                'deliveryDate' => $order->expected_delivery_date?->format('d M Y'),
                'progress' => $order->productionProgress(),
            ])
            ->all();

        return [
            'overdueTasks' => $overdueTasks,
            'blockedTasks' => $blockedTasks,
            'dueSoonOrders' => $dueSoonOrders,
        ];
    }

    /**
     * Compact KPI snapshot stored on the send log so any past report can
     * be reconstructed without re-querying mutable tables.
     *
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public function summaryOf(array $report): array
    {
        $summary = [
            'reportDate' => $report['reportDate'] instanceof CarbonInterface ? $report['reportDate']->toDateString() : null,
            'paymentsIn' => round((float) ($report['today']['paymentsIn'] ?? 0), 2),
            'expensesOut' => round((float) ($report['today']['expensesOut'] ?? 0), 2),
            'piecesCompleted' => (int) ($report['today']['piecesCompleted'] ?? 0),
        ];

        if (is_array($report['production'])) {
            $summary['overdue'] = (int) ($report['production']['overdue'] ?? 0);
            $summary['ordersInProduction'] = (int) ($report['production']['ordersInProduction'] ?? 0);
        }

        if (is_array($report['financial'])) {
            $summary['paymentsMtd'] = round((float) ($report['financial']['paymentsMtd'] ?? 0), 2);
            $summary['expensesMtd'] = round((float) ($report['financial']['expensesMtd'] ?? 0), 2);
            $summary['outstandingBalances'] = round((float) ($report['financial']['outstandingBalances'] ?? 0), 2);
        }

        return $summary;
    }

    /**
     * Format an amount as XAF ("150,000 XAF") — the app's default currency.
     */
    public static function money(null|float|int $amount): string
    {
        return number_format((float) ($amount ?? 0), 0).' XAF';
    }
}
