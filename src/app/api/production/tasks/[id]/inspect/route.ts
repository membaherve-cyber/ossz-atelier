import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { inspectSchema } from "@/lib/validation";
import { notifyUsers, recordEvent, syncOrderStatus } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/production/tasks/:id/inspect — record an inspection decision.
 * Requires production.inspect. The task must be AWAITING_INSPECTION.
 * APPROVED → status Approved; CHANGES_REQUESTED → returned to staff with
 * correction instructions. The order's production summary updates accordingly.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requirePermission("production.inspect");
  const { id } = await ctx.params;
  const body = await parseBody(req, inspectSchema);

  if (body.result === "CHANGES_REQUESTED" && !body.correctionInstructions) {
    throw new ApiError(400, "Correction instructions are required when requesting changes");
  }

  const result = await db.$transaction(async (tx) => {
    const task = await tx.productionTask.findUnique({ where: { id } });
    if (!task) throw new ApiError(404, "Production task not found");
    if (task.status !== "AWAITING_INSPECTION") {
      throw new ApiError(
        400,
        `Inspection is only possible for pieces awaiting inspection (current status: ${task.status})`
      );
    }

    await tx.inspectionRecord.create({
      data: {
        taskId: id,
        inspectorId: user.id,
        result: body.result,
        inspectionNotes: body.inspectionNotes ?? null,
        correctionInstructions: body.correctionInstructions ?? null,
      },
    });

    await tx.productionTask.update({
      where: { id },
      data: { status: body.result },
    });

    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: "INSPECTION_COMPLETED",
      actorId: user.id,
      previousValue: "AWAITING_INSPECTION",
      newValue: body.result,
      reason: body.inspectionNotes ?? null,
    });

    const approved = body.result === "APPROVED";
    await recordEvent(tx, {
      taskId: id,
      orderId: task.orderId,
      eventType: approved ? "PIECE_APPROVED" : "CORRECTIONS_REQUESTED",
      actorId: user.id,
      reason: approved
        ? body.inspectionNotes ?? null
        : body.correctionInstructions ?? null,
    });

    if (task.assignedStaffId) {
      await notifyUsers(tx, [task.assignedStaffId], {
        title: approved
          ? `${task.pieceName} approved — well done`
          : `Corrections requested on ${task.pieceName}`,
        body: approved
          ? body.inspectionNotes ?? "Your piece passed inspection."
          : body.correctionInstructions ?? "Please review the inspection feedback.",
        taskId: id,
        orderId: task.orderId,
        type: approved ? "PIECE_APPROVED" : "CORRECTIONS_REQUESTED",
      });
    }

    await syncOrderStatus(tx, task.orderId);

    return { taskId: id, pieceName: task.pieceName, status: body.result };
  });

  return json({ ok: true, ...result });
});
