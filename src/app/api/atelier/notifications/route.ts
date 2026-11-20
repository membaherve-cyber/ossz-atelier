import { z } from "zod";
import { db } from "@/lib/db";
import { json, parseBody, withApi } from "@/lib/api";
import { requireUser } from "@/lib/auth";

/** Latest notifications for the signed-in user + unread count. */
export const GET = withApi(async (req: Request) => {
  const user = await requireUser();
  const url = new URL(req.url);
  // Clamp to [1, 100] so a malformed/negative `limit` query never produces a
  // Prisma `take` error (which would surface as a 500). `Number("abc")` -> NaN
  // -> `|| 50` -> 50; `Number("-5")` -> -5 -> clamped to 1.
  const limit = Math.max(1, Math.min(100, Number(url.searchParams.get("limit") ?? 50) || 50));

  const [notifications, unreadCount] = await Promise.all([
    db.notification.findMany({
      where: { userId: user.id },
      orderBy: { createdAt: "desc" },
      take: limit,
    }),
    db.notification.count({ where: { userId: user.id, isRead: false } }),
  ]);

  return json({
    notifications: notifications.map((n) => ({
      id: n.id,
      type: n.type,
      title: n.title,
      message: n.message,
      taskId: n.taskId,
      orderId: n.orderId,
      isRead: n.isRead,
      createdAt: n.createdAt.toISOString(),
    })),
    unreadCount,
  });
});

const markSchema = z.object({
  id: z.string().optional(),
  all: z.boolean().optional(),
});

/** Mark one or all notifications as read. */
export const PATCH = withApi(async (req: Request) => {
  const user = await requireUser();
  const body = await parseBody(req, markSchema);

  if (body.all) {
    await db.notification.updateMany({
      where: { userId: user.id, isRead: false },
      data: { isRead: true },
    });
  } else if (body.id) {
    await db.notification.updateMany({
      where: { id: body.id, userId: user.id },
      data: { isRead: true },
    });
  }

  const unreadCount = await db.notification.count({
    where: { userId: user.id, isRead: false },
  });
  return json({ ok: true, unreadCount });
});
