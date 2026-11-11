"use client";

// OSSZ ATELIER — order registry: filter, register orders (with existing or
// inline new customers), inspect production progress per order and edit.

import { useCallback, useEffect, useState } from "react";
import { api, ApiClientError, qs } from "../client";
import { can, useAppStore } from "../store";
import type { Customer, OrderSummary, TaskSummary } from "../types";
import { DueDate, EmptyState, PageHeader, PriorityBadge, ProgressCell, StatusBadge } from "../ui-bits";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label as FormLabel } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Skeleton } from "@/components/ui/skeleton";
import { Separator } from "@/components/ui/separator";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PRIORITIES, PRIORITY_LABELS } from "@/lib/atelier/constants";
import { formatDate } from "@/lib/format";
import { AlertTriangle, ClipboardList, Package, Pencil, Plus, Scissors, Search } from "lucide-react";

type OrderForm = {
  orderNumber: string;
  customerMode: "existing" | "new";
  customerId: string;
  ncName: string;
  ncPhone: string;
  ncEmail: string;
  ncAddress: string;
  ncNotes: string;
  orderDate: string;
  expectedDeliveryDate: string;
  description: string;
  garmentCategory: string;
  numberOfPieces: string;
  priority: string;
  instructions: string;
  notes: string;
};

type OrderDetail = { order: OrderSummary; tasks: TaskSummary[] };

const EMPTY_FORM: OrderForm = {
  orderNumber: "",
  customerMode: "existing",
  customerId: "",
  ncName: "",
  ncPhone: "",
  ncEmail: "",
  ncAddress: "",
  ncNotes: "",
  orderDate: new Date().toISOString().slice(0, 10),
  expectedDeliveryDate: "",
  description: "",
  garmentCategory: "",
  numberOfPieces: "1",
  priority: "NORMAL",
  instructions: "",
  notes: "",
};

function useDebounced<T>(value: T, ms = 300) {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), ms);
    return () => clearTimeout(t);
  }, [value, ms]);
  return debounced;
}

function toInputDate(iso: string | null): string {
  return iso ? iso.slice(0, 10) : "";
}

function isPast(iso: string | null): boolean {
  return !!iso && new Date(iso).getTime() < Date.now();
}

export function OrdersView() {
  const user = useAppStore((s) => s.user);
  const navigate = useAppStore((s) => s.navigate);
  const { toast } = useToast();
  const canManage = can(user, "orders.manage");

  const [orders, setOrders] = useState<OrderSummary[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [priority, setPriority] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const debouncedSearch = useDebounced(search);

  const [customers, setCustomers] = useState<Customer[]>([]);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<OrderSummary | null>(null);
  const [form, setForm] = useState<OrderForm>(EMPTY_FORM);
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const [detail, setDetail] = useState<OrderDetail | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [detailId, setDetailId] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const res = await api.get<{ orders: OrderSummary[] }>(
        `/api/atelier/orders${qs({ search: debouncedSearch, priority, from, to })}`
      );
      setOrders(res.orders);
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load orders");
    }
  }, [debouncedSearch, priority, from, to]);

  const loadCustomers = useCallback(async () => {
    try {
      const res = await api.get<{ customers: Customer[] }>("/api/atelier/customers");
      setCustomers(res.customers);
    } catch {
      // Non-blocking: the new-customer mode remains available.
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    loadCustomers();
  }, [loadCustomers]);

  const openDetail = useCallback(async (id: string) => {
    setDetailId(id);
    setDetail(null);
    setDetailLoading(true);
    try {
      const res = await api.get<OrderDetail>(`/api/atelier/orders/${id}`);
      setDetail(res);
    } catch (e) {
      toast({
        title: "Could not load order",
        description: e instanceof Error ? e.message : undefined,
        variant: "destructive",
      });
      setDetailId(null);
    } finally {
      setDetailLoading(false);
    }
  }, [toast]);

  function openCreate() {
    setEditing(null);
    setForm({
      ...EMPTY_FORM,
      customerMode: customers.length > 0 ? "existing" : "new",
      customerId: "",
    });
    setFormError(null);
    setFormOpen(true);
  }

  function openEdit(o: OrderSummary) {
    setEditing(o);
    setForm({
      orderNumber: o.orderNumber,
      customerMode: "existing",
      customerId: o.customerId,
      ncName: "",
      ncPhone: "",
      ncEmail: "",
      ncAddress: "",
      ncNotes: "",
      orderDate: toInputDate(o.orderDate),
      expectedDeliveryDate: toInputDate(o.expectedDeliveryDate),
      description: o.description,
      garmentCategory: o.garmentCategory,
      numberOfPieces: String(o.numberOfPieces),
      priority: o.priority,
      instructions: o.instructions ?? "",
      notes: o.notes ?? "",
    });
    setFormError(null);
    setDetail(null);
    setFormOpen(true);
  }

  function buildPayload(): Record<string, unknown> | string {
    if (!form.orderNumber.trim()) return "Order number is required.";
    if (!form.orderDate) return "Order date is required.";
    if (!form.description.trim()) return "Description is required.";
    if (!form.garmentCategory.trim()) return "Garment category is required.";
    const pieces = Number.parseInt(form.numberOfPieces, 10);
    if (!Number.isFinite(pieces) || pieces < 1 || pieces > 200) {
      return "Number of pieces must be between 1 and 200.";
    }
    let customer: Record<string, unknown> | undefined;
    if (!editing) {
      if (form.customerMode === "existing") {
        if (!form.customerId) return "Select an existing customer or choose “New customer”.";
      } else {
        if (!form.ncName.trim() || !form.ncPhone.trim()) {
          return "New customer name and phone are required.";
        }
        customer = {
          name: form.ncName,
          phone: form.ncPhone,
          ...(form.ncEmail ? { email: form.ncEmail } : {}),
          ...(form.ncAddress ? { address: form.ncAddress } : {}),
          ...(form.ncNotes ? { notes: form.ncNotes } : {}),
        };
      }
    }
    return {
      orderNumber: form.orderNumber,
      ...(editing ? {} : form.customerMode === "existing" ? { customerId: form.customerId } : { newCustomer: customer }),
      orderDate: form.orderDate,
      expectedDeliveryDate: form.expectedDeliveryDate || null,
      description: form.description,
      garmentCategory: form.garmentCategory,
      numberOfPieces: pieces,
      priority: form.priority,
      ...(form.instructions ? { instructions: form.instructions } : {}),
      ...(form.notes ? { notes: form.notes } : {}),
    };
  }

  async function submit() {
    const payload = buildPayload();
    if (typeof payload === "string") {
      setFormError(payload);
      return;
    }
    setSubmitting(true);
    setFormError(null);
    try {
      if (editing) {
        await api.patch(`/api/atelier/orders/${editing.id}`, payload);
        toast({ title: "Order updated", description: `${form.orderNumber} was saved.` });
      } else {
        await api.post("/api/atelier/orders", payload);
        toast({ title: "Order registered", description: `${form.orderNumber} was created.` });
      }
      setFormOpen(false);
      await load();
      if (detailId) await openDetail(detailId);
    } catch (e) {
      const message =
        e instanceof ApiClientError
          ? e.details?.issues?.[0]?.message ?? e.message
          : "Something went wrong. Please try again.";
      setFormError(message);
      toast({
        title: editing ? "Could not update order" : "Could not register order",
        description: message,
        variant: "destructive",
      });
    } finally {
      setSubmitting(false);
    }
  }

  const hasFilters = !!search || !!priority || !!from || !!to;

  return (
    <div>
      <PageHeader
        title="Orders"
        description="Manually registered production orders and their progress."
      >
        {canManage && (
          <Button onClick={openCreate}>
            <Plus className="size-4 mr-1.5" />
            Register Order
          </Button>
        )}
      </PageHeader>

      <div className="mb-4 flex flex-col sm:flex-row gap-2 sm:items-center">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search order number, customer, description…"
            className="pl-8"
            aria-label="Search orders"
          />
        </div>
        <div className="flex flex-wrap gap-2 items-center">
          <Select value={priority || "all"} onValueChange={(v) => setPriority(v === "all" ? "" : v)}>
            <SelectTrigger className="w-[150px]" aria-label="Filter by priority">
              <SelectValue placeholder="Priority" />
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
          <div className="flex items-center gap-1.5">
            <Input
              type="date"
              value={from}
              onChange={(e) => setFrom(e.target.value)}
              className="w-[145px]"
              aria-label="From date"
            />
            <span className="text-muted-foreground text-sm">to</span>
            <Input
              type="date"
              value={to}
              onChange={(e) => setTo(e.target.value)}
              className="w-[145px]"
              aria-label="To date"
            />
          </div>
          {hasFilters && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => {
                setSearch("");
                setPriority("");
                setFrom("");
                setTo("");
              }}
            >
              Clear
            </Button>
          )}
        </div>
      </div>

      {error ? (
        <EmptyState
          icon={AlertTriangle}
          title="Could not load orders"
          description={error}
          action={
            <Button variant="outline" size="sm" onClick={load}>
              Try again
            </Button>
          }
        />
      ) : orders === null ? (
        <div className="space-y-3">
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
        </div>
      ) : orders.length === 0 ? (
        <div className="rounded-lg border">
          <EmptyState
            icon={Package}
            title={hasFilters ? "No orders match your filters" : "No orders registered yet"}
            description={
              hasFilters
                ? "Adjust the search or date filters to see more results."
                : "Register a customer order to start planning production."
            }
            action={
              canManage && !hasFilters ? (
                <Button size="sm" onClick={openCreate}>
                  <Plus className="size-4 mr-1.5" />
                  Register Order
                </Button>
              ) : undefined
            }
          />
        </div>
      ) : (
        <div className="rounded-lg border overflow-x-auto">
          <Table className="min-w-[980px]">
            <TableHeader>
              <TableRow>
                <TableHead>Order</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Garment</TableHead>
                <TableHead className="text-center">Pieces</TableHead>
                <TableHead>Priority</TableHead>
                <TableHead>Expected Delivery</TableHead>
                <TableHead className="min-w-52">Production</TableHead>
                <TableHead>Registered</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {orders.map((o) => (
                <TableRow
                  key={o.id}
                  className="cursor-pointer"
                  onClick={() => openDetail(o.id)}
                >
                  <TableCell className="font-medium whitespace-nowrap">{o.orderNumber}</TableCell>
                  <TableCell>
                    <p className="font-medium truncate">{o.customerName}</p>
                    <p className="text-xs text-muted-foreground truncate">{o.customerPhone}</p>
                  </TableCell>
                  <TableCell className="max-w-56">
                    <p className="truncate">{o.description}</p>
                    <p className="text-xs text-muted-foreground truncate">{o.garmentCategory}</p>
                  </TableCell>
                  <TableCell className="text-center tabular-nums">{o.numberOfPieces}</TableCell>
                  <TableCell>
                    <PriorityBadge priority={o.priority} />
                  </TableCell>
                  <TableCell>
                    <DueDate deadline={o.expectedDeliveryDate} overdue={isPast(o.expectedDeliveryDate)} />
                  </TableCell>
                  <TableCell>
                    <ProgressCell value={o.avgProgress} />
                    <p className="text-xs text-muted-foreground mt-1">
                      {o.taskCount === 0
                        ? "No tasks yet"
                        : `${o.completedTaskCount}/${o.taskCount} task${o.taskCount > 1 ? "s" : ""} done`}
                    </p>
                  </TableCell>
                  <TableCell className="text-muted-foreground whitespace-nowrap">
                    {formatDate(o.createdAt)}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      {/* ------------------------------------------------ order detail dialog */}
      <Dialog
        open={!!detailId}
        onOpenChange={(open) => {
          if (!open) setDetailId(null);
        }}
      >
        <DialogContent className="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
          {detailLoading || !detail ? (
            <div className="space-y-3 py-2">
              <Skeleton className="h-7 w-56" />
              <Skeleton className="h-24 w-full" />
              <Skeleton className="h-32 w-full" />
            </div>
          ) : (
            <>
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2 flex-wrap">
                  <span>{detail.order.orderNumber}</span>
                  <PriorityBadge priority={detail.order.priority} />
                </DialogTitle>
                <DialogDescription>
                  Registered {formatDate(detail.order.createdAt)} · {detail.order.customerName}
                </DialogDescription>
              </DialogHeader>

              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Order date</p>
                  <p className="font-medium mt-0.5">{formatDate(detail.order.orderDate)}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Expected delivery</p>
                  <div className="mt-0.5">
                    <DueDate
                      deadline={detail.order.expectedDeliveryDate}
                      overdue={isPast(detail.order.expectedDeliveryDate)}
                    />
                  </div>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Pieces</p>
                  <p className="font-medium mt-0.5 tabular-nums">{detail.order.numberOfPieces}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Category</p>
                  <p className="font-medium mt-0.5">{detail.order.garmentCategory}</p>
                </div>
              </div>

              <div>
                <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Description</p>
                <p className="text-sm">{detail.order.description}</p>
              </div>
              {detail.order.instructions && (
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Instructions</p>
                  <p className="text-sm whitespace-pre-line">{detail.order.instructions}</p>
                </div>
              )}
              {detail.order.notes && (
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Notes</p>
                  <p className="text-sm whitespace-pre-line text-muted-foreground">{detail.order.notes}</p>
                </div>
              )}

              <Separator />

              <div>
                <div className="flex items-center justify-between gap-2 mb-2">
                  <p className="text-sm font-semibold">Production tasks</p>
                  <span className="text-xs text-muted-foreground">
                    {detail.order.completedTaskCount}/{detail.order.taskCount} done · avg{" "}
                    {detail.order.avgProgress}%
                  </span>
                </div>
                {detail.tasks.length === 0 ? (
                  <EmptyState
                    icon={ClipboardList}
                    title="Not broken into tasks yet"
                    description="Split this order into garment pieces to start production."
                  />
                ) : (
                  <div className="rounded-lg border divide-y overflow-x-auto">
                    {detail.tasks.map((t) => (
                      <div
                        key={t.id}
                        className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 px-4 py-2.5 min-w-[520px]"
                      >
                        <div className="min-w-0 flex-1">
                          <p className="text-sm font-medium truncate">
                            {t.garmentPiece} <span className="text-muted-foreground font-normal">· {t.code}</span>
                          </p>
                          <p className="text-xs text-muted-foreground truncate">
                            {t.assignedStaffName ?? "Unassigned"}
                          </p>
                        </div>
                        <StatusBadge status={t.status} />
                        <ProgressCell value={t.progress} status={t.status} className="w-32" />
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {(canManage || can(user, "production.create.tasks")) && (
                <DialogFooter>
                  {can(user, "production.create.tasks") && (
                    <Button
                      onClick={() => navigate("task-create", { orderId: detail.order.id })}
                    >
                      <Scissors className="size-4 mr-1.5" />
                      Break into Tasks
                    </Button>
                  )}
                  {canManage && (
                    <Button variant="outline" onClick={() => openEdit(detail.order)}>
                      <Pencil className="size-4 mr-1.5" />
                      Edit
                    </Button>
                  )}
                </DialogFooter>
              )}
            </>
          )}
        </DialogContent>
      </Dialog>

      {/* -------------------------------------------- register / edit dialog */}
      <Dialog open={formOpen} onOpenChange={setFormOpen}>
        <DialogContent className="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editing ? `Edit Order ${editing.orderNumber}` : "Register Order"}</DialogTitle>
            <DialogDescription>
              {editing
                ? "Update order details. The linked customer cannot be changed."
                : "Enter the business order reference and garment details."}
            </DialogDescription>
          </DialogHeader>

          {!editing && (
            <>
              <div className="grid gap-1.5">
                <FormLabel>Customer</FormLabel>
                <RadioGroup
                  value={form.customerMode}
                  onValueChange={(v) => setForm({ ...form, customerMode: v as OrderForm["customerMode"] })}
                  className="flex flex-col sm:flex-row gap-2"
                >
                  <label
                    htmlFor="cust-existing"
                    className="flex flex-1 items-center gap-2 rounded-lg border p-3 cursor-pointer has-[button[data-state=checked]]:border-primary has-[button[data-state=checked]]:bg-primary/5"
                  >
                    <RadioGroupItem value="existing" id="cust-existing" />
                    <span className="text-sm font-medium">Existing customer</span>
                  </label>
                  <label
                    htmlFor="cust-new"
                    className="flex flex-1 items-center gap-2 rounded-lg border p-3 cursor-pointer has-[button[data-state=checked]]:border-primary has-[button[data-state=checked]]:bg-primary/5"
                  >
                    <RadioGroupItem value="new" id="cust-new" />
                    <span className="text-sm font-medium">New customer</span>
                  </label>
                </RadioGroup>
              </div>
              {form.customerMode === "existing" ? (
                <div className="grid gap-1.5">
                  <FormLabel htmlFor="order-customer">Select customer *</FormLabel>
                  <Select
                    value={form.customerId}
                    onValueChange={(v) => setForm({ ...form, customerId: v })}
                  >
                    <SelectTrigger id="order-customer">
                      <SelectValue placeholder={customers.length ? "Select a customer…" : "Loading customers…"} />
                    </SelectTrigger>
                    <SelectContent>
                      {customers.map((c) => (
                        <SelectItem key={c.id} value={c.id}>
                          {c.name} — {c.phone}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              ) : (
                <div className="grid gap-4 sm:grid-cols-2 rounded-lg border p-3">
                  <p className="sm:col-span-2 text-xs font-medium text-muted-foreground uppercase tracking-wide">
                    New customer details
                  </p>
                  <div className="grid gap-1.5">
                    <FormLabel htmlFor="nc-name">Name *</FormLabel>
                    <Input
                      id="nc-name"
                      value={form.ncName}
                      onChange={(e) => setForm({ ...form, ncName: e.target.value })}
                      maxLength={120}
                    />
                  </div>
                  <div className="grid gap-1.5">
                    <FormLabel htmlFor="nc-phone">Phone *</FormLabel>
                    <Input
                      id="nc-phone"
                      value={form.ncPhone}
                      onChange={(e) => setForm({ ...form, ncPhone: e.target.value })}
                      maxLength={40}
                    />
                  </div>
                  <div className="grid gap-1.5 sm:col-span-2">
                    <FormLabel htmlFor="nc-email">Email</FormLabel>
                    <Input
                      id="nc-email"
                      type="email"
                      value={form.ncEmail}
                      onChange={(e) => setForm({ ...form, ncEmail: e.target.value })}
                    />
                  </div>
                  <div className="grid gap-1.5 sm:col-span-2">
                    <FormLabel htmlFor="nc-address">Address</FormLabel>
                    <Input
                      id="nc-address"
                      value={form.ncAddress}
                      onChange={(e) => setForm({ ...form, ncAddress: e.target.value })}
                      maxLength={300}
                    />
                  </div>
                  <div className="grid gap-1.5 sm:col-span-2">
                    <FormLabel htmlFor="nc-notes">Customer notes</FormLabel>
                    <Textarea
                      id="nc-notes"
                      rows={2}
                      value={form.ncNotes}
                      onChange={(e) => setForm({ ...form, ncNotes: e.target.value })}
                    />
                  </div>
                </div>
              )}
              <Separator />
            </>
          )}

          {editing && (
            <div className="grid gap-1.5">
              <FormLabel>Customer</FormLabel>
              <p className="text-sm rounded-lg border p-3 bg-muted/40">
                <span className="font-medium">{editing.customerName}</span>
                <span className="text-muted-foreground"> · {editing.customerPhone}</span>
              </p>
            </div>
          )}

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-number">Order number *</FormLabel>
              <Input
                id="order-number"
                value={form.orderNumber}
                onChange={(e) => setForm({ ...form, orderNumber: e.target.value })}
                placeholder="e.g. OSSZ-2026-042"
                maxLength={50}
              />
            </div>
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-priority">Priority</FormLabel>
              <Select value={form.priority} onValueChange={(v) => setForm({ ...form, priority: v })}>
                <SelectTrigger id="order-priority">
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
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-date">Order date *</FormLabel>
              <Input
                id="order-date"
                type="date"
                value={form.orderDate}
                onChange={(e) => setForm({ ...form, orderDate: e.target.value })}
              />
            </div>
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-delivery">Expected delivery</FormLabel>
              <Input
                id="order-delivery"
                type="date"
                value={form.expectedDeliveryDate}
                onChange={(e) => setForm({ ...form, expectedDeliveryDate: e.target.value })}
              />
            </div>
            <div className="grid gap-1.5 sm:col-span-2">
              <FormLabel htmlFor="order-desc">Description *</FormLabel>
              <Input
                id="order-desc"
                value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })}
                placeholder="e.g. Bridal gown with hand embroidery"
                maxLength={500}
              />
            </div>
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-category">Garment category *</FormLabel>
              <Input
                id="order-category"
                value={form.garmentCategory}
                onChange={(e) => setForm({ ...form, garmentCategory: e.target.value })}
                placeholder="e.g. Suiting, Evening wear"
                maxLength={80}
              />
            </div>
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-pieces">Number of pieces *</FormLabel>
              <Input
                id="order-pieces"
                type="number"
                min={1}
                max={200}
                value={form.numberOfPieces}
                onChange={(e) => setForm({ ...form, numberOfPieces: e.target.value })}
              />
            </div>
            <div className="grid gap-1.5 sm:col-span-2">
              <FormLabel htmlFor="order-instructions">Production instructions</FormLabel>
              <Textarea
                id="order-instructions"
                rows={3}
                value={form.instructions}
                onChange={(e) => setForm({ ...form, instructions: e.target.value })}
                placeholder="Fabrics, colours, special handling…"
              />
            </div>
            <div className="grid gap-1.5 sm:col-span-2">
              <FormLabel htmlFor="order-notes">Notes</FormLabel>
              <Textarea
                id="order-notes"
                rows={2}
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
                placeholder="Payment terms, follow-ups…"
              />
            </div>
            {formError && (
              <p className="sm:col-span-2 text-sm font-medium text-red-700">{formError}</p>
            )}
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setFormOpen(false)} disabled={submitting}>
              Cancel
            </Button>
            <Button onClick={submit} disabled={submitting}>
              {submitting && (
                <span className="size-4 mr-1.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
              )}
              {editing ? "Save Changes" : "Register Order"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
