// OSSZ ATELIER — role & permission matrix for the independent production
// management system. Server-side authorization is enforced in every API route
// via requirePermission(). Staff (production workers) have no global
// permissions: their access is strictly scoped to their own assigned tasks.

export const ROLES = ["MANAGEMENT", "ADMIN", "STAFF"] as const;
export type Role = (typeof ROLES)[number];

export const ROLE_LABELS: Record<Role, string> = {
  MANAGEMENT: "Management",
  ADMIN: "Admin",
  STAFF: "Staff",
};

export const ROLE_DESCRIPTIONS: Record<Role, string> = {
  MANAGEMENT:
    "Full operational and supervisory access, final authority on incentives and critical decisions.",
  ADMIN:
    "Operational management of customers, orders, production and staff accounts. Cannot override Management-level controls.",
  STAFF:
    "Individual production worker: sees and updates only the tasks assigned to their own account.",
};

export const PERMISSIONS = [
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
  "audit.view",
] as const;

export type Permission = (typeof PERMISSIONS)[number];

const ADMIN_PERMISSIONS: readonly Permission[] = [
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
  "incentives.prepare",
];

const MATRIX: Record<Role, readonly Permission[]> = {
  MANAGEMENT: PERMISSIONS,
  ADMIN: ADMIN_PERMISSIONS,
  STAFF: [],
};

export function hasPermission(role: string, permission: Permission): boolean {
  const list = MATRIX[role as Role];
  return !!list && list.includes(permission);
}

export function permissionsFor(role: string): Permission[] {
  return [...(MATRIX[role as Role] ?? [])];
}

/** Roles whose accounts can be assigned production work. */
export const ASSIGNABLE_ROLES: Role[] = ["MANAGEMENT", "ADMIN", "STAFF"];
