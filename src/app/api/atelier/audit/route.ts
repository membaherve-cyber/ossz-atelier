import { z } from "zod";
import { db } from "@/lib/db";
import { json, parseQuery, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";

// ---------------------------------------------------------------------------
// GET /api/atelier/audit — permanent audit trail. Management ONLY: the
// audit.view permission is granted exclusively to the MANAGEMENT role, so
// Admin and Staff requests are rejected with 403 by requirePermission.
// ---------------------------------------------------------------------------

const isoDate = z
  .string()
  .refine((v) => !Number.isNaN(new Date(v).getTime()), "Invalid date");

const auditQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  pageSize: z.coerce.number().int().min(1).max(100).default(30),
  action: z.string().trim().min(1).optional(),
  entityType: z.string().trim().min(1).optional(),
  from: isoDate.optional(),
  to: isoDate.optional(),
});

/** Date-only inputs ("2026-02-01") are inclusive of the whole end day. */
function dateBound(value: string, end: boolean): Date {
  const d = new Date(value);
  const dateOnly = !value.includes("T") && !value.includes(":");
  if (dateOnly && end) d.setHours(23, 59, 59, 999);
  return d;
}

export const GET = withApi(async (req: Request) => {
  await requirePermission("audit.view");
  const q = parseQuery(req, auditQuerySchema);

  const where: Record<string, unknown> = {};
  if (q.action) where.action = q.action;
  if (q.entityType) where.entityType = q.entityType;
  if (q.from || q.to) {
    where.createdAt = {
      ...(q.from ? { gte: dateBound(q.from, false) } : {}),
      ...(q.to ? { lte: dateBound(q.to, true) } : {}),
    };
  }

  const [total, entries, distinctActions] = await db.$transaction([
    db.auditLog.count({ where }),
    db.auditLog.findMany({
      where,
      orderBy: [{ createdAt: "desc" }, { id: "desc" }],
      skip: (q.page - 1) * q.pageSize,
      take: q.pageSize,
    }),
    // Unfiltered: the filter dropdown always offers every recorded action.
    db.auditLog.findMany({
      distinct: ["action"],
      select: { action: true },
      orderBy: { action: "asc" },
    }),
  ]);

  return json({
    entries: entries.map((e) => ({
      id: e.id,
      userName: e.userName,
      action: e.action,
      entityType: e.entityType,
      entityId: e.entityId,
      details: e.details,
      createdAt: e.createdAt.toISOString(),
    })),
    total,
    page: q.page,
    pageSize: q.pageSize,
    actions: distinctActions.map((a) => a.action),
  });
});
