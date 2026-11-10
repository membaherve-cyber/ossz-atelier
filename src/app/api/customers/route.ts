import { db } from "@/lib/db";
import { json, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";

/** GET /api/customers — customer list for order intake (requires orders.view). */
export const GET = withApi(async () => {
  await requirePermission("orders.view");
  const customers = await db.customer.findMany({
    orderBy: { name: "asc" },
    select: { id: true, name: true, email: true, phone: true },
  });
  return json({ customers });
});
