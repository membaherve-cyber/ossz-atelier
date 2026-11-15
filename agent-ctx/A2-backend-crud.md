# Task A2 — Backend CRUD controllers (Orders / Customers / Users / Stages)

Agent: A2 (backend-crud) · Status: COMPLETE · all 4 files pass `/home/z/php-toolchain/php -l`.

## Files written (owned by A2)

All in `/home/z/my-project/laravel-atelier/app/Http/Controllers/` — each has
`declare(strict_types=1)`, namespace `App\Http\Controllers`, NO base class (mirrors LoginController):

1. **OrderController.php** — 344 lines
   - `index`: eager `customer` + `tasks`, `created_at desc` (then order_number), `paginate(20)->withQueryString()`;
     GET filters ported from the reference: `q`/`search` (order_number OR description OR customer name),
     `priority` (validated against Atelier::PRIORITIES, invalid silently ignored), `from`/`to` inclusive
     order-date range (date-only `to` covers the whole day via `endOfDay()`). View data: `$orders`,
     `$customers` (name asc, for the register modal), plus extra `$filters` (q/priority/from/to) for
     form repopulation.
   - `store`: customerId (required, exists customers.id — NO inline new-customer flow per contract),
     orderNumber (nullable + `Rule::unique('orders','order_number')` — blank auto-generates the next
     free ORD-#### via `Codes::unique('order', …)`), orderDate (required date), expectedDeliveryDate
     (nullable date), priority (in PRIORITIES, default NORMAL), pieceCount (required int 1–200;
     accepts `numberOfPieces` alias merged pre-validation), garmentCategory (required max 80),
     description (required max 500), instructions/notes (nullable max 4000). Audit ORDER_CREATED
     details {orderNumber, customerName, priority}. Race-safe: UniqueConstraintViolationException on
     order_number → `ValidationException::withMessages(['orderNumber' => __('validation.unique', …)])`
     (web equivalent of the reference 409). Toast `__('orders.registeredToast')`.
   - `show`: `return view('orders.show', ['order' => $order])` — standalone fragment, NO layout, for the
     orders/index Alpine modal fetch. Eager: customer, tasks (created_at asc) with assignedStaff + stageStates.
   - `update`: all fields optional, `Rule::unique(...)->ignore($order->id)` on orderNumber; changed-column
     detection via fill + getDirty; audit ORDER_UPDATED details {orderNumber, changedFields} using Prisma
     field names (numberOfPieces etc.). Toast `__('orders.updatedToast')`.
   - `destroy`: only when `$order->tasks()->count() === 0` else error flash `__('orders.hasTasksError')`;
     audit ORDER_UPDATED details {deleted: true, code: order_number}; redirect orders.index with
     `__('orders.deletedToast')`.

2. **CustomerController.php** — 179 lines
   - `index`: `withCount('orders as ordersCount')` (attribute `ordersCount` per view contract) + minimal
     eager `orders(id,customer_id)->tasks(order_id,status,progress)` for view-side aggregation; search
     `q`/`search` (name/phone/email/code contains), name asc. View data: `$customers` + `$filters`.
   - `store`: name (required min 1 max 120), phone (required min 3 max 40), email (nullable email),
     address/notes (nullable). Code via `Codes::unique('customer', …)`, created_by_id snapshot, audit
     CUSTOMER_CREATED {code, name}. Toast `__('cust.registeredToast')` (actual key — NOT cust.createdToast).
   - `update`: all fields optional, fill + getDirty, audit CUSTOMER_UPDATED {code, changedFields}.
     Toast `__('cust.updatedToast')`.
   - `destroy`: only when no orders else error `__('cust.hasOrdersError')`; audit CUSTOMER_UPDATED
     {deleted: true, code}; redirect customers.index with `__('cust.deletedToast')`.

3. **UserController.php** — 290 lines
   - `index`: ALL users (active + inactive), `orderByRaw("CASE role WHEN 'MANAGEMENT' THEN 1 WHEN
     'ADMIN' THEN 2 WHEN 'STAFF' THEN 3 ELSE 4 END")` (portable CASE, not MySQL-only FIELD) then name;
     `withCount(['assignedTasks as activeTaskCount' => closure whereNotIn status, ProductionTask::
     TERMINAL_STATUSES])`. View data: `$users`, `$me`.
   - `store`: email lowercased+trimmed BEFORE validation (unique rule compares canonical form), name
     (min 2 max 120), password (min 8, `confirmed` via password_confirmation), role (in ROLES; ADMIN
     actor + non-STAFF role → abort(403)), phone/jobTitle nullable, isActive optional default true.
     Code via `Codes::user()` (task mandate). Audit USER_CREATED {code, name, email, role}. Email race →
     inline validation error. Toast `__('users.createdToastTitle')`.
   - `update`: ported safeguards EXACTLY from users/[id]/route.ts: ADMIN actor + non-STAFF target OR
     non-STAFF resulting role → abort(403); self-deactivate → error flash `__('users.selfDeactivateError')`;
     last-active-MANAGEMENT demote/deactivate (only triggers when count of active managers <= 1) → error
     flash `__('users.lastManagementError')`; optional password (min 8 confirmed) hashed + separate
     USER_PASSWORD_RESET audit with NO details; other changes → USER_UPDATED {email, changedFields}
     (password_hash filtered out). Toast picked by what changed: resetToastTitle (password only) /
     reactivated|deactivatedToastTitle (isActive) / updatedToastTitle (otherwise).

4. **StageController.php** — 286 lines
   - `index`: ordered by sort_order, then name (exact port).
   - `store`: name required min 2 max 80 + `Rule::unique('production_stages','name')` (view contract
     "name required unique"); slugify port (non-alphanumerics → dashes) with empty-slug rejection
     (`stages.invalidNameTitle`); unique code generation with -2/-3 suffix loop (exact port);
     sortOrder default `max(sort_order)+1` when absent/blank (view contract; reference default was 0);
     weight nullable numeric 0.1–10 default 1; appliesTo normalized by `normalizeAppliesTo()` port
     (accepts null/''/"ALL"/comma string/checkbox array → "ALL" or valid unique uppercase categories;
     empty or all-6 → ALL); isActive default true. Audit STAGE_CREATED {name, appliesTo}.
     Toast `__('stages.createdToastTitle')`.
   - `update`: same fields optional (unique name ignoring self, code stays unchanged — "The stage code
     stays unchanged" per stages.editDescription); fill + getDirty; audit STAGE_UPDATED
     {name, changed: {field: value}} with camelCase keys (name/weight/sortOrder/isActive/appliesTo).
     Toast `__('stages.updatedToastTitle')`.

## Shared implementation notes

- Route params are `{id}` (no implicit binding): every action takes `string $id` + `Model::findOrFail`
  → 404 for unknown ids.
- `redirect()->back()->with('success'|'error', __(…))` flash pattern per spec §6.
- Laravel 11 default `ConvertEmptyStringsToNull`/`TrimStrings` middleware is active (bootstrap/app.php
  uses the default global stack), so empty text inputs arrive as null: updates treat present-null as an
  explicit clear for nullable columns, and a pre-validation guard rejects present-but-empty values for
  NOT NULL columns (orderNumber/orderDate/priority/pieceCount/garmentCategory/description on orders;
  name/phone on customers; name/role on users; name on stages) using existing err* keys where possible
  (orders.errOrderNumber/errOrderDate/errCategory/errPieces, cust.namePhoneRequired) and the framework
  `validation.required` line otherwise.
- Bare HTML checkboxes post `"on"` — normalized to `'1'` before validation for every isActive input.
- `isActive` on update uses `$request->has('isActive')` + `$request->boolean()` so an edit form that
  omits the field leaves it untouched while a toggle form (hidden 0 / checkbox 1) flips it.
- Unique race guards: `UniqueConstraintViolationException` on order_number/email → inline
  ValidationException on the same field (mirrors the reference 409 responses).
- Column/relation names verified against the migrations and models (orders/customers/users/
  production_stages/production_tasks); eager `tasks:order_id,status,progress` includes the FK column.

## Ambiguities resolved (deviations & rationale)

1. **expectedDeliveryDate**: view contract said "required date" but the reference API, the nullable
   migration column and the lang label (no `*`) all say optional → implemented as **nullable**.
2. **description/garmentCategory**: view contract omitted them from the store list ("description
   optional") but the reference API + lang labels (`Description *`, `Garment category *`) + NOT NULL
   columns require them → implemented as **required** (min 1 / max 500 / max 80).
3. **Toast key names**: task brief cited `cust.createdToast`/`users.createdToast`; actual lang keys are
   `cust.registeredToast`, `users.createdToastTitle`, `stages.createdToastTitle` → used the EXISTING keys.
4. **Stage weight**: view contract said "int 1-10" but the reference validator AND lang/stages.php
   (`invalidWeightDesc` "between 0.1 and 10") AND the `double` migration column say decimal 0.1–10 →
   implemented **numeric 0.1–10**.
5. **Stage sortOrder default**: reference API defaults 0; view contract explicitly says default = max+1
   → implemented **max(sort_order)+1** on store when absent/blank (Laravel-contract wins; update keeps
   posted values, null treated as absent to avoid writing weight 0 / sort 0 silently).
6. **Order destroy audit details**: contract wording was `['deleted' => true, 'code' => …]` — used
   `'code' => $order->order_number` (orders have no `code` column; the order number is its business
   code, uniform with customers' CUST-XXXX).
7. **User email on update**: reference update schema has no email field → email is NOT editable on
   update (only on create), matching users/[id]/route.ts.
8. **ORDER_CREATED audit details**: omitted `newCustomer` flag (always false in the Laravel flow —
   inline customer creation intentionally not ported per view contract).
9. **User store isActive**: default true (hard-coded true in reference); accepts explicit override.
10. **hasTasksError etc.**: used the binding contract's exact key names even though they are missing
    from the lang files today (see below) — they are B3-owned files.

## MISSING LANG KEYS (need B3 / coordinator — lang files are NOT edited by A2)

Referenced by these controllers but absent from BOTH lang/en and lang/fr today:

| Key | Where used | Why |
|---|---|---|
| `orders.hasTasksError` | OrderController::destroy | contract-mandated error flash (delete blocked by tasks) |
| `orders.deletedToast` | OrderController::destroy | delete success toast (new, follows registeredToast/updatedToast pattern) |
| `cust.hasOrdersError` | CustomerController::destroy | contract-mandated error flash (delete blocked by orders) |
| `cust.deletedToast` | CustomerController::destroy | delete success toast |
| `users.lastManagementError` | UserController::update | contract-mandated error flash (last active MANAGEMENT safeguard) |
| `users.selfDeactivateError` | UserController::update | self-deactivate guard (ported 400 from reference; reference UI hides the button, server enforces) |

All other keys used exist in BOTH locales (verified by grep): orders.registeredToast/updatedToast/
errOrderNumber/errOrderDate/errCategory/errPieces, cust.registeredToast/updatedToast/namePhoneRequired,
users.createdToastTitle/updatedToastTitle/resetToastTitle/reactivatedToastTitle/deactivatedToastTitle,
stages.createdToastTitle/updatedToastTitle/invalidNameTitle, framework `validation.unique`/
`validation.required` (Laravel 11 built-in fallback lines).

## View-agent (B3) interface summary

- `orders.index`: `$orders` (paginator, iterable), `$customers`, `$filters` {q, priority, from, to}.
- `orders.show`: `$order` (+ customer, tasks w/ assignedStaff + stageStates, tasks ordered created_at asc) — fragment, no layout.
- `customers.index`: `$customers` (each with `ordersCount` int + `orders` (id, customer_id) eager with
  `tasks` (order_id, status, progress)), `$filters` {q}.
- `users.index`: `$users` (each with `activeTaskCount` int), `$me`.
- `stages.index`: `$stages` (sort_order asc, name asc).
- POST field names: orders → customerId, orderNumber, orderDate, expectedDeliveryDate, priority,
  pieceCount (numberOfPieces accepted), garmentCategory, description, instructions, notes;
  customers → name, phone, email, address, notes; users → name, email, password + password_confirmation,
  role, phone, jobTitle, isActive; stages → name, weight, sortOrder, isActive, appliesTo (string
  "ALL"/"FULL,CUTTING" or appliesTo[] array both accepted).
- Audit actions emitted: ORDER_CREATED, ORDER_UPDATED (incl. deletes), CUSTOMER_CREATED,
  CUSTOMER_UPDATED (incl. deletes), USER_CREATED, USER_UPDATED, USER_PASSWORD_RESET, STAGE_CREATED,
  STAGE_UPDATED — all members of Atelier::AUDIT_ACTIONS.

## Verification performed

- `/home/z/php-toolchain/php -l` on all four files: **no syntax errors** (re-run after every edit).
- Grepped migrations + models: every column (snake_case) and relation (customer, tasks, orders,
  assignedTasks, assignedStaff, stageStates) confirmed; eager-select FK columns included.
- Lang key existence greps for EN and FR (parity counts match).
- No lang files, models, services, middleware, routes, config or AppServiceProvider touched.
