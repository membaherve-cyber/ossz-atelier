<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Audit;
use App\Services\Codes;
use App\Support\Atelier;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Income and customer payments (enhancement spec §6) — GET/POST /payments.
 *
 * Outstanding balances are always DERIVED (order total_value −
 * SUM(payments)) and never stored, so edited orders or payments can never
 * go stale: a payment may never exceed the order's outstanding balance
 * (store validates against every payment, update against all OTHER
 * payments of the target order). References PAY-0001 are system-generated
 * via Codes::unique(); the recording user is snapshotted into
 * recorded_by_id / recorded_by_name.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class PaymentController
{
    /** Payments per page on the index listing. */
    private const PER_PAGE = 20;

    /**
     * GET /payments (route perm accounting.payments) — filterable payment
     * listing plus the registered orders the record-payment form offers
     * (each carrying its paid_total sum for the outstanding preview).
     */
    public function index(Request $request): View
    {
        $q = trim((string) ($request->query('q') ?? ''));
        $method = (string) ($request->query('method') ?? '');
        $from = trim((string) ($request->query('from') ?? ''));
        $to = trim((string) ($request->query('to') ?? ''));

        $payments = Payment::query()->with('order.customer');

        if ($q !== '') {
            // reference / order number / customer name contains.
            $payments->where(function (Builder $query) use ($q): void {
                $query->where('reference', 'like', '%'.$q.'%')
                    ->orWhereHas('order', function (Builder $query) use ($q): void {
                        $query->where('order_number', 'like', '%'.$q.'%')
                            ->orWhereHas('customer', fn (Builder $query): Builder => $query->where('name', 'like', '%'.$q.'%'));
                    });
            });
        }

        if (in_array($method, Atelier::PAYMENT_METHODS, true)) {
            $payments->where('method', $method);
        }

        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        if ($toDate !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1) {
            $toDate = $toDate->endOfDay();
        }

        if ($fromDate !== null) {
            $payments->where('payment_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $payments->where('payment_date', '<=', $toDate);
        }

        $payments = $payments
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('accounting.payments.index', [
            'payments' => $payments,
            'orders' => Order::query()
                ->where('status', 'REGISTERED')
                ->with('customer')
                ->withSum('payments as paid_total', 'amount')
                ->orderByDesc('order_date')
                ->get(),
            'filters' => [
                'q' => $q,
                'method' => $method,
                'from' => $from,
                'to' => $to,
            ],
            'methods' => Atelier::PAYMENT_METHODS,
        ]);
    }

    /**
     * POST /payments (route perm accounting.payments) — record a customer
     * payment against a REGISTERED order. The order must have a recorded
     * total value and the amount may not exceed the outstanding balance
     * (total value − payments received so far).
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $this->rejectBlank($request, [
            'orderId' => __('pay.errOrder'),
            'amount' => __('pay.errAmount'),
            'paymentDate' => __('pay.errDate'),
        ]);

        $data = $request->validate([
            'orderId' => ['required', 'string', Rule::exists('orders', 'id')],
            'amount' => ['required', 'numeric', 'gt:0'],
            'paymentDate' => ['required', 'date'],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'txnRef' => ['nullable', 'string', 'max:100', Rule::unique('payments', 'txn_ref')],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $amount = round((float) $data['amount'], 2);

        // One transaction with a row lock on the order: the outstanding
        // balance check and the insert are atomic, so two concurrent
        // submissions can never both spend the same remaining balance
        // (prevents over-collection races).
        $payment = DB::transaction(function () use ($data, $me, $amount): Payment {
            /** @var Order $order */
            $order = Order::query()
                ->whereKey((string) $data['orderId'])
                ->lockForUpdate()
                ->firstOrFail();

            // Only REGISTERED orders can receive payments — DRAFT orders
            // are not billed and their value never counts as revenue.
            if ($order->status !== 'REGISTERED') {
                throw ValidationException::withMessages([
                    'orderId' => __('pay.errOrderNotRegistered'),
                ]);
            }

            if ($order->total_value === null) {
                throw ValidationException::withMessages([
                    'orderId' => __('pay.errNoValue'),
                ]);
            }

            $outstanding = round((float) $order->total_value - $order->paidTotal(), 2);

            if ($amount > $outstanding + 0.005) {
                throw ValidationException::withMessages([
                    'amount' => __('pay.errExceedsBalance'),
                ]);
            }

            return Codes::unique('payment', fn (string $reference): Payment => Payment::query()->create([
                'reference' => $reference,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'amount' => $amount,
                'currency' => Money::DEFAULT_CURRENCY,
                'payment_date' => Carbon::parse((string) $data['paymentDate']),
                'method' => (string) ($data['method'] ?? 'CASH'),
                'txn_ref' => $data['txnRef'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by_id' => $me->id,
                'recorded_by_name' => $me->name,
            ]));
        });

        $order = $payment->order;

        Audit::log($me, 'PAYMENT_RECORDED', 'PAYMENT', $payment->id, [
            'reference' => $payment->reference,
            'orderNumber' => $order->order_number,
            'customerName' => $order->customer?->name,
            'amount' => $amount,
            'method' => $payment->method,
        ]);

        return redirect()
            ->back()
            ->with('success', __('pay.createdToast'));
    }

    /**
     * POST /payments/{id}/update (route perm accounting.payments) — edit a
     * payment. The outstanding balance is re-checked against the target
     * order EXCLUDING this payment; only dirty columns are written and the
     * changed fields are audited.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $payment = Payment::query()->findOrFail($id);

        $this->rejectBlank($request, [
            'orderId' => __('pay.errOrder'),
            'amount' => __('pay.errAmount'),
            'paymentDate' => __('pay.errDate'),
        ]);

        $data = $request->validate([
            'orderId' => ['nullable', 'string', Rule::exists('orders', 'id')],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'paymentDate' => ['nullable', 'date'],
            'method' => ['nullable', 'string', Rule::in(Atelier::PAYMENT_METHODS)],
            'txnRef' => ['nullable', 'string', 'max:100', Rule::unique('payments', 'txn_ref')->ignore($payment->id)],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $amount = isset($data['amount'])
            ? round((float) $data['amount'], 2)
            : (float) $payment->amount;

        // Atomic balance re-check + save (the target order is locked for the
        // whole transaction so concurrent edits cannot both pass against
        // the same remaining balance).
        [$changedColumns] = DB::transaction(function () use ($data, $payment, $me, $amount): array {
            $targetOrderId = isset($data['orderId'])
                ? (string) $data['orderId']
                : (string) $payment->order_id;

            /** @var Order $order */
            $order = Order::query()
                ->whereKey($targetOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'REGISTERED') {
                throw ValidationException::withMessages([
                    'orderId' => __('pay.errOrderNotRegistered'),
                ]);
            }

            if ($order->total_value === null) {
                throw ValidationException::withMessages([
                    'orderId' => __('pay.errNoValue'),
                ]);
            }

            // Outstanding balance of the target order excluding THIS payment.
            $otherPaid = (float) $order->payments()->where('id', '!=', $payment->id)->sum('amount');
            $outstanding = round((float) $order->total_value - $otherPaid, 2);

            if ($amount > $outstanding + 0.005) {
                throw ValidationException::withMessages([
                    'amount' => __('pay.errExceedsBalance'),
                ]);
            }

            $update = [];

            if (isset($data['orderId'])) {
                $update['order_id'] = $order->id;
                $update['customer_id'] = $order->customer_id;
            }

            if (isset($data['amount'])) {
                $update['amount'] = $amount;
            }

            if (isset($data['paymentDate'])) {
                $update['payment_date'] = Carbon::parse((string) $data['paymentDate']);
            }

            if (isset($data['method'])) {
                $update['method'] = (string) $data['method'];
            }

            if (array_key_exists('txnRef', $data)) {
                $update['txn_ref'] = $data['txnRef'] ?? null;
            }

            if (array_key_exists('notes', $data)) {
                $update['notes'] = $data['notes'] ?? null;
            }

            $payment->fill($update);
            $changedColumns = array_keys($payment->getDirty());

            if ($changedColumns !== []) {
                $payment->save();
            }

            return [$changedColumns];
        });

        Audit::log($me, 'PAYMENT_UPDATED', 'PAYMENT', $payment->id, [
            'reference' => $payment->reference,
            'changedFields' => $changedColumns,
        ]);

        return redirect()
            ->back()
            ->with('success', __('pay.updatedToast'));
    }

    /**
     * POST /payments/{id}/delete (route perm accounting.payments) — remove
     * a payment; the outstanding balance is simply recomputed from the
     * remaining rows. The deletion is audited first (the row and its
     * snapshots disappear with it).
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $payment = Payment::query()->with('order')->findOrFail($id);

        Audit::log($me, 'PAYMENT_DELETED', 'PAYMENT', $payment->id, [
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'orderNumber' => $payment->order?->order_number,
        ]);

        $payment->delete();

        return redirect()
            ->back()
            ->with('success', __('pay.deletedToast'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Pre-validation guard for the NOT NULL order/amount/date columns:
     * a submitted-but-blank input arrives as null (ConvertEmptyStringsToNull)
     * and is rejected with the matching pay.* line; absent fields stay
     * untouched and keep their current values.
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
