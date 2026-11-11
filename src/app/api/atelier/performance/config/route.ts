// OSSZ ATELIER — performance scoring weights configuration (single row, id "main").
// GET    /api/atelier/performance/config   (performance.view)
// PATCH  /api/atelier/performance/config   (performance.configure — Management)

import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { getPerformanceConfig } from "@/lib/atelier/performance";
import { logAudit } from "@/lib/atelier/audit";

type WeightsPayload = {
  productivityWeight: number;
  timelyWeight: number;
  qualityWeight: number;
  reliabilityWeight: number;
  managementWeight: number;
  pieceTarget: number;
};

function toPayload(config: {
  productivityWeight: number;
  timelyWeight: number;
  qualityWeight: number;
  reliabilityWeight: number;
  managementWeight: number;
  pieceTarget: number;
}): WeightsPayload {
  return {
    productivityWeight: config.productivityWeight,
    timelyWeight: config.timelyWeight,
    qualityWeight: config.qualityWeight,
    reliabilityWeight: config.reliabilityWeight,
    managementWeight: config.managementWeight,
    pieceTarget: config.pieceTarget,
  };
}

export const GET = withApi(async () => {
  await requirePermission("performance.view");
  const config = await getPerformanceConfig();
  return json({ config: toPayload(config) });
});

const patchSchema = z.object({
  productivityWeight: z.number().min(0).max(100),
  timelyWeight: z.number().min(0).max(100),
  qualityWeight: z.number().min(0).max(100),
  reliabilityWeight: z.number().min(0).max(100),
  managementWeight: z.number().min(0).max(100),
  pieceTarget: z.number().int().min(1).max(500),
});

export const PATCH = withApi(async (req: Request) => {
  const user = await requirePermission("performance.configure");
  const body = await parseBody(req, patchSchema);

  const sum =
    body.productivityWeight +
    body.timelyWeight +
    body.qualityWeight +
    body.reliabilityWeight +
    body.managementWeight;
  if (Math.abs(sum - 100) > 0.01) {
    throw new ApiError(400, "The five weights must total 100.");
  }

  const config = await db.performanceConfig.upsert({
    where: { id: "main" },
    update: { ...body },
    create: { id: "main", ...body },
  });

  await logAudit(user, "PERFORMANCE_CONFIG_UPDATED", "PERFORMANCE_CONFIG", "main", {
    ...body,
    total: sum,
  });

  return json({ config: toPayload(config) });
});
