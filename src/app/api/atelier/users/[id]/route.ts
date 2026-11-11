import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requireUser, hashPassword } from "@/lib/auth";
import { hasPermission, ROLES } from "@/lib/permissions";
import { logAudit } from "@/lib/atelier/audit";
import type { SessionUser } from "@/lib/atelier/constants";

// ---------------------------------------------------------------------------
// PATCH /api/atelier/users/[id] — edit an account: profile fields, role,
// activation state and password resets. Admins may only manage STAFF
// accounts; Management may manage any account. Safeguards:
//   - you cannot deactivate your own account
//   - the last active MANAGEMENT account cannot be deactivated or demoted
//   - password resets are audited separately and never logged in details
// ---------------------------------------------------------------------------

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

const updateUserSchema = z.object({
  name: z.string().trim().min(2, "Name must be at least 2 characters").max(120).optional(),
  phone: z.string().trim().max(40).optional(),
  jobTitle: z.string().trim().max(120).optional(),
  role: z.enum(ROLES).optional(),
  isActive: z.boolean().optional(),
  password: z.string().min(8, "Password must be at least 8 characters").optional(),
});

export const PATCH = withApi(async (req: Request, { params }: { params: Promise<{ id: string }> }) => {
  const user = await requireAccountManagement();
  const { id } = await params;
  const body = await parseBody(req, updateUserSchema);

  const target = await db.user.findUnique({ where: { id } });
  if (!target) throw new ApiError(404, "User not found.");

  // Admins are restricted to staff accounts (both target and resulting role).
  if (user.role === "ADMIN") {
    if (target.role !== "STAFF") {
      throw new ApiError(403, "Admins can only manage Staff accounts.");
    }
    if (body.role !== undefined && body.role !== "STAFF") {
      throw new ApiError(403, "Admins can only assign the Staff role.");
    }
  }

  // You cannot deactivate yourself.
  if (body.isActive === false && target.id === user.id) {
    throw new ApiError(400, "You cannot deactivate your own account.");
  }

  // Protect the last active Management account from deactivation/demotion.
  const demotes =
    body.role !== undefined && body.role !== "MANAGEMENT" && target.role === "MANAGEMENT";
  const deactivates = body.isActive === false && target.isActive;
  if ((demotes || deactivates) && target.role === "MANAGEMENT") {
    const activeManagers = await db.user.count({
      where: { role: "MANAGEMENT", isActive: true },
    });
    if (activeManagers <= 1) {
      throw new ApiError(
        400,
        "This is the last active Management account: it cannot be deactivated or demoted."
      );
    }
  }

  const data: Record<string, unknown> = {};
  const changedFields: string[] = [];
  if (body.name !== undefined && body.name !== target.name) {
    data.name = body.name;
    changedFields.push("name");
  }
  if (body.phone !== undefined && (body.phone || null) !== target.phone) {
    data.phone = body.phone || null;
    changedFields.push("phone");
  }
  if (body.jobTitle !== undefined && (body.jobTitle || null) !== target.jobTitle) {
    data.jobTitle = body.jobTitle || null;
    changedFields.push("jobTitle");
  }
  if (body.role !== undefined && body.role !== target.role) {
    data.role = body.role;
    changedFields.push("role");
  }
  if (body.isActive !== undefined && body.isActive !== target.isActive) {
    data.isActive = body.isActive;
    changedFields.push("isActive");
  }
  if (body.password !== undefined) {
    data.passwordHash = hashPassword(body.password);
  }

  const updated = await db.user.update({ where: { id }, data });

  if (body.password !== undefined) {
    // Separate audit entry; the password itself is never recorded.
    await logAudit(user, "USER_PASSWORD_RESET", "USER", updated.id);
  }
  if (changedFields.length > 0) {
    await logAudit(user, "USER_UPDATED", "USER", updated.id, {
      email: updated.email,
      changedFields,
    });
  }

  const activeTaskCount = await db.productionTask.count({
    where: {
      assignedStaffId: updated.id,
      status: { notIn: ["DELIVERED", "CANCELLED"] },
    },
  });

  return json({
    user: {
      id: updated.id,
      name: updated.name,
      email: updated.email,
      phone: updated.phone,
      jobTitle: updated.jobTitle,
      role: updated.role,
      isActive: updated.isActive,
      createdAt: updated.createdAt.toISOString(),
      activeTaskCount,
    },
  });
});
