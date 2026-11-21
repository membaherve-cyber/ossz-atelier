<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\TreasuryAccount;
use App\Models\TreasuryMovement;
use App\Models\User;
use App\Services\Audit;
use App\Services\TreasuryService;
use App\Support\Atelier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Treasury (Finance & Comptabilité spec v1.3 §4.4) — GET/POST /treasury.
 *
 * Multi-account management: caisse, bank accounts, MTN Mobile Money,
 * Orange Money and any other configured account. Balances are always
 * derived from the movement ledger; internal transfers post two legs
 * and are neither a receipt nor an expense (§4.4). Includes the cash
 * journal grouped by day (clôture journalière view) and the
 * reconciliation flag (rapprochement).
 *
 * Read side needs treasury.view, write side treasury.manage (route
 * middleware).
 */
class TreasuryController
{
    /** Movement rows per page on the journal tab. */
    private const PER_PAGE = 30;

    /**
     * GET /treasury — accounts with derived balances, transfers form,
     * manual movement form, journal (by day) and the movement listing.
     */
    public function index(Request $request): View
    {
        $accountId = (string) ($request->query('account') ?? '');
        $kind = (string) ($request->query('kind') ?? '');

        $movements = TreasuryMovement::query()->with('account:id,name,kind');

        if ($accountId !== '') {
            $movements->where('account_id', $accountId);
        }

        if ($kind !== '' && in_array($kind, Atelier::TREASURY_MOVEMENT_KINDS, true)) {
            $movements->where('kind', $kind);
        }

        $movements = $movements
            ->orderByDesc('movement_date')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('accounting.treasury.index', [
            'overview' => TreasuryService::overview(),
            'accounts' => TreasuryAccount::query()->orderBy('name')->get(),
            'movements' => $movements,
            'journal' => TreasuryService::journalByDay(),
            'kinds' => Atelier::TREASURY_MOVEMENT_KINDS,
            'filters' => ['account' => $accountId, 'kind' => $kind],
        ]);
    }

    /**
     * POST /treasury/accounts — create a treasury account (§4.4
     * "autres comptes configurables").
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('treasury_accounts', 'name')],
            'kind' => ['required', 'string', Rule::in(Atelier::TREASURY_KINDS)],
            'method' => ['required', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $account = TreasuryAccount::query()->create([
            'name' => $data['name'],
            'kind' => $data['kind'],
            'method' => $data['method'],
            'opening_balance' => (float) ($data['opening_balance'] ?? 0),
            'notes' => $data['notes'] ?? null,
        ]);

        Audit::log($request->user(), 'TREASURY_ACCOUNT_CREATED', 'TREASURY_ACCOUNT', $account->id, [
            'name' => $account->name,
            'kind' => $account->kind,
            'method' => $account->method,
        ]);

        return redirect()->back()->with('success', __('treas.accountCreated'));
    }

    /**
     * POST /treasury/accounts/{id} — rename / deactivate an account
     * (movements are immutable history, they stay untouched).
     */
    public function updateAccount(Request $request, string $id): RedirectResponse
    {
        $account = TreasuryAccount::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('treasury_accounts', 'name')->ignore($account->id)],
            'is_active' => ['nullable'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $isActive = $request->has('is_active') ? $request->boolean('is_active') : $account->is_active;

        $account->update([
            'name' => $data['name'],
            'is_active' => $isActive,
            'notes' => $data['notes'] ?? $account->notes,
        ]);

        Audit::log($request->user(), 'TREASURY_ACCOUNT_UPDATED', 'TREASURY_ACCOUNT', $account->id, [
            'name' => $account->name,
            'isActive' => $isActive,
        ]);

        return redirect()->back()->with('success', __('treas.accountUpdated'));
    }

    /**
     * POST /treasury/transfer — internal transfer (§4.4): two legs,
     * neither a receipt nor an expense.
     */
    public function transfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_account' => ['required', 'uuid', 'exists:treasury_accounts,id'],
            'to_account' => ['required', 'uuid', 'exists:treasury_accounts,id', 'different:from_account'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $me */
        $me = $request->user();

        try {
            $legs = TreasuryService::transfer(
                TreasuryAccount::query()->findOrFail($data['from_account']),
                TreasuryAccount::query()->findOrFail($data['to_account']),
                (float) $data['amount'],
                \Carbon\Carbon::parse($data['date']),
                $data['note'] ?? null,
                $me,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        Audit::log($me, 'TREASURY_TRANSFER_RECORDED', 'TREASURY_TRANSFER', $legs['out']->id, [
            'from' => $legs['out']->account?->name,
            'to' => $legs['in']->account?->name,
            'amount' => (float) $data['amount'],
        ]);

        return redirect()->back()->with('success', __('treas.transferRecorded'));
    }

    /**
     * POST /treasury/movement — manual deposit / withdrawal (§4.4
     * "entrées et sorties de fonds").
     */
    public function storeMovement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account' => ['required', 'uuid', 'exists:treasury_accounts,id'],
            'direction' => ['required', Rule::in(['IN', 'OUT'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $me */
        $me = $request->user();

        try {
            $movement = TreasuryService::manual(
                TreasuryAccount::query()->findOrFail($data['account']),
                $data['direction'],
                (float) $data['amount'],
                \Carbon\Carbon::parse($data['date']),
                $data['note'] ?? null,
                $me,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        Audit::log($me, 'TREASURY_MOVEMENT_RECORDED', 'TREASURY_MOVEMENT', $movement->id, [
            'account' => $movement->account?->name,
            'direction' => $movement->direction,
            'kind' => $movement->kind,
            'amount' => (float) $data['amount'],
        ]);

        return redirect()->back()->with('success', __('treas.movementRecorded'));
    }

    /**
     * POST /treasury/movements/{id}/reconcile — toggle the
     * rapprochement flag (§4.4).
     */
    public function toggleReconcile(Request $request, string $id): RedirectResponse
    {
        $movement = TreasuryMovement::query()->findOrFail($id);
        $reconciled = ! $movement->reconciled;

        $movement->update([
            'reconciled' => $reconciled,
            'reconciled_at' => $reconciled ? now() : null,
        ]);

        Audit::log($request->user(), 'TREASURY_RECONCILED', 'TREASURY_MOVEMENT', $movement->id, [
            'reconciled' => $reconciled,
        ]);

        return redirect()->back()->with('success', $reconciled ? __('treas.reconciled') : __('treas.unreconciled'));
    }
}
