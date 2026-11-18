<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\StaffAdvance;
use App\Models\User;
use App\Services\Audit;
use App\Services\Codes;
use App\Services\Notifier;
use App\Services\Permissions;
use App\Support\Atelier;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Staff advances (enhancement spec §9) — GET/POST /advances.
 *
 * Workflow: PENDING_APPROVAL → APPROVED (accounting.approve) → DISBURSED
 * (staff notified) → PARTIALLY_RECOVERED → FULLY_RECOVERED, or CANCELLED
 * from any pre-disbursement state. Disbursed advances are a cash outflow
 * but never an expense (they are receivables); recoveries are applied
 * through payroll lines and never create expenses either. Outstanding
 * amounts are always derived (amount − amount_recovered mirror).
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class AdvanceController
{
    /** Advances per page on the index listing. */
    private const PER_PAGE = 20;

    /**
     * GET /advances (route perm accounting.advances) — filterable advance
     * listing (staff / status) with each row's recovery history eager-
     * loaded (recoveries → payrollItem → payroll) for the trail display.
     */
    public function index(Request $request): View
    {
        /** @var User $me */
        $me = $request->user();

        $staff = trim((string) ($request->query('staff') ?? ''));
        $status = (string) ($request->query('status') ?? '');

        $staffList = User::query()
            ->where('role', 'STAFF')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $advances = StaffAdvance::query()->with(['staff', 'recoveries.payrollItem.payroll']);

        if ($staff !== '' && $staffList->contains('id', $staff)) {
            $advances->where('staff_id', $staff);
        }

        if (in_array($status, Atelier::ADVANCE_STATUSES, true)) {
            $advances->where('status', $status);
        }

        $advances = $advances
            ->orderByDesc('date_issued')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('accounting.advances.index', [
            'advances' => $advances,
            'staffList' => $staffList,
            'filters' => [
                'staff' => $staff,
                'status' => $status,
            ],
            'statuses' => Atelier::ADVANCE_STATUSES,
            'methods' => Atelier::PAYMENT_METHODS,
            'canApprove' => Permissions::allows($me, 'accounting.approve'),
        ]);
    }

    /**
     * POST /advances (route perm accounting.advances) — record an advance
     * given to a staff member before salary payment. The reference
     * ADV-#### is system-generated; the staff member's name and the
     * recording user are snapshotted onto the row.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $this->rejectBlank($request, [
            'staffId' => __('adv.errStaff'),
            'amount' => __('adv.errAmount'),
            'dateIssued' => __('adv.errDate'),
            'reason' => __('adv.errReason'),
        ]);

        $data = $request->validate([
            'staffId' => ['required', 'string', Rule::exists('users', 'id')->where('role', 'STAFF')],
            'amount' => ['required', 'numeric', 'gt:0'],
            'dateIssued' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'repaymentPlan' => ['nullable', 'string', 'max:120'],
        ]);

        $staff = User::query()->findOrFail((string) $data['staffId']);

        $advance = Codes::unique('advance', fn (string $reference): StaffAdvance => StaffAdvance::query()->create([
            'reference' => $reference,
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'amount' => round((float) $data['amount'], 2),
            'currency' => Money::DEFAULT_CURRENCY,
            'date_issued' => Carbon::parse((string) $data['dateIssued']),
            'reason' => trim((string) $data['reason']),
            'method' => (string) ($data['method'] ?? 'CASH'),
            'repayment_plan' => $data['repaymentPlan'] ?? null,
            'status' => 'PENDING_APPROVAL',
            'recorded_by_id' => $me->id,
            'recorded_by_name' => $me->name,
        ]));

        Audit::log($me, 'ADVANCE_RECORDED', 'ADVANCE', $advance->id, [
            'reference' => $advance->reference,
            'staffName' => $advance->staff_name,
            'amount' => $advance->amount,
        ]);

        return redirect()
            ->back()
            ->with('success', __('adv.createdToast'));
    }

    /**
     * POST /advances/{id}/review (route perm accounting.approve) — approve
     * (PENDING_APPROVAL → APPROVED) or reject (PENDING_APPROVAL →
     * CANCELLED, reason required) a recorded advance.
     */
    public function review(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $base = $request->validate([
            'decision' => ['required', 'string', Rule::in(['approve', 'reject'])],
        ]);

        $decision = (string) $base['decision'];

        $reason = null;

        if ($decision === 'reject') {
            $data = $request->validate([
                'reason' => ['required', 'string', 'min:3', 'max:2000'],
            ]);

            $reason = trim((string) $data['reason']);
        }

        $advance = StaffAdvance::query()->findOrFail($id);

        if ($advance->status !== 'PENDING_APPROVAL') {
            return redirect()
                ->back()
                ->with('error', __('common.somethingWrong'));
        }

        if ($decision === 'approve') {
            $advance->forceFill(['status' => 'APPROVED'])->save();

            $toast = 'adv.approvedToast';
        } else {
            $advance->forceFill(['status' => 'CANCELLED'])->save();

            $toast = 'adv.rejectedToast';
        }

        Audit::log($me, 'ADVANCE_REVIEWED', 'ADVANCE', $advance->id, [
            'reference' => $advance->reference,
            'staffName' => $advance->staff_name,
            'decision' => $decision,
            'reason' => $reason,
        ]);

        return redirect()
            ->back()
            ->with('success', __($toast));
    }

    /**
     * POST /advances/{id}/disburse (route perm accounting.approve) — mark
     * an APPROVED advance as handed out and notify the staff member
     * (spec §9: the money is out, recovery now happens via payroll).
     */
    public function disburse(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $advance = StaffAdvance::query()->findOrFail($id);

        if ($advance->status !== 'APPROVED') {
            return redirect()
                ->back()
                ->with('error', __('common.somethingWrong'));
        }

        $advance->forceFill(['status' => 'DISBURSED'])->save();

        Audit::log($me, 'ADVANCE_DISBURSED', 'ADVANCE', $advance->id, [
            'reference' => $advance->reference,
            'staffName' => $advance->staff_name,
            'amount' => $advance->amount,
        ]);

        Notifier::users(
            [$advance->staff_id],
            'ADVANCE_DISBURSED',
            'An advance of '.Money::format($advance->amount).' was given to you ('.$advance->reference.').'
        );

        return redirect()
            ->back()
            ->with('success', __('adv.disbursedToast'));
    }

    /**
     * POST /advances/{id}/cancel (route perm accounting.approve) — cancel
     * an advance that was never handed out (PENDING_APPROVAL or APPROVED).
     */
    public function cancel(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $advance = StaffAdvance::query()->findOrFail($id);

        if (! in_array($advance->status, ['PENDING_APPROVAL', 'APPROVED'], true)) {
            return redirect()
                ->back()
                ->with('error', __('common.somethingWrong'));
        }

        $advance->forceFill(['status' => 'CANCELLED'])->save();

        Audit::log($me, 'ADVANCE_CANCELLED', 'ADVANCE', $advance->id, [
            'reference' => $advance->reference,
            'staffName' => $advance->staff_name,
            'amount' => $advance->amount,
        ]);

        return redirect()
            ->back()
            ->with('success', __('adv.cancelledToast'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Pre-validation guard for the NOT NULL staff/amount/date/reason
     * columns (see PaymentController::rejectBlank).
     *
     * @param  array<string, string>  $fields
     */
    private function rejectBlank(Request $request, array $fields): void
    {
        $messages = [];

        foreach ($fields as $field => $message) {
            if ($request->has($field) && blank($request->input($field))) {
                $messages[$field] = $message;
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }
}
