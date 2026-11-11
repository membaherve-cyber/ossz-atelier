// OSSZ ATELIER — internal notifications (no external SMS/WhatsApp/Email).

import { db } from "@/lib/db";
import type { Role } from "@/lib/permissions";

export async function notifyUsers(
  userIds: string[],
  type: string,
  title: string,
  message?: string,
  taskId?: string,
  orderId?: string
): Promise<void> {
  const unique = [...new Set(userIds.filter(Boolean))];
  if (unique.length === 0) return;
  try {
    await db.notification.createMany({
      data: unique.map((userId) => ({
        userId,
        type,
        title,
        message: message ?? null,
        taskId: taskId ?? null,
        orderId: orderId ?? null,
      })),
    });
  } catch (err) {
    console.error("[notify] failed", type, err);
  }
}

/** Notify every active user holding one of the given roles. */
export async function notifyRoles(
  roles: Role[],
  type: string,
  title: string,
  message?: string,
  taskId?: string,
  orderId?: string
): Promise<void> {
  try {
    const users = await db.user.findMany({
      where: { isActive: true, role: { in: roles as string[] } },
      select: { id: true },
    });
    await notifyUsers(
      users.map((u) => u.id),
      type,
      title,
      message,
      taskId,
      orderId
    );
  } catch (err) {
    console.error("[notifyRoles] failed", type, err);
  }
}

export async function notifyManagement(
  type: string,
  title: string,
  message?: string,
  taskId?: string,
  orderId?: string
): Promise<void> {
  await notifyRoles(["MANAGEMENT", "ADMIN"], type, title, message, taskId, orderId);
}
