"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
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
import {
  ArrowLeft,
  Scissors,
  UserCheck,
  CalendarDays,
  Phone,
  Mail,
  Ruler,
  CheckCircle2,
  AlertTriangle,
  History,
  Factory,
  CircleDollarSign,
} from "lucide-react";
import { api } from "./api-client";
import { can, useAppStore } from "./store";
import { useNav } from "./use-nav";
import { format } from "date-fns";
import { formatDate, formatFCFA, timeAgo } from "@/lib/format";
import { OrderStatusBadge, PaymentStatusBadge, TaskStatusBadge } from "./status-badge";
import { EmptyState } from "./empty-state";
import { BreakdownDialog } from "./breakdown-dialog";
import { EVENT_TYPE_LABELS, type OrderDetail } from "./types";

function prettyMeasurements(json: string | null): Array<[string, string]> {
  if (!json) return [];
  try {
    return Object.entries(JSON.parse(json));
  } catch {
    return [];
  }
}

export function OrderDetailView() {
  const user = useAppStore((s) => s.user)!;
  const { get, navigate } = useNav();
  const orderId = get("order");

  const orderQuery = useQuery({
    queryKey: ["order", orderId],
    queryFn: () => api.get<OrderDetail>(`/api/orders/${orderId}`),
    enabled: !!orderId,
  });

  if (!orderId) {
    return <EmptyState title="No order selected" description="Go back to the orders list." />;
  }

  if (orderQuery.isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-40" />
        <div className="grid lg:grid-cols-3 gap-4">
          <Skeleton className="h-48" />
          <Skeleton className="h-48 lg:col-span-2" />
        </div>
        <Skeleton className="h-64" />
      </div>
    );
  }

  if (orderQuery.isError || !orderQuery.data) {
    return (
      <EmptyState
        title="Order not found"
        description="This order may have been removed."
        action={
          <Button variant="outline" onClick={() => navigate({ view: "orders" }, false)}>
            <ArrowLeft className="size-4" /> Back to orders
          </Button>
        }
      />
    );
  }

  const { order, production } = orderQuery.data;
  const s = production.summary;
  const required = s.totalPieces - s.cancelled;
  const canCreateTasks = can(user, "production.create.tasks");
  const canAssign = can(user, "production.assign");

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center gap-3">
        <Button variant="ghost" size="icon" onClick={() => navigate({ view: "orders" }, false)} aria-label="Back">
          <ArrowLeft className="size-4" />
        </Button>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-3 flex-wrap">
            <h2 className="font-display text-2xl font-semibold font-mono">{order.reference}</h2>
            <OrderStatusBadge status={order.status} />
            <PaymentStatusBadge status={order.paymentStatus} />
          </div>
          <p className="text-sm text-muted-foreground mt-0.5">
            Placed {formatDate(order.createdAt)} · Expected delivery {formatDate(order.expectedDeliveryDate)}
          </p>
        </div>
        {s.totalPieces === 0 && canCreateTasks && order.status !== "CANCELLED" && (
          <BreakdownDialog orderId={order.id} items={order.items} />
        )}
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        {/* Customer & payment card */}
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-semibold text-muted-foreground">Customer</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p className="font-semibold text-base">{order.customer.name}</p>
            {order.customer.phone && (
              <p className="flex items-center gap-2 text-muted-foreground">
                <Phone className="size-3.5" /> {order.customer.phone}
              </p>
            )}
            {order.customer.email && (
              <p className="flex items-center gap-2 text-muted-foreground">
                <Mail className="size-3.5" /> {order.customer.email}
              </p>
            )}
            <div className="pt-2 border-t space-y-1.5">
              <p className="flex items-center justify-between">
                <span className="flex items-center gap-2 text-muted-foreground">
                  <CircleDollarSign className="size-3.5" /> Order total
                </span>
                <span className="font-semibold tabular-nums">{formatFCFA(order.totalAmount)}</span>
              </p>
              <p className="flex items-center justify-between">
                <span className="flex items-center gap-2 text-muted-foreground">
                  <CalendarDays className="size-3.5" /> Delivery
                </span>
                <span>{formatDate(order.expectedDeliveryDate)}</span>
              </p>
            </div>
            {order.notes && (
              <p className="text-xs text-muted-foreground border-t pt-2 mt-2 italic">“{order.notes}”</p>
            )}
          </CardContent>
        </Card>

        {/* Items card */}
        <Card className="lg:col-span-2">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-semibold text-muted-foreground">Order Items</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {order.items.map((item) => {
              const measurements = prettyMeasurements(item.measurements);
              return (
                <div key={item.id} className="border rounded-lg p-3">
                  <div className="flex items-center justify-between gap-2 flex-wrap">
                    <div>
                      <p className="font-semibold">
                        {item.productName}
                        {item.quantity > 1 && (
                          <Badge variant="secondary" className="ml-2">×{item.quantity}</Badge>
                        )}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        {item.category} · {formatFCFA(item.unitPrice)} each
                      </p>
                    </div>
                    <p className="font-semibold tabular-nums">
                      {formatFCFA(item.unitPrice * item.quantity)}
                    </p>
                  </div>
                  <div className="mt-2 grid sm:grid-cols-2 gap-2 text-xs">
                    {measurements.length > 0 && (
                      <div className="flex gap-1.5 flex-wrap">
                        <Ruler className="size-3.5 text-muted-foreground mt-0.5" />
                        {measurements.map(([k, v]) => (
                          <Badge key={k} variant="outline" className="font-normal text-[11px]">
                            {k}: {String(v)}
                          </Badge>
                        ))}
                      </div>
                    )}
                    {item.instructions && (
                      <p className="text-muted-foreground sm:col-span-2 italic">“{item.instructions}”</p>
                    )}
                    {item.productComponents.length > 0 && (
                      <p className="text-muted-foreground sm:col-span-2">
                        Components: {item.productComponents.join(" · ")}
                      </p>
                    )}
                  </div>
                </div>
              );
            })}
          </CardContent>
        </Card>
      </div>

      {/* Production tracking section */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between pb-2">
          <CardTitle className="text-base font-semibold flex items-center gap-2">
            <Factory className="size-4 text-primary" /> Production Tracking
          </CardTitle>
          {s.totalPieces > 0 && canAssign && (
            <Button size="sm" variant="outline" onClick={() => navigate({ view: "production", order: order.id }, false)}>
              <UserCheck className="size-4" /> Manage assignments
            </Button>
          )}
        </CardHeader>
        <CardContent className="space-y-4">
          {s.totalPieces === 0 ? (
            <EmptyState
              icon={<Scissors />}
              title="No production breakdown yet"
              description={
                canCreateTasks
                  ? "Create the production tasks for this order — one per garment piece requiring production."
                  : "A production manager has not yet created the production breakdown for this order."
              }
              action={canCreateTasks && order.status !== "CANCELLED" ? (
                <BreakdownDialog orderId={order.id} items={order.items} />
              ) : undefined}
            />
          ) : (
            <>
              {/* Summary tiles */}
              <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                {[
                  { label: "Total pieces", value: s.totalPieces, tone: "" },
                  { label: "Unassigned", value: s.unassigned, tone: s.unassigned > 0 ? "text-amber-600" : "" },
                  { label: "Not started", value: s.assigned, tone: "" },
                  { label: "In progress", value: s.inProgress, tone: "" },
                  { label: "Inspection", value: s.awaitingInspection, tone: "text-teal-600" },
                  { label: "Approved", value: s.approved, tone: "text-green-600" },
                  { label: "Blocked", value: s.blocked, tone: s.blocked > 0 ? "text-red-600" : "" },
                ].map((t) => (
                  <div key={t.label} className="rounded-lg border bg-muted/30 p-2.5 text-center">
                    <p className={`text-xl font-bold tabular-nums ${t.tone}`}>{t.value}</p>
                    <p className="text-[11px] text-muted-foreground leading-tight mt-0.5">{t.label}</p>
                  </div>
                ))}
              </div>

              {/* Overall progress vs approval */}
              <div className="grid sm:grid-cols-2 gap-4">
                <div>
                  <div className="flex items-center justify-between mb-1.5">
                    <p className="text-sm font-medium">Overall production progress</p>
                    <p className="text-sm font-bold tabular-nums">{s.overallProgress}%</p>
                  </div>
                  <Progress value={s.overallProgress} className="h-2.5" />
                  <p className="text-xs text-muted-foreground mt-1.5">
                    Average completion of all {required} required piece{required > 1 ? "s" : ""}.
                  </p>
                </div>
                <div>
                  <div className="flex items-center justify-between mb-1.5">
                    <p className="text-sm font-medium">Pieces passed inspection</p>
                    <p className="text-sm font-bold tabular-nums">{s.approvedRatio}%</p>
                  </div>
                  <Progress value={s.approvedRatio} className="h-2.5 [&>div]:bg-green-600" />
                  <p className="text-xs text-muted-foreground mt-1.5">
                    {s.approved} of {required} approved
                    {s.productionComplete ? (
                      <span className="text-green-600 font-medium"> — production complete ✓</span>
                    ) : (
                      " — order cannot be marked production-complete until all pieces are approved."
                    )}
                  </p>
                </div>
              </div>

              {/* Pieces table */}
              <div className="rounded-lg border overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Garment Piece</TableHead>
                      <TableHead>Assigned Staff</TableHead>
                      <TableHead>Progress</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Deadline</TableHead>
                      <TableHead>Updated</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {production.tasks.map((t) => (
                      <TableRow
                        key={t.id}
                        className="cursor-pointer"
                        onClick={() => navigate({ task: t.id })}
                      >
                        <TableCell className="font-medium">{t.pieceName}</TableCell>
                        <TableCell>
                          {t.assignedStaff ? (
                            <span className="flex items-center gap-1.5">
                              <UserCheck className="size-3.5 text-muted-foreground" />
                              {t.assignedStaff.name}
                            </span>
                          ) : (
                            <span className="text-muted-foreground text-xs">—</span>
                          )}
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2 min-w-28">
                            <Progress value={t.progressPercentage} className="h-2 w-16" />
                            <span className="text-xs font-semibold tabular-nums">{t.progressPercentage}%</span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <TaskStatusBadge status={t.status} />
                        </TableCell>
                        <TableCell className="text-xs text-muted-foreground">
                          {t.dueAt ? formatDate(t.dueAt) : "—"}
                        </TableCell>
                        <TableCell className="text-xs text-muted-foreground">{timeAgo(t.updatedAt)}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>

              {/* Staff + recent activity */}
              <div className="grid lg:grid-cols-2 gap-4">
                <div>
                  <p className="text-sm font-semibold mb-2 flex items-center gap-1.5">
                    <UserCheck className="size-4 text-muted-foreground" /> Staff on this order
                  </p>
                  <div className="flex flex-wrap gap-1.5">
                    {production.staff.length === 0 ? (
                      <p className="text-xs text-muted-foreground">No staff assigned yet.</p>
                    ) : (
                      production.staff.map((st) => (
                        <Badge key={st.id} variant="secondary" className="font-medium">
                          {st.name}
                        </Badge>
                      ))
                    )}
                  </div>
                </div>
                <div>
                  <p className="text-sm font-semibold mb-2 flex items-center gap-1.5">
                    <History className="size-4 text-muted-foreground" /> Recent production activity
                  </p>
                  <div className="space-y-1 max-h-48 overflow-y-auto text-xs">
                    {production.recentEvents.length === 0 ? (
                      <p className="text-muted-foreground">No activity recorded.</p>
                    ) : (
                      production.recentEvents.slice(0, 8).map((e) => (
                        <div key={e.id} className="flex items-baseline gap-2">
                          <span className="text-muted-foreground/70 tabular-nums shrink-0">
                            {format(e.createdAt, "dd MMM HH:mm")}
                          </span>
                          <span>
                            <span className="font-medium">{EVENT_TYPE_LABELS[e.eventType] ?? e.eventType}</span>{" "}
                            <span className="text-muted-foreground">
                              {e.task?.pieceName ? `— ${e.task.pieceName}` : ""} · {e.actor.name}
                            </span>
                          </span>
                        </div>
                      ))
                    )}
                  </div>
                </div>
              </div>

              {s.overdue > 0 && (
                <div className="flex items-center gap-2 text-sm text-red-600 bg-red-600/5 border border-red-600/20 rounded-lg p-3">
                  <AlertTriangle className="size-4 shrink-0" />
                  <p>
                    {s.overdue} piece{s.overdue > 1 ? "s are" : " is"} past the deadline.
                  </p>
                </div>
              )}
              {s.productionComplete && (
                <div className="flex items-center gap-2 text-sm text-green-700 bg-green-600/5 border border-green-600/20 rounded-lg p-3">
                  <CheckCircle2 className="size-4 shrink-0" />
                  <p>All required pieces have passed inspection — production is complete for this order.</p>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
