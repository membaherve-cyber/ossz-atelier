<?php

namespace App\Support;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * OSSZ ATELIER — shared domain constants & helpers.
 *
 * Ported 1:1 from the Next.js reference implementation
 * (src/lib/atelier/constants.ts). Values are stored as plain strings in the
 * database (no DB enum columns) and validated in the app layer against
 * these lists. Labels for the UI live in lang/{en,fr}/labels.php.
 */
final class Atelier
{
    /**
     * System roles (src/lib/permissions.ts ROLES).
     */
    public const ROLES = ['MANAGEMENT', 'ADMIN', 'STAFF'];

    /**
     * Every permission key (verbatim from src/lib/permissions.ts PERMISSIONS).
     * Used by App\Services\Permissions for the role matrix.
     */
    public const PERMISSIONS = [
        'customers.view',
        'customers.manage',
        'orders.view',
        'orders.manage',
        'users.view',
        'users.manage.staff',
        'users.manage.all',
        'production.view.all',
        'production.create.tasks',
        'production.assign',
        'production.inspect',
        'production.cancel',
        'production.manage.details',
        'production.stages.configure',
        'production.reports',
        'performance.view',
        'performance.configure',
        'performance.assess',
        'incentives.view',
        'incentives.prepare',
        'incentives.manage.schemes',
        'incentives.approve',
        'incentives.payment',
        'audit.view',
        // Accounting (enhancement spec §4.1/§15) — MANAGEMENT holds all of
        // them via the matrix; ADMIN holds none unless Management extends
        // the matrix; STAFF never sees confidential financial records.
        'accounting.view',
        'accounting.payments',
        'accounting.expenses',
        'accounting.payroll',
        'accounting.advances',
        'accounting.suppliers',
        'accounting.reports',
        'accounting.approve',
    ];

    /**
     * Task status workflow (10 statuses).
     */
    public const TASK_STATUSES = [
        'UNASSIGNED',
        'ASSIGNED',
        'ONGOING',
        'AWAITING_INSPECTION',
        'CHANGES_REQUESTED',
        'APPROVED',
        'READY_FOR_PICKUP',
        'DELIVERED',
        'BLOCKED',
        'CANCELLED',
    ];

    /**
     * Task priorities.
     */
    public const PRIORITIES = ['LOW', 'NORMAL', 'HIGH', 'URGENT'];

    /**
     * Task categories (one garment piece / activity per task).
     */
    public const TASK_CATEGORIES = ['FULL', 'CUTTING', 'SEWING', 'FINISHING', 'IRONING', 'OTHER'];

    /**
     * Checklist stage states (each stage of a task is in exactly one).
     */
    public const STAGE_STATES = ['NOT_STARTED', 'IN_PROGRESS', 'COMPLETED', 'NOT_APPLICABLE'];

    /**
     * Quality-inspection decisions.
     */
    public const INSPECTION_DECISIONS = ['APPROVED', 'CHANGES_REQUESTED', 'REOPENED'];

    /**
     * Internal notification types (titles in labels.notifTitle.*).
     */
    public const NOTIFICATION_TYPES = [
        'TASK_ASSIGNED',
        'TASK_REASSIGNED',
        'DEADLINE_CHANGED',
        'NEW_INSTRUCTIONS',
        'CORRECTIONS_REQUESTED',
        'BLOCK_RESOLVED',
        'TASK_APPROVED',
        'TASK_REOPENED',
        'SUBMITTED_FOR_INSPECTION',
        'TASK_OVERDUE',
        'ISSUE_REPORTED',
        'TASK_BLOCKED',
        'READY_FOR_PICKUP',
        'TASK_DELIVERED',
        'TASK_CANCELLED',
        // Accounting + workflow enhancement
        'INCENTIVE_APPROVED',
        'PAYROLL_PAID',
        'ADVANCE_DISBURSED',
    ];

    /**
     * Incentive scheme period types.
     */
    public const INCENTIVE_PERIOD_TYPES = ['MONTHLY', 'QUARTERLY', 'ANNUAL', 'QUALITY', 'ON_TIME', 'CUSTOM'];

    /**
     * Incentive record statuses.
     */
    public const INCENTIVE_STATUSES = ['PENDING', 'APPROVED', 'REJECTED'];

    /**
     * Incentive payment statuses.
     */
    public const PAYMENT_STATUSES = ['UNPAID', 'PAID'];

    /**
     * Order statuses (enhancement spec §3.2): a draft order keeps its tasks
     * out of staff notifications until Management finalizes it.
     */
    public const ORDER_STATUSES = ['DRAFT', 'REGISTERED'];

    /**
     * Customer payment methods (enhancement spec §6).
     */
    public const PAYMENT_METHODS = ['CASH', 'MTN_MOMO', 'ORANGE_MONEY', 'BANK_TRANSFER', 'OTHER'];

    /**
     * Expense approval statuses (enhancement spec §7.4).
     */
    public const EXPENSE_STATUSES = ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'PAID'];

    /**
     * Expense category groups (enhancement spec §7.1 A–F).
     */
    public const EXPENSE_GROUPS = ['MATERIALS', 'TRANSPORT', 'OPERATIONS', 'SALARIES', 'ADMIN', 'OTHER'];

    /**
     * Staff remuneration types (enhancement spec §8.1).
     */
    public const REMUNERATION_TYPES = ['MONTHLY', 'DAILY', 'WEEKLY', 'TASK_BASED', 'COMMISSION', 'OTHER'];

    /**
     * Payroll period statuses (enhancement spec §8.2).
     */
    public const PAYROLL_STATUSES = ['DRAFT', 'PENDING', 'APPROVED', 'PARTIALLY_PAID', 'PAID'];

    /**
     * Payroll item payment statuses (enhancement spec §8.2).
     */
    public const PAYROLL_ITEM_STATUSES = ['PENDING', 'PAID'];

    /**
     * Staff advance statuses (enhancement spec §9).
     */
    public const ADVANCE_STATUSES = [
        'PENDING_APPROVAL',
        'APPROVED',
        'DISBURSED',
        'PARTIALLY_RECOVERED',
        'FULLY_RECOVERED',
        'CANCELLED',
    ];

    /**
     * Supplier purchase payment statuses (enhancement spec §10).
     */
    public const PURCHASE_PAYMENT_STATUSES = ['UNPAID', 'PARTIAL', 'PAID'];

    /**
     * System sources of auto-booked expenses (enhancement spec §13/§14 —
     * one ledger row per source, never duplicated).
     */
    public const EXPENSE_SOURCES = ['INCENTIVE_RECORD', 'PURCHASE'];

    /**
     * Audit action codes — the exact keys of audit.action.* in
     * src/components/atelier/i18n/dict/audit.ts (labels in lang audit.php)
     * plus the accounting enhancement additions. Written by
     * App\Services\Audit::log().
     */
    public const AUDIT_ACTIONS = [
        'LOGIN_SUCCESS',
        'LOGIN_FAILED',
        'LOGIN_BLOCKED_INACTIVE',
        'LOGOUT',
        'USER_CREATED',
        'USER_UPDATED',
        'USER_PASSWORD_RESET',
        'STAFF_ASSESSED',
        'CUSTOMER_CREATED',
        'CUSTOMER_UPDATED',
        'ORDER_CREATED',
        'ORDER_UPDATED',
        'TASKS_CREATED',
        'TASK_ASSIGNED',
        'TASK_REASSIGNED',
        'TASK_UPDATED',
        'TASK_CANCELLED',
        'TASK_BLOCKED',
        'BLOCK_RESOLVED',
        'SUBMITTED_FOR_INSPECTION',
        'INSPECTION_COMPLETED',
        'TASK_READY_FOR_PICKUP',
        'TASK_DELIVERED',
        'TASK_STATUS_CHANGED',
        'STAGES_UPDATED',
        'PROGRESS_UPDATED',
        'COMMENT_ADDED',
        'ISSUE_REPORTED',
        'STAGE_CREATED',
        'STAGE_UPDATED',
        'PERFORMANCE_CONFIG_UPDATED',
        'INCENTIVE_SCHEME_CREATED',
        'INCENTIVE_SCHEME_UPDATED',
        'INCENTIVE_RECOMMENDATIONS_GENERATED',
        'INCENTIVE_APPROVED',
        'INCENTIVE_REJECTED',
        'INCENTIVE_PAYMENT_RECORDED',
        // Accounting + unified order workflow (enhancement)
        'ORDER_FINALIZED',
        'ORDER_VALUE_UPDATED',
        'PAYMENT_RECORDED',
        'PAYMENT_UPDATED',
        'PAYMENT_DELETED',
        'EXPENSE_CATEGORY_CREATED',
        'EXPENSE_CATEGORY_UPDATED',
        'EXPENSE_RECORDED',
        'EXPENSE_UPDATED',
        'EXPENSE_REVIEWED',
        'EXPENSE_DELETED',
        'SALARY_PROFILE_CREATED',
        'SALARY_PROFILE_UPDATED',
        'PAYROLL_CREATED',
        'PAYROLL_UPDATED',
        'PAYROLL_APPROVED',
        'PAYROLL_ITEM_PAID',
        'ADVANCE_RECORDED',
        'ADVANCE_REVIEWED',
        'ADVANCE_DISBURSED',
        'ADVANCE_CANCELLED',
        'SUPPLIER_CREATED',
        'SUPPLIER_UPDATED',
        'SUPPLIER_DELETED',
        'PURCHASE_RECORDED',
        'PURCHASE_UPDATED',
        'PURCHASE_DELETED',
        'LABOUR_ALLOCATED',
    ];

    /**
     * ProductionStage.appliesTo value meaning "every task category".
     */
    public const APPLIES_TO_ALL = 'ALL';

    /**
     * Progress-history sources (TaskProgressHistory.source column).
     */
    public const PROGRESS_SOURCES = ['MANUAL', 'STAGE', 'INSPECTION'];

    /**
     * Assignment history types (TaskAssignment.type column).
     */
    public const ASSIGNMENT_TYPES = ['ASSIGN', 'REASSIGN', 'UNASSIGN'];

    // ------------------------------------------------------------------
    // Helpers (ported from constants.ts)
    // ------------------------------------------------------------------

    /**
     * Statuses in which a task is considered finished (no further actions).
     * (= TERMINAL_STATUSES / OVERDUE_EXEMPT_STATUSES in constants.ts.)
     *
     * @return list<string>
     */
    public static function terminalStatuses(): array
    {
        return ['DELIVERED', 'CANCELLED'];
    }

    /**
     * Statuses in which work still needs to happen or the order is not
     * closed. (= ACTIVE_STATUSES in constants.ts.)
     *
     * @return list<string>
     */
    public static function activeStatuses(): array
    {
        return [
            'ASSIGNED',
            'ONGOING',
            'AWAITING_INSPECTION',
            'CHANGES_REQUESTED',
            'APPROVED',
            'READY_FOR_PICKUP',
            'BLOCKED',
        ];
    }

    /**
     * A task is overdue when its deadline has passed and it is not
     * delivered or cancelled (ported from taskIsOverdue()).
     *
     * Accepts the deadline as a string (e.g. "2026-02-01 12:00:00") or any
     * DateTimeInterface (Eloquent models expose Carbon instances).
     */
    public static function taskIsOverdue(string|DateTimeInterface|null $deadline, string $status, ?DateTimeInterface $at = null): bool
    {
        if ($deadline === null || in_array($status, self::terminalStatuses(), true)) {
            return false;
        }

        try {
            $deadlineAt = $deadline instanceof DateTimeInterface
                ? $deadline
                : new DateTimeImmutable($deadline);

            return $deadlineAt->getTimestamp() < ($at ?? new DateTimeImmutable())->getTimestamp();
        } catch (\Exception) {
            // Unparseable deadline string — treat as not overdue.
            return false;
        }
    }

    /**
     * Whether a production stage (by its appliesTo value) applies to a task
     * category. "ALL" applies everywhere; otherwise appliesTo is a
     * comma-separated list of categories (e.g. "FULL,CUTTING").
     * (Ported from stageAppliesTo() in constants.ts.)
     */
    public static function stageAppliesTo(string $appliesTo, string $category): bool
    {
        if ($appliesTo === self::APPLIES_TO_ALL) {
            return true;
        }

        $categories = array_values(array_filter(
            array_map('trim', explode(',', $appliesTo)),
            static fn (string $c): bool => $c !== ''
        ));

        return in_array($category, $categories, true);
    }
}
