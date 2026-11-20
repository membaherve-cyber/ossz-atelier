<?php

namespace App\Services;

use App\Models\User;
use App\Support\Atelier;

/**
 * OSSZ ATELIER — role & permission matrix.
 *
 * Ported from src/lib/permissions.ts, extended twice:
 *   - the accounting enhancement added the accounting.* keys;
 *   - per management decision the ADMIN role was then granted FULL access:
 *     ADMIN now mirrors MANAGEMENT and holds every permission — including
 *     the confidential accounting module (payments, expenses, payroll,
 *     advances, suppliers, reports, approvals), the daily business-health
 *     report settings, the audit trail, incentive approvals/payments,
 *     performance configuration/assessment and Management-account
 *     management (users.manage.all).
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
     * ADMIN — FULL access (management decision): identical to the
     * MANAGEMENT list, so the admin account can use every feature of the
     * application. The original operational ADMIN_PERMISSIONS subset was
     * retired when management requested admin parity.
     */
    public const ADMIN = self::PERMISSIONS;

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
