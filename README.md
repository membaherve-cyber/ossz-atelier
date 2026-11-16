# OSSZ ATELIER — Garment Production Management

A bilingual (English / French) production management application for the OSSZ
Atelier. It tracks customers, orders and garment production tasks through
configurable workshop stages, with role-based access for Management, Admin and
Staff accounts.

Built with **Next.js 16 (App Router) + TypeScript + Tailwind CSS 4 +
shadcn/ui + Prisma (SQLite)**.

## Features

- **Role-based accounts** — Management, Admin and Staff roles with scoped
  permissions (sidebar navigation, actions and API routes are role-guarded).
- **Dashboard** — live KPIs: orders in production, on-time rate, overdue
  tasks, staff workload and recent activity.
- **Customers & Orders** — customer registry with measurements, orders with
  garments, deadlines and financial summary.
- **Production Tasks** — full task lifecycle: assignment, stage progress
  checklists, staff submissions, QC inspection (approve / rework), blocking,
  reopening, cancellation and complete audit history.
- **Configurable Stages** — workshop pipeline stages (cutting, assembly,
  finishing, QC…) editable by Admin.
- **Performance** — monthly staff performance assessments computed from
  completed tasks, on-time rate and QC results, with a configurable scoring
  model.
- **Incentives** — bonus schemes per stage, monthly records, approval flow
  and payout tracking.
- **Audit Trail** — every significant action is logged with user, entity and
  timestamp.
- **Notifications** — in-app notification bell with unread badge.
- **Reports** — production, deadline, workload and incentive reporting.
- **EN / FR i18n** — complete English and French dictionaries with instant
  language toggle, persisted per user.

## Getting Started

### Prerequisites

- [Bun](https://bun.sh) (recommended) or Node.js 20+

### Install & Run

```bash
bun install

# Create the SQLite database and generate the Prisma client
bun run db:push

# Seed demo data (accounts, stages, orders, tasks, history)
bun prisma/seed.ts

# Start the dev server on http://localhost:3000
bun run dev
```

### Environment

Create a `.env` file (see `.env.example` spirit — a single variable is needed):

```env
DATABASE_URL=file:/absolute/path/to/project/db/custom.db
```

## Demo Accounts

All demo accounts share the password **`Atelier@2026`**.

| Role      | Email                    |
| --------- | ------------------------ |
| Management| `management@atelier.com` |
| Admin     | `admin@atelier.com`      |
| Staff     | `hamidou@atelier.com`    |
| Staff     | `lawal@atelier.com`      |
| Staff     | `ngozi@atelier.com`      |

## Project Structure

```
prisma/                  Database schema + seed
db/                      SQLite database file (created at runtime, not committed)
src/app/                 Next.js App Router entry (single-page app on /)
src/app/api/             REST API routes (auth, atelier domain, production)
src/components/atelier/  Atelier app shell, views and bilingual i18n dictionaries
src/components/ui/       shadcn/ui component library
src/lib/                 Auth, permissions, validation, formatting, domain logic
public/                  Static assets (OSSZ logo, hero imagery)
```

## Tech Notes

- **Auth**: cookie-based sessions with scrypt password hashing (`src/lib/auth.ts`).
- **Permissions**: central role-permission matrix (`src/lib/permissions.ts`).
- **i18n**: typed dictionary modules under `src/components/atelier/i18n/dict/`
  with `en` / `fr` keys for every view.
- **Database**: Prisma + SQLite. The schema lives in `prisma/schema.prisma`;
  run `bun run db:push` after schema changes.

## License

Proprietary — OSSZ Atelier internal tool.
