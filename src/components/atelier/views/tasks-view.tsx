"use client";

// OSSZ ATELIER — production monitoring board (supervisors) / my tasks (staff).
// Staff requests are force-scoped to their own tasks by the API.

import { useCallback, useEffect, useState } from "react";
import { api, qs } from "../client";
import { isSupervisor, useAppStore } from "../store";
import type { TaskListResponse, TaskSummary, UserRecord } from "../types";
import { DueDate, EmptyState, PageHeader, ProgressCell, StatusBadge } from "../ui-bits";
import {
  PRIORITIES,
  PRIORITY_LABELS,
  STATUS_LABELS,
  TASK_STATUSES,
} from "@/lib/atelier/constants";
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

const SORT_OPTIONS: Array<{ value: SortKey; label: string }> = [
  { value: "updated", label: "Recently updated" },
  { value: "deadline", label: "Deadline" },
  { value: "progress", label: "Progress" },
  { value: "created", label: "Newest" },
];

export function TasksView() {
  const user = useAppStore((s) => s.user);
  const openTask = useAppStore((s) => s.openTask);
  const navigate = useAppStore((s) => s.navigate);
  const { toast } = useToast();
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
      const message = e instanceof Error ? e.message : "Failed to load tasks.";
      setError(message);
      toast({ title: "Could not load tasks", description: message, variant: "destructive" });
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
      ? "No tasks match your filters"
      : "No production tasks yet"
    : "No tasks assigned to you";
  const emptyDescription = supervisor
    ? hasFilters
      ? "Try adjusting the search or filters above."
      : "Create tasks from a customer order to start production."
    : "Garment pieces allocated to your account will appear here.";

  return (
    <div>
      <PageHeader
        title={supervisor ? "Production" : "My Tasks"}
        description={
          supervisor
            ? "Monitor every garment production task across the atelier."
            : "Your assigned garment pieces and their production status."
        }
      >
        {supervisor && (
          <Button size="sm" onClick={() => navigate("task-create")}>
            <Plus className="size-4" />
            Create Tasks
          </Button>
        )}
      </PageHeader>

      {/* ---- Filter bar ---- */}
      <Card className="shadow-sm mb-4">
        <CardContent className="p-4">
          <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <div className="space-y-1.5 lg:col-span-2">
              <Label htmlFor="tasks-search">Search</Label>
              <div className="relative">
                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                <Input
                  id="tasks-search"
                  className="pl-8"
                  placeholder="Order number, customer, piece or task code"
                  value={searchInput}
                  onChange={(e) => setSearchInput(e.target.value)}
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label>Status</Label>
              <Select
                value={statusFilter}
                onValueChange={(v) => {
                  setStatusFilter(v);
                  setPage(1);
                }}
              >
                <SelectTrigger className="w-full" aria-label="Filter by status">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All statuses</SelectItem>
                  {TASK_STATUSES.map((s) => (
                    <SelectItem key={s} value={s}>
                      {STATUS_LABELS[s]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label>Sort by</Label>
              <Select
                value={sortBy}
                onValueChange={(v) => {
                  setSortBy(v as SortKey);
                  setPage(1);
                }}
              >
                <SelectTrigger className="w-full" aria-label="Sort tasks">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {SORT_OPTIONS.map((o) => (
                    <SelectItem key={o.value} value={o.value}>
                      {o.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {supervisor && (
              <div className="space-y-1.5">
                <Label>Staff</Label>
                <Select
                  value={staffFilter}
                  onValueChange={(v) => {
                    setStaffFilter(v);
                    setPage(1);
                  }}
                >
                  <SelectTrigger className="w-full" aria-label="Filter by staff">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All staff</SelectItem>
                    <SelectItem value="unassigned">Unassigned</SelectItem>
                    {staff.map((u) => (
                      <SelectItem key={u.id} value={u.id}>
                        {u.name}
                        {u.activeTaskCount != null ? ` (${u.activeTaskCount} active)` : ""}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}

            {supervisor && (
              <div className="space-y-1.5">
                <Label>Priority</Label>
                <Select
                  value={priorityFilter}
                  onValueChange={(v) => {
                    setPriorityFilter(v);
                    setPage(1);
                  }}
                >
                  <SelectTrigger className="w-full" aria-label="Filter by priority">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All priorities</SelectItem>
                    {PRIORITIES.map((p) => (
                      <SelectItem key={p} value={p}>
                        {PRIORITY_LABELS[p]}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}

            {supervisor && (
              <div className="space-y-1.5">
                <Label htmlFor="tasks-from">Deadline from</Label>
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
                <Label htmlFor="tasks-to">Deadline to</Label>
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
              <Label htmlFor="tasks-overdue">Overdue only</Label>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* ---- Inline error when stale results are shown ---- */}
      {error && tasks.length > 0 && (
        <Alert variant="destructive" className="mb-4">
          <AlertTriangle />
          <AlertTitle>Could not refresh tasks</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* ---- Table ---- */}
      {error && !loading && tasks.length === 0 ? (
        <Card className="shadow-sm">
          <CardContent className="p-0">
            <EmptyState
              icon={AlertTriangle}
              title="Could not load tasks"
              description={error}
              action={
                <Button variant="outline" size="sm" onClick={() => fetchTasks()}>
                  Retry
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
                  <TableHead className="pl-4 w-[220px]">Task</TableHead>
                  <TableHead className="w-[200px]">Order</TableHead>
                  <TableHead className="w-[150px]">Assignee</TableHead>
                  <TableHead className="w-[160px]">Status</TableHead>
                  <TableHead className="w-[170px]">Progress</TableHead>
                  <TableHead className="w-[140px]">Deadline</TableHead>
                  <TableHead className="pr-4 text-right">Last Update</TableHead>
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
                              Clear filters
                            </Button>
                          ) : undefined
                        }
                      />
                    </TableCell>
                  </TableRow>
                ) : (
                  tasks.map((t) => (
                    <TableRow
                      key={t.id}
                      tabIndex={0}
                      className="cursor-pointer focus-visible:bg-muted/60 outline-none"
                      onClick={() => openTask(t.id)}
                      onKeyDown={(e) => {
                        if (e.key === "Enter" || e.key === " ") {
                          e.preventDefault();
                          openTask(t.id);
                        }
                      }}
                      aria-label={`Open task ${t.code} — ${t.garmentPiece}`}
                    >
                      <TableCell className="pl-4">
                        <p className="font-medium text-sm leading-tight">{t.garmentPiece}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">{t.code}</p>
                      </TableCell>
                      <TableCell>
                        <p className="text-sm leading-tight">{t.orderNumber}</p>
                        <p className="text-xs text-muted-foreground mt-0.5 truncate max-w-44">
                          {t.customerName}
                        </p>
                      </TableCell>
                      <TableCell>
                        {t.assignedStaffName ? (
                          <span className="text-sm">{t.assignedStaffName}</span>
                        ) : (
                          <span className="text-sm text-muted-foreground italic">Unassigned</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <StatusBadge status={t.status} />
                      </TableCell>
                      <TableCell>
                        <ProgressCell value={t.progress} status={t.status} />
                      </TableCell>
                      <TableCell>
                        <DueDate deadline={t.deadline} overdue={t.overdue} />
                      </TableCell>
                      <TableCell
                        className="pr-4 text-right text-xs text-muted-foreground"
                        title={formatDateTime(t.updatedAt)}
                      >
                        {timeAgo(t.updatedAt)}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>

            {/* ---- Pagination ---- */}
            <div className="flex flex-col sm:flex-row items-center justify-between gap-2 border-t px-4 py-3">
              <p className="text-xs text-muted-foreground">
                {total} task{total === 1 ? "" : "s"} total
              </p>
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page <= 1 || loading}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                >
                  <ChevronLeft className="size-4" />
                  Previous
                </Button>
                <span className="text-xs text-muted-foreground tabular-nums whitespace-nowrap px-1">
                  Page {page} of {totalPages}
                </span>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page >= totalPages || loading}
                  onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                >
                  Next
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
