# Task 3-b — Customers + Orders + Users module (APIs & views)

Agent: 3-b · Status: COMPLETE · All curl + browser smoke tests passed.

## Files written (owned by 3-b)

API routes:
- `src/app/api/atelier/customers/route.ts` — GET (list + search + orderCount, name asc), POST (create, code via nextCustomerCode, audit CUSTOMER_CREATED)
- `src/app/api/atelier/customers/[id]/route.ts` — PATCH (partial update, 404 guard, audit CUSTOMER_UPDATED with changedFields)
- `src/app/api/atelier/orders/route.ts` — GET (search/priority/from/to filters on orderDate), POST (existing customerId OR inline newCustomer created in the SAME transaction; duplicate orderNumber → 409 `An order with number "X" already exists. Order numbers must be unique.` — pre-check + P2002 race guard; audit ORDER_CREATED)
- `src/app/api/atelier/orders/[id]/route.ts` — GET (order + tasks via serializeTask/taskInclude, createdAt asc), PATCH (all editable fields, 409 on orderNumber clash, audit ORDER_UPDATED)
- `src/app/api/atelier/users/route.ts` — GET (role filter, includeInactive default TRUE, activeTaskCount via groupBy, name asc, NEVER passwordHash), POST (users.manage.staff|all; ADMIN may only create STAFF → 403; MANAGEMENT any role; duplicate email → 409 "An account with this email already exists."; hashPassword; audit USER_CREATED {name,email,role})
- `src/app/api/atelier/users/[id]/route.ts` — PATCH (ADMIN: target must be STAFF + resulting role must be STAFF → 403; self-deactivate → 400; last active MANAGEMENT deactivate/demote → 400; password → hash + separate audit USER_PASSWORD_RESET, no password in details; other changes → audit USER_UPDATED with changedFields)

Views (placeholders fully replaced):
- `src/components/atelier/views/customers-view.tsx` — search (debounced 300ms), table (code/name/phone/email/address/orders/registered + edit action), create/edit dialog, toasts, EmptyState, skeletons
- `src/components/atelier/views/orders-view.tsx` — filters (search/priority/from/to + clear), table with PriorityBadge/DueDate/ProgressCell(avgProgress) + "n/m tasks done", row click → detail dialog (full fields + task list + "Break into Tasks" → navigate("task-create",{orderId}) + Edit), register dialog with customer mode radio (existing Select / new inline fields), edit dialog (customer read-only), 409 duplicate error shown INSIDE form (red) + destructive toast
- `src/components/atelier/views/users-view.tsx` — table (user+email, RoleBadge, jobTitle, phone, Active/Inactive badge, active tasks, created), Create Account dialog (password show/hide, role select: 3 roles for MANAGEMENT / STAFF-only for ADMIN, note "Staff accounts can be assigned production tasks."), row DropdownMenu (Edit / Reset Password AlertDialog / Deactivate·Reactivate AlertDialog), ADMIN sees actions only on STAFF rows, own-row deactivate hidden (client hint; server enforces too)

## Implementation notes / decisions

- `serializeOrder` duplicated in both order route files (route files may only export handlers in Next 16 — no shared exports).
- Orders `from`/`to` filter on `orderDate`; date-only `to` treated as inclusive end-of-day (T23:59:59.999Z).
- Order stats exclude CANCELLED tasks; completed = APPROVED|READY_FOR_PICKUP|DELIVERED; avgProgress rounded int, 0 when no tasks.
- POST /orders: if BOTH customerId and newCustomer are sent, newCustomer wins (UI only ever sends one).
- Customer PATCH: sending `email: ""` clears the email; POST treats "" as absent.
- Zod v4 style (`z.email()` with preprocess for optional/nullable empty strings).
- Next 16: `params` awaited (`const { id } = await params`) in every [id] route.
- Warm amber/bronze theme respected (no blue/indigo); mobile-first with overflow-x-auto + min-w tables; dialogs max-w with internal scroll.

## Verified (curl + agent-browser)

- Staff (hamidou) gets 403 on every route (GET/POST customers, orders, users) — verified.
- Duplicate orderNumber → exact 409 message (curl + inside form in browser).
- Duplicate user email → 409 "An account with this email already exists."
- ADMIN creating/promoting non-STAFF → 403; ADMIN editing ADMIN → 403.
- Self-deactivate → 400; last active MANAGEMENT demote/deactivate → 400 (2nd manager case allowed, tested then cleaned).
- Order with inline newCustomer creates both in one transaction.
- UI flows tested in headless browser (management session): customers create/toast, orders detail dialog + tasks + 409 inline error, new-customer order registration, users create dialog (3 role options), row dropdown (reset pw dialog with min-8 gating, deactivate confirm). No page errors.
- All test data removed afterwards (customers/orders/users back to seed: 2 customers, 4 orders, 5 users).

## Lint / types

- `bunx eslint` on my 9 files: 0 problems. `bunx tsc --noEmit`: 0 errors in my files.
- Project-wide `bun run lint` still fails ONLY in `src/components/atelier/views/task-detail-view.tsx` (agent 3-a's file: conditional useMemo + undefined Alert/AlertTitle/AlertDescription) — not mine.

## For other agents / coordinator

- OBSERVATION (not my file): `src/components/atelier/views/dashboard-view.tsx` line ~157 imports `UserQuestion` from lucide-react — THIS ICON DOES NOT EXIST in the installed lucide-react (verified via node require). It renders for MANAGEMENT/ADMIN sessions and will throw at render time ("Element type is invalid"). Suggest replacing with an existing icon (e.g. `CircleHelp` or `UserRoundSearch`). I did NOT touch the file.
- Task-create view (agent 3-a) receives `viewParams.orderId` from my "Break into Tasks" button via `navigate("task-create", { orderId })`.
- Audit actions produced by this module: CUSTOMER_CREATED, CUSTOMER_UPDATED, ORDER_CREATED, ORDER_UPDATED, USER_CREATED, USER_UPDATED, USER_PASSWORD_RESET (details never contain passwords).
