// OSSZ ATELIER — production reports for a period (month or year).
// GET /api/atelier/reports?type=MONTH|YEAR&value=2026-11  (production.reports)
// Combines the staff performance engine with direct order/task aggregates.

import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseQuery, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { defaultPeriod, listStaffPerformance, periodRange, type PeriodType } from "@/lib/atelier/performance";

const PERIOD_PATTERNS: Record<PeriodType, { pattern: RegExp; expected: string }> = {
  MONTH: { pattern: /^\d{4}-(0[1-9]|1[0-2])$/, expected: "YYYY-MM (e.g. 2026-11)" },
  QUARTER: { pattern: /^\d{4}-Q[1-4]$/, expected: "YYYY-QN (e.g. 2026-Q4)" },
  YEAR: { pattern: /^\d{4}$/, expected: "YYYY (e.g. 2026)" },
};

const reportQuerySchema = z.object({
  type: z.enum(["MONTH", "YEAR"]).default("MONTH"),
  value: z.string().trim().optional(),
});

export const GET = withApi(async (req: Request) => {
  await requirePermission("production.reports");
  const q = parseQuery(req, reportQuerySchema);

  const value = q.value?.trim() || defaultPeriod(q.type);
  const check = PERIOD_PATTERNS[q.type];
  if (!check.pattern.test(value)) {
    throw new ApiError(400, `Invalid period value. Expected format: ${check.expected}.`);
  }

  const range = periodRange(q.type, value);
  const { staff } = await listStaffPerformance(q.type, value);

  const [ordersRegistered, deliveredTasks, completedTasks] = await Promise.all([
    db.order.count({
      where: { createdAt: { gte: range.start, lt: range.end } },
    }),
    db.productionTask.findMany({
      where: { deliveredAt: { gte: range.start, lt: range.end } },
      select: { orderId: true },
      distinct: ["orderId"],
    }),
    db.productionTask.findMany({
      where: { completedAt: { gte: range.start, lt: range.end } },
      select: { completedAt: true, deadline: true },
    }),
  ]);

  const tasksCompleted = completedTasks.length;
  const onTime = completedTasks.filter(
    (t) => t.deadline && t.completedAt && t.completedAt.getTime() <= t.deadline.getTime()
  ).length;

  return json({
    range: { type: range.type, value: range.value, label: range.label, months: range.months },
    staff,
    summary: {
      ordersRegistered,
      ordersDelivered: deliveredTasks.length,
      tasksCompleted,
      piecesCompleted: tasksCompleted, // one task = one garment piece
      onTimeCompletionRate:
        tasksCompleted > 0 ? Math.round((onTime / tasksCompleted) * 100) : 0,
    },
  });
});
