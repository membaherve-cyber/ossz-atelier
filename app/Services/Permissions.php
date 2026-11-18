<?php

namespace App\Services;

use App\Models\User;
use App\Support\Atelier;

/**
 * OSSZ ATELIER — role & permission matrix.
 *
 * Ported VERBATIM from src/lib/permissions.ts, extended by the accounting
 * enhancement:
 *   - MANAGEMENT holds every permission (sole final approver of
 *     incentives, audit visibility, Management-account management, and
 *     the full confidential accounting module — payments, expenses,
 *     payroll, advances, suppliers, reports and approvals).
 *   - ADMIN holds the operational ADMIN_PERMISSIONS list (no
 *     performance.configure / performance.assess, no users.manage.all, no
 *     incentives.manage.schemes / approve / payment, no audit.view, and
 *     NO accounting permissions — accounting is confidential and
 *     Management-only until Management explicitly extends the matrix).
 *   - STAFF holds no global permissions — their access is strictly scoped
 *     to their own assigned tasks inside the controllers (never to
 *     financial records or other staff salaries).
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
     * ADMIN — operational management (verbatim ADMIN_PERMISSIONS list from
     * src/lib/permissions.ts).
     */
    public const ADMIN = [
        'customers.view',
        'customers.manage',
        'orders.view',
        'orders.manage',
        'users.view',
        'users.manage.staff',
        'production.view.all',
        'production.create.tasks',
        'production.assign',
        'production.inspect',
        'production.cancel',
        'production.manage.details',
        'production.stages.configure',
        'production.reports',
        'performance.view',
        'incentives.view',
        'incentives.prepare',
    ];

    /**
     * STAFF — no global permissions (own-task scoping only).
     */
    public const STAFF = [];

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
     * (= ASSIGNABLE_ROLES in permissions.ts).
     *
     * @return list<string>
     */
    public static function assignableRoles(): array
    {
        return ['MANAGEMENT', 'ADMIN', 'STAFF'];
    }
}
