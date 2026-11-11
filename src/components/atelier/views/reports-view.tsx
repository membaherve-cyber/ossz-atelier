"use client";

// OSSZ ATELIER — production reports view (supervisors only).
// Monthly / annual summary of registered orders, deliveries, completions and
// on-time rate, plus the full staff performance table for the period.

import { useCallback, useEffect, useState } from "react";
import { api, qs } from "../client";
import type { StaffPerformanceRow } from "../types";
import { EmptyState, PageHeader, SectionCard, StatCard } from "../ui-bits";
import {
  AssessmentReadOnly,
  downloadCsv,
  monthNow,
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
      setError(e instanceof Error ? e.message : "Failed to load the report.");
    } finally {
      setLoading(false);
    }
  }, [rType, value]);

  useEffect(() => {
    void load();
  }, [load]);

  function exportCsv() {
    if (!data) return;
    downloadCsv(`ossz-report-${data.range.value}.csv`, [
      ["OSSZ ATELIER — Production Report"],
      ["Period", data.range.label],
      ["Orders Registered", data.summary.ordersRegistered],
      ["Orders Delivered", data.summary.ordersDelivered],
      ["Tasks Completed", data.summary.tasksCompleted],
      ["Pieces Completed", data.summary.piecesCompleted],
      ["On-Time Completion Rate (%)", data.summary.onTimeCompletionRate],
      [],
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
    toast({
      title: "Report exported",
      description: `ossz-report-${data.range.value}.csv downloaded.`,
    });
  }

  return (
    <div>
      <PageHeader
        title="Production Reports"
        description="Period summary of registered orders, deliveries, completions and staff performance."
      >
        <Button variant="outline" size="sm" onClick={exportCsv} disabled={!data}>
          <Download className="size-4 mr-1.5" />
          Export CSV
        </Button>
      </PageHeader>

      {/* ---------------------------------------------------- period selector */}
      <SectionCard title="Reporting Period" className="mb-4">
        <div className="flex flex-wrap items-end gap-3">
          <div className="space-y-1.5">
            <Label htmlFor="report-type">Report type</Label>
            <Select value={rType} onValueChange={(v) => setRType(v as ReportType)}>
              <SelectTrigger id="report-type" className="w-[170px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="MONTH">Monthly report</SelectItem>
                <SelectItem value="YEAR">Annual report</SelectItem>
              </SelectContent>
            </Select>
          </div>
          {rType === "MONTH" ? (
            <div className="space-y-1.5">
              <Label htmlFor="report-month">Month</Label>
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
              <Label htmlFor="report-year">Year</Label>
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
            <p className="pb-2 text-sm font-medium text-muted-foreground ml-auto">{data.range.label}</p>
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
          title="Could not load the report"
          description={error}
          action={
            <Button variant="outline" size="sm" onClick={() => void load()}>
              Try again
            </Button>
          }
        />
      ) : !data ? null : (
        <>
          {/* ------------------------------------------------- summary cards */}
          <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 mb-4">
            <StatCard
              label="Orders Registered"
              value={data.summary.ordersRegistered}
              hint={data.range.label}
              icon={Package}
            />
            <StatCard
              label="Orders Delivered"
              value={data.summary.ordersDelivered}
              hint="orders with a delivery in period"
              icon={Truck}
              tone="success"
            />
            <StatCard
              label="Tasks Completed"
              value={data.summary.tasksCompleted}
              hint="approved in period"
              icon={CheckCircle2}
              tone="info"
            />
            <StatCard
              label="Pieces Completed"
              value={data.summary.piecesCompleted}
              hint="one task = one garment piece"
              icon={Shirt}
            />
            <StatCard
              label="On-Time Completion"
              value={`${data.summary.onTimeCompletionRate}%`}
              hint="completions before deadline"
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
            title={`Staff Performance — ${data.range.label}`}
            contentClassName="p-0"
            className="mb-4"
          >
            {data.staff.length === 0 ? (
              <EmptyState
                icon={Users}
                title="No staff accounts"
                description="Create staff accounts to include them in production reports."
              />
            ) : (
              <StaffPerformanceTable
                staff={data.staff}
                assessmentCell={(row) => <AssessmentReadOnly row={row} />}
              />
            )}
          </SectionCard>

          <p className="text-xs text-muted-foreground">
            Reports are generated from live production records — assignments, completions and
            inspection results.
          </p>
        </>
      )}
    </div>
  );
}
