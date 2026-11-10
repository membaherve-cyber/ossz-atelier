"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import {
  Scissors,
  Clock,
  Hourglass,
  ClipboardCheck,
  Undo2,
  CheckCircle2,
  AlertTriangle,
  ChevronRight,
} from "lucide-react";
import { api, qs } from "./api-client";
import { useNav } from "./use-nav";
import { formatDate, timeAgo } from "@/lib/format";
import { TaskStatusBadge } from "./status-badge";
import { EmptyState } from "./empty-state";
import type { TaskListItem } from "./types";

type FilterId = "all" | "notstarted" | "inprogress" | "awaiting" | "changes" | "completed" | "overdue";

const FILTERS: Array<{ id: FilterId; label: string; icon: typeof Scissors }> = [
  { id: "all", label: "All assigned", icon: Scissors },
  { id: "notstarted", label: "Not started", icon: Hourglass },
  { id: "inprogress", label: "In progress", icon: Clock },
  { id: "awaiting", label: "Awaiting inspection", icon: ClipboardCheck },
  { id: "changes", label: "Changes requested", icon: Undo2 },
  { id: "completed", label: "Approved", icon: CheckCircle2 },
  { id: "overdue", label: "Overdue", icon: AlertTriangle },
];

export function MyTasksView() {
  const { navigate } = useNav();
  const [filter, setFilter] = useState<FilterId>("all");

  const tasksQuery = useQuery({
    queryKey: ["my-tasks", filter],
    queryFn: () =>
      api.get<{ tasks: TaskListItem[]; total: number }>(
        "/api/production/tasks" + qs({ mine: true, pageSize: 100, sort: "dueAt_asc" })
      ),
  });

  const allTasks = tasksQuery.data?.tasks ?? [];

  const tasks = allTasks.filter((t) => {
    switch (filter) {
      case "notstarted":
        return t.status === "ASSIGNED";
      case "inprogress":
        return t.status === "IN_PROGRESS";
      case "awaiting":
        return t.status === "AWAITING_INSPECTION";
      case "changes":
        return t.status === "CHANGES_REQUESTED";
      case "completed":
        return t.status === "APPROVED";
      case "overdue":
        return t.overdue;
      default:
        return t.status !== "CANCELLED";
    }
  });

  return (
    <div className="space-y-4">
      {/* Filter chips */}
      <div className="flex gap-1.5 flex-wrap">
        {FILTERS.map((f) => {
          const count =
            f.id === "all"
              ? allTasks.filter((t) => t.status !== "CANCELLED").length
              : f.id === "overdue"
                ? allTasks.filter((t) => t.overdue).length
                : f.id === "notstarted"
                  ? allTasks.filter((t) => t.status === "ASSIGNED").length
                  : f.id === "inprogress"
                    ? allTasks.filter((t) => t.status === "IN_PROGRESS").length
                    : f.id === "awaiting"
                      ? allTasks.filter((t) => t.status === "AWAITING_INSPECTION").length
                      : f.id === "changes"
                        ? allTasks.filter((t) => t.status === "CHANGES_REQUESTED").length
                        : allTasks.filter((t) => t.status === "APPROVED").length;
          return (
            <Button
              key={f.id}
              size="sm"
              variant={filter === f.id ? "default" : "outline"}
              className="h-8 text-xs"
              onClick={() => setFilter(f.id)}
            >
              <f.icon className="size-3.5" />
              {f.label}
              <span className={cn("ml-1 tabular-nums", filter === f.id ? "opacity-80" : "text-muted-foreground")}>
                ({count})
              </span>
            </Button>
          );
        })}
      </div>

      {/* Task cards */}
      {tasksQuery.isLoading ? (
        <div className="grid sm:grid-cols-2 gap-3">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-40" />
          ))}
        </div>
      ) : tasks.length === 0 ? (
        <Card>
          <CardContent>
            <EmptyState
              icon={<Scissors />}
              title="Nothing here"
              description={
                filter === "all"
                  ? "No garment pieces are assigned to your account yet. You will be notified when a task is assigned to you."
                  : "No tasks match this filter."
              }
            />
          </CardContent>
        </Card>
      ) : (
        <div className="grid sm:grid-cols-2 gap-3">
          {tasks.map((t) => (
            <Card
              key={t.id}
              className={cn(
                "cursor-pointer transition-all hover:shadow-md hover:border-primary/40",
                t.status === "CHANGES_REQUESTED" && "border-orange-600/40",
                t.status === "BLOCKED" && "border-red-600/40",
                t.overdue && "border-red-600/50"
              )}
              onClick={() => navigate({ view: "mytasks", task: t.id })}
            >
              <CardContent className="p-4 space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="font-semibold truncate">{t.pieceName}</p>
                    <p className="text-xs text-muted-foreground">
                      <span className="font-mono">{t.order.reference}</span> · {t.product.name}
                    </p>
                  </div>
                  <ChevronRight className="size-4 text-muted-foreground shrink-0 mt-1" />
                </div>

                <div className="flex items-center gap-2 flex-wrap">
                  <TaskStatusBadge status={t.status} />
                  {t.overdue && (
                    <Badge className="bg-red-600/10 text-red-700 border border-red-600/30">Overdue</Badge>
                  )}
                </div>

                <div>
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-xs text-muted-foreground">Progress</span>
                    <span className="text-xs font-bold tabular-nums">{t.progressPercentage}%</span>
                  </div>
                  <Progress value={t.progressPercentage} className="h-2" />
                </div>

                <div className="flex items-center justify-between text-xs text-muted-foreground">
                  <span>
                    {t.dueAt ? (
                      <>
                        Deadline <span className={cn("font-medium", t.overdue && "text-red-600")}>{formatDate(t.dueAt)}</span>
                      </>
                    ) : (
                      "No deadline set"
                    )}
                  </span>
                  <span>Updated {timeAgo(t.updatedAt)}</span>
                </div>

                {t.status === "CHANGES_REQUESTED" && (
                  <p className="text-xs text-orange-700 bg-orange-600/5 border border-orange-600/20 rounded p-2">
                    Corrections were requested — open the task to see the inspection feedback.
                  </p>
                )}
                {t.status === "AWAITING_INSPECTION" && (
                  <p className="text-xs text-teal-700 bg-teal-600/5 border border-teal-600/20 rounded p-2">
                    Submitted for inspection — waiting for the inspector's decision.
                  </p>
                )}
                {t.status === "BLOCKED" && (
                  <p className="text-xs text-red-700 bg-red-600/5 border border-red-600/20 rounded p-2">
                    Blocked: {t.blockedReason}
                  </p>
                )}
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
