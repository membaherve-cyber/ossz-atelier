import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { hasPermission } from "@/lib/permissions";
import { submitSchema } from "@/lib/validation";
import { notifyUsers, recordEvent, userIdsWithPermission } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/submit — submit a finished piece for inspection.
 * The assigned staff member (or a manager) reports the piece as ready. The task
 * becomes AWAITING_INSPECTION — never automatically approved.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requireUser();
  const { id } = await ctx.params;
  const body = await parseBody(req, submitSchema);

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");

    const isOwner = task.assignedStaffId === user.id;
    const isManager = hasPermission(user.role, "production.view.all");
    if (!isOwner && !isManager) {
      throw new ApiError(403, "You can only submit tasks assigned to you");
    }

    if (!["ASSIGNED", "IN_PROGRESS", "CHANGES_REQUESTED"].includes(task.status)) {
      throw new ApiError(
        400,
        task.status === "AWAITING_INSPECTION"
          ? "This piece has already been submitted for inspection"
          : `This task is ${task.status.toLowerCase()} and cannot be submitted`
      );
    }
    if (task.progressPercentage < 100) {
      throw new ApiError(
        400,
        `Progress is at ${task.progressPercentage}%. The piece must reach 100% before it can be submitted for inspection.`
      );
    }

    await tx.productionTask.update({
      where: { id },
      data: { status: "AWAITING_INSPECTION" },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: "SUBMITTED_FOR_INSPECTION",
      actorId: user.id,
      reason: body.note ?? null,
    });

    const inspectors = await userIdsWithPermission(tx, "production.inspect");
    await notifyUsers(tx, inspectors, {
      title: `${task.pieceName} submitted for inspection`,
      body: `${user.name} reports the piece is ready. ${body.note ?? ""}`.trim(),
      taskId: id,
      orderId: task.orderId,
      type: "SUBMITTED_FOR_INSPECTION",
    });

    return { taskId: id, pieceName: task.pieceName, status: "AWAITING_INSPECTION" };
  });

  return json({ ok: true, ...result });
});
