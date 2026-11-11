import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, parseQuery, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { nextCustomerCode } from "@/lib/atelier/tasks";
import { PRIORITIES } from "@/lib/atelier/constants";

// ---------------------------------------------------------------------------
// Shared serialization — OrderSummary with production stats computed from the
// order's tasks (CANCELLED tasks are excluded from all figures).
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

/** Treat a date-only "to" value as inclusive of the entire day. */
function endOfRange(value: string): Date {
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return new Date(`${value}T23:59:59.999Z`);
  return new Date(value);
}

// ---------------------------------------------------------------------------
// GET /api/atelier/orders — filterable order list with production summaries.
// ---------------------------------------------------------------------------

const listQuerySchema = z.object({
  search: z.string().trim().max(120).optional(),
  priority: z.string().optional(),
  from: isoDate.optional(),
  to: isoDate.optional(),
});

export const GET = withApi(async (req: Request) => {
  await requirePermission("orders.view");
  const q = parseQuery(req, listQuerySchema);

  const where: Record<string, unknown> = {};
  if (q.search) {
    const s = q.search;
    where.OR = [
      { orderNumber: { contains: s } },
      { description: { contains: s } },
      { customer: { name: { contains: s } } },
    ];
  }
  if (q.priority && (PRIORITIES as readonly string[]).includes(q.priority)) {
    where.priority = q.priority;
  }
  if (q.from || q.to) {
    where.orderDate = {
      ...(q.from ? { gte: new Date(q.from) } : {}),
      ...(q.to ? { lte: endOfRange(q.to) } : {}),
    };
  }

  const orders = await db.order.findMany({
    where,
    include: orderInclude,
    orderBy: { createdAt: "desc" },
  });

  return json({ orders: orders.map(serializeOrder) });
});

// ---------------------------------------------------------------------------
// POST /api/atelier/orders — register a manually entered order. Either an
// existing customerId or inline newCustomer details must be provided; a new
// customer is created inside the same transaction as the order.
// ---------------------------------------------------------------------------

const optionalEmail = z.preprocess(
  (v) => (typeof v === "string" && v.trim() === "" ? undefined : v),
  z.email("Enter a valid email address").optional()
);

const newCustomerSchema = z.object({
  name: z.string().trim().min(1, "Customer name is required").max(120),
  phone: z.string().trim().min(3, "Phone number is required").max(40),
  email: optionalEmail,
  address: z.string().trim().max(300).optional(),
  notes: z.string().trim().max(2000).optional(),
});

const createOrderSchema = z
  .object({
    orderNumber: z.string().trim().min(1, "Order number is required").max(50),
    customerId: z.string().min(1).optional(),
    newCustomer: newCustomerSchema.optional(),
    orderDate: isoDate,
    expectedDeliveryDate: isoDate.nullable().optional(),
    description: z.string().trim().min(1, "Description is required").max(500),
    garmentCategory: z.string().trim().min(1, "Garment category is required").max(80),
    numberOfPieces: z
      .number()
      .int("Number of pieces must be a whole number")
      .min(1, "At least one piece is required")
      .max(200, "An order cannot exceed 200 pieces"),
    instructions: z.string().trim().max(4000).optional(),
    notes: z.string().trim().max(4000).optional(),
    priority: z.enum(PRIORITIES).default("NORMAL"),
  })
  .refine((v) => !!v.customerId || !!v.newCustomer, {
    message: "Select an existing customer or provide new customer details.",
    path: ["customerId"],
  });

function duplicateOrderError(orderNumber: string): ApiError {
  return new ApiError(
    409,
    `An order with number "${orderNumber}" already exists. Order numbers must be unique.`
  );
}

export const POST = withApi(async (req: Request) => {
  const user = await requirePermission("orders.manage");
  const body = await parseBody(req, createOrderSchema);

  // Friendly duplicate check before touching the database.
  const clash = await db.order.findUnique({ where: { orderNumber: body.orderNumber } });
  if (clash) throw duplicateOrderError(body.orderNumber);

  // When reusing an existing customer, make sure it still exists.
  if (!body.newCustomer) {
    const customer = await db.customer.findUnique({ where: { id: body.customerId! } });
    if (!customer) throw new ApiError(400, "The selected customer no longer exists.");
  }

  let createdId: string;
  try {
    // Compute the customer code BEFORE the transaction (reads outside,
    // writes inside — avoids interactive-transaction timeouts).
    const newCustomerCode = body.newCustomer ? await nextCustomerCode() : null;
    createdId = await db.$transaction(async (tx) => {
      let customerId: string;
      if (body.newCustomer) {
        const code = newCustomerCode!;
        const customer = await tx.customer.create({
          data: {
            code,
            name: body.newCustomer!.name,
            phone: body.newCustomer!.phone,
            email: body.newCustomer!.email ?? null,
            address: body.newCustomer!.address || null,
            notes: body.newCustomer!.notes || null,
            createdById: user.id,
          },
        });
        customerId = customer.id;
      } else {
        customerId = body.customerId!;
      }
      const order = await tx.order.create({
        data: {
          orderNumber: body.orderNumber,
          customerId,
          orderDate: new Date(body.orderDate),
          expectedDeliveryDate: body.expectedDeliveryDate
            ? new Date(body.expectedDeliveryDate)
            : null,
          description: body.description,
          garmentCategory: body.garmentCategory,
          numberOfPieces: body.numberOfPieces,
          instructions: body.instructions || null,
          priority: body.priority,
          notes: body.notes || null,
          createdById: user.id,
        },
      });
      return order.id;
    });
  } catch (err) {
    // Race-safe duplicate guard (Prisma unique constraint on orderNumber).
    if (
      err instanceof Error &&
      "code" in err &&
      (err as { code?: string }).code === "P2002"
    ) {
      throw duplicateOrderError(body.orderNumber);
    }
    throw err;
  }

  const order = await db.order.findUnique({
    where: { id: createdId },
    include: orderInclude,
  });
  if (!order) throw new ApiError(500, "Order could not be loaded after creation.");

  await logAudit(user, "ORDER_CREATED", "ORDER", order.id, {
    orderNumber: order.orderNumber,
    customerName: order.customer.name,
    priority: order.priority,
    newCustomer: !!body.newCustomer,
  });

  return json({ order: serializeOrder(order) }, 201);
});
