<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductionStage;
use App\Models\ProductionTask;
use App\Models\TaskAssignment;
use App\Models\TaskStageState;
use App\Models\TaskStatusChange;
use App\Models\User;
use App\Services\Audit;
use App\Services\Codes;
use App\Services\Notifier;
use App\Services\Permissions;
use App\Support\Atelier;
use App\Support\Trans;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Manually registered garment orders (spec §3) — unified registration
 * workflow (enhancement): registering an order and breaking it into
 * garment-piece tasks with staff assignment happens in ONE transaction
 * (POST /orders), so an order never exists half-registered.
 *
 * - GET  /orders — filterable listing (search / priority / date range)
 *        with payment summaries.
 * - GET  /orders/create — the 4-step wizard (customer → order →
 *        pieces & assignment → review), spec §3.2 sections A–D.
 * - POST /orders — creates customer (optional) + order + per-piece
 *        production tasks + assignments + notifications atomically.
 *        "Save as draft" stores everything with status DRAFT and no
 *        staff notifications until finalized.
 * - GET  /orders/{id} — dedicated order details page (spec §3.3):
 *        order/customer info, pieces & tasks, financials (perm-gated),
 *        and the order's audit history.
 * - POST /orders/{id}/update — partial updates incl. total value.
 * - POST /orders/{id}/finalize — DRAFT → REGISTERED + notifications.
 * - POST /orders/{id}/delete — only task-less orders.
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
     * production + payment summaries.
     */
    public function index(Request $request): View
    {
        $q = trim((string) ($request->query('q') ?? $request->query('search') ?? ''));
        $priority = (string) ($request->query('priority') ?? '');
        $from = trim((string) ($request->query('from') ?? ''));
        $to = trim((string) ($request->query('to') ?? ''));

        $orders = Order::query()
            ->with(['customer', 'tasks'])
            ->withSum('payments as paid_total', 'amount');

        if ($q !== '') {
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
     * GET /orders/create (route perm orders.manage) — the unified
     * registration wizard (spec §3.2): customer selection / quick
     * registration, order information, garment pieces with per-staff
     * assignment, and the review step before saving.
     */
    public function create(Request $request): View
    {
        return view('orders.create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'code', 'name', 'phone']),
            'staffList' => User::query()
                ->where('is_active', true)
                ->whereIn('role', Permissions::assignableRoles())
                ->orderBy('name')
                ->get(['id', 'name', 'role', 'job_title']),
            'categories' => Atelier::TASK_CATEGORIES,
            'priorities' => Atelier::PRIORITIES,
            'preselectCustomer' => (string) ($request->query('customer') ?? ''),
        ]);
    }

    /**
     * POST /orders (route perm orders.manage) — the unified registration
     * (spec §3.2): one DB::transaction creates the customer (when new),
     * the order, one production task per piece × assignment with its
     * checklist states, the assignment history and status changes.
     * Drafts skip staff notifications until finalize().
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $me */
        $me = $request->user();

        $data = $request->validate([
            'customerMode' => ['required', 'string', Rule::in(['existing', 'new'])],
            'customerId' => ['required_if:customerMode,existing', 'nullable', 'string', Rule::exists('customers', 'id')],
            'newCustomerName' => ['required_if:customerMode,new', 'nullable', 'string', 'max:120'],
            'newCustomerPhone' => ['required_if:customerMode,new', 'nullable', 'string', 'max:40'],
            'newCustomerEmail' => ['nullable', 'string', 'email', 'max:120'],
            'newCustomerAddress' => ['nullable', 'string', 'max:300'],

            'orderNumber' => ['nullable', 'string', 'max:50', Rule::unique('orders', 'order_number')],
            'orderDate' => ['required', 'date'],
            'expectedDeliveryDate' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'garmentCategory' => ['nullable', 'string', 'max:80'],
            'priority' => ['nullable', 'string', Rule::in(Atelier::PRIORITIES)],
            'totalValue' => ['nullable', 'numeric', 'min:0'],
            'instructions' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],

            'pieces' => ['required', 'array', 'min:1', 'max:50'],
            'pieces.*.name' => ['required', 'string', 'max:120'],
            'pieces.*.description' => ['nullable', 'string', 'max:500'],
            'pieces.*.quantity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'pieces.*.category' => ['nullable', 'string', Rule::in(Atelier::TASK_CATEGORIES)],
            'pieces.*.priority' => ['nullable', 'string', Rule::in(Atelier::PRIORITIES)],
            'pieces.*.deadline' => ['nullable', 'date'],
            'pieces.*.instructions' => ['nullable', 'string', 'max:4000'],
            'pieces.*.assignments' => ['nullable', 'array', 'max:10'],
            'pieces.*.assignments.*.staffId' => ['required_with:pieces.*.assignments', 'nullable', 'string'],
            'pieces.*.assignments.*.note' => ['nullable', 'string', 'max:200'],

            'draft' => ['nullable', 'boolean'],
        ]);

        // ---- Normalize pieces + assignments (spec §3.2 section C) ----------
        $pieces = [];
        $seen = [];
        $allStaffIds = [];

        foreach ($data['pieces'] as $index => $piece) {
            $name = trim((string) ($piece['name'] ?? ''));
            $key = mb_strtolower($name);

            if ($name === '') {
                throw ValidationException::withMessages([
                    'pieces.'.$index.'.name' => __('orders.errPieceName'),
                ]);
            }

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'pieces.'.$index.'.name' => __('orders.errDuplicatePiece'),
                ]);
            }

            $seen[$key] = true;

            $assignments = [];

            foreach ((array) ($piece['assignments'] ?? []) as $a) {
                $staffId = trim((string) ($a['staffId'] ?? ''));

                if ($staffId === '') {
                    continue;
                }

                if (isset($assignments[$staffId])) {
                    continue; // same staff selected twice on one piece — dedupe
                }

                $assignments[$staffId] = trim((string) ($a['note'] ?? '')) !== ''
                    ? trim((string) $a['note'])
                    : null;
                $allStaffIds[] = $staffId;
            }

            $pieces[$index] = [
                'name' => $name,
                'key' => $key,
                'description' => trim((string) ($piece['description'] ?? '')) !== '' ? trim((string) $piece['description']) : null,
                'quantity' => max(1, (int) ($piece['quantity'] ?? 1)),
                'category' => (string) ($piece['category'] ?? '') !== '' ? (string) $piece['category'] : 'FULL',
                'priority' => (string) ($piece['priority'] ?? '') !== '' ? (string) $piece['priority'] : null,
                'deadline' => trim((string) ($piece['deadline'] ?? '')) !== '' ? trim((string) $piece['deadline']) : null,
                'instructions' => trim((string) ($piece['instructions'] ?? '')) !== '' ? trim((string) $piece['instructions']) : null,
                'assignments' => $assignments,
            ];
        }

        // ---- Resolve assignees (active + assignable role, spec §3.2) -------
        $staff = User::query()
            ->whereIn('id', array_values(array_unique($allStaffIds)))
            ->get()
            ->keyBy('id');

        foreach ($allStaffIds as $staffId) {
            $assignee = $staff->get($staffId);

            if ($assignee === null || ! $assignee->is_active || ! in_array($assignee->role, Permissions::assignableRoles(), true)) {
                throw ValidationException::withMessages([
                    'pieces' => __('orders.errAssignee'),
                ]);
            }
        }

        // ---- Applicable checklist stages per category (reads before txn) ---
        $allStages = ProductionStage::query()->where('is_active', true)->orderBy('sort_order')->get();
        $stageCache = [];

        foreach (array_unique(array_column($pieces, 'category')) as $category) {
            $stageCache[(string) $category] = $allStages->filter(
                static fn (ProductionStage $stage): bool => Atelier::stageAppliesTo(
                    (string) $stage->applies_to,
                    (string) $category
                )
            )->values();
        }

        $isDraft = (bool) ($data['draft'] ?? false);
        $orderNumber = trim((string) ($data['orderNumber'] ?? '')) !== '' ? trim((string) $data['orderNumber']) : null;
        $priority = (string) ($data['priority'] ?? 'NORMAL');
        $totalPieces = array_sum(array_column($pieces, 'quantity'));

        // ---- ONE transaction: customer + order + tasks + assignments -------
        [$order, $tasks, $newCustomer] = DB::transaction(function () use ($data, $pieces, $stageCache, $staff, $me, $isDraft, $orderNumber, $priority, $totalPieces): array {
            // Section A — customer (existing or quick registration).
            if (($data['customerMode'] ?? '') === 'new') {
                $newCustomer = Codes::unique('customer', fn (string $code) => Customer::query()->create([
                    'code' => $code,
                    'name' => trim((string) $data['newCustomerName']),
                    'phone' => trim((string) $data['newCustomerPhone']),
                    'email' => trim((string) ($data['newCustomerEmail'] ?? '')) !== '' ? trim((string) $data['newCustomerEmail']) : null,
                    'address' => trim((string) ($data['newCustomerAddress'] ?? '')) !== '' ? trim((string) $data['newCustomerAddress']) : null,
                    'created_by_id' => $me->id,
                ]));
            } else {
                $newCustomer = Customer::query()->findOrFail((string) $data['customerId']);
            }

            // Section B — the order itself.
            $createOrder = fn (string $number) => Order::query()->create([
                'order_number' => $number,
                'customer_id' => $newCustomer->id,
                'order_date' => Carbon::parse((string) $data['orderDate']),
                'expected_delivery_date' => isset($data['expectedDeliveryDate']) && $data['expectedDeliveryDate'] !== null
                    ? Carbon::parse((string) $data['expectedDeliveryDate'])
                    : null,
                'description' => trim((string) $data['description']),
                'garment_category' => trim((string) ($data['garmentCategory'] ?? '')) !== ''
                    ? trim((string) $data['garmentCategory'])
                    : 'GENERAL',
                'number_of_pieces' => $totalPieces,
                'instructions' => $data['instructions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'priority' => $priority,
                'total_value' => isset($data['totalValue']) && $data['totalValue'] !== null && (float) $data['totalValue'] > 0
                    ? round((float) $data['totalValue'], 2)
                    : null,
                'currency' => 'XAF',
                'status' => $isDraft ? 'DRAFT' : 'REGISTERED',
                'created_by_id' => $me->id,
            ]);

            try {
                $order = $orderNumber === null
                    ? Codes::unique('order', $createOrder)
                    : $createOrder($orderNumber);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'orderNumber' => __('validation.unique', ['attribute' => __('orders.fieldOrderNumber')]),
                ]);
            }

            // Section C — one task per piece × assignment (spec §3.2; multiple
            // staff on one piece each get their own task with their own
            // responsibility note, preserving individual progress).
            $tasks = [];

            foreach ($pieces as $piece) {
                $pieceAssignments = $piece['assignments'];

                $taskRows = $pieceAssignments === []
                    // Explicitly unassigned piece → one UNASSIGNED task.
                    ? [[null, null]]
                    : array_map(
                        static fn (string $staffId, ?string $note): array => [$staffId, $note],
                        array_keys($pieceAssignments),
                        $pieceAssignments,
                    );

                foreach ($taskRows as [$staffId, $note]) {
                    $assignee = $staffId !== null ? $staff->get($staffId) : null;

                    $instructions = $piece['instructions'];

                    if ($note !== null && $note !== '') {
                        $prefix = 'Responsibility: '.$note;
                        $instructions = $instructions === null
                            ? $prefix
                            : $prefix."\n".$instructions;
                    }

                    if ($piece['description'] !== null && $piece['description'] !== '') {
                        $prefix = $piece['description'];
                        $instructions = $instructions === null
                            ? $prefix
                            : $prefix."\n".$instructions;
                    }

                    $task = ProductionTask::query()->create([
                        'code' => Codes::task(),
                        'order_id' => $order->id,
                        'garment_piece' => $piece['name'],
                        'category' => $piece['category'],
                        'quantity' => $piece['quantity'],
                        'instructions' => $instructions,
                        'deadline' => $piece['deadline'] !== null
                            ? Carbon::parse($piece['deadline'])
                            : $order->expected_delivery_date,
                        'priority' => $piece['priority'] ?? (string) $order->priority,
                        'status' => $assignee !== null ? 'ASSIGNED' : 'UNASSIGNED',
                        'progress' => 0,
                        'assigned_staff_id' => $assignee?->id,
                        'created_by_id' => $me->id,
                    ]);

                    foreach ($stageCache[$piece['category']] as $stage) {
                        TaskStageState::query()->create([
                            'task_id' => $task->id,
                            'stage_id' => $stage->id,
                            'state' => 'NOT_STARTED',
                        ]);
                    }

                    if ($assignee !== null) {
                        TaskAssignment::query()->create([
                            'task_id' => $task->id,
                            'staff_id' => $assignee->id,
                            'staff_name' => $assignee->name,
                            'assigned_by_id' => $me->id,
                            'assigned_by_name' => $me->name,
                            'type' => 'ASSIGN',
                            'note' => $note,
                        ]);

                        TaskStatusChange::query()->create([
                            'task_id' => $task->id,
                            'user_id' => $me->id,
                            'user_name' => $me->name,
                            'from_status' => 'UNASSIGNED',
                            'to_status' => 'ASSIGNED',
                            'reason' => null,
                        ]);
                    }

                    $tasks[] = $task;
                }
            }

            return [$order, $tasks, $newCustomer];
        });

        // ---- Audits (after commit, best-effort like the rest of the app) ----
        if (($data['customerMode'] ?? '') === 'new') {
            Audit::log($me, 'CUSTOMER_CREATED', 'CUSTOMER', $newCustomer->id, [
                'name' => $newCustomer->name,
                'phone' => $newCustomer->phone,
            ]);
        }

        Audit::log($me, 'ORDER_CREATED', 'ORDER', $order->id, [
            'orderNumber' => $order->order_number,
            'customerName' => $newCustomer->name,
            'priority' => $order->priority,
            'pieceGroups' => count($pieces),
            'tasks' => count($tasks),
            'draft' => $isDraft,
            'totalValue' => $order->total_value,
        ]);

        if ($tasks !== []) {
            Audit::log($me, 'TASKS_CREATED', 'ORDER', $order->id, [
                'count' => count($tasks),
                'codes' => array_map(static fn (ProductionTask $t): string => (string) $t->code, $tasks),
            ]);
        }

        // ---- Section D outcome: notifications for assignees (never drafts) -
        if (! $isDraft) {
            foreach ($tasks as $task) {
                if ($task->assigned_staff_id === null) {
                    continue;
                }

                Notifier::users(
                    [$task->assigned_staff_id],
                    'TASK_ASSIGNED',
                    'You were assigned '.$task->code.' — '.$task->garment_piece
                        .' ('.$order->order_number.').',
                    $task->id,
                    $order->id
                );
            }
        }

        $toast = $isDraft
            ? __('orders.draftSavedToast')
            : Trans::fill('orders.savedWithTasksToast', ['count' => count($tasks)]);

        return redirect()
            ->route('orders.show', ['id' => $order->id])
            ->with('success', $toast);
    }

    /**
     * GET /orders/{id} (route perm orders.view) — dedicated order details
     * page (spec §3.3): order + customer information, garment pieces and
     * tasks with individual progress, inspection status, payments and
     * order-level cost breakdown (perm-gated for authorized Management
     * only) and the order's complete activity history.
     */
    public function show(string $id): View
    {
        $order = Order::query()
            ->with([
                'customer',
                'tasks' => function (HasMany $query): void {
                    $query->orderBy('created_at')->with(['assignedStaff', 'stageStates']);
                },
                'payments' => function (HasMany $query): void {
                    $query->orderByDesc('payment_date');
                },
                'expenses' => function (HasMany $query): void {
                    $query->with('category')->orderByDesc('date_incurred');
                },
                'labourAllocations' => function (HasMany $query): void {
                    $query->orderByDesc('created_at');
                },
            ])
            ->withSum('payments as paid_total', 'amount')
            ->findOrFail($id);

        $activity = AuditLog::query()
            ->where('entity_type', 'ORDER')
            ->where('entity_id', $order->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        /** @var \App\Models\User $me */
        $me = auth()->user();

        return view('orders.show', [
            'order' => $order,
            'activity' => $activity,
            'costs' => $order->costBreakdown(),
            'canSeeFinancials' => Permissions::allows($me, 'accounting.payments'),
            'canManage' => Permissions::allows($me, 'orders.manage'),
            'priorities' => Atelier::PRIORITIES,
        ]);
    }

    /**
     * POST /orders/{id}/update (route perm orders.manage) — partial edit;
     * the customer cannot be changed, present-null clears nullable columns,
     * and only dirty columns are written + audited.
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
            'totalValue' => ['nullable', 'numeric', 'min:0'],
        ]);

        $update = [];

        foreach ([
            'orderNumber' => 'order_number',
            'orderDate' => 'order_date',
            'expectedDeliveryDate' => 'expected_delivery_date',
            'priority' => 'priority',
            'pieceCount' => 'number_of_pieces',
            'garmentCategory' => 'garment_category',
            'description' => 'description',
            'instructions' => 'instructions',
            'notes' => 'notes',
        ] as $field => $column) {
            if (array_key_exists($field, $data)) {
                $update[$column] = $field === 'pieceCount'
                    ? (int) $data[$field]
                    : $data[$field];
            }
        }

        if (array_key_exists('totalValue', $data)) {
            $value = $data['totalValue'] !== null && (float) $data['totalValue'] > 0
                ? round((float) $data['totalValue'], 2)
                : null;
            $update['total_value'] = $value;
        }

        $order->fill($update);
        $changedColumns = array_keys($order->getDirty());

        if ($changedColumns !== []) {
            try {
                $order->save();
            } catch (UniqueConstraintViolationException) {
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
     * POST /orders/{id}/finalize (route perm orders.manage) — promote a
     * DRAFT order to REGISTERED and notify every assigned staff member
     * (the notifications a draft deliberately skipped, spec §3.2 D).
     */
    public function finalize(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $order = Order::query()->with('tasks')->findOrFail($id);

        if ($order->status !== 'DRAFT') {
            return redirect()
                ->back()
                ->with('error', __('orders.updateFailed'));
        }

        $order->forceFill(['status' => 'REGISTERED'])->save();

        Audit::log($me, 'ORDER_FINALIZED', 'ORDER', $order->id, [
            'orderNumber' => $order->order_number,
            'tasks' => $order->tasks->count(),
        ]);

        foreach ($order->tasks as $task) {
            if ($task->assigned_staff_id !== null) {
                Notifier::users(
                    [$task->assigned_staff_id],
                    'TASK_ASSIGNED',
                    'You were assigned '.$task->code.' — '.$task->garment_piece
                        .' ('.$order->order_number.').',
                    $task->id,
                    $order->id
                );
            }
        }

        return redirect()
            ->back()
            ->with('success', __('orders.finalizedToast'));
    }

    /**
     * POST /orders/{id}/delete (route perm orders.manage) — remove a
     * task-less order (registered or draft).
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
     * API's "numberOfPieces" spelling is merged in as an alias.
     */
    private function mergePieceCountAlias(Request $request): void
    {
        if ($request->missing('pieceCount') && $request->has('numberOfPieces')) {
            $request->merge(['pieceCount' => $request->input('numberOfPieces')]);
        }
    }

    /**
     * Pre-validation guard for NOT NULL columns (see the store() contract).
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
     * Audit field names use the Prisma camelCase spellings.
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
            'total_value' => 'totalValue',
            'status' => 'status',
        ];

        return array_values(array_map(
            static fn (string $column): string => $names[$column] ?? $column,
            $columns
        ));
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
