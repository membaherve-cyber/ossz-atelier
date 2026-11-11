# Task 3-d — Production Stages Config + Audit Trail + Notifications Page

**Agent:** 3-d (parallel with 3-a task UI, 3-b customers/orders/users, 3-c performance/incentives/reports)
**Status:** COMPLETE — all 3 API routes + 3 views implemented, curl-tested, browser-tested, lint clean.

## Files created / owned (all mine, no other files touched except one disclosed shared fix below)

### API routes
- `src/app/api/atelier/stages/route.ts` — GET (list ordered by sortOrder asc, name asc → `{ stages: StageConfig[] }`), POST (creates stage; slugified unique `code` with `-2`/`-3` suffix on collision; `appliesTo` normalized: empty or all 6 categories → `"ALL"`, else comma-joined; audits `STAGE_CREATED` with `{ name, appliesTo }`; returns 201 `{ stage }`). Exports `normalizeAppliesTo()` + `slugify()` reused by the PATCH route.
- `src/app/api/atelier/stages/[id]/route.ts` — PATCH (partial update of name/weight/sortOrder/isActive/appliesTo; 404 on unknown id; audits `STAGE_UPDATED` with `{ name, changed: {...} }` containing only effectively-changed fields; stages are NEVER deleted — deactivation only).
- `src/app/api/atelier/audit/route.ts` — GET with `page/pageSize(default 30, max 100)/action/entityType/from/to`; requires `audit.view` (MANAGEMENT ONLY — Admin/Staff get 403 via requirePermission since only MANAGEMENT holds `audit.view`); returns `{ entries, total, page, pageSize, actions }` where `actions` = unfiltered distinct action list for the filter dropdown; date-only `to` values are inclusive through end-of-day.

### Views (replaced placeholders)
- `src/components/atelier/views/stages-view.tsx` — PageHeader + amber info Alert, table (sort/name/mono code/Applies-To badges incl. "All categories"/weight/Active Switch with optimistic toggle + rollback + toast/ Edit), shared Add/Edit Dialog (name, 6 category checkboxes + hint, weight step 0.1 default 1, sortOrder), NO delete anywhere. Deactivated rows dimmed.
- `src/components/atelier/views/audit-view.tsx` — PageHeader, filters Card (action Select from API `actions`, entityType Select, from/to date inputs, Reset), table (formatDateTime, userName ?? "System", mono action badge title-cased with underscores retained e.g. `Task_Assigned`, `TYPE · id[:8]`, truncated pretty-JSON details with full-JSON title tooltip), pagination (pageSize 30, Prev/Next), empty state "No audit entries match these filters."
- `src/components/atelier/views/notifications-view.tsx` — fetches existing `GET /api/atelier/notifications?limit=100`, All/Unread Tabs (client-side), "Mark all as read" (`PATCH { all: true }`, disabled at 0 unread), card-row list with amber unread dot + `bg-amber-50/60` unread rows, type badge, timeAgo; click → mark read (`PATCH { id }`) + `openTask(taskId)` when present; store `setUnreadCount` kept in sync; `max-h-[60vh] overflow-y-auto`; EmptyState "You're all caught up."

## SHARED FILE I MODIFIED (disclosure — one line, blocking bug fix)
- `src/components/atelier/views/dashboard-view.tsx` line 157: changed `icon={UserQuestion}` → `icon={HelpCircle}`. The scaffold file used `UserQuestion` which is (a) not imported and (b) does not exist in lucide-react 0.525.0, crashing the ENTIRE app with `ReferenceError: UserQuestion is not defined` for every MANAGEMENT/ADMIN login (Staff branch unaffected). This blocked browser access to all supervisor-only views incl. my Stages/Audit views and every other agent's MGMT/ADMIN testing. Fix is the already-imported icon used by the same StatCard. If the orchestrator prefers a different resolution, the alternative is importing `CircleHelp`/`UserRoundQuestion`-class icon — but `HelpCircle` (already imported, currently unused) is the zero-risk fix.

## Verification results
- curl (management@atelier.com): GET /api/atelier/stages 200; POST "Cutting" → code `cutting-2` (collision suffix works); POST with all 6 categories → `appliesTo: "ALL"`; POST weight 99 → 400 validation; PATCH rename+rescope → 200; PATCH deactivate → 200; PATCH unknown id → 404; audit filters (action/entityType/from/to) all work; invalid `from` → 400.
- curl (admin@atelier.com): GET /api/atelier/audit → **403** (Management only) ✓; GET /api/atelier/stages → 200 ✓.
- curl (staff hamidou@atelier.com): GET /api/atelier/stages → 403 ✓.
- Browser (agent-browser): MGMT dashboard renders after shared fix; Stages view (table, add dialog create "Button Attach Test" weight 1.5 sort 25, switch deactivate with toast, edit dialog rename to "Button Attach Test v2", code stable); Audit view (filter dropdowns incl. dynamic actions list, Stage_Updated filter → 5 entries, Reset, Next → page 2 of 3, JSON title tooltip); Notifications view as staff (All/Unread tabs, click → badge 6→5 + task opened, mark all read → disabled + "You're all caught up."); Admin nav hides Audit History, shows Production Stages (loads fine).
- `bun run lint`: **0 errors, 0 warnings** across the whole project (only remaining rule fix was mine: `react-hooks/set-state-in-effect` in audit-view — resolved by using the dashboard-view `.then()` + cancelled-flag fetch pattern).

## Test data left behind (intentional, non-polluting)
Three INACTIVE stages (safe — never added to new tasks): "Cutting Test Stage" (`cutting-2`), "Quality Check" (`quality-check-2`), "Button Attach Test v2" (`button-attach-test`). Deletion is by design impossible; management can reactivate them from the UI if desired.

## Notes for other agents / orchestrator
- The dev server died mid-session (port 3000 refused connections, no next process); I restarted it with the project's own `bun run dev` (same command the system uses, logs still tee to dev.log). If your session sees connection-refused, check `ps aux | grep next`.
- Audit entityType values currently written across modules: USER, STAFF, CUSTOMER, ORDER, TASK, STAGE, INCENTIVE, INCENTIVE_SCHEME, INCENTIVE_RECORD, PERFORMANCE_CONFIG — my AuditView entityType filter covers all of them.
- `useToast` toasts do NOT auto-dismiss quickly (scaffold `TOAST_REMOVE_DELAY = 1000000`); they can cover UI during browser automation — press Escape or click X.
