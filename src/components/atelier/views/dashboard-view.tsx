"use client";

// OSSZ ATELIER — central dashboard, role aware:
// - Management/Admin: production statistics, overdue & due-soon work, staff
//   workload and recent activity.
// - Staff: personal statistics and assigned tasks.

import { useEffect, useState } from "react";
import { api } from "../client";
import { useAppStore } from "../store";
import type { ManagerDashboard, StaffDashboard } from "../types";
import {
  StatCard,
  StatusBadge,
  ProgressCell,
  DueDate,
  EmptyState,
  SectionCard,
  PageHeader,
} from "../ui-bits";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { useI18n } from "../i18n/use-i18n";
import { roleLabel } from "../i18n/core";
import { timeAgo } from "@/lib/format";
import {
  Package,
  Shirt,
  HelpCircle,
  Loader,
  ClipboardCheck,
  BadgeCheck,
  ShoppingBag,
  Truck,
  Ban,
  AlertTriangle,
  Users,
  History,
  ListTodo,
} from "lucide-react";

export function DashboardView() {
  const { t, lang } = useI18n();
  const user = useAppStore((s) => s.user);
  const navigate = useAppStore((s) => s.navigate);
  const openTask = useAppStore((s) => s.openTask);
  const [data, setData] = useState<ManagerDashboard | StaffDashboard | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    api
      .get<ManagerDashboard | StaffDashboard>("/api/atelier/dashboard")
      .then((res) => {
        if (cancelled) return;
        if (res && res.stats && res.role) {
          setData(res);
        } else {
          setError(t("dash.loadError"));
        }
      })
      .catch((e) => !cancelled && setError(e.message));
    return () => {
      cancelled = true;
    };
  }, []);

  if (error) {
    return <EmptyState icon={AlertTriangle} title={t("dash.loadError")} description={error} />;
  }
  if (!data || !user) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-56" />
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          {Array.from({ length: 8 }).map((_, i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
        <Skeleton className="h-64" />
      </div>
    );
  }

  // ------------------------------------------------------------------ Staff
  if (data.role === "STAFF") {
    const d = data as StaffDashboard;
    return (
      <div>
        <PageHeader
          title={t("dash.welcome", { name: user.name.split(" ")[0] })}
          description={t("dash.staffDesc")}
        />
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
          <StatCard label={t("dash.stat.myActive")} value={d.stats.myActive} icon={ListTodo} tone="info" onClick={() => navigate("tasks")} />
          <StatCard label={t("dash.stat.awaiting")} value={d.stats.myAwaiting} icon={ClipboardCheck} tone="warning" onClick={() => navigate("tasks")} />
          <StatCard label={t("dash.stat.completedThisMonth")} value={d.stats.myCompletedThisMonth} icon={BadgeCheck} tone="success" />
          <StatCard label={t("dash.stat.overdue")} value={d.stats.myOverdue} icon={AlertTriangle} tone={d.stats.myOverdue > 0 ? "danger" : "default"} onClick={() => navigate("tasks")} />
        </div>

        <SectionCard
          title={t("dash.myTasks")}
          action={
            <Button variant="ghost" size="sm" onClick={() => navigate("tasks")}>
              {t("dash.viewAll")}
            </Button>
          }
        >
          {d.myTasks.length === 0 ? (
            <EmptyState
              title={t("dash.noTasksTitle")}
              description={t("dash.noTasksDesc")}
            />
          ) : (
            <div className="divide-y rounded-lg border overflow-x-auto">
              {d.myTasks.slice(0, 6).map((task) => (
                <button
                  key={task.id}
                  onClick={() => openTask(task.id)}
                  className="w-full grid grid-cols-[1fr_auto] sm:grid-cols-[2fr_2fr_1fr_130px_150px] gap-x-4 items-center px-4 py-3 text-left hover:bg-muted/50 transition-colors min-w-[640px]"
                >
                  <div className="min-w-0">
                    <p className="font-medium text-sm truncate">
                      {task.garmentPiece} <span className="text-muted-foreground">· {task.code}</span>
                    </p>
                    <p className="text-xs text-muted-foreground truncate">
                      {task.orderNumber} — {task.customerName}
                    </p>
                  </div>
                  <div className="hidden sm:block min-w-0">
                    <StatusBadge status={task.status} />
                  </div>
                  <div className="hidden sm:block">
                    <DueDate deadline={task.deadline} overdue={task.overdue} />
                  </div>
                  <ProgressCell value={task.progress} status={task.status} />
                  <span className="hidden sm:block text-xs text-muted-foreground text-right">
                    {timeAgo(task.updatedAt)}
                  </span>
                </button>
              ))}
            </div>
          )}
        </SectionCard>
      </div>
    );
  }

  // ------------------------------------------------------- Management / Admin
  const d = data as ManagerDashboard;
  const supervisorName = user.role === "MANAGEMENT" ? roleLabel("MANAGEMENT", lang) : roleLabel("ADMIN", lang);
  return (
    <div>
      <PageHeader
        title={t("dash.managerTitle", { role: supervisorName })}
        description={t("dash.managerDesc")}
      >
        <Button size="sm" onClick={() => navigate("task-create")}>
          <Shirt className="size-4 mr-1.5" />
          {t("dash.createTasks")}
        </Button>
      </PageHeader>

      <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 mb-6">
        <StatCard label={t("dash.stat.totalOrders")} value={d.stats.totalOrders} icon={Package} onClick={() => navigate("orders")} />
        <StatCard label={t("dash.stat.activeTasks")} value={d.stats.activeTasks} icon={Shirt} tone="info" onClick={() => navigate("tasks")} />
        <StatCard label={t("dash.stat.unassigned")} value={d.stats.unassigned} icon={HelpCircle} tone={d.stats.unassigned > 0 ? "warning" : "default"} onClick={() => navigate("tasks")} />
        <StatCard label={t("dash.stat.ongoing")} value={d.stats.ongoing} icon={Loader} onClick={() => navigate("tasks")} />
        <StatCard label={t("dash.stat.awaiting")} value={d.stats.awaitingInspection} icon={ClipboardCheck} tone="warning" onClick={() => navigate("tasks")} />
        <StatCard label={t("dash.stat.approved")} value={d.stats.approved} icon={BadgeCheck} tone="success" onClick={() => navigate("tasks")} />
        <StatCard label={t("dash.stat.readyForPickup")} value={d.stats.readyForPickup} icon={ShoppingBag} tone="success" onClick={() => navigate("tasks")} />
        <StatCard label={t("dash.stat.delivered")} value={d.stats.delivered} icon={Truck} onClick={() => navigate("tasks")} />
        <StatCard label={t("dash.stat.blocked")} value={d.stats.blocked} icon={Ban} tone={d.stats.blocked > 0 ? "danger" : "default"} onClick={() => navigate("tasks")} />
        <StatCard label={t("dash.stat.overdue")} value={d.stats.overdue} icon={AlertTriangle} tone={d.stats.overdue > 0 ? "danger" : "default"} onClick={() => navigate("tasks")} />
      </div>

      <div className="grid lg:grid-cols-2 gap-4 mb-4">
        <SectionCard
          title={t("dash.overdueTitle")}
          action={
            <Button variant="ghost" size="sm" onClick={() => navigate("tasks")}>
              {t("dash.productionBoard")}
            </Button>
          }
        >
          {d.overdueTasks.length === 0 ? (
            <EmptyState icon={BadgeCheck} title={t("dash.overdueEmptyTitle")} description={t("dash.overdueEmptyDesc")} />
          ) : (
            <div className="divide-y rounded-lg border overflow-x-auto">
              {d.overdueTasks.map((task) => (
                <button
                  key={task.id}
                  onClick={() => openTask(task.id)}
                  className="w-full flex items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-muted/50 min-w-[420px]"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium truncate">
                      {task.code} — {task.garmentPiece}
                    </p>
                    <p className="text-xs text-muted-foreground truncate">
                      {task.orderNumber} · {task.customerName} · {task.assignedStaffName ?? t("dash.unassigned")}
                    </p>
                  </div>
                  <div className="flex items-center gap-3 shrink-0">
                    <ProgressCell value={task.progress} status={task.status} className="w-28" />
                    <DueDate deadline={task.deadline} overdue />
                  </div>
                </button>
              ))}
            </div>
          )}
        </SectionCard>

        <SectionCard title={t("dash.dueSoonTitle")}>
          {d.dueSoon.length === 0 ? (
            <EmptyState title={t("dash.dueSoonEmptyTitle")} description={t("dash.dueSoonEmptyDesc")} />
          ) : (
            <div className="divide-y rounded-lg border overflow-x-auto">
              {d.dueSoon.map((task) => (
                <button
                  key={task.id}
                  onClick={() => openTask(task.id)}
                  className="w-full flex items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-muted/50 min-w-[420px]"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium truncate">
                      {task.code} — {task.garmentPiece}
                    </p>
                    <p className="text-xs text-muted-foreground truncate">
                      {task.orderNumber} · {task.assignedStaffName ?? t("dash.unassigned")}
                    </p>
                  </div>
                  <div className="flex items-center gap-3 shrink-0">
                    <ProgressCell value={task.progress} status={task.status} className="w-28" />
                    <DueDate deadline={task.deadline} />
                  </div>
                </button>
              ))}
            </div>
          )}
        </SectionCard>
      </div>

      <div className="grid lg:grid-cols-2 gap-4">
        <SectionCard title={t("dash.workloadTitle")}>
          {d.staffWorkload.length === 0 ? (
            <EmptyState icon={Users} title={t("dash.workloadEmpty")} />
          ) : (
            <div className="divide-y rounded-lg border">
              {d.staffWorkload.map((s) => (
                <div key={s.staffId} className="flex items-center justify-between px-4 py-2.5">
                  <div>
                    <p className="text-sm font-medium">{s.staffName}</p>
                    <p className="text-xs text-muted-foreground">
                      {t("dash.awaitingCount", { count: s.awaitingInspection })}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    {s.overdue > 0 && (
                      <span className="rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-semibold px-2 py-0.5">
                        {t("dash.overdueCount", { count: s.overdue })}
                      </span>
                    )}
                    <span className="rounded-full bg-primary/10 text-primary text-sm font-bold px-2.5 py-0.5 tabular-nums">
                      {s.activeTasks}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </SectionCard>

        <SectionCard title={t("dash.activityTitle")}>
          {d.recentActivity.length === 0 ? (
            <EmptyState icon={History} title={t("dash.activityEmpty")} />
          ) : (
            <div className="divide-y rounded-lg border max-h-80 overflow-y-auto">
              {d.recentActivity.map((a) => (
                <div key={a.id} className="flex items-center gap-3 px-4 py-2.5">
                  <div className="min-w-0 flex-1">
                    <p className="text-sm">
                      <span className="font-medium">{a.userName ?? t("dash.system")}</span>{" "}
                      <span className="text-muted-foreground">{a.action.replaceAll("_", " ").toLowerCase()}</span>
                    </p>
                    <p className="text-xs text-muted-foreground">{a.entityType ?? ""}</p>
                  </div>
                  <span className="text-xs text-muted-foreground shrink-0">{timeAgo(a.createdAt)}</span>
                </div>
              ))}
            </div>
          )}
        </SectionCard>
      </div>
    </div>
  );
}
