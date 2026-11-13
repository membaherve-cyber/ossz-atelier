import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { updateOrderSchema } from "@/lib/validation";
import { computeOrderProductionSummary, syncOrderStatus } from "@/lib/production";
import type { Prisma } from "@prisma/client";

type Ctx = { params: Promise<{ id: string }> };

/** GET /api/orders/:id — order detail with the full production tracking section. */
export const GET = withApi(async (_req: Request, ctx: Ctx) => {
  await requirePermission("orders.view");
  const { id } = await ctx.params;

  const order = await db.order.findUnique({
    where: { id },
    include: {
      customer: true,
      items: {
        include: {
          product: { include: { components: { orderBy: { sortOrder: "asc" } } } },
          productionTasks: {
            orderBy: { createdAt: "asc" },
            include: { assignedStaff: { select: { id: true, name: true } } },
          },
        },
      },
    },
  });
  if (!order) throw new ApiError(404, "Order not found");

  const tasks = await db.productionTask.findMany({
    where: { orderId: order.id },
    include: { assignedStaff: { select: { id: true, name: true } } },
    orderBy: { createdAt: "asc" },
  });
  const summary = computeOrderProductionSummary(tasks);

  const recentEvents = await db.productionEvent.findMany({
    where: { orderId: order.id },
    orderBy: { createdAt: "desc" },
    take: 12,
    include: {
      actor: { select: { id: true, name: true } },
      task: { select: { id: true, pieceName: true } },
    },
  });

  const staffIds = summary.staffIds;
  const staff = await db.user.findMany({
    where: { id: { in: staffIds } },
    select: { id: true, name: true, role: true },
  });

  const { items, customer, ...orderData } = order;
  return json({
    order: {
      ...orderData,
      customer: { id: customer.id, name: customer.name, email: customer.email, phone: customer.phone },
      items: items.map((i) => ({
        id: i.id,
        productId: i.productId,
        productName: i.product.name,
        category: i.product.category,
        quantity: i.quantity,
        unitPrice: i.unitPrice,
        measurements: i.measurements,
        instructions: i.instructions,
        productComponents: i.product.components.map((c) => c.name),
      })),
    },
    production: {
      summary,
      tasks: tasks.map((t) => ({
        id: t.id,
        pieceName: t.pieceName,
        status: t.status,
        progressPercentage: t.progressPercentage,
        assignedStaff: t.assignedStaff,
        assignedAt: t.assignedAt,
        dueAt: t.dueAt,
        productionInstructions: t.productionInstructions,
        blockedReason: t.blockedReason,
        createdAt: t.createdAt,
        updatedAt: t.updatedAt,
      })),
      staff,
      recentEvents,
    },
  });
});

/** PATCH /api/orders/:id — update order status/payment/dates (requires orders.manage). */
export const PATCH = withApi(async (req: Request, ctx: Ctx) => {
  await requirePermission("orders.manage");
  const { id } = await ctx.params;
  const data = await parseBody(req, updateOrderSchema);

  const order = await db.order.findUnique({ where: { id } });
  if (!order) throw new ApiError(404, "Order not found");

  // Guard: cannot mark an order as production-ready while pieces remain unapproved.
  if (data.status && ["READY", "DISPATCHED", "COMPLETED"].includes(data.status)) {
    const tasks = await db.productionTask.findMany({
      where: { orderId: id },
      select: { status: true, progressPercentage: true, assignedStaffId: true, dueAt: true },
    });
    const summary = computeOrderProductionSummary(tasks);
    if (summary.totalPieces > 0 && !summary.productionComplete && data.status !== "CANCELLED") {
      throw new ApiError(
        400,
        `Cannot mark this order as ${data.status}: ${summary.totalPieces - summary.approved - summary.cancelled} garment piece(s) have not passed inspection yet.`
      );
    }
  }

  const update: Prisma.OrderUpdateInput = {
    ...(data.status !== undefined ? { status: data.status } : {}),
    ...(data.paymentStatus !== undefined ? { paymentStatus: data.paymentStatus } : {}),
    ...(data.notes !== undefined ? { notes: data.notes ?? null } : {}),
    ...(data.expectedDeliveryDate !== undefined
      ? {
          expectedDeliveryDate: data.expectedDeliveryDate
            ? new Date(data.expectedDeliveryDate)
            : null,
        }
      : {}),
  };

  const updated = await db.$transaction(async (tx) => {
    const o = await tx.order.update({ where: { id }, data: update });
    await syncOrderStatus(tx, id);
    return tx.order.findUnique({
      where: { id },
      include: { customer: { select: { id: true, name: true } } },
    });
  });

  return json({ order: updated });
});
