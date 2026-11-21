<?php

namespace App\Services;

use App\Models\AccountingAccount;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\StaffAdvance;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Support\Atelier;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * OSSZ ATELIER — general accounting posting service
 * (Finance & Comptabilité spec v1.3 §6, SYSCOHADA referential).
 *
 * Journal entries are generated AUTOMATICALLY from the operations the
 * users record (payments, expenses, purchases, payroll, advances,
 * transfers, revenue at order registration) — the user never picks an
 * account (§6). The unique (source_type, source_id) index on
 * journal_entries guarantees an operation is never posted twice.
 * Manual entries (OD journal) are reserved for the Comptable role.
 *
 * Compact SYSCOHADA mapping (adjustable by the accountant):
 *   revenue (order registered)   DR 411 Clients        CR 701 Ventes
 *   customer payment             DR [571/521/522/585]  CR 411 Clients
 *   expense approved             DR [charge by group]  CR 401 Fournisseurs
 *   expense paid                 DR 401                CR [treasury]
 *   purchase payment             DR 6021 Achats        CR [treasury]
 *   payroll item paid            DR 6611 Rémunérations CR [treasury]
 *   advance disbursed            DR 421 Personnel      CR [treasury]
 *   treasury transfer            DR [dest account]     CR [source account]
 *   deposit / withdrawal         DR [account]/585      CR 585/[account]
 *
 * The final configuration and any official use of the produced
 * statements must be validated by a professional accountant (§6).
 */
final class AccountingPostService
{
    /**
     * SYSCOHADA treasury account per payment method.
     */
    private const METHOD_ACCOUNT = [
        'CASH' => '571',
        'BANK_TRANSFER' => '521',
        'MTN_MOMO' => '522',
        'ORANGE_MONEY' => '522',
        'OTHER' => '585',
    ];

    /**
     * SYSCOHADA charge account per expense-category group.
     */
    private const GROUP_ACCOUNT = [
        'MATERIALS' => '6021',
        'TRANSPORT' => '618',
        'OPERATIONS' => '624',
        'SALARIES' => '6611',
        'ADMIN' => '628',
        'OTHER' => '608',
    ];

    // ------------------------------------------------------------------
    // Automatic postings
    // ------------------------------------------------------------------

    /**
     * Revenue at order REGISTRATION (§6/§10): the invoice (order value)
     * and its collection are distinct events.
     */
    public static function postOrderRevenue(Order $order, ?User $by = null): ?JournalEntry
    {
        if ($order->status !== 'REGISTERED' || (float) $order->total_value <= 0) {
            return null;
        }

        return self::post(
            'VT',
            Carbon::parse($order->updated_at ?? $order->created_at),
            $order->order_number.' — '.__('gen.revenueEntry'),
            'ORDER',
            $order->id,
            [
                ['411', (float) $order->total_value, 0.0],
                ['701', 0.0, (float) $order->total_value],
            ],
            $by,
        );
    }

    /**
     * Customer payment collected (§4.2): treasury in, client debt down.
     */
    public static function postPayment(Payment $payment, ?User $by = null): ?JournalEntry
    {
        $treasuryCode = self::METHOD_ACCOUNT[$payment->method] ?? '585';
        $poster = $by ?? User::find($payment->recorded_by_id);

        return self::post(
            $payment->method === 'CASH' ? 'CS' : 'BQ',
            Carbon::parse($payment->payment_date),
            $payment->reference.' — '.$payment->order?->order_number,
            'PAYMENT',
            $payment->id,
            [
                [$treasuryCode, (float) $payment->amount, 0.0],
                ['411', 0.0, (float) $payment->amount],
            ],
            $poster,
        );
    }

    /**
     * Expense lifecycle: APPROVED books the charge against the supplier
     * debt (401); PAID settles it from treasury. An expense recorded
     * directly as PAID gets one combined entry.
     */
    public static function postExpense(Expense $expense, string $event, ?User $by = null): ?JournalEntry
    {
        $chargeCode = self::GROUP_ACCOUNT[$expense->category?->group ?? 'OTHER'] ?? '608';
        $treasuryCode = self::METHOD_ACCOUNT[$expense->method ?? 'CASH'] ?? '571';
        $label = $expense->reference.' — '.mb_substr($expense->description ?? '', 0, 60);

        if ($event === 'APPROVED' && $expense->status !== 'PAID') {
            return self::post('AC', Carbon::parse($expense->date_incurred), $label, 'EXPENSE_APPROVED', $expense->id, [
                [$chargeCode, (float) $expense->amount, 0.0],
                ['401', 0.0, (float) $expense->amount],
            ], $by);
        }

        if ($event === 'PAID') {
            // Direct-to-paid: single balanced entry.
            if ($expense->approved_at === null) {
                return self::post($expense->method === 'CASH' ? 'CS' : 'BQ', Carbon::parse($expense->date_paid ?? $expense->date_incurred), $label, 'EXPENSE_PAID', $expense->id, [
                    [$chargeCode, (float) $expense->amount, 0.0],
                    [$treasuryCode, 0.0, (float) $expense->amount],
                ], $by);
            }

            // Approved earlier → settle the 401 debt.
            return self::post($expense->method === 'CASH' ? 'CS' : 'BQ', Carbon::parse($expense->date_paid ?? $expense->date_incurred), $label, 'EXPENSE_PAID', $expense->id, [
                ['401', (float) $expense->amount, 0.0],
                [$treasuryCode, 0.0, (float) $expense->amount],
            ], $by);
        }

        return null;
    }

    /**
     * Supplier purchase payment (§4.6): the charge enters at payment
     * time (matches the app's single cost ledger — the auto-booked
     * expense is this same event, so no double counting).
     */
    public static function postPurchasePayment(PurchasePayment $purchasePayment, Purchase $purchase, ?User $by = null): ?JournalEntry
    {
        $treasuryCode = self::METHOD_ACCOUNT[$purchasePayment->method] ?? '571';
        $chargeCode = self::GROUP_ACCOUNT[$purchase->expenseCategory?->group ?? 'MATERIALS'] ?? '6021';

        return self::post(
            $purchasePayment->method === 'CASH' ? 'CS' : 'BQ',
            Carbon::parse($purchasePayment->payment_date),
            $purchase->reference.' — '.($purchase->supplier_name ?? __('gen.supplier')),
            'PURCHASE_PAYMENT',
            $purchasePayment->id,
            [
                [$chargeCode, (float) $purchasePayment->amount, 0.0],
                [$treasuryCode, 0.0, (float) $purchasePayment->amount],
            ],
            $by,
        );
    }

    /**
     * Supplier purchase paid directly (status flipped to PAID without
     * instalment rows — legacy/quick path): the charge enters once, from
     * treasury. Skipped when the purchase settles through purchase
     * payments (those post their own PURCHASE_PAYMENT entries — §10 no
     * double counting).
     */
    public static function postPurchaseSettled(Purchase $purchase, ?User $by = null): ?JournalEntry
    {
        if ($purchase->payment_status !== 'PAID' || $purchase->payments()->exists()) {
            return null;
        }

        $treasuryCode = self::METHOD_ACCOUNT[$purchase->method ?? 'CASH'] ?? '571';
        $chargeCode = self::GROUP_ACCOUNT[$purchase->expenseCategory?->group ?? 'MATERIALS'] ?? '6021';

        return self::post(
            $purchase->method === 'CASH' ? 'CS' : 'BQ',
            Carbon::parse($purchase->purchase_date),
            $purchase->reference.' — '.($purchase->supplier_name ?? __('gen.supplier')),
            'PURCHASE_SETTLED',
            $purchase->id,
            [
                [$chargeCode, (float) $purchase->total_amount, 0.0],
                [$treasuryCode, 0.0, (float) $purchase->total_amount],
            ],
            $by,
        );
    }

    /**
     * Payroll item paid (§4.7): remuneration charge from treasury.
     */
    public static function postPayrollItem(array $item, string $treasuryMethod, ?User $by = null): ?JournalEntry
    {
        $treasuryCode = self::METHOD_ACCOUNT[$treasuryMethod] ?? '571';

        return self::post(
            $treasuryMethod === 'CASH' ? 'CS' : 'BQ',
            Carbon::parse($item['payment_date'] ?? now()),
            __('gen.payrollEntry').' — '.($item['staff_name'] ?? ''),
            'PAYROLL_ITEM',
            $item['id'],
            [
                ['6611', (float) $item['net_pay'], 0.0],
                [$treasuryCode, 0.0, (float) $item['net_pay']],
            ],
            $by,
        );
    }

    /**
     * Staff advance disbursed (§4.7): personnel advance asset from
     * treasury (recovered later through payroll, §9 of the payroll spec).
     */
    public static function postAdvanceDisbursed(StaffAdvance $advance, string $treasuryMethod, ?User $by = null): ?JournalEntry
    {
        $treasuryCode = self::METHOD_ACCOUNT[$treasuryMethod] ?? '571';

        return self::post(
            $treasuryMethod === 'CASH' ? 'CS' : 'BQ',
            Carbon::parse($advance->updated_at ?? now()),
            __('gen.advanceEntry').' — '.$advance->staff_name,
            'ADVANCE',
            $advance->id,
            [
                ['421', (float) $advance->amount, 0.0],
                [$treasuryCode, 0.0, (float) $advance->amount],
            ],
            $by,
        );
    }

    /**
     * Treasury transfer (§4.4): destination debited, source credited —
     * neither a receipt nor an expense.
     */
    public static function postTransfer(array $transfer, ?User $by = null): ?JournalEntry
    {
        $fromCode = self::treasuryAccountCode($transfer['from_account_id'] ?? null);
        $toCode = self::treasuryAccountCode($transfer['to_account_id'] ?? null);

        return self::post(
            'OD',
            Carbon::parse($transfer['date']),
            __('gen.transferEntry').' — '.($transfer['from_name'] ?? '').' → '.($transfer['to_name'] ?? ''),
            'TREASURY_TRANSFER',
            $transfer['id'] ?? null,
            [
                [$toCode, (float) $transfer['amount'], 0.0],
                [$fromCode, 0.0, (float) $transfer['amount']],
            ],
            $by,
        );
    }

    /**
     * Manual deposit / withdrawal (§4.4): the counterpart goes through
     * 585 virements internes so the books stay balanced; the comptable
     * can reclassify from there.
     */
    public static function postManualMovement(array $movement, ?User $by = null): ?JournalEntry
    {
        $accountCode = self::treasuryAccountCode($movement['account_id'] ?? null);

        if ($movement['direction'] === 'IN') {
            $lines = [[$accountCode, (float) $movement['amount'], 0.0], ['585', 0.0, (float) $movement['amount']]];
        } else {
            $lines = [['585', (float) $movement['amount'], 0.0], [$accountCode, 0.0, (float) $movement['amount']]];
        }

        return self::post(
            'OD',
            Carbon::parse($movement['date']),
            __('gen.manualMovementEntry').' — '.($movement['account_name'] ?? ''),
            'TREASURY_MANUAL',
            $movement['id'] ?? null,
            $lines,
            $by,
        );
    }

    // ------------------------------------------------------------------
    // Manual entry (Comptable only, §6)
    // ------------------------------------------------------------------

    /**
     * Manual OD entry for diverse operations and corrections (§6) —
     * reserved for the accounting.journal permission (checked by the
     * controller BEFORE calling this). Debits must equal credits.
     *
     * @param list<array{code: string, label: ?string, debit: float, credit: float}> $lines
     */
    public static function manualEntry(
        CarbonInterface $date,
        string $description,
        array $lines,
        User $by,
        string $reference = null,
    ): JournalEntry {
        $debit = round(array_sum(array_map(static fn (array $l): float => (float) $l['debit'], $lines)), 2);
        $credit = round(array_sum(array_map(static fn (array $l): float => (float) $l['credit'], $lines)), 2);

        if (count($lines) < 2) {
            throw new InvalidArgumentException('an entry needs at least two lines');
        }

        if (abs($debit - $credit) >= 0.005) {
            throw new InvalidArgumentException(__('gen.unbalancedEntry'));
        }

        $mapped = array_map(static function (array $l): array {
            $account = AccountingAccount::byCode($l['code']);

            if ($account === null || ! $account->is_active) {
                throw new InvalidArgumentException(__('gen.unknownAccount', ['code' => $l['code']]));
            }

            return [$l['code'], (float) $l['debit'], (float) $l['credit'], $l['label'] ?? null];
        }, $lines);

        return self::post('OD', $date, $description, null, null, $mapped, $by, false, $reference);
    }

    // ------------------------------------------------------------------
    // History generation (retroactive)
    // ------------------------------------------------------------------

    /**
     * Generate journal entries from ALL recorded history (guarded
     * button for the comptable): every unposted order revenue, payment,
     * paid expense, purchase payment, paid payroll item, disbursed
     * advance and treasury movement. Idempotent — posted sources are
     * skipped by the unique (source_type, source_id) index.
     *
     * @return array{created: int, skipped: int}
     */
    public static function generateFromHistory(User $by): array
    {
        $created = 0;
        $skipped = 0;

        // Registered orders → revenue.
        Order::query()->where('status', 'REGISTERED')->chunk(200, static function ($orders) use (&$created, &$skipped, $by): void {
            foreach ($orders as $order) {
                $entry = self::alreadyPosted('ORDER', $order->id) ? null : self::postOrderRevenue($order, $by);
                $entry === null ? $skipped++ : $created++;
            }
        });

        // Payments.
        Payment::query()->chunk(200, static function ($payments) use (&$created, &$skipped, $by): void {
            foreach ($payments as $payment) {
                $entry = self::alreadyPosted('PAYMENT', $payment->id) ? null : self::postPayment($payment, $by);
                $entry === null ? $skipped++ : $created++;
            }
        });

        // Expenses (approved → charge/401; paid → settlement).
        // NOTE: purchase-sourced rows are skipped — their purchase settles
        // through purchase payments (or PURCHASE_SETTLED), never twice.
        Expense::query()
            ->with('category')
            ->where(static fn ($q) => $q->whereNull('source_type')->orWhere('source_type', '!=', 'PURCHASE'))
            ->chunk(200, static function ($expenses) use (&$created, &$skipped, $by): void {
            foreach ($expenses as $expense) {
                if (in_array($expense->status, Expense::COST_STATUSES, true)) {
                    if (! self::alreadyPosted('EXPENSE_APPROVED', $expense->id)) {
                        $entry = self::postExpense($expense, 'APPROVED', $by);
                        $entry === null ? $skipped++ : $created++;
                    } else {
                        $skipped++;
                    }

                    if ($expense->status === 'PAID' && ! self::alreadyPosted('EXPENSE_PAID', $expense->id)) {
                        $entry = self::postExpense($expense, 'PAID', $by);
                        $entry === null ? $skipped++ : $created++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }
            }
        });

        // Purchase payments.
        PurchasePayment::query()->with('purchase.expenseCategory')->chunk(200, static function ($payments) use (&$created, &$skipped, $by): void {
            foreach ($payments as $purchasePayment) {
                $entry = self::alreadyPosted('PURCHASE_PAYMENT', $purchasePayment->id)
                    ? null
                    : self::postPurchasePayment($purchasePayment, $purchasePayment->purchase, $by);
                $entry === null ? $skipped++ : $created++;
            }
        });

        // Purchases marked PAID without instalment rows (legacy path).
        Purchase::query()->where('payment_status', 'PAID')->chunk(200, static function ($purchases) use (&$created, &$skipped, $by): void {
            foreach ($purchases as $purchase) {
                $entry = self::alreadyPosted('PURCHASE_SETTLED', $purchase->id)
                    ? null
                    : self::postPurchaseSettled($purchase, $by);
                $entry === null ? $skipped++ : $created++;
            }
        });

        // Treasury manual movements + transfers.
        DB::table('treasury_movements')
            ->whereIn('kind', ['DEPOSIT', 'WITHDRAWAL'])
            ->orderBy('movement_date')
            ->get()
            ->each(static function ($movement) use (&$created, &$skipped, $by): void {
                if (self::alreadyPosted('TREASURY_MANUAL', $movement->id)) {
                    $skipped++;

                    return;
                }

                $entry = self::postManualMovement([
                    'id' => $movement->id,
                    'account_id' => $movement->account_id,
                    'account_name' => TreasuryAccount::find($movement->account_id)?->name,
                    'direction' => $movement->direction,
                    'amount' => (float) $movement->amount,
                    'date' => $movement->movement_date,
                ], $by);
                $entry === null ? $skipped++ : $created++;
            });

        DB::table('treasury_movements')
            ->where('kind', 'TRANSFER_OUT')
            ->orderBy('movement_date')
            ->get()
            ->each(static function ($out) use (&$created, &$skipped, $by): void {
                if (self::alreadyPosted('TREASURY_TRANSFER', $out->id)) {
                    $skipped++;

                    return;
                }

                $in = DB::table('treasury_movements')->where('kind', 'TRANSFER_IN')
                    ->where('movement_date', $out->movement_date)
                    ->where('amount', $out->amount)
                    ->first();

                $entry = self::postTransfer([
                    'id' => $out->id,
                    'from_account_id' => $out->account_id,
                    'to_account_id' => $in->account_id ?? null,
                    'from_name' => TreasuryAccount::find($out->account_id)?->name,
                    'to_name' => $in ? TreasuryAccount::find($in->account_id)?->name : null,
                    'amount' => (float) $out->amount,
                    'date' => $out->movement_date,
                ], $by);
                $entry === null ? $skipped++ : $created++;
            });

        Audit::log($by, 'JOURNAL_GENERATED_FROM_HISTORY', null, null, ['created' => $created, 'skipped' => $skipped]);

        return ['created' => $created, 'skipped' => $skipped];
    }

    // ------------------------------------------------------------------
    // Reports: grand livre + balance (§6)
    // ------------------------------------------------------------------

    /**
     * Grand livre: all journal lines of one account, chronological,
     * with the running balance (debit-positive).
     *
     * @return array{account: AccountingAccount, lines: list<array<string, mixed>>, totals: array{debit: float, credit: float}}
     */
    public static function generalLedger(AccountingAccount $account): array
    {
        $lines = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.entry_id')
            ->where('journal_lines.account_id', $account->id)
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.created_at')
            ->select('journal_entries.entry_date', 'journal_entries.journal', 'journal_entries.description',
                'journal_entries.reference', 'journal_lines.debit', 'journal_lines.credit', 'journal_lines.label')
            ->get();

        $running = 0.0;
        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $running += (float) $line->debit - (float) $line->credit;
            $totalDebit += (float) $line->debit;
            $totalCredit += (float) $line->credit;
            $rows[] = [
                'date' => $line->entry_date,
                'journal' => $line->journal,
                'description' => $line->description,
                'reference' => $line->reference,
                'label' => $line->label,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => round($running, 2),
            ];
        }

        return [
            'account' => $account,
            'lines' => $rows,
            'totals' => ['debit' => round($totalDebit, 2), 'credit' => round($totalCredit, 2)],
        ];
    }

    /**
     * Balance (trial balance): per-account Σ debit / Σ credit and the
     * global check that debits equal credits.
     *
     * @return array{rows: list<array<string, mixed>>, totals: array{debit: float, credit: float}, balanced: bool}
     */
    public static function trialBalance(): array
    {
        $rows = DB::table('journal_lines')
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'journal_lines.account_id')
            ->groupBy('accounting_accounts.id', 'accounting_accounts.code', 'accounting_accounts.name', 'accounting_accounts.type')
            ->orderBy('accounting_accounts.code')
            ->selectRaw('accounting_accounts.code, accounting_accounts.name, accounting_accounts.type,
                COALESCE(SUM(journal_lines.debit), 0) AS total_debit,
                COALESCE(SUM(journal_lines.credit), 0) AS total_credit')
            ->get();

        $mapped = $rows->map(static fn ($row): array => [
            'code' => $row->code,
            'name' => $row->name,
            'type' => $row->type,
            'debit' => round((float) $row->total_debit, 2),
            'credit' => round((float) $row->total_credit, 2),
            'balance' => round((float) $row->total_debit - (float) $row->total_credit, 2),
        ])->all();

        $debit = round(array_sum(array_column($mapped, 'debit')), 2);
        $credit = round(array_sum(array_column($mapped, 'credit')), 2);

        return [
            'rows' => $mapped,
            'totals' => ['debit' => $debit, 'credit' => $credit],
            'balanced' => abs($debit - $credit) < 0.005,
        ];
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /** Whether a source already has its journal entry. */
    public static function alreadyPosted(string $sourceType, ?string $sourceId): bool
    {
        if ($sourceId === null) {
            return false;
        }

        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    /**
     * SYSCOHADA code for a treasury account by its kind.
     */
    private static function treasuryAccountCode(?string $accountId): string
    {
        $account = $accountId ? TreasuryAccount::find($accountId) : null;

        return match ($account?->kind) {
            'CASH' => '571',
            'BANK' => '521',
            'MOMO' => '522',
            default => '585',
        };
    }

    /**
     * The single posting funnel: create a balanced entry with its
     * lines. Idempotent per (source_type, source_id) via the unique
     * index — duplicates collapse silently (null returned).
     *
     * @param list<array{0: string, 1: float, 2: float}|array{0: string, 1: float, 2: float, 3: ?string}> $lines
     */
    private static function post(
        string $journal,
        CarbonInterface $date,
        string $description,
        ?string $sourceType,
        ?string $sourceId,
        array $lines,
        ?User $by,
        bool $generated = true,
        ?string $reference = null,
    ): ?JournalEntry {
        if (! in_array($journal, Atelier::JOURNALS, true)) {
            throw new InvalidArgumentException("unknown journal [{$journal}]");
        }

        if ($sourceType !== null && $sourceId !== null && self::alreadyPosted($sourceType, $sourceId)) {
            return null; // already posted — never twice (§10 no double counting)
        }

        return DB::transaction(static function () use ($journal, $date, $description, $sourceType, $sourceId, $lines, $by, $generated, $reference): JournalEntry {
            $entry = JournalEntry::query()->create([
                'journal' => $journal,
                'entry_date' => $date,
                'reference' => $reference,
                'description' => mb_substr($description, 0, 300),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'is_generated' => $generated,
                'posted_by_id' => $by?->id,
                'posted_by_name' => $by?->name,
            ]);

            foreach ($lines as $line) {
                [$code, $debit, $credit] = $line;
                $label = $line[3] ?? null;

                $account = AccountingAccount::byCode($code);

                if ($account === null) {
                    throw new InvalidArgumentException("unknown SYSCOHADA account [{$code}]");
                }

                JournalLine::query()->create([
                    'entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'label' => $label,
                    'debit' => max(0.0, (float) $debit),
                    'credit' => max(0.0, (float) $credit),
                ]);
            }

            return $entry;
        });
    }
}
