"use client";

// OSSZ ATELIER — user accounts & roles: create accounts, edit profiles,
// reset passwords and activate/deactivate. Admins manage staff accounts only;
// Management manages every account (incl. the last-manager safeguard).

import { useCallback, useEffect, useState } from "react";
import { api, ApiClientError } from "../client";
import { can, useAppStore } from "../store";
import type { UserRecord } from "../types";
import { useI18n, type TranslateFn } from "../i18n/use-i18n";
import { roleLabel } from "../i18n/core";
import { EmptyState, PageHeader, RoleBadge } from "../ui-bits";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { ROLES } from "@/lib/permissions";
import { formatDate } from "@/lib/format";
import {
  AlertTriangle,
  Eye,
  EyeOff,
  KeyRound,
  MoreHorizontal,
  Pencil,
  Power,
  ShieldCheck,
  UserPlus,
  Users,
} from "lucide-react";

type CreateForm = {
  name: string;
  email: string;
  password: string;
  role: string;
  phone: string;
  jobTitle: string;
};

type EditForm = {
  name: string;
  phone: string;
  jobTitle: string;
  role: string;
};

/** Per-role permission hint shown in the create dialog (bilingual). */
function roleHint(role: string, t: TranslateFn): string {
  switch (role) {
    case "MANAGEMENT":
      return t("users.roleHint.management");
    case "ADMIN":
      return t("users.roleHint.admin");
    case "STAFF":
      return t("users.roleHint.staff");
    default:
      return "";
  }
}

export function UsersView() {
  const user = useAppStore((s) => s.user);
  const { t, lang } = useI18n();
  const { toast } = useToast();
  const canManageStaff = can(user, "users.manage.staff") || can(user, "users.manage.all");
  const isManagement = user?.role === "MANAGEMENT";

  const [users, setUsers] = useState<UserRecord[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [createOpen, setCreateOpen] = useState(false);
  const [createForm, setCreateForm] = useState<CreateForm>({
    name: "",
    email: "",
    password: "",
    role: "STAFF",
    phone: "",
    jobTitle: "",
  });
  const [showCreatePw, setShowCreatePw] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const [editTarget, setEditTarget] = useState<UserRecord | null>(null);
  const [editForm, setEditForm] = useState<EditForm>({ name: "", phone: "", jobTitle: "", role: "STAFF" });

  const [pwTarget, setPwTarget] = useState<UserRecord | null>(null);
  const [newPassword, setNewPassword] = useState("");
  const [showNewPw, setShowNewPw] = useState(false);

  const [statusTarget, setStatusTarget] = useState<UserRecord | null>(null);

  const load = useCallback(async () => {
    try {
      const res = await api.get<{ users: UserRecord[] }>("/api/atelier/users");
      setUsers(res.users);
      setError(null);
    } catch (e) {
      // Empty string marks a generic failure; the translated message is
      // rendered at display time so it follows the active language.
      setError(e instanceof Error ? e.message : "");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  function errMessage(e: unknown): string {
    if (e instanceof ApiClientError) return e.details?.issues?.[0]?.message ?? e.message;
    return t("common.somethingWrong");
  }

  function openCreate() {
    setCreateForm({ name: "", email: "", password: "", role: "STAFF", phone: "", jobTitle: "" });
    setShowCreatePw(false);
    setFormError(null);
    setCreateOpen(true);
  }

  async function submitCreate() {
    if (!createForm.name.trim() || !createForm.email.trim()) {
      setFormError(t("users.nameEmailRequired"));
      return;
    }
    if (createForm.password.length < 8) {
      setFormError(t("users.passwordTooShort"));
      return;
    }
    setSubmitting(true);
    setFormError(null);
    try {
      const res = await api.post<{ user: UserRecord }>("/api/atelier/users", {
        name: createForm.name,
        email: createForm.email,
        password: createForm.password,
        role: createForm.role,
        ...(createForm.phone ? { phone: createForm.phone } : {}),
        ...(createForm.jobTitle ? { jobTitle: createForm.jobTitle } : {}),
      });
      toast({
        title: t("users.createdToastTitle"),
        description: t("users.createdToastDesc", {
          name: res.user.name,
          role: roleLabel(res.user.role, lang).toLowerCase(),
        }),
      });
      setCreateOpen(false);
      await load();
    } catch (e) {
      const message = errMessage(e);
      setFormError(message);
      toast({ title: t("users.createFailedToast"), description: message, variant: "destructive" });
    } finally {
      setSubmitting(false);
    }
  }

  function openEdit(u: UserRecord) {
    setEditTarget(u);
    setEditForm({
      name: u.name,
      phone: u.phone ?? "",
      jobTitle: u.jobTitle ?? "",
      role: u.role,
    });
  }

  async function submitEdit() {
    if (!editTarget) return;
    if (!editForm.name.trim() || editForm.name.trim().length < 2) {
      toast({
        title: t("users.updateFailedToast"),
        description: t("users.nameTooShort"),
        variant: "destructive",
      });
      return;
    }
    setSubmitting(true);
    try {
      await api.patch(`/api/atelier/users/${editTarget.id}`, {
        name: editForm.name,
        phone: editForm.phone,
        jobTitle: editForm.jobTitle,
        ...(isManagement ? { role: editForm.role } : {}),
      });
      toast({ title: t("users.updatedToastTitle"), description: t("users.savedToast", { name: editForm.name }) });
      setEditTarget(null);
      await load();
    } catch (e) {
      toast({ title: t("users.updateFailedToast"), description: errMessage(e), variant: "destructive" });
    } finally {
      setSubmitting(false);
    }
  }

  async function submitPasswordReset() {
    if (!pwTarget) return;
    if (newPassword.length < 8) return;
    setSubmitting(true);
    try {
      await api.patch(`/api/atelier/users/${pwTarget.id}`, { password: newPassword });
      toast({ title: t("users.resetToastTitle"), description: t("users.resetToastDesc", { name: pwTarget.name }) });
      setPwTarget(null);
      setNewPassword("");
      setShowNewPw(false);
    } catch (e) {
      toast({ title: t("users.resetFailedToast"), description: errMessage(e), variant: "destructive" });
    } finally {
      setSubmitting(false);
    }
  }

  async function submitStatusChange() {
    if (!statusTarget) return;
    const next = !statusTarget.isActive;
    setSubmitting(true);
    try {
      await api.patch(`/api/atelier/users/${statusTarget.id}`, { isActive: next });
      toast({
        title: next ? t("users.reactivatedToastTitle") : t("users.deactivatedToastTitle"),
        description: next
          ? t("users.reactivatedToastDesc", { name: statusTarget.name })
          : t("users.deactivatedToastDesc", { name: statusTarget.name }),
      });
      setStatusTarget(null);
      await load();
    } catch (e) {
      toast({ title: t("users.updateFailedToast"), description: errMessage(e), variant: "destructive" });
    } finally {
      setSubmitting(false);
    }
  }

  function canActOn(u: UserRecord): boolean {
    return isManagement || u.role === "STAFF";
  }

  return (
    <div>
      <PageHeader
        title={t("users.title")}
        description={t("users.description")}
      >
        {canManageStaff && (
          <Button onClick={openCreate}>
            <UserPlus className="size-4 mr-1.5" />
            {t("users.createAccount")}
          </Button>
        )}
      </PageHeader>

      {error !== null ? (
        <EmptyState
          icon={AlertTriangle}
          title={t("users.errorTitle")}
          description={error || t("users.loadFailed")}
          action={
            <Button variant="outline" size="sm" onClick={load}>
              {t("common.tryAgain")}
            </Button>
          }
        />
      ) : users === null ? (
        <div className="space-y-3">
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
        </div>
      ) : users.length === 0 ? (
        <div className="rounded-lg border">
          <EmptyState icon={Users} title={t("users.emptyTitle")} />
        </div>
      ) : (
        <div className="rounded-lg border overflow-x-auto">
          <Table className="min-w-[980px]">
            <TableHeader>
              <TableRow>
                <TableHead>{t("users.col.userId")}</TableHead>
                <TableHead>{t("users.col.user")}</TableHead>
                <TableHead>{t("users.col.role")}</TableHead>
                <TableHead>{t("users.col.jobTitle")}</TableHead>
                <TableHead>{t("users.col.phone")}</TableHead>
                <TableHead>{t("users.col.status")}</TableHead>
                <TableHead className="text-center">{t("users.col.activeTasks")}</TableHead>
                <TableHead>{t("users.col.created")}</TableHead>
                {canManageStaff && <TableHead className="w-12 text-right sr-only">{t("common.actions")}</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.map((u) => (
                <TableRow key={u.id}>
                  <TableCell>
                    <span className="inline-flex rounded-md border bg-muted/50 px-2 py-1 font-mono text-xs font-semibold tracking-wide text-foreground">
                      {u.code ?? "—"}
                    </span>
                  </TableCell>
                  <TableCell>
                    <p className="font-medium">{u.name}</p>
                    <p className="text-xs text-muted-foreground truncate">{u.email}</p>
                  </TableCell>
                  <TableCell>
                    <RoleBadge role={u.role} />
                  </TableCell>
                  <TableCell className="text-muted-foreground">{u.jobTitle ?? "—"}</TableCell>
                  <TableCell className="whitespace-nowrap text-muted-foreground">{u.phone ?? "—"}</TableCell>
                  <TableCell>
                    {u.isActive ? (
                      <Badge variant="outline" className="bg-emerald-50 text-emerald-800 border-emerald-300 whitespace-nowrap">
                        {t("users.active")}
                      </Badge>
                    ) : (
                      <Badge variant="outline" className="bg-stone-100 text-stone-500 border-stone-300 whitespace-nowrap">
                        {t("users.inactive")}
                      </Badge>
                    )}
                  </TableCell>
                  <TableCell className="text-center tabular-nums">
                    <span className="inline-flex rounded-full bg-primary/10 text-primary text-xs font-bold px-2 py-0.5">
                      {u.activeTaskCount ?? 0}
                    </span>
                  </TableCell>
                  <TableCell className="text-muted-foreground whitespace-nowrap">{formatDate(u.createdAt)}</TableCell>
                  {canManageStaff && (
                    <TableCell className="text-right">
                      {canActOn(u) ? (
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="size-8" aria-label={t("users.actionsFor", { name: u.name })}>
                              <MoreHorizontal className="size-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end" className="w-48">
                            <DropdownMenuLabel>{u.name}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => openEdit(u)}>
                              <Pencil className="size-4 mr-2" />
                              {t("users.editDetails")}
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { setPwTarget(u); setNewPassword(""); setShowNewPw(false); }}>
                              <KeyRound className="size-4 mr-2" />
                              {t("users.resetPassword")}
                            </DropdownMenuItem>
                            {u.id !== user?.id && (
                              <DropdownMenuItem
                                onClick={() => setStatusTarget(u)}
                                className={!u.isActive ? "" : "text-red-700 focus:text-red-700 focus:bg-red-50"}
                              >
                                <Power className="size-4 mr-2" />
                                {u.isActive ? t("users.deactivate") : t("users.reactivate")}
                              </DropdownMenuItem>
                            )}
                          </DropdownMenuContent>
                        </DropdownMenu>
                      ) : (
                        <span className="text-xs text-muted-foreground">{t("users.adminAccessNote")}</span>
                      )}
                    </TableCell>
                  )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      {/* ---------------------------------------------------- create account */}
      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="sm:max-w-lg max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{t("users.createAccount")}</DialogTitle>
            <DialogDescription>
              {t("users.createDescription")}
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-1.5">
              <Label htmlFor="new-name">{t("users.fullName")}</Label>
              <Input
                id="new-name"
                value={createForm.name}
                onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                placeholder={t("users.namePlaceholder")}
                maxLength={120}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-email">{t("users.email")}</Label>
              <Input
                id="new-email"
                type="email"
                value={createForm.email}
                onChange={(e) => setCreateForm({ ...createForm, email: e.target.value })}
                placeholder="name@atelier.com"
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-password">{t("users.password")}</Label>
              <div className="relative">
                <Input
                  id="new-password"
                  type={showCreatePw ? "text" : "password"}
                  value={createForm.password}
                  onChange={(e) => setCreateForm({ ...createForm, password: e.target.value })}
                  placeholder={t("users.passwordPlaceholder")}
                  className="pr-10"
                  autoComplete="new-password"
                />
                <button
                  type="button"
                  onClick={() => setShowCreatePw(!showCreatePw)}
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  aria-label={showCreatePw ? t("common.hidePassword") : t("common.showPassword")}
                >
                  {showCreatePw ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                </button>
              </div>
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-role">{t("users.roleRequired")}</Label>
              <Select
                value={createForm.role}
                onValueChange={(v) => setCreateForm({ ...createForm, role: v })}
              >
                <SelectTrigger id="new-role">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {(isManagement ? ROLES : (["STAFF"] as const)).map((r) => (
                    <SelectItem key={r} value={r}>
                      {roleLabel(r, lang)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-phone">{t("users.phoneLabel")}</Label>
              <Input
                id="new-phone"
                value={createForm.phone}
                onChange={(e) => setCreateForm({ ...createForm, phone: e.target.value })}
                maxLength={40}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-job">{t("users.jobTitleLabel")}</Label>
              <Input
                id="new-job"
                value={createForm.jobTitle}
                onChange={(e) => setCreateForm({ ...createForm, jobTitle: e.target.value })}
                placeholder={t("users.jobTitlePlaceholder")}
                maxLength={120}
              />
            </div>
            <div className="sm:col-span-2 rounded-lg border bg-muted/40 p-3 flex gap-2">
              <ShieldCheck className="size-4 shrink-0 mt-0.5 text-primary" />
              <p className="text-xs text-muted-foreground">
                {roleHint(createForm.role, t)}
                {createForm.role === "STAFF" && ` ${t("users.roleHint.staffExtra")}`}
              </p>
            </div>
            {formError && (
              <p className="sm:col-span-2 text-sm font-medium text-red-700">{formError}</p>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateOpen(false)} disabled={submitting}>
              {t("common.cancel")}
            </Button>
            <Button onClick={submitCreate} disabled={submitting}>
              {submitting && (
                <span className="size-4 mr-1.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
              )}
              {t("users.createAccount")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ------------------------------------------------------- edit dialog */}
      <Dialog open={!!editTarget} onOpenChange={(open) => !open && setEditTarget(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{t("users.editTitle")}</DialogTitle>
            <DialogDescription>{editTarget?.email}</DialogDescription>
          </DialogHeader>
          <div className="grid gap-4">
            <div className="grid gap-1.5">
              <Label htmlFor="edit-name">{t("users.fullName")}</Label>
              <Input
                id="edit-name"
                value={editForm.name}
                onChange={(e) => setEditForm({ ...editForm, name: e.target.value })}
                maxLength={120}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="edit-phone">{t("users.phoneLabel")}</Label>
              <Input
                id="edit-phone"
                value={editForm.phone}
                onChange={(e) => setEditForm({ ...editForm, phone: e.target.value })}
                maxLength={40}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="edit-job">{t("users.jobTitleLabel")}</Label>
              <Input
                id="edit-job"
                value={editForm.jobTitle}
                onChange={(e) => setEditForm({ ...editForm, jobTitle: e.target.value })}
                maxLength={120}
              />
            </div>
            {isManagement && (
              <div className="grid gap-1.5">
                <Label htmlFor="edit-role">{t("users.roleLabel")}</Label>
                <Select value={editForm.role} onValueChange={(v) => setEditForm({ ...editForm, role: v })}>
                  <SelectTrigger id="edit-role">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {ROLES.map((r) => (
                      <SelectItem key={r} value={r}>
                        {roleLabel(r, lang)}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditTarget(null)} disabled={submitting}>
              {t("common.cancel")}
            </Button>
            <Button onClick={submitEdit} disabled={submitting}>
              {submitting && (
                <span className="size-4 mr-1.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
              )}
              {t("common.saveChanges")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ----------------------------------------------- reset password alert */}
      <AlertDialog open={!!pwTarget} onOpenChange={(open) => !open && setPwTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{t("users.resetPassword")}</AlertDialogTitle>
            <AlertDialogDescription>
              {t("users.resetPwDescription", { name: pwTarget?.name ?? "", email: pwTarget?.email ?? "" })}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="grid gap-1.5">
            <Label htmlFor="reset-password">{t("users.newPassword")}</Label>
            <div className="relative">
              <Input
                id="reset-password"
                type={showNewPw ? "text" : "password"}
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                placeholder={t("users.passwordPlaceholder")}
                className="pr-10"
                autoComplete="new-password"
              />
              <button
                type="button"
                onClick={() => setShowNewPw(!showNewPw)}
                className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                aria-label={showNewPw ? t("common.hidePassword") : t("common.showPassword")}
              >
                {showNewPw ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
              </button>
            </div>
            {newPassword.length > 0 && newPassword.length < 8 && (
              <p className="text-xs text-red-700">{t("users.passwordTooShort")}</p>
            )}
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={submitting}>{t("common.cancel")}</AlertDialogCancel>
            <AlertDialogAction
              disabled={newPassword.length < 8 || submitting}
              onClick={(e) => {
                e.preventDefault();
                submitPasswordReset();
              }}
              className="bg-primary text-primary-foreground hover:bg-primary/90"
            >
              {t("users.resetPasswordAction")}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* ------------------------------------- deactivate / reactivate alert */}
      <AlertDialog open={!!statusTarget} onOpenChange={(open) => !open && setStatusTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {statusTarget?.isActive ? t("users.deactivateTitle") : t("users.reactivateTitle")}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {statusTarget?.isActive
                ? t("users.deactivateDesc", { name: statusTarget?.name ?? "" })
                : t("users.reactivateDesc", { name: statusTarget?.name ?? "" })}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={submitting}>{t("common.cancel")}</AlertDialogCancel>
            <AlertDialogAction
              disabled={submitting}
              onClick={(e) => {
                e.preventDefault();
                submitStatusChange();
              }}
              className={
                statusTarget?.isActive
                  ? "bg-red-600 text-white hover:bg-red-700"
                  : "bg-primary text-primary-foreground hover:bg-primary/90"
              }
            >
              {statusTarget?.isActive ? t("users.deactivate") : t("users.reactivate")}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
