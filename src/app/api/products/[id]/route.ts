import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { productUpdateSchema } from "@/lib/validation";

type Ctx = { params: Promise<{ id: string }> };

/** PATCH /api/products/:id — update a product and its components (requires products.manage). */
export const PATCH = withApi(async (req: Request, ctx: Ctx) => {
  await requirePermission("products.manage");
  const { id } = await ctx.params;
  const data = await parseBody(req, productUpdateSchema);

  const product = await db.product.findUnique({ where: { id } });
  if (!product) throw new ApiError(404, "Product not found");

  const updated = await db.$transaction(async (tx) => {
    if (data.components !== undefined) {
      // Components are replaced atomically; existing production tasks are
      // unaffected (they store their own piece names).
      await tx.productComponent.deleteMany({ where: { productId: id } });
      if (data.components.length > 0) {
        await tx.productComponent.createMany({
          data: data.components.map((c, i) => ({
            productId: id,
            name: c.name,
            defaultInstructions: c.defaultInstructions ?? null,
            sortOrder: i,
          })),
        });
      }
    }
    return tx.product.update({
      where: { id },
      data: {
        ...(data.name !== undefined ? { name: data.name } : {}),
        ...(data.category !== undefined ? { category: data.category } : {}),
        ...(data.description !== undefined ? { description: data.description ?? null } : {}),
        ...(data.basePrice !== undefined ? { basePrice: data.basePrice } : {}),
        ...(data.imageUrl !== undefined ? { imageUrl: data.imageUrl ?? null } : {}),
        ...(data.isActive !== undefined ? { isActive: data.isActive } : {}),
      },
      include: { components: { orderBy: { sortOrder: "asc" } } },
    });
  });
  return json({ product: updated });
});
