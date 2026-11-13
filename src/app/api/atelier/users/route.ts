import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, parseQuery, withApi } from "@/lib/api";
import { requirePermission, requireUser, hashPassword } from "@/lib/auth";
import { hasPermission, ROLES } from "@/lib/permissions";
import { logAudit } from "@/lib/atelier/audit";
import type { SessionUser } from "@/lib/atelier/constants";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Staff-account managers (ADMIN) or full account managers (MANAGEMENT). */
async function requireAccountManagement(): Promise<SessionUser> {
  const user = await requireUser();
  if (
    !hasPermission(user.role, "users.manage.staff") &&
    !hasPermission(user.role, "users.manage.all")
  ) {
    throw new ApiError(403, "Your account is not authorized to manage user accounts.");
  }
  return user;
}

const ACTIVE_TASK_EXCLUDED_STATUSES = ["DELIVERED", "CANCELLED"];

async function activeTaskCounts(): Promise<Map<string, number>> {
  const grouped = await db.productionTask.groupBy({
    by: ["assignedStaffId"],
    where: {
      assignedStaffId: { not: null },
      status: { notIn: ACTIVE_TASK_EXCLUDED_STATUSES },
    },
    _count: { _all: true },
  });
  return new Map(grouped.map((g) => [g.assignedStaffId!, g._count._all]));
}

function serializeUser(
  u: {
    id: string;
    code: string | null;
    name: string;
    email: string;
    phone: string | null;
    jobTitle: string | null;
    role: string;
    isActive: boolean;
    createdAt: Date;
  },
  taskCounts: Map<string, number>
) {
  return {
    id: u.id,
    code: u.code,
    name: u.name,
    email: u.email,
    phone: u.phone,
    jobTitle: u.jobTitle,
    role: u.role,
    isActive: u.isActive,
    createdAt: u.createdAt.toISOString(),
    activeTaskCount: taskCounts.get(u.id) ?? 0,
  };
}

// ---------------------------------------------------------------------------
// GET /api/atelier/users — account directory. Returns ALL users (active and
// inactive) by default so Management keeps sight of deactivated accounts.
// ---------------------------------------------------------------------------

const listQuerySchema = z.object({
  role: z.string().optional(),
  includeInactive: z
    .string()
    .optional()
    .transform((v) => v !== "false" && v !== "0"),
});

export const GET = withApi(async (req: Request) => {
  await requirePermission("users.view");
  const q = parseQuery(req, listQuerySchema);

  const where: Record<string, unknown> = {};
  if (q.role && (ROLES as readonly string[]).includes(q.role)) where.role = q.role;
  if (!q.includeInactive) where.isActive = true;

  const [users, taskCounts] = await Promise.all([
    db.user.findMany({ where, orderBy: { name: "asc" } }),
    activeTaskCounts(),
  ]);

  return json({ users: users.map((u) => serializeUser(u, taskCounts)) });
});

// ---------------------------------------------------------------------------
// POST /api/atelier/users — create an account. Admins may only create STAFF;
// Management may create any role. Passwords are never logged or returned.
// ---------------------------------------------------------------------------

const createUserSchema = z.object({
  name: z.string().trim().min(2, "Name must be at least 2 characters").max(120),
  email: z.preprocess(
    (v) => (typeof v === "string" ? v.trim().toLowerCase() : v),
    z.email("Enter a valid email address")
  ),
  password: z.string().min(8, "Password must be at least 8 characters"),
  role: z.enum(ROLES),
  phone: z.string().trim().max(40).optional(),
  jobTitle: z.string().trim().max(120).optional(),
});

export const POST = withApi(async (req: Request) => {
  const user = await requireAccountManagement();
  const body = await parseBody(req, createUserSchema);

  if (user.role === "ADMIN" && body.role !== "STAFF") {
    throw new ApiError(403, "Admins can only create Staff accounts.");
  }

  const clash = await db.user.findUnique({ where: { email: body.email } });
  if (clash) throw new ApiError(409, "An account with this email already exists.");

  // Next sequential account identifier: USR-001, USR-002, ...
  const lastUser = await db.user.findFirst({
    orderBy: { code: "desc" },
    where: { code: { startsWith: "USR-" } },
    select: { code: true },
  });
  const nextNumber = lastUser ? parseInt(lastUser.code!.slice(4), 10) + 1 : 1;
  const code = `USR-${String(nextNumber).padStart(3, "0")}`;

  let created;
  try {
    created = await db.user.create({
      data: {
        code,
        name: body.name,
        email: body.email,
        phone: body.phone || null,
        jobTitle: body.jobTitle || null,
        passwordHash: hashPassword(body.password),
        role: body.role,
        isActive: true,
      },
    });
  } catch (err) {
    if (
      err instanceof Error &&
      "code" in err &&
      (err as { code?: string }).code === "P2002"
    ) {
      throw new ApiError(409, "An account with this email already exists.");
    }
    throw err;
  }

  await logAudit(user, "USER_CREATED", "USER", created.id, {
    code: created.code,
    name: created.name,
    email: created.email,
    role: created.role,
  });

  return json({ user: serializeUser(created, new Map()) }, 201);
});
