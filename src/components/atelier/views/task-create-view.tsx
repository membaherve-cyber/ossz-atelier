"use client";

// OSSZ ATELIER — break a customer order into garment-piece production tasks.
// Supervisor-only screen (Management / Admin).

import { useCallback, useEffect, useMemo, useState } from "react";
import { format } from "date-fns";
import { api } from "../client";
import { isSupervisor, useAppStore } from "../store";
import type { OrderSummary, UserRecord } from "../types";
import { EmptyState, PageHeader, PriorityBadge } from "../ui-bits";
import {
  CATEGORY_LABELS,
  PRIORITIES,
  PRIORITY_LABELS,
  TASK_CATEGORIES,
} from "@/lib/atelier/constants";
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
      setOrdersError(e instanceof Error ? e.message : "Failed to load orders.");
    }
  }, []);

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
        title: "Select an order first",
        description: "Tasks are always created for a specific customer order.",
        variant: "destructive",
      });
      return;
    }
    const names = rows.map((r) => r.garmentPiece.trim());
    if (names.some((n) => !n)) {
      toast({
        title: "Missing garment piece name",
        description: "Every garment piece needs a name (e.g. Jacket, Trousers).",
        variant: "destructive",
      });
      return;
    }
    const seen = new Set<string>();
    for (const name of names) {
      const key = name.toLowerCase();
      if (seen.has(key)) {
        toast({
          title: "Duplicate garment piece",
          description: `"${name}" appears more than once in this form.`,
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
        title: `${rows.length} task${rows.length === 1 ? "" : "s"} created`,
        description: `Production tasks were created for order ${selectedOrder.orderNumber}.`,
      });
      navigate("tasks");
    } catch (e) {
      toast({
        title: "Could not create tasks",
        description: e instanceof Error ? e.message : "Unexpected error.",
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
        title="Not authorized"
        description="Only Management and Admin accounts can create production tasks."
        action={
          <Button variant="outline" size="sm" onClick={() => navigate("tasks")}>
            <ArrowLeft className="size-4" />
            Back to tasks
          </Button>
        }
      />
    );
  }

  if (ordersError) {
    return (
      <div>
        <PageHeader
          title="Create Production Tasks"
          description="Break a customer order into individual garment-piece tasks."
        />
        <Card className="shadow-sm">
          <CardContent className="p-0">
            <EmptyState
              icon={AlertTriangle}
              title="Could not load orders"
              description={ordersError}
              action={
                <Button variant="outline" size="sm" onClick={() => loadOrders()}>
                  <RefreshCw className="size-4" />
                  Retry
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
        Back to tasks
      </Button>
      <PageHeader
        title="Create Production Tasks"
        description="Break a customer order into individual garment-piece tasks. Each piece becomes a trackable task with its own checklist."
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
              title="No orders yet"
              description="Register a customer order first — tasks are always created from an order."
              action={
                <Button variant="outline" size="sm" onClick={() => navigate("orders")}>
                  Go to Orders
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
              <CardTitle className="text-base">Customer order</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-1.5">
                <Label htmlFor="order-select">Order</Label>
                <Select value={selectedOrderId} onValueChange={selectOrder}>
                  <SelectTrigger id="order-select" className="w-full">
                    <SelectValue placeholder="Select the order to break into tasks" />
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
                      Customer
                    </p>
                    <p className="text-sm font-medium mt-0.5">{selectedOrder.customerName}</p>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      Phone
                    </p>
                    <p className="text-sm mt-0.5">{selectedOrder.customerPhone ?? "—"}</p>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      Order date
                    </p>
                    <p className="text-sm mt-0.5">{formatDate(selectedOrder.orderDate)}</p>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      Expected delivery
                    </p>
                    <p className="text-sm mt-0.5">
                      {formatDate(selectedOrder.expectedDeliveryDate)}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      Priority
                    </p>
                    <div className="mt-1">
                      <PriorityBadge priority={selectedOrder.priority} />
                    </div>
                  </div>
                  <div>
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      Pieces
                    </p>
                    <p className="text-sm mt-0.5">{selectedOrder.numberOfPieces}</p>
                  </div>
                  <div className="col-span-2 md:col-span-3">
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      Order instructions
                    </p>
                    <p className="text-sm mt-0.5 whitespace-pre-wrap">
                      {selectedOrder.instructions ?? "—"}
                    </p>
                  </div>
                  <div className="col-span-2 md:col-span-3">
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      Existing tasks on this order
                    </p>
                    <p className="text-sm mt-0.5">
                      {selectedOrder.taskCount} total ({selectedOrder.completedTaskCount} completed
                      {selectedOrder.taskCount > 0
                        ? `, ${Math.round(selectedOrder.avgProgress)}% average progress`
                        : ""}
                      ) — duplicate piece names are rejected.
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
                <CardTitle className="text-base">Garment pieces</CardTitle>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setRows((rs) => [...rs, makeRow(selectedOrder)])}
                >
                  <Plus className="size-4" />
                  Add Piece
                </Button>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              {rows.map((row, i) => (
                <div key={i} className="rounded-lg border p-4 space-y-3">
                  <div className="flex items-center justify-between">
                    <p className="text-sm font-semibold">Piece {i + 1}</p>
                    {rows.length > 1 && (
                      <Button
                        variant="ghost"
                        size="icon"
                        className="size-8 text-muted-foreground hover:text-destructive"
                        onClick={() => setRows((rs) => rs.filter((_, j) => j !== i))}
                        aria-label={`Remove piece ${i + 1}`}
                      >
                        <Trash2 className="size-4" />
                      </Button>
                    )}
                  </div>

                  <div className="grid gap-3 sm:grid-cols-2">
                    <div className="space-y-1.5 sm:col-span-2">
                      <Label htmlFor={`piece-${i}`}>Garment piece name *</Label>
                      <Input
                        id={`piece-${i}`}
                        placeholder="e.g. Jacket, Matching Trousers"
                        value={row.garmentPiece}
                        onChange={(e) => updateRow(i, { garmentPiece: e.target.value })}
                      />
                    </div>

                    <div className="space-y-1.5">
                      <Label>Category</Label>
                      <Select
                        value={row.category}
                        onValueChange={(v) => updateRow(i, { category: v })}
                      >
                        <SelectTrigger className="w-full" aria-label={`Category for piece ${i + 1}`}>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {TASK_CATEGORIES.map((c) => (
                            <SelectItem key={c} value={c}>
                              {CATEGORY_LABELS[c]}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-1.5">
                      <Label>Assigned staff</Label>
                      <Select
                        value={row.assignedStaffId}
                        onValueChange={(v) => updateRow(i, { assignedStaffId: v })}
                      >
                        <SelectTrigger className="w-full" aria-label={`Staff for piece ${i + 1}`}>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value={UNASSIGNED}>Leave unassigned</SelectItem>
                          {staff.map((u) => (
                            <SelectItem key={u.id} value={u.id}>
                              {u.name}
                              {u.activeTaskCount != null ? ` (${u.activeTaskCount} active)` : ""}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-1.5">
                      <Label>Priority</Label>
                      <Select
                        value={row.priority}
                        onValueChange={(v) => updateRow(i, { priority: v })}
                      >
                        <SelectTrigger className="w-full" aria-label={`Priority for piece ${i + 1}`}>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {PRIORITIES.map((p) => (
                            <SelectItem key={p} value={p}>
                              {PRIORITY_LABELS[p]}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor={`deadline-${i}`}>Deadline</Label>
                      <Input
                        id={`deadline-${i}`}
                        type="datetime-local"
                        value={row.deadline}
                        onChange={(e) => updateRow(i, { deadline: e.target.value })}
                      />
                    </div>

                    <div className="space-y-1.5 sm:col-span-2">
                      <Label htmlFor={`instructions-${i}`}>Instructions (optional)</Label>
                      <Textarea
                        id={`instructions-${i}`}
                        placeholder="Piece-specific production instructions"
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
              {rows.length} piece{rows.length === 1 ? "" : "s"} on order{" "}
              {selectedOrder?.orderNumber ?? "—"}
            </p>
            <div className="flex gap-2">
              <Button variant="outline" disabled={submitting} onClick={() => navigate("tasks")}>
                Cancel
              </Button>
              <Button disabled={submitting || !selectedOrderId} onClick={submit}>
                {submitting ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : (
                  <Check className="size-4" />
                )}
                Create {rows.length} Task{rows.length === 1 ? "" : "s"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
