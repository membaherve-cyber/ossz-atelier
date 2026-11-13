import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { assignSchema } from "@/lib/validation";
import { notifyUsers, recordEvent } from "@/lib/production";
import { ASSIGNABLE_ROLES } from "@/lib/permissions";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/assign — assign or reassign a garment piece.
 * Requires production.assign. Preserves all history; the reassignment is
 * recorded as a new assignment record and audit event.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requirePermission("production.assign");
  const { id } = await ctx.params;
  const body = await parseBody(req, assignSchema);

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");
    if (["APPROVED", "CANCELLED"].includes(task.status)) {
      throw new ApiError(400, `This task is ${task.status.toLowerCase()} and can no longer be assigned`);
    }

    // Only active, authorized staff accounts can receive production work.
    const staff = await tx.user.findUnique({ where: { id: body.staffId } });
    if (!staff) throw new ApiError(404, "Staff account not found");
    if (!staff.isActive) {
      throw new ApiError(400, "This staff account is inactive and cannot be assigned production work");
    }
    if (!ASSIGNABLE_ROLES.includes(staff.role as (typeof ASSIGNABLE_ROLES)[number])) {
      throw new ApiError(400, `Accounts with the "${staff.role}" role cannot be assigned production work`);
    }

    const previousStaffId = task.assignedStaffId;
    const isReassignment = !!previousStaffId && previousStaffId !== staff.id;

    await tx.productionTask.update({
      where: { id },
      data: {
        assignedStaffId: staff.id,
        assignedById: user.id,
        assignedAt: new Date(),
        ...(body.dueAt !== undefined
          ? { dueAt: body.dueAt ? new Date(body.dueAt) : null }
          : {}),
        ...(body.productionInstructions !== undefined
          ? { productionInstructions: body.productionInstructions }
          : {}),
        ...(task.status === "UNASSIGNED" ? { status: "ASSIGNED" } : {}),
      },
    });

    await tx.assignmentRecord.create({
      data: {
        taskId: id,
        previousStaffId,
        newStaffId: staff.id,
        assignedById: user.id,
        reason: body.reason ?? null,
      },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: isReassignment ? "TASK_REASSIGNED" : "PIECE_ASSIGNED",
      actorId: user.id,
      previousValue: previousStaffId,
      newValue: staff.id,
      reason: body.reason ?? null,
    });

    // Notify the new assignee (and the previous one on reassignment).
    await notifyUsers(tx, [staff.id], {
      title: isReassignment
        ? `Task reassigned to you: ${task.pieceName}`
        : `New task assigned: ${task.pieceName}`,
      body: body.reason ?? (body.dueAt ? `Deadline: ${new Date(body.dueAt).toLocaleDateString()}` : null),
      taskId: id,
      orderId: task.orderId,
      type: isReassignment ? "TASK_REASSIGNED" : "TASK_ASSIGNED",
    });
    if (isReassignment && previousStaffId) {
      await notifyUsers(tx, [previousStaffId], {
        title: `Task reassigned: ${task.pieceName}`,
        body: body.reason ?? "This task has been assigned to another staff member.",
        taskId: id,
        orderId: task.orderId,
        type: "TASK_REASSIGNED",
      });
    }

    return { taskId: id, pieceName: task.pieceName, assignedTo: staff.name };
  });

  return json({ ok: true, ...result });
});
