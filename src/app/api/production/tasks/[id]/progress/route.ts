import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { hasPermission } from "@/lib/permissions";
import { progressSchema } from "@/lib/validation";
import { notifyUsers, recordEvent, userIdsWithPermission } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/progress — update completion progress (0-100).
 *
 * - The assigned staff member updates their own task (optimistic concurrency:
 *   `expectedProgress` must match the stored value, otherwise HTTP 409).
 * - Managers (production.correct.progress) may correct an inaccurate entry;
 *   the original record is preserved and the correction is flagged with a reason.
 * - Every update appends a new history entry — nothing is overwritten.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requireUser();
  const { id } = await ctx.params;
  const body = await parseBody(req, progressSchema);

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");

    const isOwner = task.assignedStaffId === user.id;
    const canCorrect = hasPermission(user.role, "production.correct.progress");
    if (!isOwner && !canCorrect) {
      throw new ApiError(403, "You can only update progress on tasks assigned to you");
    }
    const isCorrection = !isOwner;

    if (isCorrection && !body.correctionReason) {
      throw new ApiError(400, "A correction reason is required when adjusting another staff member's progress");
    }

    if (["APPROVED", "CANCELLED"].includes(task.status)) {
      throw new ApiError(
        400,
        `This task is ${task.status.toLowerCase()} — progress can no longer be updated`
      );
    }
    if (task.status === "AWAITING_INSPECTION") {
      throw new ApiError(
        400,
        "This piece is awaiting inspection. It must be returned for corrections before progress can change."
      );
    }
    if (task.status === "BLOCKED" && !isCorrection) {
      throw new ApiError(
        400,
        "This task is blocked. Report the issue to a manager — the blocker must be resolved before work continues."
      );
    }

    // Optimistic concurrency: reject if someone else changed the progress meanwhile.
    if (task.progressPercentage !== body.expectedProgress) {
      throw new ApiError(
        409,
        `This task's progress changed to ${task.progressPercentage}% while you were working. Please review the latest state and try again.`
      );
    }

    const previousStatus = task.status;
    let newStatus = previousStatus;
    if (previousStatus === "ASSIGNED" && body.newProgress > 0) newStatus = "IN_PROGRESS";
    if (previousStatus === "CHANGES_REQUESTED") newStatus = "IN_PROGRESS";
    if (previousStatus === "BLOCKED" && isCorrection) newStatus = "BLOCKED"; // corrections may occur while blocked; status unchanged

    await tx.productionTask.update({
      where: { id },
      data: { progressPercentage: body.newProgress, status: newStatus },
    });

    if (previousStatus === "ASSIGNED" && newStatus === "IN_PROGRESS") {
      await recordEvent(tx, {
        taskId: id,
        orderId: task.orderId,
        eventType: "PRODUCTION_STARTED",
        actorId: user.id,
      });
    }

    await tx.progressUpdate.create({
      data: {
        taskId: id,
        staffId: user.id,
        previousProgress: task.progressPercentage,
        newProgress: body.newProgress,
        previousStatus,
        newStatus,
        note: body.note ?? null,
        issueReason: body.issueReason ?? null,
        isCorrection,
        correctionReason: isCorrection ? body.correctionReason : null,
      },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: isCorrection ? "PROGRESS_CORRECTED" : "PROGRESS_UPDATED",
      actorId: user.id,
      previousValue: `${task.progressPercentage}%`,
      newValue: `${body.newProgress}%`,
      reason: isCorrection ? body.correctionReason : body.note ?? null,
    });

    if (isCorrection && task.assignedStaffId) {
      await notifyUsers(tx, [task.assignedStaffId], {
        title: `Progress corrected on ${task.pieceName}`,
        body: `A manager adjusted the progress from ${task.progressPercentage}% to ${body.newProgress}%. Reason: ${body.correctionReason}`,
        taskId: id,
        orderId: task.orderId,
        type: "PROGRESS_CORRECTED",
      });
    }

    if (body.newProgress === 100 && !isCorrection) {
      const managers = await userIdsWithPermission(tx, "production.inspect");
      await notifyUsers(tx, managers, {
        title: `${task.pieceName} reported at 100%`,
        body: `${user.name} reported 100% progress on ${task.pieceName}.`,
        taskId: id,
        orderId: task.orderId,
        type: "PROGRESS_COMPLETE",
      });
    }

    return {
      taskId: id,
      pieceName: task.pieceName,
      progress: body.newProgress,
      status: newStatus,
    };
  });

  return json({ ok: true, ...result });
});
