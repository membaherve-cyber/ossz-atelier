import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import {
  canAccessTask,
  changeTaskStatus,
  getApplicableStages,
  recalculateProgress,
  serializeTask,
  taskInclude,
} from "@/lib/atelier/tasks";
import { isStageState, TERMINAL_STATUSES } from "@/lib/atelier/constants";

type Params = { params: Promise<{ id: string }> };

const stagesSchema = z.object({
  updates: z
    .array(
      z.object({
        stageId: z.string().min(1),
        state: z.string().min(1),
        comment: z.string().trim().max(500).optional().nullable(),
      })
    )
    .min(1, "At least one stage update is required")
    .max(30),
});

/**
 * POST /api/atelier/tasks/[id]/stages — update production checklist stages.
 * Every change is recorded immutably (who, previous state, new state, when,
 * optional comment). Progress is recalculated from the required stages.
 */
export const POST = withApi(async (req: Request, { params }: Params) => {
  const user = await requireUser();
  const { id } = await params;
  const body = await parseBody(req, stagesSchema);

  const task = await db.productionTask.findUnique({
    where: { id },
    include: taskInclude,
  });
  if (!task) throw new ApiError(404, "Task not found.");

  if (!canAccessTask(user, { assignedStaffId: task.assignedStaffId })) {
    throw new ApiError(403, "You are not authorized to update this task.");
  }

  const supervisor = user.role === "MANAGEMENT" || user.role === "ADMIN";
  if (TERMINAL_STATUSES.includes(task.status as never)) {
    throw new ApiError(400, "This task is finished and its checklist can no longer be modified.");
  }
  if (!supervisor && !["ASSIGNED", "ONGOING", "CHANGES_REQUESTED"].includes(task.status)) {
    throw new ApiError(400, `The checklist cannot be updated while the task is ${task.status}.`);
  }

  // Validate stages against this task's category workflow.
  const applicable = await getApplicableStages(task.category);
  const applicableIds = new Set(applicable.map((s) => s.id));
  const stageById = new Map(applicable.map((s) => [s.id, s]));

  const existingStates = await db.taskStageState.findMany({ where: { taskId: id } });
  const stateMap = new Map(existingStates.map((s) => [s.stageId, s]));

  for (const u of body.updates) {
    if (!applicableIds.has(u.stageId)) {
      throw new ApiError(400, "One of the stages does not apply to this task category.");
    }
    if (!isStageState(u.state)) {
      throw new ApiError(400, `Invalid stage state: ${u.state}`);
    }
  }

  const applied: Array<{ stageName: string; previousState: string; newState: string }> = [];

  await db.$transaction(async (tx) => {
    for (const u of body.updates) {
      const stage = stageById.get(u.stageId)!;
      const previous = stateMap.get(u.stageId)?.state ?? "NOT_STARTED";
      if (previous === u.state) continue;

      await tx.taskStageUpdate.create({
        data: {
          taskId: id,
          stageId: stage.id,
          stageName: stage.name,
          userId: user.id,
          userName: user.name,
          previousState: previous,
          newState: u.state,
          comment: u.comment || null,
        },
      });

      const existing = stateMap.get(u.stageId);
      if (existing) {
        await tx.taskStageState.update({
          where: { id: existing.id },
          data: {
            state: u.state,
            updatedById: user.id,
            updatedByName: user.name,
            updatedAt: new Date(),
          },
        });
      } else {
        await tx.taskStageState.create({
          data: {
            taskId: id,
            stageId: stage.id,
            state: u.state,
            updatedById: user.id,
            updatedByName: user.name,
          },
        });
      }
      applied.push({ stageName: stage.name, previousState: previous, newState: u.state });
    }
  });

  if (applied.length === 0) {
    throw new ApiError(400, "No stage changes were provided (states identical).");
  }

  // Recalculate numerical progress from the checklist (never decreases).
  await recalculateProgress(id, user);

  // First update moves an assigned task into Ongoing.
  if (task.status === "ASSIGNED") {
    await db.productionTask.update({ where: { id }, data: { status: "ONGOING" } });
    await changeTaskStatus(id, "ASSIGNED", "ONGOING", user, "Production started");
  }

  await logAudit(user, "STAGES_UPDATED", "TASK", id, { changes: applied });

  const [refreshed, stageStates] = await Promise.all([
    db.productionTask.findUnique({ where: { id }, include: taskInclude }),
    db.taskStageState.findMany({ where: { taskId: id } }),
  ]);
  const newStateMap = new Map(stageStates.map((s) => [s.stageId, s.state]));

  return json({
    task: refreshed ? serializeTask(refreshed) : null,
    stages: applicable.map((s) => ({
      id: s.id,
      code: s.code,
      name: s.name,
      sortOrder: s.sortOrder,
      weight: s.weight,
      state: newStateMap.get(s.id) ?? "NOT_STARTED",
    })),
  });
});
