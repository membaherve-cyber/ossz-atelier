import { db } from "@/lib/db";
import { json, withApi } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { serializeTask, taskInclude } from "@/lib/atelier/tasks";
import { taskIsOverdue } from "@/lib/atelier/constants";
import { notifyUsers } from "@/lib/atelier/notify";

/**
 * Central dashboard (role aware).
 * - MANAGEMENT/ADMIN: full production statistics, overdue & due-soon lists,
 *   staff workload and recent activity.
 * - STAFF: personal statistics and assigned task list.
 */
export const GET = withApi(async () => {
  const user = await requireUser();
  const now = new Date();
  const soon = new Date(now.getTime() + 7 * 24 * 3600 * 1000);

  if (user.role === "MANAGEMENT" || user.role === "ADMIN") {
    const tasks = await db.productionTask.findMany({
      include: taskInclude,
      orderBy: { updatedAt: "desc" },
    });
    const [totalOrders, recentActivity, staff] = await Promise.all([
      db.order.count(),
      db.auditLog.findMany({ orderBy: { createdAt: "desc" }, take: 12 }),
      db.user.findMany({
        where: { role: "STAFF", isActive: true },
        select: { id: true, name: true },
        orderBy: { name: "asc" },
      }),
    ]);

    // One-time overdue notifications for newly overdue tasks (no cron needed).
    const overdueTasks = tasks.filter(
      (t) => taskIsOverdue(t.deadline, t.status, now) && t.assignedStaffId
    );
    if (overdueTasks.length > 0) {
      const existing = await db.notification.findMany({
        where: { type: "TASK_OVERDUE", taskId: { in: overdueTasks.map((t) => t.id) } },
        select: { taskId: true },
      });
      const alreadyNotified = new Set(existing.map((n) => n.taskId));
      const fresh = overdueTasks.filter((t) => !alreadyNotified.has(t.id));
      if (fresh.length > 0) {
        const managers = await db.user.findMany({
          where: { isActive: true, role: { in: ["MANAGEMENT", "ADMIN"] } },
          select: { id: true },
        });
        for (const t of fresh) {
          await notifyUsers(
            managers.map((m) => m.id),
            "TASK_OVERDUE",
            "Task overdue",
            `${t.code} — ${t.garmentPiece} (${t.order.orderNumber}) has passed its deadline.`,
            t.id,
            t.orderId
          );
        }
      }
    }

    const serialized = tasks.map(serializeTask);
    const active = serialized.filter(
      (t) => !["DELIVERED", "CANCELLED"].includes(t.status)
    );

    const stats = {
      totalOrders,
      activeTasks: active.length,
      unassigned: serialized.filter((t) => t.status === "UNASSIGNED").length,
      ongoing: serialized.filter((t) => t.status === "ONGOING").length,
      awaitingInspection: serialized.filter((t) => t.status === "AWAITING_INSPECTION").length,
      approved: serialized.filter((t) => t.status === "APPROVED").length,
      readyForPickup: serialized.filter((t) => t.status === "READY_FOR_PICKUP").length,
      delivered: serialized.filter((t) => t.status === "DELIVERED").length,
      blocked: serialized.filter((t) => t.status === "BLOCKED").length,
      overdue: serialized.filter((t) => t.overdue).length,
    };

    const staffWorkload = staff.map((s) => {
      const mine = active.filter((t) => t.assignedStaffId === s.id);
      return {
        staffId: s.id,
        staffName: s.name,
        activeTasks: mine.length,
        overdue: mine.filter((t) => t.overdue).length,
        awaitingInspection: mine.filter((t) => t.status === "AWAITING_INSPECTION").length,
      };
    });

    return json({
      role: user.role,
      stats,
      overdueTasks: serialized.filter((t) => t.overdue).slice(0, 8),
      dueSoon: serialized
        .filter(
          (t) =>
            t.deadline &&
            !t.overdue &&
            !["DELIVERED", "CANCELLED"].includes(t.status) &&
            new Date(t.deadline) <= soon
        )
        .sort((a, b) => new Date(a.deadline!).getTime() - new Date(b.deadline!).getTime())
        .slice(0, 8),
      staffWorkload,
      recentActivity: recentActivity.map((a) => ({
        id: a.id,
        userName: a.userName,
        action: a.action,
        entityType: a.entityType,
        entityId: a.entityId,
        createdAt: a.createdAt.toISOString(),
      })),
    });
  }

  // --- Staff dashboard ------------------------------------------------------
  const myTasks = await db.productionTask.findMany({
    where: { assignedStaffId: user.id },
    include: taskInclude,
    orderBy: { updatedAt: "desc" },
  });
  const serialized = myTasks.map(serializeTask);
  const active = serialized.filter((t) => !["DELIVERED", "CANCELLED"].includes(t.status));
  const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

  const completedThisMonth = await db.productionTask.count({
    where: {
      assignedStaffId: user.id,
      completedAt: { gte: monthStart },
    },
  });

  const recentNotifications = await db.notification.findMany({
    where: { userId: user.id },
    orderBy: { createdAt: "desc" },
    take: 6,
  });

  return json({
    role: user.role,
    stats: {
      myActive: active.length,
      myAwaiting: serialized.filter((t) => t.status === "AWAITING_INSPECTION").length,
      myCompletedThisMonth: completedThisMonth,
      myOverdue: serialized.filter((t) => t.overdue).length,
    },
    myTasks: serialized,
    upcomingDeadlines: active
      .filter((t) => t.deadline)
      .sort((a, b) => new Date(a.deadline!).getTime() - new Date(b.deadline!).getTime())
      .slice(0, 5),
    recentNotifications: recentNotifications.map((n) => ({
      id: n.id,
      type: n.type,
      title: n.title,
      message: n.message,
      isRead: n.isRead,
      createdAt: n.createdAt.toISOString(),
    })),
  });
});
