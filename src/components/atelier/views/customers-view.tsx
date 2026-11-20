"use client";

// OSSZ ATELIER — customer directory: search, register and edit customers.

import { useCallback, useEffect, useState } from "react";
import { api, ApiClientError } from "../client";
import { can, useAppStore } from "../store";
import { useI18n } from "../i18n/use-i18n";
import type { Customer } from "../types";
import { EmptyState, PageHeader } from "../ui-bits";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { AlertTriangle, Pencil, Plus, Search, Users } from "lucide-react";
import { formatDate } from "@/lib/format";

type CustomerForm = {
  name: string;
  phone: string;
  email: string;
  address: string;
  notes: string;
};

const EMPTY_FORM: CustomerForm = { name: "", phone: "", email: "", address: "", notes: "" };

function useDebounced<T>(value: T, ms = 300) {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), ms);
    return () => clearTimeout(t);
  }, [value, ms]);
  return debounced;
}

export function CustomersView() {
  const user = useAppStore((s) => s.user);
  const { toast } = useToast();
  const { t } = useI18n();
  const canManage = can(user, "customers.manage");

  const [customers, setCustomers] = useState<Customer[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebounced(search);

  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Customer | null>(null);
  const [form, setForm] = useState<CustomerForm>(EMPTY_FORM);
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get<{ customers: Customer[] }>(
        `/api/atelier/customers${debouncedSearch ? `?search=${encodeURIComponent(debouncedSearch)}` : ""}`
      );
      setCustomers(res.customers ?? []);
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : t("cust.loadFail"));
    }
  }, [debouncedSearch, t]);

  useEffect(() => {
    load();
  }, [load]);

  function openCreate() {
    setEditing(null);
    setForm(EMPTY_FORM);
    setFormError(null);
    setDialogOpen(true);
  }

  function openEdit(c: Customer) {
    setEditing(c);
    setForm({
      name: c.name,
      phone: c.phone,
      email: c.email ?? "",
      address: c.address ?? "",
      notes: c.notes ?? "",
    });
    setFormError(null);
    setDialogOpen(true);
  }

  async function submit() {
    if (!form.name.trim() || !form.phone.trim()) {
      setFormError(t("cust.namePhoneRequired"));
      return;
    }
    setSubmitting(true);
    setFormError(null);
    try {
      if (editing) {
        await api.patch(`/api/atelier/customers/${editing.id}`, {
          name: form.name,
          phone: form.phone,
          email: form.email,
          address: form.address,
          notes: form.notes,
        });
        toast({
          title: t("cust.updatedToast"),
          description: t("cust.updatedToastDesc", { name: form.name }),
        });
      } else {
        const res = await api.post<{ customer: Customer }>("/api/atelier/customers", {
          name: form.name,
          phone: form.phone,
          ...(form.email ? { email: form.email } : {}),
          ...(form.address ? { address: form.address } : {}),
          ...(form.notes ? { notes: form.notes } : {}),
        });
        toast({
          title: t("cust.registeredToast"),
          description: t("cust.registeredToastDesc", {
            name: res.customer.name,
            code: res.customer.code,
          }),
        });
      }
      setDialogOpen(false);
      await load();
    } catch (e) {
      const message =
        e instanceof ApiClientError
          ? e.details?.issues?.[0]?.message ?? e.message
          : t("common.somethingWrong");
      setFormError(message);
      toast({
        title: editing ? t("cust.updateFailed") : t("cust.registerFailed"),
        description: message,
        variant: "destructive",
      });
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div>
      <PageHeader
        title={t("cust.title")}
        description={t("cust.subtitle")}
      >
        {canManage && (
          <Button onClick={openCreate}>
            <Plus className="size-4 mr-1.5" />
            {t("cust.register")}
          </Button>
        )}
      </PageHeader>

      <div className="mb-4">
        <div className="relative max-w-sm">
          <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={t("cust.searchPlaceholder")}
            className="pl-8"
            aria-label={t("cust.searchLabel")}
          />
        </div>
      </div>

      {error ? (
        <EmptyState
          icon={AlertTriangle}
          title={t("cust.loadFailedTitle")}
          description={error}
          action={
            <Button variant="outline" size="sm" onClick={load}>
              {t("common.tryAgain")}
            </Button>
          }
        />
      ) : customers === null ? (
        <div className="space-y-3">
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
        </div>
      ) : customers.length === 0 ? (
        <div className="rounded-lg border">
          <EmptyState
            icon={Users}
            title={search ? t("cust.emptySearchTitle") : t("cust.emptyTitle")}
            description={
              search
                ? t("cust.emptySearchDesc")
                : t("cust.emptyDesc")
            }
            action={
              canManage && !search ? (
                <Button size="sm" onClick={openCreate}>
                  <Plus className="size-4 mr-1.5" />
                  {t("cust.register")}
                </Button>
              ) : undefined
            }
          />
        </div>
      ) : (
        <div className="rounded-lg border overflow-x-auto">
          <Table className="min-w-[820px]">
            <TableHeader>
              <TableRow>
                <TableHead>{t("cust.colCode")}</TableHead>
                <TableHead>{t("cust.colName")}</TableHead>
                <TableHead>{t("cust.colPhone")}</TableHead>
                <TableHead>{t("cust.colEmail")}</TableHead>
                <TableHead>{t("cust.colAddress")}</TableHead>
                <TableHead className="text-center">{t("cust.colOrders")}</TableHead>
                <TableHead>{t("cust.colRegistered")}</TableHead>
                {canManage && (
                  <TableHead className="w-12 text-right sr-only">{t("common.actions")}</TableHead>
                )}
              </TableRow>
            </TableHeader>
            <TableBody>
              {customers.map((c) => (
                <TableRow key={c.id}>
                  <TableCell className="font-mono text-xs text-muted-foreground whitespace-nowrap">
                    {c.code}
                  </TableCell>
                  <TableCell className="font-medium">{c.name}</TableCell>
                  <TableCell className="whitespace-nowrap">{c.phone}</TableCell>
                  <TableCell className="max-w-48 truncate">{c.email ?? "—"}</TableCell>
                  <TableCell className="max-w-56 truncate text-muted-foreground">
                    {c.address ?? "—"}
                  </TableCell>
                  <TableCell className="text-center tabular-nums">
                    <span className="inline-flex rounded-full bg-primary/10 text-primary text-xs font-bold px-2 py-0.5">
                      {c.orderCount ?? 0}
                    </span>
                  </TableCell>
                  <TableCell className="text-muted-foreground whitespace-nowrap">
                    {formatDate(c.createdAt)}
                  </TableCell>
                  {canManage && (
                    <TableCell className="text-right">
                      <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        onClick={() => openEdit(c)}
                        aria-label={t("cust.editAria", { name: c.name })}
                      >
                        <Pencil className="size-4" />
                      </Button>
                    </TableCell>
                  )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="sm:max-w-lg max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editing ? t("cust.editTitle") : t("cust.register")}</DialogTitle>
            <DialogDescription>
              {editing
                ? t("cust.editDesc", { name: editing.name, code: editing.code })
                : t("cust.registerDesc")}
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-1.5">
              <Label htmlFor="cust-name">{t("cust.fieldName")}</Label>
              <Input
                id="cust-name"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                placeholder={t("cust.namePlaceholder")}
                maxLength={120}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="cust-phone">{t("cust.fieldPhone")}</Label>
              <Input
                id="cust-phone"
                value={form.phone}
                onChange={(e) => setForm({ ...form, phone: e.target.value })}
                placeholder="+237 6XX XX XX XX"
                maxLength={40}
              />
            </div>
            <div className="grid gap-1.5 sm:col-span-2">
              <Label htmlFor="cust-email">{t("cust.fieldEmail")}</Label>
              <Input
                id="cust-email"
                type="email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                placeholder="customer@example.com"
              />
            </div>
            <div className="grid gap-1.5 sm:col-span-2">
              <Label htmlFor="cust-address">{t("cust.fieldAddress")}</Label>
              <Input
                id="cust-address"
                value={form.address}
                onChange={(e) => setForm({ ...form, address: e.target.value })}
                placeholder={t("cust.addressPlaceholder")}
                maxLength={300}
              />
            </div>
            <div className="grid gap-1.5 sm:col-span-2">
              <Label htmlFor="cust-notes">{t("cust.fieldNotes")}</Label>
              <Textarea
                id="cust-notes"
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
                placeholder={t("cust.notesPlaceholder")}
                rows={3}
              />
            </div>
            {formError && (
              <p className="sm:col-span-2 text-sm font-medium text-red-700">{formError}</p>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)} disabled={submitting}>
              {t("common.cancel")}
            </Button>
            <Button onClick={submit} disabled={submitting}>
              {submitting && <span className="size-4 mr-1.5 animate-spin rounded-full border-2 border-current border-t-transparent" />}
              {editing ? t("common.saveChanges") : t("cust.register")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
