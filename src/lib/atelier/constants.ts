// OSSZ ATELIER — shared domain constants & types (safe for client and server).
// Values are stored as strings in the database and validated here.

import type { Permission, Role } from "@/lib/permissions";

// --- Task status workflow ---------------------------------------------------

export const TASK_STATUSES = [
  "UNASSIGNED",
  "ASSIGNED",
  "ONGOING",
  "AWAITING_INSPECTION",
  "CHANGES_REQUESTED",
  "APPROVED",
  "READY_FOR_PICKUP",
  "DELIVERED",
  "BLOCKED",
  "CANCELLED",
] as const;
export type TaskStatus = (typeof TASK_STATUSES)[number];

export const STATUS_LABELS: Record<TaskStatus, string> = {
  UNASSIGNED: "Unassigned",
  ASSIGNED: "Assigned",
  ONGOING: "Ongoing",
  AWAITING_INSPECTION: "Awaiting Inspection",
  CHANGES_REQUESTED: "Changes Requested",
  APPROVED: "Approved",
  READY_FOR_PICKUP: "Ready for Pickup",
  DELIVERED: "Delivered/Collected",
  BLOCKED: "Blocked",
  CANCELLED: "Cancelled",
};

/** Statuses in which a task is considered finished (no further actions). */
export const TERMINAL_STATUSES: TaskStatus[] = ["DELIVERED", "CANCELLED"];

/** Statuses in which work still needs to happen or the order is not closed. */
export const ACTIVE_STATUSES: TaskStatus[] = [
  "ASSIGNED",
  "ONGOING",
  "AWAITING_INSPECTION",
  "CHANGES_REQUESTED",
  "APPROVED",
  "READY_FOR_PICKUP",
  "BLOCKED",
];

/** Statuses excluded from overdue computation. */
export const OVERDUE_EXEMPT_STATUSES: TaskStatus[] = ["DELIVERED", "CANCELLED"];

// --- Priorities --------------------------------------------------------------

export const PRIORITIES = ["LOW", "NORMAL", "HIGH", "URGENT"] as const;
export type Priority = (typeof PRIORITIES)[number];
export const PRIORITY_LABELS: Record<Priority, string> = {
  LOW: "Low",
  NORMAL: "Normal",
  HIGH: "High",
  URGENT: "Urgent",
};

// --- Production checklist stages ---------------------------------------------

export const STAGE_STATES = ["NOT_STARTED", "IN_PROGRESS", "COMPLETED", "NOT_APPLICABLE"] as const;
export type StageState = (typeof STAGE_STATES)[number];
export const STAGE_STATE_LABELS: Record<StageState, string> = {
  NOT_STARTED: "Not Started",
  IN_PROGRESS: "In Progress",
  COMPLETED: "Completed",
  NOT_APPLICABLE: "Not Applicable",
};

export const TASK_CATEGORIES = ["FULL", "CUTTING", "SEWING", "FINISHING", "IRONING", "OTHER"] as const;
export type TaskCategory = (typeof TASK_CATEGORIES)[number];
export const CATEGORY_LABELS: Record<TaskCategory, string> = {
  FULL: "Full Production",
  CUTTING: "Cutting Only",
  SEWING: "Sewing/Stitching Only",
  FINISHING: "Finishing",
  IRONING: "Ironing Only",
  OTHER: "Other Activity",
};

/** All stages apply to "ALL"; a stage may also be scoped to specific categories. */
export const APPLIES_TO_ALL = "ALL";

export function stageAppliesTo(appliesTo: string, category: string): boolean {
  if (appliesTo === APPLIES_TO_ALL) return true;
  return appliesTo
    .split(",")
    .map((c) => c.trim())
    .filter(Boolean)
    .includes(category);
}

// --- Quality control ----------------------------------------------------------

export const INSPECTION_DECISIONS = ["APPROVED", "CHANGES_REQUESTED", "REOPENED"] as const;
export type InspectionDecision = (typeof INSPECTION_DECISIONS)[number];
export const INSPECTION_DECISION_LABELS: Record<InspectionDecision, string> = {
  APPROVED: "Approved",
  CHANGES_REQUESTED: "Changes Requested",
  REOPENED: "Reopened",
};

// --- Notifications -------------------------------------------------------------

export const NOTIFICATION_TYPES = [
  "TASK_ASSIGNED",
  "TASK_REASSIGNED",
  "DEADLINE_CHANGED",
  "NEW_INSTRUCTIONS",
  "CORRECTIONS_REQUESTED",
  "BLOCK_RESOLVED",
  "TASK_APPROVED",
  "TASK_REOPENED",
  "SUBMITTED_FOR_INSPECTION",
  "TASK_OVERDUE",
  "ISSUE_REPORTED",
  "TASK_BLOCKED",
  "READY_FOR_PICKUP",
  "TASK_DELIVERED",
  "TASK_CANCELLED",
] as const;
export type NotificationType = (typeof NOTIFICATION_TYPES)[number];

// --- Performance & incentives ---------------------------------------------------

export const INCENTIVE_PERIOD_TYPES = [
  "MONTHLY",
  "QUARTERLY",
  "ANNUAL",
  "QUALITY",
  "ON_TIME",
  "CUSTOM",
] as const;
export type IncentivePeriodType = (typeof INCENTIVE_PERIOD_TYPES)[number];
export const INCENTIVE_PERIOD_LABELS: Record<IncentivePeriodType, string> = {
  MONTHLY: "Monthly productivity",
  QUARTERLY: "Quarterly performance",
  ANNUAL: "Annual performance",
  QUALITY: "Quality achievements",
  ON_TIME: "On-time completion",
  CUSTOM: "Management-defined target",
};

export const INCENTIVE_STATUSES = ["PENDING", "APPROVED", "REJECTED"] as const;
export type IncentiveStatus = (typeof INCENTIVE_STATUSES)[number];

export const PAYMENT_STATUSES = ["UNPAID", "PAID"] as const;
export type PaymentStatus = (typeof PAYMENT_STATUSES)[number];

// --- Shared API types ------------------------------------------------------------

export type SessionUser = {
  id: string;
  code: string | null;
  name: string;
  email: string;
  role: Role;
  permissions: Permission[];
};

export function isTaskStatus(value: string): value is TaskStatus {
  return (TASK_STATUSES as readonly string[]).includes(value);
}

export function isPriority(value: string): value is Priority {
  return (PRIORITIES as readonly string[]).includes(value);
}

export function isStageState(value: string): value is StageState {
  return (STAGE_STATES as readonly string[]).includes(value);
}

export function isTaskCategory(value: string): value is TaskCategory {
  return (TASK_CATEGORIES as readonly string[]).includes(value);
}

/** A task is overdue when its deadline has passed and it is not delivered/cancelled. */
export function taskIsOverdue(
  deadline: string | Date | null | undefined,
  status: string,
  at: Date = new Date()
): boolean {
  if (!deadline || (OVERDUE_EXEMPT_STATUSES as string[]).includes(status)) return false;
  return new Date(deadline).getTime() < at.getTime();
}
