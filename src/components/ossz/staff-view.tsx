"use client";

import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Plus, UserPlus, Loader2, ShieldAlert } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { api } from "./api-client";
import { can, useAppStore } from "./store";
import { formatDate, initials } from "@/lib/format";
import { EmptyState } from "./empty-state";
import type { Role, StaffUser } from "./types";
import { ROLE_LABELS } from "@/lib/permissions";

const ROLE_BADGE_STYLES: Record<Role, string> = {
  ADMIN: "bg-primary/10 text-primary border-primary/30",
  MANAGER: "bg-amber-600/10 text-amber-700 border-amber-600/30",
  INSPECTOR: "bg-teal-600/10 text-teal-700 border-teal-600/30",
  TAILOR: "bg-stone-500/10 text-stone-600 border-stone-500/25",
};

export function StaffView() {
  const user = useAppStore((s) => s.user)!;
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const canManage = can(user, "staff.manage");

  const [createOpen, setCreateOpen] = useState(false);
  const [form, setForm] = useState({ name: "", email: "", password: "", role: "TAILOR" });

  const staffQuery = useQuery({
    queryKey: ["staff"],
    queryFn: () => api.get<{ users: StaffUser[]; viewerId: string }>("/api/users"),
  });

  const createMutation = useMutation({
    mutationFn: (payload: unknown) => api.post("/api/users", payload),
    onSuccess: () => {
      toast({ title: "Staff account created" });
      queryClient.invalidateQueries({ queryKey: ["staff"] });
      setCreateOpen(false);
      setForm({ name: "", email: "", password: "", role: "TAILOR" });
    },
    onError: (err: Error) => {
      toast({ title: "Could not create account", description: err.message, variant: "destructive" });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: unknown }) => api.patch(`/api/users/${id}`, payload),
    onSuccess: () => {
      toast({ title: "Account updated" });
      queryClient.invalidateQueries({ queryKey: ["staff"] });
    },
    onError: (err: Error) => {
      toast({ title: "Could not update account", description: err.message, variant: "destructive" });
    },
  });

  const users = staffQuery.data?.users ?? [];
  const viewerId = staffQuery.data?.viewerId;

  return (
    <div className="space-y-4">
      {canManage && (
        <div className="flex justify-end">
          <Button onClick={() => setCreateOpen(true)}>
            <UserPlus className="size-4" /> New Staff Account
          </Button>
        </div>
      )}

      <Card>
        <CardContent className="p-0">
          {staffQuery.isLoading ? (
            <div className="p-6 space-y-3">
              {Array.from({ length: 5 }).map((_, i) => (
                <Skeleton key={i} className="h-10" />
              ))}
            </div>
          ) : users.length === 0 ? (
            <EmptyState title="No staff accounts" />
          ) : (
            <>
              {/* Desktop table */}
              <div className="hidden md:block">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Name</TableHead>
                      <TableHead>Email</TableHead>
                      <TableHead>Role</TableHead>
                      <TableHead>Active Tasks</TableHead>
                      <TableHead>Member Since</TableHead>
                      <TableHead>Status</TableHead>
                      {canManage && <TableHead className="text-right">Actions</TableHead>}
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {users.map((u) => (
                      <TableRow key={u.id}>
                        <TableCell>
                          <div className="flex items-center gap-2.5">
                            <Avatar className="size-8">
                              <AvatarFallback className="bg-primary/10 text-primary text-xs font-semibold">
                                {initials(u.name)}
                              </AvatarFallback>
                            </Avatar>
                            <span className="font-medium">{u.name}</span>
                          </div>
                        </TableCell>
                        <TableCell className="text-muted-foreground">{u.email}</TableCell>
                        <TableCell>
                          <Badge variant="outline" className={ROLE_BADGE_STYLES[u.role]}>
                            {u.roleLabel}
                          </Badge>
                        </TableCell>
                        <TableCell className="tabular-nums">{u.activeTaskCount}</TableCell>
                        <TableCell className="text-muted-foreground text-sm">{formatDate(u.createdAt)}</TableCell>
                        <TableCell>
                          <Badge variant={u.isActive ? "secondary" : "outline"} className={u.isActive ? "bg-green-600/10 text-green-700" : "text-stone-500"}>
                            {u.isActive ? "Active" : "Inactive"}
                          </Badge>
                        </TableCell>
                        {canManage && (
                          <TableCell className="text-right">
                            <div className="flex items-center justify-end gap-2">
                              <Select
                                value={u.role}
                                onValueChange={(role) => updateMutation.mutate({ id: u.id, payload: { role } })}
                              >
                                <SelectTrigger className="h-7 w-36 text-xs">
                                  <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                  {(Object.keys(ROLE_LABELS) as Role[]).map((r) => (
                                    <SelectItem key={r} value={r}>
                                      {ROLE_LABELS[r]}
                                    </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                              {u.id === viewerId ? (
                                <span className="text-xs text-muted-foreground flex items-center gap-1">
                                  <ShieldAlert className="size-3.5" /> You
                                </span>
                              ) : (
                                <Switch
                                  checked={u.isActive}
                                  onCheckedChange={(isActive) => updateMutation.mutate({ id: u.id, payload: { isActive } })}
                                  aria-label="Toggle active"
                                />
                              )}
                            </div>
                          </TableCell>
                        )}
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>

              {/* Mobile cards */}
              <div className="md:hidden divide-y">
                {users.map((u) => (
                  <div key={u.id} className="p-4 space-y-2">
                    <div className="flex items-center gap-2.5">
                      <Avatar className="size-9">
                        <AvatarFallback className="bg-primary/10 text-primary text-xs font-semibold">
                          {initials(u.name)}
                        </AvatarFallback>
                      </Avatar>
                      <div className="min-w-0 flex-1">
                        <p className="font-semibold truncate">{u.name}</p>
                        <p className="text-xs text-muted-foreground truncate">{u.email}</p>
                      </div>
                      <Badge variant="outline" className={ROLE_BADGE_STYLES[u.role]}>
                        {u.roleLabel}
                      </Badge>
                    </div>
                    <div className="flex items-center justify-between">
                      <Badge variant={u.isActive ? "secondary" : "outline"} className={u.isActive ? "bg-green-600/10 text-green-700" : "text-stone-500"}>
                        {u.isActive ? "Active" : "Inactive"}
                      </Badge>
                      {canManage && u.id !== viewerId && (
                        <Switch
                          checked={u.isActive}
                          onCheckedChange={(isActive) => updateMutation.mutate({ id: u.id, payload: { isActive } })}
                          aria-label="Toggle active"
                        />
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </CardContent>
      </Card>

      {/* Create dialog */}
      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="font-display">New Staff Account</DialogTitle>
            <DialogDescription>
              The staff member will use these credentials to sign in to the OSSZ backoffice.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label>Full name</Label>
              <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} placeholder="e.g. Amina Sali" />
            </div>
            <div className="space-y-1.5">
              <Label>Email</Label>
              <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} placeholder="name@ossz.com" />
            </div>
            <div className="space-y-1.5">
              <Label>Temporary password</Label>
              <Input type="text" value={form.password} onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))} placeholder="Minimum 8 characters" />
            </div>
            <div className="space-y-1.5">
              <Label>Role</Label>
              <Select value={form.role} onValueChange={(role) => setForm((f) => ({ ...f, role }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {(Object.keys(ROLE_LABELS) as Role[]).map((r) => (
                    <SelectItem key={r} value={r}>
                      {ROLE_LABELS[r]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateOpen(false)}>Cancel</Button>
            <Button
              onClick={() => {
                if (form.name.trim().length < 2 || !form.email.includes("@") || form.password.length < 8) {
                  toast({ title: "Check name, email and password (min 8 chars)", variant: "destructive" });
                  return;
                }
                createMutation.mutate({ ...form, email: form.email.trim().toLowerCase() });
              }}
              disabled={createMutation.isPending}
            >
              {createMutation.isPending && <Loader2 className="size-4 animate-spin" />}
              <Plus className="size-4" /> Create account
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
