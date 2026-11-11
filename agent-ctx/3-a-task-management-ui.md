# Task 3-a — Task Management UI module (TasksView / TaskCreateView / TaskDetailView)

Agent: task-management-ui (3-a)
Status: COMPLETE — all three views written, `bun run lint` clean (whole project), `tsc --noEmit` clean for my files.

## Files written (owned by 3-a, placeholders fully replaced)

1. `src/components/atelier/views/tasks-view.tsx` — production board (supervisors) / My Tasks (staff)
   - Debounced search (~300ms), staff filter (supervisors only, incl. "All staff"/"Unassigned" + activeTaskCount hints), status filter, priority filter (supervisor only), overdue-only switch, sort (updated/deadline/progress/created), deadline from/to date range (supervisor only).
   - Staff sessions see only search + status + overdue + sort (API force-scopes their list anyway).
   - shadcn Table, `min-w-[900px]`, skeleton loading rows, EmptyState (context-aware incl. "Clear filters"), toast + inline error handling (stale-results Alert + retry).
   - Pagination: Previous/Next + "Page X of Y" + total, pageSize 20. Rows are keyboard accessible (tabIndex + Enter/Space) and open via `openTask(task.id)`.
   - Supervisor-only "Create Tasks" button → `navigate("task-create")`.

2. `src/components/atelier/views/task-create-view.tsx` — order → garment-piece tasks
   - Supervisor-only guard (staff → EmptyState "Not authorized").
   - Fetches `GET /api/atelier/orders` (graceful error + retry) and `GET /api/atelier/users?role=STAFF` (silent fallback to "Leave unassigned").
   - Order Select (`orderNumber — customerName (description)`) with preselect from `viewParams.orderId`; order info panel (customer, phone, order date, expected delivery, priority badge, piece count, instructions, existing taskCount/avg progress warning about duplicate pieces).
   - Dynamic piece rows (add/remove, min 1): garmentPiece, category (default FULL), staff (optional), priority (defaults to order's), deadline datetime-local (defaults to order's expectedDeliveryDate), instructions textarea.
   - Client validation: required names, case-insensitive duplicate detection → toast, no submit. Success → toast with count + `navigate("tasks")`; server errors (409 duplicate piece etc.) toasted.
   - "none" sentinel used for the unassigned Select value (Radix forbids empty-string SelectItem values).

3. `src/components/atelier/views/task-detail-view.tsx` — main task screen
   - Fetches `GET /api/atelier/tasks/[id]`; 403/404 → EmptyState with server message; other errors → retry EmptyState; skeleton on first load; silent (non-flashing) refetch after every mutation + toast.
   - Header: code — garmentPiece (font-display), Status/Priority/Category badges, order + customer, assignee, DueDate (overdue red), created date; red Alert (BLOCKED + reason), stone Alert (CANCELLED + reason), amber Alert (CHANGES_REQUESTED).
   - Layout: `grid lg:grid-cols-3` — left (2/3): Progress & Checklist, Comments & Issues; right (1/3): Workflow Actions, Task Details; History full-width below. DOM order chosen so mobile stacks sensibly (progress → actions → comments → details → history).
   - Progress & Checklist: large ProgressCell; manual progress update (number input 0-100 + note + Update) shown when (supervisor OR assignee) AND status in ASSIGNED/ONGOING/CHANGES_REQUESTED, supervisor "correction" hint; checklist stages sorted by sortOrder with StageStateChip, 4-state toggle buttons (Not Started / In Progress / Completed / N/A) with pending-state highlighting, "was X" revert indicator, weight badge (×N) when weight > 1, single optional save comment, one "Save Checklist" button posting `{ updates: [{ stageId, state, comment }] }`; recalculation note. Checklist editable when (supervisor && !terminal) || (assignee && inWork).
   - Actions (all hide when unavailable; server still enforces): ASSIGN/REASSIGN dialog (staff select, current assignee disabled, optional note), UPDATE dialog (deadline/priority/instructions/garmentPiece, only changed fields sent, ISO strings), SUBMIT dialog (optional note; disabled for staff with <100% + "Reach 100% progress first" hint), INSPECT dialog (Approve w/ optional comments + Request Changes w/ required comments ≥3), READY (direct), DELIVER dialog (note), REOPEN dialog (comments required ≥3), BLOCK dialog (reason required ≥3), UNBLOCK dialog (note), CANCEL AlertDialog (destructive, reason required ≥3, preventDefault to keep open on validation failure).
   - Comments & Issues: list (desc) with red left border + AlertTriangle for issues, ScrollArea max-h-96; add form with "Add Note" + "Report Issue" (destructive-outline) buttons; available to assignee + supervisors when not DELIVERED/CANCELLED.
   - History: merged chronological-desc timeline of assignments/statusChanges/stageUpdates/progressHistory/inspections/comments with per-type icons & colors (UserPlus/ArrowRightLeft/ListChecks/TrendingUp w/ source badge/ShieldCheck decision-colored/MessageSquare), userName + timeAgo with formatDateTime tooltip; Tabs filter All/Progress/Checklist/Status/Inspections; ScrollArea max-h-96.
   - Hooks ordering fixed: stages + history `useMemo` moved above early-return guards (rules of hooks).

## Contracts consumed (verified against route.ts sources)
- `GET/POST /api/atelier/tasks`, `GET/PATCH /api/atelier/tasks/[id]` (all 9 actions), `POST .../stages`, `POST .../progress`, `POST .../comments` — fully implemented & tested live via seeded data (login management@atelier.com worked; task list + detail responses confirmed).
- `GET /api/atelier/users?role=STAFF` and `GET /api/atelier/orders` — NOT yet implemented by agents 3-b at the time of writing (returned HTML 404 fallback). My views handle their absence gracefully (retry state for orders; silent "Leave unassigned" fallback for staff; lazy refetch of staff list each time the assign dialog opens). Code follows the documented contracts.

## Notes / issues for other agents
- PRE-EXISTING TS errors (NOT mine, do not touch per instructions, reporting as requested):
  - `src/app/api/atelier/tasks/[id]/progress/route.ts:76` — `changeTaskStatus(id, task.status, newStatus, ...)`: `newStatus` is typed `string`, parameter expects `TaskStatus` union → TS2345.
  - `src/app/api/atelier/tasks/[id]/route.ts:201` — same pattern in the ASSIGN case.
  Both are runtime-safe (values are valid statuses) but fail `tsc --noEmit`. Suggest `newStatus as TaskStatus` or typing the variable as `TaskStatus`.
- `examples/` and `skills/` folders also have pre-existing TS errors (unrelated scaffolding).
- Shared-file change requests: none. types.ts / store.ts / client.ts / ui-bits.tsx / constants.ts were sufficient as-is.
- Contract quirk noticed (informational): `GET /api/atelier/tasks` applies the `overdue` filter AFTER pagination (rows filtered on the page), so with "Overdue only" the page may show fewer rows than pageSize and `total` reflects the unfiltered count. My UI displays server values as-is.
- Radix Select forbids empty-string item values — anyone building selects should use a sentinel ("all"/"none") like I did.
