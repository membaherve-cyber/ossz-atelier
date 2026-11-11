import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { serializeTask, taskInclude } from "@/lib/atelier/tasks";
import { PRIORITIES } from "@/lib/atelier/constants";

// ---------------------------------------------------------------------------
// Shared serialization — must stay in sync with /api/atelier/orders.
// ---------------------------------------------------------------------------

const COMPLETED_STATUSES = ["APPROVED", "READY_FOR_PICKUP", "DELIVERED"];

type OrderWithRelations = {
  id: string;
  orderNumber: string;
  customerId: string;
  orderDate: Date;
  expectedDeliveryDate: Date | null;
  description: string;
  garmentCategory: string;
  numberOfPieces: number;
  instructions: string | null;
  priority: string;
  notes: string | null;
  createdAt: Date;
  customer: { name: string; phone: string };
  tasks: Array<{ status: string; progress: number }>;
};

function serializeOrder(o: OrderWithRelations) {
  const activeTasks = o.tasks.filter((t) => t.status !== "CANCELLED");
  const taskCount = activeTasks.length;
  const completedTaskCount = activeTasks.filter((t) =>
    (COMPLETED_STATUSES as readonly string[]).includes(t.status)
  ).length;
  const avgProgress =
    taskCount === 0
      ? 0
      : Math.round(activeTasks.reduce((sum, t) => sum + t.progress, 0) / taskCount);
  return {
    id: o.id,
    orderNumber: o.orderNumber,
    customerId: o.customerId,
    customerName: o.customer.name,
    customerPhone: o.customer.phone,
    orderDate: o.orderDate.toISOString(),
    expectedDeliveryDate: o.expectedDeliveryDate?.toISOString() ?? null,
    description: o.description,
    garmentCategory: o.garmentCategory,
    numberOfPieces: o.numberOfPieces,
    instructions: o.instructions,
    priority: o.priority,
    notes: o.notes,
    createdAt: o.createdAt.toISOString(),
    taskCount,
    completedTaskCount,
    avgProgress,
  };
}

const orderInclude = { customer: true, tasks: true } as const;

const isoDate = z
  .string()
  .refine((v) => !Number.isNaN(new Date(v).getTime()), "Invalid date");

// ---------------------------------------------------------------------------
// GET /api/atelier/orders/[id] — order detail with its production tasks.
// ---------------------------------------------------------------------------

export const GET = withApi(async (req: Request, { params }: { params: Promise<{ id: string }> }) => {
  await requirePermission("orders.view");
  const { id } = await params;

  const order = await db.order.findUnique({ where: { id }, include: orderInclude });
  if (!order) throw new ApiError(404, "Order not found.");

  const tasks = await db.productionTask.findMany({
    where: { orderId: id },
    include: taskInclude,
    orderBy: { createdAt: "asc" },
  });

  return json({ order: serializeOrder(order), tasks: tasks.map(serializeTask) });
});

// ---------------------------------------------------------------------------
// PATCH /api/atelier/orders/[id] — edit order details (customer is fixed).
// ---------------------------------------------------------------------------

const optionalDate = z.preprocess(
  (v) => (typeof v === "string" && v.trim() === "" ? null : v),
  isoDate.nullable().optional()
);

const updateOrderSchema = z.object({
  orderNumber: z.string().trim().min(1, "Order number is required").max(50).optional(),
  orderDate: isoDate.optional(),
  expectedDeliveryDate: optionalDate,
  description: z.string().trim().min(1, "Description is required").max(500).optional(),
  garmentCategory: z.string().trim().min(1, "Garment category is required").max(80).optional(),
  numberOfPieces: z
    .number()
    .int("Number of pieces must be a whole number")
    .min(1, "At least one piece is required")
    .max(200, "An order cannot exceed 200 pieces")
    .optional(),
  instructions: z.string().trim().max(4000).optional(),
  notes: z.string().trim().max(4000).optional(),
  priority: z.enum(PRIORITIES).optional(),
});

export const PATCH = withApi(async (req: Request, { params }: { params: Promise<{ id: string }> }) => {
  const user = await requirePermission("orders.manage");
  const { id } = await params;
  const body = await parseBody(req, updateOrderSchema);

  const existing = await db.order.findUnique({ where: { id } });
  if (!existing) throw new ApiError(404, "Order not found.");

  // Unique order number guard (friendly message on clash).
  if (body.orderNumber !== undefined && body.orderNumber !== existing.orderNumber) {
    const clash = await db.order.findUnique({ where: { orderNumber: body.orderNumber } });
    if (clash && clash.id !== existing.id) {
      throw new ApiError(
        409,
        `An order with number "${body.orderNumber}" already exists. Order numbers must be unique.`
      );
    }
  }

  const data: Record<string, unknown> = {};
  const changedFields: string[] = [];
  if (body.orderNumber !== undefined && body.orderNumber !== existing.orderNumber) {
    data.orderNumber = body.orderNumber;
    changedFields.push("orderNumber");
  }
  if (body.orderDate !== undefined) {
    const next = new Date(body.orderDate).getTime();
    if (next !== existing.orderDate.getTime()) {
      data.orderDate = new Date(body.orderDate);
      changedFields.push("orderDate");
    }
  }
  if (body.expectedDeliveryDate !== undefined) {
    const next = body.expectedDeliveryDate ? new Date(body.expectedDeliveryDate).getTime() : null;
    const current = existing.expectedDeliveryDate?.getTime() ?? null;
    if (next !== current) {
      data.expectedDeliveryDate = body.expectedDeliveryDate ? new Date(body.expectedDeliveryDate) : null;
      changedFields.push("expectedDeliveryDate");
    }
  }
  if (body.description !== undefined && body.description !== existing.description) {
    data.description = body.description;
    changedFields.push("description");
  }
  if (body.garmentCategory !== undefined && body.garmentCategory !== existing.garmentCategory) {
    data.garmentCategory = body.garmentCategory;
    changedFields.push("garmentCategory");
  }
  if (body.numberOfPieces !== undefined && body.numberOfPieces !== existing.numberOfPieces) {
    data.numberOfPieces = body.numberOfPieces;
    changedFields.push("numberOfPieces");
  }
  if (body.instructions !== undefined && (body.instructions || null) !== existing.instructions) {
    data.instructions = body.instructions || null;
    changedFields.push("instructions");
  }
  if (body.notes !== undefined && (body.notes || null) !== existing.notes) {
    data.notes = body.notes || null;
    changedFields.push("notes");
  }
  if (body.priority !== undefined && body.priority !== existing.priority) {
    data.priority = body.priority;
    changedFields.push("priority");
  }

  if (changedFields.length > 0) {
    await db.order.update({ where: { id }, data });
    await logAudit(user, "ORDER_UPDATED", "ORDER", id, {
      orderNumber: body.orderNumber ?? existing.orderNumber,
      changedFields,
    });
  }

  const order = await db.order.findUnique({ where: { id }, include: orderInclude });
  if (!order) throw new ApiError(404, "Order not found.");

  return json({ order: serializeOrder(order) });
});
