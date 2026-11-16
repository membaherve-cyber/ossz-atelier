<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Audit;
use App\Services\Codes;
use App\Support\Atelier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Manually registered garment orders (spec §3: /orders).
 *
 * Ported from the Next.js reference implementation:
 * - GET/POST src/app/api/atelier/orders/route.ts — the filterable listing
 *   (search / priority / inclusive order-date range) and registration. Order
 *   numbers are unique; a blank number auto-generates the next free ORD-####
 *   suggestion via Codes::unique(). The reference's inline new-customer flow
 *   is intentionally NOT ported: the Laravel contract registers customers
 *   separately, so an existing customerId is required.
 * - GET/PATCH src/app/api/atelier/orders/[id]/route.ts — the detail fragment
 *   for the orders/index Alpine modal and partial updates with
 *   changed-field auditing (Prisma camelCase field names).
 * The delete flow (POST /orders/{id}/delete) is the Laravel contract
 * addition: an order can only be deleted while it has no production tasks.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class OrderController
{
    /** Orders per page on the index listing. */
    private const PER_PAGE = 20;

    /**
     * GET /orders (route perm orders.view) — filterable order list with
     * production summaries. Filters accept both the contract's `q` and the
     * reference's `search` parameter name; invalid priorities and
     * unparseable dates are silently ignored (lenient GET handling like the
     * reference list endpoint).
     */
    public function index(Request $request): View
    {
        $q = trim((string) ($request->query('q') ?? $request->query('search') ?? ''));
        $priority = (string) ($request->query('priority') ?? '');
        $from = trim((string) ($request->query('from') ?? ''));
        $to = trim((string) ($request->query('to') ?? ''));

        $orders = Order::query()
            ->with(['customer', 'tasks']);

        if ($q !== '') {
            // order_number OR description OR customer name (reference OR
            // filter of the list endpoint).
            $orders->where(function (Builder $query) use ($q): void {
                $query->where('order_number', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%')
                    ->orWhereHas('customer', function (Builder $query) use ($q): void {
                        $query->where('name', 'like', '%'.$q.'%');
                    });
            });
        }

        if (in_array($priority, Atelier::PRIORITIES, true)) {
            $orders->where('priority', $priority);
        }

        // Inclusive order-date range; a date-only "to" value covers the
        // whole day (endOfRange() port).
        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        if ($toDate !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1) {
            $toDate = $toDate->endOfDay();
        }

        if ($fromDate !== null) {
            $orders->where('order_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $orders->where('order_date', '<=', $toDate);
        }

        $orders = $orders
            ->orderByDesc('created_at')
            ->orderBy('order_number')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'customers' => Customer::query()->orderBy('name')->get(),
            'filters' => [
                'q' => $q,
                'priority' => $priority,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * POST /orders (route perm orders.manage) — register a manually entered
     * order. A blank order number auto-generates the next free ORD-#### via
     * Codes::unique(); a supplied number must be unique (Rule::unique plus a
     * UniqueConstraintViolationException race guard — the web equivalent of
     * the reference 409 response).
     */
    public function store(Request $request): RedirectResponse
    {
        $me = $request->user();

        $this->mergePieceCountAlias($request);

        $this->rejectBlank($request, [
            'orderDate' => __('orders.errOrderDate'),
            'priority' => __('validation.required', ['attribute' => __('orders.fieldPriority')]),
            'pieceCount' => __('orders.errPieces'),
            'garmentCategory' => __('orders.errCategory'),
            'description' => __('orders.errDescription'),
        ]);

        $data = $request->validate([
            'customerId' => ['required', 'string', Rule::exists('customers', 'id')],
            'orderNumber' => ['nullable', 'string', 'max:50', Rule::unique('orders', 'order_number')],
            'orderDate' => ['required', 'date'],
            'expectedDeliveryDate' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', Rule::in(Atelier::PRIORITIES)],
            'pieceCount' => ['required', 'integer', 'min:1', 'max:200'],
            'garmentCategory' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $orderNumber = $data['orderNumber'] ?? null;
        $priority = (string) ($data['priority'] ?? 'NORMAL');

        $createOrder = function (string $number) use ($data, $me, $priority): Order {
            return Order::query()->create([
                'order_number' => $number,
                'customer_id' => $data['customerId'],
                'order_date' => $data['orderDate'],
                'expected_delivery_date' => $data['expectedDeliveryDate'],
                'description' => $data['description'],
                'garment_category' => $data['garmentCategory'],
                'number_of_pieces' => (int) $data['pieceCount'],
                'instructions' => $data['instructions'],
                'notes' => $data['notes'],
                'priority' => $priority,
                'created_by_id' => $me->id,
            ]);
        };

        try {
            // Blank number: Codes::unique() retries with a fresh ORD-#### on
            // collision; a supplied number rides on the validation rule plus
            // the race guard below.
            $order = $orderNumber === null
                ? Codes::unique('order', $createOrder)
                : $createOrder($orderNumber);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'orderNumber' => __('validation.unique', ['attribute' => __('orders.fieldOrderNumber')]),
            ]);
        }

        Audit::log($me, 'ORDER_CREATED', 'ORDER', $order->id, [
            'orderNumber' => $order->order_number,
            'customerName' => $order->customer->name,
            'priority' => $order->priority,
        ]);

        return redirect()
            ->back()
            ->with('success', __('orders.registeredToast'));
    }

    /**
     * GET /orders/{id} (route perm orders.view) — order detail as a
     * standalone HTML fragment (no layout) fetched by the orders/index
     * Alpine modal. Tasks are listed in registration order (created_at asc)
     * with their assignee and checklist states, mirroring the GET handler of
     * orders/[id]/route.ts.
     */
    public function show(string $id): View
    {
        $order = Order::query()
            ->with([
                'customer',
                'tasks' => function (HasMany $query): void {
                    $query->orderBy('created_at')->with(['assignedStaff', 'stageStates']);
                },
            ])
            ->findOrFail($id);

        return view('orders.show', ['order' => $order]);
    }

    /**
     * POST /orders/{id}/update (route perm orders.manage) — edit order
     * details, ported from the PATCH handler of orders/[id]/route.ts: every
     * field is optional, the linked customer cannot be changed, present-null
     * clears the nullable columns (expectedDeliveryDate / instructions /
     * notes) and only columns whose submitted value differs are written. The
     * audit entry lists the changed fields under their Prisma (camelCase)
     * names, exactly like the reference changedFields array.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $order = Order::query()->findOrFail($id);

        $this->mergePieceCountAlias($request);

        $this->rejectBlank($request, [
            'orderNumber' => __('orders.errOrderNumber'),
            'orderDate' => __('orders.errOrderDate'),
            'priority' => __('validation.required', ['attribute' => __('orders.fieldPriority')]),
            'pieceCount' => __('orders.errPieces'),
            'garmentCategory' => __('orders.errCategory'),
            'description' => __('orders.errDescription'),
        ]);

        $data = $request->validate([
            'orderNumber' => ['nullable', 'string', 'max:50', Rule::unique('orders', 'order_number')->ignore($order->id)],
            'orderDate' => ['nullable', 'date'],
            'expectedDeliveryDate' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', Rule::in(Atelier::PRIORITIES)],
            'pieceCount' => ['nullable', 'integer', 'min:1', 'max:200'],
            'garmentCategory' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $update = [];

        if (array_key_exists('orderNumber', $data)) {
            $update['order_number'] = $data['orderNumber'];
        }

        if (array_key_exists('orderDate', $data)) {
            $update['order_date'] = $data['orderDate'];
        }

        if (array_key_exists('expectedDeliveryDate', $data)) {
            $update['expected_delivery_date'] = $data['expectedDeliveryDate'];
        }

        if (array_key_exists('priority', $data)) {
            $update['priority'] = $data['priority'];
        }

        if (array_key_exists('pieceCount', $data)) {
            $update['number_of_pieces'] = (int) $data['pieceCount'];
        }

        if (array_key_exists('garmentCategory', $data)) {
            $update['garment_category'] = $data['garmentCategory'];
        }

        if (array_key_exists('description', $data)) {
            $update['description'] = $data['description'];
        }

        if (array_key_exists('instructions', $data)) {
            $update['instructions'] = $data['instructions'];
        }

        if (array_key_exists('notes', $data)) {
            $update['notes'] = $data['notes'];
        }

        $order->fill($update);
        $changedColumns = array_keys($order->getDirty());

        if ($changedColumns !== []) {
            try {
                $order->save();
            } catch (UniqueConstraintViolationException) {
                // Race on the unique order_number column — same inline error
                // as the reference 409 response.
                throw ValidationException::withMessages([
                    'orderNumber' => __('validation.unique', ['attribute' => __('orders.fieldOrderNumber')]),
                ]);
            }

            Audit::log($me, 'ORDER_UPDATED', 'ORDER', $order->id, [
                'orderNumber' => $order->order_number,
                'changedFields' => $this->prismaFieldNames($changedColumns),
            ]);
        }

        return redirect()
            ->back()
            ->with('success', __('orders.updatedToast'));
    }

    /**
     * POST /orders/{id}/delete (route perm orders.manage) — remove a
     * task-less order. Orders that already have production tasks are kept
     * and the caller is flashed orders.hasTasksError (spec §5: "Order delete
     * only when it has no tasks"). There is no ORDER_DELETED audit code, so
     * deletes are recorded as ORDER_UPDATED with {deleted: true, code: the
     * order number — the order's business code, uniform with the customers'
     * CUST-XXXX}.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $order = Order::query()->findOrFail($id);

        if ($order->tasks()->count() !== 0) {
            return redirect()
                ->back()
                ->with('error', __('orders.hasTasksError'));
        }

        $orderNumber = $order->order_number;

        $order->delete();

        Audit::log($me, 'ORDER_UPDATED', 'ORDER', $order->id, [
            'deleted' => true,
            'code' => $orderNumber,
        ]);

        return redirect()
            ->route('orders.index')
            ->with('success', __('orders.deletedToast'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * The view contract names the piece field "pieceCount"; the reference
     * API's "numberOfPieces" spelling is merged in as an alias whenever the
     * contract name is absent.
     */
    private function mergePieceCountAlias(Request $request): void
    {
        if ($request->missing('pieceCount') && $request->has('numberOfPieces')) {
            $request->merge(['pieceCount' => $request->input('numberOfPieces')]);
        }
    }

    /**
     * Pre-validation guard for NOT NULL columns: with ConvertEmptyStringsToNull
     * active, a submitted-but-blank input arrives as null, which the nullable
     * rules would swallow before hitting a NOT NULL database column. Such
     * values are rejected with the matching message ($fields maps input
     * name => message); absent fields stay untouched and keep their defaults.
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
     * Audit field names use the Prisma camelCase spellings of the changed
     * order columns (orderNumber, numberOfPieces, …), mirroring the
     * changedFields array of the reference PATCH handler.
     *
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function prismaFieldNames(array $columns): array
    {
        $names = [
            'order_number' => 'orderNumber',
            'order_date' => 'orderDate',
            'expected_delivery_date' => 'expectedDeliveryDate',
            'garment_category' => 'garmentCategory',
            'number_of_pieces' => 'numberOfPieces',
        ];

        return array_values(array_map(
            static fn (string $column): string => $names[$column] ?? $column,
            $columns
        ));
    }

    /**
     * Parse a date-range filter value; unparseable input (typos in the URL)
     * is treated as absent so the listing stays usable.
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
