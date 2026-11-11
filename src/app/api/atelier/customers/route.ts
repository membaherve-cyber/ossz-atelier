import { z } from "zod";
import { db } from "@/lib/db";
import { json, parseBody, parseQuery, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { nextCustomerCode } from "@/lib/atelier/tasks";

// ---------------------------------------------------------------------------
// GET /api/atelier/customers — searchable customer directory with order counts.
// ---------------------------------------------------------------------------

const listQuerySchema = z.object({
  search: z.string().trim().max(120).optional(),
});

export const GET = withApi(async (req: Request) => {
  await requirePermission("customers.view");
  const q = parseQuery(req, listQuerySchema);

  const where = q.search
    ? {
        OR: [
          { name: { contains: q.search } },
          { phone: { contains: q.search } },
          { email: { contains: q.search } },
          { code: { contains: q.search } },
        ],
      }
    : {};

  const customers = await db.customer.findMany({
    where,
    orderBy: { name: "asc" },
    include: { _count: { select: { orders: true } } },
  });

  return json({
    customers: customers.map((c) => ({
      id: c.id,
      code: c.code,
      name: c.name,
      phone: c.phone,
      email: c.email,
      address: c.address,
      notes: c.notes,
      createdAt: c.createdAt.toISOString(),
      orderCount: c._count.orders,
    })),
  });
});

// ---------------------------------------------------------------------------
// POST /api/atelier/customers — register a new customer (code auto-generated).
// ---------------------------------------------------------------------------

const optionalEmail = z.preprocess(
  (v) => (typeof v === "string" && v.trim() === "" ? undefined : v),
  z.email("Enter a valid email address").optional()
);

const customerSchema = z.object({
  name: z.string().trim().min(1, "Customer name is required").max(120),
  phone: z.string().trim().min(3, "Phone number is required").max(40),
  email: optionalEmail,
  address: z.string().trim().max(300).optional(),
  notes: z.string().trim().max(2000).optional(),
});

export const POST = withApi(async (req: Request) => {
  const user = await requirePermission("customers.manage");
  const body = await parseBody(req, customerSchema);

  const code = await nextCustomerCode();
  const customer = await db.customer.create({
    data: {
      code,
      name: body.name,
      phone: body.phone,
      email: body.email ?? null,
      address: body.address || null,
      notes: body.notes || null,
      createdById: user.id,
    },
  });

  await logAudit(user, "CUSTOMER_CREATED", "CUSTOMER", customer.id, {
    code: customer.code,
    name: customer.name,
  });

  return json(
    {
      customer: {
        id: customer.id,
        code: customer.code,
        name: customer.name,
        phone: customer.phone,
        email: customer.email,
        address: customer.address,
        notes: customer.notes,
        createdAt: customer.createdAt.toISOString(),
        orderCount: 0,
      },
    },
    201
  );
});
