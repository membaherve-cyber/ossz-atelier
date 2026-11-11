import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission, requireUser } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { notifyManagement, notifyUsers } from "@/lib/atelier/notify";
import {
  canAccessTask,
  changeTaskStatus,
  getApplicableStages,
  serializeTask,
  taskInclude,
} from "@/lib/atelier/tasks";
import { INSPECTION_DECISIONS, isPriority, TERMINAL_STATUSES, type TaskStatus } from "@/lib/atelier/constants";

type Params = { params: Promise<{ id: string }> };

async function loadTask(id: string) {
  const task = await db.productionTask.findUnique({
    where: { id },
    include: taskInclude,
  });
  if (!task) throw new ApiError(404, "Task not found.");
  return task;
}

// ---------------------------------------------------------------------------
// GET /api/atelier/tasks/[id] — full task detail with histories.
// Staff may only load their own tasks (server-enforced).
// ---------------------------------------------------------------------------

export const GET = withApi(async (_req: Request, { params }: Params) => {
  const user = await requireUser();
  const { id } = await params;
  const task = await loadTask(id);

  if (!canAccessTask(user, { assignedStaffId: task.assignedStaffId })) {
    throw new ApiError(403, "You are not authorized to view this task.");
  }

  const [stageStates, assignments, statusChanges, stageUpdates, progressHistory, inspections, comments] =
    await Promise.all([
      db.taskStageState.findMany({ where: { taskId: id } }),
      db.taskAssignment.findMany({ where: { taskId: id }, orderBy: { createdAt: "desc" } }),
      db.taskStatusChange.findMany({ where: { taskId: id }, orderBy: { createdAt: "desc" } }),
      db.taskStageUpdate.findMany({ where: { taskId: id }, orderBy: { createdAt: "desc" } }),
      db.taskProgressHistory.findMany({ where: { taskId: id }, orderBy: { createdAt: "desc" } }),
      db.inspectionRecord.findMany({ where: { taskId: id }, orderBy: { createdAt: "desc" } }),
      db.taskComment.findMany({ where: { taskId: id }, orderBy: { createdAt: "desc" } }),
    ]);

  const applicable = await getApplicableStages(task.category);
  const stateMap = new Map(stageStates.map((s) => [s.stageId, s.state]));

  return json({
    task: serializeTask(task),
    stages: applicable.map((s) => ({
      id: s.id,
      code: s.code,
      name: s.name,
      sortOrder: s.sortOrder,
      weight: s.weight,
      state: stateMap.get(s.id) ?? "NOT_STARTED",
    })),
    assignments: assignments.map((a) => ({
      id: a.id,
      staffId: a.staffId,
      staffName: a.staffName,
      assignedByName: a.assignedByName,
      type: a.type,
      note: a.note,
      createdAt: a.createdAt.toISOString(),
    })),
    statusChanges: statusChanges.map((s) => ({
      id: s.id,
      fromStatus: s.fromStatus,
      toStatus: s.toStatus,
      reason: s.reason,
      userName: s.userName,
      createdAt: s.createdAt.toISOString(),
    })),
    stageUpdates: stageUpdates.map((s) => ({
      id: s.id,
      stageName: s.stageName,
      previousState: s.previousState,
      newState: s.newState,
      comment: s.comment,
      userName: s.userName,
      createdAt: s.createdAt.toISOString(),
    })),
    progressHistory: progressHistory.map((p) => ({
      id: p.id,
      previousProgress: p.previousProgress,
      newProgress: p.newProgress,
      source: p.source,
      note: p.note,
      userName: p.userName,
      createdAt: p.createdAt.toISOString(),
    })),
    inspections: inspections.map((i) => ({
      id: i.id,
      decision: i.decision,
      comments: i.comments,
      inspectorName: i.inspectorName,
      createdAt: i.createdAt.toISOString(),
    })),
    comments: comments.map((c) => ({
      id: c.id,
      message: c.message,
      isIssue: c.isIssue,
      userName: c.userName,
      createdAt: c.createdAt.toISOString(),
    })),
  });
});

// ---------------------------------------------------------------------------
// PATCH /api/atelier/tasks/[id] — action-based workflow mutations.
// ---------------------------------------------------------------------------

const isoDate = z
  .string()
  .refine((v) => !Number.isNaN(new Date(v).getTime()), "Invalid date");

const patchSchema = z.object({
  action: z.enum([
    "ASSIGN",
    "UPDATE",
    "CANCEL",
    "BLOCK",
    "UNBLOCK",
    "SUBMIT",
    "INSPECT",
    "READY",
    "DELIVER",
  ]),
  staffId: z.string().optional(),
  note: z.string().trim().max(1000).optional(),
  reason: z.string().trim().max(1000).optional(),
  comments: z.string().trim().max(2000).optional(),
  decision: z.string().optional(),
  deadline: isoDate.optional().nullable(),
  priority: z.string().optional(),
  instructions: z.string().trim().max(2000).optional().nullable(),
  garmentPiece: z.string().trim().min(1).max(100).optional(),
});

export const PATCH = withApi(async (req: Request, { params }: Params) => {
  const user = await requireUser();
  const { id } = await params;
  const body = await parseBody(req, patchSchema);

  const task = await loadTask(id);
  const supervisor = user.role === "MANAGEMENT" || user.role === "ADMIN";
  const isAssignee = task.assignedStaffId === user.id;

  if (!supervisor && !isAssignee) {
    throw new ApiError(403, "You are not authorized to modify this task.");
  }
  if (TERMINAL_STATUSES.includes(task.status as never)) {
    throw new ApiError(
      400,
      `This task is ${task.status.toLowerCase()} and can no longer be modified.`
    );
  }

  const taskLabel = `${task.code} — ${task.garmentPiece} (${task.order.orderNumber})`;

  switch (body.action) {
    // ---------------------------------------------------------------- ASSIGN
    case "ASSIGN": {
      await requirePermission("production.assign");
      if (!body.staffId) throw new ApiError(400, "A staff account must be selected.");
      if (body.staffId === task.assignedStaffId) {
        throw new ApiError(400, "This task is already assigned to that staff member.");
      }
      const staff = await db.user.findUnique({ where: { id: body.staffId } });
      if (!staff || !staff.isActive) throw new ApiError(400, "Invalid or inactive staff account.");

      const reassign = !!task.assignedStaffId;
      const newStatus = task.status === "UNASSIGNED" ? "ASSIGNED" : task.status;

      await db.$transaction([
        db.productionTask.update({
          where: { id },
          data: { assignedStaffId: staff.id, status: newStatus },
        }),
        db.taskAssignment.create({
          data: {
            taskId: id,
            staffId: staff.id,
            staffName: staff.name,
            assignedById: user.id,
            assignedByName: user.name,
            type: reassign ? "REASSIGN" : "ASSIGN",
            note: body.note || null,
          },
        }),
      ]);
      if (newStatus !== task.status) {
        await changeTaskStatus(id, task.status, newStatus as TaskStatus, user, body.note ?? undefined);
      }
      await notifyUsers(
        [staff.id],
        reassign ? "TASK_REASSIGNED" : "TASK_ASSIGNED",
        reassign ? "Task reassigned to you" : "New task assigned",
        `${taskLabel} has been ${reassign ? "reassigned" : "assigned"} to you.${body.note ? ` Note: ${body.note}` : ""}`,
        id,
        task.orderId
      );
      await logAudit(user, reassign ? "TASK_REASSIGNED" : "TASK_ASSIGNED", "TASK", id, {
        staff: staff.name,
        note: body.note ?? null,
      });
      break;
    }

    // ---------------------------------------------------------------- UPDATE
    case "UPDATE": {
      await requirePermission("production.manage.details");
      const data: Record<string, unknown> = {};
      const changes: Record<string, unknown> = {};
      if (body.deadline !== undefined) {
        data.deadline = body.deadline ? new Date(body.deadline) : null;
        changes.deadline = { from: task.deadline, to: data.deadline };
      }
      if (body.priority !== undefined) {
        if (!isPriority(body.priority)) throw new ApiError(400, "Invalid priority.");
        data.priority = body.priority;
        changes.priority = { from: task.priority, to: body.priority };
      }
      if (body.instructions !== undefined) {
        data.instructions = body.instructions || null;
        changes.instructions = "updated";
      }
      if (body.garmentPiece !== undefined) {
        const clash = await db.productionTask.findFirst({
          where: {
            orderId: task.orderId,
            garmentPiece: body.garmentPiece,
            status: { not: "CANCELLED" },
            id: { not: id },
          },
        });
        if (clash) {
          throw new ApiError(409, `Piece "${body.garmentPiece}" already exists on this order.`);
        }
        data.garmentPiece = body.garmentPiece;
      }
      if (Object.keys(data).length === 0) throw new ApiError(400, "Nothing to update.");

      await db.productionTask.update({ where: { id }, data });

      if (data.deadline !== undefined && task.assignedStaffId) {
        await notifyUsers(
          [task.assignedStaffId],
          "DEADLINE_CHANGED",
          "Deadline changed",
          `The deadline of ${taskLabel} has been changed.`,
          id,
          task.orderId
        );
      }
      if (data.instructions !== undefined && task.assignedStaffId) {
        await notifyUsers(
          [task.assignedStaffId],
          "NEW_INSTRUCTIONS",
          "New instructions",
          `New production instructions were added to ${taskLabel}.`,
          id,
          task.orderId
        );
      }
      await logAudit(user, "TASK_UPDATED", "TASK", id, changes);
      break;
    }

    // ---------------------------------------------------------------- CANCEL
    case "CANCEL": {
      await requirePermission("production.cancel");
      if (!body.reason || body.reason.length < 3) {
        throw new ApiError(400, "A cancellation reason is required.");
      }
      await db.productionTask.update({
        where: { id },
        data: { status: "CANCELLED", cancelReason: body.reason },
      });
      await changeTaskStatus(id, task.status, "CANCELLED", user, body.reason);
      if (task.assignedStaffId) {
        await notifyUsers(
          [task.assignedStaffId],
          "TASK_CANCELLED",
          "Task cancelled",
          `${taskLabel} has been cancelled. Reason: ${body.reason}`,
          id,
          task.orderId
        );
      }
      await logAudit(user, "TASK_CANCELLED", "TASK", id, { reason: body.reason });
      break;
    }

    // ---------------------------------------------------------------- BLOCK
    case "BLOCK": {
      if (!body.reason || body.reason.length < 3) {
        throw new ApiError(400, "A reason is required when blocking a task.");
      }
      if (!["ASSIGNED", "ONGOING", "CHANGES_REQUESTED"].includes(task.status)) {
        throw new ApiError(400, `A task with status ${task.status} cannot be blocked.`);
      }
      await db.productionTask.update({
        where: { id },
        data: { status: "BLOCKED", blockedReason: body.reason },
      });
      await changeTaskStatus(id, task.status, "BLOCKED", user, body.reason);
      await notifyManagement(
        "TASK_BLOCKED",
        "Task blocked",
        `${user.name} blocked ${taskLabel}. Reason: ${body.reason}`,
        id,
        task.orderId
      );
      await logAudit(user, "TASK_BLOCKED", "TASK", id, { reason: body.reason });
      break;
    }

    // ---------------------------------------------------------------- UNBLOCK
    case "UNBLOCK": {
      if (task.status !== "BLOCKED") throw new ApiError(400, "This task is not blocked.");
      const newStatus = task.progress > 0 ? "ONGOING" : "ASSIGNED";
      await db.productionTask.update({
        where: { id },
        data: { status: newStatus, blockedReason: null },
      });
      await changeTaskStatus(id, task.status, newStatus, user, body.note ?? undefined);
      if (task.assignedStaffId) {
        await notifyUsers(
          [task.assignedStaffId],
          "BLOCK_RESOLVED",
          "Block resolved",
          `${taskLabel} is no longer blocked. You can resume production.`,
          id,
          task.orderId
        );
      }
      await logAudit(user, "BLOCK_RESOLVED", "TASK", id, { note: body.note ?? null });
      break;
    }

    // ---------------------------------------------------------------- SUBMIT
    case "SUBMIT": {
      if (!["ASSIGNED", "ONGOING", "CHANGES_REQUESTED"].includes(task.status)) {
        throw new ApiError(
          400,
          `A task with status ${task.status} cannot be submitted for inspection.`
        );
      }
      if (!supervisor && task.progress < 100) {
        throw new ApiError(
          400,
          `Progress must reach 100% before submitting for inspection (currently ${task.progress}%).`
        );
      }
      await db.productionTask.update({ where: { id }, data: { status: "AWAITING_INSPECTION" } });
      await changeTaskStatus(id, task.status, "AWAITING_INSPECTION", user, body.note ?? undefined);
      if (body.note) {
        await db.taskComment.create({
          data: {
            taskId: id,
            userId: user.id,
            userName: user.name,
            message: `Submitted for inspection: ${body.note}`,
          },
        });
      }
      await notifyManagement(
        "SUBMITTED_FOR_INSPECTION",
        "Work submitted for inspection",
        `${user.name} submitted ${taskLabel} for inspection.`,
        id,
        task.orderId
      );
      await logAudit(user, "SUBMITTED_FOR_INSPECTION", "TASK", id, { note: body.note ?? null });
      break;
    }

    // ---------------------------------------------------------------- INSPECT
    case "INSPECT": {
      await requirePermission("production.inspect");
      const decision = body.decision ?? "";
      if (!(INSPECTION_DECISIONS as readonly string[]).includes(decision)) {
        throw new ApiError(400, "Invalid inspection decision.");
      }
      if (
        (decision === "APPROVED" || decision === "CHANGES_REQUESTED") &&
        task.status !== "AWAITING_INSPECTION"
      ) {
        throw new ApiError(400, "Only tasks awaiting inspection can be inspected.");
      }
      if (
        decision === "REOPENED" &&
        !["APPROVED", "READY_FOR_PICKUP"].includes(task.status)
      ) {
        throw new ApiError(400, "Only approved or ready-for-pickup tasks can be reopened.");
      }

      if (decision === "APPROVED") {
        await db.$transaction([
          db.productionTask.update({
            where: { id },
            data: {
              status: "APPROVED",
              completedAt: task.completedAt ?? new Date(),
              progress: 100,
            },
          }),
          ...(task.progress < 100
            ? [
                db.taskProgressHistory.create({
                  data: {
                    taskId: id,
                    userId: user.id,
                    userName: user.name,
                    previousProgress: task.progress,
                    newProgress: 100,
                    source: "INSPECTION",
                    note: "Set to 100% on approval",
                  },
                }),
              ]
            : []),
        ]);
        await changeTaskStatus(id, task.status, "APPROVED", user, body.comments ?? undefined);
        if (task.assignedStaffId) {
          await notifyUsers(
            [task.assignedStaffId],
            "TASK_APPROVED",
            "Work approved",
            `${taskLabel} passed quality inspection. Well done!`,
            id,
            task.orderId
          );
        }
      } else if (decision === "CHANGES_REQUESTED") {
        if (!body.comments || body.comments.length < 3) {
          throw new ApiError(400, "Correction instructions are required when requesting changes.");
        }
        await db.productionTask.update({
          where: { id },
          data: { status: "CHANGES_REQUESTED" },
        });
        await changeTaskStatus(id, task.status, "CHANGES_REQUESTED", user, body.comments);
        if (task.assignedStaffId) {
          await notifyUsers(
            [task.assignedStaffId],
            "CORRECTIONS_REQUESTED",
            "Corrections requested",
            `Corrections were requested on ${taskLabel}: ${body.comments}`,
            id,
            task.orderId
          );
        }
      } else {
        await db.productionTask.update({
          where: { id },
          data: { status: "ONGOING", completedAt: null },
        });
        await changeTaskStatus(id, task.status, "ONGOING", user, body.comments ?? undefined);
        if (task.assignedStaffId) {
          await notifyUsers(
            [task.assignedStaffId],
            "TASK_REOPENED",
            "Task reopened",
            `${taskLabel} has been reopened for production.`,
            id,
            task.orderId
          );
        }
      }

      await db.inspectionRecord.create({
        data: {
          taskId: id,
          inspectorId: user.id,
          inspectorName: user.name,
          decision,
          comments: body.comments || null,
        },
      });
      await logAudit(user, "INSPECTION_COMPLETED", "TASK", id, {
        decision,
        comments: body.comments ?? null,
      });
      break;
    }

    // ---------------------------------------------------------------- READY
    case "READY": {
      await requirePermission("production.inspect");
      if (task.status !== "APPROVED") {
        throw new ApiError(
          400,
          "A garment must pass quality inspection (Approved) before it can be ready for pickup."
        );
      }
      await db.productionTask.update({ where: { id }, data: { status: "READY_FOR_PICKUP" } });
      await changeTaskStatus(id, task.status, "READY_FOR_PICKUP", user);
      await notifyManagement(
        "READY_FOR_PICKUP",
        "Ready for pickup",
        `${taskLabel} is approved and ready for customer pickup.`,
        id,
        task.orderId
      );
      if (task.assignedStaffId) {
        await notifyUsers(
          [task.assignedStaffId],
          "READY_FOR_PICKUP",
          "Ready for pickup",
          `${taskLabel} is ready for customer pickup.`,
          id,
          task.orderId
        );
      }
      await logAudit(user, "TASK_READY_FOR_PICKUP", "TASK", id, {});
      break;
    }

    // ---------------------------------------------------------------- DELIVER
    case "DELIVER": {
      await requirePermission("production.inspect");
      if (task.status !== "READY_FOR_PICKUP") {
        throw new ApiError(400, "Only ready-for-pickup tasks can be marked as delivered/collected.");
      }
      await db.productionTask.update({
        where: { id },
        data: { status: "DELIVERED", deliveredAt: new Date() },
      });
      await changeTaskStatus(id, task.status, "DELIVERED", user, body.note ?? undefined);
      await notifyManagement(
        "TASK_DELIVERED",
        "Garment delivered/collected",
        `${taskLabel} has been delivered/collected by the customer.`,
        id,
        task.orderId
      );
      if (task.assignedStaffId) {
        await notifyUsers(
          [task.assignedStaffId],
          "TASK_DELIVERED",
          "Garment delivered",
          `${taskLabel} has been delivered/collected by the customer.`,
          id,
          task.orderId
        );
      }
      await logAudit(user, "TASK_DELIVERED", "TASK", id, { note: body.note ?? null });
      break;
    }
  }

  const refreshed = await loadTask(id);
  return json({ task: serializeTask(refreshed) });
});
