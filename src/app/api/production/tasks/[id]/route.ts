import { db } from "@/lib/db";
import { json, withApi, ApiError } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { hasPermission } from "@/lib/permissions";
import { isOverdue } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * GET /api/production/tasks/:id — task detail with full history.
 *
 * Access rules:
 * - Managers/inspectors/admins (production.view.all) see everything.
 * - The assigned staff member sees their own task, with the order information
 *   needed for production — payment details and order totals are never exposed
 *   to production staff.
 * - Anyone else gets 403 (URL manipulation is rejected server-side).
 */
export const GET = withApi(async (_req: Request, ctx: Ctx) => {
  const user = await requireUser();
  const { id } = await ctx.params;

  const task = await db.productionTask.findUnique({
    where: { id },
    include: {
      order: {
        include: {
          customer: { select: { id: true, name: true } },
          items: { select: { id: true, measurements: true, instructions: true, product: { select: { id: true, name: true, category: true } } } },
        },
      },
      orderItem: { select: { id: true } },
      assignedStaff: { select: { id: true, name: true, role: true } },
      assignedBy: { select: { id: true, name: true, role: true } },
    },
  });
  if (!task) throw new ApiError(404, "Production task not found");

  const isManager = hasPermission(user.role, "production.view.all");
  const isOwner = task.assignedStaffId === user.id;
  if (!isManager && !isOwner) {
    throw new ApiError(403, "You can only view production tasks assigned to you");
  }

  const [progressUpdates, inspections, assignments, events] = await Promise.all([
    db.progressUpdate.findMany({
      where: { taskId: id },
      orderBy: { createdAt: "desc" },
      include: { staff: { select: { id: true, name: true } } },
    }),
    db.inspectionRecord.findMany({
      where: { taskId: id },
      orderBy: { inspectedAt: "desc" },
      include: { inspector: { select: { id: true, name: true } } },
    }),
    db.assignmentRecord.findMany({
      where: { taskId: id },
      orderBy: { createdAt: "desc" },
      include: {
        newStaff: { select: { id: true, name: true } },
        previousStaff: { select: { id: true, name: true } },
        assignedBy: { select: { id: true, name: true } },
      },
    }),
    db.productionEvent.findMany({
      where: { taskId: id },
      orderBy: { createdAt: "desc" },
      include: { actor: { select: { id: true, name: true } } },
    }),
  ]);

  const item = task.order.items.find((i) => i.id === task.orderItemId);
  const lastInspection = inspections[0] ?? null;

  return json({
    task: {
      id: task.id,
      pieceName: task.pieceName,
      status: task.status,
      progressPercentage: task.progressPercentage,
      assignedStaff: task.assignedStaff,
      assignedBy: task.assignedBy,
      assignedAt: task.assignedAt,
      dueAt: task.dueAt,
      productionInstructions: task.productionInstructions,
      blockedReason: task.blockedReason,
      blockedAt: task.blockedAt,
      createdAt: task.createdAt,
      updatedAt: task.updatedAt,
      overdue: isOverdue(task),
      lastInspection,
    },
    order: {
      id: task.order.id,
      reference: task.order.reference,
      status: task.order.status,
      expectedDeliveryDate: task.order.expectedDeliveryDate,
      ...(isManager
        ? { paymentStatus: task.order.paymentStatus, totalAmount: task.order.totalAmount }
        : {}),
    },
    customer: { name: task.order.customer.name },
    item: {
      productName: item?.product.name ?? null,
      category: item?.product.category ?? null,
      measurements: item?.measurements ?? null,
      instructions: item?.instructions ?? null,
    },
    history: { progressUpdates, inspections, assignments, events },
    viewerIsAssignedStaff: isOwner,
  });
});
