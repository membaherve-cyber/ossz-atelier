import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { cancelSchema } from "@/lib/validation";
import { notifyUsers, recordEvent, syncOrderStatus } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/cancel — cancel a production task with a
 * reason. Requires production.cancel. History is fully retained.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requirePermission("production.cancel");
  const { id } = await ctx.params;
  const body = await parseBody(req, cancelSchema);

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");
    if (task.status === "CANCELLED") {
      throw new ApiError(400, "This task is already cancelled");
    }

    await tx.productionTask.update({
      where: { id },
      data: { status: "CANCELLED" },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: "TASK_CANCELLED",
      actorId: user.id,
      previousValue: task.status,
      newValue: "CANCELLED",
      reason: body.reason,
    });

    if (task.assignedStaffId) {
      await notifyUsers(tx, [task.assignedStaffId], {
        title: `Task cancelled: ${task.pieceName}`,
        body: body.reason,
        taskId: id,
        orderId: task.orderId,
        type: "TASK_CANCELLED",
      });
    }

    await syncOrderStatus(tx, task.orderId);

    return { taskId: id, pieceName: task.pieceName, status: "CANCELLED" };
  });

  return json({ ok: true, ...result });
});
