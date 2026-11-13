import { db } from "@/lib/db";
import { json, parseBody, parseQuery, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { createOrderSchema, orderListQuerySchema } from "@/lib/validation";
import { computeOrderProductionSummary, nextOrderReference } from "@/lib/production";
import type { Prisma } from "@prisma/client";

/** GET /api/orders — paginated order list with production summaries (requires orders.view). */
export const GET = withApi(async (req) => {
  await requirePermission("orders.view");
  const q = parseQuery(req, orderListQuerySchema);
  const page = q.page ?? 1;
  const pageSize = q.pageSize ?? 20;

  const where: Prisma.OrderWhereInput = {
    ...(q.status ? { status: q.status } : {}),
    ...(q.q
      ? {
          OR: [
            { reference: { contains: q.q } },
            { customer: { name: { contains: q.q } } },
          ],
        }
      : {}),
  };

  const [total, orders] = await Promise.all([
    db.order.count({ where }),
    db.order.findMany({
      where,
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * pageSize,
      take: pageSize,
      include: {
        customer: { select: { id: true, name: true } },
        items: { select: { quantity: true } },
        productionTasks: {
          select: {
            status: true,
            progressPercentage: true,
            assignedStaffId: true,
            dueAt: true,
          },
        },
      },
    }),
  ]);

  const items = orders.map((o) => {
    const { productionTasks, ...rest } = o;
    return {
      ...rest,
      itemCount: o.items.reduce((s, i) => s + i.quantity, 0),
      production: computeOrderProductionSummary(productionTasks),
    };
  });

  return json({ orders: items, total, page, pageCount: Math.max(1, Math.ceil(total / pageSize)) });
});

/** POST /api/orders — create a customer order (requires orders.manage). */
export const POST = withApi(async (req) => {
  await requirePermission("orders.manage");
  const data = await parseBody(req, createOrderSchema);

  if (!data.customerId && !data.customer) {
    throw new ApiError(400, "Provide either customerId or a new customer object");
  }

  const productIds = data.items.map((i) => i.productId);
  const products = await db.product.findMany({ where: { id: { in: productIds } } });
  const productMap = new Map(products.map((p) => [p.id, p]));
  for (const item of data.items) {
    if (!productMap.has(item.productId)) {
      throw new ApiError(400, `Unknown product in order items (${item.productId})`);
    }
  }

  const order = await db.$transaction(async (tx) => {
    let customerId = data.customerId ?? null;
    if (!customerId && data.customer) {
      const customer = await tx.customer.create({
        data: {
          name: data.customer.name,
          email: data.customer.email || null,
          phone: data.customer.phone || null,
        },
      });
      customerId = customer.id;
    }
    const existingCustomer = await tx.customer.findUnique({ where: { id: customerId! } });
    if (!existingCustomer) throw new ApiError(400, "Customer not found");

    const reference = await nextOrderReference(tx);

    const itemsData = data.items.map((i) => ({
      productId: i.productId,
      quantity: i.quantity,
      unitPrice: i.unitPrice ?? productMap.get(i.productId)!.basePrice,
      measurements: i.measurements ? JSON.stringify(i.measurements) : null,
      instructions: i.instructions ?? null,
    }));

    return tx.order.create({
      data: {
        reference,
        customerId: customerId!,
        status: "PENDING",
        paymentStatus: data.paymentStatus ?? "UNPAID",
        totalAmount: itemsData.reduce((s, i) => s + i.unitPrice * i.quantity, 0),
        expectedDeliveryDate: data.expectedDeliveryDate
          ? new Date(data.expectedDeliveryDate)
          : null,
        notes: data.notes ?? null,
        items: { create: itemsData },
      },
      include: {
        customer: { select: { id: true, name: true } },
        items: { include: { product: true } },
      },
    });
  });

  return json({ order }, 201);
});
