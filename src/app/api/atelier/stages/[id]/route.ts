import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { normalizeAppliesTo, slugify } from "../route";

// ---------------------------------------------------------------------------
// PATCH /api/atelier/stages/[id] — update a stage (rename, re-weight, re-order,
// re-scope, activate/deactivate). Stages are NEVER deleted: they are referenced
// by immutable task history records, so deactivation is the only removal.
// ---------------------------------------------------------------------------

const updateStageSchema = z.object({
  name: z.string().trim().min(2, "Name must be at least 2 characters").max(80).optional(),
  weight: z.coerce.number().min(0.1, "Weight must be at least 0.1").max(10).optional(),
  sortOrder: z.coerce.number().int().min(0).max(999).optional(),
  isActive: z.boolean().optional(),
  appliesTo: z.array(z.string()).max(12).optional(),
});

export const PATCH = withApi(
  async (req: Request, { params }: { params: Promise<{ id: string }> }) => {
    const user = await requirePermission("production.stages.configure");
    const { id } = await params;
    const body = await parseBody(req, updateStageSchema);

    const stage = await db.productionStage.findUnique({ where: { id } });
    if (!stage) throw new ApiError(404, "Stage not found.");

    const data: {
      name?: string;
      weight?: number;
      sortOrder?: number;
      isActive?: boolean;
      appliesTo?: string;
    } = {};
    const changes: Record<string, unknown> = {};

    if (body.name !== undefined && body.name !== stage.name) {
      if (!slugify(body.name)) {
        throw new ApiError(400, "Stage name must contain at least one letter or number.");
      }
      data.name = body.name;
      changes.name = body.name;
    }
    if (body.weight !== undefined && body.weight !== stage.weight) {
      data.weight = body.weight;
      changes.weight = body.weight;
    }
    if (body.sortOrder !== undefined && body.sortOrder !== stage.sortOrder) {
      data.sortOrder = body.sortOrder;
      changes.sortOrder = body.sortOrder;
    }
    if (body.isActive !== undefined && body.isActive !== stage.isActive) {
      data.isActive = body.isActive;
      changes.isActive = body.isActive;
    }
    if (body.appliesTo !== undefined) {
      const normalized = normalizeAppliesTo(body.appliesTo);
      if (normalized !== stage.appliesTo) {
        data.appliesTo = normalized;
        changes.appliesTo = normalized;
      }
    }

    const updated = await db.productionStage.update({ where: { id }, data });

    await logAudit(user, "STAGE_UPDATED", "STAGE", stage.id, {
      name: updated.name,
      changed: changes,
    });

    return json({
      stage: {
        id: updated.id,
        code: updated.code,
        name: updated.name,
        sortOrder: updated.sortOrder,
        weight: updated.weight,
        appliesTo: updated.appliesTo,
        isActive: updated.isActive,
      },
    });
  }
);
