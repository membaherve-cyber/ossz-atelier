<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\Audit;
use App\Services\Codes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

/**
 * Manually registered customer directory (spec §3: /customers).
 *
 * Ported from the Next.js reference implementation:
 * - GET/POST src/app/api/atelier/customers/route.ts — the searchable
 *   directory (name / phone / email / code contains) and registration with
 *   an auto-generated unique CUST-#### code plus the createdById snapshot;
 * - PATCH src/app/api/atelier/customers/[id]/route.ts — partial updates
 *   with changed-field auditing (the column names double as the Prisma
 *   camelCase field names for this model).
 * The delete flow (POST /customers/{id}/delete) is the Laravel contract
 * addition: a customer can only be deleted while it has no orders (spec
 * §5: "Customer delete only when no orders").
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class CustomerController
{
    /**
     * GET /customers (route perm customers.view) — searchable customer
     * directory. Each row carries its order count (ordersCount, view
     * contract §8) plus the minimal eager orders(id, customer_id) ->
     * tasks(order_id, status, progress) set the view aggregates over.
     * Both the contract's `q` and the reference's `search` parameter
     * names are accepted; rows are ordered by name asc.
     */
    public function index(Request $request): View
    {
        $q = trim((string) ($request->query('q') ?? $request->query('search') ?? ''));

        $customers = Customer::query()
            ->withCount('orders as ordersCount')
            ->with(['orders' => function (HasMany $query): void {
                $query->select(['id', 'customer_id'])
                    ->with(['tasks' => function (HasMany $query): void {
                        $query->select(['order_id', 'status', 'progress']);
                    }]);
            }]);

        if ($q !== '') {
            // name / phone / email / code contains (reference OR filter).
            $customers->where(function (Builder $query) use ($q): void {
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%');
            });
        }

        $customers = $customers->orderBy('name')->get();

        return view('customers.index', [
            'customers' => $customers,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    /**
     * POST /customers (route perm customers.manage) — register a customer.
     * The unique CUST-#### code comes from Codes::unique() (retry-safe
     * against concurrent registrations); the registering user is
     * snapshotted into created_by_id, exactly like the reference's
     * createdById. Audit CUSTOMER_CREATED {code, name}.
     */
    public function store(Request $request): RedirectResponse
    {
        $me = $request->user();

        $this->rejectBlank($request, [
            'name' => __('cust.namePhoneRequired'),
            'phone' => __('cust.namePhoneRequired'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'phone' => ['required', 'string', 'min:3', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer = Codes::unique('customer', function (string $code) use ($data, $me): Customer {
            return Customer::query()->create([
                'code' => $code,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by_id' => $me->id,
            ]);
        });

        Audit::log($me, 'CUSTOMER_CREATED', 'CUSTOMER', $customer->id, [
            'code' => $customer->code,
            'name' => $customer->name,
        ]);

        return redirect()
            ->back()
            ->with('success', __('cust.registeredToast'));
    }

    /**
     * POST /customers/{id}/update (route perm customers.manage) — edit a
     * customer, ported from the PATCH handler of customers/[id]/route.ts:
     * every field is optional, present-null clears the nullable columns
     * (email / address / notes — ConvertEmptyStringsToNull semantics) and
     * only columns whose submitted value differs are written. The audit
     * entry lists the changed fields, exactly like the reference
     * changedFields array.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $customer = Customer::query()->findOrFail($id);

        $this->rejectBlank($request, [
            'name' => __('cust.namePhoneRequired'),
            'phone' => __('cust.namePhoneRequired'),
        ]);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'min:1', 'max:120'],
            'phone' => ['nullable', 'string', 'min:3', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $update = [];

        if (array_key_exists('name', $data)) {
            $update['name'] = $data['name'];
        }

        if (array_key_exists('phone', $data)) {
            $update['phone'] = $data['phone'];
        }

        if (array_key_exists('email', $data)) {
            $update['email'] = $data['email'] ?? null;
        }

        if (array_key_exists('address', $data)) {
            $update['address'] = $data['address'] ?? null;
        }

        if (array_key_exists('notes', $data)) {
            $update['notes'] = $data['notes'] ?? null;
        }

        $customer->fill($update);
        $changedColumns = array_keys($customer->getDirty());

        if ($changedColumns !== []) {
            $customer->save();
        }

        Audit::log($me, 'CUSTOMER_UPDATED', 'CUSTOMER', $customer->id, [
            'code' => $customer->code,
            'changedFields' => $changedColumns,
        ]);

        return redirect()
            ->back()
            ->with('success', __('cust.updatedToast'));
    }

    /**
     * POST /customers/{id}/delete (route perm customers.manage) — remove an
     * order-less customer. Customers that already placed orders are kept
     * and the caller is flashed cust.hasOrdersError. There is no
     * CUSTOMER_DELETED audit code, so deletes are recorded as
     * CUSTOMER_UPDATED with {deleted: true, code: the CUST-XXXX code}.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $customer = Customer::query()->findOrFail($id);

        if ($customer->orders()->count() !== 0) {
            return redirect()
                ->back()
                ->with('error', __('cust.hasOrdersError'));
        }

        $code = $customer->code;

        $customer->delete();

        Audit::log($me, 'CUSTOMER_UPDATED', 'CUSTOMER', $customer->id, [
            'deleted' => true,
            'code' => $code,
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', __('cust.deletedToast'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Pre-validation guard for the NOT NULL name/phone columns: with
     * ConvertEmptyStringsToNull active, a submitted-but-blank input arrives
     * as null, which the nullable update rules would swallow before hitting
     * a NOT NULL database column. Blank submissions are rejected with the
     * cust.namePhoneRequired line; absent fields stay untouched and keep
     * their defaults.
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
