# OSSZ ATELIER — Production Management System

Standalone garment production management for **OSSZ Collections** (Douala, Cameroon): manual order registration, staff task allocation, checklist tracking, quality control, performance scoring and incentives — with a full English / French interface.

**Live deployment:** [atelier.osszcollection.com](https://atelier.osszcollection.com) — independent system, own database, own accounts (separate from the OSSZ Collections e-commerce site).

Built with **Laravel 11** (PHP 8.2+), Blade + Alpine.js, MySQL.

---

## Features

### Orders & Customers
- Customer registry with auto-generated codes (`CUST-0001`)
- Manual order registration (garment category, piece count, priority, expected delivery)
- Order production overview: per-order task progress, average completion, "break into tasks" flow

### Production Tasks (one task = one garment piece)
- Task board with search, staff / status / priority filters, overdue-only toggle, deadline range and sorting
- Task detail: progress tracking, weighted production checklist (cutting, sewing, fitting, finishing…), comments & issue reporting, full history timeline
- Workflow: ASSIGN → ONGOING → AWAITING_INSPECTION → APPROVED → READY_FOR_PICKUP → DELIVERED, with corrections, blocking, reopening and cancellation
- Quality inspections with approve / request-changes / reopen decisions
- Automatic notifications to assignees and supervisors at every step

### Staff Performance & Incentives
- Weighted scoring engine (productivity, timeliness, quality, reliability, management assessment — weights configurable, must total 100)
- Monthly / quarterly / annual reporting periods
- Incentive schemes with period-based generation, management approval workflow and payment recording

### Administration
- Users & roles: MANAGEMENT, ADMIN (staff accounts only), STAFF
- Production stage configuration (weights, applicable garment categories, ordering)
- Full audit trail of every action (who, what, when, details)
- Bilingual UI (English / Français) switchable on every page

### Daily Email Report (Management only)
- Automated daily business-health e-mail: today's orders, completed pieces,
  payments in, expenses out, production snapshot (overdue, blocked, on-time
  rate), month-to-date financials, staff workload and an attention list
- Distribution list managed in-app (Administration → Daily Report): add,
  remove, activate/deactivate recipient addresses — `info@osszcollection.com`
  active by default
- Configurable send time + business timezone, section toggles, reply-to,
  manual "Send Now" and "Send Test to My Inbox", delivery history log
- Ships as `atelier:daily-report` artisan command + Laravel scheduler
  (sendmail or SMTP transport — see DEPLOYMENT.md §6/§7)

---

## Requirements

- PHP 8.2+ (extensions: mbstring, openssl, pdo, pdo_mysql, tokenizer, xml, ctype, json)
- MySQL 5.7+ / MariaDB (SQLite works for local development)
- Composer 2

## Installation

```bash
git clone <repository-url> ossz-atelier
cd ossz-atelier
composer install --no-dev --optimize-autoloader

cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ossz_atelier
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Create the tables and seed the demo dataset:

```bash
php artisan migrate
php artisan db:seed
```

Point the web server document root at `public/` (see [DEPLOYMENT.md](DEPLOYMENT.md) for shared-hosting notes).

## Demo accounts

Seeded by `php artisan db:seed` (password for every account: `Atelier@2026`):

| Role       | Email                    | Sees                                              |
|------------|--------------------------|---------------------------------------------------|
| MANAGEMENT | management@atelier.com   | Everything: orders, tasks, performance, incentives, audit |
| ADMIN      | admin@atelier.com        | Production, customers, users, stages, reports (no audit) |
| STAFF      | hamidou@atelier.com      | Own dashboard + own tasks only                    |
| STAFF      | lawal@atelier.com        | Own dashboard + own tasks only                    |
| STAFF      | ngozi@atelier.com        | Own dashboard + own tasks only                    |

> Change these passwords before going live (Users & Roles → Reset Password).

## Project structure

```
app/
  Http/Controllers/   One controller per screen + TaskActionController (workflow)
  Http/Middleware/    EnsurePermission (role matrix), SetLocale, guests
  Models/             18 Eloquent models (UUID keys, Prisma-parity columns)
  Services/           Audit, Codes, Notifier, Permissions, ProgressService, PerformanceService
  Support/            Atelier (constants & helpers), Trans ({param} translation filler)
config/               Laravel configuration (env-driven)
database/
  migrations/         18 migrations (users → audit_logs)
  seeders/            Demo dataset (users, stages, customers, orders, tasks, history)
lang/en + lang/fr/    18 file pairs — the complete UI string inventory
resources/views/      Blade + Alpine.js (server-rendered, forms with CSRF)
routes/web.php        The full route table with permission middleware
public/               index.php, .htaccess, css/app.css, js/alpine.min.js, img/
```

## Security notes

- All POST routes are CSRF-protected; all authenticated routes go through the permission matrix (`app/Services/Permissions.php`)
- STAFF accounts are hard-scoped to their own tasks in every controller (never trust the UI hiding)
- Login is rate-limited (5 attempts / minute per email + IP); inactive accounts are blocked and audited
- Audit trail records every mutation with actor, action, entity and details

## License

Proprietary — © OSSZ Collections.
