"use client";

// OSSZ ATELIER — Production stages configuration (Management/Admin).
// Stages are never deleted: existing task histories keep the recorded stage
// names, so deactivation is the only form of removal.

import { useCallback, useEffect, useState } from "react";
import { api, ApiClientError } from "../client";
import type { StageConfig } from "../types";
import { CategoryBadge, EmptyState, PageHeader } from "../ui-bits";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
import { useToast } from "@/hooks/use-toast";
import { APPLIES_TO_ALL, CATEGORY_LABELS, TASK_CATEGORIES } from "@/lib/atelier/constants";
import { AlertTriangle, Info, ListChecks, Pencil, Plus } from "lucide-react";

type FormState = {
  name: string;
  weight: string;
  sortOrder: string;
  appliesTo: Set<string>;
};

function emptyForm(): FormState {
  return { name: "", weight: "1", sortOrder: "0", appliesTo: new Set() };
}

function formFromStage(stage: StageConfig): FormState {
  return {
    name: stage.name,
    weight: String(stage.weight),
    sortOrder: String(stage.sortOrder),
    appliesTo:
      stage.appliesTo === APPLIES_TO_ALL
        ? new Set(TASK_CATEGORIES as readonly string[])
        : new Set(stage.appliesTo.split(",").filter(Boolean)),
  };
}

function formatWeight(w: number): string {
  return Number.isInteger(w) ? String(w) : w.toFixed(1);
}

// ---------------------------------------------------------------------------
// Add / edit dialog (shared form)
// ---------------------------------------------------------------------------

function StageDialog({
  open,
  stage,
  onClose,
  onSaved,
}: {
  open: boolean;
  stage: StageConfig | null;
  onClose: () => void;
  onSaved: (stage: StageConfig) => void;
}) {
  const { toast } = useToast();
  const [form, setForm] = useState<FormState>(emptyForm());
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (open) setForm(stage ? formFromStage(stage) : emptyForm());
  }, [open, stage]);

  const toggleCategory = (code: string, checked: boolean) => {
    setForm((f) => {
      const next = new Set(f.appliesTo);
      if (checked) next.add(code);
      else next.delete(code);
      return { ...f, appliesTo: next };
    });
  };

  const submit = async () => {
    const name = form.name.trim();
    if (name.length < 2 || name.length > 80) {
      toast({
        title: "Invalid name",
        description: "The stage name must be between 2 and 80 characters.",
        variant: "destructive",
      });
      return;
    }
    const weight = form.weight.trim() === "" ? 1 : Number(form.weight);
    if (!Number.isFinite(weight) || weight < 0.1 || weight > 10) {
      toast({
        title: "Invalid weight",
        description: "Weight must be a number between 0.1 and 10.",
        variant: "destructive",
      });
      return;
    }
    const sortOrder = form.sortOrder.trim() === "" ? 0 : Number(form.sortOrder);
    if (!Number.isInteger(sortOrder) || sortOrder < 0 || sortOrder > 999) {
      toast({
        title: "Invalid sort order",
        description: "Sort order must be a whole number between 0 and 999.",
        variant: "destructive",
      });
      return;
    }

    const payload = {
      name,
      weight,
      sortOrder,
      appliesTo: TASK_CATEGORIES.filter((c) => form.appliesTo.has(c)),
    };

    setSaving(true);
    try {
      const res = stage
        ? await api.patch<{ stage: StageConfig }>(`/api/atelier/stages/${stage.id}`, payload)
        : await api.post<{ stage: StageConfig }>("/api/atelier/stages", payload);
      toast({
        title: stage ? "Stage updated" : "Stage created",
        description: `"${res.stage.name}" was saved successfully.`,
      });
      onSaved(res.stage);
      onClose();
    } catch (err) {
      toast({
        title: "Could not save the stage",
        description: err instanceof ApiClientError ? err.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{stage ? "Edit Stage" : "Add Stage"}</DialogTitle>
          <DialogDescription>
            {stage
              ? "Update the stage details. The stage code stays unchanged."
              : "Define a new checklist stage and the task categories it applies to."}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="stage-name">Name</Label>
            <Input
              id="stage-name"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              placeholder="e.g. Fabric cutting"
              maxLength={80}
            />
          </div>

          <div className="space-y-1.5">
            <Label>Applies to</Label>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-lg border p-3">
              {TASK_CATEGORIES.map((code) => (
                <label
                  key={code}
                  className="flex items-center gap-2 text-sm cursor-pointer min-h-11 sm:min-h-0"
                >
                  <Checkbox
                    checked={form.appliesTo.has(code)}
                    onCheckedChange={(v) => toggleCategory(code, v === true)}
                  />
                  {CATEGORY_LABELS[code]}
                </label>
              ))}
            </div>
            <p className="text-xs text-muted-foreground">
              Leave all unchecked or check all to apply to every category.
            </p>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label htmlFor="stage-weight">Weight</Label>
              <Input
                id="stage-weight"
                type="number"
                step="0.1"
                min="0.1"
                max="10"
                value={form.weight}
                onChange={(e) => setForm((f) => ({ ...f, weight: e.target.value }))}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="stage-sort">Sort order</Label>
              <Input
                id="stage-sort"
                type="number"
                step="1"
                min="0"
                max="999"
                value={form.sortOrder}
                onChange={(e) => setForm((f) => ({ ...f, sortOrder: e.target.value }))}
              />
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={onClose} disabled={saving}>
            Cancel
          </Button>
          <Button onClick={submit} disabled={saving}>
            {saving ? "Saving…" : stage ? "Save changes" : "Create stage"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ---------------------------------------------------------------------------
// Main view
// ---------------------------------------------------------------------------

export function StagesView() {
  const { toast } = useToast();
  const [stages, setStages] = useState<StageConfig[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<StageConfig | null>(null);
  const [togglingId, setTogglingId] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const res = await api.get<{ stages: StageConfig[] }>("/api/atelier/stages");
      setStages(res.stages);
      setError(null);
    } catch (err) {
      setError(err instanceof ApiClientError ? err.message : "Unexpected error.");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const toggleActive = async (stage: StageConfig) => {
    const next = !stage.isActive;
    // Optimistic: flip immediately, roll back on failure.
    setStages((list) =>
      (list ?? []).map((s) => (s.id === stage.id ? { ...s, isActive: next } : s))
    );
    setTogglingId(stage.id);
    try {
      const res = await api.patch<{ stage: StageConfig }>(`/api/atelier/stages/${stage.id}`, {
        isActive: next,
      });
      setStages((list) => (list ?? []).map((s) => (s.id === res.stage.id ? res.stage : s)));
      toast({
        title: res.stage.isActive ? "Stage activated" : "Stage deactivated",
        description: res.stage.isActive
          ? `"${res.stage.name}" is available to new tasks again.`
          : `"${res.stage.name}" will no longer be added to new tasks.`,
      });
    } catch (err) {
      setStages((list) =>
        (list ?? []).map((s) => (s.id === stage.id ? { ...s, isActive: stage.isActive } : s))
      );
      toast({
        title: "Could not update the stage",
        description: err instanceof ApiClientError ? err.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setTogglingId(null);
    }
  };

  const openCreate = () => {
    setEditing(null);
    setDialogOpen(true);
  };

  const openEdit = (stage: StageConfig) => {
    setEditing(stage);
    setDialogOpen(true);
  };

  const handleSaved = (saved: StageConfig) => {
    setStages((list) => {
      const base = list ?? [];
      const exists = base.some((s) => s.id === saved.id);
      const next = exists ? base.map((s) => (s.id === saved.id ? saved : s)) : [...base, saved];
      return next.sort(
        (a, b) => a.sortOrder - b.sortOrder || a.name.localeCompare(b.name)
      );
    });
  };

  if (error) {
    return (
      <EmptyState
        icon={AlertTriangle}
        title="Could not load the stage configuration"
        description={error}
        action={
          <Button variant="outline" size="sm" onClick={load}>
            Try again
          </Button>
        }
      />
    );
  }

  return (
    <div>
      <PageHeader
        title="Production Stages"
        description="Configure the checklist stages available to production tasks. Different task categories use different stage sets."
      >
        <Button onClick={openCreate}>
          <Plus className="size-4 mr-1.5" />
          Add Stage
        </Button>
      </PageHeader>

      <Alert className="mb-4 border-amber-300 bg-amber-50/70 text-amber-900">
        <Info className="size-4" />
        <AlertTitle>Stage changes never rewrite history</AlertTitle>
        <AlertDescription className="text-amber-800/90">
          Existing task histories keep the stage names at the time of recording. Deactivating a
          stage removes it from new tasks only.
        </AlertDescription>
      </Alert>

      {stages === null ? (
        <div className="space-y-2">
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
        </div>
      ) : stages.length === 0 ? (
        <EmptyState
          icon={ListChecks}
          title="No stages configured"
          description="Create the first checklist stage to start building the production workflow."
          action={
            <Button size="sm" onClick={openCreate}>
              <Plus className="size-4 mr-1.5" />
              Add Stage
            </Button>
          }
        />
      ) : (
        <div className="rounded-lg border overflow-x-auto">
          <Table className="min-w-[760px]">
            <TableHeader>
              <TableRow>
                <TableHead className="w-20">Sort</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Code</TableHead>
                <TableHead>Applies To</TableHead>
                <TableHead className="w-20 text-right">Weight</TableHead>
                <TableHead className="w-24 text-center">Active</TableHead>
                <TableHead className="w-20 text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {stages.map((s) => (
                <TableRow key={s.id} className={s.isActive ? "" : "opacity-60"}>
                  <TableCell className="text-muted-foreground tabular-nums">{s.sortOrder}</TableCell>
                  <TableCell className="font-medium">{s.name}</TableCell>
                  <TableCell>
                    <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">
                      {s.code}
                    </code>
                  </TableCell>
                  <TableCell>
                    {s.appliesTo === APPLIES_TO_ALL ? (
                      <Badge
                        variant="outline"
                        className="border-amber-300 bg-amber-50 text-amber-900"
                      >
                        All categories
                      </Badge>
                    ) : (
                      <div className="flex flex-wrap gap-1">
                        {s.appliesTo
                          .split(",")
                          .filter(Boolean)
                          .map((code) => (
                            <CategoryBadge key={code} category={code} />
                          ))}
                      </div>
                    )}
                  </TableCell>
                  <TableCell className="text-right tabular-nums">{formatWeight(s.weight)}</TableCell>
                  <TableCell className="text-center">
                    <Switch
                      checked={s.isActive}
                      onCheckedChange={() => toggleActive(s)}
                      disabled={togglingId === s.id}
                      aria-label={`${s.isActive ? "Deactivate" : "Activate"} stage ${s.name}`}
                    />
                  </TableCell>
                  <TableCell className="text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => openEdit(s)}
                      aria-label={`Edit stage ${s.name}`}
                    >
                      <Pencil className="size-4" />
                      <span className="sr-only">Edit</span>
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      <StageDialog
        open={dialogOpen}
        stage={editing}
        onClose={() => setDialogOpen(false)}
        onSaved={handleSaved}
      />
    </div>
  );
}
