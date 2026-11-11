"use client";

// OSSZ ATELIER — incentives view (supervisors only).
// Tab 1: incentive schemes (Management can create / edit / activate).
// Tab 2: evaluations & approvals — generate recommendations from the
// performance engine, then Management approves/rejects and records payments.

import { useCallback, useEffect, useState } from "react";
import { api } from "../client";
import { can, useAppStore } from "../store";
import type { IncentiveRecordRow, IncentiveSchemeRecord } from "../types";
import { EmptyState, PageHeader, SectionCard } from "../ui-bits";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/hooks/use-toast";
import {
  INCENTIVE_PERIOD_LABELS,
  INCENTIVE_PERIOD_TYPES,
  type IncentivePeriodType,
} from "@/lib/atelier/constants";
import { formatDate, formatFCFA } from "@/lib/format";
import { cn } from "@/lib/utils";
import {
  Award,
  CheckCircle2,
  Gift,
  Pencil,
  Plus,
  Save,
  Sparkles,
  XCircle,
} from "lucide-react";

// ---------------------------------------------------------------------------
// Small presentational bits
// ---------------------------------------------------------------------------

function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    PENDING: "bg-amber-50 text-amber-800 border-amber-300",
    APPROVED: "bg-emerald-50 text-emerald-800 border-emerald-300",
    REJECTED: "bg-red-50 text-red-800 border-red-300",
  };
  const labels: Record<string, string> = {
    PENDING: "Pending",
    APPROVED: "Approved",
    REJECTED: "Rejected",
  };
  return (
    <Badge variant="outline" className={cn("whitespace-nowrap", styles[status] ?? "")}>
      {labels[status] ?? status}
    </Badge>
  );
}

function PaymentBadge({ record }: { record: IncentiveRecordRow }) {
  if (record.paymentStatus === "PAID") {
    return (
      <div className="flex flex-col gap-0.5">
        <Badge variant="outline" className="bg-emerald-50 text-emerald-800 border-emerald-300 whitespace-nowrap">
          Paid
        </Badge>
        <span className="text-[11px] text-muted-foreground whitespace-nowrap">
          {record.paymentDate ? formatDate(record.paymentDate) : ""}
        </span>
      </div>
    );
  }
  return (
    <Badge variant="outline" className="bg-stone-100 text-stone-600 border-stone-300 whitespace-nowrap">
      Unpaid
    </Badge>
  );
}

// ---------------------------------------------------------------------------
// Scheme dialog form state
// ---------------------------------------------------------------------------

type SchemeForm = {
  name: string;
  periodType: IncentivePeriodType;
  minScore: string;
  amount: string;
  description: string;
};

const EMPTY_SCHEME: SchemeForm = {
  name: "",
  periodType: "MONTHLY",
  minScore: "",
  amount: "",
  description: "",
};

function periodLabel(periodType: string): string {
  return INCENTIVE_PERIOD_LABELS[periodType as IncentivePeriodType] ?? periodType;
}

// ---------------------------------------------------------------------------
// Main view
// ---------------------------------------------------------------------------

export function IncentivesView() {
  const user = useAppStore((s) => s.user);
  const { toast } = useToast();
  const canManageSchemes = can(user, "incentives.manage.schemes");
  const canPrepare = can(user, "incentives.prepare");
  const canApprove = can(user, "incentives.approve");
  const canPay = can(user, "incentives.payment");

  const [schemes, setSchemes] = useState<IncentiveSchemeRecord[] | null>(null);
  const [records, setRecords] = useState<IncentiveRecordRow[] | null>(null);
  const [tab, setTab] = useState("schemes");

  // --- scheme dialog ---
  const [schemeDialogOpen, setSchemeDialogOpen] = useState(false);
  const [editingScheme, setEditingScheme] = useState<IncentiveSchemeRecord | null>(null);
  const [schemeForm, setSchemeForm] = useState<SchemeForm>(EMPTY_SCHEME);
  const [savingScheme, setSavingScheme] = useState(false);
  const [togglingId, setTogglingId] = useState<string | null>(null);

  // --- generation toolbar ---
  const [selectedSchemeId, setSelectedSchemeId] = useState("");
  const [periodInput, setPeriodInput] = useState(
    `${new Date().getUTCFullYear()}-${String(new Date().getUTCMonth() + 1).padStart(2, "0")}`
  );
  const [generating, setGenerating] = useState(false);

  // --- review dialog ---
  const [reviewing, setReviewing] = useState<IncentiveRecordRow | null>(null);
  const [approveAmount, setApproveAmount] = useState("");
  const [reviewComments, setReviewComments] = useState("");
  const [submittingReview, setSubmittingReview] = useState(false);
  const [payingId, setPayingId] = useState<string | null>(null);

  const loadSchemes = useCallback(async () => {
    try {
      const res = await api.get<{ schemes: IncentiveSchemeRecord[] }>("/api/atelier/incentives/schemes");
      setSchemes(res.schemes);
      setSelectedSchemeId((prev) => prev || res.schemes.find((s) => s.isActive)?.id || "");
    } catch {
      setSchemes([]);
    }
  }, []);

  const loadRecords = useCallback(async () => {
    try {
      const res = await api.get<{ records: IncentiveRecordRow[] }>("/api/atelier/incentives/records");
      setRecords(res.records);
    } catch {
      setRecords([]);
    }
  }, []);

  useEffect(() => {
    void loadSchemes();
  }, [loadSchemes]);

  useEffect(() => {
    void loadRecords();
  }, [loadRecords]);

  const selectedScheme = schemes?.find((s) => s.id === selectedSchemeId) ?? null;
  const isQuarterly = selectedScheme?.periodType === "QUARTERLY";
  const isAnnual = selectedScheme?.periodType === "ANNUAL";
  const periodPlaceholder = isQuarterly ? "2026-Q4" : isAnnual ? "2026" : "2026-11";
  const periodHint = isQuarterly
    ? "Enter a quarter (YYYY-QN)"
    : isAnnual
      ? "Enter a year (YYYY)"
      : "Enter a month (YYYY-MM)";

  // ------------------------------------------------------------- schemes tab

  function openCreateScheme() {
    setEditingScheme(null);
    setSchemeForm(EMPTY_SCHEME);
    setSchemeDialogOpen(true);
  }

  function openEditScheme(scheme: IncentiveSchemeRecord) {
    setEditingScheme(scheme);
    setSchemeForm({
      name: scheme.name,
      periodType: (scheme.periodType as IncentivePeriodType) ?? "MONTHLY",
      minScore: scheme.minScore == null ? "" : String(scheme.minScore),
      amount: scheme.amount == null ? "" : String(scheme.amount),
      description: scheme.description ?? "",
    });
    setSchemeDialogOpen(true);
  }

  async function saveScheme() {
    const name = schemeForm.name.trim();
    if (name.length < 2 || name.length > 120) {
      toast({
        title: "Invalid name",
        description: "Scheme name must be between 2 and 120 characters.",
        variant: "destructive",
      });
      return;
    }
    const minScore = schemeForm.minScore.trim() === "" ? null : Number(schemeForm.minScore);
    if (minScore != null && (!Number.isFinite(minScore) || minScore < 0 || minScore > 100)) {
      toast({
        title: "Invalid minimum score",
        description: "Minimum score must be between 0 and 100.",
        variant: "destructive",
      });
      return;
    }
    const amount = schemeForm.amount.trim() === "" ? null : Number(schemeForm.amount);
    if (amount != null && (!Number.isFinite(amount) || amount < 0)) {
      toast({
        title: "Invalid amount",
        description: "Amount must be zero or more (FCFA).",
        variant: "destructive",
      });
      return;
    }
    const description = schemeForm.description.trim();

    setSavingScheme(true);
    try {
      if (editingScheme) {
        await api.patch(`/api/atelier/incentives/schemes/${editingScheme.id}`, {
          name,
          periodType: schemeForm.periodType,
          minScore,
          amount,
          description: description || null,
        });
        toast({ title: "Scheme updated", description: `${name} was saved.` });
      } else {
        await api.post("/api/atelier/incentives/schemes", {
          name,
          periodType: schemeForm.periodType,
          ...(minScore != null ? { minScore } : {}),
          ...(amount != null ? { amount } : {}),
          ...(description ? { description } : {}),
        });
        toast({ title: "Scheme created", description: `${name} was added.` });
      }
      setSchemeDialogOpen(false);
      await loadSchemes();
    } catch (e) {
      toast({
        title: "Could not save scheme",
        description: e instanceof Error ? e.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setSavingScheme(false);
    }
  }

  async function toggleSchemeActive(scheme: IncentiveSchemeRecord, checked: boolean) {
    setTogglingId(scheme.id);
    try {
      await api.patch(`/api/atelier/incentives/schemes/${scheme.id}`, { isActive: checked });
      setSchemes((prev) =>
        prev ? prev.map((s) => (s.id === scheme.id ? { ...s, isActive: checked } : s)) : prev
      );
      toast({
        title: checked ? "Scheme activated" : "Scheme deactivated",
        description: scheme.name,
      });
    } catch (e) {
      toast({
        title: "Could not update scheme",
        description: e instanceof Error ? e.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setTogglingId(null);
    }
  }

  // --------------------------------------------------------- evaluations tab

  async function generateRecommendations() {
    if (!selectedSchemeId) {
      toast({
        title: "Select a scheme",
        description: "Choose the incentive scheme to evaluate.",
        variant: "destructive",
      });
      return;
    }
    const period = periodInput.trim();
    if (!period) {
      toast({
        title: "Enter a period",
        description: isQuarterly
          ? "Expected format: YYYY-QN (e.g. 2026-Q4)."
          : isAnnual
            ? "Expected format: YYYY (e.g. 2026)."
            : "Expected format: YYYY-MM (e.g. 2026-11).",
        variant: "destructive",
      });
      return;
    }
    setGenerating(true);
    try {
      const res = await api.post<{ records: IncentiveRecordRow[] }>("/api/atelier/incentives/records", {
        schemeId: selectedSchemeId,
        period,
      });
      const eligibleCount = res.records.filter((r) => r.eligible).length;
      toast({
        title: "Recommendations generated",
        description: `${res.records.length} evaluation(s) prepared — ${eligibleCount} eligible.`,
      });
      await loadRecords();
    } catch (e) {
      toast({
        title: "Generation failed",
        description: e instanceof Error ? e.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setGenerating(false);
    }
  }

  function openReview(record: IncentiveRecordRow) {
    setReviewing(record);
    setApproveAmount(String(record.proposedAmount));
    setReviewComments("");
  }

  async function submitReview(status: "APPROVED" | "REJECTED") {
    if (!reviewing) return;
    let approvedAmount: number | undefined;
    if (status === "APPROVED") {
      const n = Number(approveAmount);
      if (!Number.isFinite(n) || n < 0) {
        toast({
          title: "Invalid amount",
          description: "Approved amount must be zero or more (FCFA).",
          variant: "destructive",
        });
        return;
      }
      approvedAmount = n;
    }
    setSubmittingReview(true);
    try {
      const res = await api.patch<{ record: IncentiveRecordRow }>(
        `/api/atelier/incentives/records/${reviewing.id}`,
        {
          action: "REVIEW",
          status,
          ...(approvedAmount !== undefined ? { approvedAmount } : {}),
          ...(reviewComments.trim() ? { approvalComments: reviewComments.trim() } : {}),
        }
      );
      setRecords((prev) =>
        prev ? prev.map((r) => (r.id === res.record.id ? res.record : r)) : prev
      );
      toast({
        title: status === "APPROVED" ? "Incentive approved" : "Incentive rejected",
        description: `${res.record.staffName} — ${res.record.schemeName} (${res.record.period}).`,
      });
      setReviewing(null);
    } catch (e) {
      toast({
        title: "Review failed",
        description: e instanceof Error ? e.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setSubmittingReview(false);
    }
  }

  async function markPaid(record: IncentiveRecordRow) {
    setPayingId(record.id);
    try {
      const res = await api.patch<{ record: IncentiveRecordRow }>(
        `/api/atelier/incentives/records/${record.id}`,
        { action: "PAYMENT", paymentStatus: "PAID" }
      );
      setRecords((prev) =>
        prev ? prev.map((r) => (r.id === res.record.id ? res.record : r)) : prev
      );
      toast({
        title: "Payment recorded",
        description: `${record.staffName} — ${formatFCFA(res.record.approvedAmount)}.`,
      });
    } catch (e) {
      toast({
        title: "Could not record payment",
        description: e instanceof Error ? e.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setPayingId(null);
    }
  }

  // ----------------------------------------------------------------- render

  return (
    <div>
      <PageHeader
        title="Staff Incentives"
        description="Define bonus schemes, generate evaluation recommendations from performance data, approve payments and record payouts."
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="mb-4">
          <TabsTrigger value="schemes">
            <Gift className="size-4 mr-1.5" />
            Incentive Schemes
          </TabsTrigger>
          <TabsTrigger value="evaluations">
            <Award className="size-4 mr-1.5" />
            Evaluations & Approvals
          </TabsTrigger>
        </TabsList>

        {/* ------------------------------------------------------ schemes tab */}
        <TabsContent value="schemes">
          <SectionCard
            title="Incentive Schemes"
            action={
              canManageSchemes ? (
                <Button size="sm" onClick={openCreateScheme}>
                  <Plus className="size-4 mr-1.5" />
                  New Scheme
                </Button>
              ) : undefined
            }
            contentClassName="p-0"
          >
            {schemes === null ? (
              <div className="p-4 space-y-3">
                {Array.from({ length: 3 }).map((_, i) => (
                  <Skeleton key={i} className="h-12 w-full" />
                ))}
              </div>
            ) : schemes.length === 0 ? (
              <EmptyState
                icon={Gift}
                title="No incentive schemes yet"
                description={
                  canManageSchemes
                    ? "Create your first scheme to start rewarding staff performance."
                    : "Management has not defined any incentive scheme yet."
                }
                action={
                  canManageSchemes ? (
                    <Button size="sm" onClick={openCreateScheme}>
                      <Plus className="size-4 mr-1.5" />
                      New Scheme
                    </Button>
                  ) : undefined
                }
              />
            ) : (
              <div className="overflow-x-auto">
                <Table className="min-w-[860px]">
                  <TableHeader>
                    <TableRow>
                      <TableHead>Name</TableHead>
                      <TableHead>Period Type</TableHead>
                      <TableHead className="text-center">Min Score</TableHead>
                      <TableHead className="text-right">Amount</TableHead>
                      <TableHead className="text-center">Active</TableHead>
                      <TableHead>Created By</TableHead>
                      {canManageSchemes && <TableHead className="text-right">Actions</TableHead>}
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {schemes.map((s) => (
                      <TableRow key={s.id} className={cn(!s.isActive && "opacity-60")}>
                        <TableCell>
                          <p className="font-medium">{s.name}</p>
                          {s.description && (
                            <p className="text-xs text-muted-foreground max-w-md truncate">
                              {s.description}
                            </p>
                          )}
                        </TableCell>
                        <TableCell>
                          <Badge variant="secondary" className="whitespace-nowrap">
                            {periodLabel(s.periodType)}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-center tabular-nums">
                          {s.minScore == null ? <span className="text-muted-foreground">Any</span> : `≥ ${s.minScore}`}
                        </TableCell>
                        <TableCell className="text-right tabular-nums font-medium">
                          {s.amount == null ? <span className="text-muted-foreground">—</span> : formatFCFA(s.amount)}
                        </TableCell>
                        <TableCell className="text-center">
                          {canManageSchemes ? (
                            <Switch
                              checked={s.isActive}
                              disabled={togglingId === s.id}
                              onCheckedChange={(checked) => void toggleSchemeActive(s, checked)}
                              aria-label={`Toggle ${s.name}`}
                            />
                          ) : (
                            <Badge
                              variant="outline"
                              className={cn(
                                "whitespace-nowrap",
                                s.isActive
                                  ? "bg-emerald-50 text-emerald-800 border-emerald-300"
                                  : "bg-stone-100 text-stone-500 border-stone-300"
                              )}
                            >
                              {s.isActive ? "Active" : "Inactive"}
                            </Badge>
                          )}
                        </TableCell>
                        <TableCell className="text-muted-foreground">{s.createdByName ?? "—"}</TableCell>
                        {canManageSchemes && (
                          <TableCell className="text-right">
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => openEditScheme(s)}
                              aria-label={`Edit ${s.name}`}
                            >
                              <Pencil className="size-3.5 mr-1" />
                              Edit
                            </Button>
                          </TableCell>
                        )}
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
          </SectionCard>
        </TabsContent>

        {/* -------------------------------------------------- evaluations tab */}
        <TabsContent value="evaluations">
          <SectionCard title="Generate Recommendations" className="mb-4">
            <div className="flex flex-col sm:flex-row sm:items-end gap-3">
              <div className="space-y-1.5 flex-1 min-w-[200px]">
                <Label htmlFor="incentive-scheme">Scheme</Label>
                <Select value={selectedSchemeId} onValueChange={setSelectedSchemeId}>
                  <SelectTrigger id="incentive-scheme" className="w-full">
                    <SelectValue placeholder="Select a scheme" />
                  </SelectTrigger>
                  <SelectContent>
                    {(schemes ?? []).filter((s) => s.isActive).map((s) => (
                      <SelectItem key={s.id} value={s.id}>
                        {s.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="incentive-period">Period</Label>
                <Input
                  id="incentive-period"
                  className="sm:w-[170px]"
                  placeholder={periodPlaceholder}
                  value={periodInput}
                  onChange={(e) => setPeriodInput(e.target.value)}
                />
              </div>
              {canPrepare && (
                <Button onClick={() => void generateRecommendations()} disabled={generating || !selectedSchemeId}>
                  <Sparkles className="size-4 mr-1.5" />
                  {generating ? "Generating..." : "Generate Recommendations"}
                </Button>
              )}
            </div>
            <p className="text-xs text-muted-foreground mt-3">
              {periodHint}
              {selectedScheme ? ` — ${periodLabel(selectedScheme.periodType)}.` : "."} Scores are read
              from the performance engine; approved or rejected records are never overwritten by regeneration.
            </p>
          </SectionCard>

          <SectionCard title="Evaluation Records" contentClassName="p-0">
            {records === null ? (
              <div className="p-4 space-y-3">
                {Array.from({ length: 3 }).map((_, i) => (
                  <Skeleton key={i} className="h-12 w-full" />
                ))}
              </div>
            ) : records.length === 0 ? (
              <EmptyState
                icon={Award}
                title="No evaluations yet"
                description="Generate recommendations for a scheme and period to create evaluation records."
              />
            ) : (
              <div className="overflow-x-auto max-h-[520px] overflow-y-auto">
                <Table className="min-w-[1100px]">
                  <TableHeader>
                    <TableRow>
                      <TableHead>Staff</TableHead>
                      <TableHead>Scheme</TableHead>
                      <TableHead>Period</TableHead>
                      <TableHead className="text-center">Score</TableHead>
                      <TableHead className="text-center">Eligible</TableHead>
                      <TableHead className="text-right">Proposed</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead className="text-right">Approved</TableHead>
                      <TableHead>Approved By</TableHead>
                      <TableHead>Payment</TableHead>
                      {(canApprove || canPay) && <TableHead className="text-right">Actions</TableHead>}
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {records.map((r) => (
                      <TableRow key={r.id}>
                        <TableCell className="font-medium">{r.staffName}</TableCell>
                        <TableCell className="text-muted-foreground">{r.schemeName}</TableCell>
                        <TableCell className="tabular-nums">{r.period}</TableCell>
                        <TableCell className="text-center">
                          <span className="font-semibold tabular-nums">{r.performanceScore}</span>
                        </TableCell>
                        <TableCell className="text-center">
                          {r.eligible ? (
                            <CheckCircle2 className="size-4 text-emerald-600 mx-auto" aria-label="Eligible" />
                          ) : (
                            <XCircle className="size-4 text-stone-400 mx-auto" aria-label="Not eligible" />
                          )}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">{formatFCFA(r.proposedAmount)}</TableCell>
                        <TableCell>
                          <StatusBadge status={r.status} />
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                          {r.approvedAmount == null ? (
                            <span className="text-muted-foreground">—</span>
                          ) : (
                            formatFCFA(r.approvedAmount)
                          )}
                        </TableCell>
                        <TableCell className="text-muted-foreground">{r.approvedByName ?? "—"}</TableCell>
                        <TableCell>
                          <PaymentBadge record={r} />
                        </TableCell>
                        {(canApprove || canPay) && (
                          <TableCell className="text-right">
                            <div className="flex items-center justify-end gap-1.5">
                              {canApprove && r.status === "PENDING" && (
                                <Button variant="outline" size="sm" onClick={() => openReview(r)}>
                                  Review
                                </Button>
                              )}
                              {canPay && r.status === "APPROVED" && r.paymentStatus === "UNPAID" && (
                                <Button
                                  variant="outline"
                                  size="sm"
                                  disabled={payingId === r.id}
                                  onClick={() => void markPaid(r)}
                                >
                                  <CheckCircle2 className="size-3.5 mr-1" />
                                  Mark as Paid
                                </Button>
                              )}
                              {!(canApprove && r.status === "PENDING") &&
                                !(canPay && r.status === "APPROVED" && r.paymentStatus === "UNPAID") && (
                                  <span className="text-muted-foreground text-sm">—</span>
                                )}
                            </div>
                          </TableCell>
                        )}
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
          </SectionCard>
        </TabsContent>
      </Tabs>

      {/* ------------------------------------------------------ scheme dialog */}
      <Dialog open={schemeDialogOpen} onOpenChange={setSchemeDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editingScheme ? "Edit Scheme" : "New Incentive Scheme"}</DialogTitle>
            <DialogDescription>
              Define the evaluation rule. Amounts are in FCFA; minimum score is compared against the
              staff performance score for the period.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="scheme-name">
                Name <span className="text-red-600">*</span>
              </Label>
              <Input
                id="scheme-name"
                maxLength={120}
                value={schemeForm.name}
                onChange={(e) => setSchemeForm((f) => ({ ...f, name: e.target.value }))}
                placeholder="e.g. Monthly Excellence Bonus"
              />
            </div>
            <div className="space-y-1.5">
              <Label>Period type</Label>
              <Select
                value={schemeForm.periodType}
                onValueChange={(v) => setSchemeForm((f) => ({ ...f, periodType: v as IncentivePeriodType }))}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {INCENTIVE_PERIOD_TYPES.map((t) => (
                    <SelectItem key={t} value={t}>
                      {INCENTIVE_PERIOD_LABELS[t]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="scheme-min">Min score (0-100)</Label>
                <Input
                  id="scheme-min"
                  type="number"
                  min={0}
                  max={100}
                  value={schemeForm.minScore}
                  onChange={(e) => setSchemeForm((f) => ({ ...f, minScore: e.target.value }))}
                  placeholder="Optional"
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="scheme-amount">Amount (FCFA)</Label>
                <Input
                  id="scheme-amount"
                  type="number"
                  min={0}
                  value={schemeForm.amount}
                  onChange={(e) => setSchemeForm((f) => ({ ...f, amount: e.target.value }))}
                  placeholder="Optional"
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="scheme-desc">Description</Label>
              <Textarea
                id="scheme-desc"
                rows={3}
                value={schemeForm.description}
                onChange={(e) => setSchemeForm((f) => ({ ...f, description: e.target.value }))}
                placeholder="What this scheme rewards."
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setSchemeDialogOpen(false)} disabled={savingScheme}>
              Cancel
            </Button>
            <Button onClick={() => void saveScheme()} disabled={savingScheme}>
              <Save className="size-4 mr-1.5" />
              {savingScheme ? "Saving..." : editingScheme ? "Save Changes" : "Create Scheme"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ------------------------------------------------------ review dialog */}
      <Dialog open={reviewing !== null} onOpenChange={(o) => !o && setReviewing(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Review Incentive</DialogTitle>
            <DialogDescription>
              Approve or reject this incentive recommendation. The decision is final — regeneration
              will not alter reviewed records.
            </DialogDescription>
          </DialogHeader>
          {reviewing && (
            <div className="space-y-4">
              <div className="rounded-lg border bg-muted/40 p-3 text-sm space-y-1">
                <p className="font-medium">
                  {reviewing.staffName} — {reviewing.schemeName}
                </p>
                <p className="text-muted-foreground">
                  Period {reviewing.period} · Score {reviewing.performanceScore} ·{" "}
                  {reviewing.eligible ? "eligible" : "not eligible"}
                </p>
                <p>
                  Proposed amount:{" "}
                  <span className="font-semibold">{formatFCFA(reviewing.proposedAmount)}</span>
                </p>
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="approve-amount">Approved amount (FCFA)</Label>
                <Input
                  id="approve-amount"
                  type="number"
                  min={0}
                  value={approveAmount}
                  onChange={(e) => setApproveAmount(e.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="review-comments">Comments</Label>
                <Textarea
                  id="review-comments"
                  rows={3}
                  value={reviewComments}
                  onChange={(e) => setReviewComments(e.target.value)}
                  placeholder="Optional note recorded with the decision."
                />
              </div>
            </div>
          )}
          <DialogFooter className="gap-2 sm:gap-2">
            <Button
              variant="destructive"
              disabled={submittingReview}
              onClick={() => void submitReview("REJECTED")}
            >
              <XCircle className="size-4 mr-1.5" />
              Reject
            </Button>
            <Button disabled={submittingReview} onClick={() => void submitReview("APPROVED")}>
              <CheckCircle2 className="size-4 mr-1.5" />
              Approve
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
