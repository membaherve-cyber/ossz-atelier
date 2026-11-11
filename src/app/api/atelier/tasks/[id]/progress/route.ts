import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import {
  canAccessTask,
  changeTaskStatus,
  serializeTask,
  taskInclude,
} from "@/lib/atelier/tasks";
import { TERMINAL_STATUSES, type TaskStatus } from "@/lib/atelier/constants";

type Params = { params: Promise<{ id: string }> };

const progressSchema = z.object({
  progress: z.coerce.number().int().min(0).max(100),
  note: z.string().trim().max(500).optional().nullable(),
});

/**
 * POST /api/atelier/tasks/[id]/progress — numerical progress update.
 * Append-only history: previous value, new value, author, source, note.
 * 100% does NOT auto-approve: inspection submission is an explicit action.
 */
export const POST = withApi(async (req: Request, { params }: Params) => {
  const user = await requireUser();
  const { id } = await params;
  const body = await parseBody(req, progressSchema);

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
    throw new ApiError(400, "This task is finished and its progress can no longer be updated.");
  }
  if (!supervisor && !["ASSIGNED", "ONGOING", "CHANGES_REQUESTED"].includes(task.status)) {
    throw new ApiError(400, `Progress cannot be updated while the task is ${task.status}.`);
  }
  if (!supervisor && task.status === "BLOCKED") {
    throw new ApiError(400, "Resolve the blocker before updating progress.");
  }
  if (body.progress === task.progress) {
    throw new ApiError(400, `Progress is already ${task.progress}%.`);
  }

  const newStatus = task.status === "ASSIGNED" && body.progress > 0 ? "ONGOING" : task.status;

  await db.$transaction([
    db.productionTask.update({
      where: { id },
      data: { progress: body.progress, status: newStatus },
    }),
    db.taskProgressHistory.create({
      data: {
        taskId: id,
        userId: user.id,
        userName: user.name,
        previousProgress: task.progress,
        newProgress: body.progress,
        source: "MANUAL",
        note: body.note || null,
      },
    }),
  ]);

  if (newStatus !== task.status) {
    await changeTaskStatus(id, task.status, newStatus as TaskStatus, user, "Production started");
  }

  await logAudit(user, "PROGRESS_UPDATED", "TASK", id, {
    from: task.progress,
    to: body.progress,
    note: body.note ?? null,
    correction: supervisor,
  });

  const refreshed = await db.productionTask.findUnique({ where: { id }, include: taskInclude });
  return json({ task: refreshed ? serializeTask(refreshed) : null });
});
