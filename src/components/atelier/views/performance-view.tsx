"use client";

// OSSZ ATELIER — staff performance view (supervisors only).
// Period-scoped metrics & scores computed by the server-side performance
// engine, with Management-only weight configuration and manual assessments.

import { useCallback, useEffect, useState } from "react";
import type { ReactNode } from "react";
import { api, qs } from "../client";
import { can, useAppStore } from "../store";
import type { PerformanceResponse, PerformanceWeights, StaffPerformanceRow } from "../types";
import { EmptyState, PageHeader, SectionCard } from "../ui-bits";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useToast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { AlertTriangle, ChevronLeft, ChevronRight, Download, Save, TrendingUp, Users } from "lucide-react";

// ---------------------------------------------------------------------------
// Period helpers (client-side, UTC to match the engine)
// ---------------------------------------------------------------------------

type PeriodChoice = "MONTH" | "QUARTER" | "YEAR";

export function monthNow(): string {
  const now = new Date();
  return `${now.getUTCFullYear()}-${String(now.getUTCMonth() + 1).padStart(2, "0")}`;
}

export function yearNow(): string {
  return String(new Date().getUTCFullYear());
}

function quarterNow(): string {
  const now = new Date();
  return `${now.getUTCFullYear()}-Q${Math.floor(now.getUTCMonth() / 3) + 1}`;
}

function shiftMonth(value: string, delta: number): string {
  const m = /^(\d{4})-(\d{2})$/.exec(value);
  if (!m) return value;
  const d = new Date(Date.UTC(Number(m[1]), Number(m[2]) - 1 + delta, 1));
  return `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, "0")}`;
}

function shiftQuarter(value: string, delta: number): string {
  const m = /^(\d{4})-Q([1-4])$/.exec(value);
  if (!m) return value;
  const total = Number(m[1]) * 4 + (Number(m[2]) - 1) + delta;
  const year = Math.floor(total / 4);
  return `${year}-Q${total - year * 4 + 1}`;
}

function shiftYear(value: string, delta: number): string {
  const m = /^\d{4}$/.exec(value);
  if (!m) return value;
  return String(Number(m[1]) + delta);
}

// ---------------------------------------------------------------------------
// Shared bits (also used by the reports view)
// ---------------------------------------------------------------------------

export function scoreTone(value: number): string {
  if (value >= 75) return "text-emerald-700";
  if (value >= 50) return "text-amber-700";
  return "text-red-700";
}

function csvCell(value: string | number | null | undefined): string {
  const s = value == null ? "" : String(value);
  return /[",\n]/.test(s) ? `"${s.replaceAll('"', '""')}"` : s;
}

export function downloadCsv(
  filename: string,
  rows: Array<Array<string | number | null | undefined>>
): void {
  const csv = rows.map((r) => r.map(csvCell).join(",")).join("\n");
  const blob = new Blob([`\uFEFF${csv}`], { type: "text/csv;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

export function AssessmentReadOnly({ row }: { row: StaffPerformanceRow }) {
  if (!row.managementAssessed) return <span className="text-muted-foreground">—</span>;
  return <span className="font-medium tabular-nums">{row.scores.management}</span>;
}

/** Staff performance table shared with the reports view. */
export function StaffPerformanceTable({
  staff,
  assessmentCell,
}: {
  staff: StaffPerformanceRow[];
  assessmentCell?: (row: StaffPerformanceRow) => ReactNode;
}) {
  return (
    <div className="overflow-x-auto">
      <Table className="min-w-[1150px]">
        <TableHeader>
          <TableRow>
            <TableHead rowSpan={2}>Staff</TableHead>
            <TableHead colSpan={8} className="text-center border-l">
              Recorded metrics
            </TableHead>
            <TableHead colSpan={5} className="text-center border-l">
              Scores
            </TableHead>
            <TableHead rowSpan={2} className="text-center border-l">
              Overall
            </TableHead>
            {assessmentCell && (
              <TableHead rowSpan={2} className="border-l min-w-[130px]">
                Mgmt Assessment
              </TableHead>
            )}
          </TableRow>
          <TableRow>
            <TableHead className="border-l text-center">Assigned</TableHead>
            <TableHead className="text-center">Completed</TableHead>
            <TableHead className="text-center">On-time</TableHead>
            <TableHead className="text-center">Overdue</TableHead>
            <TableHead className="text-center">Avg Hours</TableHead>
            <TableHead className="text-center">Quality %</TableHead>
            <TableHead className="text-center">Corrections</TableHead>
            <TableHead className="text-center">Blocked</TableHead>
            <TableHead className="border-l text-center">Productivity</TableHead>
            <TableHead className="text-center">Timeliness</TableHead>
            <TableHead className="text-center">Quality</TableHead>
            <TableHead className="text-center">Reliability</TableHead>
            <TableHead className="text-center">Mgmt</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {staff.map((row) => (
            <TableRow key={row.staffId}>
              <TableCell>
                <div className="min-w-[160px]">
                  <p className="font-medium flex items-center gap-2">
                    {row.staffName}
                    {!row.isActive && (
                      <Badge variant="outline" className="bg-stone-50 text-stone-500 border-stone-300 text-[10px] px-1.5">
                        Inactive
                      </Badge>
                    )}
                  </p>
                  <p className="text-xs text-muted-foreground truncate">{row.staffEmail}</p>
                </div>
              </TableCell>
              <TableCell className="text-center tabular-nums">{row.metrics.tasksAssigned}</TableCell>
              <TableCell className="text-center tabular-nums">{row.metrics.tasksCompleted}</TableCell>
              <TableCell className="text-center tabular-nums">{row.metrics.onTimeCompletions}</TableCell>
              <TableCell className="text-center tabular-nums">
                <span className={row.metrics.overdueTasks > 0 ? "text-red-700 font-semibold" : undefined}>
                  {row.metrics.overdueTasks}
                </span>
              </TableCell>
              <TableCell className="text-center tabular-nums">
                {row.metrics.avgCompletionHours != null ? row.metrics.avgCompletionHours.toFixed(1) : "—"}
              </TableCell>
              <TableCell className="text-center tabular-nums">{row.metrics.qualityApprovalRate}%</TableCell>
              <TableCell className="text-center tabular-nums">{row.metrics.correctionsReturned}</TableCell>
              <TableCell className="text-center tabular-nums">{row.metrics.blockedTasks}</TableCell>
              <TableCell className="border-l text-center tabular-nums text-muted-foreground">
                {row.scores.productivity}
              </TableCell>
              <TableCell className="text-center tabular-nums text-muted-foreground">
                {row.scores.timeliness}
              </TableCell>
              <TableCell className="text-center tabular-nums text-muted-foreground">
                {row.scores.quality}
              </TableCell>
              <TableCell className="text-center tabular-nums text-muted-foreground">
                {row.scores.reliability}
              </TableCell>
              <TableCell className="text-center tabular-nums text-muted-foreground">
                {row.scores.management}
              </TableCell>
              <TableCell className="border-l text-center">
                <span className={cn("font-bold tabular-nums", scoreTone(row.overall))}>{row.overall}</span>
              </TableCell>
              {assessmentCell && <TableCell className="border-l">{assessmentCell(row)}</TableCell>}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Weights configuration (Management only)
// ---------------------------------------------------------------------------

type WeightsForm = Record<keyof PerformanceWeights, string>;

const WEIGHT_FIELDS: Array<{ key: keyof PerformanceWeights; label: string }> = [
  { key: "productivityWeight", label: "Productivity %" },
  { key: "timelyWeight", label: "Timely Completion %" },
  { key: "qualityWeight", label: "Quality & Approval %" },
  { key: "reliabilityWeight", label: "Reliability %" },
  { key: "managementWeight", label: "Management Assessment %" },
];

function toForm(config: PerformanceWeights): WeightsForm {
  return {
    productivityWeight: String(config.productivityWeight),
    timelyWeight: String(config.timelyWeight),
    qualityWeight: String(config.qualityWeight),
    reliabilityWeight: String(config.reliabilityWeight),
    managementWeight: String(config.managementWeight),
    pieceTarget: String(config.pieceTarget),
  };
}

// ---------------------------------------------------------------------------
// Main view
// ---------------------------------------------------------------------------

export function PerformanceView() {
  const user = useAppStore((s) => s.user);
  const { toast } = useToast();
  const canConfigure = can(user, "performance.configure");
  const canAssess = can(user, "performance.assess");

  const [pType, setPType] = useState<PeriodChoice>("MONTH");
  const [monthValue, setMonthValue] = useState(monthNow());
  const [quarterValue, setQuarterValue] = useState(quarterNow());
  const [yearValue, setYearValue] = useState(yearNow());

  const periodValue = pType === "MONTH" ? monthValue : pType === "QUARTER" ? quarterValue : yearValue;

  const [data, setData] = useState<PerformanceResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [config, setConfig] = useState<PerformanceWeights | null>(null);
  const [weightsForm, setWeightsForm] = useState<WeightsForm | null>(null);
  const [savingConfig, setSavingConfig] = useState(false);

  const [drafts, setDrafts] = useState<Record<string, string>>({});
  const [savingRow, setSavingRow] = useState<string | null>(null);

  const loadPerformance = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.get<PerformanceResponse>(
        `/api/atelier/performance${qs({ type: pType, value: periodValue })}`
      );
      setData(res);
      setDrafts({});
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load performance data.");
    } finally {
      setLoading(false);
    }
  }, [pType, periodValue]);

  useEffect(() => {
    void loadPerformance();
  }, [loadPerformance]);

  useEffect(() => {
    if (!canConfigure) return;
    let cancelled = false;
    api
      .get<{ config: PerformanceWeights }>("/api/atelier/performance/config")
      .then((res) => {
        if (cancelled) return;
        setConfig(res.config);
        setWeightsForm(toForm(res.config));
      })
      .catch(() => undefined);
    return () => {
      cancelled = true;
    };
  }, [canConfigure]);

  const num = (v: string | undefined): number => {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
  };
  const weightSum = weightsForm
    ? num(weightsForm.productivityWeight) +
      num(weightsForm.timelyWeight) +
      num(weightsForm.qualityWeight) +
      num(weightsForm.reliabilityWeight) +
      num(weightsForm.managementWeight)
    : 0;
  const sumOk = Math.abs(weightSum - 100) <= 0.01;
  const targetValue = weightsForm ? num(weightsForm.pieceTarget) : 0;
  const targetOk = Number.isInteger(targetValue) && targetValue >= 1 && targetValue <= 500;

  async function saveConfig() {
    if (!weightsForm || !sumOk || !targetOk) return;
    setSavingConfig(true);
    try {
      const res = await api.patch<{ config: PerformanceWeights }>("/api/atelier/performance/config", {
        productivityWeight: num(weightsForm.productivityWeight),
        timelyWeight: num(weightsForm.timelyWeight),
        qualityWeight: num(weightsForm.qualityWeight),
        reliabilityWeight: num(weightsForm.reliabilityWeight),
        managementWeight: num(weightsForm.managementWeight),
        pieceTarget: targetValue,
      });
      setConfig(res.config);
      setWeightsForm(toForm(res.config));
      toast({
        title: "Weights saved",
        description: "Scoring weights updated — all scores were recomputed.",
      });
      await loadPerformance();
    } catch (e) {
      toast({
        title: "Could not save weights",
        description: e instanceof Error ? e.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setSavingConfig(false);
    }
  }

  async function saveAssessment(row: StaffPerformanceRow) {
    const raw = (drafts[row.staffId] ?? "").trim();
    const score = Number(raw);
    if (raw === "" || !Number.isFinite(score) || score < 0 || score > 100) {
      toast({
        title: "Invalid score",
        description: "Enter a management assessment between 0 and 100.",
        variant: "destructive",
      });
      return;
    }
    setSavingRow(row.staffId);
    try {
      await api.put("/api/atelier/performance/assessment", {
        staffId: row.staffId,
        period: periodValue,
        score,
      });
      toast({
        title: "Assessment saved",
        description: `${row.staffName} — ${score}/100 for ${periodValue}.`,
      });
      setDrafts((prev) => {
        const next = { ...prev };
        delete next[row.staffId];
        return next;
      });
      await loadPerformance();
    } catch (e) {
      toast({
        title: "Could not save assessment",
        description: e instanceof Error ? e.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setSavingRow(null);
    }
  }

  function stepPeriod(delta: number) {
    if (pType === "MONTH") setMonthValue((v) => shiftMonth(v, delta));
    else if (pType === "QUARTER") setQuarterValue((v) => shiftQuarter(v, delta));
    else setYearValue((v) => shiftYear(v, delta));
  }

  function exportCsv() {
    if (!data) return;
    downloadCsv(`ossz-performance-${data.range.value}.csv`, [
      [
        "Staff",
        "Email",
        "Assigned",
        "Completed",
        "On-time",
        "Overdue",
        "Avg Hours",
        "Quality %",
        "Corrections",
        "Blocked",
        "Productivity",
        "Timeliness",
        "Quality",
        "Reliability",
        "Management",
        "Overall",
      ],
      ...data.staff.map((s) => [
        s.staffName,
        s.staffEmail,
        s.metrics.tasksAssigned,
        s.metrics.tasksCompleted,
        s.metrics.onTimeCompletions,
        s.metrics.overdueTasks,
        s.metrics.avgCompletionHours,
        s.metrics.qualityApprovalRate,
        s.metrics.correctionsReturned,
        s.metrics.blockedTasks,
        s.scores.productivity,
        s.scores.timeliness,
        s.scores.quality,
        s.scores.reliability,
        s.managementAssessed ? s.scores.management : "",
        s.overall,
      ]),
    ]);
  }

  const assessmentCell = canAssess
    ? (row: StaffPerformanceRow) => (
        <div className="flex items-center gap-1">
          <Input
            aria-label={`Management assessment for ${row.staffName}`}
            type="number"
            min={0}
            max={100}
            className="h-8 w-[76px] text-center"
            value={
              drafts[row.staffId] !== undefined
                ? drafts[row.staffId]
                : row.managementAssessed
                  ? String(row.scores.management)
                  : ""
            }
            onChange={(e) => setDrafts((d) => ({ ...d, [row.staffId]: e.target.value }))}
          />
          <Button
            variant="ghost"
            size="icon"
            className="size-8"
            disabled={savingRow === row.staffId}
            onClick={() => void saveAssessment(row)}
            aria-label={`Save assessment for ${row.staffName}`}
          >
            <Save className="size-4" />
          </Button>
        </div>
      )
    : (row: StaffPerformanceRow) => <AssessmentReadOnly row={row} />;

  return (
    <div>
      <PageHeader
        title="Staff Performance"
        description="Metrics and weighted scores per staff member, computed from real assignments, completions and inspections."
      >
        <Button variant="outline" size="sm" onClick={exportCsv} disabled={!data || data.staff.length === 0}>
          <Download className="size-4 mr-1.5" />
          Export CSV
        </Button>
      </PageHeader>

      {/* ---------------------------------------------------- period selector */}
      <SectionCard title="Reporting Period" className="mb-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1.5">
            <Label htmlFor="perf-type">Period type</Label>
            <Select value={pType} onValueChange={(v) => setPType(v as PeriodChoice)}>
              <SelectTrigger id="perf-type" className="w-[150px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="MONTH">Monthly</SelectItem>
                <SelectItem value="QUARTER">Quarterly</SelectItem>
                <SelectItem value="YEAR">Annual</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {pType === "MONTH" && (
            <div className="space-y-1.5">
              <Label htmlFor="perf-month">Month</Label>
              <Input
                id="perf-month"
                type="month"
                className="w-[170px]"
                value={monthValue}
                onChange={(e) => setMonthValue(e.target.value)}
              />
            </div>
          )}

          {pType === "QUARTER" && (
            <>
              <div className="space-y-1.5">
                <Label htmlFor="perf-qyear">Year</Label>
                <Input
                  id="perf-qyear"
                  type="number"
                  className="w-[110px]"
                  value={quarterValue.slice(0, 4)}
                  onChange={(e) =>
                    setQuarterValue(`${e.target.value}-${quarterValue.slice(5) || "Q1"}`)
                  }
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="perf-quarter">Quarter</Label>
                <Select
                  value={quarterValue.slice(5) || "Q1"}
                  onValueChange={(q) => setQuarterValue(`${quarterValue.slice(0, 4)}-${q}`)}
                >
                  <SelectTrigger id="perf-quarter" className="w-[110px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Q1">Q1</SelectItem>
                    <SelectItem value="Q2">Q2</SelectItem>
                    <SelectItem value="Q3">Q3</SelectItem>
                    <SelectItem value="Q4">Q4</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </>
          )}

          {pType === "YEAR" && (
            <div className="space-y-1.5">
              <Label htmlFor="perf-year">Year</Label>
              <Input
                id="perf-year"
                type="number"
                className="w-[110px]"
                value={yearValue}
                onChange={(e) => setYearValue(e.target.value)}
              />
            </div>
          )}

          <div className="flex items-center gap-1.5 pb-0.5">
            <Button
              variant="outline"
              size="icon"
              onClick={() => stepPeriod(-1)}
              aria-label="Previous period"
            >
              <ChevronLeft className="size-4" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              onClick={() => stepPeriod(1)}
              aria-label="Next period"
            >
              <ChevronRight className="size-4" />
            </Button>
          </div>

          {data && (
            <p className="pb-2 text-sm font-medium text-muted-foreground ml-auto">
              {data.range.label}
            </p>
          )}
        </div>
      </SectionCard>

      {/* ------------------------------------------- weights (Management only) */}
      {canConfigure && weightsForm && (
        <SectionCard
          title="Weights Configuration"
          className="mb-4"
          action={
            <Button
              size="sm"
              onClick={() => void saveConfig()}
              disabled={!sumOk || !targetOk || savingConfig}
            >
              <Save className="size-4 mr-1.5" />
              {savingConfig ? "Saving..." : "Save Weights"}
            </Button>
          }
        >
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            {WEIGHT_FIELDS.map((f) => (
              <div key={f.key} className="space-y-1.5">
                <Label htmlFor={`w-${f.key}`}>{f.label}</Label>
                <Input
                  id={`w-${f.key}`}
                  type="number"
                  min={0}
                  max={100}
                  value={weightsForm[f.key]}
                  onChange={(e) =>
                    setWeightsForm((form) => (form ? { ...form, [f.key]: e.target.value } : form))
                  }
                />
              </div>
            ))}
            <div className="space-y-1.5">
              <Label htmlFor="w-pieceTarget">Piece Target / month</Label>
              <Input
                id="w-pieceTarget"
                type="number"
                min={1}
                max={500}
                value={weightsForm.pieceTarget}
                onChange={(e) =>
                  setWeightsForm((form) =>
                    form ? { ...form, pieceTarget: e.target.value } : form
                  )
                }
              />
            </div>
          </div>
          <div className="flex flex-wrap items-center justify-between gap-2 mt-4">
            <p className={cn("text-sm font-medium", sumOk ? "text-emerald-700" : "text-red-700")}>
              Total weight: {Math.round(weightSum * 10) / 10}% {sumOk ? "— balanced" : "— must equal 100"}
            </p>
            <p className="text-xs text-muted-foreground">
              Piece target: {config?.pieceTarget ?? "—"} pieces per month for 100% productivity.
            </p>
          </div>
        </SectionCard>
      )}

      {/* -------------------------------------------------------- staff table */}
      {loading && !data ? (
        <div className="space-y-3">
          <Skeleton className="h-10 w-72" />
          <Skeleton className="h-72 w-full" />
        </div>
      ) : error ? (
        <EmptyState
          icon={AlertTriangle}
          title="Could not load performance data"
          description={error}
          action={
            <Button variant="outline" size="sm" onClick={() => void loadPerformance()}>
              Try again
            </Button>
          }
        />
      ) : !data || data.staff.length === 0 ? (
        <EmptyState
          icon={Users}
          title="No staff accounts"
          description="Create staff accounts to start tracking production performance."
        />
      ) : (
        <SectionCard
          title={`Staff Performance — ${data.range.label}`}
          contentClassName="p-0"
          className="mb-4"
        >
          <StaffPerformanceTable staff={data.staff} assessmentCell={assessmentCell} />
          <p className="text-xs text-muted-foreground px-4 py-3 border-t">
            Productivity = pieces completed vs monthly target · Timeliness = on-time share of
            completions · Quality = share of approvals without corrections · Reliability = 100 −
            (blocks + corrections)/assigned · Overall = weighted sum (weights total 100)
          </p>
        </SectionCard>
      )}

      {!loading && data && data.staff.length > 0 && (
        <p className="text-xs text-muted-foreground flex items-center gap-1.5">
          <TrendingUp className="size-3.5" />
          Scores are color coded: emerald 75+, amber 50+, red below 50.
        </p>
      )}
    </div>
  );
}
