// OSSZ ATELIER — staff performance overview for a period (month / quarter / year).
// GET /api/atelier/performance?type=MONTH|QUARTER|YEAR&value=2026-11

import { z } from "zod";
import { ApiError, json, parseQuery, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { defaultPeriod, listStaffPerformance, type PeriodType } from "@/lib/atelier/performance";

const PERIOD_PATTERNS: Record<PeriodType, { pattern: RegExp; expected: string }> = {
  MONTH: { pattern: /^\d{4}-(0[1-9]|1[0-2])$/, expected: "YYYY-MM (e.g. 2026-11)" },
  QUARTER: { pattern: /^\d{4}-Q[1-4]$/, expected: "YYYY-QN (e.g. 2026-Q4)" },
  YEAR: { pattern: /^\d{4}$/, expected: "YYYY (e.g. 2026)" },
};

const listQuerySchema = z.object({
  type: z.enum(["MONTH", "QUARTER", "YEAR"]).default("MONTH"),
  value: z.string().trim().optional(),
});

export const GET = withApi(async (req: Request) => {
  await requirePermission("performance.view");
  const q = parseQuery(req, listQuerySchema);

  const value = q.value?.trim() || defaultPeriod(q.type);
  const check = PERIOD_PATTERNS[q.type];
  if (!check.pattern.test(value)) {
    throw new ApiError(400, `Invalid period value. Expected format: ${check.expected}.`);
  }

  const { range, staff } = await listStaffPerformance(q.type, value);

  return json({
    range: { type: range.type, value: range.value, label: range.label, months: range.months },
    staff,
  });
});
