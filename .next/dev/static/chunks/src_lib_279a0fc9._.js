(globalThis.TURBOPACK || (globalThis.TURBOPACK = [])).push([typeof document === "object" ? document.currentScript : undefined,
"[project]/src/lib/format.ts [app-client] (ecmascript)", ((__turbopack_context__) => {
"use strict";

__turbopack_context__.s([
    "formatDate",
    ()=>formatDate,
    "formatDateTime",
    ()=>formatDateTime,
    "formatFCFA",
    ()=>formatFCFA,
    "initials",
    ()=>initials,
    "safeDate",
    ()=>safeDate,
    "timeAgo",
    ()=>timeAgo
]);
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$format$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__$3c$locals$3e$__ = __turbopack_context__.i("[project]/node_modules/date-fns/format.js [app-client] (ecmascript) <locals>");
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$formatDistanceToNow$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/node_modules/date-fns/formatDistanceToNow.js [app-client] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$isValid$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/node_modules/date-fns/isValid.js [app-client] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$locale$2f$fr$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/node_modules/date-fns/locale/fr.js [app-client] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$core$2e$ts__$5b$app$2d$client$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/components/atelier/i18n/core.ts [app-client] (ecmascript)");
;
;
;
/** Locale matching the active UI language (English by default). */ function dateLocale() {
    return (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$core$2e$ts__$5b$app$2d$client$5d$__$28$ecmascript$29$__["currentLang"])() === "fr" ? __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$locale$2f$fr$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__["fr"] : undefined;
}
function formatFCFA(amount) {
    if (amount == null) return "—";
    const locale = (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$components$2f$atelier$2f$i18n$2f$core$2e$ts__$5b$app$2d$client$5d$__$28$ecmascript$29$__["currentLang"])() === "fr" ? "fr-FR" : "en-US";
    return `${new Intl.NumberFormat(locale).format(amount)} FCFA`;
}
function toDate(value) {
    if (value == null) return null;
    const d = typeof value === "string" ? new Date(value) : value;
    return (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$isValid$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__["isValid"])(d) ? d : null;
}
function formatDate(value) {
    const d = toDate(value);
    return d ? (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$format$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__$3c$locals$3e$__["format"])(d, "dd MMM yyyy", {
        locale: dateLocale()
    }) : "—";
}
function formatDateTime(value) {
    const d = toDate(value);
    return d ? (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$format$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__$3c$locals$3e$__["format"])(d, "dd MMM yyyy, HH:mm", {
        locale: dateLocale()
    }) : "—";
}
function timeAgo(value) {
    const d = toDate(value);
    return d ? (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$formatDistanceToNow$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__["formatDistanceToNow"])(d, {
        addSuffix: true,
        locale: dateLocale()
    }) : "—";
}
function initials(name) {
    if (!name) return "?";
    return name.split(/\s+/).filter(Boolean).slice(0, 2).map((p)=>p[0].toUpperCase()).join("");
}
function safeDate(value) {
    const d = new Date(value);
    return (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$date$2d$fns$2f$isValid$2e$js__$5b$app$2d$client$5d$__$28$ecmascript$29$__["isValid"])(d) ? d : null;
}
if (typeof globalThis.$RefreshHelpers$ === 'object' && globalThis.$RefreshHelpers !== null) {
    __turbopack_context__.k.registerExports(__turbopack_context__.m, globalThis.$RefreshHelpers$);
}
}),
"[project]/src/lib/atelier/constants.ts [app-client] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — shared domain constants & types (safe for client and server).
// Values are stored as strings in the database and validated here.
__turbopack_context__.s([
    "ACTIVE_STATUSES",
    ()=>ACTIVE_STATUSES,
    "APPLIES_TO_ALL",
    ()=>APPLIES_TO_ALL,
    "CATEGORY_LABELS",
    ()=>CATEGORY_LABELS,
    "INCENTIVE_PERIOD_LABELS",
    ()=>INCENTIVE_PERIOD_LABELS,
    "INCENTIVE_PERIOD_TYPES",
    ()=>INCENTIVE_PERIOD_TYPES,
    "INCENTIVE_STATUSES",
    ()=>INCENTIVE_STATUSES,
    "INSPECTION_DECISIONS",
    ()=>INSPECTION_DECISIONS,
    "INSPECTION_DECISION_LABELS",
    ()=>INSPECTION_DECISION_LABELS,
    "NOTIFICATION_TYPES",
    ()=>NOTIFICATION_TYPES,
    "OVERDUE_EXEMPT_STATUSES",
    ()=>OVERDUE_EXEMPT_STATUSES,
    "PAYMENT_STATUSES",
    ()=>PAYMENT_STATUSES,
    "PRIORITIES",
    ()=>PRIORITIES,
    "PRIORITY_LABELS",
    ()=>PRIORITY_LABELS,
    "STAGE_STATES",
    ()=>STAGE_STATES,
    "STAGE_STATE_LABELS",
    ()=>STAGE_STATE_LABELS,
    "STATUS_LABELS",
    ()=>STATUS_LABELS,
    "TASK_CATEGORIES",
    ()=>TASK_CATEGORIES,
    "TASK_STATUSES",
    ()=>TASK_STATUSES,
    "TERMINAL_STATUSES",
    ()=>TERMINAL_STATUSES,
    "isPriority",
    ()=>isPriority,
    "isStageState",
    ()=>isStageState,
    "isTaskCategory",
    ()=>isTaskCategory,
    "isTaskStatus",
    ()=>isTaskStatus,
    "stageAppliesTo",
    ()=>stageAppliesTo,
    "taskIsOverdue",
    ()=>taskIsOverdue
]);
const TASK_STATUSES = [
    "UNASSIGNED",
    "ASSIGNED",
    "ONGOING",
    "AWAITING_INSPECTION",
    "CHANGES_REQUESTED",
    "APPROVED",
    "READY_FOR_PICKUP",
    "DELIVERED",
    "BLOCKED",
    "CANCELLED"
];
const STATUS_LABELS = {
    UNASSIGNED: "Unassigned",
    ASSIGNED: "Assigned",
    ONGOING: "Ongoing",
    AWAITING_INSPECTION: "Awaiting Inspection",
    CHANGES_REQUESTED: "Changes Requested",
    APPROVED: "Approved",
    READY_FOR_PICKUP: "Ready for Pickup",
    DELIVERED: "Delivered/Collected",
    BLOCKED: "Blocked",
    CANCELLED: "Cancelled"
};
const TERMINAL_STATUSES = [
    "DELIVERED",
    "CANCELLED"
];
const ACTIVE_STATUSES = [
    "ASSIGNED",
    "ONGOING",
    "AWAITING_INSPECTION",
    "CHANGES_REQUESTED",
    "APPROVED",
    "READY_FOR_PICKUP",
    "BLOCKED"
];
const OVERDUE_EXEMPT_STATUSES = [
    "DELIVERED",
    "CANCELLED"
];
const PRIORITIES = [
    "LOW",
    "NORMAL",
    "HIGH",
    "URGENT"
];
const PRIORITY_LABELS = {
    LOW: "Low",
    NORMAL: "Normal",
    HIGH: "High",
    URGENT: "Urgent"
};
const STAGE_STATES = [
    "NOT_STARTED",
    "IN_PROGRESS",
    "COMPLETED",
    "NOT_APPLICABLE"
];
const STAGE_STATE_LABELS = {
    NOT_STARTED: "Not Started",
    IN_PROGRESS: "In Progress",
    COMPLETED: "Completed",
    NOT_APPLICABLE: "Not Applicable"
};
const TASK_CATEGORIES = [
    "FULL",
    "CUTTING",
    "SEWING",
    "FINISHING",
    "IRONING",
    "OTHER"
];
const CATEGORY_LABELS = {
    FULL: "Full Production",
    CUTTING: "Cutting Only",
    SEWING: "Sewing/Stitching Only",
    FINISHING: "Finishing",
    IRONING: "Ironing Only",
    OTHER: "Other Activity"
};
const APPLIES_TO_ALL = "ALL";
function stageAppliesTo(appliesTo, category) {
    if (appliesTo === APPLIES_TO_ALL) return true;
    return appliesTo.split(",").map((c)=>c.trim()).filter(Boolean).includes(category);
}
const INSPECTION_DECISIONS = [
    "APPROVED",
    "CHANGES_REQUESTED",
    "REOPENED"
];
const INSPECTION_DECISION_LABELS = {
    APPROVED: "Approved",
    CHANGES_REQUESTED: "Changes Requested",
    REOPENED: "Reopened"
};
const NOTIFICATION_TYPES = [
    "TASK_ASSIGNED",
    "TASK_REASSIGNED",
    "DEADLINE_CHANGED",
    "NEW_INSTRUCTIONS",
    "CORRECTIONS_REQUESTED",
    "BLOCK_RESOLVED",
    "TASK_APPROVED",
    "TASK_REOPENED",
    "SUBMITTED_FOR_INSPECTION",
    "TASK_OVERDUE",
    "ISSUE_REPORTED",
    "TASK_BLOCKED",
    "READY_FOR_PICKUP",
    "TASK_DELIVERED",
    "TASK_CANCELLED"
];
const INCENTIVE_PERIOD_TYPES = [
    "MONTHLY",
    "QUARTERLY",
    "ANNUAL",
    "QUALITY",
    "ON_TIME",
    "CUSTOM"
];
const INCENTIVE_PERIOD_LABELS = {
    MONTHLY: "Monthly productivity",
    QUARTERLY: "Quarterly performance",
    ANNUAL: "Annual performance",
    QUALITY: "Quality achievements",
    ON_TIME: "On-time completion",
    CUSTOM: "Management-defined target"
};
const INCENTIVE_STATUSES = [
    "PENDING",
    "APPROVED",
    "REJECTED"
];
const PAYMENT_STATUSES = [
    "UNPAID",
    "PAID"
];
function isTaskStatus(value) {
    return TASK_STATUSES.includes(value);
}
function isPriority(value) {
    return PRIORITIES.includes(value);
}
function isStageState(value) {
    return STAGE_STATES.includes(value);
}
function isTaskCategory(value) {
    return TASK_CATEGORIES.includes(value);
}
function taskIsOverdue(deadline, status, at = new Date()) {
    if (!deadline || OVERDUE_EXEMPT_STATUSES.includes(status)) return false;
    return new Date(deadline).getTime() < at.getTime();
}
if (typeof globalThis.$RefreshHelpers$ === 'object' && globalThis.$RefreshHelpers !== null) {
    __turbopack_context__.k.registerExports(__turbopack_context__.m, globalThis.$RefreshHelpers$);
}
}),
"[project]/src/lib/permissions.ts [app-client] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — role & permission matrix for the independent production
// management system. Server-side authorization is enforced in every API route
// via requirePermission(). Staff (production workers) have no global
// permissions: their access is strictly scoped to their own assigned tasks.
__turbopack_context__.s([
    "ASSIGNABLE_ROLES",
    ()=>ASSIGNABLE_ROLES,
    "PERMISSIONS",
    ()=>PERMISSIONS,
    "ROLES",
    ()=>ROLES,
    "ROLE_DESCRIPTIONS",
    ()=>ROLE_DESCRIPTIONS,
    "ROLE_LABELS",
    ()=>ROLE_LABELS,
    "hasPermission",
    ()=>hasPermission,
    "permissionsFor",
    ()=>permissionsFor
]);
const ROLES = [
    "MANAGEMENT",
    "ADMIN",
    "STAFF"
];
const ROLE_LABELS = {
    MANAGEMENT: "Management",
    ADMIN: "Admin",
    STAFF: "Staff"
};
const ROLE_DESCRIPTIONS = {
    MANAGEMENT: "Full operational and supervisory access, final authority on incentives and critical decisions.",
    ADMIN: "Operational management of customers, orders, production and staff accounts. Cannot override Management-level controls.",
    STAFF: "Individual production worker: sees and updates only the tasks assigned to their own account."
};
const PERMISSIONS = [
    "customers.view",
    "customers.manage",
    "orders.view",
    "orders.manage",
    "users.view",
    "users.manage.staff",
    "users.manage.all",
    "production.view.all",
    "production.create.tasks",
    "production.assign",
    "production.inspect",
    "production.cancel",
    "production.manage.details",
    "production.stages.configure",
    "production.reports",
    "performance.view",
    "performance.configure",
    "performance.assess",
    "incentives.view",
    "incentives.prepare",
    "incentives.manage.schemes",
    "incentives.approve",
    "incentives.payment",
    "audit.view"
];
const ADMIN_PERMISSIONS = [
    "customers.view",
    "customers.manage",
    "orders.view",
    "orders.manage",
    "users.view",
    "users.manage.staff",
    "production.view.all",
    "production.create.tasks",
    "production.assign",
    "production.inspect",
    "production.cancel",
    "production.manage.details",
    "production.stages.configure",
    "production.reports",
    "performance.view",
    "incentives.view",
    "incentives.prepare"
];
const MATRIX = {
    MANAGEMENT: PERMISSIONS,
    ADMIN: ADMIN_PERMISSIONS,
    STAFF: []
};
function hasPermission(role, permission) {
    const list = MATRIX[role];
    return !!list && list.includes(permission);
}
function permissionsFor(role) {
    return [
        ...MATRIX[role] ?? []
    ];
}
const ASSIGNABLE_ROLES = [
    "MANAGEMENT",
    "ADMIN",
    "STAFF"
];
if (typeof globalThis.$RefreshHelpers$ === 'object' && globalThis.$RefreshHelpers !== null) {
    __turbopack_context__.k.registerExports(__turbopack_context__.m, globalThis.$RefreshHelpers$);
}
}),
]);

//# sourceMappingURL=src_lib_279a0fc9._.js.map