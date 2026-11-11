// OSSZ ATELIER — manual Management assessment of a staff member for a period.
// PUT /api/atelier/performance/assessment  (performance.assess — Management)
// Upserts StaffAssessment on the (staffId, period) unique key.

import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";

const assessmentSchema = z.object({
  staffId: z.string().min(1),
  period: z
    .string()
    .trim()
    .regex(/^\d{4}(-Q[1-4]|-(0[1-9]|1[0-2]))?$/, "Invalid period. Expected YYYY-MM, YYYY-QN or YYYY."),
  score: z.number().min(0).max(100),
  comments: z.string().trim().max(2000).optional(),
});

export const PUT = withApi(async (req: Request) => {
  const user = await requirePermission("performance.assess");
  const body = await parseBody(req, assessmentSchema);

  const staff = await db.user.findUnique({ where: { id: body.staffId } });
  if (!staff || staff.role !== "STAFF") {
    throw new ApiError(404, "Staff account not found.");
  }

  const assessment = await db.staffAssessment.upsert({
    where: { staffId_period: { staffId: staff.id, period: body.period } },
    update: {
      staffName: staff.name,
      score: body.score,
      comments: body.comments ?? null,
    },
    create: {
      staffId: staff.id,
      staffName: staff.name,
      period: body.period,
      score: body.score,
      comments: body.comments ?? null,
    },
  });

  await logAudit(user, "STAFF_ASSESSED", "STAFF", staff.id, {
    staffId: staff.id,
    period: body.period,
    score: body.score,
  });

  return json({ assessment });
});
