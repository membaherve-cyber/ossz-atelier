<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\User;
use App\Services\Notifier;
use App\Services\Permissions;
use App\Support\Atelier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Central dashboard (spec §3: GET / — `dashboard` view, auth only).
 *
 * Role-aware 1:1 port of src/app/api/atelier/dashboard/route.ts:
 * - MANAGEMENT / ADMIN: production statistics, due-soon + overdue cards,
 *   staff workload and recent audit activity;
 * - STAFF: personal statistics and the latest assigned tasks.
 *
 * Manager visits additionally carry the TASK_OVERDUE side effect of the
 * reference route: assigned tasks that just went overdue and have no
 * TASK_OVERDUE notification yet generate one notification to every active
 * MANAGEMENT / ADMIN account ("CODE — piece (orderNumber) has passed its
 * deadline.") — a cron-free way to alert supervisors once per task.
 *
 * A single `dashboard` view is returned for both roles; the Blade template
 * branches on `$me->role === 'STAFF'` (view contract #2).
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class DashboardController
{
    /** Due-soon horizon in days (dashboard route: soon = now + 7 days). */
    private const DUE_SOON_DAYS = 7;

    /** Maximum rows in the due-soon / overdue / recent-activity cards. */
    private const CARD_LIMIT = 8;

    /** Maximum rows in the staff "My Production Tasks" list. */
    private const MY_TASKS_LIMIT = 6;

    /** Statuses counting as "my active" on the staff dashboard. */
    private const STAFF_ACTIVE_STATUSES = ['ASSIGNED', 'ONGOING', 'AWAITING_INSPECTION', 'CHANGES_REQUESTED'];

    /** Statuses counting as "awaiting inspection" on the staff dashboard. */
    private const STAFF_AWAITING_STATUSES = ['AWAITING_INSPECTION', 'CHANGES_REQUESTED'];

    /**
     * GET / — manager or staff dashboard, branched on the signed-in role.
     */
    public function index(Request $request): View
    {
        /** @var User $me */
        $me = $request->user();

        if ($me->role === 'STAFF') {
            return $this->staffDashboard($me);
        }

        return $this->managerDashboard();
    }

    // ------------------------------------------------------------------
    // Manager / Admin dashboard
    // ------------------------------------------------------------------

    /**
     * Full production overview: stats grid, due-soon + overdue cards,
     * active-task workload per assignable account and the latest audit
     * activity (view contract #2, semantics per the dashboard route).
     */
    private function managerDashboard(): View
    {
        // One-time overdue notifications for newly overdue tasks (no cron
        // needed) — ported verbatim from the reference dashboard route.
        $this->notifyNewlyOverdueTasks();

        $statusCounts = ProductionTask::query()
            ->groupBy('status')
            ->selectRaw('status, count(*) as aggregate')
            ->pluck('aggregate', 'status');

        $count = static fn (string $status): int => (int) ($statusCounts[$status] ?? 0);

        $stats = [
            'registeredOrders' => (int) Order::query()->count(),
            'activeTasks' => (int) ProductionTask::query()
                ->whereNotIn('status', Atelier::terminalStatuses())
                ->count(),
            'unassigned' => $count('UNASSIGNED'),
            'ongoing' => $count('ONGOING'),
            'awaitingInspection' => $count('AWAITING_INSPECTION'),
            'readyForPickup' => $count('READY_FOR_PICKUP'),
            'delivered' => $count('DELIVERED'),
            'blocked' => $count('BLOCKED'),
            'cancelled' => $count('CANCELLED'),
            // Project vocabulary: "active STAFF" = role STAFF + is_active.
            'activeStaff' => (int) User::query()
                ->where('is_active', true)
                ->where('role', 'STAFF')
                ->count(),
            'customers' => (int) Customer::query()->count(),
        ];

        $now = now();

        // Deadlines within the next 7 days — already-overdue work renders
        // in the separate overdue card instead (dashboard route filter).
        $dueSoon = ProductionTask::query()
            ->with(['order.customer', 'assignedStaff'])
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$now, $now->copy()->addDays(self::DUE_SOON_DAYS)])
            ->whereNotIn('status', Atelier::terminalStatuses())
            ->orderBy('deadline')
            ->limit(self::CARD_LIMIT)
            ->get();

        $overdue = ProductionTask::query()
            ->with(['order.customer', 'assignedStaff'])
            ->whereNotNull('deadline')
            ->where('deadline', '<', $now)
            ->whereNotIn('status', Atelier::terminalStatuses())
            ->orderByDesc('updated_at')
            ->limit(self::CARD_LIMIT)
            ->get();

        return view('dashboard', [
            'stats' => $stats,
            'dueSoon' => $dueSoon,
            'overdue' => $overdue,
            'workload' => $this->workload(),
            'recent' => $this->recentActivity(),
        ]);
    }

    /**
     * Active-task count per ACTIVE user in an assignable role
     * (Permissions::assignableRoles), name-ordered — the "Staff Workload
     * (Active Tasks)" card.
     *
     * @return list<array{user: User, activeCount: int}>
     */
    private function workload(): array
    {
        $users = User::query()
            ->where('is_active', true)
            ->whereIn('role', Permissions::assignableRoles())
            ->orderBy('name')
            ->get();

        $activeCounts = ProductionTask::query()
            ->whereNotIn('status', Atelier::terminalStatuses())
            ->whereNotNull('assigned_staff_id')
            ->groupBy('assigned_staff_id')
            ->selectRaw('assigned_staff_id, count(*) as aggregate')
            ->pluck('aggregate', 'assigned_staff_id');

        return $users
            ->map(static fn (User $user): array => [
                'user' => $user,
                'activeCount' => (int) ($activeCounts[$user->id] ?? 0),
            ])
            ->all();
    }

    /**
     * Latest audit entries for the "Recent Activity" card (8 rows — view
     * contract; the reference route keeps 12). AuditLog has no Eloquent
     * relations, so the acting User models are attached in memory; views
     * may also read the never-null user_name snapshot.
     *
     * @return Collection<int, AuditLog>
     */
    private function recentActivity(): Collection
    {
        $logs = AuditLog::query()
            ->orderByDesc('created_at')
            ->limit(self::CARD_LIMIT)
            ->get();

        $userIds = $logs
            ->pluck('user_id')
            ->filter(static fn ($id) => (string) $id !== '')
            ->unique()
            ->values();

        $users = $userIds->isEmpty()
            ? Collection::make()
            : User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        return $logs->each(static function (AuditLog $log) use ($users): void {
            $log->setAttribute('user', $users->get($log->user_id));
        });
    }

    /**
     * TASK_OVERDUE side effect (dashboard route port): every assigned task
     * that is currently overdue and has no TASK_OVERDUE notification yet
     * triggers one notification to all active MANAGEMENT / ADMIN users.
     * Runs on every manager dashboard hit.
     */
    private function notifyNewlyOverdueTasks(): void
    {
        $overdue = ProductionTask::query()
            ->with('order')
            ->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->whereNotIn('status', Atelier::terminalStatuses())
            ->whereNotNull('assigned_staff_id')
            ->get();

        if ($overdue->isEmpty()) {
            return;
        }

        $alreadyNotified = Notification::query()
            ->where('type', 'TASK_OVERDUE')
            ->whereIn('task_id', $overdue->pluck('id'))
            ->pluck('task_id')
            ->all();

        $fresh = $overdue->filter(
            static fn (ProductionTask $task): bool => ! in_array($task->id, $alreadyNotified, true)
        );

        if ($fresh->isEmpty()) {
            return;
        }

        $managerIds = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['MANAGEMENT', 'ADMIN'])
            ->pluck('id')
            ->all();

        foreach ($fresh as $task) {
            Notifier::users(
                $managerIds,
                'TASK_OVERDUE',
                $task->code.' — '.$task->garment_piece.' ('.$task->order->order_number.') has passed its deadline.',
                $task->id,
                $task->order_id
            );
        }
    }

    // ------------------------------------------------------------------
    // Staff dashboard
    // ------------------------------------------------------------------

    /**
     * Personal overview for STAFF accounts: my stats and the latest six
     * assigned tasks (view contract #2 — statuses per the controller
     * contract, overdue via Atelier::taskIsOverdue).
     */
    private function staffDashboard(User $me): View
    {
        $myTasks = ProductionTask::query()
            ->where('assigned_staff_id', $me->id)
            ->orderByDesc('updated_at')
            ->limit(self::MY_TASKS_LIMIT)
            ->with('order.customer')
            ->get();

        // One query feeds every personal stat; overdue uses the shared
        // Atelier::taskIsOverdue() predicate.
        $allMine = ProductionTask::query()
            ->where('assigned_staff_id', $me->id)
            ->get();

        $monthStart = now()->startOfMonth();

        $staffStats = [
            'myActive' => $allMine
                ->whereIn('status', self::STAFF_ACTIVE_STATUSES)
                ->count(),
            'myAwaiting' => $allMine
                ->whereIn('status', self::STAFF_AWAITING_STATUSES)
                ->count(),
            'myCompletedThisMonth' => $allMine
                ->filter(static fn (ProductionTask $task): bool => $task->completed_at !== null
                    && $task->completed_at->gte($monthStart))
                ->count(),
            'myOverdue' => $allMine
                ->filter(static fn (ProductionTask $task): bool => Atelier::taskIsOverdue(
                    $task->deadline,
                    $task->status
                ))
                ->count(),
        ];

        return view('dashboard', [
            'staffStats' => $staffStats,
            'myTasks' => $myTasks,
        ]);
    }
}
