import { db } from "@/lib/db";
import { json, parseQuery, withApi, ApiError } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { hasPermission } from "@/lib/permissions";
import { taskListQuerySchema } from "@/lib/validation";
import { isOverdue, OVERDUE_STATUSES } from "@/lib/production";
import type { Prisma } from "@prisma/client";

/**
 * GET /api/production/tasks — filtered, paginated task list.
 * - `mine=1` → the authenticated user's own tasks (available to every account).
 * - Without `mine` → requires production.view.all.
 */
export const GET = withApi(async (req) => {
  const user = await requireUser();
  const q = parseQuery(req, taskListQuerySchema);

  const mine = q.mine === true;
  if (!mine && !hasPermission(user.role, "production.view.all")) {
    throw new ApiError(403, "Your account is not authorized to view all production tasks");
  }

  const page = q.page ?? 1;
  const pageSize = q.pageSize ?? 25;

  const where: Prisma.ProductionTaskWhereInput = {
    ...(mine ? { assignedStaffId: user.id } : {}),
    ...(!mine && q.staffId ? { assignedStaffId: q.staffId } : {}),
    ...(q.status ? { status: q.status } : {}),
    ...(q.orderId ? { orderId: q.orderId } : {}),
    ...(q.garment ? { pieceName: { contains: q.garment } } : {}),
    ...(q.q ? { order: { reference: { contains: q.q } } } : {}),
    ...(q.progressMin !== undefined || q.progressMax !== undefined
      ? {
          progressPercentage: {
            ...(q.progressMin !== undefined ? { gte: q.progressMin } : {}),
            ...(q.progressMax !== undefined ? { lte: q.progressMax } : {}),
          },
        }
      : {}),
    ...(q.assignedFrom || q.assignedTo
      ? {
          assignedAt: {
            ...(q.assignedFrom ? { gte: new Date(q.assignedFrom) } : {}),
            ...(q.assignedTo ? { lte: new Date(q.assignedTo) } : {}),
          },
        }
      : {}),
    ...(q.dueFrom || q.dueTo
      ? {
          dueAt: {
            ...(q.dueFrom ? { gte: new Date(q.dueFrom) } : {}),
            ...(q.dueTo ? { lte: new Date(q.dueTo) } : {}),
          },
        }
      : {}),
    ...(q.overdue === true
      ? { dueAt: { lt: new Date() }, status: { in: [...OVERDUE_STATUSES] } }
      : {}),
  };

  const orderBy: Prisma.ProductionTaskOrderByWithRelationInput = (() => {
    switch (q.sort) {
      case "updatedAt_asc":
        return { updatedAt: "asc" as const };
      case "dueAt_asc":
        return { dueAt: "asc" as const };
      case "dueAt_desc":
        return { dueAt: "desc" as const };
      case "progress_asc":
        return { progressPercentage: "asc" as const };
      case "progress_desc":
        return { progressPercentage: "desc" as const };
      case "createdAt_desc":
        return { createdAt: "desc" as const };
      default:
        return { updatedAt: "desc" as const };
    }
  })();

  const [total, tasks] = await Promise.all([
    db.productionTask.count({ where }),
    db.productionTask.findMany({
      where,
      orderBy,
      skip: (page - 1) * pageSize,
      take: pageSize,
      include: {
        order: { select: { id: true, reference: true, status: true, expectedDeliveryDate: true } },
        orderItem: {
          select: { id: true, product: { select: { id: true, name: true, category: true } } },
        },
        assignedStaff: { select: { id: true, name: true, role: true } },
      },
    }),
  ]);

  return json({
    tasks: tasks.map((t) => ({
      id: t.id,
      pieceName: t.pieceName,
      status: t.status,
      progressPercentage: t.progressPercentage,
      dueAt: t.dueAt,
      assignedAt: t.assignedAt,
      assignedStaff: t.assignedStaff,
      productionInstructions: t.productionInstructions,
      blockedReason: t.blockedReason,
      createdAt: t.createdAt,
      updatedAt: t.updatedAt,
      order: t.order,
      product: t.orderItem.product,
      overdue: isOverdue(t),
    })),
    total,
    page,
    pageCount: Math.max(1, Math.ceil(total / pageSize)),
  });
});
