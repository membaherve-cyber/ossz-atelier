"use client";

// OSSZ ATELIER — order registry: filter, register orders (with existing or
// inline new customers), inspect production progress per order and edit.

import { useCallback, useEffect, useState } from "react";
import { api, ApiClientError, qs } from "../client";
import { can, useAppStore } from "../store";
import { useI18n } from "../i18n/use-i18n";
import { priorityLabel } from "../i18n/core";
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
import { PRIORITIES } from "@/lib/atelier/constants";
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
  const { t, lang } = useI18n();
  // Hoisted because the task list below shadows `t` with its map parameter.
  const unassignedLabel = t("orders.unassigned");
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
      setError(e instanceof Error ? e.message : t("orders.loadFail"));
    }
  }, [debouncedSearch, priority, from, to, t]);

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
        title: t("orders.loadOrderFailed"),
        description: e instanceof Error ? e.message : undefined,
        variant: "destructive",
      });
      setDetailId(null);
    } finally {
      setDetailLoading(false);
    }
  }, [toast, t]);

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
    if (!form.orderNumber.trim()) return t("orders.errOrderNumber");
    if (!form.orderDate) return t("orders.errOrderDate");
    if (!form.description.trim()) return t("orders.errDescription");
    if (!form.garmentCategory.trim()) return t("orders.errCategory");
    const pieces = Number.parseInt(form.numberOfPieces, 10);
    if (!Number.isFinite(pieces) || pieces < 1 || pieces > 200) {
      return t("orders.errPieces");
    }
    let customer: Record<string, unknown> | undefined;
    if (!editing) {
      if (form.customerMode === "existing") {
        if (!form.customerId) return t("orders.errCustomerSelect");
      } else {
        if (!form.ncName.trim() || !form.ncPhone.trim()) {
          return t("orders.errNewCustomer");
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
        toast({
          title: t("orders.updatedToast"),
          description: t("orders.updatedToastDesc", { number: form.orderNumber }),
        });
      } else {
        await api.post("/api/atelier/orders", payload);
        toast({
          title: t("orders.registeredToast"),
          description: t("orders.registeredToastDesc", { number: form.orderNumber }),
        });
      }
      setFormOpen(false);
      await load();
      if (detailId) await openDetail(detailId);
    } catch (e) {
      const message =
        e instanceof ApiClientError
          ? e.details?.issues?.[0]?.message ?? e.message
          : t("common.somethingWrong");
      setFormError(message);
      toast({
        title: editing ? t("orders.updateFailed") : t("orders.registerFailed"),
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
        title={t("orders.title")}
        description={t("orders.subtitle")}
      >
        {canManage && (
          <Button onClick={openCreate}>
            <Plus className="size-4 mr-1.5" />
            {t("orders.register")}
          </Button>
        )}
      </PageHeader>

      <div className="mb-4 flex flex-col sm:flex-row gap-2 sm:items-center">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={t("orders.searchPlaceholder")}
            className="pl-8"
            aria-label={t("orders.searchLabel")}
          />
        </div>
        <div className="flex flex-wrap gap-2 items-center">
          <Select value={priority || "all"} onValueChange={(v) => setPriority(v === "all" ? "" : v)}>
            <SelectTrigger className="w-[150px]" aria-label={t("orders.filterPriorityLabel")}>
              <SelectValue placeholder={t("orders.priorityPlaceholder")} />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">{t("orders.allPriorities")}</SelectItem>
              {PRIORITIES.map((p) => (
                <SelectItem key={p} value={p}>
                  {priorityLabel(p, lang)}
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
              aria-label={t("orders.fromDateLabel")}
            />
            <span className="text-muted-foreground text-sm">{t("orders.toLabel")}</span>
            <Input
              type="date"
              value={to}
              onChange={(e) => setTo(e.target.value)}
              className="w-[145px]"
              aria-label={t("orders.toDateLabel")}
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
              {t("orders.clear")}
            </Button>
          )}
        </div>
      </div>

      {error ? (
        <EmptyState
          icon={AlertTriangle}
          title={t("orders.loadFailedTitle")}
          description={error}
          action={
            <Button variant="outline" size="sm" onClick={load}>
              {t("common.tryAgain")}
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
            title={hasFilters ? t("orders.emptyFiltersTitle") : t("orders.emptyTitle")}
            description={
              hasFilters
                ? t("orders.emptyFiltersDesc")
                : t("orders.emptyDesc")
            }
            action={
              canManage && !hasFilters ? (
                <Button size="sm" onClick={openCreate}>
                  <Plus className="size-4 mr-1.5" />
                  {t("orders.register")}
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
                <TableHead>{t("orders.colOrder")}</TableHead>
                <TableHead>{t("orders.colCustomer")}</TableHead>
                <TableHead>{t("orders.colGarment")}</TableHead>
                <TableHead className="text-center">{t("orders.colPieces")}</TableHead>
                <TableHead>{t("orders.colPriority")}</TableHead>
                <TableHead>{t("orders.colDelivery")}</TableHead>
                <TableHead className="min-w-52">{t("orders.colProduction")}</TableHead>
                <TableHead>{t("orders.colRegistered")}</TableHead>
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
                        ? t("orders.noTasks")
                        : t(o.taskCount > 1 ? "orders.tasksDonePlural" : "orders.tasksDone", {
                            done: o.completedTaskCount,
                            total: o.taskCount,
                          })}
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
              {/* Accessible title/description while the order loads (Radix
                  requires them inside DialogContent). */}
              <DialogTitle className="sr-only">{t("orders.detailTitle")}</DialogTitle>
              <DialogDescription className="sr-only">{t("common.loading")}</DialogDescription>
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
                  {t("orders.detailMeta", {
                    date: formatDate(detail.order.createdAt),
                    name: detail.order.customerName,
                  })}
                </DialogDescription>
              </DialogHeader>

              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">{t("orders.statOrderDate")}</p>
                  <p className="font-medium mt-0.5">{formatDate(detail.order.orderDate)}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">{t("orders.statDelivery")}</p>
                  <div className="mt-0.5">
                    <DueDate
                      deadline={detail.order.expectedDeliveryDate}
                      overdue={isPast(detail.order.expectedDeliveryDate)}
                    />
                  </div>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">{t("orders.statPieces")}</p>
                  <p className="font-medium mt-0.5 tabular-nums">{detail.order.numberOfPieces}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">{t("orders.statCategory")}</p>
                  <p className="font-medium mt-0.5">{detail.order.garmentCategory}</p>
                </div>
              </div>

              <div>
                <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">{t("orders.descriptionLabel")}</p>
                <p className="text-sm">{detail.order.description}</p>
              </div>
              {detail.order.instructions && (
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">{t("orders.instructionsLabel")}</p>
                  <p className="text-sm whitespace-pre-line">{detail.order.instructions}</p>
                </div>
              )}
              {detail.order.notes && (
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">{t("orders.notesLabel")}</p>
                  <p className="text-sm whitespace-pre-line text-muted-foreground">{detail.order.notes}</p>
                </div>
              )}

              <Separator />

              <div>
                <div className="flex items-center justify-between gap-2 mb-2">
                  <p className="text-sm font-semibold">{t("orders.productionTasks")}</p>
                  <span className="text-xs text-muted-foreground">
                    {t(
                      detail.order.taskCount > 1
                        ? "orders.summaryDonePlural"
                        : "orders.summaryDone",
                      {
                        done: detail.order.completedTaskCount,
                        total: detail.order.taskCount,
                        progress: detail.order.avgProgress,
                      }
                    )}
                  </span>
                </div>
                {detail.tasks.length === 0 ? (
                  <EmptyState
                    icon={ClipboardList}
                    title={t("orders.noTasksTitle")}
                    description={t("orders.noTasksDesc")}
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
                            {t.assignedStaffName ?? unassignedLabel}
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
                      {t("orders.breakIntoTasks")}
                    </Button>
                  )}
                  {canManage && (
                    <Button variant="outline" onClick={() => openEdit(detail.order)}>
                      <Pencil className="size-4 mr-1.5" />
                      {t("common.edit")}
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
            <DialogTitle>
              {editing
                ? t("orders.editTitle", { number: editing.orderNumber })
                : t("orders.register")}
            </DialogTitle>
            <DialogDescription>
              {editing ? t("orders.editDesc") : t("orders.registerDesc")}
            </DialogDescription>
          </DialogHeader>

          {!editing && (
            <>
              <div className="grid gap-1.5">
                <FormLabel>{t("orders.customerLabel")}</FormLabel>
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
                    <span className="text-sm font-medium">{t("orders.existingCustomer")}</span>
                  </label>
                  <label
                    htmlFor="cust-new"
                    className="flex flex-1 items-center gap-2 rounded-lg border p-3 cursor-pointer has-[button[data-state=checked]]:border-primary has-[button[data-state=checked]]:bg-primary/5"
                  >
                    <RadioGroupItem value="new" id="cust-new" />
                    <span className="text-sm font-medium">{t("orders.newCustomer")}</span>
                  </label>
                </RadioGroup>
              </div>
              {form.customerMode === "existing" ? (
                <div className="grid gap-1.5">
                  <FormLabel htmlFor="order-customer">{t("orders.selectCustomer")}</FormLabel>
                  <Select
                    value={form.customerId}
                    onValueChange={(v) => setForm({ ...form, customerId: v })}
                  >
                    <SelectTrigger id="order-customer">
                      <SelectValue
                        placeholder={customers.length ? t("orders.selectCustomerPlaceholder") : t("orders.loadingCustomers")}
                      />
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
                    {t("orders.newCustomerDetails")}
                  </p>
                  <div className="grid gap-1.5">
                    <FormLabel htmlFor="nc-name">{t("orders.ncName")}</FormLabel>
                    <Input
                      id="nc-name"
                      value={form.ncName}
                      onChange={(e) => setForm({ ...form, ncName: e.target.value })}
                      maxLength={120}
                    />
                  </div>
                  <div className="grid gap-1.5">
                    <FormLabel htmlFor="nc-phone">{t("orders.ncPhone")}</FormLabel>
                    <Input
                      id="nc-phone"
                      value={form.ncPhone}
                      onChange={(e) => setForm({ ...form, ncPhone: e.target.value })}
                      maxLength={40}
                    />
                  </div>
                  <div className="grid gap-1.5 sm:col-span-2">
                    <FormLabel htmlFor="nc-email">{t("orders.ncEmail")}</FormLabel>
                    <Input
                      id="nc-email"
                      type="email"
                      value={form.ncEmail}
                      onChange={(e) => setForm({ ...form, ncEmail: e.target.value })}
                    />
                  </div>
                  <div className="grid gap-1.5 sm:col-span-2">
                    <FormLabel htmlFor="nc-address">{t("orders.ncAddress")}</FormLabel>
                    <Input
                      id="nc-address"
                      value={form.ncAddress}
                      onChange={(e) => setForm({ ...form, ncAddress: e.target.value })}
                      maxLength={300}
                    />
                  </div>
                  <div className="grid gap-1.5 sm:col-span-2">
                    <FormLabel htmlFor="nc-notes">{t("orders.ncNotes")}</FormLabel>
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
              <FormLabel>{t("orders.customerLabel")}</FormLabel>
              <p className="text-sm rounded-lg border p-3 bg-muted/40">
                <span className="font-medium">{editing.customerName}</span>
                <span className="text-muted-foreground"> · {editing.customerPhone}</span>
              </p>
            </div>
          )}

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-number">{t("orders.fieldOrderNumber")}</FormLabel>
              <Input
                id="order-number"
                value={form.orderNumber}
                onChange={(e) => setForm({ ...form, orderNumber: e.target.value })}
                placeholder={t("orders.numberPlaceholder")}
                maxLength={50}
              />
            </div>
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-priority">{t("orders.fieldPriority")}</FormLabel>
              <Select value={form.priority} onValueChange={(v) => setForm({ ...form, priority: v })}>
                <SelectTrigger id="order-priority">
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
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-date">{t("orders.fieldOrderDate")}</FormLabel>
              <Input
                id="order-date"
                type="date"
                value={form.orderDate}
                onChange={(e) => setForm({ ...form, orderDate: e.target.value })}
              />
            </div>
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-delivery">{t("orders.fieldDelivery")}</FormLabel>
              <Input
                id="order-delivery"
                type="date"
                value={form.expectedDeliveryDate}
                onChange={(e) => setForm({ ...form, expectedDeliveryDate: e.target.value })}
              />
            </div>
            <div className="grid gap-1.5 sm:col-span-2">
              <FormLabel htmlFor="order-desc">{t("orders.fieldDescription")}</FormLabel>
              <Input
                id="order-desc"
                value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })}
                placeholder={t("orders.descPlaceholder")}
                maxLength={500}
              />
            </div>
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-category">{t("orders.fieldCategory")}</FormLabel>
              <Input
                id="order-category"
                value={form.garmentCategory}
                onChange={(e) => setForm({ ...form, garmentCategory: e.target.value })}
                placeholder={t("orders.categoryPlaceholder")}
                maxLength={80}
              />
            </div>
            <div className="grid gap-1.5">
              <FormLabel htmlFor="order-pieces">{t("orders.fieldPieces")}</FormLabel>
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
              <FormLabel htmlFor="order-instructions">{t("orders.fieldInstructions")}</FormLabel>
              <Textarea
                id="order-instructions"
                rows={3}
                value={form.instructions}
                onChange={(e) => setForm({ ...form, instructions: e.target.value })}
                placeholder={t("orders.instructionsPlaceholder")}
              />
            </div>
            <div className="grid gap-1.5 sm:col-span-2">
              <FormLabel htmlFor="order-notes">{t("orders.fieldNotes")}</FormLabel>
              <Textarea
                id="order-notes"
                rows={2}
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
                placeholder={t("orders.notesPlaceholder")}
              />
            </div>
            {formError && (
              <p className="sm:col-span-2 text-sm font-medium text-red-700">{formError}</p>
            )}
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setFormOpen(false)} disabled={submitting}>
              {t("common.cancel")}
            </Button>
            <Button onClick={submit} disabled={submitting}>
              {submitting && (
                <span className="size-4 mr-1.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
              )}
              {editing ? t("common.saveChanges") : t("orders.register")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
