"use client";

import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Timer, Hourglass, Repeat, AlertTriangle, BarChart3 } from "lucide-react";
import { api } from "./api-client";
import { useNav } from "./use-nav";
import { formatDate } from "@/lib/format";
import { EmptyState } from "./empty-state";
import type { ReportsData } from "./types";

function formatDuration(ms: number | null | undefined): string {
  if (ms == null) return "—";
  const hours = ms / 3_600_000;
  if (hours < 1) return `${Math.round(ms / 60_000)} min`;
  if (hours < 48) return `${Math.round(hours)} h`;
  return `${(hours / 24).toFixed(1)} days`;
}

export function ReportsView() {
  const { navigate } = useNav();

  const reportsQuery = useQuery({
    queryKey: ["reports"],
    queryFn: () => api.get<ReportsData>("/api/production/reports"),
  });

  if (reportsQuery.isLoading) {
    return (
      <div className="space-y-4">
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
        <Skeleton className="h-64" />
      </div>
    );
  }

  const r = reportsQuery.data?.reports;
  if (!r) return <EmptyState title="Reports unavailable" />;

  const metricCards = [
    {
      label: "Avg assignment → approval",
      value: formatDuration(r.avgAssignmentToApprovalMs),
      hint: "Production working time per piece",
      icon: Timer,
    },
    {
      label: "Avg submission → inspection",
      value: formatDuration(r.avgSubmissionToInspectionMs),
      hint: "Waiting time for inspection",
      icon: Hourglass,
    },
    {
      label: "Rework frequency",
      value: r.totals.reworkRate == null ? "—" : `${r.totals.reworkRate}%`,
      hint: `${r.totals.reworkTasks} of ${r.totals.tasks} pieces needed corrections`,
      icon: Repeat,
    },
    {
      label: "Overdue tasks",
      value: String(r.totals.overdue),
      hint: "Currently past deadline",
      icon: AlertTriangle,
    },
  ];

  return (
    <div className="space-y-5">
      {/* Metric cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        {metricCards.map((c) => (
          <Card key={c.label}>
            <CardContent className="p-4">
              <div className="flex items-center justify-between gap-2">
                <p className="text-xs text-muted-foreground font-medium leading-tight">{c.label}</p>
                <c.icon className="size-4 text-primary shrink-0" />
              </div>
              <p className="text-2xl font-bold mt-1.5">{c.value}</p>
              <p className="text-[11px] text-muted-foreground mt-0.5 leading-tight">{c.hint}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      {r.totals.tasks === 0 ? (
        <Card>
          <CardContent>
            <EmptyState
              icon={<BarChart3 />}
              title="No production data yet"
              description="Metrics will appear once production tasks are created and worked on. We never invent statistics — only recorded events are used."
            />
          </CardContent>
        </Card>
      ) : (
        <>
          {/* Per-order progress */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base font-semibold">Production Progress by Order</CardTitle>
            </CardHeader>
            <CardContent className="px-0 pb-0">
              {r.perOrder.length === 0 ? (
                <EmptyState title="No orders with production tasks" />
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Order</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Pieces</TableHead>
                        <TableHead>Progress</TableHead>
                        <TableHead>Approved</TableHead>
                        <TableHead>Overdue</TableHead>
                        <TableHead>Complete</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {r.perOrder.map((o) => (
                        <TableRow key={o.orderId}>
                          <TableCell className="font-mono text-xs font-semibold text-primary">{o.reference}</TableCell>
                          <TableCell>{o.customer}</TableCell>
                          <TableCell className="tabular-nums">{o.totalPieces}</TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2 min-w-28">
                              <Progress value={o.overallProgress} className="h-2 w-16" />
                              <span className="text-xs font-semibold tabular-nums">{o.overallProgress}%</span>
                            </div>
                          </TableCell>
                          <TableCell className="text-xs">
                            {o.approved}/{o.totalPieces - o.cancelled}
                          </TableCell>
                          <TableCell className={o.overdue > 0 ? "text-red-600 font-semibold" : ""}>
                            {o.overdue}
                          </TableCell>
                          <TableCell>
                            {o.productionComplete ? (
                              <Badge className="bg-green-600/90 text-white">Yes</Badge>
                            ) : (
                              <span className="text-muted-foreground text-xs">No</span>
                            )}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>

          <div className="grid lg:grid-cols-2 gap-4">
            {/* Per-staff performance */}
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-base font-semibold">Staff Performance</CardTitle>
              </CardHeader>
              <CardContent className="px-0 pb-0">
                {r.perStaff.length === 0 ? (
                  <EmptyState title="No staff assignments recorded" />
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Staff</TableHead>
                        <TableHead>Assigned</TableHead>
                        <TableHead>Approved</TableHead>
                        <TableHead>Reworked</TableHead>
                        <TableHead>Avg cycle</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {r.perStaff.map((s) => (
                        <TableRow key={s.staffId}>
                          <TableCell className="font-medium">{s.name}</TableCell>
                          <TableCell className="tabular-nums">{s.assigned}</TableCell>
                          <TableCell className="tabular-nums text-green-700 font-semibold">{s.approved}</TableCell>
                          <TableCell className="tabular-nums">{s.reworked}</TableCell>
                          <TableCell className="text-muted-foreground text-sm">{formatDuration(s.avgCycleMs)}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>

            {/* Overdue list */}
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-base font-semibold flex items-center gap-2">
                  <AlertTriangle className="size-4 text-red-600" /> Overdue Tasks
                </CardTitle>
              </CardHeader>
              <CardContent>
                {r.overdue.length === 0 ? (
                  <EmptyState title="Nothing overdue" description="All tasks are within their deadlines." />
                ) : (
                  <div className="space-y-2">
                    {r.overdue.map((t) => (
                      <button
                        key={t.taskId}
                        className="w-full text-left border rounded-lg p-2.5 hover:bg-muted/40 text-sm"
                        onClick={() => navigate({ view: "production", task: t.taskId })}
                      >
                        <div className="flex items-center justify-between gap-2">
                          <p className="font-semibold">
                            {t.pieceName}
                            <span className="font-normal text-muted-foreground"> · {t.reference}</span>
                          </p>
                          <Badge variant="outline" className="text-red-700 border-red-600/30 text-[10px]">
                            {formatDate(t.dueAt)}
                          </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground">
                          {t.staff ?? "Unassigned"} · {t.status.replaceAll("_", " ").toLowerCase()}
                        </p>
                      </button>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </div>
  );
}
