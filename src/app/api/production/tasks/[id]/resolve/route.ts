import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { resolveSchema } from "@/lib/validation";
import { notifyUsers, recordEvent } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/resolve — resolve a blocker and resume
 * production. Requires production.resolve.blockers.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requirePermission("production.resolve.blockers");
  const { id } = await ctx.params;
  const body = await parseBody(req, resolveSchema);

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");
    if (task.status !== "BLOCKED") {
      throw new ApiError(400, "This task is not blocked");
    }

    const newStatus = task.progressPercentage > 0 ? "IN_PROGRESS" : "ASSIGNED";
    await tx.productionTask.update({
      where: { id },
      data: {
        status: newStatus,
        blockedReason: null,
        blockedById: null,
        blockedAt: null,
      },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: "BLOCKER_RESOLVED",
      actorId: user.id,
      previousValue: "BLOCKED",
      newValue: newStatus,
      reason: body.note ?? null,
    });

    if (task.assignedStaffId) {
      await notifyUsers(tx, [task.assignedStaffId], {
        title: `Blocker cleared on ${task.pieceName}`,
        body: body.note ?? "Production can resume on this piece.",
        taskId: id,
        orderId: task.orderId,
        type: "BLOCKER_RESOLVED",
      });
    }

    return { taskId: id, pieceName: task.pieceName, status: newStatus };
  });

  return json({ ok: true, ...result });
});
