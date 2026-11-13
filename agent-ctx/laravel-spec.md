# OSSZ ATELIER — Laravel Conversion Architecture Spec (CONTRACT)

This document is the BINDING contract for all Laravel conversion agents. When this spec
and your own judgment disagree, the spec wins. Read the whole spec before writing code.

## 0. Mission

Convert the working Next.js reference implementation of "OSSZ ATELIER" (independent
garment-production management system) into a standalone **Laravel 11 + Blade + Alpine.js +
MySQL** application that deploys on Namecheap shared hosting (cPanel) at
atelier.osszcollection.com. The deliverable lives in `/home/z/my-project/laravel-atelier/`.
The sandbox has NO PHP — never try to run `php`/`composer`/`artisan`. Write correct code
by construction; it will be run on the server after `composer install`.

Reference implementation (READ these, they contain the exact business rules and UI):
- Data model: `/home/z/my-project/prisma/schema.prisma` (18 models)
- Seed data: `/home/z/my-project/prisma/seed.ts`
- Business logic: `/home/z/my-project/src/lib/atelier/*.ts` (constants, production,
  performance, tasks, audit, notify) and `/home/z/my-project/src/lib/permissions.ts`
- API behavior (status transitions, validation, notifications, audit codes):
  `/home/z/my-project/src/app/api/atelier/**`
- UI structure + bilingual strings: `/home/z/my-project/src/components/atelier/**`
  (dict files in `src/components/atelier/i18n/dict/*.ts` are the COMPLETE string inventory)

Hard requirements inherited from the project spec:
- 100% standalone: own database (`ossz_atelier`), own accounts, no connection to the OSSZ
  e-commerce site. Customers and orders are registered MANUALLY.
- Roles: MANAGEMENT (all permissions, sole final approver of incentives), ADMIN (no
  incentive approval, no managing Management accounts), STAFF (sees/updates ONLY own
  assigned tasks). No public registration.
- 12 configurable checklist stages, 4 states each (NOT_STARTED/IN_PROGRESS/COMPLETED/
  NOT_APPLICABLE); progress = weighted average of stages (IN_PROGRESS counts half).
- 10 task statuses; Ready for Pickup ONLY after quality inspection APPROVED.
- Performance weights: productivity 30 / timely 25 / quality 30 / reliability 10 /
  management 5 (configurable, must total 100). pieceTarget default 20/month.
- Internal notifications only. Permanent audit trail (Management-only visibility).
- Bilingual EN/FR everywhere with a language switch. UI language from session.
- No hardcoded staff/customer/order names in code (seed data is fine).

## 1. Stack & versions

- PHP >= 8.2, laravel/framework ^11.0 (ONLY dependency; no other composer packages).
- Blade views + Alpine.js 3 (local file `public/js/alpine.min.js`, downloaded at
  finalization; still reference it as `<script src="/js/alpine.min.js" defer></script>`).
- Tailwind CSS v4 via a single committed compiled stylesheet `public/css/app.css`
  (built at finalization from `resources/css/app.css`; do NOT add a build step for the
  server). Views may use ONLY: the brand tokens below, standard Tailwind utilities
  (spacing/layout/typography/borders/shadows/flex/grid/etc.), and the standard palettes
  `stone, teal, amber, orange, rose, green, emerald, red`.
- MySQL/MariaDB via PDO. String enums (validated in app layer), no DB enum columns.

## 2. Directory layout (exact)

```
laravel-atelier/
├── app/
│   ├── Http/Controllers/        (see §6)
│   ├── Http/Middleware/         SetLocale.php, EnsurePermission.php
│   ├── Models/                  18 models (see §4)
│   ├── Providers/AppServiceProvider.php
│   ├── Services/                Permissions.php, Codes.php, ProgressService.php,
│                                PerformanceService.php, Notifier.php, Audit.php
│   └── Support/Atelier.php      (domain constants: statuses, priorities, categories,
│                                 stage states, inspection decisions, notification types,
│                                 audit action codes, role/permission lists)
├── bootstrap/app.php, bootstrap/providers.php
├── config/app.php, auth.php, cache.php, database.php, filesystems.php, logging.php, session.php
├── database/migrations/         (see §4), database/seeders/DatabaseSeeder.php
├── lang/en/*.php, lang/fr/*.php (see §7)
├── public/index.php, public/.htaccess, public/robots.txt
├── resources/css/app.css        (brand tokens — finalizer owns)
├── resources/views/             (see §8)
├── routes/web.php
├── composer.json, artisan, .env.example, .gitignore, README.md
```

## 3. Routes (web.php — exact table; CSRF-protected POST everywhere)

Middleware groups: `web` (default) + global `SetLocale`; named middleware alias
`perm` => EnsurePermission (usage: `->middleware('perm:orders.manage')`).

| Method | URI | Controller@method | Access |
|---|---|---|---|
| GET | /locale/{lang} | LocaleController@switch (en\|fr; store session `locale`; redirect back) | public |
| GET | /login | Auth\\LoginController@show | guest |
| POST | /login | Auth\\LoginController@attempt | guest (rate limit 5/min per email+ip) |
| POST | /logout | Auth\\LoginController@logout | auth |
| GET | / | DashboardController@index | auth |
| GET | /tasks | TaskController@index | auth (STAFF: own tasks only) |
| GET | /tasks/create | TaskController@create | perm:production.create.tasks |
| POST | /tasks | TaskController@store | perm:production.create.tasks |
| GET | /tasks/{id} | TaskController@show | auth (STAFF: own only) |
| POST | /tasks/{id}/assign | TaskActionController@assign | perm:production.assign |
| POST | /tasks/{id}/status | TaskActionController@status | mixed (see §6) |
| POST | /tasks/{id}/progress | TaskActionController@progress | mixed |
| POST | /tasks/{id}/stages | TaskActionController@stages | mixed |
| POST | /tasks/{id}/inspect | TaskActionController@inspect | perm:production.inspect |
| POST | /tasks/{id}/comment | TaskActionController@comment | auth (STAFF on own task) |
| POST | /tasks/{id}/block | TaskActionController@block | mixed |
| POST | /tasks/{id}/unblock | TaskActionController@unblock | mixed |
| POST | /tasks/{id}/update | TaskActionController@update | perm:production.manage.details |
| GET | /orders | OrderController@index | perm:orders.view |
| POST | /orders | OrderController@store | perm:orders.manage |
| GET | /orders/{id} | OrderController@show | perm:orders.view |
| POST | /orders/{id} | OrderController@update | perm:orders.manage |
| POST | /orders/{id}/delete | OrderController@destroy | perm:orders.manage (only if no tasks) |
| GET | /customers | CustomerController@index | perm:customers.view |
| POST | /customers | CustomerController@store | perm:customers.manage |
| POST | /customers/{id} | CustomerController@update | perm:customers.manage |
| POST | /customers/{id}/delete | CustomerController@destroy | perm:customers.manage (only if no orders) |
| GET | /users | UserController@index | perm:users.view |
| POST | /users | UserController@store | perm:users.manage.staff |
| POST | /users/{id} | UserController@update | perm:users.manage.staff (ADMIN: staff only; last-active-MANAGEMENT safeguard) |
| GET | /stages | StageController@index | perm:production.stages.configure (view) — ADMIN can view |
| POST | /stages | StageController@store | perm:production.stages.configure |
| POST | /stages/{id} | StageController@update | perm:production.stages.configure |
| GET | /performance | PerformanceController@index | perm:performance.view |
| POST | /performance/config | PerformanceController@updateConfig | perm:performance.configure |
| POST | /performance/assess | PerformanceController@assess | perm:performance.assess |
| GET | /incentives | IncentiveController@index | perm:incentives.view |
| POST | /incentives/schemes | IncentiveController@storeScheme | perm:incentives.manage.schemes |
| POST | /incentives/schemes/{id} | IncentiveController@updateScheme | perm:incentives.manage.schemes |
| POST | /incentives/generate | IncentiveController@generate | perm:incentives.prepare |
| POST | /incentives/{id}/review | IncentiveController@review | perm:incentives.approve (approve/reject — MANAGEMENT ONLY) |
| POST | /incentives/{id}/payment | IncentiveController@payment | perm:incentives.payment (MANAGEMENT ONLY) |
| GET | /reports | ReportController@index | perm:production.reports |
| GET | /notifications | NotificationController@index | auth |
| POST | /notifications/read | NotificationController@read | auth |
| GET | /audit | AuditController@index | perm:audit.view (MANAGEMENT only) |

Permission keys = the exact PERMISSIONS list in `/home/z/my-project/src/lib/permissions.ts`
(port matrix verbatim into App\Services\Permissions).

## 4. Data model (migrations + models)

Port `/home/z/my-project/prisma/schema.prisma` to Laravel migrations 1:1:
- Table names follow Laravel conventions from model names: users, customers, orders,
  production_tasks, task_assignments, production_stages, task_stage_states,
  task_stage_updates, task_progress_histories, task_status_changes, inspection_records,
  task_comments, notifications, performance_configs, staff_assessments, incentive_schemes,
  incentive_records, audit_logs.
- All PKs: `char(36)` string UUIDs EXCEPT performance_configs.id = string 'main'.
  Models: `protected $keyType = 'string'; public $incrementing = false;` and generate
  UUIDs via `Str::uuid()` in a shared `creating` hook or in services (be consistent:
  use a `HasUuid` trait in app/Models/Concerns).
- Columns/types/defaults/indexes/unique constraints exactly as in schema.prisma
  (string = varchar(255) unless obviously longer: use text for `details`, `message`,
  `instructions`, `notes`, `comments`, `description`; `$table->string('role')->default('STAFF')` etc.).
- Boolean + nullable timestamps as in Prisma. Add proper foreign keys with
  `onDelete('cascade')` for child tables (task_* -> production_tasks, orders ->
  customers, notifications -> users).
- Models: `protected $guarded = ['id'];` (mass-assignable), `$casts` for booleans and
  datetimes, and Eloquent relations matching Prisma (User hasMany assignedTasks etc.).
- Model list (18): User, Customer, Order, ProductionTask, TaskAssignment,
  ProductionStage, TaskStageState, TaskStageUpdate, TaskProgressHistory,
  TaskStatusChange, InspectionRecord, TaskComment, Notification, PerformanceConfig,
  StaffAssessment, IncentiveScheme, IncentiveRecord, AuditLog.
- User model implements Authenticatable via `Illuminate\Foundation\Auth\User` +
  `Notifiable` (unused channels ok).

## 5. Services (app/Services) — contracts

- **Permissions**: `allows(User $user, string $permission): bool`; constants for the 3
  roles; matrix ported from src/lib/permissions.ts. Also `assignableRoles()`.
- **Codes**: next code generators with retry-on-collision, zero-padded 3-4 digits:
  `user()` => "USR-001", `customer()` => "CUST-0001", `task()` => "TASK-0001",
  `order()` => "ORD-0001". Read src/lib/atelier/tasks.ts for existing patterns.
- **ProgressService**: `recompute(ProductionTask $task): int` — weighted stage average
  (COMPLETED=1, IN_PROGRESS=0.5, NOT_STARTED/NOT_APPLICABLE=0 for applicable stages;
  stages filtered by appliesTo vs task category; no applicable stages => keep manual
  progress). Writes TaskProgressHistory rows with source STAGE when changed.
- **PerformanceService**: `forRange(string $rangeType, string $rangeValue): array` —
  port the EXACT formulas from src/lib/atelier/performance.ts (productivity =
  piecesCompleted/pieceTarget capped 100; timeliness from on-time vs overdue completions;
  quality from inspection approval rate; reliability from corrections/blocked penalties;
  management from StaffAssessment score if assessed else null weight renormalization;
  overall = weighted sum). Returns rows keyed like the Next.js StaffPerformanceRow.
  `config(): PerformanceConfig` (create default if missing).
- **Notifier**: `users(array $userIds, string $type, ?string $message = null, ?string $taskId = null, ?string $orderId = null)`,
  `roles(array $roles, ...)`, `management(...)` — creates Notification rows; title =
  `__('labels.notifTitle.' . $type)` computed AT DISPLAY TIME instead: store the ENGLISH
  title from lang/en (so rows are readable in DB) — views render
  `__('labels.notifTitle.'.$n->type)` when key exists (fallback to stored title).
- **Audit**: `log(?User $user, string $action, ?string $entityType = null, ?string $entityId = null, ?array $details = null)` —
  action codes = the exact codes in `src/components/atelier/i18n/dict/audit.ts`
  (`audit.action.*` keys: LOGIN_SUCCESS, LOGIN_FAILED, LOGIN_BLOCKED_INACTIVE, LOGOUT,
  USER_CREATED, USER_UPDATED, USER_PASSWORD_RESET, STAFF_ASSESSED, CUSTOMER_CREATED,
  CUSTOMER_UPDATED, ORDER_CREATED, ORDER_UPDATED, TASKS_CREATED, TASK_ASSIGNED,
  TASK_REASSIGNED, TASK_UPDATED, TASK_CANCELLED, TASK_BLOCKED, READY_FOR_PICKUP,
  DELIVERED, TASK_STATUS_CHANGED, BLOCK_RESOLVED, SUBMITTED_FOR_INSPECTION,
  INSPECTION_COMPLETED, STAGES_UPDATED, PROGRESS_UPDATED, COMMENT_ADDED, ISSUE_REPORTED,
  STAGE_CREATED, STAGE_UPDATED, PERFORMANCE_CONFIG_UPDATED, INCENTIVE_SCHEME_CREATED,
  INCENTIVE_SCHEME_UPDATED, INCENTIVE_RECOMMENDATIONS_GENERATED, INCENTIVE_APPROVED,
  INCENTIVE_REJECTED, INCENTIVE_PAYMENT_RECORDED). details stored as JSON string.

Business rules to port EXACTLY (read the Next.js API routes named below):
- Status transitions + permission gates + notifications:
  src/app/api/atelier/tasks/[id]/route.ts (PATCH actions) and
  src/app/api/atelier/tasks/route.ts (create + assign).
- ASSIGN allows MANAGEMENT/ADMIN/STAFF targets; reassignment notifies old+new assignee.
- ONGOING start: assignee or supervisor. Submit for inspection: assignee sets
  AWAITING_INSPECTION (requires progress 100 OR all applicable stages COMPLETED).
- Inspection APPROVED => status APPROVED, progress=100, completedAt=now;
  CHANGES_REQUESTED => status CHANGES_REQUESTED + notify assignee; REOPENED => ONGOING.
- READY_FOR_PICKUP only from APPROVED (supervisor); DELIVERED sets deliveredAt.
- BLOCKED requires reason, stores blockedReason, notifies management; unblock clears.
- CANCELLED requires reason (supervisor, not DELIVERED).
- Deadline/instructions update notifies assignee (DEADLINE_CHANGED / NEW_INSTRUCTIONS).
- Order delete only when it has no tasks. Customer delete only when no orders.
- Users: ADMIN cannot act on non-STAFF accounts; cannot deactivate/edit the last active
  MANAGEMENT account; password resets audit USER_PASSWORD_RESET; user codes USR-xxx.
- Incentives: generation creates PENDING records (scheme x staff x period unique, skip
  existing); eligibility = score >= minScore; approve/reject MANAGEMENT only
  (ADMIN sees but cannot); approved amount required on approval; payment mark PAID+date.
- Login: rate limit 5 attempts/min per email+IP (RateLimiter), inactive account blocked
  (LOGIN_BLOCKED_INACTIVE), success audits LOGIN_SUCCESS + regenerates session id.

## 6. Controllers

Standard resource-ish controllers returning **Blade views** (GET) or **redirect back
with success flash + audit + notifications** (POST). Validation via
`$request->validate([...])` with rules matching src/lib/validation.ts. Flash pattern:
`return redirect()->back()->with('success', __('orders.registeredToast'));` — layout
renders one toast/alert partial reading `session('success')` / `session('error')`.
STAFF scoping: every query limited to `assigned_staff_id = user id` for STAFF role.
`TaskActionController@status` reads `$request->input('action')`:
start|submit|ready|deliver|reopen|cancel and applies §5 rules.
Errors: validation errors redirect back with errors (Blade shows them inline);
authorization failures abort(403) or redirect back with error flash.

## 7. Language files (lang/en + lang/fr — mirror of the TS dictionaries)

Port EVERY entry of the 16 TS dict files in
`/home/z/my-project/src/components/atelier/i18n/dict/` into PHP lang files. Mapping:
dict file `X.ts` with key `"seg1.seg2.seg3"` => file `lang/{locale}/seg1.php` with
nested array `['seg2' => ['seg3' => 'text']]`. Files produced: common, shell, login,
dash, notif, tasks, tc, td, orders, cust, perf, inc, rep, users, stages, audit.
PLUS `lang/{locale}/labels.php` ported from the domain-label keys in `common.ts`
(status.*, priority.*, category.*, stageState.*, role.*, inspection.*,
incentivePeriod.*, incStatus.*, paymentStatus.*, notifTitle.*) as nested arrays.
Usage in Blade: `__('tasks.title')`, `__('labels.status.ONGOING')`.
Also add `lang/{locale}/validation.php` minimal custom attribute names (skip — default
English Laravel validation messages acceptable) and `lang/{locale}/flash.php` if needed.
KEEP EN and FR arrays key-identical (same structure, same keys).

## 8. Views (Blade + Alpine + Tailwind)

Layout `resources/views/layouts/app.blade.php`:
- `<html lang="{{ str_replace('_','-',app()->getLocale()) }}">`, dark sidebar (see §9
  tokens) with logo `/img/ossz-logo.png` + "OSSZ ATELIER" + `__('shell.brandTag')`;
  nav sections per role EXACTLY like navFor() in
  src/components/atelier/app-shell.tsx (STAFF: Workspace[My Dashboard, My Tasks,
  Notifications]; else Overview[Dashboard], Production[Production, Create Tasks,
  Orders, Customers], Insights[Performance, Incentives, Reports], Administration
  [Management: Users & Roles, Production Stages, Audit History; Admin: Staff Accounts,
  Production Stages]) with active-state highlight; footer text `__('shell.footer.left',
  ['year' => date('Y')])` + `__('shell.footer.right')`.
- Header: view title, language switch (two pill buttons EN/FR linking
  `/locale/en|fr`, active one highlighted with bg-primary), notification bell
  (Alpine dropdown; polling every 30s via fetch to a small JSON endpoint
  `GET /notifications` returning `{html}` — simpler: bell links straight to
  /notifications page and shows unread count badge from view composer), user menu
  (Alpine dropdown: name, email, role, User ID code, Sign out POST form).
- Sticky footer at bottom (`min-h-screen flex flex-col` wrapper, footer `mt-auto`).
- View composer or layout `@php` block shares: `$me = auth()->user()`,
  `$unreadCount = \App\Models\Notification::where('user_id', auth()->id())
  ->where('is_read', false)->count()`.
- Include `<script src="/js/alpine.min.js" defer></script>` and
  `<link rel="stylesheet" href="/css/app.css">`.

Per-screen views mirror the Next.js views (same sections, badges, tables, dialogs as
Alpine `x-data` toggled modals, same Tailwind classes where practical):
auth/login (brand panel with hero image /img/atelier-hero.png, language switch visible
pre-login), dashboard (manager + staff variants), tasks/index (+filters+pagination),
tasks/create (order picker, per-piece rows, summary), tasks/show (progress+checklist,
workflow actions, comments, details card, history tabs), orders/index (+detail Alpine
modal + register/edit modal), customers/index, users/index (+create/edit/password/status
modals), stages/index (+create/edit modal), performance/index (range picker, weights
form MANAGEMENT, assessments, table), incentives/index (schemes tab + records tab +
generation + review modal + payment), reports/index (period select + stats + table),
notifications/index, audit/index (filters + table, Management only).
ALL strings via `__('...')`. Codes (TASK-0001 etc.), names, DB content never translated.
Badge colors per status (port STATUS_STYLES from ui-bits.tsx using palette classes:
UNASSIGNED stone-100/700/300, ASSIGNED teal-50/800/300, ONGOING amber-50/800/300,
AWAITING_INSPECTION orange-50/800/300, CHANGES_REQUESTED rose-50/800/300, APPROVED
green-50/800/300, READY_FOR_PICKUP emerald-50/800/300, DELIVERED stone-800/50/800,
BLOCKED red-50/800/300, CANCELLED stone-100/500/300 + line-through).
A shared Blade partial `partials/badges.blade.php` exposing
`@statusBadge($status) @priorityBadge($p) @categoryBadge($c) @roleBadge($r)
@stageStateChip($s)` (using labels.php translations) is owned by the tasks agent;
others import it.

## 9. Brand tokens (resources/css/app.css — owned by finalizer, views assume them)

```css
@import "tailwindcss";
@theme {
  --color-background: #f9f9fa;      --color-foreground: #26262c;
  --color-card: #ffffff;            --color-card-foreground: #26262c;
  --color-primary: #5533AA;         --color-primary-foreground: #ffffff;
  --color-secondary: #ececf0;       --color-secondary-foreground: #4b4658;
  --color-muted: #eeeef1;           --color-muted-foreground: #71717a;
  --color-accent: #f4f0fb;          --color-accent-foreground: #553ea0;
  --color-destructive: #dc2626;
  --color-border: #e2e2e7;          --color-input: #e2e2e7;   --color-ring: #8b6fd8;
  --color-sidebar: #1b1b20;         --color-sidebar-foreground: #e9e9ec;
  --color-sidebar-primary: #8b6fd8; --color-sidebar-primary-foreground: #17171c;
  --color-sidebar-accent: #34343c;  --color-sidebar-accent-foreground: #f1f1f3;
  --color-sidebar-border: #3d3d45;
  --font-display: "Georgia", "Times New Roman", serif;
}
```
So utilities like `bg-primary text-primary-foreground bg-sidebar border-sidebar-border
text-muted-foreground bg-muted/50 font-display` all exist. Views use them freely.

## 10. Non-negotiables

- Never commit real credentials. .env.example uses EMPTY values.
- Never connect to anything but the configured `ossz_atelier` database.
- All POST forms include @csrf. All queries scoped per role.
- Bilingual parity: every UI string from the TS dicts must exist in BOTH locales.
- PSR-12, strict types where practical, `declare(strict_types=1);` optional.
