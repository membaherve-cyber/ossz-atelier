import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { hasPermission } from "@/lib/permissions";
import { notificationsReadSchema } from "@/lib/validation";
import { notifyUsers, OVERDUE_STATUSES } from "@/lib/production";

/**
 * GET /api/notifications — the authenticated user's notifications with an
 * unread count. For managers, newly overdue tasks are detected (idempotently)
 * and turned into TASK_OVERDUE notifications.
 */
export const GET = withApi(async () => {
  const user = await requireUser();

  // Overdue sweep (only for users who can view all production).
  if (hasPermission(user.role, "production.view.all")) {
    const overdueTasks = await db.productionTask.findMany({
      where: {
        dueAt: { lt: new Date() },
        status: { in: [...OVERDUE_STATUSES] },
      },
      select: { id: true, orderId: true, pieceName: true },
      take: 200,
    });
    for (const task of overdueTasks) {
      const existing = await db.notification.findFirst({
        where: { type: "TASK_OVERDUE", taskId: task.id },
        select: { id: true },
      });
      if (!existing) {
        await notifyUsers(db, [user.id], {
          title: `Overdue: ${task.pieceName}`,
          body: "This production task has passed its deadline.",
          taskId: task.id,
          orderId: task.orderId,
          type: "TASK_OVERDUE",
        });
      }
    }
  }

  const [notifications, unreadCount] = await Promise.all([
    db.notification.findMany({
      where: { userId: user.id },
      orderBy: { createdAt: "desc" },
      take: 50,
      include: {
        order: { select: { reference: true } },
        task: { select: { pieceName: true } },
      },
    }),
    db.notification.count({ where: { userId: user.id, isRead: false } }),
  ]);

  return json({ notifications, unreadCount });
});

/** POST /api/notifications — mark notifications as read (own only). */
export const POST = withApi(async (req) => {
  const user = await requireUser();
  const body = await parseBody(req, notificationsReadSchema);

  if (body.all) {
    await db.notification.updateMany({
      where: { userId: user.id, isRead: false },
      data: { isRead: true },
    });
    return json({ ok: true });
  }

  if (body.ids && body.ids.length > 0) {
    const result = await db.notification.updateMany({
      where: { userId: user.id, id: { in: body.ids }, isRead: false },
      data: { isRead: true },
    });
    return json({ ok: true, updated: result.count });
  }

  throw new ApiError(400, "Provide ids or all=true");
});
