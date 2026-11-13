"use client";

// OSSZ ATELIER — production monitoring board (supervisors) / my tasks (staff).
// Staff requests are force-scoped to their own tasks by the API.

import { useCallback, useEffect, useState } from "react";
import { api, qs } from "../client";
import { useI18n } from "../i18n/use-i18n";
import { priorityLabel, statusLabel } from "../i18n/core";
import { isSupervisor, useAppStore } from "../store";
import type { TaskListResponse, TaskSummary, UserRecord } from "../types";
import { DueDate, EmptyState, PageHeader, ProgressCell, StatusBadge } from "../ui-bits";
import { PRIORITIES, TASK_STATUSES } from "@/lib/atelier/constants";
import { formatDateTime, timeAgo } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
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
  AlertTriangle,
  ChevronLeft,
  ChevronRight,
  Plus,
  Search,
  Shirt,
} from "lucide-react";

const PAGE_SIZE = 20;

type SortKey = "updated" | "deadline" | "progress" | "created";

const SORT_OPTIONS: Array<{ value: SortKey; labelKey: string }> = [
  { value: "updated", labelKey: "tasks.sortUpdated" },
  { value: "deadline", labelKey: "tasks.sortDeadline" },
  { value: "progress", labelKey: "tasks.sortProgress" },
  { value: "created", labelKey: "tasks.sortCreated" },
];

export function TasksView() {
  const user = useAppStore((s) => s.user);
  const openTask = useAppStore((s) => s.openTask);
  const navigate = useAppStore((s) => s.navigate);
  const { toast } = useToast();
  const { t, lang } = useI18n();
  const supervisor = isSupervisor(user);

  // ---- Filters -------------------------------------------------------------
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [staffFilter, setStaffFilter] = useState("all");
  const [statusFilter, setStatusFilter] = useState("all");
  const [priorityFilter, setPriorityFilter] = useState("all");
  const [overdueOnly, setOverdueOnly] = useState(false);
  const [sortBy, setSortBy] = useState<SortKey>("updated");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [page, setPage] = useState(1);

  // ---- Data ----------------------------------------------------------------
  const [tasks, setTasks] = useState<TaskSummary[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [staff, setStaff] = useState<UserRecord[]>([]);

  // Debounce the search input (~300ms).
  useEffect(() => {
    const timer = setTimeout(() => {
      setSearch(searchInput.trim());
      setPage(1);
    }, 300);
    return () => clearTimeout(timer);
  }, [searchInput]);

  // Staff filter options (supervisors only — staff sessions get 403).
  useEffect(() => {
    if (!supervisor) return;
    let cancelled = false;
    api
      .get<{ users: UserRecord[] }>("/api/atelier/users?role=STAFF")
      .then((res) => {
        if (!cancelled) setStaff(res.users.filter((u) => u.isActive));
      })
      .catch(() => {
        // Options stay empty; filtering still works without them.
      });
    return () => {
      cancelled = true;
    };
  }, [supervisor]);

  const fetchTasks = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get<TaskListResponse>(
        "/api/atelier/tasks" +
          qs({
            page,
            pageSize: PAGE_SIZE,
            search,
            staffId: staffFilter === "all" ? undefined : staffFilter,
            status: statusFilter === "all" ? undefined : statusFilter,
            priority: priorityFilter === "all" ? undefined : priorityFilter,
            overdue: overdueOnly ? true : undefined,
            sort: sortBy,
            from: from || undefined,
            to: to || undefined,
          })
      );
      setTasks(res.tasks);
      setTotal(res.total);
      setError(null);
    } catch (e) {
      const message = e instanceof Error ? e.message : t("tasks.loadFailed");
      setError(message);
      toast({ title: t("tasks.loadFailedTitle"), description: message, variant: "destructive" });
    } finally {
      setLoading(false);
    }
  }, [
    page,
    search,
    staffFilter,
    statusFilter,
    priorityFilter,
    overdueOnly,
    sortBy,
    from,
    to,
    toast,
    t,
  ]);

  useEffect(() => {
    fetchTasks();
  }, [fetchTasks]);

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  const hasFilters =
    !!search ||
    staffFilter !== "all" ||
    statusFilter !== "all" ||
    priorityFilter !== "all" ||
    overdueOnly ||
    !!from ||
    !!to;

  const emptyTitle = supervisor
    ? hasFilters
      ? t("tasks.emptyFilteredTitle")
      : t("tasks.emptyTitle")
    : t("tasks.emptyStaffTitle");
  const emptyDescription = supervisor
    ? hasFilters
      ? t("tasks.emptyFilteredDesc")
      : t("tasks.emptyDesc")
    : t("tasks.emptyStaffDesc");

  return (
    <div>
      <PageHeader
        title={supervisor ? t("tasks.title") : t("tasks.myTitle")}
        description={
          supervisor ? t("tasks.description") : t("tasks.myDescription")
        }
      >
        {supervisor && (
          <Button size="sm" onClick={() => navigate("task-create")}>
            <Plus className="size-4" />
            {t("tasks.createTasks")}
          </Button>
        )}
      </PageHeader>

      {/* ---- Filter bar ---- */}
      <Card className="shadow-sm mb-4">
        <CardContent className="p-4">
          <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <div className="space-y-1.5 lg:col-span-2">
              <Label htmlFor="tasks-search">{t("common.search")}</Label>
              <div className="relative">
                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                <Input
                  id="tasks-search"
                  className="pl-8"
                  placeholder={t("tasks.searchPlaceholder")}
                  value={searchInput}
                  onChange={(e) => setSearchInput(e.target.value)}
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label>{t("tasks.status")}</Label>
              <Select
                value={statusFilter}
                onValueChange={(v) => {
                  setStatusFilter(v);
                  setPage(1);
                }}
              >
                <SelectTrigger className="w-full" aria-label={t("tasks.filterByStatus")}>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">{t("tasks.allStatuses")}</SelectItem>
                  {TASK_STATUSES.map((s) => (
                    <SelectItem key={s} value={s}>
                      {statusLabel(s, lang)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label>{t("tasks.sortBy")}</Label>
              <Select
                value={sortBy}
                onValueChange={(v) => {
                  setSortBy(v as SortKey);
                  setPage(1);
                }}
              >
                <SelectTrigger className="w-full" aria-label={t("tasks.sortAria")}>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {SORT_OPTIONS.map((o) => (
                    <SelectItem key={o.value} value={o.value}>
                      {t(o.labelKey)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {supervisor && (
              <div className="space-y-1.5">
                <Label>{t("tasks.staff")}</Label>
                <Select
                  value={staffFilter}
                  onValueChange={(v) => {
                    setStaffFilter(v);
                    setPage(1);
                  }}
                >
                  <SelectTrigger className="w-full" aria-label={t("tasks.filterByStaff")}>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">{t("tasks.allStaff")}</SelectItem>
                    <SelectItem value="unassigned">{t("tasks.unassignedFilter")}</SelectItem>
                    {staff.map((u) => (
                      <SelectItem key={u.id} value={u.id}>
                        {u.name}
                        {u.activeTaskCount != null
                          ? ` (${t("tasks.activeCount", { count: u.activeTaskCount })})`
                          : ""}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}

            {supervisor && (
              <div className="space-y-1.5">
                <Label>{t("tasks.priority")}</Label>
                <Select
                  value={priorityFilter}
                  onValueChange={(v) => {
                    setPriorityFilter(v);
                    setPage(1);
                  }}
                >
                  <SelectTrigger className="w-full" aria-label={t("tasks.filterByPriority")}>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">{t("tasks.allPriorities")}</SelectItem>
                    {PRIORITIES.map((p) => (
                      <SelectItem key={p} value={p}>
                        {priorityLabel(p, lang)}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}

            {supervisor && (
              <div className="space-y-1.5">
                <Label htmlFor="tasks-from">{t("tasks.deadlineFrom")}</Label>
                <Input
                  id="tasks-from"
                  type="date"
                  value={from}
                  onChange={(e) => {
                    setFrom(e.target.value);
                    setPage(1);
                  }}
                />
              </div>
            )}

            {supervisor && (
              <div className="space-y-1.5">
                <Label htmlFor="tasks-to">{t("tasks.deadlineTo")}</Label>
                <Input
                  id="tasks-to"
                  type="date"
                  value={to}
                  onChange={(e) => {
                    setTo(e.target.value);
                    setPage(1);
                  }}
                />
              </div>
            )}

            <div className="flex items-center gap-2.5 pt-1 lg:pt-6">
              <Switch
                id="tasks-overdue"
                checked={overdueOnly}
                onCheckedChange={(v) => {
                  setOverdueOnly(v);
                  setPage(1);
                }}
              />
              <Label htmlFor="tasks-overdue">{t("tasks.overdueOnly")}</Label>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* ---- Inline error when stale results are shown ---- */}
      {error && tasks.length > 0 && (
        <Alert variant="destructive" className="mb-4">
          <AlertTriangle />
          <AlertTitle>{t("tasks.refreshFailed")}</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* ---- Table ---- */}
      {error && !loading && tasks.length === 0 ? (
        <Card className="shadow-sm">
          <CardContent className="p-0">
            <EmptyState
              icon={AlertTriangle}
              title={t("tasks.loadFailedTitle")}
              description={error}
              action={
                <Button variant="outline" size="sm" onClick={() => fetchTasks()}>
                  {t("common.tryAgain")}
                </Button>
              }
            />
          </CardContent>
        </Card>
      ) : (
        <Card className="shadow-sm">
          <CardContent className="p-0">
            <Table className="min-w-[900px]">
              <TableHeader>
                <TableRow>
                  <TableHead className="pl-4 w-[220px]">{t("tasks.colTask")}</TableHead>
                  <TableHead className="w-[200px]">{t("tasks.colOrder")}</TableHead>
                  <TableHead className="w-[150px]">{t("tasks.colAssignee")}</TableHead>
                  <TableHead className="w-[160px]">{t("tasks.colStatus")}</TableHead>
                  <TableHead className="w-[170px]">{t("tasks.colProgress")}</TableHead>
                  <TableHead className="w-[140px]">{t("tasks.colDeadline")}</TableHead>
                  <TableHead className="pr-4 text-right">{t("tasks.colLastUpdate")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  Array.from({ length: 8 }).map((_, i) => (
                    <TableRow key={`skeleton-${i}`}>
                      {Array.from({ length: 7 }).map((_, j) => (
                        <TableCell key={j} className={j === 0 ? "pl-4" : j === 6 ? "pr-4" : ""}>
                          <Skeleton className="h-5 w-full max-w-32 mx-auto" />
                        </TableCell>
                      ))}
                    </TableRow>
                  ))
                ) : tasks.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7}>
                      <EmptyState
                        icon={Shirt}
                        title={emptyTitle}
                        description={emptyDescription}
                        action={
                          supervisor && hasFilters ? (
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => {
                                setSearchInput("");
                                setStaffFilter("all");
                                setStatusFilter("all");
                                setPriorityFilter("all");
                                setOverdueOnly(false);
                                setFrom("");
                                setTo("");
                                setPage(1);
                              }}
                            >
                              {t("tasks.clearFilters")}
                            </Button>
                          ) : undefined
                        }
                      />
                    </TableCell>
                  </TableRow>
                ) : (
                  tasks.map((task) => (
                    <TableRow
                      key={task.id}
                      tabIndex={0}
                      className="cursor-pointer focus-visible:bg-muted/60 outline-none"
                      onClick={() => openTask(task.id)}
                      onKeyDown={(e) => {
                        if (e.key === "Enter" || e.key === " ") {
                          e.preventDefault();
                          openTask(task.id);
                        }
                      }}
                      aria-label={t("tasks.openTaskAria", {
                        code: task.code,
                        piece: task.garmentPiece,
                      })}
                    >
                      <TableCell className="pl-4">
                        <p className="font-medium text-sm leading-tight">{task.garmentPiece}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">{task.code}</p>
                      </TableCell>
                      <TableCell>
                        <p className="text-sm leading-tight">{task.orderNumber}</p>
                        <p className="text-xs text-muted-foreground mt-0.5 truncate max-w-44">
                          {task.customerName}
                        </p>
                      </TableCell>
                      <TableCell>
                        {task.assignedStaffName ? (
                          <span className="text-sm">{task.assignedStaffName}</span>
                        ) : (
                          <span className="text-sm text-muted-foreground italic">
                            {t("tasks.unassigned")}
                          </span>
                        )}
                      </TableCell>
                      <TableCell>
                        <StatusBadge status={task.status} />
                      </TableCell>
                      <TableCell>
                        <ProgressCell value={task.progress} status={task.status} />
                      </TableCell>
                      <TableCell>
                        <DueDate deadline={task.deadline} overdue={task.overdue} />
                      </TableCell>
                      <TableCell
                        className="pr-4 text-right text-xs text-muted-foreground"
                        title={formatDateTime(task.updatedAt)}
                      >
                        {timeAgo(task.updatedAt)}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>

            {/* ---- Pagination ---- */}
            <div className="flex flex-col sm:flex-row items-center justify-between gap-2 border-t px-4 py-3">
              <p className="text-xs text-muted-foreground">
                {total === 1
                  ? t("tasks.totalOne", { count: total })
                  : t("tasks.totalMany", { count: total })}
              </p>
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page <= 1 || loading}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                >
                  <ChevronLeft className="size-4" />
                  {t("tasks.previous")}
                </Button>
                <span className="text-xs text-muted-foreground tabular-nums whitespace-nowrap px-1">
                  {t("tasks.pageOf", { page, totalPages })}
                </span>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page >= totalPages || loading}
                  onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                >
                  {t("tasks.next")}
                  <ChevronRight className="size-4" />
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
