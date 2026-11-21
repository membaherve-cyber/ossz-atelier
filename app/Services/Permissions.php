<?php

namespace App\Services;

use App\Models\User;
use App\Support\Atelier;

/**
 * OSSZ ATELIER — role & permission matrix.
 *
 * Ported from src/lib/permissions.ts, extended three times:
 *   - the accounting enhancement added the accounting.* keys;
 *   - per management decision the ADMIN role was then granted FULL access:
 *     ADMIN now mirrors MANAGEMENT and holds every permission — including
 *     the confidential accounting module (payments, expenses, payroll,
 *     advances, suppliers, reports, approvals), the daily business-health
 *     report settings, the audit trail, incentive approvals/payments,
 *     performance configuration/assessment and Management-account
 *     management (users.manage.all).
 *   - the Finance & Comptabilité module v1.3 added the operational roles
 *     of spec §12 and their permission sets:
 *       MANAGER    — responsable de production: sees production/orders,
 *                    creates and assigns tasks, validates material
 *                    declarations (§5.8), no confidential accounting;
 *       COMPTABLE  — accountant: the full accounting suite + treasury +
 *                    budgets + manual journal entries (§6/§12), no user
 *                    management, no production validation;
 *       MAGASINIER — storekeeper: materials master + stock + issues to
 *                    employees with order/garment assignment (§12), no
 *                    financial records.
 *   - STAFF holds no global permissions except materials.declare (declare
 *     own consumptions, §5.6 method 2) — their access is strictly scoped
 *     to their own assigned tasks and issues inside the controllers.
 *
 * Consumed by App\Http\Middleware\EnsurePermission (alias `perm`) and by
 * the views to build the navigation per role.
 */
final class Permissions
{
    /**
     * All permission keys (kept in sync with Atelier::PERMISSIONS).
     */
    public const PERMISSIONS = Atelier::PERMISSIONS;

    /**
     * MANAGEMENT — full operational and supervisory access.
     */
    public const MANAGEMENT = self::PERMISSIONS;

    /**
     * ADMIN — FULL access (management decision): identical to the
     * MANAGEMENT list, so the admin account can use every feature of the
     * application. The original operational ADMIN_PERMISSIONS subset was
     * retired when management requested admin parity.
     */
    public const ADMIN = self::PERMISSIONS;

    /**
     * MANAGER — responsable de production (spec v1.3 §12): full
     * production supervision (view all, create tasks, assign, inspect,
     * manage details), order/customer visibility, performance and
     * production reports, material traceability view and — the core of
     * §5.8 — validation of employee material declarations.
     */
    public const MANAGER = [
        'customers.view',
        'orders.view',
        'production.view.all',
        'production.create.tasks',
        'production.assign',
        'production.inspect',
        'production.manage.details',
        'production.reports',
        'performance.view',
        'materials.view',
        'materials.issue',
        'materials.declare',
        'materials.validate',
        'treasury.view',
    ];

    /**
     * COMPTABLE — accountant (spec v1.3 §12): the confidential accounting
     * suite (payments, expenses, payroll, advances, suppliers, reports,
     * approvals), treasury management (§4.4), manual journal entries
     * (§6) and budgets (§8). No user management, no production actions.
     */
    public const COMPTABLE = [
        'accounting.view',
        'accounting.payments',
        'accounting.expenses',
        'accounting.payroll',
        'accounting.advances',
        'accounting.suppliers',
        'accounting.reports',
        'accounting.approve',
        'accounting.journal',
        'treasury.view',
        'treasury.manage',
        'materials.view',
        'budgets.view',
        'budgets.manage',
        'customers.view',
        'orders.view',
    ];

    /**
     * MAGASINIER — storekeeper (spec v1.3 §12): materials master and
     * stock, issues to employees with the mandatory order / garment /
     * employee assignment (§5.5), restitutions. Read-only visibility of
     * customers/orders to pick the right order when issuing.
     */
    public const MAGASINIER = [
        'customers.view',
        'orders.view',
        'production.view.all',
        'materials.view',
        'materials.manage',
        'materials.issue',
        'materials.declare',
    ];

    /**
     * STAFF — own-task scoping only, plus the right to declare material
     * consumption/restitution for their own issues (§5.6 method 2,
     * controller scopes to issues where they are the beneficiary).
     */
    public const STAFF = [
        'materials.declare',
    ];

    /**
     * Full role => permissions matrix (MATRIX in permissions.ts).
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        return [
            'MANAGEMENT' => self::MANAGEMENT,
            'ADMIN' => self::ADMIN,
            'MANAGER' => self::MANAGER,
            'COMPTABLE' => self::COMPTABLE,
            'MAGASINIER' => self::MAGASINIER,
            'STAFF' => self::STAFF,
        ];
    }

    /**
     * Whether the given user's role grants the permission
     * (= hasPermission() in permissions.ts).
     */
    public static function allows(User $user, string $permission): bool
    {
        return in_array($permission, self::for($user->role), true);
    }

    /**
     * Permissions granted to a role (= permissionsFor()); unknown roles
     * get nothing.
     *
     * @return list<string>
     */
    public static function for(string $role): array
    {
        return self::matrix()[$role] ?? [];
    }

    /**
     * Roles whose accounts can be assigned production work
     * (= ASSIGNABLE_ROLES in permissions.ts). The three operational
     * roles can all receive tasks; COMPTABLE is administrative only.
     *
     * @return list<string>
     */
    public static function assignableRoles(): array
    {
        return ['MANAGEMENT', 'ADMIN', 'MANAGER', 'MAGASINIER', 'STAFF'];
    }
}
