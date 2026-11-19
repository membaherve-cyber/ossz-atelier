<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\ProductionTask;
use App\Models\Purchase;
use App\Models\StaffAdvance;
use App\Services\AccountingService;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Financial reports (enhancement spec §12) — GET /accounting/reports.
 *
 * Thirteen traceable reports, every figure computed from recorded
 * database rows (committed expenses = Expense::COST_STATUSES; salaries
 * from approved payrolls; outstanding balances derived, never stored).
 * The controller renders the shared table view with a fixed contract —
 * ['title', 'columns' (key => label), 'rows' (key => pre-formatted
 * strings), 'summary' (label => formatted)] — and streams the same
 * payload as a UTF-8 BOM CSV via ?export=csv.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class FinancialReportController
{
    /** Report types (spec §12 list, in order). */
    private const TYPES = [
        'daily',
        'monthly',
        'annual',
        'expensesByCategory',
        'transportation',
        'purchases',
        'payroll',
        'outstandingSalaries',
        'advances',
        'paymentHistory',
        'outstandingBalances',
        'profitability',
        'performance',
    ];

    /**
     * GET /accounting/reports (route perm accounting.reports) — run the
     * selected report over the selected period, render it (or stream the
     * CSV export / prepare the print view).
     */
    public function index(Request $request): View|StreamedResponse
    {
        $type = (string) ($request->query('type') ?? 'daily');

        if (! in_array($type, self::TYPES, true)) {
            abort(404);
        }

        $from = trim((string) ($request->query('from') ?? ''));
        $to = trim((string) ($request->query('to') ?? ''));

        $period = AccountingService::resolvePeriod(
            (string) ($request->query('period') ?? 'year'),
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
        );

        $report = $this->build($type, $period['from'], $period['to']);

        if ((string) $request->query('export') === 'csv') {
            $filename = 'atelier-'.$type.'-'.$period['from']->toDateString().'-'.$period['to']->toDateString().'.csv';

            return response()->streamDownload(function () use ($report): void {
                // UTF-8 BOM so Excel detects the encoding.
                echo "\xEF\xBB\xBF";

                $out = fopen('php://output', 'w');

                fputcsv($out, array_keys($report['columns']));

                foreach ($report['rows'] as $row) {
                    fputcsv($out, array_values($row));
                }

                foreach ($report['summary'] as $label => $value) {
                    fputcsv($out, [$label, $value]);
                }

                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return view('accounting.reports', [
            'report' => $report,
            'type' => $type,
            'types' => self::TYPES,
            'period' => $period,
            'print' => (string) $request->query('print') === '1',
        ]);
    }

    // ------------------------------------------------------------------
    // Report builders (spec §12) — every cell pre-formatted as a string
    // ------------------------------------------------------------------

    /**
     * Build the report payload for one of the self::TYPES over the
     * inclusive period (spec §12).
     *
     * @return array{title: string, columns: array<string, string>, rows: list<array<string, string>>, summary: array<string, string>}
     */
    private function build(string $type, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return match ($type) {
            'daily' => $this->dailyReport($from, $to),
            'monthly' => $this->monthlyReport($from, $to),
            'annual' => $this->annualReport($from, $to),
            'expensesByCategory' => $this->expensesByCategoryReport($from, $to),
            'transportation' => $this->transportationReport($from, $to),
            'purchases' => $this->purchasesReport($from, $to),
            'payroll' => $this->payrollReport($from, $to),
            'outstandingSalaries' => $this->outstandingSalariesReport($from, $to),
            'advances' => $this->advancesReport($from, $to),
            'paymentHistory' => $this->paymentHistoryReport($from, $to),
            'outstandingBalances' => $this->outstandingBalancesReport($from, $to),
            'profitability' => $this->profitabilityReport($from, $to),
            'performance' => $this->performanceReport($from, $to),
            default => throw new \InvalidArgumentException("Unknown report type [{$type}]."),
        };
    }

    /**
     * @param  array<string, string>  $columns
     * @param  list<array<string, string>>  $rows
     * @param  array<string, string>  $summary
     * @return array{title: string, columns: array<string, string>, rows: list<array<string, string>>, summary: array<string, string>}
     */
    private function report(string $type, array $columns, array $rows, array $summary): array
    {
        return [
            'title' => __('frep.type.'.$type),
            'columns' => $columns,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * Daily Income and Expenses — union of days with payments (income)
     * and committed expenses (by date incurred), chronological.
     */
    private function dailyReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $income = Payment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m-%d') AS d, SUM(amount) AS total")
            ->groupBy('d')->pluck('total', 'd');

        $expenses = Expense::query()
            ->whereIn('status', Expense::COST_STATUSES)
            ->whereBetween('date_incurred', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_FORMAT(date_incurred, '%Y-%m-%d') AS d, SUM(amount) AS total")
            ->groupBy('d')->pluck('total', 'd');

        $days = $income->keys()->merge($expenses->keys())->unique()->sort()->values();

        $rows = [];
        $totalIncome = 0.0;
        $totalExpenses = 0.0;

        foreach ($days as $day) {
            $dayIncome = (float) ($income[$day] ?? 0);
            $dayExpenses = (float) ($expenses[$day] ?? 0);

            $totalIncome += $dayIncome;
            $totalExpenses += $dayExpenses;

            $rows[] = [
                'period' => $this->formatDate(Carbon::parse((string) $day)),
                'income' => Money::amount($dayIncome),
                'expenses' => Money::amount($dayExpenses),
                'net' => Money::amount($dayIncome - $dayExpenses),
            ];
        }

        return $this->report('daily', [
            'period' => __('frep.colPeriod'),
            'income' => __('frep.colIncome'),
            'expenses' => __('frep.colExpenses'),
            'net' => __('frep.colNet'),
        ], $rows, [
            __('frep.colIncome') => Money::amount($totalIncome),
            __('frep.colExpenses') => Money::amount($totalExpenses),
            __('frep.colNet') => Money::amount($totalIncome - $totalExpenses),
        ]);
    }

    /**
     * Monthly Income and Expenses — AccountingService::monthlySeries
     * buckets with income / expenses / salaries / net.
     */
    private function monthlyReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = [];

        foreach (AccountingService::monthlySeries($from, $to) as $bucket) {
            $rows[] = [
                'period' => $bucket['label'],
                'income' => Money::amount($bucket['payments']),
                'expenses' => Money::amount($bucket['expenses']),
                'salaries' => Money::amount($bucket['salaries']),
                'net' => Money::amount($bucket['net']),
            ];
        }

        // Summary totals come straight from the database over the FULL
        // selected range — the chart buckets above are clamped to the last
        // 24 months for long ranges, so summing them would under-report.
        $totalIncome = (float) Payment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $totalExpenses = (float) Expense::query()
            ->whereIn('status', Expense::COST_STATUSES)
            ->whereBetween('date_incurred', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $totalSalaries = (float) PayrollItem::query()
            ->whereIn('payment_status', ['PENDING', 'PAID'])
            ->whereHas('payroll', fn ($q) => $q->whereIn('status', ['APPROVED', 'PARTIALLY_PAID', 'PAID'])
                ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()]))
            ->sum('net_pay');

        return $this->report('monthly', [
            'period' => __('frep.colPeriod'),
            'income' => __('frep.colIncome'),
            'expenses' => __('frep.colExpenses'),
            'salaries' => __('frep.colSalaries'),
            'net' => __('frep.colNet'),
        ], $rows, [
            __('frep.colIncome') => Money::amount($totalIncome),
            __('frep.colExpenses') => Money::amount($totalExpenses),
            __('frep.colSalaries') => Money::amount($totalSalaries),
            __('frep.colNet') => Money::amount($totalIncome - $totalExpenses - $totalSalaries),
        ]);
    }

    /**
     * Annual Financial Summary — per year: revenue (registered order
     * value), income (payments), expenses, salaries and net.
     */
    private function annualReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $revenue = Order::query()
            ->where('status', 'REGISTERED')
            ->whereBetween('order_date', [$from, $to])
            ->whereNotNull('total_value')
            ->selectRaw("DATE_FORMAT(order_date, '%Y') AS y, SUM(total_value) AS total")
            ->groupBy('y')->pluck('total', 'y');

        $income = Payment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_FORMAT(payment_date, '%Y') AS y, SUM(amount) AS total")
            ->groupBy('y')->pluck('total', 'y');

        $expenses = Expense::query()
            ->whereIn('status', Expense::COST_STATUSES)
            ->whereBetween('date_incurred', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_FORMAT(date_incurred, '%Y') AS y, SUM(amount) AS total")
            ->groupBy('y')->pluck('total', 'y');

        $salaries = PayrollItem::query()
            ->whereIn('payment_status', ['PENDING', 'PAID'])
            ->whereHas('payroll', fn ($q) => $q->whereIn('status', ['APPROVED', 'PARTIALLY_PAID', 'PAID'])
                ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()]))
            ->selectRaw("DATE_FORMAT((SELECT period_start FROM payrolls WHERE payrolls.id = payroll_items.payroll_id), '%Y') AS y, SUM(net_pay) AS total")
            ->groupBy('y')->pluck('total', 'y');

        $years = $revenue->keys()
            ->merge($income->keys())
            ->merge($expenses->keys())
            ->merge($salaries->keys())
            ->unique()
            ->sort()
            ->values();

        $rows = [];
        $totalRevenue = 0.0;
        $totalIncome = 0.0;
        $totalExpenses = 0.0;
        $totalSalaries = 0.0;

        foreach ($years as $year) {
            $yearRevenue = (float) ($revenue[$year] ?? 0);
            $yearIncome = (float) ($income[$year] ?? 0);
            $yearExpenses = (float) ($expenses[$year] ?? 0);
            $yearSalaries = (float) ($salaries[$year] ?? 0);

            $totalRevenue += $yearRevenue;
            $totalIncome += $yearIncome;
            $totalExpenses += $yearExpenses;
            $totalSalaries += $yearSalaries;

            $rows[] = [
                'year' => (string) $year,
                'revenue' => Money::amount($yearRevenue),
                'income' => Money::amount($yearIncome),
                'expenses' => Money::amount($yearExpenses),
                'salaries' => Money::amount($yearSalaries),
                'net' => Money::amount($yearRevenue - $yearExpenses - $yearSalaries),
            ];
        }

        return $this->report('annual', [
            'year' => __('frep.colYear'),
            'revenue' => __('frep.colRevenue'),
            'income' => __('frep.colIncome'),
            'expenses' => __('frep.colExpenses'),
            'salaries' => __('frep.colSalaries'),
            'net' => __('frep.colNet'),
        ], $rows, [
            __('frep.colRevenue') => Money::amount($totalRevenue),
            __('frep.colIncome') => Money::amount($totalIncome),
            __('frep.colExpenses') => Money::amount($totalExpenses),
            __('frep.colSalaries') => Money::amount($totalSalaries),
            __('frep.colNet') => Money::amount($totalRevenue - $totalExpenses - $totalSalaries),
        ]);
    }

    /**
     * Expense Breakdown by Category — committed expenses grouped by
     * category (with its group and entry count), largest first.
     */
    private function expensesByCategoryReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = Expense::query()
            ->whereIn('expenses.status', Expense::COST_STATUSES)
            ->whereBetween('expenses.date_incurred', [$from->toDateString(), $to->toDateString()])
            ->join('expense_categories AS c', 'c.id', '=', 'expenses.expense_category_id')
            ->groupBy('c.id', 'c.name', 'c.group')
            ->orderByDesc('total')
            ->get(['c.name', 'c.group', DB::raw('COUNT(*) AS entries'), DB::raw('SUM(expenses.amount) AS total')]);

        $reportRows = [];
        $total = 0.0;

        foreach ($rows as $row) {
            $amount = (float) $row->total;
            $total += $amount;

            $reportRows[] = [
                'category' => (string) $row->name,
                'group' => __('labels.expGroup.'.((string) $row->group)),
                'count' => (string) (int) $row->entries,
                'amount' => Money::amount($amount),
            ];
        }

        return $this->report('expensesByCategory', [
            'category' => __('frep.colCategory'),
            'group' => __('frep.colGroup'),
            'count' => __('frep.colCount'),
            'amount' => __('frep.colAmount'),
        ], $reportRows, [
            __('frep.totalRow') => Money::amount($total),
        ]);
    }

    /**
     * Transportation Expenses — committed expenses whose category belongs
     * to the TRANSPORT group, newest first.
     */
    private function transportationReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $expenses = Expense::query()
            ->with(['order'])
            ->whereIn('status', Expense::COST_STATUSES)
            ->whereBetween('date_incurred', [$from->toDateString(), $to->toDateString()])
            ->whereHas('category', fn ($q) => $q->where('group', 'TRANSPORT'))
            ->orderByDesc('date_incurred')
            ->orderByDesc('created_at')
            ->get();

        $rows = [];
        $total = 0.0;

        foreach ($expenses as $expense) {
            $total += (float) $expense->amount;

            $rows[] = [
                'date' => $this->formatDate($expense->date_incurred),
                'reference' => (string) $expense->reference,
                'description' => (string) $expense->description,
                'order' => $expense->order?->order_number ?? '—',
                'amount' => Money::amount($expense->amount),
            ];
        }

        return $this->report('transportation', [
            'date' => __('frep.colDate'),
            'reference' => __('frep.colReference'),
            'description' => __('frep.colDescription'),
            'order' => __('frep.colOrder'),
            'amount' => __('frep.colAmount'),
        ], $rows, [
            __('frep.totalRow') => Money::amount($total),
        ]);
    }

    /**
     * Material Purchases — all purchases in the period with their payment
     * status, newest first.
     */
    private function purchasesReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $purchases = Purchase::query()
            ->with(['order'])
            ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('purchase_date')
            ->orderByDesc('created_at')
            ->get();

        $rows = [];
        $total = 0.0;

        foreach ($purchases as $purchase) {
            $total += (float) $purchase->total_amount;

            $rows[] = [
                'date' => $this->formatDate($purchase->purchase_date),
                'reference' => (string) $purchase->reference,
                'supplier' => (string) $purchase->supplier_name,
                'description' => (string) $purchase->description,
                'order' => $purchase->order?->order_number ?? '—',
                'paymentStatus' => __('labels.purchPayStatus.'.((string) $purchase->payment_status)),
                'total' => Money::amount($purchase->total_amount),
            ];
        }

        return $this->report('purchases', [
            'date' => __('frep.colDate'),
            'reference' => __('frep.colReference'),
            'supplier' => __('frep.colSupplier'),
            'description' => __('frep.colDescription'),
            'order' => __('frep.colOrder'),
            'paymentStatus' => __('frep.colPaymentStatus'),
            'total' => __('frep.colTotal'),
        ], $rows, [
            __('frep.totalRow') => Money::amount($total),
        ]);
    }

    /**
     * Monthly Payroll Expenditure — every line of the approved payrolls
     * whose period starts in the range, with payment state and date.
     */
    private function payrollReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $payrolls = Payroll::query()
            ->whereIn('status', ['APPROVED', 'PARTIALLY_PAID', 'PAID'])
            ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])
            ->with(['items' => fn ($q) => $q->whereIn('payment_status', ['PENDING', 'PAID'])->orderBy('staff_name')])
            ->orderByDesc('period_start')
            ->get();

        $rows = [];
        $total = 0.0;

        foreach ($payrolls as $payroll) {
            foreach ($payroll->items as $item) {
                $total += (float) $item->net_pay;

                $rows[] = [
                    'payroll' => (string) $payroll->label,
                    'period' => $this->formatDate($payroll->period_start).' – '.$this->formatDate($payroll->period_end),
                    'staff' => (string) $item->staff_name,
                    'netPay' => Money::amount($item->net_pay),
                    'paymentStatus' => __('labels.prlPayStatus.'.((string) $item->payment_status)),
                    'paymentDate' => $this->formatDate($item->payment_date),
                ];
            }
        }

        return $this->report('payroll', [
            'payroll' => __('frep.colPayroll'),
            'period' => __('frep.colPeriod'),
            'staff' => __('frep.colStaff'),
            'netPay' => __('frep.colNetPay'),
            'paymentStatus' => __('frep.colPaymentStatus'),
            'paymentDate' => __('frep.colPaymentDate'),
        ], $rows, [
            __('frep.totalRow') => Money::amount($total),
        ]);
    }

    /**
     * Outstanding Salary Payments — still-PENDING lines of the approved /
     * partially-paid payrolls in the range.
     */
    private function outstandingSalariesReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $payrolls = Payroll::query()
            ->whereIn('status', ['APPROVED', 'PARTIALLY_PAID'])
            ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])
            ->with(['items' => fn ($q) => $q->where('payment_status', 'PENDING')->orderBy('staff_name')])
            ->orderByDesc('period_start')
            ->get();

        $rows = [];
        $total = 0.0;

        foreach ($payrolls as $payroll) {
            foreach ($payroll->items as $item) {
                $total += (float) $item->net_pay;

                $rows[] = [
                    'payroll' => (string) $payroll->label,
                    'staff' => (string) $item->staff_name,
                    'netPay' => Money::amount($item->net_pay),
                ];
            }
        }

        return $this->report('outstandingSalaries', [
            'payroll' => __('frep.colPayroll'),
            'staff' => __('frep.colStaff'),
            'netPay' => __('frep.colNetPay'),
        ], $rows, [
            __('frep.totalRow') => Money::amount($total),
        ]);
    }

    /**
     * Staff Advances — every advance issued in the range (status shown
     * next to the reference), with its outstanding balance.
     */
    private function advancesReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $advances = StaffAdvance::query()
            ->whereBetween('date_issued', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('date_issued')
            ->orderByDesc('created_at')
            ->get();

        $rows = [];
        $totalAmount = 0.0;
        $totalOutstanding = 0.0;

        foreach ($advances as $advance) {
            $outstanding = $advance->outstanding();

            // Only advances whose money actually left the workshop count
            // towards the disbursed total — PENDING_APPROVAL / APPROVED /
            // CANCELLED rows are listed for visibility but never issued.
            if (in_array((string) $advance->status, StaffAdvance::DISBURSED_STATUSES, true)) {
                $totalAmount += (float) $advance->amount;
            }

            $totalOutstanding += $outstanding;

            $rows[] = [
                'reference' => $advance->reference.' · '.__('labels.advStatus.'.((string) $advance->status)),
                'staff' => (string) $advance->staff_name,
                'amount' => Money::amount($advance->amount),
                'outstanding' => Money::amount($outstanding),
                'date' => $this->formatDate($advance->date_issued),
            ];
        }

        return $this->report('advances', [
            'reference' => __('frep.colReference'),
            'staff' => __('frep.colStaff'),
            'amount' => __('frep.colAmount'),
            'outstanding' => __('frep.colOutstanding'),
            'date' => __('frep.colDate'),
        ], $rows, [
            __('frep.colAmount') => Money::amount($totalAmount),
            __('frep.colOutstanding') => Money::amount($totalOutstanding),
        ]);
    }

    /**
     * Customer Payment History — every payment received in the range,
     * newest first.
     */
    private function paymentHistoryReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $payments = Payment::query()
            ->with(['order', 'customer'])
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->get();

        $rows = [];
        $total = 0.0;

        foreach ($payments as $payment) {
            $total += (float) $payment->amount;

            $rows[] = [
                'date' => $this->formatDate($payment->payment_date),
                'reference' => (string) $payment->reference,
                'order' => $payment->order?->order_number ?? '—',
                'customer' => $payment->customer?->name ?? $payment->order?->customer?->name ?? '—',
                'method' => __('labels.payMethod.'.((string) $payment->method)),
                'amount' => Money::amount($payment->amount),
            ];
        }

        return $this->report('paymentHistory', [
            'date' => __('frep.colDate'),
            'reference' => __('frep.colReference'),
            'order' => __('frep.colOrder'),
            'customer' => __('frep.colCustomer'),
            'method' => __('frep.colMethod'),
            'amount' => __('frep.colAmount'),
        ], $rows, [
            __('frep.totalRow') => Money::amount($total),
        ]);
    }

    /**
     * Outstanding Customer Balances — REGISTERED orders with a recorded
     * value in the range, value vs paid vs outstanding.
     */
    private function outstandingBalancesReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = Order::query()
            ->where('status', 'REGISTERED')
            ->whereNotNull('total_value')
            ->whereBetween('order_date', [$from, $to])
            ->with('customer')
            ->withSum('payments as paid_total', 'amount')
            ->orderByDesc('order_date')
            ->get();

        $rows = [];
        $totalValue = 0.0;
        $totalPaid = 0.0;
        $totalOutstanding = 0.0;

        foreach ($orders as $order) {
            $value = (float) $order->total_value;
            $paid = (float) ($order->paid_total ?? 0);
            $outstanding = max(0.0, $value - $paid);

            $totalValue += $value;
            $totalPaid += $paid;
            $totalOutstanding += $outstanding;

            $rows[] = [
                'order' => (string) $order->order_number,
                'customer' => $order->customer?->name ?? '—',
                'value' => Money::amount($value),
                'paid' => Money::amount($paid),
                'outstanding' => Money::amount($outstanding),
            ];
        }

        return $this->report('outstandingBalances', [
            'order' => __('frep.colOrder'),
            'customer' => __('frep.colCustomer'),
            'value' => __('frep.colValue'),
            'paid' => __('frep.colPaid'),
            'outstanding' => __('frep.colOutstanding'),
        ], $rows, [
            __('frep.colValue') => Money::amount($totalValue),
            __('frep.colPaid') => Money::amount($totalPaid),
            __('frep.colOutstanding') => Money::amount($totalOutstanding),
        ]);
    }

    /**
     * Order-level Profitability — AccountingService::orderProfitability
     * rows (attributable materials / transport / other / allocated
     * labour per order, spec §11).
     */
    private function profitabilityReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = [];
        $totalValue = 0.0;
        $totalPaid = 0.0;
        $totalCost = 0.0;
        $totalContribution = 0.0;

        foreach (AccountingService::orderProfitability($from, $to) as $row) {
            /** @var Order $order */
            $order = $row['order'];

            $totalValue += (float) $row['value'];
            $totalPaid += (float) $row['paid'];
            $totalCost += (float) $row['totalCost'];
            $totalContribution += (float) $row['contribution'];

            $rows[] = [
                'order' => (string) $order->order_number,
                'customer' => $order->customer?->name ?? '—',
                'value' => Money::amount($row['value']),
                'paid' => Money::amount($row['paid']),
                'materials' => Money::amount($row['materials']),
                'transport' => Money::amount($row['transport']),
                'other' => Money::amount($row['other']),
                'labour' => Money::amount($row['labour']),
                'totalCost' => Money::amount($row['totalCost']),
                'contribution' => Money::amount($row['contribution']),
            ];
        }

        return $this->report('profitability', [
            'order' => __('frep.colOrder'),
            'customer' => __('frep.colCustomer'),
            'value' => __('frep.colValue'),
            'paid' => __('frep.colPaid'),
            'materials' => __('frep.colMaterials'),
            'transport' => __('frep.colTransport'),
            'other' => __('frep.colOther'),
            'labour' => __('frep.colLabour'),
            'totalCost' => __('frep.colTotalCost'),
            'contribution' => __('frep.colContribution'),
        ], $rows, [
            __('frep.colValue') => Money::amount($totalValue),
            __('frep.colPaid') => Money::amount($totalPaid),
            __('frep.colTotalCost') => Money::amount($totalCost),
            __('frep.colContribution') => Money::amount($totalContribution),
        ]);
    }

    /**
     * Workshop Operating Performance — monthly financial series plus the
     * monthly operational counts: orders registered (by order date),
     * orders with a delivery in the month and tasks delivered in the
     * month (by delivered_at).
     */
    private function performanceReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $ordersByMonth = Order::query()
            ->where('status', 'REGISTERED')
            ->whereBetween('order_date', [$from, $to])
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') AS ym, COUNT(*) AS total")
            ->groupBy('ym')->pluck('total', 'ym');

        $tasksByMonth = ProductionTask::query()
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(delivered_at, '%Y-%m') AS ym, COUNT(*) AS total")
            ->groupBy('ym')->pluck('total', 'ym');

        $deliveredOrdersByMonth = ProductionTask::query()
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(delivered_at, '%Y-%m') AS ym, COUNT(DISTINCT order_id) AS total")
            ->groupBy('ym')->pluck('total', 'ym');

        $rows = [];

        // Summary totals come straight from the database over the FULL
        // selected range — the chart buckets below are clamped to the last
        // 24 months for long ranges, so summing them would under-report.
        $totalRevenue = (float) Order::query()
            ->where('status', 'REGISTERED')
            ->whereBetween('order_date', [$from, $to])
            ->sum('total_value');

        $totalIncome = (float) Payment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $totalExpenses = (float) Expense::query()
            ->whereIn('status', Expense::COST_STATUSES)
            ->whereBetween('date_incurred', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $totalSalaries = (float) PayrollItem::query()
            ->whereIn('payment_status', ['PENDING', 'PAID'])
            ->whereHas('payroll', fn ($q) => $q->whereIn('status', ['APPROVED', 'PARTIALLY_PAID', 'PAID'])
                ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()]))
            ->sum('net_pay');

        $totalOrders = (int) Order::query()
            ->where('status', 'REGISTERED')
            ->whereBetween('order_date', [$from, $to])
            ->count();

        $totalTasks = (int) ProductionTask::query()
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to])
            ->count();

        $totalDelivered = (int) ProductionTask::query()
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to])
            ->distinct('order_id')
            ->count('order_id');

        foreach (AccountingService::monthlySeries($from, $to) as $bucket) {
            $key = $bucket['key'];

            $orders = (int) ($ordersByMonth[$key] ?? 0);
            $delivered = (int) ($deliveredOrdersByMonth[$key] ?? 0);
            $tasks = (int) ($tasksByMonth[$key] ?? 0);

            $rows[] = [
                'period' => $bucket['label'],
                'revenue' => Money::amount($bucket['revenue']),
                'income' => Money::amount($bucket['payments']),
                'expenses' => Money::amount($bucket['expenses']),
                'salaries' => Money::amount($bucket['salaries']),
                'net' => Money::amount($bucket['net']),
                'orders' => (string) $orders,
                'delivered' => (string) $delivered,
                'tasksCompleted' => (string) $tasks,
            ];
        }

        return $this->report('performance', [
            'period' => __('frep.colPeriod'),
            'revenue' => __('frep.colRevenue'),
            'income' => __('frep.colIncome'),
            'expenses' => __('frep.colExpenses'),
            'salaries' => __('frep.colSalaries'),
            'net' => __('frep.colNet'),
            'orders' => __('frep.colOrders'),
            'delivered' => __('frep.colDelivered'),
            'tasksCompleted' => __('frep.colTasksCompleted'),
        ], $rows, [
            __('frep.colRevenue') => Money::amount($totalRevenue),
            __('frep.colIncome') => Money::amount($totalIncome),
            __('frep.colExpenses') => Money::amount($totalExpenses),
            __('frep.colSalaries') => Money::amount($totalSalaries),
            __('frep.colNet') => Money::amount($totalRevenue - $totalExpenses - $totalSalaries),
            __('frep.colOrders') => (string) $totalOrders,
            __('frep.colDelivered') => (string) $totalDelivered,
            __('frep.colTasksCompleted') => (string) $totalTasks,
        ]);
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Locale-aware date cell ("3 Feb 2027"); null renders an em dash.
     */
    private function formatDate(?Carbon $date): string
    {
        if ($date === null) {
            return '—';
        }

        return $date->locale(app()->getLocale())->translatedFormat('j M Y');
    }
}
