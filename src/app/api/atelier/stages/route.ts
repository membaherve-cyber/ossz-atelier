import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { APPLIES_TO_ALL, TASK_CATEGORIES } from "@/lib/atelier/constants";

// ---------------------------------------------------------------------------
// GET /api/atelier/stages — full production stage configuration.
// Management/Admin only (production.stages.configure).
// ---------------------------------------------------------------------------

type StageRow = {
  id: string;
  code: string;
  name: string;
  sortOrder: number;
  weight: number;
  appliesTo: string;
  isActive: boolean;
};

function serializeStage(s: StageRow) {
  return {
    id: s.id,
    code: s.code,
    name: s.name,
    sortOrder: s.sortOrder,
    weight: s.weight,
    appliesTo: s.appliesTo,
    isActive: s.isActive,
  };
}

/** Normalize a category list: valid unique codes only; empty or all 6 → "ALL". */
export function normalizeAppliesTo(list: string[]): string {
  const valid = [...new Set(list.map((c) => c.trim().toUpperCase()))].filter((c) =>
    (TASK_CATEGORIES as readonly string[]).includes(c)
  );
  if (valid.length === 0 || valid.length === TASK_CATEGORIES.length) return APPLIES_TO_ALL;
  return valid.join(",");
}

/** Lowercase slug: non-alphanumerics collapse to dashes, edges trimmed. */
export function slugify(name: string): string {
  return name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

export const GET = withApi(async () => {
  await requirePermission("production.stages.configure");
  const stages = await db.productionStage.findMany({
    orderBy: [{ sortOrder: "asc" }, { name: "asc" }],
  });
  return json({ stages: stages.map(serializeStage) });
});

// ---------------------------------------------------------------------------
// POST /api/atelier/stages — create a checklist stage.
// Stages are never deleted, only deactivated, so codes are unique forever.
// ---------------------------------------------------------------------------

const createStageSchema = z.object({
  name: z.string().trim().min(2, "Name must be at least 2 characters").max(80),
  weight: z.coerce.number().min(0.1, "Weight must be at least 0.1").max(10).default(1),
  sortOrder: z.coerce.number().int().min(0).max(999).default(0),
  appliesTo: z.array(z.string()).max(12),
});

export const POST = withApi(async (req: Request) => {
  const user = await requirePermission("production.stages.configure");
  const body = await parseBody(req, createStageSchema);

  const base = slugify(body.name);
  if (!base) {
    throw new ApiError(400, "Stage name must contain at least one letter or number.");
  }

  // Generate a unique code from the slugified name (append -2, -3, ... on clash).
  const existing = await db.productionStage.findMany({ select: { code: true } });
  const taken = new Set(existing.map((s) => s.code));
  let code = base;
  let suffix = 2;
  while (taken.has(code)) code = `${base}-${suffix++}`;

  const appliesTo = normalizeAppliesTo(body.appliesTo);

  const stage = await db.productionStage.create({
    data: {
      code,
      name: body.name,
      sortOrder: body.sortOrder,
      weight: body.weight,
      appliesTo,
      isActive: true,
    },
  });

  await logAudit(user, "STAGE_CREATED", "STAGE", stage.id, {
    name: stage.name,
    appliesTo: stage.appliesTo,
  });

  return json({ stage: serializeStage(stage) }, 201);
});
