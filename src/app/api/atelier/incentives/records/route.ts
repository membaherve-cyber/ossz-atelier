// OSSZ ATELIER — incentive evaluation records.
// GET   /api/atelier/incentives/records?period=&schemeId=   (incentives.view)
// POST  /api/atelier/incentives/records                     (incentives.prepare — Admin or Management)
//        Body: { schemeId, period } — generates (or refreshes) PENDING
//        recommendations from the live performance engine. Records already
//        APPROVED or REJECTED are never touched by regeneration.

import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, parseQuery, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { listStaffPerformance, type PeriodType } from "@/lib/atelier/performance";

type RecordPayload = {
  id: string;
  schemeId: string;
  schemeName: string;
  staffId: string;
  staffName: string;
  period: string;
  performanceScore: number;
  eligible: boolean;
  proposedAmount: number;
  approvedAmount: number | null;
  status: string;
  approvedByName: string | null;
  approvalComments: string | null;
  managementComments: string | null;
  paymentStatus: string;
  paymentDate: string | null;
};

type RecordRow = {
  id: string;
  schemeId: string;
  schemeName: string;
  staffId: string;
  staffName: string;
  period: string;
  performanceScore: number;
  eligible: boolean;
  proposedAmount: number;
  approvedAmount: number | null;
  status: string;
  approvedByName: string | null;
  approvalComments: string | null;
  managementComments: string | null;
  paymentStatus: string;
  paymentDate: Date | null;
};

function serializeRecord(r: RecordRow): RecordPayload {
  return {
    id: r.id,
    schemeId: r.schemeId,
    schemeName: r.schemeName,
    staffId: r.staffId,
    staffName: r.staffName,
    period: r.period,
    performanceScore: r.performanceScore,
    eligible: r.eligible,
    proposedAmount: r.proposedAmount,
    approvedAmount: r.approvedAmount,
    status: r.status,
    approvedByName: r.approvedByName,
    approvalComments: r.approvalComments,
    managementComments: r.managementComments,
    paymentStatus: r.paymentStatus,
    paymentDate: r.paymentDate ? r.paymentDate.toISOString() : null,
  };
}

// ---------------------------------------------------------------------------
// GET — list records, optionally filtered by period and/or scheme.
// ---------------------------------------------------------------------------

const listQuerySchema = z.object({
  period: z.string().trim().optional(),
  schemeId: z.string().trim().optional(),
});

export const GET = withApi(async (req: Request) => {
  await requirePermission("incentives.view");
  const q = parseQuery(req, listQuerySchema);

  const where: { period?: string; schemeId?: string } = {};
  if (q.period) where.period = q.period;
  if (q.schemeId) where.schemeId = q.schemeId;

  const records = await db.incentiveRecord.findMany({
    where,
    orderBy: { createdAt: "desc" },
  });

  return json({ records: records.map(serializeRecord) });
});

// ---------------------------------------------------------------------------
// POST — generate recommendations for a scheme + period.
// ---------------------------------------------------------------------------

const generateSchema = z.object({
  schemeId: z.string().min(1),
  period: z.string().trim().min(1),
});

/** Maps a scheme periodType to the performance engine period type + format. */
const SCHEME_PERIOD_MAP: Record<string, { type: PeriodType; pattern: RegExp; expected: string }> = {
  MONTHLY: { type: "MONTH", pattern: /^\d{4}-(0[1-9]|1[0-2])$/, expected: "YYYY-MM (e.g. 2026-11)" },
  QUARTERLY: { type: "QUARTER", pattern: /^\d{4}-Q[1-4]$/, expected: "YYYY-QN (e.g. 2026-Q4)" },
  ANNUAL: { type: "YEAR", pattern: /^\d{4}$/, expected: "YYYY (e.g. 2026)" },
  QUALITY: { type: "MONTH", pattern: /^\d{4}-(0[1-9]|1[0-2])$/, expected: "YYYY-MM (e.g. 2026-11)" },
  ON_TIME: { type: "MONTH", pattern: /^\d{4}-(0[1-9]|1[0-2])$/, expected: "YYYY-MM (e.g. 2026-11)" },
  CUSTOM: { type: "MONTH", pattern: /^\d{4}-(0[1-9]|1[0-2])$/, expected: "YYYY-MM (e.g. 2026-11)" },
};

export const POST = withApi(async (req: Request) => {
  const user = await requirePermission("incentives.prepare");
  const body = await parseBody(req, generateSchema);

  const scheme = await db.incentiveScheme.findUnique({ where: { id: body.schemeId } });
  if (!scheme) throw new ApiError(404, "Incentive scheme not found.");
  if (!scheme.isActive) {
    throw new ApiError(400, "This scheme is inactive. Reactivate it before generating evaluations.");
  }

  const mapping = SCHEME_PERIOD_MAP[scheme.periodType] ?? SCHEME_PERIOD_MAP.CUSTOM;
  const period = body.period.toUpperCase();
  if (!mapping.pattern.test(period)) {
    throw new ApiError(400, `Invalid period for this scheme. Expected format: ${mapping.expected}.`);
  }

  const { staff } = await listStaffPerformance(mapping.type, period);

  const minScore = scheme.minScore;
  const baseAmount = scheme.amount ?? 0;

  const existing = await db.incentiveRecord.findMany({
    where: { schemeId: scheme.id, period },
  });
  const byStaff = new Map(existing.map((r) => [r.staffId, r]));

  for (const s of staff) {
    const score =
      scheme.periodType === "QUALITY"
        ? s.scores.quality
        : scheme.periodType === "ON_TIME"
          ? s.scores.timeliness
          : s.overall;
    const eligible = minScore == null ? true : score >= minScore;
    const proposedAmount = eligible ? baseAmount : 0;

    const prior = byStaff.get(s.staffId);
    if (!prior) {
      await db.incentiveRecord.create({
        data: {
          schemeId: scheme.id,
          schemeName: scheme.name,
          staffId: s.staffId,
          staffName: s.staffName,
          period,
          performanceScore: score,
          eligible,
          proposedAmount,
          status: "PENDING",
          paymentStatus: "UNPAID",
        },
      });
    } else if (prior.status === "PENDING") {
      // Refresh PENDING recommendations only — approved/rejected are final.
      await db.incentiveRecord.update({
        where: { id: prior.id },
        data: {
          staffName: s.staffName,
          performanceScore: score,
          eligible,
          proposedAmount,
        },
      });
    }
  }

  const records = await db.incentiveRecord.findMany({
    where: { schemeId: scheme.id, period },
    orderBy: { createdAt: "desc" },
  });

  await logAudit(user, "INCENTIVE_RECOMMENDATIONS_GENERATED", "INCENTIVE_SCHEME", scheme.id, {
    schemeId: scheme.id,
    period,
    count: records.length,
  });

  return json({ records: records.map(serializeRecord) });
});
