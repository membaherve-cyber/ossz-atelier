# Task A3 — Backend "Insights" controllers (Dashboard / Incentives / Reports / Notifications / Audit)

Agent: A3 (backend-insights) — Laravel conversion phase
Status: COMPLETE — all 5 controllers written, `php -l` clean on every file (PHP 8.4.8 toolchain).

## Files written (all mine, nothing else touched)

| File | Lines | Route coverage |
|---|---|---|
| `app/Http/Controllers/DashboardController.php` | 293 | GET / (`dashboard` view) |
| `app/Http/Controllers/IncentiveController.php` | 661 | GET /incentives + POST schemes, schemes/{id}, generate, {id}/review, {id}/payment |
| `app/Http/Controllers/ReportController.php` | 217 | GET /reports (`reports.index`) |
| `app/Http/Controllers/NotificationController.php` | 90 | GET /notifications + POST /notifications/read |
| `app/Http/Controllers/AuditController.php` | 111 | GET /audit (`audit.index`) |

PerformanceController was NOT written (owned by A1, per contract).

## View variable contracts delivered (exactly per laravel-view-contract.md)

### `dashboard` (single view, branch on `$me->role === 'STAFF'`)
- MANAGEMENT/ADMIN: `$stats` (registeredOrders, activeTasks, unassigned, ongoing,
  awaitingInspection, readyForPickup, delivered, blocked, cancelled, activeStaff,
  customers — all ints), `$dueSoon` (ProductionTask with order.customer +
  assignedStaff eager-loaded; deadline within next 7 days, not terminal, not yet
  overdue, orderBy deadline asc, limit 8), `$overdue` (same loads; deadline past,
  not terminal, orderBy updated_at desc — Next.js parity, limit 8),
  `$workload` (list of ['user'=>User,'activeCount'=>int] over ACTIVE users in
  assignable roles, name-ordered), `$recent` (latest 8 AuditLog).
- STAFF: `$staffStats` (myActive = ASSIGNED/ONGOING/AWAITING_INSPECTION/
  CHANGES_REQUESTED; myAwaiting = AWAITING_INSPECTION+CHANGES_REQUESTED;
  myCompletedThisMonth = completed_at >= start of current month; myOverdue =
  Atelier::taskIsOverdue), `$myTasks` (latest 6 by updated_at, order.customer loaded).

### `incentives.index`
`$schemes` (each with in-memory ->recordsCount AND ->records_count attached),
`$records` (paginated 20, latest first, filters via query: status, period, staff),
`$filters` (status/period/staff, period uppercased), `$staffList` (active STAFF
users, name-ordered), `$canApprove` (MANAGEMENT), `$canPrepare`,
`$canManageSchemes` (Permissions::allows).

### `reports.index`
`$period` (month|quarter|year), `$stats` (totalTasks, completed, delivered,
cancelled, blocked, overdue, avgProgress, totalPieces, byStatus [all 10 statuses,
0-filled], byCategory [all 6 categories, 0-filled], topStaff [up to 5 of
['user'=>User,'completed'=>int,'pieces'=>int]]), `$start`/`$end` (Carbon,
inclusive start / exclusive end).

### `notifications.index`
`$notifications` (own only, latest first, paginate 20, withQueryString).

### `audit.index`
`$logs` (paginate 30, withQueryString, orderBy created_at desc then id desc),
`$actions` (= Atelier::AUDIT_ACTIONS), `$users` (all users, name-ordered),
`$filters` (action/user/from/to).

## IMPORTANT for view agents (B1/B4): snapshot models have no Eloquent relations

IncentiveRecord / IncentiveScheme / AuditLog keep denormalized snapshots
(staff_name, scheme_name, approved_by_name, user_name …) and define NO relation
methods (1:1 Prisma port). `with('user')` would throw. My controllers attach the
related models IN MEMORY so BOTH access styles work:
- `$record->user?->name` / `$record->scheme?->name` / `$record->approvedBy?->name` (may be null if the account/scheme was deleted)
- or the snapshots `$record->staff_name` / `$record->scheme_name` / `$record->approved_by_name` / `$log->user_name` (user_name is never null — Audit writes "System").
- Schemes: `$scheme->recordsCount` (also `->records_count`).

## POST input contracts (Blade forms — exact input names)

- `POST /incentives/schemes`: name (required 2-120), periodType (INCENTIVE_PERIOD_TYPES),
  minScore (nullable numeric 0-100), amount (nullable numeric >= 0 — single amount
  column, FCFA), description (nullable max 2000), isActive (optional boolean,
  default true).
- `POST /incentives/schemes/{id}`: same fields, all optional (only present keys applied).
- `POST /incentives/generate`: periodType + periodValue (required string) — iterates
  every ACTIVE scheme of that periodType × active STAFF; OPTIONAL schemeId narrows to
  one scheme (Next.js UI parity — inc.gen.* lang keys still offer a scheme select).
  periodValue formats: MONTHLY/QUALITY/ON_TIME/CUSTOM = YYYY-MM, QUARTERLY = YYYY-QN,
  ANNUAL = YYYY. Below-minScore staff skipped; existing (scheme, staff, period)
  records skipped silently; created rows: eligible=true, proposed_amount =
  scheme->amount ?? 0, status PENDING, payment_status UNPAID.
- `POST /incentives/{id}/review`: decision (approve|reject). approve →
  approvedAmount required numeric > 0 (validation error). reject → rejectionReason
  required min 3 (stored in `management_comments`). Optional comments /
  approvalComments → stored in `approval_comments`. Only PENDING records reviewable
  (else error flash inc.toast.reviewFailed).
- `POST /incentives/{id}/payment`: paidAt (optional date, default now) →
  payment_status PAID + `payment_date` column. Only APPROVED & unpaid records.

## Audit codes + toasts used

INCENTIVE_SCHEME_CREATED/UPDATED, INCENTIVE_RECOMMENDATIONS_GENERATED (details
['created'=>n, 'period', 'periodType', 'schemes']), INCENTIVE_APPROVED/REJECTED
(entity INCENTIVE_RECORD, details recordId/amount/staffName/period),
INCENTIVE_PAYMENT_RECORDED (paymentDate as DATE_ATOM).
Toasts: inc.toast.schemeCreated / schemeUpdated / generatedDesc (with count) /
approvedTitle / rejectedTitle / paymentRecorded; errors: inc.toast.reviewFailed /
paymentFailed / generationFailed / formatMonth|formatQuarter|formatYear /
selectSchemeDesc.

## Ambiguities resolved (deviations from the task text, all forced by the binding schema)

1. **"amount = fixed_amount + amount_per_piece * pieces"** — incentive_schemes has
   a SINGLE `amount` column (verified migration + Prisma schema + inc.schemeDialog
   lang keys "Amount (FCFA)"). Followed the Next.js generate route semantics:
   proposedAmount = scheme amount (all created rows are eligible, so no 0-amount rows).
2. **"approved_at"** — no such column on incentive_records (Prisma has none).
   Approval actor/timestamps: approved_by_id + approved_by_name + updated_at.
3. **"rejectionReason"** — input name kept, stored in `management_comments`
   (rejection is a Management decision); optional decision note in `approval_comments`.
4. **"payment_status PAID + paid_at"** — the column is `payment_date` (Prisma
   paymentDate). Input name stays `paidAt` per the contract.
5. **`inc.generatedToast` does not exist** in lang/en+fr (A-agents add no keys).
   Used `inc.toast.generatedDesc` with {total}/{eligible} = created count.
6. **Lang placeholders are `{param}` (ICU) style, NOT Laravel `:param`** — core
   Laravel does NOT substitute `{param}` in `__()` calls. All my parameterized
   flashes go through a private `transWithBraceParams()` helper that replaces both
   styles. **Every view agent must handle this too** (e.g. dash.welcome uses
   `{name}`, dash.awaitingCount uses `{count}`) — either str_replace at call time or
   ask the finalizer for a global fix (e.g. a Translator::stringable hook). All
   OTHER flash keys I use are placeholder-free.
7. **Dashboard dueSoon** excludes already-overdue tasks (deadline >= now) per the
   Next.js filter — overdue work renders in the separate overdue card.
8. **activeStaff stat** = active users holding the STAFF role (project vocabulary:
   "active STAFF" = role STAFF + is_active, cf. generate instruction). The workload
   list separately covers all assignable roles per its contract.
9. **Report base populations**: creation-based (tasks created in range) for
   totalTasks/byStatus/byCategory/cancelled/blocked/overdue/avgProgress;
   completion-based for completed/delivered/totalPieces (= completed; "one task =
   one garment piece" per Next.js) and topStaff (completed_at in range grouped by
   assigned_staff_id, top 5). `$period` selects the CURRENT month/quarter/year; an
   optional `value` query param (YYYY-MM | YYYY-QN | YYYY) addresses a past period
   (Next.js parity) — views may ignore it.
10. **Dashboard TASK_OVERDUE side effect** (ported exactly from the Next.js
    dashboard route): on each manager dashboard hit, assigned tasks that just went
    overdue and have no TASK_OVERDUE notification yet generate one notification to
    all active MANAGEMENT/ADMIN users (message "CODE — piece (orderNumber) has
    passed its deadline."). No other controller emits TASK_OVERDUE.
11. **recent = 8** (contract) vs 12 (Next.js) — followed the contract.
12. **Notification read** uses `$request->boolean('all')` (accepts 1/true/on) and
    scopes single-id updates to the owner; redirects back, no toast.

## PerformanceService integration (A1 — parallel agent)

- Called ONLY in `generate()`: `PerformanceService::forRange($rangeType, $periodValue)`
  with rangeType in {MONTH, QUARTER, YEAR} mapped from the scheme period type
  (SCHEME_PERIOD_MAP port). Wrapped in try/catch → error flash on failure.
- Defensive row parsing: accepts a plain list of rows OR a ['rows'=>…]/['staff'=>…]
  envelope; staff id read from staffId/staff_id/userId/user_id; score read from
  `scores.quality|timeliness|overall` (nested), flat `quality|timeliness|overall`,
  or overallScore/overall_score fallbacks. QUALITY schemes score against quality,
  ON_TIME against timeliness, everything else against overall (Next.js parity).
- `::config()` not needed by any of my controllers.

## Verification

- `/home/z/php-toolchain/php -l` clean on all 5 files.
- Every column name cross-checked against the migrations
  (production_tasks, orders, users, notifications, audit_logs, incentive_schemes,
  incentive_records): status, assigned_staff_id, deadline, created_at/updated_at,
  completed_at, delivered_at, code, garment_piece, order_number, user_id, is_read,
  task_id, type, action, entity_type, entity_id, scheme_id/staff_id/staff_name/
  scheme_name/period/performance_score/eligible/proposed_amount/approved_amount/
  approved_by_id/approved_by_name/approval_comments/management_comments/
  payment_status/payment_date, is_active, role, name, min_score, amount,
  description, period_type, created_by_id/created_by_name.
- No model/service/lang/migration/route files were modified.
