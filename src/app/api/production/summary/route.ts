import { db } from "@/lib/db";
import { json, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { OVERDUE_STATUSES } from "@/lib/production";

/**
 * GET /api/production/summary — management dashboard cards and recent activity.
 * Requires production.view.all.
 */
export const GET = withApi(async () => {
  await requirePermission("production.view.all");

  const [tasks, orderCountAwaiting] = await Promise.all([
    db.productionTask.findMany({
      select: {
        id: true,
        status: true,
        progressPercentage: true,
        dueAt: true,
        orderId: true,
        pieceName: true,
      },
    },
    ),
    db.order.count(),
  ]);

  const byStatus = (s: string) => tasks.filter((t) => t.status === s).length;
  const overdue = tasks.filter(
    (t) => t.dueAt && t.dueAt.getTime() < Date.now() && OVERDUE_STATUSES.includes(t.status as never)
  );

  // Orders with at least one non-cancelled, non-approved piece still in production.
  const ordersInProduction = new Set(
    tasks.filter((t) => t.status !== "CANCELLED" && t.status !== "APPROVED").map((t) => t.orderId)
  );

  const recentEvents = await db.productionEvent.findMany({
    orderBy: { createdAt: "desc" },
    take: 10,
    include: {
      actor: { select: { id: true, name: true } },
      task: { select: { id: true, pieceName: true } },
      order: { select: { id: true, reference: true } },
    },
  });

  return json({
    summary: {
      totalPieces: tasks.length,
      unassigned: byStatus("UNASSIGNED"),
      assigned: byStatus("ASSIGNED"),
      inProgress: byStatus("IN_PROGRESS"),
      awaitingInspection: byStatus("AWAITING_INSPECTION"),
      changesRequested: byStatus("CHANGES_REQUESTED"),
      approved: byStatus("APPROVED"),
      blocked: byStatus("BLOCKED"),
      cancelled: byStatus("CANCELLED"),
      overdue: overdue.length,
      ordersAwaitingProduction: ordersInProduction.size,
      totalOrders: orderCountAwaiting,
    },
    recentEvents,
  });
});
