"use client";

// OSSZ ATELIER — user accounts & roles: create accounts, edit profiles,
// reset passwords and activate/deactivate. Admins manage staff accounts only;
// Management manages every account (incl. the last-manager safeguard).

import { useCallback, useEffect, useState } from "react";
import { api, ApiClientError } from "../client";
import { can, useAppStore } from "../store";
import type { UserRecord } from "../types";
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

const ROLE_HINTS: Record<string, string> = {
  MANAGEMENT: "Full access, final authority on incentives and critical decisions.",
  ADMIN: "Operational management of customers, orders, production and staff accounts.",
  STAFF: "Sees and updates only the tasks assigned to their own account.",
};

export function UsersView() {
  const user = useAppStore((s) => s.user);
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
      setError(e instanceof Error ? e.message : "Failed to load users");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  function errMessage(e: unknown): string {
    if (e instanceof ApiClientError) return e.details?.issues?.[0]?.message ?? e.message;
    return "Something went wrong. Please try again.";
  }

  function openCreate() {
    setCreateForm({ name: "", email: "", password: "", role: "STAFF", phone: "", jobTitle: "" });
    setShowCreatePw(false);
    setFormError(null);
    setCreateOpen(true);
  }

  async function submitCreate() {
    if (!createForm.name.trim() || !createForm.email.trim()) {
      setFormError("Name and email are required.");
      return;
    }
    if (createForm.password.length < 8) {
      setFormError("Password must be at least 8 characters.");
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
        title: "Account created",
        description: `${res.user.name} (${res.user.role.toLowerCase()}) can now sign in.`,
      });
      setCreateOpen(false);
      await load();
    } catch (e) {
      const message = errMessage(e);
      setFormError(message);
      toast({ title: "Could not create account", description: message, variant: "destructive" });
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
        title: "Could not update account",
        description: "Name must be at least 2 characters.",
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
      toast({ title: "Account updated", description: `${editForm.name} was saved.` });
      setEditTarget(null);
      await load();
    } catch (e) {
      toast({ title: "Could not update account", description: errMessage(e), variant: "destructive" });
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
      toast({ title: "Password reset", description: `${pwTarget.name} can sign in with the new password.` });
      setPwTarget(null);
      setNewPassword("");
      setShowNewPw(false);
    } catch (e) {
      toast({ title: "Could not reset password", description: errMessage(e), variant: "destructive" });
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
        title: next ? "Account reactivated" : "Account deactivated",
        description: next
          ? `${statusTarget.name} can sign in again.`
          : `${statusTarget.name} can no longer sign in.`,
      });
      setStatusTarget(null);
      await load();
    } catch (e) {
      toast({ title: "Could not update account", description: errMessage(e), variant: "destructive" });
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
        title="Users & Roles"
        description="Accounts with access to the atelier management system."
      >
        {canManageStaff && (
          <Button onClick={openCreate}>
            <UserPlus className="size-4 mr-1.5" />
            Create Account
          </Button>
        )}
      </PageHeader>

      {error ? (
        <EmptyState
          icon={AlertTriangle}
          title="Could not load users"
          description={error}
          action={
            <Button variant="outline" size="sm" onClick={load}>
              Try again
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
          <EmptyState icon={Users} title="No accounts found" />
        </div>
      ) : (
        <div className="rounded-lg border overflow-x-auto">
          <Table className="min-w-[900px]">
            <TableHeader>
              <TableRow>
                <TableHead>User</TableHead>
                <TableHead>Role</TableHead>
                <TableHead>Job title</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-center">Active tasks</TableHead>
                <TableHead>Created</TableHead>
                {canManageStaff && <TableHead className="w-12 text-right sr-only">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.map((u) => (
                <TableRow key={u.id}>
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
                        Active
                      </Badge>
                    ) : (
                      <Badge variant="outline" className="bg-stone-100 text-stone-500 border-stone-300 whitespace-nowrap">
                        Inactive
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
                            <Button variant="ghost" size="icon" className="size-8" aria-label={`Actions for ${u.name}`}>
                              <MoreHorizontal className="size-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end" className="w-48">
                            <DropdownMenuLabel>{u.name}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => openEdit(u)}>
                              <Pencil className="size-4 mr-2" />
                              Edit details
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { setPwTarget(u); setNewPassword(""); setShowNewPw(false); }}>
                              <KeyRound className="size-4 mr-2" />
                              Reset password
                            </DropdownMenuItem>
                            {u.id !== user?.id && (
                              <DropdownMenuItem
                                onClick={() => setStatusTarget(u)}
                                className={!u.isActive ? "" : "text-red-700 focus:text-red-700 focus:bg-red-50"}
                              >
                                <Power className="size-4 mr-2" />
                                {u.isActive ? "Deactivate" : "Reactivate"}
                              </DropdownMenuItem>
                            )}
                          </DropdownMenuContent>
                        </DropdownMenu>
                      ) : (
                        <span className="text-xs text-muted-foreground">Admin access: staff only</span>
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
            <DialogTitle>Create Account</DialogTitle>
            <DialogDescription>
              New accounts can sign in immediately with the password you set.
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-1.5">
              <Label htmlFor="new-name">Full name *</Label>
              <Input
                id="new-name"
                value={createForm.name}
                onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                placeholder="e.g. Marie Ngo"
                maxLength={120}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-email">Email *</Label>
              <Input
                id="new-email"
                type="email"
                value={createForm.email}
                onChange={(e) => setCreateForm({ ...createForm, email: e.target.value })}
                placeholder="name@atelier.com"
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-password">Password *</Label>
              <div className="relative">
                <Input
                  id="new-password"
                  type={showCreatePw ? "text" : "password"}
                  value={createForm.password}
                  onChange={(e) => setCreateForm({ ...createForm, password: e.target.value })}
                  placeholder="At least 8 characters"
                  className="pr-10"
                  autoComplete="new-password"
                />
                <button
                  type="button"
                  onClick={() => setShowCreatePw(!showCreatePw)}
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  aria-label={showCreatePw ? "Hide password" : "Show password"}
                >
                  {showCreatePw ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                </button>
              </div>
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-role">Role *</Label>
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
                      {r === "MANAGEMENT" ? "Management" : r === "ADMIN" ? "Admin" : "Staff"}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-phone">Phone</Label>
              <Input
                id="new-phone"
                value={createForm.phone}
                onChange={(e) => setCreateForm({ ...createForm, phone: e.target.value })}
                maxLength={40}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="new-job">Job title</Label>
              <Input
                id="new-job"
                value={createForm.jobTitle}
                onChange={(e) => setCreateForm({ ...createForm, jobTitle: e.target.value })}
                placeholder="e.g. Tailor, Cutter"
                maxLength={120}
              />
            </div>
            <div className="sm:col-span-2 rounded-lg border bg-muted/40 p-3 flex gap-2">
              <ShieldCheck className="size-4 shrink-0 mt-0.5 text-primary" />
              <p className="text-xs text-muted-foreground">
                {ROLE_HINTS[createForm.role] ?? ""}
                {createForm.role === "STAFF" && " Staff accounts can be assigned production tasks."}
              </p>
            </div>
            {formError && (
              <p className="sm:col-span-2 text-sm font-medium text-red-700">{formError}</p>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateOpen(false)} disabled={submitting}>
              Cancel
            </Button>
            <Button onClick={submitCreate} disabled={submitting}>
              {submitting && (
                <span className="size-4 mr-1.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
              )}
              Create Account
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ------------------------------------------------------- edit dialog */}
      <Dialog open={!!editTarget} onOpenChange={(open) => !open && setEditTarget(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Edit Account</DialogTitle>
            <DialogDescription>{editTarget?.email}</DialogDescription>
          </DialogHeader>
          <div className="grid gap-4">
            <div className="grid gap-1.5">
              <Label htmlFor="edit-name">Full name *</Label>
              <Input
                id="edit-name"
                value={editForm.name}
                onChange={(e) => setEditForm({ ...editForm, name: e.target.value })}
                maxLength={120}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="edit-phone">Phone</Label>
              <Input
                id="edit-phone"
                value={editForm.phone}
                onChange={(e) => setEditForm({ ...editForm, phone: e.target.value })}
                maxLength={40}
              />
            </div>
            <div className="grid gap-1.5">
              <Label htmlFor="edit-job">Job title</Label>
              <Input
                id="edit-job"
                value={editForm.jobTitle}
                onChange={(e) => setEditForm({ ...editForm, jobTitle: e.target.value })}
                maxLength={120}
              />
            </div>
            {isManagement && (
              <div className="grid gap-1.5">
                <Label htmlFor="edit-role">Role</Label>
                <Select value={editForm.role} onValueChange={(v) => setEditForm({ ...editForm, role: v })}>
                  <SelectTrigger id="edit-role">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {ROLES.map((r) => (
                      <SelectItem key={r} value={r}>
                        {r === "MANAGEMENT" ? "Management" : r === "ADMIN" ? "Admin" : "Staff"}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditTarget(null)} disabled={submitting}>
              Cancel
            </Button>
            <Button onClick={submitEdit} disabled={submitting}>
              {submitting && (
                <span className="size-4 mr-1.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
              )}
              Save Changes
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ----------------------------------------------- reset password alert */}
      <AlertDialog open={!!pwTarget} onOpenChange={(open) => !open && setPwTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Reset password</AlertDialogTitle>
            <AlertDialogDescription>
              Set a new password for {pwTarget?.name} ({pwTarget?.email}). They will use it at the
              next sign-in.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="grid gap-1.5">
            <Label htmlFor="reset-password">New password *</Label>
            <div className="relative">
              <Input
                id="reset-password"
                type={showNewPw ? "text" : "password"}
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                placeholder="At least 8 characters"
                className="pr-10"
                autoComplete="new-password"
              />
              <button
                type="button"
                onClick={() => setShowNewPw(!showNewPw)}
                className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                aria-label={showNewPw ? "Hide password" : "Show password"}
              >
                {showNewPw ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
              </button>
            </div>
            {newPassword.length > 0 && newPassword.length < 8 && (
              <p className="text-xs text-red-700">Password must be at least 8 characters.</p>
            )}
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={submitting}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              disabled={newPassword.length < 8 || submitting}
              onClick={(e) => {
                e.preventDefault();
                submitPasswordReset();
              }}
              className="bg-primary text-primary-foreground hover:bg-primary/90"
            >
              Reset Password
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* ------------------------------------- deactivate / reactivate alert */}
      <AlertDialog open={!!statusTarget} onOpenChange={(open) => !open && setStatusTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {statusTarget?.isActive ? "Deactivate account" : "Reactivate account"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {statusTarget?.isActive
                ? `${statusTarget?.name} will no longer be able to sign in. Task history and audit records are kept.`
                : `${statusTarget?.name} will be able to sign in again with their existing password.`}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={submitting}>Cancel</AlertDialogCancel>
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
              {statusTarget?.isActive ? "Deactivate" : "Reactivate"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
