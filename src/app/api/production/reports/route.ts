import { db } from "@/lib/db";
import { json, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { computeOrderProductionSummary, OVERDUE_STATUSES } from "@/lib/production";

/**
 * GET /api/production/reports — production performance metrics computed from
 * recorded system events. Empty metrics are returned as null (no invented data).
 * Requires production.reports.
 */
export const GET = withApi(async () => {
  await requirePermission("production.reports");

  const tasks = await db.productionTask.findMany({
    include: {
      order: { select: { id: true, reference: true, customer: { select: { name: true } } } },
      assignedStaff: { select: { id: true, name: true } },
      inspections: { select: { result: true, inspectedAt: true } },
    },
  });

  const events = await db.productionEvent.findMany({
    orderBy: { createdAt: "asc" },
    select: { taskId: true, eventType: true, createdAt: true },
  });

  // ---- Cycle-time metrics (from recorded events) -----------------------------

  const approvedEvents = events.filter((e) => e.eventType === "PIECE_APPROVED");
  const submitEvents = events.filter((e) => e.eventType === "SUBMITTED_FOR_INSPECTION");
  const inspectionEvents = events.filter((e) =>
    ["INSPECTION_COMPLETED"].includes(e.eventType)
  );

  // Average assignment → approval, per task (last approval event wins).
  const assignmentToApproval: number[] = [];
  for (const task of tasks) {
    if (task.status !== "APPROVED" || !task.assignedAt) continue;
    const approvals = approvedEvents.filter(
      (e) => e.taskId === task.id
    );
    const last = approvals[approvals.length - 1];
    if (last) assignmentToApproval.push(last.createdAt.getTime() - task.assignedAt.getTime());
  }

  // Average submission → inspection, per submitted round.
  const submissionToInspection: number[] = [];
  for (const task of tasks) {
    const submits = submitEvents.filter((e) => e.taskId === task.id);
    const inspections = inspectionEvents.filter((e) => e.taskId === task.id);
    for (const insp of inspections) {
      const submit = [...submits]
        .filter((s) => s.createdAt.getTime() <= insp.createdAt.getTime())
        .sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime())[0];
      if (submit) submissionToInspection.push(insp.createdAt.getTime() - submit.createdAt.getTime());
    }
  }

  const avg = (arr: number[]) =>
    arr.length === 0 ? null : Math.round(arr.reduce((s, v) => s + v, 0) / arr.length);

  const reworkTasks = tasks.filter((t) =>
    t.inspections.some((i) => i.result === "CHANGES_REQUESTED")
  );

  // ---- Per-order progress ------------------------------------------------------

  const orderIds = [...new Set(tasks.map((t) => t.orderId))];
  const perOrder = orderIds
    .map((orderId) => {
      const orderTasks = tasks.filter((t) => t.orderId === orderId);
      const summary = computeOrderProductionSummary(orderTasks);
      return {
        orderId,
        reference: orderTasks[0]!.order.reference,
        customer: orderTasks[0]!.order.customer.name,
        ...summary,
      };
    })
    .sort((a, b) => a.reference.localeCompare(b.reference));

  // ---- Per-staff performance ----------------------------------------------------

  const staffMap = new Map<string, { name: string; assigned: number; approved: number; reworked: number; cycleTimes: number[] }>();
  for (const task of tasks) {
    if (!task.assignedStaff) continue;
    const entry = staffMap.get(task.assignedStaff.id) ?? {
      name: task.assignedStaff.name,
      assigned: 0,
      approved: 0,
      reworked: 0,
      cycleTimes: [],
    };
    entry.assigned += 1;
    if (task.status === "APPROVED") {
      entry.approved += 1;
      if (task.assignedAt) {
        const approvals = approvedEvents.filter((e) => e.taskId === task.id);
        const last = approvals[approvals.length - 1];
        if (last) entry.cycleTimes.push(last.createdAt.getTime() - task.assignedAt.getTime());
      }
    }
    if (task.inspections.some((i) => i.result === "CHANGES_REQUESTED")) entry.reworked += 1;
    staffMap.set(task.assignedStaff.id, entry);
  }
  const perStaff = [...staffMap.entries()].map(([staffId, v]) => ({
    staffId,
    name: v.name,
    assigned: v.assigned,
    approved: v.approved,
    reworked: v.reworked,
    avgCycleMs: avg(v.cycleTimes),
  }));

  const overdueTasks = tasks.filter(
    (t) => t.dueAt && t.dueAt.getTime() < Date.now() && OVERDUE_STATUSES.includes(t.status as never)
  );

  return json({
    reports: {
      totals: {
        tasks: tasks.length,
        approved: tasks.filter((t) => t.status === "APPROVED").length,
        awaitingInspection: tasks.filter((t) => t.status === "AWAITING_INSPECTION").length,
        reworkTasks: reworkTasks.length,
        reworkRate: tasks.length === 0 ? null : Math.round((reworkTasks.length / tasks.length) * 1000) / 10,
        overdue: overdueTasks.length,
      },
      avgAssignmentToApprovalMs: avg(assignmentToApproval),
      avgSubmissionToInspectionMs: avg(submissionToInspection),
      perOrder,
      perStaff,
      overdue: overdueTasks.map((t) => ({
        taskId: t.id,
        pieceName: t.pieceName,
        reference: t.order.reference,
        staff: t.assignedStaff?.name ?? null,
        dueAt: t.dueAt,
        status: t.status,
      })),
    },
  });
});
