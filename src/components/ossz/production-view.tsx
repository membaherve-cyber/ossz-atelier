"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Search,
  Factory,
  Hourglass,
  Users2,
  CheckCircle2,
  AlertTriangle,
  PackageOpen,
  ChevronLeft,
  ChevronRight,
  UserCheck,
  ClipboardCheck,
} from "lucide-react";
import { api, qs } from "./api-client";
import { can, useAppStore } from "./store";
import { useNav } from "./use-nav";
import { formatDate, timeAgo } from "@/lib/format";
import { TaskStatusBadge } from "./status-badge";
import { EmptyState } from "./empty-state";
import type {
  ProductionSummaryData,
  TaskListItem,
  TaskStatus,
  WorkloadEntry,
} from "./types";
import { TASK_STATUS_LABELS } from "./types";

export function ProductionView() {
  const user = useAppStore((s) => s.user)!;
  const { get, navigate } = useNav();
  const orderFilter = get("order");

  const [q, setQ] = useState("");
  const [status, setStatus] = useState<string>("all");
  const [staffId, setStaffId] = useState<string>("all");
  const [garment, setGarment] = useState("");
  const [overdueOnly, setOverdueOnly] = useState(false);
  const [progressMin, setProgressMin] = useState("");
  const [progressMax, setProgressMax] = useState("");
  const [dueFrom, setDueFrom] = useState("");
  const [dueTo, setDueTo] = useState("");
  const [page, setPage] = useState(1);

  const summaryQuery = useQuery({
    queryKey: ["production-summary"],
    queryFn: () => api.get<ProductionSummaryData>("/api/production/summary"),
  });

  const staffQuery = useQuery({
    queryKey: ["staff-assignable"],
    queryFn: () =>
      api.get<{ users: Array<{ id: string; name: string; role: string; activeTaskCount: number }> }>(
        "/api/users?assignable=1"
      ),
  });

  const tasksQuery = useQuery({
    queryKey: [
      "production-tasks",
      q,
      status,
      staffId,
      garment,
      overdueOnly,
      progressMin,
      progressMax,
      dueFrom,
      dueTo,
      page,
      orderFilter,
    ],
    queryFn: () =>
      api.get<{ tasks: TaskListItem[]; total: number; pageCount: number }>(
        "/api/production/tasks" +
          qs({
            q: q || undefined,
            status: status === "all" ? undefined : status,
            staffId: staffId === "all" ? undefined : staffId,
            garment: garment || undefined,
            overdue: overdueOnly || undefined,
            progressMin: progressMin || undefined,
            progressMax: progressMax || undefined,
            dueFrom: dueFrom ? new Date(dueFrom + "T00:00:00").toISOString() : undefined,
            dueTo: dueTo ? new Date(dueTo + "T23:59:59").toISOString() : undefined,
            orderId: orderFilter || undefined,
            page,
            pageSize: 15,
          })
      ),
  });

  const workloadQuery = useQuery({
    queryKey: ["workload"],
    queryFn: () => api.get<{ workload: WorkloadEntry[] }>("/api/production/workload"),
  });

  const summary = summaryQuery.data?.summary;
  const tasks = tasksQuery.data?.tasks ?? [];
  const total = tasksQuery.data?.total ?? 0;
  const pageCount = tasksQuery.data?.pageCount ?? 1;
  const staff = staffQuery.data?.users ?? [];

  const cards = summary
    ? [
        { label: "Total Pieces", value: summary.totalPieces, icon: Factory, tone: "" },
        { label: "Unassigned", value: summary.unassigned, icon: PackageOpen, tone: "text-amber-600" },
        { label: "In Progress", value: summary.inProgress, icon: Factory, tone: "" },
        { label: "Awaiting Inspection", value: summary.awaitingInspection, icon: ClipboardCheck, tone: "text-teal-600" },
        { label: "Approved", value: summary.approved, icon: CheckCircle2, tone: "text-green-600" },
        { label: "Blocked", value: summary.blocked, icon: AlertTriangle, tone: "text-red-600" },
        { label: "Overdue Tasks", value: summary.overdue, icon: AlertTriangle, tone: "text-red-600" },
        { label: "Orders Awaiting Production", value: summary.ordersAwaitingProduction, icon: Hourglass, tone: "text-amber-600" },
      ]
    : [];

  const filtersActive =
    q || status !== "all" || staffId !== "all" || garment || overdueOnly || progressMin || progressMax || dueFrom || dueTo;

  return (
    <div className="space-y-5">
      {/* Summary cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {summaryQuery.isLoading
          ? Array.from({ length: 8 }).map((_, i) => <Skeleton key={i} className="h-[76px]" />)
          : cards.map((c) => (
              <Card key={c.label}>
                <CardContent className="p-4">
                  <div className="flex items-center justify-between gap-2">
                    <p className="text-xs text-muted-foreground font-medium leading-tight">{c.label}</p>
                    <c.icon className={`size-4 shrink-0 ${c.tone}`} />
                  </div>
                  <p className="text-2xl font-bold mt-1.5 tabular-nums">{c.value}</p>
                </CardContent>
              </Card>
            ))}
      </div>

      {/* Quick status tabs */}
      <div className="flex gap-1.5 flex-wrap">
        {(["all", "UNASSIGNED", "IN_PROGRESS", "AWAITING_INSPECTION", "CHANGES_REQUESTED", "APPROVED", "BLOCKED", "CANCELLED"] as const).map(
          (s) => (
            <Button
              key={s}
              size="sm"
              variant={status === s ? "default" : "outline"}
              className="h-7 text-xs"
              onClick={() => {
                setStatus(s);
                setPage(1);
              }}
            >
              {s === "all" ? "All" : TASK_STATUS_LABELS[s]}
            </Button>
          )
        )}
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <div className="space-y-1.5">
            <Label className="text-xs">Order reference</Label>
            <div className="relative">
              <Search className="absolute left-2.5 top-2.5 size-3.5 text-muted-foreground" />
              <Input className="pl-7 h-8 text-xs" placeholder="OSSZ-ORD…" value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }} />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs">Staff member</Label>
            <Select value={staffId} onValueChange={(v) => { setStaffId(v); setPage(1); }}>
              <SelectTrigger className="h-8 text-xs"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All staff</SelectItem>
                {staff.map((st) => (
                  <SelectItem key={st.id} value={st.id}>
                    {st.name} ({st.activeTaskCount} active)
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs">Garment type</Label>
            <Input className="h-8 text-xs" placeholder="Jacket, Trousers…" value={garment} onChange={(e) => { setGarment(e.target.value); setPage(1); }} />
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs">Progress range (%)</Label>
            <div className="flex gap-1.5">
              <Input type="number" min={0} max={100} className="h-8 text-xs" placeholder="Min" value={progressMin} onChange={(e) => { setProgressMin(e.target.value); setPage(1); }} />
              <Input type="number" min={0} max={100} className="h-8 text-xs" placeholder="Max" value={progressMax} onChange={(e) => { setProgressMax(e.target.value); setPage(1); }} />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs">Deadline range</Label>
            <div className="flex gap-1.5">
              <Input type="date" className="h-8 text-xs px-2" value={dueFrom} onChange={(e) => { setDueFrom(e.target.value); setPage(1); }} />
              <Input type="date" className="h-8 text-xs px-2" value={dueTo} onChange={(e) => { setDueTo(e.target.value); setPage(1); }} />
            </div>
          </div>
          <div className="flex items-end justify-between gap-2">
            <div className="flex items-center gap-2 pb-1.5">
              <Switch id="overdue-only" checked={overdueOnly} onCheckedChange={(v) => { setOverdueOnly(v); setPage(1); }} />
              <Label htmlFor="overdue-only" className="text-xs">Overdue only</Label>
            </div>
            {filtersActive && (
              <Button
                variant="ghost"
                size="sm"
                className="h-7 text-xs"
                onClick={() => {
                  setQ(""); setStatus("all"); setStaffId("all"); setGarment("");
                  setOverdueOnly(false); setProgressMin(""); setProgressMax(""); setDueFrom(""); setDueTo("");
                  setPage(1);
                }}
              >
                Reset
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Task table */}
      <Card>
        <CardContent className="p-0">
          {tasksQuery.isLoading ? (
            <div className="p-6 space-y-3">
              {Array.from({ length: 6 }).map((_, i) => <Skeleton key={i} className="h-10" />)}
            </div>
          ) : tasks.length === 0 ? (
            <EmptyState
              icon={<Factory />}
              title="No production tasks match"
              description="Adjust the filters, or create a production breakdown from an order page."
            />
          ) : (
            <>
              {/* Desktop table */}
              <div className="hidden md:block">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Order</TableHead>
                      <TableHead>Garment Piece</TableHead>
                      <TableHead>Product</TableHead>
                      <TableHead>Assigned Staff</TableHead>
                      <TableHead>Progress</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Deadline</TableHead>
                      <TableHead>Updated</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {tasks.map((t) => (
                      <TableRow key={t.id} className="cursor-pointer" onClick={() => navigate({ task: t.id })}>
                        <TableCell className="font-mono text-xs font-semibold text-primary">{t.order.reference}</TableCell>
                        <TableCell className="font-medium">{t.pieceName}</TableCell>
                        <TableCell className="text-muted-foreground text-xs">{t.product.name}</TableCell>
                        <TableCell>
                          {t.assignedStaff ? (
                            <span className="flex items-center gap-1.5 text-sm">
                              <UserCheck className="size-3.5 text-muted-foreground" />
                              {t.assignedStaff.name}
                            </span>
                          ) : (
                            <Badge variant="outline" className="text-amber-700 border-amber-600/30">Unassigned</Badge>
                          )}
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2 min-w-28">
                            <Progress value={t.progressPercentage} className="h-2 w-16" />
                            <span className="text-xs font-semibold tabular-nums">{t.progressPercentage}%</span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="flex flex-col items-start gap-1">
                            <TaskStatusBadge status={t.status} />
                            {t.overdue && <Badge className="bg-red-600/10 text-red-700 border border-red-600/30 text-[10px]">Overdue</Badge>}
                          </div>
                        </TableCell>
                        <TableCell className={`text-xs ${t.overdue ? "text-red-600 font-semibold" : "text-muted-foreground"}`}>
                          {t.dueAt ? formatDate(t.dueAt) : "—"}
                        </TableCell>
                        <TableCell className="text-xs text-muted-foreground">{timeAgo(t.updatedAt)}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>

              {/* Mobile cards */}
              <div className="md:hidden divide-y">
                {tasks.map((t) => (
                  <button key={t.id} className="w-full text-left p-4 hover:bg-muted/40" onClick={() => navigate({ task: t.id })}>
                    <div className="flex items-center justify-between gap-2">
                      <p className="font-mono text-xs font-semibold text-primary">{t.order.reference}</p>
                      <TaskStatusBadge status={t.status} />
                    </div>
                    <p className="font-semibold mt-1">{t.pieceName}</p>
                    <p className="text-xs text-muted-foreground">
                      {t.product.name} · {t.assignedStaff?.name ?? "Unassigned"}
                      {t.dueAt ? ` · due ${formatDate(t.dueAt)}` : ""}
                    </p>
                    <div className="flex items-center gap-2 mt-2">
                      <Progress value={t.progressPercentage} className="h-2 flex-1" />
                      <span className="text-xs font-semibold tabular-nums">{t.progressPercentage}%</span>
                    </div>
                    {t.overdue && <Badge className="mt-2 bg-red-600/10 text-red-700 border border-red-600/30 text-[10px]">Overdue</Badge>}
                  </button>
                ))}
              </div>

              {total > 15 && (
                <div className="flex items-center justify-between px-4 py-3 border-t">
                  <p className="text-xs text-muted-foreground">
                    {total} task{total > 1 ? "s" : ""} · page {page} of {pageCount}
                  </p>
                  <div className="flex gap-1">
                    <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                      <ChevronLeft className="size-4" /> Prev
                    </Button>
                    <Button variant="outline" size="sm" disabled={page >= pageCount} onClick={() => setPage((p) => p + 1)}>
                      Next <ChevronRight className="size-4" />
                    </Button>
                  </div>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      {/* Staff workload */}
      {can(user, "production.view.all") && (
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-base font-semibold flex items-center gap-2">
              <Users2 className="size-4 text-primary" /> Staff Workload
            </CardTitle>
          </CardHeader>
          <CardContent>
            {workloadQuery.isLoading ? (
              <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {Array.from({ length: 3 }).map((_, i) => <Skeleton key={i} className="h-28" />)}
              </div>
            ) : (workloadQuery.data?.workload ?? []).length === 0 ? (
              <EmptyState title="No staff accounts available" />
            ) : (
              <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {(workloadQuery.data?.workload ?? []).map((w) => (
                  <button
                    key={w.staffId}
                    className="border rounded-lg p-3 text-left hover:bg-muted/40 transition-colors"
                    onClick={() => { setStaffId(w.staffId); setPage(1); }}
                  >
                    <div className="flex items-center justify-between">
                      <p className="font-semibold text-sm">{w.name}</p>
                      <Badge variant="secondary" className="text-[10px]">{w.active} active</Badge>
                    </div>
                    <div className="mt-2 flex items-center gap-2">
                      <Progress value={w.avgProgress} className="h-1.5 flex-1" />
                      <span className="text-[10px] font-semibold tabular-nums text-muted-foreground">avg {w.avgProgress}%</span>
                    </div>
                    <div className="flex flex-wrap gap-1 mt-2">
                      {w.notStarted > 0 && <Badge variant="outline" className="text-[10px]">{w.notStarted} not started</Badge>}
                      {w.inProgress > 0 && <Badge variant="outline" className="text-[10px]">{w.inProgress} in progress</Badge>}
                      {w.awaitingInspection > 0 && <Badge variant="outline" className="text-[10px] text-teal-700">{w.awaitingInspection} inspection</Badge>}
                      {w.changesRequested > 0 && <Badge variant="outline" className="text-[10px] text-orange-700">{w.changesRequested} changes</Badge>}
                      {w.approved > 0 && <Badge variant="outline" className="text-[10px] text-green-700">{w.approved} approved</Badge>}
                      {w.blocked > 0 && <Badge variant="outline" className="text-[10px] text-red-700">{w.blocked} blocked</Badge>}
                      {w.overdue > 0 && <Badge variant="outline" className="text-[10px] bg-red-600/10 text-red-700 border-red-600/30">{w.overdue} overdue</Badge>}
                      {w.total === 0 && <span className="text-xs text-muted-foreground">No tasks assigned</span>}
                    </div>
                  </button>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
