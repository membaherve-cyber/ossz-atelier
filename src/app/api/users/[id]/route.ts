import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { hashPassword, requirePermission } from "@/lib/auth";
import { updateUserSchema } from "@/lib/validation";

type Ctx = { params: Promise<{ id: string }> };

/** PATCH /api/users/:id — update a staff account (requires staff.manage). */
export const PATCH = withApi(async (req: Request, ctx: Ctx) => {
  const viewer = await requirePermission("staff.manage");
  const { id } = await ctx.params;
  const data = await parseBody(req, updateUserSchema);

  const user = await db.user.findUnique({ where: { id } });
  if (!user) throw new ApiError(404, "Staff account not found");

  // Safety: an administrator cannot deactivate or demote their own account.
  if (viewer.id === id && (data.isActive === false || (data.role && data.role !== "ADMIN"))) {
    throw new ApiError(400, "You cannot deactivate or demote your own account");
  }

  if (data.isActive === false) {
    const activeTasks = await db.productionTask.count({
      where: {
        assignedStaffId: id,
        status: { in: ["ASSIGNED", "IN_PROGRESS", "CHANGES_REQUESTED", "AWAITING_INSPECTION", "BLOCKED"] },
      },
    });
    if (activeTasks > 0) {
      throw new ApiError(
        400,
        `This staff member still has ${activeTasks} active production task(s). Reassign them before deactivating the account.`
      );
    }
  }

  const updated = await db.user.update({
    where: { id },
    data: {
      ...(data.name !== undefined ? { name: data.name } : {}),
      ...(data.role !== undefined ? { role: data.role } : {}),
      ...(data.isActive !== undefined ? { isActive: data.isActive } : {}),
      ...(data.password !== undefined ? { passwordHash: hashPassword(data.password) } : {}),
    },
    select: { id: true, email: true, name: true, role: true, isActive: true, createdAt: true },
  });
  return json({ user: updated });
});
