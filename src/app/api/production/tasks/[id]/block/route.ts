import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { hasPermission } from "@/lib/permissions";
import { blockSchema } from "@/lib/validation";
import { notifyUsers, recordEvent, userIdsWithPermission } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/block — report a production problem.
 * The assigned staff member (or a manager) reports the blocker with a reason;
 * the reason, reporter and timestamp are recorded.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requireUser();
  const { id } = await ctx.params;
  const body = await parseBody(req, blockSchema);

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");

    const isOwner = task.assignedStaffId === user.id;
    const isManager = hasPermission(user.role, "production.view.all");
    if (!isOwner && !isManager) {
      throw new ApiError(403, "You can only report problems on tasks assigned to you");
    }

    if (!["ASSIGNED", "IN_PROGRESS", "CHANGES_REQUESTED"].includes(task.status)) {
      throw new ApiError(
        400,
        task.status === "BLOCKED"
          ? "This task is already blocked"
          : `This task is ${task.status.toLowerCase()} and cannot be blocked`
      );
    }

    await tx.productionTask.update({
      where: { id },
      data: {
        status: "BLOCKED",
        blockedReason: body.reason,
        blockedById: user.id,
        blockedAt: new Date(),
      },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: "PRODUCTION_BLOCKED",
      actorId: user.id,
      reason: body.reason,
    });

    const managers = await userIdsWithPermission(tx, "production.resolve.blockers");
    await notifyUsers(tx, managers, {
      title: `${task.pieceName} is blocked`,
      body: `${user.name} reported: ${body.reason}`,
      taskId: id,
      orderId: task.orderId,
      type: "TASK_BLOCKED",
    });

    return { taskId: id, pieceName: task.pieceName, status: "BLOCKED" };
  });

  return json({ ok: true, ...result });
});
