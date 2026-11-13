import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission, requireUser } from "@/lib/auth";
import { productSchema } from "@/lib/validation";

/** GET /api/products — product catalog with garment components. */
export const GET = withApi(async (req) => {
  await requireUser();
  const url = new URL(req.url);
  const activeOnly = url.searchParams.get("all") !== "1";

  const products = await db.product.findMany({
    where: activeOnly ? { isActive: true } : undefined,
    orderBy: { name: "asc" },
    include: {
      components: { orderBy: { sortOrder: "asc" } },
    },
  });
  return json({ products });
});

/** POST /api/products — create a product (requires products.manage). */
export const POST = withApi(async (req) => {
  await requirePermission("products.manage");
  const data = await parseBody(req, productSchema);

  const product = await db.$transaction(async (tx) => {
    return tx.product.create({
      data: {
        name: data.name,
        category: data.category,
        description: data.description ?? null,
        basePrice: data.basePrice,
        imageUrl: data.imageUrl ?? null,
        isActive: data.isActive ?? true,
        components: {
          create: (data.components ?? []).map((c, i) => ({
            name: c.name,
            defaultInstructions: c.defaultInstructions ?? null,
            sortOrder: i,
          })),
        },
      },
      include: { components: { orderBy: { sortOrder: "asc" } } },
    });
  });
  return json({ product }, 201);
});
