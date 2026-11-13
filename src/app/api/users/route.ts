import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { hashPassword, requirePermission } from "@/lib/auth";
import { createUserSchema } from "@/lib/validation";
import { ROLE_LABELS, type Role } from "@/lib/permissions";

/** GET /api/users — list staff accounts (requires staff.view). */
export const GET = withApi(async (req) => {
  const user = await requirePermission("staff.view");
  const url = new URL(req.url);
  const assignableOnly = url.searchParams.get("assignable") === "1";

  const users = await db.user.findMany({
    orderBy: [{ isActive: "desc" }, { name: "asc" }],
    select: {
      id: true,
      email: true,
      name: true,
      role: true,
      isActive: true,
      createdAt: true,
    },
  });

  const withCounts = await Promise.all(
    users.map(async (u) => {
      const counts = await db.productionTask.groupBy({
        by: ["status"],
        where: { assignedStaffId: u.id },
        _count: { _all: true },
      });
      const byStatus: Record<string, number> = {};
      for (const c of counts) byStatus[c.status] = c._count._all;
      return {
        ...u,
        roleLabel: ROLE_LABELS[u.role as Role] ?? u.role,
        activeTaskCount: (byStatus.ASSIGNED ?? 0) + (byStatus.IN_PROGRESS ?? 0) +
          (byStatus.CHANGES_REQUESTED ?? 0) + (byStatus.AWAITING_INSPECTION ?? 0) +
          (byStatus.BLOCKED ?? 0),
      };
    })
  );

  const filtered = assignableOnly
    ? withCounts.filter((u) => u.isActive && ["ADMIN", "MANAGER", "TAILOR"].includes(u.role))
    : withCounts;

  return json({ users: filtered, viewerId: user.id });
});

/** POST /api/users — create a staff account (requires staff.manage). */
export const POST = withApi(async (req) => {
  await requirePermission("staff.manage");
  const data = await parseBody(req, createUserSchema);

  const existing = await db.user.findUnique({ where: { email: data.email } });
  if (existing) throw new ApiError(409, "An account with this email already exists");

  const user = await db.user.create({
    data: {
      email: data.email,
      name: data.name,
      role: data.role,
      passwordHash: hashPassword(data.password),
      isActive: true,
    },
    select: { id: true, email: true, name: true, role: true, isActive: true, createdAt: true },
  });
  return json({ user }, 201);
});
