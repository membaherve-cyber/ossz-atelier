# OSSZ ATELIER — Laravel Controller/View Contract (BINDING)

This document binds the backend agents (controllers) and view agents (Blade) to one
shared contract. It complements `agent-ctx/laravel-spec.md` (read that first — it wins
on conflicts, this file only ADDS precise variable contracts).

Base rules for ALL controllers:
- `declare(strict_types=1);`, namespace `App\Http\Controllers`, no base class.
- POST success: `return redirect()->back()->with('success', __('<file>.<key>'));`
  (or `redirect()->route('...')` where noted) + `Audit::log(...)` + `Notifier::...`.
- POST failure (business rule): `return redirect()->back()->with('error', __('...'))`.
- Authorization failure inside "mixed" actions: `abort(403)` or error-flash redirect.
- Validation via `$request->validate([...])` (errors render inline in Blade).
- STAFF scoping: every task query filtered to `assigned_staff_id === $me->id` for
  STAFF role users. Helpers: `$me = $request->user()`, `Permissions::allows($me,'x')`.
- Dates passed to views as Eloquent/Carbon; views format with `formatDateTime()`
  helper registered in AppServiceProvider (see below).

View composer (ALREADY implemented in AppServiceProvider — do NOT touch it):
```php
// shares with EVERY view: $me (User|null) and $unread (int)
View::composer('*', ...); // $view->with('me', $user)->with('unread', $count)
```

NO global date helper exists. Views format dates directly on Carbon-cast model
attributes: `{{ $task->deadline?->format('M j, Y, H:i') }}`,
`{{ $task->createdAt?->format('M j, Y') }}` etc. Migrations timestamp
columns use created_at/updated_at (Laravel convention) — check each model's
$casts for exact attribute names (Prisma createdAt => created_at).

## GET view contracts (view name => variables)

1. `auth.login` — no variables required (layout NOT used; standalone Blade file).
2. `dashboard` (single view, role-branch inside controller passes different shapes):
   - MANAGEMENT/ADMIN: `$stats` (assoc: registeredOrders, activeTasks, unassigned,
     ongoing, awaitingInspection, readyForPickup, delivered, blocked, cancelled,
     activeStaff, customers), `$dueSoon` (list of ProductionTask with
     order+customer+assignee loaded), `$overdue` (same), `$workload`
     (list of ['user'=>User,'activeCount'=>int]), `$recent` (list of
     AuditLog with user loaded, latest 8).
   - STAFF: `$staffStats` (myActive, myAwaiting, myCompletedThisMonth, myOverdue),
     `$myTasks` (list of ProductionTask with order+customer, latest 6),
     `$me` (from composer anyway).
   - View branches on `$me->role === 'STAFF'`.
3. `tasks.index` — `$tasks` (LengthAwarePaginator, 20/page; each ProductionTask
   with order.customer + assignee + stageStates loaded via eager loading),
   `$filters` (assoc with keys: q, staff, status, priority, overdue, sort,
   deadlineFrom, deadlineTo — current values), `$staffList` (list of active
   User with role != STAFF excluded — i.e. assignable users; EMPTY for STAFF),
   `$isStaff` (bool).
4. `tasks.create` — `$orders` (list of Order with customer + tasks count),
   `$staffList` (active assignable users), `$preselectOrderId` (?string).
5. `tasks.show` — `$task` (ProductionTask with order.customer, assignee,
   creator, stageStates.stage, comments.user, assignments.user,
   statusChanges.user, progressHistory, inspections.user ALL eager loaded),
   `$stages` (list of ProductionStage ordered by sortOrder — the FULL stage
   list; view joins with $task->stageStates by stage_id),
   `$staffList` (active assignable users, for assign dialog),
   `$isAssignee` (bool), `$isSupervisor` (bool: MANAGEMENT or ADMIN).
6. `orders.index` — `$orders` (list/paginated 20 of Order with customer +
   tasks loaded), `$customers` (list for the register modal select).
7. `orders.show` — `$order` (Order with customer + tasks.assignee +
   tasks.stageStates loaded) — rendered INSIDE the orders/index Alpine modal
   via fetch? NO: simpler — orders/index links to a details Alpine modal that
   fetches `GET /orders/{id}` as a FULL HTML fragment view `orders.show`
   (standalone fragment without layout: use `view('orders.show')` WITHOUT
   extending layout; the modal injects the returned HTML). Controller:
   `$order` eager-loaded; view is a partial (no layout, no <html>).
8. `customers.index` — `$customers` (list of Customer with ordersCount +
   orders tasks aggregated; include `ordersCount` via withCount('orders')).
9. `users.index` — `$users` (list of User ordered by role then name, each
   with activeTaskCount), `$me`.
10. `stages.index` — `$stages` (list of ProductionStage ordered by sortOrder).
11. `performance.index` — `$rows` (list of assoc arrays per staff user:
    ['user'=>User,'productivity'=>?float,'timeliness'=>?float,'quality'=>?float,
    'reliability'=>?float,'management'=>?float,'overall'=>?float,
    'pieces'=>int,'pieceTarget'=>int,'completed'=>int,'onTime'=>int,
    'overdue'=>int,'inspections'=>int,'approved'=>int,'corrections'=>int,
    'blockedCount'=>int,'assessment'=>?StaffAssessment]),
    `$config` (PerformanceConfig), `$rangeType`, `$rangeValue`,
    `$assessments` (list of StaffAssessment with assessor+user loaded, latest 10),
    `$staffList` (active STAFF users for assess form).
12. `incentives.index` — `$schemes` (list of IncentiveScheme with recordsCount),
    `$records` (paginated 20 IncentiveRecord with user + scheme + approvedBy
    loaded + filters support via query string: status, period, staff),
    `$filters` (assoc: status, period, staff), `$staffList`,
    `$canApprove` (bool = MANAGEMENT), `$canPrepare`, `$canManageSchemes`.
13. `reports.index` — `$period` ('month'|'quarter'|'year' current selection),
    `$stats` (assoc: totalTasks, completed, delivered, cancelled, blocked,
    overdue, avgProgress, totalPieces, byStatus [status=>count],
    byCategory [cat=>count], topStaff [list of ['user','completed','pieces']]),
    `$start`, `$end` (Carbon boundary dates).
14. `notifications.index` — `$notifications` (paginated 20 of Notification,
    own only, latest first).
15. `audit.index` — `$logs` (paginated 30 of AuditLog with user loaded,
    filters: action, user, from, to via query string), `$actions`
    (Atelier::AUDIT_ACTIONS), `$users` (all users for filter select),
    `$filters`.

## POST action contracts (input names => behavior) — mirror Next.js API

TaskActionController (all take $id; task loaded or 404; STAFF-only-own-task
checks apply; supervisor = MANAGEMENT/ADMIN):
- `assign`: input `staffId` (required, exists, role in assignableRoles), optional
  `note`. Creates/updates task.assigned_staff_id, status UNASSIGNED->ASSIGNED
  (stays if already ASSIGNED+reassign => notify old+new TASK_REASSIGNED else
  TASK_ASSIGNED), writes TaskAssignment row (type ASSIGN/REASSIGN), audit
  TASK_ASSIGNED/TASK_REASSIGNED, notification to new assignee (message=note).
- `status`: input `action` = start|submit|ready|deliver|reopen|cancel (+ optional
  `note`, and `reason` required for cancel). Gates (ported from Next.js
  tasks/[id]/route.ts): start (assignee or supervisor, from ASSIGNED or
  CHANGES_REQUESTED) -> ONGOING; submit (assignee, requires progress==100 OR all
  applicable stages COMPLETED) -> AWAITING_INSPECTION + notify supervisors;
  ready (supervisor, from APPROVED) -> READY_FOR_PICKUP + notify assignee;
  deliver (supervisor, from READY_FOR_PICKUP) -> DELIVERED + deliveredAt=now +
  notify assignee; reopen (supervisor, from APPROVED/READY_FOR_PICKUP/
  AWAITING_INSPECTION/CHANGES_REQUESTED, reason required) -> ONGOING; cancel
  (supervisor with production.cancel, not DELIVERED, reason required) ->
  CANCELLED. All write TaskStatusChange + audit + notifications per Next.js.
- `progress`: input `value` (int 0-100), optional `note`. Assignee or supervisor,
  status in ASSIGNED/ONGOING/CHANGES_REQUESTED. Clamp, write
  TaskProgressHistory(source MANUAL), audit PROGRESS_UPDATED, notify none.
- `stages`: input `updates` (array of ['stageId','state','comment'?]). Assignee
  (in-work statuses) or supervisor (non-terminal). Validates state in
  STAGE_STATES. Upserts TaskStageState, writes TaskStageUpdate rows,
  ProgressService::recompute, audit STAGES_UPDATED, notify counterpart
  (assignee updates -> notify supervisors; supervisor updates -> notify assignee).
- `inspect`: input `decision` (APPROVED|CHANGES_REQUESTED|REOPENED), `comments`
  (required >=3 chars for CHANGES_REQUESTED and REOPENED). Supervisor only
  (route perm). APPROVED: status APPROVED, progress=100 (history source
  INSPECTION), completedAt=now, notify assignee TASK_APPROVED;
  CHANGES_REQUESTED: status CHANGES_REQUESTED, notify CORRECTIONS_REQUESTED;
  REOPENED: status ONGOING, notify TASK_REOPENED. InspectionRecord row +
  INSPECTION_COMPLETED audit.
- `comment`: input `body` (required >=1), optional `isIssue` (checkbox). Assignee
  or supervisor, task not terminal. TaskComment row + audit COMMENT_ADDED /
  ISSUE_REPORTED (+ notify supervisors if issue).
- `block`: input `reason` (required >=3). Assignee or supervisor, not terminal,
  not already BLOCKED. Stores blocked_reason, status BLOCKED, TaskStatusChange,
  audit TASK_BLOCKED, notify management TASK_BLOCKED.
- `unblock`: input `note` (optional). Supervisor only. Clears blocked_reason,
  back to ONGOING, audit BLOCK_RESOLVED, notify assignee BLOCK_RESOLVED.
- `update`: input optional deadline, priority, instructions, garmentPiece.
  Supervisor only (route perm). Only changed fields applied; notify assignee
  DEADLINE_CHANGED and/or NEW_INSTRUCTIONS when those change; audit TASK_UPDATED.

TaskController:
- `store`: input `orderId` (required exists), `pieces` (array, min 1; each:
  garmentPiece required, category in TASK_CATEGORIES default FULL,
  staffId optional (assignable), priority in PRIORITIES default order priority,
  deadline optional date (default order expected delivery), instructions
  optional). Duplicate piece (same order + lower(garmentPiece)) => validation
  error. Creates tasks (Codes::task()), TaskStageState rows for ALL stages
  applicable to category (state NOT_STARTED), initial TaskAssignment if
  staffId, audit TASKS_CREATED (details: count + codes), notify assignees
  TASK_ASSIGNED. Redirect to route tasks.index with success toast
  __('tc.createdToast', ['count' => n]).
- `show` STAFF scoping: `abort(403)` if not assigned.
- `index` filters: q (matches code/garmentPiece/order.orderNumber/customer
  name), staff (assigned_staff_id or 'unassigned'), status, priority,
  overdue (bool), sort (updated|deadline|progress|created), deadlineFrom/to.
  STAFF: forced own-scope, no staff/priority filters shown (view hides).

OrderController:
- `store`: customerId (required exists — NO inline new customer; the Next.js
  flow registers customer separately), orderNumber (required, unique), orderDate
  (required date), expectedDeliveryDate (required date), priority (in
  PRIORITIES default NORMAL), pieceCount (required int min 1), description
  optional, instructions optional. Codes::order(), audit ORDER_CREATED,
  success __('orders.registeredToast').
- `update`: same fields optional; audit ORDER_UPDATED, toast.
- `destroy`: only when `$order->tasks()->count() === 0` else error flash
  __('orders.hasTasksError'); audit ORDER_UPDATED with details
  ['deleted'=>true, ...] (use ORDER_UPDATED code; there is no ORDER_DELETED
  code — keep ORDER_UPDATED).
- `show`: fragment view (no layout) for modal fetch.

CustomerController:
- `store`: name required, phone optional, email optional (email fmt), address
  optional, notes optional. Codes::customer(), audit CUSTOMER_CREATED,
  toast __('cust.createdToast').
- `update`: audit CUSTOMER_UPDATED, toast.
- `destroy`: only when no orders else error __('cust.hasOrdersError').

UserController:
- `store`: name, email (unique, lowercase), role (ADMIN: only STAFF allowed;
  MANAGEMENT: any role), password (min 8, confirmed via password_confirmation),
  isActive optional. Codes::user(), audit USER_CREATED, toast
  __('users.createdToast').
- `update`: ADMIN cannot act on non-STAFF target (403); nobody may edit the
  last active MANAGEMENT account's role/active status (error flash
  __('users.lastManagementError')); optional password reset (min 8, confirmed)
  audits USER_PASSWORD_RESET; audit USER_UPDATED; toast.
- `index`: users with activeTaskCount, ordered role (MANAGEMENT, ADMIN, STAFF)
  then name. View MUST display each user's `code` (USR-001) prominently.

StageController:
- `store`: name required unique, description optional, appliesTo (default
  'ALL'; free text but view offers ALL or comma-list of categories),
  sortOrder (int, default = max+1), weight (int 1-10 default 1),
  isActive default true. Audit STAGE_CREATED, toast __('stages.createdToast').
- `update`: same fields optional; audit STAGE_UPDATED, toast.

PerformanceController:
- `index`: reads query range (default: type=month, value=now format 'Y-m'),
    calls PerformanceService::forRange($type, $value).
- `updateConfig`: weights (productivity, timely, quality, reliability,
  management — each int 0-100; MUST total exactly 100 else error flash
  __('perf.weightsTotalError')), pieceTarget int min 1. Audit
  PERFORMANCE_CONFIG_UPDATED, toast __('perf.configSavedToast').
- `assess`: staffId (active STAFF), score (int 0-100), notes optional,
  periodValue default current month. Creates StaffAssessment (upsert per
  staff+period: update if exists), audit STAFF_ASSESSED, toast
  __('perf.assessedToast').

IncentiveController:
- `storeScheme`/`updateScheme`: name, periodType (INCENTIVE_PERIOD_TYPES),
  minScore (int 0-100), amountPerPiece (numeric min 0), fixedAmount
  (numeric min 0, one of amountPerPiece/fixedAmount may be 0 — at least one
  must be > 0), description optional, isActive. Audit INCENTIVE_SCHEME_CREATED/
  UPDATED, toasts.
- `generate`: periodValue (e.g. '2026-11' for MONTHLY), periodType. Uses
  PerformanceService for the range; for each active scheme x active STAFF:
  if overall >= minScore create PENDING IncentiveRecord (amount =
  fixedAmount + amountPerPiece * pieces) unless (scheme, user, periodValue)
  already exists (skip silently). Audit INCENTIVE_RECOMMENDATIONS_GENERATED
  (details: created count), toast __('inc.generatedToast', ['count'=>n]).
- `review`: decision (approve|reject) — MANAGEMENT only enforced by route perm
  (incentives.approve). approve: approvedAmount required numeric min >0,
  status APPROVED, approved_by, approved_at. reject: status REJECTED,
  rejectionReason required >=3. Audit INCENTIVE_APPROVED/REJECTED, notify
  staff user (type ISSUE_REPORTED? NO — notifications: use type
  'TASK_APPROVED'? NO — see Notifier: generic message works; use
  Notifier::users([$user->id], 'INCENTIVE'...)? NOTIFICATION_TYPES has no
  incentive type. Send plain notification with message text
  __('inc.notifApproved')/__('inc.notifRejected') stored as message with type
  'TASK_APPROVED'?? NO. Decision: do NOT send notifications for incentives;
  staff see their records under /incentives (perm incentives.view — staff
  does NOT have it, so no notification needed; Management/Admin review in
  UI). SKIP notifications here. Toasts only.)
- `payment`: recordId, paidAt date. MANAGEMENT only (route perm). Sets
  payment_status PAID + paid_at. Audit INCENTIVE_PAYMENT_RECORDED, toast.

ReportController `index`: period query param (month|quarter|year, default
month) — computes $stats over ProductionTask in range (created_at within
range for creation-based counts; completed_at for completion counts; see
Next.js reports view semantics: read src/components/atelier/views/
reports-view.tsx and src/app/api/atelier/reports/route.ts).

NotificationController:
- `index`: own notifications paginated 20, latest first.
- `read`: input `id` (single) or `all` (bool). Marks read, redirect back (or
  to route notifications.index) — no toast.

AuditController `index`: filters action/user/from/to; paginated 30;
Management only (route perm audit.view).

DashboardController: mirrors Next.js src/app/api/atelier/dashboard/route.ts
+ dashboard-view.tsx. dueSoon = deadline within 7 days, not terminal,
ordered by deadline, limit 8. overdue = Atelier::taskIsOverdue, limit 8.
workload = active tasks count per assignable user. recent = latest 8 audit
rows. STAFF variant: myActive (ASSIGNED/ONGOING/AWAITING_INSPECTION/
CHANGES_REQUESTED), myAwaiting (AWAITING_INSPECTION+CHANGES_REQUESTED),
myCompletedThisMonth (completedAt in current month), myOverdue.

## Routes/helpers the views rely on
- `route('locale.switch', 'fr')` links for language pills.
- `route('tasks.show', $task->id)` etc. for links.
- Flash: layout reads `session('success')` / `session('error')` into a single
  toast partial (auto-dismiss with Alpine `x-init`, aria-live).
- Validation errors: `@error('field')` inline red text + `old()` repopulation.
- CSRF: `@csrf` in EVERY form.

## HEADER DESIGN (binding — user-approved from the Next.js version)
The top header bar of EVERY authenticated page shows the logo CENTERED:
- The header is `relative flex items-center h-16 border-b bg-background/95
  backdrop-blur px-4 sm:px-6`.
- Centered element: `<a href="{{ route('dashboard') }}" class="absolute
  left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"><img src="/img/ossz-logo.png"
  alt="{{ __('login.logoAlt') }}" class="h-7 sm:h-9 w-auto rounded-md
  ring-1 ring-black/10"></a>` — mathematically centered, clickable to dashboard.
- Left side: mobile hamburger (Alpine off-canvas nav, lg:hidden) + page title
  `<h1 class="hidden sm:block font-display text-lg font-semibold truncate
  max-w-[calc(50%-6.5rem)]">`.
- Right side: language pills, bell link with `$unread` badge, user menu.
- On screens < 360px the logo may drop absolute centering and flow inline
  (`max-[359px]:static max-[359px]:translate-x-0 max-[359px]:translate-y-0`).
- The sidebar (desktop) and mobile drawer show centered TEXT brand only:
  "OSSZ ATELIER" + `__('shell.brandTag')` — NO logo image there.

## Files that agents MUST NOT touch
- Anything in app/Models, app/Services (except ADDING PerformanceService.php),
  app/Support, app/Http/Middleware, app/Providers/AppServiceProvider.php
  (ALREADY COMPLETE — it shares $me + $unread with every view via View::composer('*')
  and registers the login rate limiter; nobody edits it), config/*, bootstrap/*,
  routes/web.php, database/*, public/index.php, public/.htaccess, artisan,
  composer.json. Controllers live in app/Http/Controllers/*.php (TaskAction
  controller file name TaskActionController.php). Views under resources/views.

NO global date helper exists. Views format dates directly on Carbon-cast model
attributes: `{{ $task->deadline?->format('M j, Y, H:i') }}`. Check each model's
$casts for exact attribute names (Prisma createdAt => created_at).

## Badge partial API — B1 owns `resources/views/partials/badges.blade.php`
Implements Blade directives used by ALL view agents (B1 implements EXACTLY this
API; B2/B3/B4 call EXACTLY these — registered via Blade::directive in a small
AppServiceProvider-free way: define them in
`resources/views/partials/badges.blade.php` is NOT possible for directives, so
B1 instead creates `app/Support/BladeDirectives.php` as a plain static class
with `::register()` called ALREADY?? NO — see resolution below).

RESOLUTION (binding): B1 creates `resources/views/partials/badges.blade.php`
exposing ONE anonymous Blade component `<x-badges.status :status="$s"/>`,
`<x-badges.priority :priority="$p"/>`, `<x-badges.category :category="$c"/>`,
`<x-badges.role :role="$r"/>`, `<x-badges.stage-state :state="$s"/>`
(component files under resources/views/components/badges/*.blade.php —
AUTOMATICALLY discovered by Laravel, NO registration needed).
Base classes on every badge: `inline-flex items-center rounded-full border
px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap`. Palettes per spec §8:
status UNASSIGNED stone-100/700/300, ASSIGNED teal-50/800/300, ONGOING
amber-50/800/300, AWAITING_INSPECTION orange-50/800/300, CHANGES_REQUESTED
rose-50/800/300, APPROVED green-50/800/300, READY_FOR_PICKUP
emerald-50/800/300, DELIVERED stone-800/50/800, BLOCKED red-50/800/300,
CANCELLED stone-100/500/300 + line-through. Labels via
`__('labels.status.'.$status)`, `__('labels.priority.'.$p)`,
`__('labels.category.'.$c)`, `__('labels.role.'.$r)`,
`__('labels.stageState.'.$s)`.

## lang file ownership (ONLY if a string is truly missing — prefer existing keys)
Read lang/en/<file>.php + lang/fr/<file>.php FIRST and use only existing keys
wherever possible (17 file pairs mirror the complete Next.js string inventory;
the views mirror the same UI so coverage should be complete). If a key is
genuinely missing, add it to BOTH locales in the file you OWN — B1: shell.php,
notif.php, common.php, labels.php; B2: tasks.php, tc.php, td.php; B3: orders.php,
cust.php, users.php, stages.php; B4: perf.php, inc.php, rep.php. A-agents add
NO lang keys. NEVER edit lang/audit.php, lang/login.php, lang/dash.php
(read-only; use the closest existing key if something is missing there).
