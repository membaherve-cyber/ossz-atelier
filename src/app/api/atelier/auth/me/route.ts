import { db } from "@/lib/db";
import { json, withApi } from "@/lib/api";
import { getSessionUser } from "@/lib/auth";

export const GET = withApi(async () => {
  const user = await getSessionUser();
  if (!user) return json({ user: null });
  const unreadCount = await db.notification.count({
    where: { userId: user.id, isRead: false },
  });
  return json({ user, unreadCount });
});
