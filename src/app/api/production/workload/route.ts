import { db } from "@/lib/db";
import { json, withApi } from "@/lib/api";
import { requirePermission } from "@/lib/auth";
import { ASSIGNABLE_ROLES } from "@/lib/permissions";

/**
 * GET /api/production/workload — per-staff task workload.
 * Requires production.view.all.
 */
export const GET = withApi(async () => {
  await requirePermission("production.view.all");

  const staff = await db.user.findMany({
    where: { isActive: true },
    select: { id: true, name: true, role: true },
    orderBy: { name: "asc" },
  });

  const tasks = await db.productionTask.findMany({
    where: { status: { not: "CANCELLED" } },
    select: {
      assignedStaffId: true,
      status: true,
      progressPercentage: true,
      dueAt: true,
    },
  });

  const workload = staff
    .filter((s) => ASSIGNABLE_ROLES.includes(s.role as (typeof ASSIGNABLE_ROLES)[number]))
    .map((s) => {
      const own = tasks.filter((t) => t.assignedStaffId === s.id);
      const active = own.filter((t) =>
        ["ASSIGNED", "IN_PROGRESS", "CHANGES_REQUESTED", "AWAITING_INSPECTION", "BLOCKED"].includes(t.status)
      );
      const overdue = active.filter(
        (t) => t.dueAt && t.dueAt.getTime() < Date.now()
      );
      const avgProgress = own.length
        ? Math.round(own.reduce((sum, t) => sum + t.progressPercentage, 0) / own.length)
        : 0;
      return {
        staffId: s.id,
        name: s.name,
        role: s.role,
        total: own.length,
        notStarted: own.filter((t) => t.status === "ASSIGNED").length,
        inProgress: own.filter((t) => t.status === "IN_PROGRESS").length,
        awaitingInspection: own.filter((t) => t.status === "AWAITING_INSPECTION").length,
        changesRequested: own.filter((t) => t.status === "CHANGES_REQUESTED").length,
        approved: own.filter((t) => t.status === "APPROVED").length,
        blocked: own.filter((t) => t.status === "BLOCKED").length,
        active: active.length,
        overdue: overdue.length,
        avgProgress,
      };
    });

  return json({ workload });
});
