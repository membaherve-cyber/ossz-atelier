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
  const user = useAppStore((s) => s.user);
  const navigate = useAppStore((s) => s.navigate);
  const openTask = useAppStore((s) => s.openTask);
  const [data, setData] = useState<ManagerDashboard | StaffDashboard | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    api
      .get<ManagerDashboard | StaffDashboard>("/api/atelier/dashboard")
      .then((res) => !cancelled && setData(res))
      .catch((e) => !cancelled && setError(e.message));
    return () => {
      cancelled = true;
    };
  }, []);

  if (error) {
    return <EmptyState icon={AlertTriangle} title="Could not load dashboard" description={error} />;
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
          title={`Welcome back, ${user.name.split(" ")[0]}`}
          description="Here is your personal production overview. Only your assigned tasks are visible."
        />
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
          <StatCard label="My Active Tasks" value={d.stats.myActive} icon={ListTodo} tone="info" onClick={() => navigate("tasks")} />
          <StatCard label="Awaiting Inspection" value={d.stats.myAwaiting} icon={ClipboardCheck} tone="warning" onClick={() => navigate("tasks")} />
          <StatCard label="Completed This Month" value={d.stats.myCompletedThisMonth} icon={BadgeCheck} tone="success" />
          <StatCard label="Overdue" value={d.stats.myOverdue} icon={AlertTriangle} tone={d.stats.myOverdue > 0 ? "danger" : "default"} onClick={() => navigate("tasks")} />
        </div>

        <SectionCard
          title="My Production Tasks"
          action={
            <Button variant="ghost" size="sm" onClick={() => navigate("tasks")}>
              View all
            </Button>
          }
        >
          {d.myTasks.length === 0 ? (
            <EmptyState
              title="No tasks assigned yet"
              description="When Management allocates garment pieces to your account, they will appear here."
            />
          ) : (
            <div className="divide-y rounded-lg border overflow-x-auto">
              {d.myTasks.slice(0, 6).map((t) => (
                <button
                  key={t.id}
                  onClick={() => openTask(t.id)}
                  className="w-full grid grid-cols-[1fr_auto] sm:grid-cols-[2fr_2fr_1fr_130px_150px] gap-x-4 items-center px-4 py-3 text-left hover:bg-muted/50 transition-colors min-w-[640px]"
                >
                  <div className="min-w-0">
                    <p className="font-medium text-sm truncate">
                      {t.garmentPiece} <span className="text-muted-foreground">· {t.code}</span>
                    </p>
                    <p className="text-xs text-muted-foreground truncate">
                      {t.orderNumber} — {t.customerName}
                    </p>
                  </div>
                  <div className="hidden sm:block min-w-0">
                    <StatusBadge status={t.status} />
                  </div>
                  <div className="hidden sm:block">
                    <DueDate deadline={t.deadline} overdue={t.overdue} />
                  </div>
                  <ProgressCell value={t.progress} status={t.status} />
                  <span className="hidden sm:block text-xs text-muted-foreground text-right">
                    {timeAgo(t.updatedAt)}
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
  const supervisorName = user.role === "MANAGEMENT" ? "Management" : "Admin";
  return (
    <div>
      <PageHeader
        title={`${supervisorName} Dashboard`}
        description="Complete production overview across all registered orders and staff."
      >
        <Button size="sm" onClick={() => navigate("task-create")}>
          <Shirt className="size-4 mr-1.5" />
          Create Tasks
        </Button>
      </PageHeader>

      <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 mb-6">
        <StatCard label="Registered Orders" value={d.stats.totalOrders} icon={Package} onClick={() => navigate("orders")} />
        <StatCard label="Active Tasks" value={d.stats.activeTasks} icon={Shirt} tone="info" onClick={() => navigate("tasks")} />
        <StatCard label="Unassigned" value={d.stats.unassigned} icon={HelpCircle} tone={d.stats.unassigned > 0 ? "warning" : "default"} onClick={() => navigate("tasks")} />
        <StatCard label="Ongoing" value={d.stats.ongoing} icon={Loader} onClick={() => navigate("tasks")} />
        <StatCard label="Awaiting Inspection" value={d.stats.awaitingInspection} icon={ClipboardCheck} tone="warning" onClick={() => navigate("tasks")} />
        <StatCard label="Approved" value={d.stats.approved} icon={BadgeCheck} tone="success" onClick={() => navigate("tasks")} />
        <StatCard label="Ready for Pickup" value={d.stats.readyForPickup} icon={ShoppingBag} tone="success" onClick={() => navigate("tasks")} />
        <StatCard label="Delivered" value={d.stats.delivered} icon={Truck} onClick={() => navigate("tasks")} />
        <StatCard label="Blocked" value={d.stats.blocked} icon={Ban} tone={d.stats.blocked > 0 ? "danger" : "default"} onClick={() => navigate("tasks")} />
        <StatCard label="Overdue" value={d.stats.overdue} icon={AlertTriangle} tone={d.stats.overdue > 0 ? "danger" : "default"} onClick={() => navigate("tasks")} />
      </div>

      <div className="grid lg:grid-cols-2 gap-4 mb-4">
        <SectionCard
          title="Overdue Tasks"
          action={
            <Button variant="ghost" size="sm" onClick={() => navigate("tasks")}>
              Production board
            </Button>
          }
        >
          {d.overdueTasks.length === 0 ? (
            <EmptyState icon={BadgeCheck} title="Nothing overdue" description="All deadlines are under control." />
          ) : (
            <div className="divide-y rounded-lg border overflow-x-auto">
              {d.overdueTasks.map((t) => (
                <button
                  key={t.id}
                  onClick={() => openTask(t.id)}
                  className="w-full flex items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-muted/50 min-w-[420px]"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium truncate">
                      {t.code} — {t.garmentPiece}
                    </p>
                    <p className="text-xs text-muted-foreground truncate">
                      {t.orderNumber} · {t.customerName} · {t.assignedStaffName ?? "Unassigned"}
                    </p>
                  </div>
                  <div className="flex items-center gap-3 shrink-0">
                    <ProgressCell value={t.progress} status={t.status} className="w-28" />
                    <DueDate deadline={t.deadline} overdue />
                  </div>
                </button>
              ))}
            </div>
          )}
        </SectionCard>

        <SectionCard title="Due in the Next 7 Days">
          {d.dueSoon.length === 0 ? (
            <EmptyState title="No imminent deadlines" description="No tasks are due within the next seven days." />
          ) : (
            <div className="divide-y rounded-lg border overflow-x-auto">
              {d.dueSoon.map((t) => (
                <button
                  key={t.id}
                  onClick={() => openTask(t.id)}
                  className="w-full flex items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-muted/50 min-w-[420px]"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium truncate">
                      {t.code} — {t.garmentPiece}
                    </p>
                    <p className="text-xs text-muted-foreground truncate">
                      {t.orderNumber} · {t.assignedStaffName ?? "Unassigned"}
                    </p>
                  </div>
                  <div className="flex items-center gap-3 shrink-0">
                    <ProgressCell value={t.progress} status={t.status} className="w-28" />
                    <DueDate deadline={t.deadline} />
                  </div>
                </button>
              ))}
            </div>
          )}
        </SectionCard>
      </div>

      <div className="grid lg:grid-cols-2 gap-4">
        <SectionCard title="Staff Workload (Active Tasks)">
          {d.staffWorkload.length === 0 ? (
            <EmptyState icon={Users} title="No active staff accounts" />
          ) : (
            <div className="divide-y rounded-lg border">
              {d.staffWorkload.map((s) => (
                <div key={s.staffId} className="flex items-center justify-between px-4 py-2.5">
                  <div>
                    <p className="text-sm font-medium">{s.staffName}</p>
                    <p className="text-xs text-muted-foreground">
                      {s.awaitingInspection} awaiting inspection
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    {s.overdue > 0 && (
                      <span className="rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-semibold px-2 py-0.5">
                        {s.overdue} overdue
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

        <SectionCard title="Recent Activity">
          {d.recentActivity.length === 0 ? (
            <EmptyState icon={History} title="No activity recorded yet" />
          ) : (
            <div className="divide-y rounded-lg border max-h-80 overflow-y-auto">
              {d.recentActivity.map((a) => (
                <div key={a.id} className="flex items-center gap-3 px-4 py-2.5">
                  <div className="min-w-0 flex-1">
                    <p className="text-sm">
                      <span className="font-medium">{a.userName ?? "System"}</span>{" "}
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
