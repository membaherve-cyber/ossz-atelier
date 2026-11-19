<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\AdvanceRecovery;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\SalaryProfile;
use App\Models\StaffAdvance;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\PayrollService;
use App\Services\Permissions;
use App\Support\Atelier;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Salaries and payroll (enhancement spec §8) — GET/POST /payroll and
 * POST /salary-profiles.
 *
 * Payroll periods are proposed from each active STAFF member's latest
 * ACTIVE salary profile (PayrollService::previewItems), adjusted by
 * Management, approved (accounting.approve) and then paid line by line.
 * Advance recoveries are REBUILT on every line edit: the item's
 * advance_recoveries rows are deleted, the advances' recovery state is
 * refreshed and the submitted deduction is re-applied to the staff
 * member's outstanding advances (oldest first) — recovered advances
 * reduce net pay and never become expenses (spec §9).
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class PayrollController
{
    /**
     * GET /payroll (route perm accounting.payroll) — the payroll periods
     * tab (paginated, items eager-loaded for the per-period totals) and
     * the salary profiles tab, plus the proposed lines for a new period.
     */
    public function index(Request $request): View
    {
        /** @var User $me */
        $me = $request->user();

        $tab = (string) ($request->query('tab') ?? 'payroll');

        if (! in_array($tab, ['payroll', 'profiles'], true)) {
            $tab = 'payroll';
        }

        return view('accounting.payroll.index', [
            'payrolls' => Payroll::query()
                ->with('items.staff')
                ->orderByDesc('period_start')
                ->paginate(10)
                ->withQueryString(),
            'profiles' => SalaryProfile::query()
                ->with('staff')
                ->orderByDesc('effective_date')
                ->get(),
            'staffList' => User::query()
                ->where('role', 'STAFF')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'job_title']),
            'remunerationTypes' => Atelier::REMUNERATION_TYPES,
            'preview' => PayrollService::previewItems(),
            'tab' => $tab,
            'methods' => Atelier::PAYMENT_METHODS,
            'canApprove' => Permissions::allows($me, 'accounting.approve'),
        ]);
    }

    /**
     * POST /payroll (route perm accounting.payroll) — create a payroll
     * period with one proposed line per active STAFF member. Labels are
     * unique and periods may not overlap an existing one; the whole
     * period + items set is written in a single transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $this->rejectBlank($request, [
            'label' => __('prl.errLabel'),
            'periodStart' => __('prl.errDates'),
            'periodEnd' => __('prl.errDates'),
        ]);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:60', Rule::unique('payrolls', 'label')],
            'periodStart' => ['required', 'date'],
            'periodEnd' => ['required', 'date', 'after_or_equal:periodStart'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $start = Carbon::parse((string) $data['periodStart']);
        $end = Carbon::parse((string) $data['periodEnd']);

        $overlaps = Payroll::query()
            ->where('period_start', '<=', $end->toDateString())
            ->where('period_end', '>=', $start->toDateString())
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'periodEnd' => __('prl.errOverlap'),
            ]);
        }

        $preview = PayrollService::previewItems();

        if ($preview === []) {
            throw ValidationException::withMessages([
                'staff' => __('prl.errNoStaff'),
            ]);
        }

        $payroll = DB::transaction(function () use ($data, $preview, $me, $start, $end): Payroll {
            // Overlap check re-verified INSIDE the transaction (locked rows)
            // so two concurrent submissions cannot both create overlapping
            // periods by passing the pre-check above.
            $overlaps = Payroll::query()
                ->where('period_start', '<=', $end->toDateString())
                ->where('period_end', '>=', $start->toDateString())
                ->lockForUpdate()
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'periodEnd' => __('prl.errOverlap'),
                ]);
            }

            $payroll = Payroll::query()->create([
                'label' => trim((string) $data['label']),
                'period_start' => $start,
                'period_end' => $end,
                'status' => 'PENDING',
                'notes' => $data['notes'] ?? null,
                'created_by_id' => $me->id,
                'created_by_name' => $me->name,
            ]);

            foreach ($preview as $entry) {
                $payroll->items()->create([
                    'staff_id' => $entry['staff']->id,
                    'staff_name' => $entry['staff']->name,
                    'salary_amount' => $entry['salary'],
                    'allowances' => $entry['allowances'],
                    'bonuses' => 0,
                    'deductions' => 0,
                    'advance_recovered' => 0,
                    'net_pay' => $entry['net'],
                    'payment_status' => 'PENDING',
                ]);
            }

            return $payroll;
        });

        Audit::log($me, 'PAYROLL_CREATED', 'PAYROLL', $payroll->id, [
            'label' => $payroll->label,
            'periodStart' => $start->toDateString(),
            'periodEnd' => $end->toDateString(),
            'items' => count($preview),
        ]);

        return redirect()
            ->route('payroll.index', ['tab' => 'payroll'])
            ->with('success', __('prl.createdToast'));
    }

    /**
     * POST /payroll/{id}/approve (route perm accounting.approve) — promote
     * a PENDING payroll to APPROVED with the approver snapshotted; the
     * lines then become payable.
     */
    public function approve(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $payroll = Payroll::query()->findOrFail($id);

        if ($payroll->status !== 'PENDING') {
            return redirect()
                ->back()
                ->with('error', __('common.somethingWrong'));
        }

        $payroll->forceFill([
            'status' => 'APPROVED',
            'approved_by_id' => $me->id,
            'approved_by_name' => $me->name,
            'approved_at' => now(),
        ])->save();

        Audit::log($me, 'PAYROLL_APPROVED', 'PAYROLL', $payroll->id, [
            'label' => $payroll->label,
            'items' => $payroll->items()->count(),
        ]);

        return redirect()
            ->back()
            ->with('success', __('prl.approvedToast'));
    }

    /**
     * POST /payroll/items/{id}/update (route perm accounting.payroll) —
     * edit one payroll line (paid lines are locked). The advance-recovery
     * deduction is authoritative: existing recovery rows of this line are
     * deleted, the affected advances are refreshed, and the submitted
     * advanceRecovered target is re-applied to the staff member's
     * outstanding advances (oldest first, capped by each balance). Net
     * pay is then recomputed and the payroll's paid-status re-derived.
     */
    public function updateItem(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $item = PayrollItem::query()->with('payroll')->findOrFail($id);

        if ($item->payment_status !== 'PENDING') {
            throw ValidationException::withMessages([
                'paymentStatus' => __('prl.editBlocked'),
            ]);
        }

        $data = $request->validate([
            'salaryAmount' => ['nullable', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'bonuses' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'advanceRecovered' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        // Submitted values are authoritative (blank numeric → 0); absent
        // fields keep the line's current amounts.
        $salary = array_key_exists('salaryAmount', $data) ? (float) ($data['salaryAmount'] ?? 0) : (float) $item->salary_amount;
        $allowances = array_key_exists('allowances', $data) ? (float) ($data['allowances'] ?? 0) : (float) $item->allowances;
        $bonuses = array_key_exists('bonuses', $data) ? (float) ($data['bonuses'] ?? 0) : (float) $item->bonuses;
        $deductions = array_key_exists('deductions', $data) ? (float) ($data['deductions'] ?? 0) : (float) $item->deductions;
        $target = array_key_exists('advanceRecovered', $data) ? (float) ($data['advanceRecovered'] ?? 0) : (float) $item->advance_recovered;

        // A net pay can never go negative — deductions + advance recovery
        // are capped at what the line actually pays out.
        $gross = round($salary + $allowances + $bonuses, 2);
        $outflows = round($deductions + $target, 2);

        if ($outflows > $gross + 0.005) {
            throw ValidationException::withMessages([
                'deductions' => __('prl.errNegativeNet'),
            ]);
        }

        [$changedColumns] = DB::transaction(function () use ($item, $data, $me, $salary, $allowances, $bonuses, $deductions, $target): array {
            // Lock the line so two concurrent edits of the same item cannot
            // interleave their delete/re-insert of advance recoveries.
            $item = PayrollItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            // (a) Drop this line's recoveries and refresh the advances.
            $advanceIds = $item->advanceRecoveries()->pluck('advance_id')->unique()->values()->all();

            $item->advanceRecoveries()->delete();

            foreach (StaffAdvance::query()->whereIn('id', $advanceIds)->get() as $advance) {
                $advance->refreshRecoveryState();
            }

            // (b) Re-apply the deduction to the outstanding advances.
            $applied = 0.0;

            foreach (PayrollService::outstandingAdvances((string) $item->staff_id) as $advance) {
                $remaining = round($target - $applied, 2);

                if ($remaining <= 0.005) {
                    break;
                }

                $applied = round($applied + PayrollService::applyRecovery(
                    $advance,
                    $item,
                    $remaining,
                    (string) $me->id,
                    (string) $me->name
                ), 2);
            }

            // (c) Recompute net pay and persist the line.
            $item->salary_amount = $salary;
            $item->allowances = $allowances;
            $item->bonuses = $bonuses;
            $item->deductions = $deductions;
            $item->advance_recovered = $applied;
            $item->net_pay = PayrollService::netPay($salary, $allowances, $bonuses, $deductions, $applied);

            if (array_key_exists('notes', $data)) {
                $item->notes = $data['notes'] ?? null;
            }

            $changedColumns = array_keys($item->getDirty());

            if ($changedColumns !== []) {
                $item->save();
            }

            $this->refreshPayrollStatus($item->payroll);

            return [$changedColumns];
        });

        Audit::log($me, 'PAYROLL_UPDATED', 'PAYROLL', $item->payroll->id, [
            'label' => $item->payroll->label,
            'staffName' => $item->staff_name,
            'changedFields' => $changedColumns,
        ]);

        return redirect()
            ->back()
            ->with('success', __('prl.updatedToast'));
    }

    /**
     * POST /payroll/items/{id}/pay (route perm accounting.payroll) —
     * record one salary payment on an APPROVED / PARTIALLY_PAID payroll;
     * the staff member is notified and the payroll's status is re-derived
     * (PARTIALLY_PAID / PAID).
     */
    public function payItem(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $item = PayrollItem::query()->with('payroll')->findOrFail($id);
        $payroll = $item->payroll;

        $data = $request->validate([
            'paymentDate' => ['required', 'date'],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'txnRef' => ['nullable', 'string', 'max:100'],
        ]);

        if ($item->payment_status !== 'PENDING' || ! in_array($payroll->status, ['APPROVED', 'PARTIALLY_PAID'], true)) {
            return redirect()
                ->back()
                ->with('error', __('prl.paymentBlocked'));
        }

        DB::transaction(function () use ($item, $payroll, $data): void {
            $item->forceFill([
                'payment_status' => 'PAID',
                'payment_date' => Carbon::parse((string) $data['paymentDate']),
                'method' => $data['method'] ?? null,
                'txn_ref' => $data['txnRef'] ?? null,
            ])->save();

            $this->refreshPayrollStatus($payroll);
        });

        Audit::log($me, 'PAYROLL_ITEM_PAID', 'PAYROLL', $payroll->id, [
            'label' => $payroll->label,
            'staffName' => $item->staff_name,
            'netPay' => $item->net_pay,
            'paymentDate' => Carbon::parse((string) $data['paymentDate'])->format(DATE_ATOM),
        ]);

        Notifier::users(
            [$item->staff_id],
            'PAYROLL_PAID',
            'Your salary for '.$payroll->label.' was paid ('.Money::format($item->net_pay).').'
        );

        return redirect()
            ->back()
            ->with('success', __('prl.paidToast'));
    }

    /**
     * POST /payroll/{id}/delete (route perm accounting.payroll) — remove a
     * payroll period together with its lines. Payrolls holding a recorded
     * payment are blocked; recoveries applied through the period's lines
     * are rolled back first so the advances keep correct balances.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $payroll = Payroll::query()->findOrFail($id);

        if ($payroll->items()->where('payment_status', 'PAID')->exists()) {
            return redirect()
                ->back()
                ->with('error', __('prl.deleteBlocked'));
        }

        $label = $payroll->label;

        DB::transaction(function () use ($payroll): void {
            $itemIds = $payroll->items()->pluck('id');

            $advanceIds = AdvanceRecovery::query()
                ->whereIn('payroll_item_id', $itemIds)
                ->pluck('advance_id')
                ->unique()
                ->values()
                ->all();

            AdvanceRecovery::query()->whereIn('payroll_item_id', $itemIds)->delete();

            foreach (StaffAdvance::query()->whereIn('id', $advanceIds)->get() as $advance) {
                $advance->refreshRecoveryState();
            }

            $payroll->delete(); // payroll_items cascade; recoveries already gone
        });

        Audit::log($me, 'PAYROLL_UPDATED', 'PAYROLL', $payroll->id, [
            'deleted' => true,
            'label' => $label,
        ]);

        return redirect()
            ->back()
            ->with('success', __('prl.deletedToast'));
    }

    /**
     * POST /salary-profiles (route perm accounting.payroll) — register a
     * staff member's remuneration structure. Several profiles per staff
     * are allowed (history); payroll generation uses the latest ACTIVE one.
     */
    public function storeProfile(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $this->rejectBlank($request, [
            'staffId' => __('prl.errStaff'),
            'baseAmount' => __('prl.errBaseAmount'),
            'effectiveDate' => __('prl.errEffectiveDate'),
        ]);

        $data = $request->validate([
            'staffId' => ['required', 'string', Rule::exists('users', 'id')->where('role', 'STAFF')],
            'remunerationType' => ['nullable', 'string', Rule::in(Atelier::REMUNERATION_TYPES)],
            'baseAmount' => ['required', 'numeric', 'min:0'],
            'effectiveDate' => ['required', 'date'],
            'frequency' => ['nullable', 'string', 'max:40'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['ACTIVE', 'INACTIVE'])],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ]);

        $staff = User::query()->findOrFail((string) $data['staffId']);

        $profile = SalaryProfile::query()->create([
            'staff_id' => $staff->id,
            'remuneration_type' => (string) ($data['remunerationType'] ?? 'MONTHLY'),
            'base_amount' => round((float) $data['baseAmount'], 2),
            'currency' => Money::DEFAULT_CURRENCY,
            'effective_date' => Carbon::parse((string) $data['effectiveDate']),
            'frequency' => $data['frequency'] ?? null,
            'allowances' => round((float) ($data['allowances'] ?? 0), 2),
            'status' => (string) ($data['status'] ?? 'ACTIVE'),
            'remarks' => $data['remarks'] ?? null,
            'created_by_id' => $me->id,
            'created_by_name' => $me->name,
        ]);

        Audit::log($me, 'SALARY_PROFILE_CREATED', 'SALARY_PROFILE', $profile->id, [
            'staffName' => $staff->name,
            'type' => $profile->remuneration_type,
            'baseAmount' => $profile->base_amount,
        ]);

        return redirect()
            ->back()
            ->with('success', __('prl.profileCreated'));
    }

    /**
     * POST /salary-profiles/{id} (route perm accounting.payroll) — partial
     * profile edit; present-null clears the nullable columns
     * (frequency / remarks), only dirty columns are written + audited.
     */
    public function updateProfile(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $profile = SalaryProfile::query()->with('staff')->findOrFail($id);

        $this->rejectBlank($request, [
            'staffId' => __('prl.errStaff'),
            'baseAmount' => __('prl.errBaseAmount'),
            'effectiveDate' => __('prl.errEffectiveDate'),
        ]);

        $data = $request->validate([
            'staffId' => ['nullable', 'string', Rule::exists('users', 'id')->where('role', 'STAFF')],
            'remunerationType' => ['nullable', 'string', Rule::in(Atelier::REMUNERATION_TYPES)],
            'baseAmount' => ['nullable', 'numeric', 'min:0'],
            'effectiveDate' => ['nullable', 'date'],
            'frequency' => ['nullable', 'string', 'max:40'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['ACTIVE', 'INACTIVE'])],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ]);

        $update = [];

        if (isset($data['staffId'])) {
            $update['staff_id'] = (string) $data['staffId'];
        }

        if (isset($data['remunerationType'])) {
            $update['remuneration_type'] = (string) $data['remunerationType'];
        }

        if (isset($data['baseAmount'])) {
            $update['base_amount'] = round((float) $data['baseAmount'], 2);
        }

        if (isset($data['effectiveDate'])) {
            $update['effective_date'] = Carbon::parse((string) $data['effectiveDate']);
        }

        if (array_key_exists('frequency', $data)) {
            $update['frequency'] = $data['frequency'] ?? null;
        }

        if (array_key_exists('allowances', $data)) {
            $update['allowances'] = round((float) ($data['allowances'] ?? 0), 2);
        }

        if (isset($data['status'])) {
            $update['status'] = (string) $data['status'];
        }

        if (array_key_exists('remarks', $data)) {
            $update['remarks'] = $data['remarks'] ?? null;
        }

        $profile->fill($update);
        $changedColumns = array_keys($profile->getDirty());

        if ($changedColumns !== []) {
            $profile->save();
        }

        Audit::log($me, 'SALARY_PROFILE_UPDATED', 'SALARY_PROFILE', $profile->id, [
            'staffName' => $profile->staff?->name,
            'changedFields' => $changedColumns,
        ]);

        return redirect()
            ->back()
            ->with('success', __('prl.profileUpdated'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Re-derive a payroll's payment status from its lines — all PAID →
     * PAID, some PAID → PARTIALLY_PAID, none → APPROVED. Periods not yet
     * approved (DRAFT/PENDING) keep their workflow status; line edits
     * never approve a payroll implicitly.
     */
    private function refreshPayrollStatus(Payroll $payroll): void
    {
        if (! in_array($payroll->status, ['APPROVED', 'PARTIALLY_PAID', 'PAID'], true)) {
            return;
        }

        $total = $payroll->items()->count();
        $paid = $payroll->items()->where('payment_status', 'PAID')->count();

        $status = $paid === 0 ? 'APPROVED' : ($paid === $total ? 'PAID' : 'PARTIALLY_PAID');

        if ($status !== $payroll->status) {
            $payroll->forceFill(['status' => $status])->save();
        }
    }

    /**
     * Pre-validation guard for the NOT NULL label/date columns (see
     * PaymentController::rejectBlank).
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
