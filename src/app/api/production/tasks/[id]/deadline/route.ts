import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { deadlineSchema } from "@/lib/validation";
import { notifyUsers, recordEvent } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/deadline — set or clear the assignment
 * deadline. Requires production.manage.deadlines. The assigned staff member
 * is notified of the change.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requirePermission("production.manage.deadlines");
  const { id } = await ctx.params;
  const body = await parseBody(req, deadlineSchema);

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");
    if (["APPROVED", "CANCELLED"].includes(task.status)) {
      throw new ApiError(400, `This task is ${task.status.toLowerCase()}; its deadline can no longer change`);
    }

    const newDueAt = body.dueAt ? new Date(body.dueAt) : null;
    await tx.productionTask.update({
      where: { id },
      data: { dueAt: newDueAt },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: "DEADLINE_CHANGED",
      actorId: user.id,
      previousValue: task.dueAt ? task.dueAt.toISOString() : null,
      newValue: newDueAt ? newDueAt.toISOString() : null,
      reason: body.reason ?? null,
    });

    if (task.assignedStaffId) {
      await notifyUsers(tx, [task.assignedStaffId], {
        title: `Deadline changed on ${task.pieceName}`,
        body: newDueAt
          ? `New deadline: ${newDueAt.toLocaleString()}. ${body.reason ?? ""}`.trim()
          : "The deadline was removed from this task.",
        taskId: id,
        orderId: task.orderId,
        type: "DEADLINE_CHANGED",
      });
    }

    return { taskId: id, pieceName: task.pieceName, dueAt: newDueAt };
  });

  return json({ ok: true, ...result });
});
