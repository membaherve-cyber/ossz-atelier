import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { reopenSchema } from "@/lib/validation";
import { notifyUsers, recordEvent, syncOrderStatus } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/reopen — reopen approved work for
 * corrections with a recorded reason. Requires production.reopen.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requirePermission("production.reopen");
  const { id } = await ctx.params;
  const body = await parseBody(req, reopenSchema);

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");
    if (task.status !== "APPROVED") {
      throw new ApiError(400, "Only approved pieces can be reopened");
    }

    await tx.productionTask.update({
      where: { id },
      data: { status: "IN_PROGRESS" },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: "TASK_REOPENED",
      actorId: user.id,
      previousValue: "APPROVED",
      newValue: "IN_PROGRESS",
      reason: body.reason,
    });

    if (task.assignedStaffId) {
      await notifyUsers(tx, [task.assignedStaffId], {
        title: `${task.pieceName} reopened for corrections`,
        body: body.reason,
        taskId: id,
        orderId: task.orderId,
        type: "TASK_REOPENED",
      });
    }

    await syncOrderStatus(tx, task.orderId);

    return { taskId: id, pieceName: task.pieceName, status: "IN_PROGRESS" };
  });

  return json({ ok: true, ...result });
});
