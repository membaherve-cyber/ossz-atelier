// OSSZ ATELIER — incentive schemes (definitions of bonus rules).
// GET   /api/atelier/incentives/schemes  (incentives.view)
// POST  /api/atelier/incentives/schemes  (incentives.manage.schemes — Management)

import { z } from "zod";
import { db } from "@/lib/db";
import { json, parseBody, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { INCENTIVE_PERIOD_TYPES } from "@/lib/atelier/constants";

type SchemePayload = {
  id: string;
  name: string;
  periodType: string;
  minScore: number | null;
  amount: number | null;
  description: string | null;
  isActive: boolean;
  createdByName: string | null;
};

const schemeSelect = {
  id: true,
  name: true,
  periodType: true,
  minScore: true,
  amount: true,
  description: true,
  isActive: true,
  createdByName: true,
} as const;

export const GET = withApi(async () => {
  await requirePermission("incentives.view");
  const schemes = await db.incentiveScheme.findMany({
    orderBy: { createdAt: "desc" },
    select: schemeSelect,
  });
  return json({ schemes: schemes as SchemePayload[] });
});

const createSchema = z.object({
  name: z.string().trim().min(2, "Scheme name must be at least 2 characters").max(120),
  periodType: z.enum(INCENTIVE_PERIOD_TYPES),
  minScore: z.number().min(0).max(100).nullish(),
  amount: z.number().min(0).nullish(),
  description: z.string().trim().max(2000).nullish(),
});

export const POST = withApi(async (req: Request) => {
  const user = await requirePermission("incentives.manage.schemes");
  const body = await parseBody(req, createSchema);

  const scheme = await db.incentiveScheme.create({
    data: {
      name: body.name,
      periodType: body.periodType,
      minScore: body.minScore ?? null,
      amount: body.amount ?? null,
      description: body.description ?? null,
      isActive: true,
      createdById: user.id,
      createdByName: user.name,
    },
    select: schemeSelect,
  });

  await logAudit(user, "INCENTIVE_SCHEME_CREATED", "INCENTIVE_SCHEME", scheme.id, {
    name: scheme.name,
    periodType: scheme.periodType,
    minScore: scheme.minScore,
    amount: scheme.amount,
  });

  return json({ scheme: scheme as SchemePayload }, 201);
});
