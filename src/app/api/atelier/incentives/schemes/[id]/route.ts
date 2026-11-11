// OSSZ ATELIER — update or deactivate an incentive scheme (no hard delete).
// PATCH /api/atelier/incentives/schemes/[id]  (incentives.manage.schemes — Management)

import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
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

const patchSchema = z.object({
  name: z.string().trim().min(2, "Scheme name must be at least 2 characters").max(120).optional(),
  periodType: z.enum(INCENTIVE_PERIOD_TYPES).optional(),
  minScore: z.number().min(0).max(100).nullish(),
  amount: z.number().min(0).nullish(),
  description: z.string().trim().max(2000).nullish(),
  isActive: z.boolean().optional(),
});

export const PATCH = withApi(
  async (req: Request, { params }: { params: Promise<{ id: string }> }) => {
    const user = await requirePermission("incentives.manage.schemes");
    const { id } = await params;
    const body = await parseBody(req, patchSchema);

    const existing = await db.incentiveScheme.findUnique({ where: { id } });
    if (!existing) throw new ApiError(404, "Incentive scheme not found.");

    const scheme = await db.incentiveScheme.update({
      where: { id },
      data: {
        ...(body.name !== undefined ? { name: body.name } : {}),
        ...(body.periodType !== undefined ? { periodType: body.periodType } : {}),
        ...(body.minScore !== undefined ? { minScore: body.minScore ?? null } : {}),
        ...(body.amount !== undefined ? { amount: body.amount ?? null } : {}),
        ...(body.description !== undefined ? { description: body.description ?? null } : {}),
        ...(body.isActive !== undefined ? { isActive: body.isActive } : {}),
      },
      select: schemeSelect,
    });

    await logAudit(user, "INCENTIVE_SCHEME_UPDATED", "INCENTIVE_SCHEME", id, {
      name: scheme.name,
      updated: {
        ...(body.name !== undefined ? { name: body.name } : {}),
        ...(body.periodType !== undefined ? { periodType: body.periodType } : {}),
        ...(body.minScore !== undefined ? { minScore: body.minScore ?? null } : {}),
        ...(body.amount !== undefined ? { amount: body.amount ?? null } : {}),
        ...(body.description !== undefined ? { description: body.description ?? null } : {}),
        ...(body.isActive !== undefined ? { isActive: body.isActive } : {}),
      },
    });

    return json({ scheme: scheme as SchemePayload });
  }
);
