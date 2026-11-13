"use client";

// OSSZ ATELIER — break a customer order into garment-piece production tasks.
// Supervisor-only screen (Management / Admin).

import { useCallback, useEffect, useMemo, useState } from "react";
import { format } from "date-fns";
import { api } from "../client";
import { useI18n } from "../i18n/use-i18n";
import { categoryLabel, priorityLabel } from "../i18n/core";
import { isSupervisor, useAppStore } from "../store";
import type { OrderSummary, UserRecord } from "../types";
import { EmptyState, PageHeader, PriorityBadge } from "../ui-bits";
import { PRIORITIES, TASK_CATEGORIES } from "@/lib/atelier/constants";
import { formatDate } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
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
import { Textarea } from "@/components/ui/textarea";
import {
  AlertTriangle,
  ArrowLeft,
  Ban,
  Check,
  Loader2,
  Plus,
  RefreshCw,
  Trash2,
} from "lucide-react";

const UNASSIGNED = "none";

type PieceRow = {
  garmentPiece: string;
  category: string;
  assignedStaffId: string; // UNASSIGNED sentinel or staff id
  priority: string;
  deadline: string; // datetime-local input value
  instructions: string;
};

/** Convert an ISO timestamp to a `datetime-local` input value (local time). */
function toDatetimeLocal(iso: string | null): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  return format(d, "yyyy-MM-dd'T'HH:mm");
}

function makeRow(order: OrderSummary | null): PieceRow {
  return {
    garmentPiece: "",
    category: "FULL",
    assignedStaffId: UNASSIGNED,
    priority: order?.priority ?? "NORMAL",
    deadline: toDatetimeLocal(order?.expectedDeliveryDate ?? null),
    instructions: "",
  };
}

export function TaskCreateView() {
  const user = useAppStore((s) => s.user);
  const viewParams = useAppStore((s) => s.viewParams);
  const navigate = useAppStore((s) => s.navigate);
  const { toast } = useToast();
  const { t, lang } = useI18n();
  const supervisor = isSupervisor(user);

  const [orders, setOrders] = useState<OrderSummary[] | null>(null);
  const [ordersError, setOrdersError] = useState<string | null>(null);
  const [staff, setStaff] = useState<UserRecord[]>([]);
  const [selectedOrderId, setSelectedOrderId] = useState("");
  const [rows, setRows] = useState<PieceRow[]>([makeRow(null)]);
  const [submitting, setSubmitting] = useState(false);

  const loadOrders = useCallback(async () => {
    setOrdersError(null);
    try {
      const res = await api.get<{ orders: OrderSummary[] }>("/api/atelier/orders");
      setOrders(res.orders);
    } catch (e) {
      setOrdersError(e instanceof Error ? e.message : t("tc.loadOrdersFailed"));
    }
  }, [t]);

  useEffect(() => {
    if (!supervisor) return;
    loadOrders();
    api
      .get<{ users: UserRecord[] }>("/api/atelier/users?role=STAFF")
      .then((res) => setStaff(res.users.filter((u) => u.isActive)))
      .catch(() => {
        // Staff options stay empty; pieces can still be left unassigned.
      });
  }, [supervisor, loadOrders]);

  const selectedOrder = useMemo(
    () => orders?.find((o) => o.id === selectedOrderId) ?? null,
    [orders, selectedOrderId]
  );

  const selectOrder = useCallback(
    (id: string) => {
      setSelectedOrderId(id);
      const order = orders?.find((o) => o.id === id) ?? null;
      setRows([makeRow(order)]);
    },
    [orders]
  );

  // Preselect the order passed through navigation params.
  useEffect(() => {
    if (!orders || !viewParams.orderId || selectedOrderId) return;
    if (orders.some((o) => o.id === viewParams.orderId)) {
      const order = orders.find((o) => o.id === viewParams.orderId) ?? null;
      setSelectedOrderId(viewParams.orderId);
      setRows([makeRow(order)]);
    }
  }, [orders, viewParams.orderId, selectedOrderId]);

  function updateRow(index: number, patch: Partial<PieceRow>) {
    setRows((rs) => rs.map((r, i) => (i === index ? { ...r, ...patch } : r)));
  }

  async function submit() {
    if (!selectedOrder) {
      toast({
        title: t("tc.selectOrderTitle"),
        description: t("tc.selectOrderDesc"),
        variant: "destructive",
      });
      return;
    }
    const names = rows.map((r) => r.garmentPiece.trim());
    if (names.some((n) => !n)) {
      toast({
        title: t("tc.missingNameTitle"),
        description: t("tc.missingNameDesc"),
        variant: "destructive",
      });
      return;
    }
    const seen = new Set<string>();
    for (const name of names) {
      const key = name.toLowerCase();
      if (seen.has(key)) {
        toast({
          title: t("tc.duplicateTitle"),
          description: t("tc.duplicateDesc", { name }),
          variant: "destructive",
        });
        return;
      }
      seen.add(key);
    }

    setSubmitting(true);
    try {
      await api.post("/api/atelier/tasks", {
        orderId: selectedOrder.id,
        tasks: rows.map((r) => ({
          garmentPiece: r.garmentPiece.trim(),
          category: r.category,
          instructions: r.instructions.trim() || null,
          deadline: r.deadline ? new Date(r.deadline).toISOString() : null,
          priority: r.priority,
          assignedStaffId: r.assignedStaffId === UNASSIGNED ? null : r.assignedStaffId,
        })),
      });
      toast({
        title:
          rows.length === 1
            ? t("tc.createdOne", { count: rows.length })
            : t("tc.createdMany", { count: rows.length }),
        description: t("tc.createdDesc", { order: selectedOrder.orderNumber }),
      });
      navigate("tasks");
    } catch (e) {
      toast({
        title: t("tc.createFailed"),
        description: e instanceof Error ? e.message : t("tc.unexpectedError"),
        variant: "destructive",
      });
    } finally {
      setSubmitting(false);
    }
  }

  // ---- Guards ----------------------------------------------------------------

  if (!supervisor) {
    return (
      <EmptyState
        icon={Ban}
        title={t("tc.notAuthorized")}
        description={t("tc.notAuthorizedDesc")}
        action={
          <Button variant="outline" size="sm" onClick={() => navigate("tasks")}>
            <ArrowLeft className="size-4" />
            {t("tc.backToTasks")}
          </Button>
        }
      />
    );
  }

  if (ordersError) {
    return (
      <div>
        <PageHeader
          title={t("tc.title")}
          description={t("tc.description")}
        />
        <Card className="shadow-sm">
          <CardContent className="p-0">
            <EmptyState
              icon={AlertTriangle}
              title={t("tc.loadOrdersTitle")}
              description={ordersError}
              action={
                <Button variant="outline" size="sm" onClick={() => loadOrders()}>
                  <RefreshCw className="size-4" />
                  {t("common.tryAgain")}
                </Button>
              }
            />
          </CardContent>
        </Card>
      </div>
    );
  }

  // ---- Main form --------------------------------------------------------------

  return (
    <div>
      <Button variant="ghost" size="sm" className="-ml-2 mb-2" onClick={() => navigate("tasks")}>
        <ArrowLeft className="size-4" />
        {t("tc.backToTasks")}
      </Button>
      <PageHeader
        title={t("tc.title")}
        description={t("tc.descriptionFull")}
      />

      {orders === null ? (
        <div className="space-y-4">
          <Skeleton className="h-52" />
          <Skeleton className="h-72" />
        </div>
      ) : orders.length === 0 ? (
        <Card className="shadow-sm">
          <CardContent className="p-0">
            <EmptyState
              title={t("tc.noOrders")}
              description={t("tc.noOrdersDesc")}
              action={
                <Button variant="outline" size="sm" onClick={() => navigate("orders")}>
                  {t("tc.goToOrders")}
                </Button>
              }
            />
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {/* ---- Order selection ---- */}
          <Card className="shadow-sm">
            <CardHeader className="pb-3">
              <CardTitle className="text-base">{t("tc.customerOrder")}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-1.5">
                <Label htmlFor="order-select">{t("tc.order")}</Label>
                <Select value={selectedOrderId} onValueChange={selectOrder}>
                  <SelectTrigger id="order-select" className="w-full">
                    <SelectValue placeholder={t("tc.orderPlaceholder")} />
                  </SelectTrigger>
                  <SelectContent>
                    {orders.map((o) => (
                      <SelectItem key={o.id} value={o.id}>
                        {o.orderNumber} — {o.customerName} ({o.description})
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {selectedOrder && (
                <div className="grid grid-cols-2 md:grid-cols-3 gap-3 rounded-lg border bg-muted/30 p-4">
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      {t("tc.customer")}
                    </p>
                    <p className="text-sm font-medium mt-0.5">{selectedOrder.customerName}</p>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      {t("tc.phone")}
                    </p>
                    <p className="text-sm mt-0.5">{selectedOrder.customerPhone ?? "—"}</p>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      {t("tc.orderDate")}
                    </p>
                    <p className="text-sm mt-0.5">{formatDate(selectedOrder.orderDate)}</p>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      {t("tc.expectedDelivery")}
                    </p>
                    <p className="text-sm mt-0.5">
                      {formatDate(selectedOrder.expectedDeliveryDate)}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      {t("tc.priority")}
                    </p>
                    <div className="mt-1">
                      <PriorityBadge priority={selectedOrder.priority} />
                    </div>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      {t("tc.pieces")}
                    </p>
                    <p className="text-sm mt-0.5">{selectedOrder.numberOfPieces}</p>
                  </div>
                  <div className="col-span-2 md:col-span-3">
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      {t("tc.orderInstructions")}
                    </p>
                    <p className="text-sm mt-0.5 whitespace-pre-wrap">
                      {selectedOrder.instructions ?? "—"}
                    </p>
                  </div>
                  <div className="col-span-2 md:col-span-3">
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      {t("tc.existingTasks")}
                    </p>
                    <p className="text-sm mt-0.5">
                      {selectedOrder.taskCount > 0
                        ? t("tc.existingTasksSummaryProgress", {
                            total: selectedOrder.taskCount,
                            completed: selectedOrder.completedTaskCount,
                            progress: Math.round(selectedOrder.avgProgress),
                          })
                        : t("tc.existingTasksSummary", {
                            total: selectedOrder.taskCount,
                            completed: selectedOrder.completedTaskCount,
                          })}
                    </p>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* ---- Piece rows ---- */}
          <Card className="shadow-sm">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between gap-2">
                <CardTitle className="text-base">{t("tc.garmentPieces")}</CardTitle>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setRows((rs) => [...rs, makeRow(selectedOrder)])}
                >
                  <Plus className="size-4" />
                  {t("tc.addPiece")}
                </Button>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              {rows.map((row, i) => (
                <div key={i} className="rounded-lg border p-4 space-y-3">
                  <div className="flex items-center justify-between">
                    <p className="text-sm font-semibold">
                      {t("tc.pieceNumber", { number: i + 1 })}
                    </p>
                    {rows.length > 1 && (
                      <Button
                        variant="ghost"
                        size="icon"
                        className="size-8 text-muted-foreground hover:text-destructive"
                        onClick={() => setRows((rs) => rs.filter((_, j) => j !== i))}
                        aria-label={t("tc.removePiece", { number: i + 1 })}
                      >
                        <Trash2 className="size-4" />
                      </Button>
                    )}
                  </div>

                  <div className="grid gap-3 sm:grid-cols-2">
                    <div className="space-y-1.5 sm:col-span-2">
                      <Label htmlFor={`piece-${i}`}>{t("tc.pieceName")}</Label>
                      <Input
                        id={`piece-${i}`}
                        placeholder={t("tc.pieceNamePlaceholder")}
                        value={row.garmentPiece}
                        onChange={(e) => updateRow(i, { garmentPiece: e.target.value })}
                      />
                    </div>

                    <div className="space-y-1.5">
                      <Label>{t("tc.category")}</Label>
                      <Select
                        value={row.category}
                        onValueChange={(v) => updateRow(i, { category: v })}
                      >
                        <SelectTrigger
                          className="w-full"
                          aria-label={t("tc.categoryForPiece", { number: i + 1 })}
                        >
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {TASK_CATEGORIES.map((c) => (
                            <SelectItem key={c} value={c}>
                              {categoryLabel(c, lang)}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-1.5">
                      <Label>{t("tc.assignedStaff")}</Label>
                      <Select
                        value={row.assignedStaffId}
                        onValueChange={(v) => updateRow(i, { assignedStaffId: v })}
                      >
                        <SelectTrigger
                          className="w-full"
                          aria-label={t("tc.staffForPiece", { number: i + 1 })}
                        >
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value={UNASSIGNED}>{t("tc.leaveUnassigned")}</SelectItem>
                          {staff.map((u) => (
                            <SelectItem key={u.id} value={u.id}>
                              {u.name}
                              {u.activeTaskCount != null
                                ? ` (${t("tc.activeCount", { count: u.activeTaskCount })})`
                                : ""}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-1.5">
                      <Label>{t("tc.priority")}</Label>
                      <Select
                        value={row.priority}
                        onValueChange={(v) => updateRow(i, { priority: v })}
                      >
                        <SelectTrigger
                          className="w-full"
                          aria-label={t("tc.priorityForPiece", { number: i + 1 })}
                        >
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {PRIORITIES.map((p) => (
                            <SelectItem key={p} value={p}>
                              {priorityLabel(p, lang)}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor={`deadline-${i}`}>{t("tc.deadline")}</Label>
                      <Input
                        id={`deadline-${i}`}
                        type="datetime-local"
                        value={row.deadline}
                        onChange={(e) => updateRow(i, { deadline: e.target.value })}
                      />
                    </div>

                    <div className="space-y-1.5 sm:col-span-2">
                      <Label htmlFor={`instructions-${i}`}>{t("tc.instructions")}</Label>
                      <Textarea
                        id={`instructions-${i}`}
                        placeholder={t("tc.instructionsPlaceholder")}
                        value={row.instructions}
                        onChange={(e) => updateRow(i, { instructions: e.target.value })}
                      />
                    </div>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>

          {/* ---- Submit ---- */}
          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pb-2">
            <p className="text-sm text-muted-foreground">
              {rows.length === 1
                ? t("tc.summaryOne", {
                    count: rows.length,
                    order: selectedOrder?.orderNumber ?? "—",
                  })
                : t("tc.summaryMany", {
                    count: rows.length,
                    order: selectedOrder?.orderNumber ?? "—",
                  })}
            </p>
            <div className="flex gap-2">
              <Button variant="outline" disabled={submitting} onClick={() => navigate("tasks")}>
                {t("common.cancel")}
              </Button>
              <Button disabled={submitting || !selectedOrderId} onClick={submit}>
                {submitting ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : (
                  <Check className="size-4" />
                )}
                {rows.length === 1
                  ? t("tc.createOne", { count: rows.length })
                  : t("tc.createMany", { count: rows.length })}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
