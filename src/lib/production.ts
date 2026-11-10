import type { Prisma } from "@prisma/client";
import { hasPermission, type Permission } from "@/lib/permissions";

// ---------------------------------------------------------------------------
// Task status workflow
// ---------------------------------------------------------------------------

export const TASK_STATUSES = [
  "UNASSIGNED",
  "ASSIGNED",
  "IN_PROGRESS",
  "AWAITING_INSPECTION",
  "CHANGES_REQUESTED",
  "APPROVED",
  "BLOCKED",
  "CANCELLED",
] as const;
export type TaskStatus = (typeof TASK_STATUSES)[number];

/** Statuses in which a task is considered "active production" (not finished, not unassigned). */
export const ACTIVE_STATUSES: TaskStatus[] = [
  "ASSIGNED",
  "IN_PROGRESS",
  "CHANGES_REQUESTED",
  "AWAITING_INSPECTION",
  "BLOCKED",
];

/** Statuses in which a task with a past deadline is considered overdue. */
export const OVERDUE_STATUSES: TaskStatus[] = [
  "ASSIGNED",
  "IN_PROGRESS",
  "CHANGES_REQUESTED",
  "AWAITING_INSPECTION",
  "BLOCKED",
];

export function isOverdue(task: { dueAt: Date | null; status: string }): boolean {
  if (!task.dueAt) return false;
  if (!OVERDUE_STATUSES.includes(task.status as TaskStatus)) return false;
  return task.dueAt.getTime() < Date.now();
}

// ---------------------------------------------------------------------------
// Event types (audit trail)
// ---------------------------------------------------------------------------

export const EVENT_TYPES = [
  "TASK_CREATED",
  "PIECE_ASSIGNED",
  "TASK_REASSIGNED",
  "PRODUCTION_STARTED",
  "PROGRESS_UPDATED",
  "PROGRESS_CORRECTED",
  "PRODUCTION_BLOCKED",
  "BLOCKER_RESOLVED",
  "SUBMITTED_FOR_INSPECTION",
  "INSPECTION_COMPLETED",
  "CORRECTIONS_REQUESTED",
  "PIECE_APPROVED",
  "TASK_REOPENED",
  "TASK_CANCELLED",
  "DEADLINE_CHANGED",
] as const;
export type EventType = (typeof EVENT_TYPES)[number];

// ---------------------------------------------------------------------------
// Audit events
// ---------------------------------------------------------------------------

export async function recordEvent(
  tx: Prisma.TransactionClient,
  data: {
    taskId: string;
    orderId: string;
    eventType: EventType;
    actorId: string;
    previousValue?: string | null;
    newValue?: string | null;
    reason?: string | null;
  }
): Promise<void> {
  await tx.productionEvent.create({
    data: {
      taskId: data.taskId,
      orderId: data.orderId,
      eventType: data.eventType,
      actorId: data.actorId,
      previousValue: data.previousValue ?? null,
      newValue: data.newValue ?? null,
      reason: data.reason ?? null,
    },
  });
}

// ---------------------------------------------------------------------------
// Notifications
// ---------------------------------------------------------------------------

export async function userIdsWithPermission(
  tx: Prisma.TransactionClient,
  permission: Permission
): Promise<string[]> {
  const users = await tx.user.findMany({
    where: { isActive: true },
    select: { id: true, role: true },
  });
  return users.filter((u) => hasPermission(u.role, permission)).map((u) => u.id);
}

export async function notifyUsers(
  tx: Prisma.TransactionClient,
  userIds: string[],
  data: {
    title: string;
    body?: string | null;
    taskId?: string | null;
    orderId?: string | null;
    type: string;
  }
): Promise<void> {
  const unique = [...new Set(userIds)];
  if (unique.length === 0) return;
  await tx.notification.createMany({
    data: unique.map((userId) => ({
      userId,
      title: data.title,
      body: data.body ?? null,
      taskId: data.taskId ?? null,
      orderId: data.orderId ?? null,
      type: data.type,
    })),
  });
}

// ---------------------------------------------------------------------------
// Order production summary
// ---------------------------------------------------------------------------

export type OrderProductionSummary = {
  totalPieces: number;
  unassigned: number;
  assigned: number;
  inProgress: number;
  awaitingInspection: number;
  changesRequested: number;
  approved: number;
  blocked: number;
  cancelled: number;
  overdue: number;
  /** Average progress of all non-cancelled pieces (0-100, 2-decimal precision). */
  overallProgress: number;
  /** Share of non-cancelled pieces that passed inspection (0-100). */
  approvedRatio: number;
  productionComplete: boolean;
  staffIds: string[];
};

export function computeOrderProductionSummary(
  tasks: Array<{
    status: string;
    progressPercentage: number;
    assignedStaffId: string | null;
    dueAt: Date | null;
  }>
): OrderProductionSummary {
  const count = (s: TaskStatus) => tasks.filter((t) => t.status === s).length;
  const nonCancelled = tasks.filter((t) => t.status !== "CANCELLED");
  const required = nonCancelled.length;
  const overallProgress =
    required === 0
      ? 0
      : Math.round(
          (nonCancelled.reduce((sum, t) => sum + t.progressPercentage, 0) / required) * 100
        ) / 100;
  const approvedCount = count("APPROVED");
  const approvedRatio =
    required === 0 ? 0 : Math.round((approvedCount / required) * 10000) / 100;
  return {
    totalPieces: tasks.length,
    unassigned: count("UNASSIGNED"),
    assigned: count("ASSIGNED"),
    inProgress: count("IN_PROGRESS"),
    awaitingInspection: count("AWAITING_INSPECTION"),
    changesRequested: count("CHANGES_REQUESTED"),
    approved: approvedCount,
    blocked: count("BLOCKED"),
    cancelled: count("CANCELLED"),
    overdue: tasks.filter((t) => isOverdue(t)).length,
    overallProgress,
    approvedRatio,
    productionComplete: required > 0 && approvedCount === required,
    staffIds: [...new Set(nonCancelled.map((t) => t.assignedStaffId).filter((x): x is string => !!x))],
  };
}

/**
 * Keep the order lifecycle consistent with production:
 *  - any active production -> IN_PRODUCTION (from PENDING/CONFIRMED)
 *  - all required pieces approved -> READY (from PENDING/CONFIRMED/IN_PRODUCTION)
 */
export async function syncOrderStatus(tx: Prisma.TransactionClient, orderId: string): Promise<void> {
  const order = await tx.order.findUnique({ where: { id: orderId } });
  if (!order) return;
  if (["CANCELLED", "DISPATCHED", "COMPLETED"].includes(order.status)) return;
  const tasks = await tx.productionTask.findMany({
    where: { orderId },
    select: { status: true, progressPercentage: true, assignedStaffId: true, dueAt: true },
  });
  const summary = computeOrderProductionSummary(tasks);
  if (summary.productionComplete) {
    if (order.status !== "READY") {
      await tx.order.update({ where: { id: orderId }, data: { status: "READY" } });
    }
  } else if (
    summary.totalPieces - summary.cancelled > 0 &&
    summary.unassigned < summary.totalPieces - summary.cancelled &&
    ["PENDING", "CONFIRMED"].includes(order.status)
  ) {
    await tx.order.update({ where: { id: orderId }, data: { status: "IN_PRODUCTION" } });
  }
}

// ---------------------------------------------------------------------------
// Serialization helpers shared by API routes
// ---------------------------------------------------------------------------

/** Fetch the "next" production reference counter for order references. */
export async function nextOrderReference(tx: Prisma.TransactionClient): Promise<string> {
  const count = await tx.order.count();
  // Guard against reference collisions with pre-existing data.
  let n = count + 1;
  for (let i = 0; i < 1000; i++) {
    const reference = `OSSZ-ORD-${String(n).padStart(3, "0")}`;
    const existing = await tx.order.findUnique({ where: { reference } });
    if (!existing) return reference;
    n += 1;
  }
  throw new Error("Unable to allocate order reference");
}
