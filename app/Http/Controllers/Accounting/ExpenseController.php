<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\User;
use App\Services\AccountingPostService;
use App\Services\Audit;
use App\Services\Codes;
use App\Services\Permissions;
use App\Services\TreasuryService;
use App\Support\Atelier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Workshop cost ledger (enhancement spec §7) — GET/POST /expenses.
 *
 * Every operating cost exists in `expenses` exactly once: manual entries
 * here, paid supplier purchases (source_type=PURCHASE) and paid incentives
 * (source_type=INCENTIVE_RECORD) booked by their own flows. Auto-generated
 * rows are never editable/deletable here; only APPROVED + PAID rows count
 * as costs in dashboards and reports. The default category set is seeded
 * lazily on the first page visit (ExpenseCategory::ensureDefaults(), the
 * PerformanceConfig::ensure() pattern).
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class ExpenseController
{
    /** Expenses per page on the index listing. */
    private const PER_PAGE = 20;

    /**
     * GET /expenses (route perm accounting.expenses) — filterable ledger
     * listing (search / category / group / status / date range) plus the
     * category, order, staff and constant lists the record form and the
     * review actions need.
     */
    public function index(Request $request): View
    {
        // Lazy default-category seed (idempotent, spec §7.1).
        ExpenseCategory::ensureDefaults();

        /** @var User $me */
        $me = $request->user();

        $q = trim((string) ($request->query('q') ?? ''));
        $category = trim((string) ($request->query('category') ?? ''));
        $group = (string) ($request->query('group') ?? '');
        $status = (string) ($request->query('status') ?? '');
        $from = trim((string) ($request->query('from') ?? ''));
        $to = trim((string) ($request->query('to') ?? ''));

        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        $expenses = Expense::query()->with(['category', 'order.customer', 'responsibleStaff']);

        if ($q !== '') {
            // reference / description / payee contains.
            $expenses->where(function (Builder $query) use ($q): void {
                $query->where('reference', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%')
                    ->orWhere('payee', 'like', '%'.$q.'%');
            });
        }

        if ($category !== '' && $categories->contains('id', $category)) {
            $expenses->where('expense_category_id', $category);
        }

        if (in_array($group, Atelier::EXPENSE_GROUPS, true)) {
            $expenses->whereHas('category', fn (Builder $query): Builder => $query->where('group', $group));
        }

        if (in_array($status, Atelier::EXPENSE_STATUSES, true)) {
            $expenses->where('status', $status);
        }

        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        if ($toDate !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1) {
            $toDate = $toDate->endOfDay();
        }

        if ($fromDate !== null) {
            $expenses->where('date_incurred', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $expenses->where('date_incurred', '<=', $toDate);
        }

        $expenses = $expenses
            ->orderByDesc('date_incurred')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('accounting.expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'groups' => Atelier::EXPENSE_GROUPS,
            'statuses' => Atelier::EXPENSE_STATUSES,
            'orders' => Order::query()->orderByDesc('order_date')->get(['id', 'order_number']),
            'staff' => User::query()
                ->where('is_active', true)
                ->whereIn('role', Permissions::assignableRoles())
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
            'filters' => [
                'q' => $q,
                'category' => $category,
                'group' => $group,
                'status' => $status,
                'from' => $from,
                'to' => $to,
            ],
            'canApprove' => Permissions::allows($me, 'accounting.approve'),
            'methods' => Atelier::PAYMENT_METHODS,
        ]);
    }

    /**
     * POST /expenses (route perm accounting.expenses) — record a manual
     * ledger entry. The reference EXP-#### is generated inside the same
     * transaction as the insert; receipts are stored under
     * storage/app/receipts (local disk).
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $this->rejectBlank($request, [
            'categoryId' => __('exp.errCategory'),
            'description' => __('exp.errDescription'),
            'amount' => __('exp.errAmount'),
            'dateIncurred' => __('exp.errDate'),
        ]);

        $data = $request->validate([
            'categoryId' => ['required', 'string', Rule::exists('expense_categories', 'id')],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'dateIncurred' => ['required', 'date'],
            'datePaid' => ['nullable', 'date'],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'payee' => ['nullable', 'string', 'max:120'],
            'orderId' => ['nullable', 'string', Rule::exists('orders', 'id')],
            'staffId' => ['nullable', 'string', Rule::exists('users', 'id')],
            'remarks' => ['nullable', 'string', 'max:4000'],
            'status' => ['nullable', 'string', Rule::in(Atelier::EXPENSE_STATUSES)],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);

        $receiptPath = $this->storeReceipt($request);

        $expense = DB::transaction(fn (): Expense => Codes::unique(
            'expense',
            fn (string $reference): Expense => Expense::query()->create([
                'reference' => $reference,
                'expense_category_id' => (string) $data['categoryId'],
                'description' => trim((string) $data['description']),
                'amount' => round((float) $data['amount'], 2),
                'currency' => 'XAF',
                'date_incurred' => Carbon::parse((string) $data['dateIncurred']),
                'date_paid' => isset($data['datePaid']) && $data['datePaid'] !== null
                    ? Carbon::parse((string) $data['datePaid'])
                    : null,
                'method' => $data['method'] ?? null,
                'payee' => $data['payee'] ?? null,
                'order_id' => $data['orderId'] ?? null,
                'responsible_staff_id' => $data['staffId'] ?? null,
                'receipt_path' => $receiptPath,
                'remarks' => $data['remarks'] ?? null,
                'status' => (string) ($data['status'] ?? 'PENDING'),
                'recorded_by_id' => $me->id,
                'recorded_by_name' => $me->name,
            ])
        ));

        Audit::log($me, 'EXPENSE_RECORDED', 'EXPENSE', $expense->id, [
            'reference' => $expense->reference,
            'category' => $expense->category?->name,
            'amount' => $expense->amount,
            'status' => $expense->status,
        ]);

        // Finance module v1.3 — a directly-PAID expense leaves the
        // treasury and posts its journal entry immediately (§4.3/§6).
        if ($expense->status === 'PAID') {
            TreasuryService::recordForExpense($expense);
            AccountingPostService::postExpense($expense, 'PAID', $me);
        } elseif ($expense->status === 'APPROVED') {
            AccountingPostService::postExpense($expense, 'APPROVED', $me);
        }

        return redirect()
            ->back()
            ->with('success', __('exp.createdToast'));
    }

    /**
     * POST /expenses/{id}/update (route perm accounting.expenses) — edit a
     * manual entry. Every field is optional; present-null clears the
     * nullable columns (datePaid / method / payee / orderId / staffId /
     * remarks), a newly uploaded receipt replaces (and deletes) the old
     * file, and only dirty columns are written + audited.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $expense = Expense::query()->findOrFail($id);

        $this->rejectBlank($request, [
            'categoryId' => __('exp.errCategory'),
            'description' => __('exp.errDescription'),
            'amount' => __('exp.errAmount'),
            'dateIncurred' => __('exp.errDate'),
        ]);

        $data = $request->validate([
            'categoryId' => ['nullable', 'string', Rule::exists('expense_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'dateIncurred' => ['nullable', 'date'],
            'datePaid' => ['nullable', 'date'],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'payee' => ['nullable', 'string', 'max:120'],
            'orderId' => ['nullable', 'string', Rule::exists('orders', 'id')],
            'staffId' => ['nullable', 'string', Rule::exists('users', 'id')],
            'remarks' => ['nullable', 'string', 'max:4000'],
            'status' => ['nullable', 'string', Rule::in(Atelier::EXPENSE_STATUSES)],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);

        $update = [];

        if (isset($data['categoryId'])) {
            $update['expense_category_id'] = (string) $data['categoryId'];
        }

        if (isset($data['description'])) {
            $update['description'] = trim((string) $data['description']);
        }

        if (isset($data['amount'])) {
            $update['amount'] = round((float) $data['amount'], 2);
        }

        if (isset($data['dateIncurred'])) {
            $update['date_incurred'] = Carbon::parse((string) $data['dateIncurred']);
        }

        if (array_key_exists('datePaid', $data)) {
            $update['date_paid'] = $data['datePaid'] !== null
                ? Carbon::parse((string) $data['datePaid'])
                : null;
        }

        if (array_key_exists('method', $data)) {
            $update['method'] = $data['method'] ?? null;
        }

        if (array_key_exists('payee', $data)) {
            $update['payee'] = $data['payee'] ?? null;
        }

        if (array_key_exists('orderId', $data)) {
            $update['order_id'] = $data['orderId'] ?? null;
        }

        if (array_key_exists('staffId', $data)) {
            $update['responsible_staff_id'] = $data['staffId'] ?? null;
        }

        if (array_key_exists('remarks', $data)) {
            $update['remarks'] = $data['remarks'] ?? null;
        }

        if (isset($data['status'])) {
            $update['status'] = (string) $data['status'];
        }

        $newReceipt = $this->storeReceipt($request);

        if ($newReceipt !== null) {
            $this->deleteReceiptFile($expense->receipt_path);
            $update['receipt_path'] = $newReceipt;
        }

        $expense->fill($update);
        $changedColumns = array_keys($expense->getDirty());

        if ($changedColumns !== []) {
            $expense->save();
        }

        Audit::log($me, 'EXPENSE_UPDATED', 'EXPENSE', $expense->id, [
            'reference' => $expense->reference,
            'changedFields' => $changedColumns,
        ]);

        return redirect()
            ->back()
            ->with('success', __('exp.updatedToast'));
    }

    /**
     * POST /expenses/{id}/review (route perm accounting.approve) — the
     * approval workflow: approve (PENDING/DRAFT → APPROVED, approver
     * snapshotted), reject (PENDING/DRAFT → REJECTED, reason required) or
     * markPaid (APPROVED → PAID, date_paid defaults to today when blank).
     */
    public function review(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $base = $request->validate([
            'decision' => ['required', 'string', Rule::in(['approve', 'reject', 'markPaid'])],
        ]);

        $decision = (string) $base['decision'];

        $reason = null;

        if ($decision === 'reject') {
            $data = $request->validate([
                'reason' => ['required', 'string', 'min:3', 'max:2000'],
            ]);

            $reason = trim((string) $data['reason']);
        }

        $expense = Expense::query()->findOrFail($id);

        if ($decision === 'markPaid') {
            if ($expense->status !== 'APPROVED') {
                return redirect()
                    ->back()
                    ->with('error', __('common.somethingWrong'));
            }

            $expense->forceFill([
                'status' => 'PAID',
                'date_paid' => $expense->date_paid ?? Carbon::today(),
            ])->save();

            // Finance module v1.3 §4.3/§4.4/§6 — settlement leaves the
            // treasury and posts its journal entry.
            TreasuryService::recordForExpense($expense);
            AccountingPostService::postExpense($expense, 'PAID', $me);

            $toast = 'exp.paidToast';
        } elseif ($decision === 'reject') {
            if (! in_array($expense->status, ['PENDING', 'DRAFT'], true)) {
                return redirect()
                    ->back()
                    ->with('error', __('common.somethingWrong'));
            }

            $expense->forceFill(['status' => 'REJECTED'])->save();

            $toast = 'exp.rejectedToast';
        } else {
            if (! in_array($expense->status, ['PENDING', 'DRAFT'], true)) {
                return redirect()
                    ->back()
                    ->with('error', __('common.somethingWrong'));
            }

            $expense->forceFill([
                'status' => 'APPROVED',
                'approved_by_id' => $me->id,
                'approved_by_name' => $me->name,
                'approved_at' => now(),
            ])->save();

            // Finance module v1.3 §6 — the approved charge is booked
            // against the supplier debt (401) until settlement.
            AccountingPostService::postExpense($expense, 'APPROVED', $me);

            $toast = 'exp.approvedToast';
        }

        Audit::log($me, 'EXPENSE_REVIEWED', 'EXPENSE', $expense->id, [
            'reference' => $expense->reference,
            'decision' => $decision,
            'reason' => $reason,
        ]);

        return redirect()
            ->back()
            ->with('success', __($toast));
    }

    /**
     * POST /expenses/{id}/delete (route perm accounting.expenses) — remove
     * a manual entry together with its receipt file. Auto-generated rows
     * (incentive payouts, paid purchases) are blocked; the deletion is
     * audited before the row disappears.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $expense = Expense::query()->findOrFail($id);

        if ($expense->source_type !== null) {
            return redirect()
                ->back()
                ->with('error', __('exp.deleteBlocked'));
        }

        Audit::log($me, 'EXPENSE_DELETED', 'EXPENSE', $expense->id, [
            'reference' => $expense->reference,
            'category' => $expense->category?->name,
            'amount' => $expense->amount,
            'status' => $expense->status,
        ]);

        $this->deleteReceiptFile($expense->receipt_path);

        $expense->delete();

        return redirect()
            ->back()
            ->with('success', __('exp.deletedToast'));
    }

    /**
     * GET /expenses/{id}/receipt (route perm accounting.expenses) — stream
     * the stored receipt attachment from storage/app/receipts. The
     * resolved path is verified to stay INSIDE storage/app (containment
     * guard) so a tampered receipt_path can never stream arbitrary
     * server files.
     */
    public function receipt(string $id): BinaryFileResponse
    {
        $expense = Expense::query()->findOrFail($id);

        $path = $expense->receipt_path;

        abort_unless(is_string($path) && $path !== '', 404);

        $absolute = realpath(storage_path('app/'.$path));
        $root = realpath(storage_path('app'));

        abort_unless(
            $absolute !== false
            && $root !== false
            && str_starts_with($absolute, $root.DIRECTORY_SEPARATOR)
            && is_file($absolute),
            404
        );

        return response()->file($absolute);
    }

    /**
     * POST /expense-categories (route perm accounting.expenses) — create a
     * custom (non-system) category. Names are unique case-insensitively
     * across ALL rows, system ones included.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $this->rejectBlank($request, [
            'name' => __('exp.errCategoryName'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'group' => ['required', 'string', Rule::in(Atelier::EXPENSE_GROUPS)],
        ]);

        $name = trim((string) $data['name']);

        $duplicate = ExpenseCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => __('validation.unique', ['attribute' => __('exp.fieldName')]),
            ]);
        }

        $category = ExpenseCategory::query()->create([
            'name' => $name,
            'group' => (string) $data['group'],
            'is_system' => false,
            'is_active' => true,
            'created_by_id' => $me->id,
            'created_by_name' => $me->name,
        ]);

        Audit::log($me, 'EXPENSE_CATEGORY_CREATED', 'EXPENSE_CATEGORY', $category->id, [
            'name' => $category->name,
            'group' => $category->group,
        ]);

        return redirect()
            ->back()
            ->with('success', __('exp.categoryCreated'));
    }

    /**
     * POST /expense-categories/{id} (route perm accounting.expenses) —
     * rename a category and/or toggle its active flag. Deactivating a
     * category that recorded expenses rely on is blocked; system
     * categories are never deleted, only deactivated (spec §7.1).
     */
    public function updateCategory(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $category = ExpenseCategory::query()->findOrFail($id);

        $this->rejectBlank($request, [
            'name' => __('exp.errCategoryName'),
        ]);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'group' => ['nullable', 'string', Rule::in(Atelier::EXPENSE_GROUPS)],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $update = [];

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);

            $duplicate = ExpenseCategory::query()
                ->where('id', '!=', $category->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'name' => __('validation.unique', ['attribute' => __('exp.fieldName')]),
                ]);
            }

            $update['name'] = $name;
        }

        if (isset($data['group'])) {
            $update['group'] = (string) $data['group'];
        }

        if (array_key_exists('isActive', $data) && $data['isActive'] !== null) {
            $activating = (bool) $data['isActive'];

            if (! $activating && $category->is_active && $category->expenses()->exists()) {
                return redirect()
                    ->back()
                    ->with('error', __('exp.errCategoryInUse'));
            }

            $update['is_active'] = $activating;
        }

        $category->fill($update);
        $changedColumns = array_keys($category->getDirty());

        if ($changedColumns !== []) {
            $category->save();
        }

        Audit::log($me, 'EXPENSE_CATEGORY_UPDATED', 'EXPENSE_CATEGORY', $category->id, [
            'name' => $category->name,
            'changedFields' => $changedColumns,
        ]);

        return redirect()
            ->back()
            ->with('success', __('exp.categoryUpdated'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Store an uploaded receipt under storage/app/receipts with a
     * collision-free filename; null when no valid file was submitted.
     *
     * Security: the extension is derived from the file CONTENT
     * (guessExtension), never from the client-supplied filename, and only
     * receipt-safe types are accepted — an upload can never plant an
     * executable extension in storage. The route validation
     * (mimes:jpg,jpeg,png,webp,pdf) is the first gate; this is the second.
     */
    private function storeReceipt(Request $request): ?string
    {
        $file = $request->file('receipt');

        if ($file === null || ! $file->isValid()) {
            return null;
        }

        $extension = strtolower((string) $file->guessExtension());

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
            return null;
        }

        return $file->storeAs('receipts', uniqid('', true).'-'.time().'.'.$extension, 'local');
    }

    /**
     * Best-effort removal of a stored receipt file (never breaks the
     * parent flow when the file is already gone).
     */
    private function deleteReceiptFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $absolute = storage_path('app/'.$path);

        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * Pre-validation guard for the NOT NULL category/description/amount/
     * date columns (see PaymentController::rejectBlank).
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

    /**
     * Parse a date-range filter value leniently.
     */
    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}
