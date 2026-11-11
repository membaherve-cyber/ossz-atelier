import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";

// ---------------------------------------------------------------------------
// PATCH /api/atelier/customers/[id] — update an existing customer.
// ---------------------------------------------------------------------------

const optionalEmail = z.preprocess(
  (v) => (typeof v === "string" && v.trim() === "" ? null : v),
  z.email("Enter a valid email address").nullable().optional()
);

const updateCustomerSchema = z.object({
  name: z.string().trim().min(1, "Customer name is required").max(120).optional(),
  phone: z.string().trim().min(3, "Phone number is required").max(40).optional(),
  email: optionalEmail,
  address: z.string().trim().max(300).optional(),
  notes: z.string().trim().max(2000).optional(),
});

export const PATCH = withApi(async (req: Request, { params }: { params: Promise<{ id: string }> }) => {
  const user = await requirePermission("customers.manage");
  const { id } = await params;
  const body = await parseBody(req, updateCustomerSchema);

  const existing = await db.customer.findUnique({ where: { id } });
  if (!existing) throw new ApiError(404, "Customer not found.");

  const data: Record<string, string | null> = {};
  const changedFields: string[] = [];
  if (body.name !== undefined && body.name !== existing.name) {
    data.name = body.name;
    changedFields.push("name");
  }
  if (body.phone !== undefined && body.phone !== existing.phone) {
    data.phone = body.phone;
    changedFields.push("phone");
  }
  if (body.email !== undefined && (body.email ?? null) !== existing.email) {
    data.email = body.email ?? null;
    changedFields.push("email");
  }
  if (body.address !== undefined && (body.address || null) !== existing.address) {
    data.address = body.address || null;
    changedFields.push("address");
  }
  if (body.notes !== undefined && (body.notes || null) !== existing.notes) {
    data.notes = body.notes || null;
    changedFields.push("notes");
  }

  const customer = await db.customer.update({ where: { id }, data });

  await logAudit(user, "CUSTOMER_UPDATED", "CUSTOMER", customer.id, {
    code: customer.code,
    changedFields,
  });

  const orderCount = await db.order.count({ where: { customerId: customer.id } });

  return json({
    customer: {
      id: customer.id,
      code: customer.code,
      name: customer.name,
      phone: customer.phone,
      email: customer.email,
      address: customer.address,
      notes: customer.notes,
      createdAt: customer.createdAt.toISOString(),
      orderCount,
    },
  });
});
