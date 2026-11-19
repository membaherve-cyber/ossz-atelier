<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit;
use App\Services\Codes;
use App\Support\Atelier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Suppliers and material purchases (enhancement spec §10) — GET/POST
 * /suppliers + /purchases.
 *
 * Purchases carry their lines in purchase_items; the total is always
 * recomputed server-side from the submitted lines. Marking a purchase
 * PAID books exactly ONE expense row (source_type=PURCHASE) in the
 * single cost ledger via Expense::bookForSource() — idempotent, so
 * re-submitting or transitioning twice never double counts. Unpaid
 * purchases stay out of the expense ledger.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class SupplierController
{
    /** Purchases per page on the index listing. */
    private const PER_PAGE = 20;

    /**
     * GET /suppliers (route perm accounting.suppliers) — the suppliers
     * tab (with purchase counts) and the purchases tab (paginated), plus
     * the order and materials-category lists the purchase form needs.
     */
    public function index(Request $request): View
    {
        $tab = (string) ($request->query('tab') ?? 'suppliers');

        if (! in_array($tab, ['suppliers', 'purchases'], true)) {
            $tab = 'suppliers';
        }

        return view('accounting.suppliers.index', [
            'suppliers' => Supplier::query()
                ->withCount('purchases')
                ->orderBy('name')
                ->get(),
            'purchases' => Purchase::query()
                ->with(['supplier', 'order'])
                ->orderByDesc('purchase_date')
                ->orderByDesc('created_at')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'orders' => Order::query()->orderByDesc('order_date')->get(['id', 'order_number']),
            'materialCategories' => ExpenseCategory::query()
                ->where('group', 'MATERIALS')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'paymentStatuses' => Atelier::PURCHASE_PAYMENT_STATUSES,
            'methods' => Atelier::PAYMENT_METHODS,
            'tab' => $tab,
        ]);
    }

    /**
     * POST /suppliers (route perm accounting.suppliers) — register a
     * material supplier.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $this->rejectBlank($request, [
            'name' => __('sup.errName'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:120'],
            'materials' => ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $supplier = Supplier::query()->create([
            'name' => trim((string) $data['name']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'email' => $data['email'] ?? null,
            'materials' => $data['materials'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Audit::log($me, 'SUPPLIER_CREATED', 'SUPPLIER', $supplier->id, [
            'name' => $supplier->name,
            'phone' => $supplier->phone,
        ]);

        return redirect()
            ->back()
            ->with('success', __('sup.supplierCreated'));
    }

    /**
     * POST /suppliers/{id}/update (route perm accounting.suppliers) —
     * partial supplier edit; present-null clears the nullable columns,
     * only dirty columns are written + audited.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $supplier = Supplier::query()->findOrFail($id);

        $this->rejectBlank($request, [
            'name' => __('sup.errName'),
        ]);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:120'],
            'materials' => ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $update = [];

        if (isset($data['name'])) {
            $update['name'] = trim((string) $data['name']);
        }

        foreach (['phone', 'address', 'email', 'materials', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field] ?? null;
            }
        }

        $supplier->fill($update);
        $changedColumns = array_keys($supplier->getDirty());

        if ($changedColumns !== []) {
            $supplier->save();
        }

        Audit::log($me, 'SUPPLIER_UPDATED', 'SUPPLIER', $supplier->id, [
            'name' => $supplier->name,
            'changedFields' => $changedColumns,
        ]);

        return redirect()
            ->back()
            ->with('success', __('sup.supplierUpdated'));
    }

    /**
     * POST /suppliers/{id}/delete (route perm accounting.suppliers) —
     * remove a supplier that has no recorded purchases.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $supplier = Supplier::query()->findOrFail($id);

        if ($supplier->purchases()->exists()) {
            return redirect()
                ->back()
                ->with('error', __('sup.deleteBlocked'));
        }

        Audit::log($me, 'SUPPLIER_DELETED', 'SUPPLIER', $supplier->id, [
            'name' => $supplier->name,
        ]);

        $supplier->delete();

        return redirect()
            ->back()
            ->with('success', __('sup.supplierDeleted'));
    }

    /**
     * POST /purchases (route perm accounting.suppliers) — record a
     * material purchase with its lines. The reference PUR-#### is
     * generated inside the same transaction as the purchase + items; a
     * purchase recorded as PAID books its ledger expense immediately
     * (exactly once, source_type=PURCHASE).
     */
    public function storePurchase(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $this->rejectBlank($request, [
            'supplierId' => __('sup.errSupplier'),
            'purchaseDate' => __('sup.errPurchaseDate'),
            'description' => __('sup.errSummary'),
        ]);

        $data = $request->validate([
            'supplierId' => ['required', 'string', Rule::exists('suppliers', 'id')],
            'purchaseDate' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'categoryId' => ['nullable', 'string', Rule::exists('expense_categories', 'id')],
            'paymentStatus' => ['nullable', 'string', Rule::in(Atelier::PURCHASE_PAYMENT_STATUSES)],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'orderId' => ['nullable', 'string', Rule::exists('orders', 'id')],
            'notes' => ['nullable', 'string', 'max:4000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unitPrice' => ['required', 'numeric', 'min:0'],
        ]);

        $supplier = Supplier::query()->findOrFail((string) $data['supplierId']);
        $paymentStatus = (string) ($data['paymentStatus'] ?? 'UNPAID');
        $receiptPath = $this->storeReceipt($request);

        [$purchase, $expenseBooked] = DB::transaction(function () use ($data, $me, $supplier, $paymentStatus, $receiptPath): array {
            ['lines' => $lines, 'total' => $total] = $this->buildLines($data['items']);

            $purchase = Codes::unique('purchase', fn (string $reference): Purchase => Purchase::query()->create([
                'reference' => $reference,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'purchase_date' => Carbon::parse((string) $data['purchaseDate']),
                'description' => trim((string) $data['description']),
                'expense_category_id' => $data['categoryId'] ?? null,
                'total_amount' => $total,
                'currency' => 'XAF',
                'payment_status' => $paymentStatus,
                'method' => $data['method'] ?? null,
                'receipt_path' => $receiptPath,
                'order_id' => $data['orderId'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by_id' => $me->id,
                'recorded_by_name' => $me->name,
            ]));

            foreach ($lines as $line) {
                $purchase->items()->create($line);
            }

            $expenseBooked = false;

            if ($paymentStatus === 'PAID') {
                $this->bookPurchaseExpense($purchase, $me);

                $expenseBooked = true;
            }

            return [$purchase, $expenseBooked];
        });

        Audit::log($me, 'PURCHASE_RECORDED', 'PURCHASE', $purchase->id, [
            'reference' => $purchase->reference,
            'supplierName' => $purchase->supplier_name,
            'total' => $purchase->total_amount,
            'paymentStatus' => $purchase->payment_status,
            'expenseBooked' => $expenseBooked,
        ]);

        return redirect()
            ->back()
            ->with('success', __($expenseBooked ? 'sup.paidToast' : 'sup.purchaseCreated'));
    }

    /**
     * POST /purchases/{id}/update (route perm accounting.suppliers) —
     * partial purchase edit; present-null clears the nullable columns
     * (categoryId / method / orderId / notes). Submitted items fully
     * replace the line set and recompute the total inside one
     * transaction; a transition to PAID books the ledger expense
     * (idempotent — an already-booked purchase is returned untouched).
     */
    public function updatePurchase(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $purchase = Purchase::query()->findOrFail($id);

        $this->rejectBlank($request, [
            'supplierId' => __('sup.errSupplier'),
            'purchaseDate' => __('sup.errPurchaseDate'),
            'description' => __('sup.errSummary'),
        ]);

        $data = $request->validate([
            'supplierId' => ['nullable', 'string', Rule::exists('suppliers', 'id')],
            'purchaseDate' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'categoryId' => ['nullable', 'string', Rule::exists('expense_categories', 'id')],
            'paymentStatus' => ['nullable', 'string', Rule::in(Atelier::PURCHASE_PAYMENT_STATUSES)],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'orderId' => ['nullable', 'string', Rule::exists('orders', 'id')],
            'notes' => ['nullable', 'string', 'max:4000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'items' => ['nullable', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unitPrice' => ['required', 'numeric', 'min:0'],
        ]);

        $wasPaid = $purchase->payment_status === 'PAID';

        [$changedColumns, $expenseBooked, $oldReceiptPath] = DB::transaction(function () use ($data, $me, $purchase, $wasPaid, $request): array {
            $update = [];

            if (isset($data['supplierId'])) {
                $supplier = Supplier::query()->findOrFail((string) $data['supplierId']);
                $update['supplier_id'] = $supplier->id;
                $update['supplier_name'] = $supplier->name;
            }

            if (isset($data['purchaseDate'])) {
                $update['purchase_date'] = Carbon::parse((string) $data['purchaseDate']);
            }

            if (isset($data['description'])) {
                $update['description'] = trim((string) $data['description']);
            }

            if (array_key_exists('categoryId', $data)) {
                $update['expense_category_id'] = $data['categoryId'] ?? null;
            }

            if (isset($data['paymentStatus'])) {
                $update['payment_status'] = (string) $data['paymentStatus'];
            }

            if (array_key_exists('method', $data)) {
                $update['method'] = $data['method'] ?? null;
            }

            if (array_key_exists('orderId', $data)) {
                $update['order_id'] = $data['orderId'] ?? null;
            }

            if (array_key_exists('notes', $data)) {
                $update['notes'] = $data['notes'] ?? null;
            }

            // Full line replacement + server-side total recompute.
            if (isset($data['items'])) {
                ['lines' => $lines, 'total' => $total] = $this->buildLines($data['items']);

                $purchase->items()->delete();

                foreach ($lines as $line) {
                    $purchase->items()->create($line);
                }

                $update['total_amount'] = $total;
            }

            $newReceipt = $this->storeReceipt($request);

            if ($newReceipt !== null) {
                $update['receipt_path'] = $newReceipt;
            }

            $purchase->fill($update);
            $changedColumns = array_keys($purchase->getDirty());

            if ($changedColumns !== []) {
                $purchase->save();
            }

            // ---- Keep the expense ledger in sync with the purchase --------
            // A PAID purchase owns exactly one source_type=PURCHASE expense
            // row: booking on the PAID transition, amount/category/date
            // refresh on every paid edit, and removal when the purchase is
            // switched back to an unpaid status (an unpaid purchase is not
            // a cost that left the workshop).
            $expenseBooked = false;
            $linkedExpense = Expense::query()
                ->where('source_type', 'PURCHASE')
                ->where('source_id', $purchase->id)
                ->first();

            if ($purchase->payment_status === 'PAID') {
                if ($linkedExpense === null) {
                    if (! $wasPaid) {
                        $this->bookPurchaseExpense($purchase, $me);
                        $expenseBooked = true;
                    }
                } else {
                    // Refresh the booked row so the ledger never shows a
                    // stale amount/category/date after a paid-purchase edit.
                    $linkedExpense->forceFill([
                        'expense_category_id' => $purchase->expense_category_id
                            ?? ExpenseCategory::defaultPurchaseCategory()->id,
                        'description' => $purchase->description,
                        'amount' => (float) $purchase->total_amount,
                        'date_incurred' => $purchase->purchase_date->toDateString(),
                        'date_paid' => $purchase->purchase_date->toDateString(),
                        'method' => $purchase->method ?? 'CASH',
                        'payee' => $purchase->supplier_name,
                        'order_id' => $purchase->order_id,
                    ])->save();
                }
            } elseif ($wasPaid) {
                // PAID → UNPAID/PARTIAL: the money is no longer out, so the
                // auto-booked cost must go (the audit trail keeps the
                // purchase's own history).
                $linkedExpense?->delete();
            }

            // Old receipt file is only removed AFTER the transaction — a
            // rollback must never leave the still-live row pointing at a
            // deleted file.
            $oldReceiptPath = $newReceipt !== null ? $purchase->getOriginal('receipt_path') : null;

            return [$changedColumns, $expenseBooked, $oldReceiptPath];
        });

        if ($oldReceiptPath !== null && $oldReceiptPath !== '') {
            $this->deleteReceiptFile($oldReceiptPath);
        }

        Audit::log($me, 'PURCHASE_UPDATED', 'PURCHASE', $purchase->id, [
            'reference' => $purchase->reference,
            'supplierName' => $purchase->supplier_name,
            'changedFields' => $changedColumns,
            'expenseBooked' => $expenseBooked,
        ]);

        return redirect()
            ->back()
            ->with('success', __($expenseBooked ? 'sup.paidToast' : 'sup.purchaseUpdated'));
    }

    /**
     * POST /purchases/{id}/delete (route perm accounting.suppliers) —
     * remove a purchase together with its lines (cascade) and its
     * receipt file; the deletion is audited before the row disappears.
     */
    public function destroyPurchase(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $purchase = Purchase::query()->findOrFail($id);

        Audit::log($me, 'PURCHASE_DELETED', 'PURCHASE', $purchase->id, [
            'reference' => $purchase->reference,
            'supplierName' => $purchase->supplier_name,
            'total' => $purchase->total_amount,
            'paymentStatus' => $purchase->payment_status,
        ]);

        $this->deleteReceiptFile($purchase->receipt_path);

        $purchase->delete(); // purchase_items cascade

        return redirect()
            ->back()
            ->with('success', __('sup.purchaseDeleted'));
    }

    /**
     * GET /purchases/{id}/receipt (route perm accounting.suppliers) —
     * stream the stored purchase receipt from storage/app/receipts.
     * The resolved path is verified to stay INSIDE storage/app so a
     * tampered receipt_path can never make the app stream arbitrary
     * server files.
     */
    public function purchaseReceipt(string $id): BinaryFileResponse
    {
        $purchase = Purchase::query()->findOrFail($id);

        return $this->receiptResponse($purchase->receipt_path);
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Normalize submitted purchase lines and recompute the totals
     * server-side (line_total = round(quantity × unitPrice, 2)).
     *
     * @param  list<array{description: mixed, quantity: mixed, unitPrice: mixed}>  $items
     * @return array{lines: list<array{description: string, quantity: float, unit_price: float, line_total: float}>, total: float}
     */
    private function buildLines(array $items): array
    {
        $lines = [];
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unitPrice'];
            $lineTotal = round($quantity * $unitPrice, 2);

            $lines[] = [
                'description' => (string) $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $total += $lineTotal;
        }

        return ['lines' => $lines, 'total' => round($total, 2)];
    }

    /**
     * Book the single ledger expense of a paid purchase (source PURCHASE,
     * unique per purchase — idempotent). The materials category of the
     * purchase is used, falling back to the default purchase category.
     */
    private function bookPurchaseExpense(Purchase $purchase, User $me): Expense
    {
        return Expense::bookForSource('PURCHASE', $purchase->id, [
            'expense_category_id' => $purchase->expense_category_id
                ?? ExpenseCategory::defaultPurchaseCategory()->id,
            'description' => $purchase->description,
            'amount' => (float) $purchase->total_amount,
            'currency' => (string) $purchase->currency,
            'date_incurred' => $purchase->purchase_date->toDateString(),
            'date_paid' => $purchase->purchase_date->toDateString(),
            'method' => $purchase->method ?? 'CASH',
            'payee' => $purchase->supplier_name,
            'order_id' => $purchase->order_id,
            'status' => 'PAID',
            'recorded_by_id' => $me->id,
            'recorded_by_name' => $me->name,
            'approved_by_id' => $me->id,
            'approved_by_name' => $me->name,
            'approved_at' => now(),
        ]);
    }

    /**
     * Store an uploaded receipt under storage/app/receipts with a
     * collision-free filename; null when no valid file was submitted.
     *
     * Security: the extension is derived from the file CONTENT (guessExtension),
     * never from the client-supplied filename, and only receipt-safe types
     * are accepted — an upload can never plant an executable extension in
     * storage. The route validation (mimes:jpg,jpeg,png,webp,pdf) is the
     * first gate; this is the second.
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
     * Pre-validation guard for the NOT NULL supplier/date/description
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

    /**
     * Stream a stored receipt file after verifying the resolved real path
     * stays inside storage/app (path-containment guard).
     */
    private function receiptResponse(?string $path): BinaryFileResponse
    {
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
}
