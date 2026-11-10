import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { createBreakdownSchema } from "@/lib/validation";
import { recordEvent, syncOrderStatus } from "@/lib/production";

type Ctx = { params: Promise<{ id: string }> };

/**
 * POST /api/orders/:id/production-tasks
 * Creates the production breakdown (one task per garment piece).
 *
 * - If `items` is provided, uses the explicit piece lists.
 * - Otherwise auto-generates pieces from each order item's product components
 *   (or the product itself when it has no components), multiplying by quantity.
 * - Idempotent: pieces that already exist for the order item are skipped,
 *   so repeated processing of the same order never duplicates tasks.
 */
export const POST = withApi(async (req: Request, ctx: Ctx) => {
  const user = await requirePermission("production.create.tasks");
  const { id } = await ctx.params;
  const body = await parseBody(req, createBreakdownSchema);

  const order = await db.order.findUnique({
    where: { id },
    include: {
      items: {
        include: { product: { include: { components: { orderBy: { sortOrder: "asc" } } } } },
      },
    },
  });
  if (!order) throw new ApiError(404, "Order not found");
  if (order.status === "CANCELLED") {
    throw new ApiError(400, "Cannot create production tasks for a cancelled order");
  }

  // Build the list of (orderItemId, pieceName, instructions) to create.
  type PieceSpec = { orderItemId: string; name: string; instructions: string | null };
  const wanted: PieceSpec[] = [];

  if (body.items) {
    const itemMap = new Map(order.items.map((i) => [i.id, i]));
    for (const entry of body.items) {
      const item = itemMap.get(entry.orderItemId);
      if (!item) throw new ApiError(400, `Order item ${entry.orderItemId} does not belong to order ${order.reference}`);
      for (const piece of entry.pieces) {
        wanted.push({
          orderItemId: item.id,
          name: piece.name,
          instructions: piece.productionInstructions ?? null,
        });
      }
    }
  } else {
    for (const item of order.items) {
      const unitNames =
        item.product.components.length > 0
          ? item.product.components.map((c) => ({
              name: c.name,
              instructions: c.defaultInstructions ?? null,
            }))
          : [{ name: item.product.name, instructions: null }];
      for (let unit = 1; unit <= item.quantity; unit++) {
        for (const u of unitNames) {
          wanted.push({
            orderItemId: item.id,
            name: item.quantity > 1 ? `${u.name} #${unit}` : u.name,
            instructions: u.instructions,
          });
        }
      }
    }
  }

  if (wanted.length === 0) {
    throw new ApiError(400, "No garment pieces to create for this order");
  }

  const result = await db.$transaction(async (tx) => {
    const existing = await tx.productionTask.findMany({
      where: { orderId: order.id },
      select: { orderItemId: true, pieceName: true },
    });
    const existingKeys = new Set(existing.map((t) => `${t.orderItemId}::${t.pieceName}`));

    const created: Array<{ id: string; pieceName: string }> = [];
    for (const piece of wanted) {
      const key = `${piece.orderItemId}::${piece.name}`;
      if (existingKeys.has(key)) continue; // duplicate prevention
      const task = await tx.productionTask.create({
        data: {
          orderId: order.id,
          orderItemId: piece.orderItemId,
          pieceName: piece.name,
          productionInstructions: piece.instructions,
          status: "UNASSIGNED",
        },
      });
      await recordEvent(tx, {
        taskId: task.id,
        orderId: order.id,
        eventType: "TASK_CREATED",
        actorId: user.id,
        newValue: piece.name,
      });
      created.push({ id: task.id, pieceName: piece.name });
      existingKeys.add(key);
    }
    await syncOrderStatus(tx, order.id);
    return created;
  });

  return json({
    created: result,
    createdCount: result.length,
    skippedCount: wanted.length - result.length,
  });
});
