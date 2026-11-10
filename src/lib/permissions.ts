// OSSZ role & permission matrix.
// Server-side authorization is enforced in every API route via requirePermission().

export const ROLES = ["ADMIN", "MANAGER", "INSPECTOR", "TAILOR"] as const;
export type Role = (typeof ROLES)[number];

export const ROLE_LABELS: Record<Role, string> = {
  ADMIN: "Administrator",
  MANAGER: "Production Manager",
  INSPECTOR: "Inspector",
  TAILOR: "Production Staff",
};

/** Staff accounts that can be assigned production work. */
export const ASSIGNABLE_ROLES: Role[] = ["ADMIN", "MANAGER", "TAILOR"];

export const PERMISSIONS = [
  "orders.view",
  "orders.manage",
  "products.view",
  "products.manage",
  "staff.view",
  "staff.manage",
  "production.view.all",
  "production.create.tasks",
  "production.assign",
  "production.inspect",
  "production.resolve.blockers",
  "production.reopen",
  "production.cancel",
  "production.correct.progress",
  "production.manage.deadlines",
  "production.reports",
] as const;

export type Permission = (typeof PERMISSIONS)[number];

const MATRIX: Record<Role, readonly Permission[]> = {
  ADMIN: PERMISSIONS,
  MANAGER: [
    "orders.view",
    "orders.manage",
    "products.view",
    "products.manage",
    "staff.view",
    "production.view.all",
    "production.create.tasks",
    "production.assign",
    "production.inspect",
    "production.resolve.blockers",
    "production.reopen",
    "production.cancel",
    "production.correct.progress",
    "production.manage.deadlines",
    "production.reports",
  ],
  INSPECTOR: [
    "orders.view",
    "products.view",
    "staff.view",
    "production.view.all",
    "production.inspect",
    "production.reports",
  ],
  TAILOR: ["products.view"],
};

export function hasPermission(role: string, permission: Permission): boolean {
  const list = MATRIX[role as Role];
  return !!list && list.includes(permission);
}

export function permissionsFor(role: string): Permission[] {
  return [...(MATRIX[role as Role] ?? [])];
}
