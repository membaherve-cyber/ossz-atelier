"use client";

import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import {
  ShoppingBag,
  Factory,
  Scissors,
  Users2,
  Clock,
  CheckCircle2,
  AlertTriangle,
  Hourglass,
  ArrowRight,
  PackageOpen,
} from "lucide-react";
import { api, qs } from "./api-client";
import { can, useAppStore } from "./store";
import { useNav } from "./use-nav";
import { timeAgo, formatDate } from "@/lib/format";
import { TaskStatusBadge, OrderStatusBadge } from "./status-badge";
import { EVENT_TYPE_LABELS, type OrderListItem, type ProductionSummaryData } from "./types";

export function DashboardView() {
  const user = useAppStore((s) => s.user)!;
  const { navigate } = useNav();

  const canViewAll = can(user, "production.view.all");

  const ordersQuery = useQuery({
    queryKey: ["orders", "dashboard"],
    queryFn: () => api.get<{ orders: OrderListItem[] }>("/api/orders?pageSize=6"),
    enabled: can(user, "orders.view"),
  });

  const summaryQuery = useQuery({
    queryKey: ["production-summary"],
    queryFn: () => api.get<ProductionSummaryData>("/api/production/summary"),
    enabled: canViewAll,
  });

  const myTasksQuery = useQuery({
    queryKey: ["my-tasks", "dashboard"],
    queryFn: () =>
      api.get<{ tasks: import("./types").TaskListItem[] }>(
        qs({ mine: true, pageSize: 5, sort: "dueAt_asc" })
      ),
  });

  const summary = summaryQuery.data?.summary;
  const orders = ordersQuery.data?.orders ?? [];
  const myTasks = myTasksQuery.data?.tasks ?? [];

  const statCards = canViewAll && summary
    ? [
        { label: "Orders in System", value: summary.totalOrders, icon: ShoppingBag, tone: "text-primary" },
        { label: "Awaiting Production", value: summary.ordersAwaitingProduction, icon: Hourglass, tone: "text-amber-600" },
        { label: "Pieces in Production", value: summary.inProgress, icon: Factory, tone: "text-primary" },
        { label: "Awaiting Inspection", value: summary.awaitingInspection, icon: Users2, tone: "text-teal-600" },
        { label: "Approved Pieces", value: summary.approved, icon: CheckCircle2, tone: "text-green-600" },
        { label: "Blocked / Overdue", value: `${summary.blocked} / ${summary.overdue}`, icon: AlertTriangle, tone: "text-red-600" },
      ]
    : [
        { label: "My Active Tasks", value: myTasks.filter((t) => !["APPROVED", "CANCELLED"].includes(t.status)).length, icon: Scissors, tone: "text-primary" },
        { label: "Awaiting Inspection", value: myTasks.filter((t) => t.status === "AWAITING_INSPECTION").length, icon: Users2, tone: "text-teal-600" },
        { label: "Changes Requested", value: myTasks.filter((t) => t.status === "CHANGES_REQUESTED").length, icon: AlertTriangle, tone: "text-orange-600" },
        { label: "Approved", value: myTasks.filter((t) => t.status === "APPROVED").length, icon: CheckCircle2, tone: "text-green-600" },
      ];

  return (
    <div className="space-y-6">
      {/* Greeting */}
      <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
          <p className="font-display text-2xl sm:text-3xl font-semibold">
            Welcome back, {user.name.split(" ")[0]}.
          </p>
          <p className="text-sm text-muted-foreground mt-1">
            {canViewAll
              ? "Here is the state of the atelier today."
              : "Here are your assigned garment pieces."}
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="default" onClick={() => navigate({ view: "mytasks" }, false)}>
            <Scissors className="size-4" /> My Tasks
          </Button>
          {can(user, "production.view.all") && (
            <Button variant="outline" onClick={() => navigate({ view: "production" }, false)}>
              <Factory className="size-4" /> Production
            </Button>
          )}
        </div>
      </div>

      {/* Stat cards */}
      <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
        {statCards.map((s) => (
          <Card key={s.label}>
            <CardContent className="p-4">
              <div className="flex items-center justify-between gap-2">
                <p className="text-xs text-muted-foreground font-medium leading-tight">{s.label}</p>
                <s.icon className={`size-4 shrink-0 ${s.tone}`} />
              </div>
              {summaryQuery.isLoading && canViewAll ? (
                <Skeleton className="h-8 w-12 mt-2" />
              ) : (
                <p className="text-2xl font-bold mt-1.5 tabular-nums">{s.value}</p>
              )}
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        {/* Recent orders */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-base font-semibold flex items-center gap-2">
              <ShoppingBag className="size-4 text-primary" /> Recent Orders
            </CardTitle>
            {can(user, "orders.view") && (
              <Button variant="ghost" size="sm" className="h-7 text-xs" onClick={() => navigate({ view: "orders" }, false)}>
                View all <ArrowRight className="size-3.5" />
              </Button>
            )}
          </CardHeader>
          <CardContent className="px-0 pb-0">
            {ordersQuery.isLoading ? (
              <div className="px-6 space-y-3 pb-4">
                {[1, 2, 3].map((i) => (
                  <Skeleton key={i} className="h-9" />
                ))}
              </div>
            ) : orders.length === 0 ? (
              <div className="px-6 pb-6 flex flex-col items-center gap-2 text-muted-foreground">
                <PackageOpen className="size-8" />
                <p className="text-sm">No orders yet.</p>
              </div>
            ) : (
              <div className="divide-y">
                {orders.map((o) => (
                  <button
                    key={o.id}
                    className="w-full px-6 py-3 hover:bg-muted/50 transition-colors text-left"
                    onClick={() => navigate({ view: "order", order: o.id })}
                  >
                    <div className="flex items-center justify-between gap-2">
                      <div className="min-w-0">
                        <p className="text-sm font-semibold font-mono">{o.reference}</p>
                        <p className="text-xs text-muted-foreground truncate">
                          {o.customer.name} · {o.itemCount} item(s) · {formatDate(o.createdAt)}
                        </p>
                      </div>
                      <div className="flex flex-col items-end gap-1 shrink-0">
                        <OrderStatusBadge status={o.status} />
                        {o.production.totalPieces > 0 && (
                          <div className="flex items-center gap-1.5">
                            <Progress value={o.production.overallProgress} className="h-1.5 w-16" />
                            <span className="text-[10px] font-semibold tabular-nums text-muted-foreground">
                              {o.production.overallProgress}%
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  </button>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* My tasks (for staff) or recent production activity (for managers) */}
        {canViewAll ? (
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-base font-semibold flex items-center gap-2">
                <Clock className="size-4 text-primary" /> Recent Production Activity
              </CardTitle>
            </CardHeader>
            <CardContent className="px-0 pb-0">
              {summaryQuery.isLoading ? (
                <div className="px-6 space-y-3 pb-4">
                  {[1, 2, 3, 4].map((i) => (
                    <Skeleton key={i} className="h-8" />
                  ))}
                </div>
              ) : (summaryQuery.data?.recentEvents ?? []).length === 0 ? (
                <div className="px-6 pb-6 flex flex-col items-center gap-2 text-muted-foreground">
                  <Factory className="size-8" />
                  <p className="text-sm">No production activity recorded yet.</p>
                </div>
              ) : (
                <div className="max-h-96 overflow-y-auto divide-y">
                  {(summaryQuery.data?.recentEvents ?? []).map((e) => (
                    <div key={e.id} className="px-6 py-2.5 flex items-center gap-3">
                      <div className="min-w-0 flex-1">
                        <p className="text-sm">
                          <span className="font-medium">{EVENT_TYPE_LABELS[e.eventType] ?? e.eventType}</span>
                          {e.task?.pieceName ? (
                            <>
                              {" — "}
                              <button
                                className="font-mono text-xs text-primary hover:underline"
                                onClick={() => navigate({ view: "production", task: e.taskId })}
                              >
                                {e.task.pieceName}
                              </button>
                            </>
                          ) : null}
                          {e.order?.reference ? (
                            <span className="text-muted-foreground"> · {e.order.reference}</span>
                          ) : null}
                        </p>
                        <p className="text-xs text-muted-foreground">
                          {e.actor.name} · {timeAgo(e.createdAt)}
                        </p>
                      </div>
                      {e.reason && (
                        <p className="text-xs text-muted-foreground/80 truncate max-w-40 hidden sm:block">
                          {e.reason}
                        </p>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        ) : (
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-base font-semibold flex items-center gap-2">
                <Scissors className="size-4 text-primary" /> My Upcoming Pieces
              </CardTitle>
            </CardHeader>
            <CardContent className="px-0 pb-0">
              {myTasksQuery.isLoading ? (
                <div className="px-6 space-y-3 pb-4">
                  {[1, 2, 3].map((i) => (
                    <Skeleton key={i} className="h-9" />
                  ))}
                </div>
              ) : myTasks.length === 0 ? (
                <div className="px-6 pb-6 flex flex-col items-center gap-2 text-muted-foreground">
                  <Scissors className="size-8" />
                  <p className="text-sm">No pieces assigned to you yet.</p>
                </div>
              ) : (
                <div className="divide-y">
                  {myTasks.map((t) => (
                    <button
                      key={t.id}
                      className="w-full px-6 py-3 hover:bg-muted/50 transition-colors text-left"
                      onClick={() => navigate({ view: "mytasks", task: t.id })}
                    >
                      <div className="flex items-center justify-between gap-2">
                        <div className="min-w-0">
                          <p className="text-sm font-semibold truncate">
                            {t.pieceName}
                            <span className="font-normal text-muted-foreground"> · {t.order.reference}</span>
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {t.product.name}
                            {t.dueAt ? ` · due ${formatDate(t.dueAt)}` : ""}
                            {t.overdue ? " · OVERDUE" : ""}
                          </p>
                        </div>
                        <div className="flex flex-col items-end gap-1 shrink-0">
                          <TaskStatusBadge status={t.status} />
                          <div className="flex items-center gap-1.5">
                            <Progress value={t.progressPercentage} className="h-1.5 w-14" />
                            <span className="text-[10px] font-semibold tabular-nums text-muted-foreground">
                              {t.progressPercentage}%
                            </span>
                          </div>
                        </div>
                      </div>
                    </button>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}
