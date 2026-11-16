<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Production tasks (spec §3: GET /tasks, GET /tasks/create, POST /tasks,
 * GET /tasks/{id}).
 *
 * 1:1 port of the Next.js reference implementation:
 * - GET  src/app/api/atelier/tasks/route.ts — role-scoped task list with
 *   the q / staff / status / priority / overdue / deadline range / sort
 *   filters (staff accounts are force-scoped to their own tasks);
 * - POST src/app/api/atelier/tasks/route.ts — break an order into
 *   garment-piece tasks (duplicate detection, checklist initialization,
 *   initial assignment + notification, audit).
 * The detail screen eager-loads everything the tasks.show view contract
 * demands (order.customer, assignee, stage states, comments, assignment
 * history, status history, progress history, inspections).
 *
 * Route permissions (production.create.tasks) are enforced by the `perm`
 * middleware; STAFF own-task scoping happens here.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / TaskActionController.
 */
class TaskController
{
    /**
     * Sort keys of the task board (listQuerySchema in the reference
     * implementation).
     */
    private const SORTS = ['updated', 'deadline', 'progress', 'created'];

    /**
     * Tasks per page on the board.
     */
    private const PAGE_SIZE = 20;

    /**
     * GET /tasks — the production board (supervisors) / my tasks (staff).
     *
     * Filter values that are not part of the domain constant lists are
     * silently ignored, mirroring the reference implementation.
     */
    public function index(Request $request): View
    {
        $me = $request->user();
        $isStaff = $me->role === 'STAFF';

        $q = trim((string) $request->query('q', ''));
        $staff = trim((string) $request->query('staff', ''));
        $status = (string) $request->query('status', '');
        $status = in_array($status, Atelier::TASK_STATUSES, true) ? $status : null;
        $priority = (string) $request->query('priority', '');
        $priority = in_array($priority, Atelier::PRIORITIES, true) ? $priority : null;
        $overdue = $request->boolean('overdue');
        $sort = (string) $request->query('sort', 'updated');
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'updated';
        $deadlineFrom = trim((string) $request->query('deadlineFrom', ''));
        $deadlineTo = trim((string) $request->query('deadlineTo', ''));

        // Staff only ever see their own assigned tasks; the staff and
        // priority filters are supervisor tools and are never applied for
        // them (the view hides both controls).
        if ($isStaff) {
            $staff = '';
            $priority = null;
        }

        $from = $this->parseDeadlineBoundary($deadlineFrom, false);
        $to = $this->parseDeadlineBoundary($deadlineTo, true);

        $query = ProductionTask::query()
            ->with(['order.customer', 'assignedStaff', 'stageStates']);

        if ($isStaff) {
            $query->where('assigned_staff_id', $me->id);
        } elseif ($staff !== '') {
            if ($staff === 'unassigned') {
                $query->whereNull('assigned_staff_id');
            } else {
                $query->where('assigned_staff_id', $staff);
            }
        }

        if ($q !== '') {
            $like = '%'.$q.'%';

            $query->where(static function (Builder $where) use ($like): void {
                $where->where('code', 'like', $like)
                    ->orWhere('garment_piece', 'like', $like)
                    ->orWhereHas('order', static function (Builder $order) use ($like): void {
                        $order->where('order_number', 'like', $like);
                    })
                    ->orWhereHas('order.customer', static function (Builder $customer) use ($like): void {
                        $customer->where('name', 'like', $like);
                    });
            });
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($priority !== null) {
            $query->where('priority', $priority);
        }

        if ($from !== null) {
            $query->where('deadline', '>=', $from);
        }

        if ($to !== null) {
            $query->where('deadline', '<=', $to);
        }

        if ($overdue) {
            // Deadline passed and the task is not terminal
            // (= Atelier::taskIsOverdue).
            $query->whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->whereNotIn('status', Atelier::terminalStatuses());
        }

        if ($sort === 'deadline') {
            // MySQL/MariaDB sort NULLs first — "deadline IS NULL" pushes
            // them last; ties break by most recently updated.
            $query->orderByRaw('deadline IS NULL')
                ->orderBy('deadline')
                ->orderByDesc('updated_at');
        } elseif ($sort === 'progress') {
            $query->orderByDesc('progress');
        } elseif ($sort === 'created') {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('updated_at');
        }

        return view('tasks.index', [
            'tasks' => $query->paginate(self::PAGE_SIZE)->withQueryString(),
            'filters' => [
                'q' => $q,
                'staff' => $staff !== '' ? $staff : null,
                'status' => $status,
                'priority' => $priority,
                'overdue' => $overdue,
                'sort' => $sort,
                'deadlineFrom' => $deadlineFrom !== '' ? $deadlineFrom : null,
                'deadlineTo' => $deadlineTo !== '' ? $deadlineTo : null,
            ],
            'staffList' => $isStaff ? collect() : $this->assignableStaff(),
            'isStaff' => $isStaff,
        ]);
    }

    /**
     * GET /tasks/create — order picker + per-piece rows (route perm
     * production.create.tasks). Existing tasks are loaded with every order
     * for the piece-count hints and the duplicate-name warning.
     */
    public function create(Request $request): View
    {
        $orders = Order::query()
            ->with(['customer', 'tasks'])
            ->orderByDesc('created_at')
            ->get();

        $preselectOrderId = trim((string) $request->query('order', ''));

        return view('tasks.create', [
            'orders' => $orders,
            'staffList' => $this->assignableStaff(),
            'preselectOrderId' => $preselectOrderId !== '' ? $preselectOrderId : null,
        ]);
    }

    /**
     * POST /tasks — break an order into production tasks (garment pieces).
     *
     * Ported from the POST handler of tasks/route.ts: duplicate piece names
     * per order (among existing non-cancelled tasks or inside the request)
     * are rejected with a field-level validation error; each task receives
     * NOT_STARTED checklist states for every stage applicable to its
     * category; assigned pieces get an assignment history row, an
     * UNASSIGNED -> ASSIGNED status change and a TASK_ASSIGNED
     * notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $me = $request->user();

        $data = $request->validate([
            'orderId' => ['required', 'string', Rule::exists('orders', 'id')],
            'pieces' => ['required', 'array', 'min:1', 'max:50'],
            'pieces.*.garmentPiece' => ['required', 'string', 'min:1', 'max:120'],
            'pieces.*.category' => ['nullable', 'string', Rule::in(Atelier::TASK_CATEGORIES)],
            'pieces.*.staffId' => ['nullable', 'string', Rule::exists('users', 'id')->where('is_active', true)],
            'pieces.*.priority' => ['nullable', 'string', Rule::in(Atelier::PRIORITIES)],
            'pieces.*.deadline' => ['nullable', 'date'],
            'pieces.*.instructions' => ['nullable', 'string', 'max:4000'],
        ]);

        /** @var Order $order */
        $order = Order::query()->with('customer')->findOrFail($data['orderId']);

        // Normalize the pieces; duplicates inside one request are rejected.
        $pieces = [];
        $seen = [];

        foreach ($data['pieces'] as $index => $piece) {
            $name = trim((string) $piece['garmentPiece']);
            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'pieces.'.$index.'.garmentPiece' => Trans::fill('tc.duplicateDesc', ['name' => $name]),
                ]);
            }

            $seen[$key] = true;

            $pieces[$index] = [
                'name' => $name,
                'key' => $key,
                'category' => (string) ($piece['category'] ?? '') !== '' ? (string) $piece['category'] : 'FULL',
                'staffId' => trim((string) ($piece['staffId'] ?? '')) !== '' ? (string) $piece['staffId'] : null,
                'priority' => (string) ($piece['priority'] ?? '') !== '' ? (string) $piece['priority'] : null,
                'deadline' => trim((string) ($piece['deadline'] ?? '')) !== '' ? trim((string) $piece['deadline']) : null,
                'instructions' => trim((string) ($piece['instructions'] ?? '')) !== ''
                    ? trim((string) $piece['instructions'])
                    : null,
            ];
        }

        // Duplicate names against the order's existing (non-cancelled)
        // tasks — a cancelled piece may be recreated.
        $existing = ProductionTask::query()
            ->where('order_id', $order->id)
            ->where('status', '!=', 'CANCELLED')
            ->pluck('garment_piece');

        foreach ($pieces as $index => $piece) {
            foreach ($existing as $existingName) {
                if (mb_strtolower(trim((string) $existingName)) === $piece['key']) {
                    throw ValidationException::withMessages([
                        'pieces.'.$index.'.garmentPiece' => Trans::fill('tc.duplicateDesc', ['name' => $piece['name']]),
                    ]);
                }
            }
        }

        // Resolve the selected staff accounts (active + assignable role).
        $staffIds = array_values(array_unique(array_filter(
            array_column($pieces, 'staffId'),
            static fn (?string $staffId): bool => $staffId !== null && $staffId !== ''
        )));

        $staff = User::query()->whereIn('id', $staffIds)->get()->keyBy('id');

        foreach ($pieces as $index => $piece) {
            if ($piece['staffId'] === null) {
                continue;
            }

            $assignee = $staff->get($piece['staffId']);

            if ($assignee === null || ! in_array($assignee->role, Permissions::assignableRoles(), true)) {
                throw ValidationException::withMessages([
                    'pieces.'.$index.'.staffId' => __('td.toast.selectStaff'),
                ]);
            }
        }

        // Applicable checklist stages per category, resolved BEFORE the
        // transaction (reads outside, writes inside — like the reference
        // implementation).
        $allStages = ProductionStage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $stageCache = [];

        foreach (array_unique(array_column($pieces, 'category')) as $category) {
            $stageCache[(string) $category] = $allStages->filter(
                static fn (ProductionStage $stage): bool => Atelier::stageAppliesTo(
                    (string) $stage->applies_to,
                    (string) $category
                )
            )->values();
        }

        $tasks = DB::transaction(function () use ($pieces, $order, $me, $staff, $stageCache): array {
            $created = [];

            foreach ($pieces as $piece) {
                $assignee = $piece['staffId'] !== null ? $staff->get($piece['staffId']) : null;

                $task = ProductionTask::query()->create([
                    'code' => Codes::task(),
                    'order_id' => $order->id,
                    'garment_piece' => $piece['name'],
                    'category' => $piece['category'],
                    'instructions' => $piece['instructions'],
                    'deadline' => $piece['deadline'] !== null
                        ? Carbon::parse($piece['deadline'])
                        : $order->expected_delivery_date,
                    'priority' => $piece['priority'] ?? (string) $order->priority,
                    'status' => $assignee !== null ? 'ASSIGNED' : 'UNASSIGNED',
                    'progress' => 0,
                    'assigned_staff_id' => $assignee?->id,
                    'created_by_id' => $me->id,
                ]);

                // Checklist stage states (all NOT_STARTED) for every stage
                // applicable to this piece's category.
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
                        'note' => null,
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

                $created[] = $task;
            }

            return $created;
        });

        // Notifications after the transaction committed.
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

        Audit::log($me, 'TASKS_CREATED', 'ORDER', $order->id, [
            'count' => count($tasks),
            'codes' => array_map(
                static fn (ProductionTask $task): string => (string) $task->code,
                $tasks
            ),
        ]);

        $count = count($tasks);

        return redirect()
            ->route('tasks.index')
            ->with('success', Trans::fill(
                $count === 1 ? 'tc.createdOne' : 'tc.createdMany',
                ['count' => $count]
            ));
    }

    /**
     * GET /tasks/{id} — task detail: progress + checklist, workflow
     * actions, comments, details card and the history tabs. STAFF accounts
     * may only open their own tasks (403 otherwise).
     */
    public function show(Request $request, string $id): View
    {
        $me = $request->user();

        $task = ProductionTask::query()
            ->with([
                'order.customer',
                'assignedStaff',
                'stageStates.stage',
                'comments.user',
                'assignments',
                'statusChanges.user',
                'progressHistories',
                'inspections.inspector',
            ])
            ->findOrFail($id);

        if ($me->role === 'STAFF' && $task->assigned_staff_id !== $me->id) {
            abort(403);
        }

        // The snapshot child models (assignments / inspections) carry no
        // user relation in the 1:1 Prisma port and the task creator exists
        // only as a snapshot id. The view contract nevertheless expects
        // assignments.user / inspections.user / creator, so the users are
        // attached in memory (same pattern as the other controllers).
        $task->setRelation(
            'creator',
            $task->created_by_id !== null ? User::query()->find($task->created_by_id) : null
        );

        $assignmentStaff = User::query()
            ->whereIn('id', $task->assignments->pluck('staff_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        foreach ($task->assignments as $assignment) {
            $assignment->setRelation('user', $assignmentStaff->get($assignment->staff_id));
        }

        foreach ($task->inspections as $inspection) {
            $inspection->setRelation('user', $inspection->inspector);
        }

        return view('tasks.show', [
            'task' => $task,
            'stages' => ProductionStage::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'staffList' => $this->assignableStaff(),
            'isAssignee' => $me->id === $task->assigned_staff_id,
            'isSupervisor' => in_array($me->role, ['MANAGEMENT', 'ADMIN'], true),
        ]);
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Active accounts holding an assignable role (MANAGEMENT / ADMIN /
     * STAFF), name-ordered — used for the assign dialogs and the staff
     * filter.
     */
    private function assignableStaff(): EloquentCollection
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', Permissions::assignableRoles())
            ->orderBy('name')
            ->get();
    }

    /**
     * Parse a deadline filter boundary; unparseable values are silently
     * ignored (like every other invalid filter). Date-only "to" values
     * include the whole final day of the range.
     */
    private function parseDeadlineBoundary(string $value, bool $isEnd): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }

        if ($isEnd && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $date->endOfDay();
        }

        return $date;
    }
}
