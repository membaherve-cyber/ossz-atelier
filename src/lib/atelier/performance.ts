// OSSZ ATELIER — staff performance & scoring engine.
// All metrics are computed from real recorded data: assignments, task
// completions, inspection results and status history. Weights are configurable
// by Management and must total 100.

import { db } from "@/lib/db";

export type PeriodType = "MONTH" | "QUARTER" | "YEAR";

export type PerformanceRange = {
  type: PeriodType;
  value: string; // "2026-11" | "2026-Q4" | "2026"
  start: Date;
  end: Date;
  label: string;
  months: number;
};

export type StaffMetrics = {
  tasksAssigned: number;
  tasksCompleted: number;
  piecesCompleted: number;
  onTimeCompletions: number;
  overdueTasks: number;
  avgCompletionHours: number | null;
  qualityApprovalRate: number; // 0-100
  correctionsReturned: number;
  blockedTasks: number;
};

export type StaffScores = {
  productivity: number;
  timeliness: number;
  quality: number;
  reliability: number;
  management: number;
};

export type StaffPerformance = {
  staffId: string;
  staffName: string;
  staffEmail: string;
  isActive: boolean;
  metrics: StaffMetrics;
  scores: StaffScores;
  overall: number;
  managementAssessed: boolean;
};

const MONTH_NAMES = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];

export function periodRange(type: PeriodType, value: string): PerformanceRange {
  if (type === "MONTH") {
    const m = /^(\d{4})-(\d{2})$/.exec(value);
    if (!m) throw new Error("Invalid month period. Expected format: YYYY-MM");
    const year = Number(m[1]);
    const month = Number(m[2]);
    const start = new Date(Date.UTC(year, month - 1, 1));
    const end = new Date(Date.UTC(year, month, 1));
    return {
      type, value, start, end,
      label: `${MONTH_NAMES[month - 1]} ${year}`,
      months: 1,
    };
  }
  if (type === "QUARTER") {
    const q = /^(\d{4})-Q([1-4])$/.exec(value.toUpperCase());
    if (!q) throw new Error("Invalid quarter period. Expected format: YYYY-QN");
    const year = Number(q[1]);
    const quarter = Number(q[2]);
    const startMonth = (quarter - 1) * 3;
    const start = new Date(Date.UTC(year, startMonth, 1));
    const end = new Date(Date.UTC(year, startMonth + 3, 1));
    return {
      type, value: `${year}-Q${quarter}`, start, end,
      label: `Q${quarter} ${year}`,
      months: 3,
    };
  }
  const y = /^(\d{4})$/.exec(value);
  if (!y) throw new Error("Invalid year period. Expected format: YYYY");
  const year = Number(y[1]);
  return {
    type, value, start: new Date(Date.UTC(year, 0, 1)), end: new Date(Date.UTC(year + 1, 0, 1)),
    label: `Year ${year}`,
    months: 12,
  };
}

export function defaultPeriod(type: PeriodType, at = new Date()): string {
  if (type === "MONTH") return `${at.getUTCFullYear()}-${String(at.getUTCMonth() + 1).padStart(2, "0")}`;
  if (type === "QUARTER") return `${at.getUTCFullYear()}-Q${Math.floor(at.getUTCMonth() / 3) + 1}`;
  return String(at.getUTCFullYear());
}

async function getConfig() {
  let config = await db.performanceConfig.findUnique({ where: { id: "main" } });
  if (!config) {
    config = await db.performanceConfig.create({ data: { id: "main" } });
  }
  return config;
}

export async function getPerformanceConfig() {
  return getConfig();
}

/** Compute one staff member's metrics & scores for a period. */
export async function computeStaffPerformance(
  staffId: string,
  range: PerformanceRange,
  at: Date = new Date()
): Promise<StaffPerformance> {
  const staff = await db.user.findUnique({ where: { id: staffId } });
  if (!staff) throw new Error("Staff account not found");

  const config = await getConfig();

  // Tasks assigned to this staff member during the period (distinct tasks).
  const assignments = await db.taskAssignment.findMany({
    where: {
      staffId,
      type: { in: ["ASSIGN", "REASSIGN"] },
      createdAt: { gte: range.start, lt: range.end },
    },
    select: { taskId: true },
    distinct: ["taskId"],
  });
  const tasksAssigned = assignments.length;

  // Tasks completed (approved) during the period.
  const completed = await db.productionTask.findMany({
    where: {
      assignedStaffId: staffId,
      completedAt: { gte: range.start, lt: range.end },
    },
    select: { id: true, deadline: true, completedAt: true, createdAt: true },
  });
  const tasksCompleted = completed.length;
  const onTime = completed.filter(
    (t) => t.deadline && t.completedAt && t.completedAt.getTime() <= t.deadline.getTime()
  ).length;

  // Overdue: deadline fell inside the period and the work was not finished on
  // time (still open, or completed after the deadline). Prisma cannot compare
  // two columns directly, so the final comparison is done in memory.
  const deadlineTasks = await db.productionTask.findMany({
    where: {
      assignedStaffId: staffId,
      deadline: { gte: range.start, lt: new Date(Math.min(at.getTime(), range.end.getTime())) },
    },
    select: { completedAt: true, deadline: true },
  });
  const overdueCount = deadlineTasks.filter(
    (t) => !t.completedAt || (t.deadline && t.completedAt.getTime() > t.deadline.getTime())
  ).length;

  // Average completion time (hours), from last assignment to approval.
  let avgCompletionHours: number | null = null;
  if (tasksCompleted > 0) {
    const completedIds = completed.map((t) => t.id);
    const lastAssignments = await db.taskAssignment.findMany({
      where: { taskId: { in: completedIds }, staffId, type: { in: ["ASSIGN", "REASSIGN"] } },
      orderBy: { createdAt: "desc" },
    });
    const assignMap = new Map<string, Date>();
    for (const a of lastAssignments) {
      if (!assignMap.has(a.taskId)) assignMap.set(a.taskId, a.createdAt);
    }
    const durations: number[] = [];
    for (const t of completed) {
      const started = assignMap.get(t.id) ?? t.createdAt;
      if (t.completedAt) durations.push((t.completedAt.getTime() - started.getTime()) / 3_600_000);
    }
    if (durations.length > 0) {
      avgCompletionHours = Math.round((durations.reduce((a, b) => a + b, 0) / durations.length) * 10) / 10;
    }
  }

  // Quality: of tasks completed in the period, share approved without any
  // corrections request across the task's whole history.
  let cleanApprovals = 0;
  if (tasksCompleted > 0) {
    const corrections = await db.inspectionRecord.findMany({
      where: {
        taskId: { in: completed.map((t) => t.id) },
        decision: "CHANGES_REQUESTED",
      },
      select: { taskId: true },
      distinct: ["taskId"],
    });
    const correctedTaskIds = new Set(corrections.map((c) => c.taskId));
    cleanApprovals = completed.filter((t) => !correctedTaskIds.has(t.id)).length;
  }

  // Tasks currently mapped to this staff member (inspection records and status
  // changes have no Prisma relation to tasks, so they are matched in memory).
  const staffTaskIds = new Set(
    (
      await db.productionTask.findMany({
        where: { assignedStaffId: staffId },
        select: { id: true },
      })
    ).map((t) => t.id)
  );

  // Corrections returned during the period (inspection decisions on this staff's tasks).
  const correctionsInPeriod = await db.inspectionRecord.findMany({
    where: {
      decision: "CHANGES_REQUESTED",
      createdAt: { gte: range.start, lt: range.end },
    },
    select: { taskId: true },
  });
  const correctionsReturned = correctionsInPeriod.filter((c) => staffTaskIds.has(c.taskId)).length;

  // Blocked occurrences during the period (distinct tasks).
  const blockedInPeriod = await db.taskStatusChange.findMany({
    where: {
      toStatus: "BLOCKED",
      createdAt: { gte: range.start, lt: range.end },
    },
    select: { taskId: true },
    distinct: ["taskId"],
  });
  const blockedTasks = blockedInPeriod.filter((b) => staffTaskIds.has(b.taskId)).length;

  // Management assessment for the period.
  const assessment = await db.staffAssessment.findUnique({
    where: { staffId_period: { staffId, period: range.value } },
  });

  const months = Math.max(1, range.months);
  const effectiveTarget = Math.max(1, config.pieceTarget * months);

  const productivity = Math.min(100, Math.round((tasksCompleted / effectiveTarget) * 100));
  const timeliness = tasksCompleted > 0 ? Math.round((onTime / tasksCompleted) * 100) : 0;
  const quality = tasksCompleted > 0 ? Math.round((cleanApprovals / tasksCompleted) * 100) : 0;
  const reliability =
    tasksAssigned > 0
      ? Math.max(0, Math.round(100 - ((blockedTasks + correctionsReturned) / tasksAssigned) * 100))
      : 100;
  const management = assessment ? Math.max(0, Math.min(100, Math.round(assessment.score))) : 0;

  const overall =
    (productivity * config.productivityWeight +
      timeliness * config.timelyWeight +
      quality * config.qualityWeight +
      reliability * config.reliabilityWeight +
      management * config.managementWeight) /
    100;
  const overallRounded = Math.round(overall * 10) / 10;

  return {
    staffId,
    staffName: staff.name,
    staffEmail: staff.email,
    isActive: staff.isActive,
    metrics: {
      tasksAssigned,
      tasksCompleted,
      piecesCompleted: tasksCompleted, // one task = one garment piece
      onTimeCompletions: onTime,
      overdueTasks: overdueCount,
      avgCompletionHours,
      qualityApprovalRate: quality,
      correctionsReturned,
      blockedTasks,
    },
    scores: { productivity, timeliness, quality, reliability, management },
    overall: overallRounded,
    managementAssessed: !!assessment,
  };
}

/** Performance for every staff account (active and inactive) for a period. */
export async function listStaffPerformance(
  type: PeriodType,
  value: string,
  at: Date = new Date()
): Promise<{ range: PerformanceRange; staff: StaffPerformance[] }> {
  const range = periodRange(type, value);
  const users = await db.user.findMany({
    where: { role: "STAFF" },
    orderBy: { name: "asc" },
  });
  const staff = await Promise.all(
    users.map((u) => computeStaffPerformance(u.id, range, at).catch(() => null))
  );
  return {
    range,
    staff: staff.filter((s): s is StaffPerformance => s !== null).sort((a, b) => b.overall - a.overall),
  };
}
