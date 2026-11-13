"use client";

// OSSZ ATELIER — production reports view (supervisors only).
// Monthly / annual summary of registered orders, deliveries, completions and
// on-time rate, plus the full staff performance table for the period.

import { useCallback, useEffect, useState } from "react";
import { api, qs } from "../client";
import { useI18n } from "../i18n/use-i18n";
import type { StaffPerformanceRow } from "../types";
import { EmptyState, PageHeader, SectionCard, StatCard } from "../ui-bits";
import {
  AssessmentReadOnly,
  downloadCsv,
  monthNow,
  periodLabel,
  StaffPerformanceTable,
  yearNow,
} from "./performance-view";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { useToast } from "@/hooks/use-toast";
import {
  AlertTriangle,
  CheckCircle2,
  Download,
  Package,
  Shirt,
  TrendingUp,
  Truck,
  Users,
} from "lucide-react";

type ReportType = "MONTH" | "YEAR";

type ReportsResponse = {
  range: { type: string; value: string; label: string; months: number };
  staff: StaffPerformanceRow[];
  summary: {
    ordersRegistered: number;
    ordersDelivered: number;
    tasksCompleted: number;
    piecesCompleted: number;
    onTimeCompletionRate: number;
  };
};

export function ReportsView() {
  const { toast } = useToast();
  const { t, lang } = useI18n();

  const [rType, setRType] = useState<ReportType>("MONTH");
  const [monthValue, setMonthValue] = useState(monthNow());
  const [yearValue, setYearValue] = useState(yearNow());
  const value = rType === "MONTH" ? monthValue : yearValue;

  const [data, setData] = useState<ReportsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.get<ReportsResponse>(`/api/atelier/reports${qs({ type: rType, value })}`);
      setData(res);
    } catch (e) {
      setError(e instanceof Error ? e.message : t("rep.loadErrorFallback"));
    } finally {
      setLoading(false);
    }
  }, [rType, value, t]);

  useEffect(() => {
    void load();
  }, [load]);

  function exportCsv() {
    if (!data) return;
    downloadCsv(`ossz-report-${data.range.value}.csv`, [
      [t("rep.csvTitle")],
      [t("rep.csvPeriod"), periodLabel(t, lang, data.range)],
      [t("rep.ordersRegistered"), data.summary.ordersRegistered],
      [t("rep.ordersDelivered"), data.summary.ordersDelivered],
      [t("rep.tasksCompleted"), data.summary.tasksCompleted],
      [t("rep.piecesCompleted"), data.summary.piecesCompleted],
      [t("rep.csvOnTimeRate"), data.summary.onTimeCompletionRate],
      [],
      [
        t("rep.csvStaff"),
        t("rep.csvEmail"),
        t("rep.csvAssigned"),
        t("rep.csvCompleted"),
        t("rep.csvOnTime"),
        t("rep.csvOverdue"),
        t("rep.csvAvgHours"),
        t("rep.csvQualityPct"),
        t("rep.csvCorrections"),
        t("rep.csvBlocked"),
        t("rep.csvProductivity"),
        t("rep.csvTimeliness"),
        t("rep.csvQuality"),
        t("rep.csvReliability"),
        t("rep.csvManagement"),
        t("rep.csvOverall"),
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
    toast({
      title: t("rep.exported"),
      description: t("rep.exportedDesc", { file: `ossz-report-${data.range.value}.csv` }),
    });
  }

  return (
    <div>
      <PageHeader
        title={t("rep.title")}
        description={t("rep.subtitle")}
      >
        <Button variant="outline" size="sm" onClick={exportCsv} disabled={!data}>
          <Download className="size-4 mr-1.5" />
          {t("rep.exportCsv")}
        </Button>
      </PageHeader>

      {/* ---------------------------------------------------- period selector */}
      <SectionCard title={t("rep.reportingPeriod")} className="mb-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1.5">
            <Label htmlFor="report-type">{t("rep.reportType")}</Label>
            <Select value={rType} onValueChange={(v) => setRType(v as ReportType)}>
              <SelectTrigger id="report-type" className="w-[170px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="MONTH">{t("rep.monthlyReport")}</SelectItem>
                <SelectItem value="YEAR">{t("rep.annualReport")}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          {rType === "MONTH" ? (
            <div className="space-y-1.5">
              <Label htmlFor="report-month">{t("rep.month")}</Label>
              <Input
                id="report-month"
                type="month"
                className="w-[170px]"
                value={monthValue}
                onChange={(e) => setMonthValue(e.target.value)}
              />
            </div>
          ) : (
            <div className="space-y-1.5">
              <Label htmlFor="report-year">{t("rep.year")}</Label>
              <Input
                id="report-year"
                type="number"
                className="w-[120px]"
                value={yearValue}
                onChange={(e) => setYearValue(e.target.value)}
              />
            </div>
          )}
          {data && (
            <p className="pb-2 text-sm font-medium text-muted-foreground ml-auto">{periodLabel(t, lang, data.range)}</p>
          )}
        </div>
      </SectionCard>

      {loading && !data ? (
        <div className="space-y-4">
          <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
            {Array.from({ length: 5 }).map((_, i) => (
              <Skeleton key={i} className="h-24" />
            ))}
          </div>
          <Skeleton className="h-64 w-full" />
        </div>
      ) : error ? (
        <EmptyState
          icon={AlertTriangle}
          title={t("rep.loadError")}
          description={error}
          action={
            <Button variant="outline" size="sm" onClick={() => void load()}>
              {t("common.tryAgain")}
            </Button>
          }
        />
      ) : !data ? null : (
        <>
          {/* ------------------------------------------------- summary cards */}
          <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 mb-4">
            <StatCard
              label={t("rep.ordersRegistered")}
              value={data.summary.ordersRegistered}
              hint={periodLabel(t, lang, data.range)}
              icon={Package}
            />
            <StatCard
              label={t("rep.ordersDelivered")}
              value={data.summary.ordersDelivered}
              hint={t("rep.ordersDeliveredHint")}
              icon={Truck}
              tone="success"
            />
            <StatCard
              label={t("rep.tasksCompleted")}
              value={data.summary.tasksCompleted}
              hint={t("rep.tasksCompletedHint")}
              icon={CheckCircle2}
              tone="info"
            />
            <StatCard
              label={t("rep.piecesCompleted")}
              value={data.summary.piecesCompleted}
              hint={t("rep.piecesCompletedHint")}
              icon={Shirt}
            />
            <StatCard
              label={t("rep.onTimeCompletion")}
              value={`${data.summary.onTimeCompletionRate}%`}
              hint={t("rep.onTimeCompletionHint")}
              icon={TrendingUp}
              tone={
                data.summary.onTimeCompletionRate >= 75
                  ? "success"
                  : data.summary.onTimeCompletionRate >= 50
                    ? "warning"
                    : "danger"
              }
            />
          </div>

          {/* -------------------------------------------------- staff table */}
          <SectionCard
            title={t("rep.staffTableTitle", { period: periodLabel(t, lang, data.range) })}
            contentClassName="p-0"
            className="mb-4"
          >
            {data.staff.length === 0 ? (
              <EmptyState
                icon={Users}
                title={t("rep.noStaff")}
                description={t("rep.noStaffDesc")}
              />
            ) : (
              <StaffPerformanceTable
                staff={data.staff}
                assessmentCell={(row) => <AssessmentReadOnly row={row} />}
              />
            )}
          </SectionCard>

          <p className="text-xs text-muted-foreground">
            {t("rep.footnote")}
          </p>
        </>
      )}
    </div>
  );
}
