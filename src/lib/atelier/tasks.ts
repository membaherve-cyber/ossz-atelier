// OSSZ ATELIER — production task domain helpers (server-side).

import { db } from "@/lib/db";
import type { SessionUser, TaskStatus } from "@/lib/atelier/constants";
import { stageAppliesTo, taskIsOverdue } from "@/lib/atelier/constants";
import { logAudit } from "@/lib/atelier/audit";

// ---------------------------------------------------------------------------
// Sequential system-generated codes (TASK-0001, CUST-0001, ...)
// ---------------------------------------------------------------------------

async function nextCode(prefix: string, count: () => Promise<number>): Promise<string> {
  const n = (await count()) + 1;
  return `${prefix}-${String(n).padStart(4, "0")}`;
}

export async function nextTaskCode(): Promise<string> {
  return nextCode("TASK", () => db.productionTask.count());
}

export async function nextCustomerCode(): Promise<string> {
  return nextCode("CUST", () => db.customer.count());
}

// ---------------------------------------------------------------------------
// Stages
// ---------------------------------------------------------------------------

/** Active stages applicable to a task category, in workflow order. */
export async function getApplicableStages(category: string) {
  const stages = await db.productionStage.findMany({
    where: { isActive: true },
    orderBy: { sortOrder: "asc" },
  });
  return stages.filter((s) => stageAppliesTo(s.appliesTo, category));
}

/**
 * Derive task progress from its applicable stage states:
 * COMPLETED = 1, IN_PROGRESS = 0.5, others = 0, weighted by stage weight.
 * NOT_APPLICABLE stages are excluded.
 */
export function deriveProgress(
  stages: { id: string; weight: number }[],
  states: { stageId: string; state: string }[]
): number | null {
  const stateMap = new Map(states.map((s) => [s.stageId, s.state]));
  let totalWeight = 0;
  let score = 0;
  for (const stage of stages) {
    const state = stateMap.get(stage.id) ?? "NOT_STARTED";
    if (state === "NOT_APPLICABLE") continue;
    totalWeight += stage.weight;
    if (state === "COMPLETED") score += stage.weight;
    else if (state === "IN_PROGRESS") score += stage.weight * 0.5;
  }
  if (totalWeight === 0) return null;
  return Math.round((score / totalWeight) * 100);
}

/**
 * Recalculate task progress from stage states. Progress never decreases from
 * stage updates (manual corrections remain possible with an explicit history
 * entry). Returns the (possibly unchanged) progress value.
 */
export async function recalculateProgress(
  taskId: string,
  user: SessionUser,
  note?: string
): Promise<number> {
  const task = await db.productionTask.findUnique({ where: { id: taskId } });
  if (!task) return 0;
  const stages = await getApplicableStages(task.category);
  const states = await db.taskStageState.findMany({ where: { taskId } });
  const derived = deriveProgress(stages, states);
  if (derived === null || derived <= task.progress) return task.progress;
  if (derived > task.progress) {
    await db.$transaction([
      db.productionTask.update({ where: { id: taskId }, data: { progress: derived } }),
      db.taskProgressHistory.create({
        data: {
          taskId,
          userId: user.id,
          userName: user.name,
          previousProgress: task.progress,
          newProgress: derived,
          source: "STAGE",
          note: note ?? "Automatic update from production checklist",
        },
      }),
    ]);
    return derived;
  }
  return task.progress;
}

// ---------------------------------------------------------------------------
// Status workflow
// ---------------------------------------------------------------------------

/** Record an immutable status change (with audit entry). */
export async function changeTaskStatus(
  taskId: string,
  fromStatus: string,
  toStatus: TaskStatus,
  user: SessionUser,
  reason?: string
): Promise<void> {
  await db.taskStatusChange.create({
    data: {
      taskId,
      userId: user.id,
      userName: user.name,
      fromStatus,
      toStatus,
      reason: reason ?? null,
    },
  });
  await logAudit(user, "TASK_STATUS_CHANGED", "TASK", taskId, { fromStatus, toStatus, reason });
}

/** Staff can only touch their own tasks; Management/Admin can touch any. */
export function canAccessTask(user: SessionUser, task: { assignedStaffId: string | null }): boolean {
  if (user.role === "MANAGEMENT" || user.role === "ADMIN") return true;
  return task.assignedStaffId === user.id;
}

// ---------------------------------------------------------------------------
// Serialization helpers (shared API shapes)
// ---------------------------------------------------------------------------

export type TaskRow = {
  id: string;
  code: string;
  orderId: string;
  orderNumber: string;
  customerId: string;
  customerName: string;
  garmentPiece: string;
  category: string;
  instructions: string | null;
  status: string;
  progress: number;
  priority: string;
  deadline: string | null;
  assignedStaffId: string | null;
  assignedStaffName: string | null;
  createdAt: Date;
  updatedAt: Date;
  completedAt: Date | null;
  deliveredAt: Date | null;
  cancelReason: string | null;
  blockedReason: string | null;
};

type TaskWithRelations = {
  id: string;
  code: string;
  orderId: string;
  garmentPiece: string;
  category: string;
  instructions: string | null;
  status: string;
  progress: number;
  priority: string;
  deadline: Date | null;
  assignedStaffId: string | null;
  createdAt: Date;
  updatedAt: Date;
  completedAt: Date | null;
  deliveredAt: Date | null;
  cancelReason: string | null;
  blockedReason: string | null;
  order: { orderNumber: string; customerId: string; customer: { name: string } };
  assignedStaff: { name: string } | null;
};

export function serializeTask(t: TaskWithRelations) {
  return {
    id: t.id,
    code: t.code,
    orderId: t.orderId,
    orderNumber: t.order.orderNumber,
    customerId: t.order.customerId,
    customerName: t.order.customer.name,
    garmentPiece: t.garmentPiece,
    category: t.category,
    instructions: t.instructions,
    status: t.status,
    progress: t.progress,
    priority: t.priority,
    deadline: t.deadline?.toISOString() ?? null,
    assignedStaffId: t.assignedStaffId,
    assignedStaffName: t.assignedStaff?.name ?? null,
    createdAt: t.createdAt.toISOString(),
    updatedAt: t.updatedAt.toISOString(),
    completedAt: t.completedAt?.toISOString() ?? null,
    deliveredAt: t.deliveredAt?.toISOString() ?? null,
    cancelReason: t.cancelReason,
    blockedReason: t.blockedReason,
    overdue: taskIsOverdue(t.deadline, t.status),
  };
}

export const taskInclude = {
  order: { include: { customer: true } },
  assignedStaff: true,
} as const;
