<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\PurchasePayment;
use App\Models\TreasuryAccount;
use App\Models\TreasuryMovement;
use App\Models\User;
use App\Support\Atelier;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * OSSZ ATELIER — treasury service (Finance & Comptabilité spec v1.3 §4.4).
 *
 * Multi-account treasury: caisse, bank accounts, MTN Mobile Money,
 * Orange Money and any other configured account. Balances are always
 * DERIVED from the movement ledger (opening + Σ IN − Σ OUT), never
 * stored. An internal transfer posts two legs and is neither a receipt
 * nor an expense (§4.4).
 *
 * The recording helpers (recordForPayment, recordForExpense, …) are the
 * single funnel every money event flows through, so the cash journal
 * stays complete and consistent.
 */
final class TreasuryService
{
    /**
     * All active accounts with their derived balance attached
     * (->balance attribute), plus the grand total.
     *
     * @return array{accounts: list<TreasuryAccount>, total: float}
     */
    public static function overview(): array
    {
        $accounts = TreasuryAccount::query()
            ->orderBy('kind')
            ->orderBy('name')
            ->get()
            ->each(static function (TreasuryAccount $account): void {
                $account->setAttribute('balance', $account->balance());
            });

        return [
            'accounts' => $accounts->all(),
            'total' => round($accounts->sum('balance'), 2),
        ];
    }

    /**
     * The treasury balance at a given moment (opening + movements up to
     * that date) across ALL accounts — used by dashboards and reports.
     */
    public static function balanceUntil(CarbonInterface $at): float
    {
        $accounts = TreasuryAccount::query()->get(['id', 'opening_balance']);

        $in = (float) TreasuryMovement::query()
            ->whereIn('account_id', $accounts->pluck('id'))
            ->where('direction', 'IN')
            ->where('movement_date', '<=', $at)
            ->sum('amount');

        $out = (float) TreasuryMovement::query()
            ->whereIn('account_id', $accounts->pluck('id'))
            ->where('direction', 'OUT')
            ->where('movement_date', '<=', $at)
            ->sum('amount');

        return round($accounts->sum('opening_balance') + $in - $out, 2);
    }

    /**
     * The default active account serving a payment method (§4.4) — the
     * account a payment lands on when none was picked explicitly.
     */
    public static function accountForMethod(string $method): ?TreasuryAccount
    {
        return TreasuryAccount::query()
            ->active()
            ->where('method', $method)
            ->orderBy('name')
            ->first();
    }

    /**
     * Record a customer payment into its account (§4.2 → §4.4). Uses
     * the explicitly picked account, else the method's default account.
     */
    public static function recordForPayment(Payment $payment): ?TreasuryMovement
    {
        $account = $payment->treasuryAccount
            ?? self::accountForMethod($payment->method);

        if ($account === null) {
            return null; // no account serves this method yet — nothing to book
        }

        return self::record(
            $account,
            'IN',
            'RECEIPT',
            (float) $payment->amount,
            Carbon::parse($payment->payment_date),
            'PAYMENT',
            $payment->id,
            trim(($payment->customer?->name ?? '').' — '.$payment->order?->order_number, ' —'),
            $payment->reference,
            $payment->recorded_by_id,
            $payment->recorded_by_name,
        );
    }

    /**
     * Record a paid expense out of its account (§4.3 → §4.4).
     */
    public static function recordForExpense(Expense $expense): ?TreasuryMovement
    {
        if (! in_array($expense->status, ['PAID'], true) || $expense->date_paid === null) {
            return null;
        }

        $account = self::accountForMethod($expense->method ?? 'CASH');

        if ($account === null) {
            return null;
        }

        return self::record(
            $account,
            'OUT',
            'EXPENSE',
            (float) $expense->amount,
            Carbon::parse($expense->date_paid),
            'EXPENSE',
            $expense->id,
            $expense->payee ?? $expense->category?->name,
            $expense->reference,
            $expense->recorded_by_id,
            $expense->recorded_by_name,
        );
    }

    /**
     * Record a supplier purchase payment (§4.6 → §4.4).
     */
    public static function recordForPurchasePayment(PurchasePayment $purchasePayment, string $supplierName): ?TreasuryMovement
    {
        $account = $purchasePayment->treasuryAccount
            ?? self::accountForMethod($purchasePayment->method);

        if ($account === null) {
            return null;
        }

        return self::record(
            $account,
            'OUT',
            'PURCHASE',
            (float) $purchasePayment->amount,
            Carbon::parse($purchasePayment->payment_date),
            'PURCHASE_PAYMENT',
            $purchasePayment->id,
            $supplierName,
            $purchasePayment->reference,
            $purchasePayment->recorded_by_id,
            $purchasePayment->recorded_by_name,
        );
    }

    /**
     * Internal transfer between two accounts (§4.4): neither a receipt
     * nor an expense — two legs (TRANSFER_OUT + TRANSFER_IN) inside one
     * transaction, so the journal can never show a one-legged transfer.
     *
     * @return array{out: TreasuryMovement, in: TreasuryMovement}
     */
    public static function transfer(
        TreasuryAccount $from,
        TreasuryAccount $to,
        float $amount,
        CarbonInterface $date,
        ?string $note,
        ?User $by,
    ): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException('treasury transfer amount must be positive');
        }

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('treasury transfer needs two different accounts');
        }

        return DB::transaction(function () use ($from, $to, $amount, $date, $note, $by): array {
            $out = self::record($from, 'OUT', 'TRANSFER_OUT', $amount, $date, 'TRANSFER', null, $to->name, $note, $by?->id, $by?->name);
            $in = self::record($to, 'IN', 'TRANSFER_IN', $amount, $date, 'TRANSFER', null, $from->name, $note, $by?->id, $by?->name);

            return ['out' => $out, 'in' => $in];
        });
    }

    /**
     * Manual deposit / withdrawal (§4.4 "entrées et sorties de fonds").
     */
    public static function manual(
        TreasuryAccount $account,
        string $direction, // IN | OUT
        float $amount,
        CarbonInterface $date,
        ?string $note,
        ?User $by,
    ): TreasuryMovement {
        if (! in_array($direction, ['IN', 'OUT'], true)) {
            throw new InvalidArgumentException('direction must be IN or OUT');
        }

        return self::record(
            $account,
            $direction,
            $direction === 'IN' ? 'DEPOSIT' : 'WITHDRAWAL',
            $amount,
            $date,
            null,
            null,
            null,
            $note,
            $by?->id,
            $by?->name,
        );
    }

    /**
     * The single funnel: append one movement row (the cash journal).
     */
    public static function record(
        TreasuryAccount $account,
        string $direction,
        string $kind,
        float $amount,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $counterparty = null,
        ?string $note = null,
        ?int $recordedById = null,
        ?string $recordedByName = null,
    ): TreasuryMovement {
        if (! in_array($direction, ['IN', 'OUT'], true)) {
            throw new InvalidArgumentException('direction must be IN or OUT');
        }

        if (! in_array($kind, Atelier::TREASURY_MOVEMENT_KINDS, true)) {
            throw new InvalidArgumentException("unknown treasury movement kind [{$kind}]");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('amount must be positive');
        }

        return TreasuryMovement::query()->create([
            'account_id' => $account->id,
            'direction' => $direction,
            'kind' => $kind,
            'amount' => $amount,
            'movement_date' => $date,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'counterparty' => $counterparty,
            'note' => $note,
            'recorded_by_id' => $recordedById,
            'recorded_by_name' => $recordedByName,
        ]);
    }

    /**
     * Daily journal (§4.4 "journal de caisse / clôture journalière"):
     * movements grouped by date with per-day in/out subtotals.
     *
     * @return array<int, array{date: string, in: float, out: float, movements: list<TreasuryMovement>}>
     */
    public static function journalByDay(int $limit = 15): array
    {
        $movements = TreasuryMovement::query()
            ->with('account:id,name')
            ->orderByDesc('movement_date')
            ->orderByDesc('created_at')
            ->limit($limit * 40)
            ->get();

        $days = [];

        foreach ($movements as $movement) {
            $key = $movement->movement_date->toDateString();

            if (! isset($days[$key])) {
                $days[$key] = ['date' => $key, 'in' => 0.0, 'out' => 0.0, 'movements' => []];
            }

            if ($movement->direction === 'IN') {
                $days[$key]['in'] += (float) $movement->amount;
            } else {
                $days[$key]['out'] += (float) $movement->amount;
            }

            $days[$key]['movements'][] = $movement;
        }

        return array_slice(array_values($days), 0, $limit);
    }
}
