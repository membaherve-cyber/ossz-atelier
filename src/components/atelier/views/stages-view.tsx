"use client";

// OSSZ ATELIER — Production stages configuration (Management/Admin).
// Stages are never deleted: existing task histories keep the recorded stage
// names, so deactivation is the only form of removal.

import { useCallback, useEffect, useState } from "react";
import { api, ApiClientError } from "../client";
import type { StageConfig } from "../types";
import { useI18n } from "../i18n/use-i18n";
import { categoryLabel } from "../i18n/core";
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
import { APPLIES_TO_ALL, TASK_CATEGORIES } from "@/lib/atelier/constants";
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
  const { t, lang } = useI18n();
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
        title: t("stages.invalidNameTitle"),
        description: t("stages.invalidNameDesc"),
        variant: "destructive",
      });
      return;
    }
    const weight = form.weight.trim() === "" ? 1 : Number(form.weight);
    if (!Number.isFinite(weight) || weight < 0.1 || weight > 10) {
      toast({
        title: t("stages.invalidWeightTitle"),
        description: t("stages.invalidWeightDesc"),
        variant: "destructive",
      });
      return;
    }
    const sortOrder = form.sortOrder.trim() === "" ? 0 : Number(form.sortOrder);
    if (!Number.isInteger(sortOrder) || sortOrder < 0 || sortOrder > 999) {
      toast({
        title: t("stages.invalidSortTitle"),
        description: t("stages.invalidSortDesc"),
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
        title: stage ? t("stages.updatedToastTitle") : t("stages.createdToastTitle"),
        description: t("stages.savedToastDesc", { name: res.stage.name }),
      });
      onSaved(res.stage);
      onClose();
    } catch (err) {
      toast({
        title: t("stages.saveFailedToast"),
        description: err instanceof ApiClientError ? err.message : t("stages.unexpectedError"),
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
          <DialogTitle>{stage ? t("stages.editTitle") : t("stages.addStage")}</DialogTitle>
          <DialogDescription>
            {stage ? t("stages.editDescription") : t("stages.addDescription")}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="stage-name">{t("stages.nameLabel")}</Label>
            <Input
              id="stage-name"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              placeholder={t("stages.namePlaceholder")}
              maxLength={80}
            />
          </div>

          <div className="space-y-1.5">
            <Label>{t("stages.appliesToLabel")}</Label>
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
                  {categoryLabel(code, lang)}
                </label>
              ))}
            </div>
            <p className="text-xs text-muted-foreground">
              {t("stages.appliesToHint")}
            </p>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label htmlFor="stage-weight">{t("stages.weightLabel")}</Label>
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
              <Label htmlFor="stage-sort">{t("stages.sortOrderLabel")}</Label>
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
            {t("common.cancel")}
          </Button>
          <Button onClick={submit} disabled={saving}>
            {saving ? t("stages.saving") : stage ? t("common.saveChanges") : t("stages.createStage")}
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
  const { t } = useI18n();
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
      // Empty string marks a generic failure; the translated message is
      // rendered at display time so it follows the active language.
      setError(err instanceof ApiClientError ? err.message : "");
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
        title: res.stage.isActive ? t("stages.activatedToastTitle") : t("stages.deactivatedToastTitle"),
        description: res.stage.isActive
          ? t("stages.activatedToastDesc", { name: res.stage.name })
          : t("stages.deactivatedToastDesc", { name: res.stage.name }),
      });
    } catch (err) {
      setStages((list) =>
        (list ?? []).map((s) => (s.id === stage.id ? { ...s, isActive: stage.isActive } : s))
      );
      toast({
        title: t("stages.updateFailedToast"),
        description: err instanceof ApiClientError ? err.message : t("stages.unexpectedError"),
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

  if (error !== null) {
    return (
      <EmptyState
        icon={AlertTriangle}
        title={t("stages.errorTitle")}
        description={error || t("stages.unexpectedError")}
        action={
          <Button variant="outline" size="sm" onClick={load}>
            {t("common.tryAgain")}
          </Button>
        }
      />
    );
  }

  return (
    <div>
      <PageHeader
        title={t("stages.title")}
        description={t("stages.description")}
      >
        <Button onClick={openCreate}>
          <Plus className="size-4 mr-1.5" />
          {t("stages.addStage")}
        </Button>
      </PageHeader>

      <Alert className="mb-4 border-amber-300 bg-amber-50/70 text-amber-900">
        <Info className="size-4" />
        <AlertTitle>{t("stages.historyTitle")}</AlertTitle>
        <AlertDescription className="text-amber-800/90">
          {t("stages.historyDescription")}
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
          title={t("stages.emptyTitle")}
          description={t("stages.emptyDescription")}
          action={
            <Button size="sm" onClick={openCreate}>
              <Plus className="size-4 mr-1.5" />
              {t("stages.addStage")}
            </Button>
          }
        />
      ) : (
        <div className="rounded-lg border overflow-x-auto">
          <Table className="min-w-[760px]">
            <TableHeader>
              <TableRow>
                <TableHead className="w-20">{t("stages.col.sort")}</TableHead>
                <TableHead>{t("stages.col.name")}</TableHead>
                <TableHead>{t("stages.col.code")}</TableHead>
                <TableHead>{t("stages.col.appliesTo")}</TableHead>
                <TableHead className="w-20 text-right">{t("stages.col.weight")}</TableHead>
                <TableHead className="w-24 text-center">{t("stages.col.active")}</TableHead>
                <TableHead className="w-20 text-right">{t("common.actions")}</TableHead>
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
                        {t("stages.allCategories")}
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
                      aria-label={
                        s.isActive
                          ? t("stages.deactivateStageAria", { name: s.name })
                          : t("stages.activateStageAria", { name: s.name })
                      }
                    />
                  </TableCell>
                  <TableCell className="text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => openEdit(s)}
                      aria-label={t("stages.editStageAria", { name: s.name })}
                    >
                      <Pencil className="size-4" />
                      <span className="sr-only">{t("common.edit")}</span>
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
