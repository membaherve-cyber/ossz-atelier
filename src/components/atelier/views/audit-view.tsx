"use client";

// OSSZ ATELIER — Audit trail browser (Management only). Read-only: entries
// are permanent and can never be edited or deleted.

import { useEffect, useState } from "react";
import { api, ApiClientError, qs } from "../client";
import type { AuditRecord } from "../types";
import { EmptyState, PageHeader, SectionCard } from "../ui-bits";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { formatDateTime } from "@/lib/format";
import { AlertTriangle, ChevronLeft, ChevronRight, RotateCcw, ScrollText } from "lucide-react";

const PAGE_SIZE = 30;

/** Entity types written by logAudit() across the codebase. */
const ENTITY_TYPES = [
  "USER",
  "STAFF",
  "CUSTOMER",
  "ORDER",
  "TASK",
  "STAGE",
  "INCENTIVE",
  "INCENTIVE_SCHEME",
  "INCENTIVE_RECORD",
  "PERFORMANCE_CONFIG",
] as const;

type AuditResponse = {
  entries: AuditRecord[];
  total: number;
  page: number;
  pageSize: number;
  actions: string[];
};

type Filters = { action: string; entityType: string; from: string; to: string };

const EMPTY_FILTERS: Filters = { action: "all", entityType: "all", from: "", to: "" };

/** "TASK_ASSIGNED" → "Task_Assigned" (readable, underscores retained). */
function actionLabel(action: string): string {
  return action
    .split("_")
    .map((w) => (w.length > 0 ? w.charAt(0) + w.slice(1).toLowerCase() : w))
    .join("_");
}

/** Pretty-print the raw details JSON for display / tooltip. */
function prettyDetails(raw: string | null): string {
  if (!raw) return "";
  try {
    return JSON.stringify(JSON.parse(raw), null, 2);
  } catch {
    return raw;
  }
}

export function AuditView() {
  const [filters, setFilters] = useState<Filters>(EMPTY_FILTERS);
  const [page, setPage] = useState(1);
  const [reloadKey, setReloadKey] = useState(0);
  const [data, setData] = useState<AuditResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    api
      .get<AuditResponse>(
        `/api/atelier/audit${qs({
          page,
          pageSize: PAGE_SIZE,
          action: filters.action === "all" ? "" : filters.action,
          entityType: filters.entityType === "all" ? "" : filters.entityType,
          from: filters.from,
          to: filters.to,
        })}`
      )
      .then((res) => {
        if (cancelled) return;
        setData(res);
        setError(null);
      })
      .catch((err) => {
        if (cancelled) return;
        setError(err instanceof ApiClientError ? err.message : "Unexpected error.");
      });
    return () => {
      cancelled = true;
    };
  }, [page, filters, reloadKey]);

  const retry = () => setReloadKey((k) => k + 1);

  const setFilter = (patch: Partial<Filters>) => {
    setFilters((f) => ({ ...f, ...patch }));
    setPage(1);
  };

  const reset = () => {
    setFilters(EMPTY_FILTERS);
    setPage(1);
  };

  const totalPages = data ? Math.max(1, Math.ceil(data.total / data.pageSize)) : 1;

  if (error) {
    return (
      <EmptyState
        icon={AlertTriangle}
        title="Could not load the audit trail"
        description={error}
        action={
          <Button variant="outline" size="sm" onClick={retry}>
            Try again
          </Button>
        }
      />
    );
  }

  return (
    <div>
      <PageHeader
        title="Audit History"
        description="Permanent record of all system activity. Entries cannot be edited or deleted."
      />

      <SectionCard title="Filters" className="mb-4">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="audit-action">
              Action
            </label>
            <Select value={filters.action} onValueChange={(v) => setFilter({ action: v })}>
              <SelectTrigger id="audit-action" className="w-full">
                <SelectValue placeholder="All actions" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All actions</SelectItem>
                {(data?.actions ?? []).map((a) => (
                  <SelectItem key={a} value={a}>
                    {actionLabel(a)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="audit-entity">
              Entity type
            </label>
            <Select value={filters.entityType} onValueChange={(v) => setFilter({ entityType: v })}>
              <SelectTrigger id="audit-entity" className="w-full">
                <SelectValue placeholder="All types" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All types</SelectItem>
                {ENTITY_TYPES.map((t) => (
                  <SelectItem key={t} value={t}>
                    {t}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="audit-from">
              From
            </label>
            <Input
              id="audit-from"
              type="date"
              value={filters.from}
              onChange={(e) => setFilter({ from: e.target.value })}
            />
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="audit-to">
              To
            </label>
            <Input
              id="audit-to"
              type="date"
              value={filters.to}
              onChange={(e) => setFilter({ to: e.target.value })}
            />
          </div>

          <Button variant="outline" onClick={reset}>
            <RotateCcw className="size-4 mr-1.5" />
            Reset
          </Button>
        </div>
      </SectionCard>

      {data === null ? (
        <div className="space-y-2">
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-12 w-full" />
          <Skeleton className="h-12 w-full" />
          <Skeleton className="h-12 w-full" />
        </div>
      ) : data.entries.length === 0 ? (
        <SectionCard title="Entries">
          <EmptyState
            icon={ScrollText}
            title="No audit entries match these filters."
            description="Try widening the date range or clearing the action and entity filters."
          />
        </SectionCard>
      ) : (
        <div className="rounded-lg border overflow-x-auto">
          <Table className="min-w-[900px]">
            <TableHeader>
              <TableRow>
                <TableHead className="w-44">Date &amp; Time</TableHead>
                <TableHead className="w-40">User</TableHead>
                <TableHead className="w-52">Action</TableHead>
                <TableHead className="w-44">Entity</TableHead>
                <TableHead>Details</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.entries.map((e) => {
                const details = prettyDetails(e.details);
                return (
                  <TableRow key={e.id}>
                    <TableCell className="text-muted-foreground whitespace-nowrap">
                      {formatDateTime(e.createdAt)}
                    </TableCell>
                    <TableCell className="font-medium whitespace-nowrap">
                      {e.userName ?? "System"}
                    </TableCell>
                    <TableCell>
                      <Badge
                        variant="outline"
                        className="font-mono text-xs border-stone-300 bg-stone-50 text-stone-700"
                      >
                        {actionLabel(e.action)}
                      </Badge>
                    </TableCell>
                    <TableCell className="whitespace-nowrap">
                      {e.entityType ? (
                        <span className="text-sm">
                          {e.entityId ? (
                            <>
                              <span className="font-medium">{e.entityType}</span>{" "}
                              <span className="font-mono text-xs text-muted-foreground">
                                · {e.entityId.slice(0, 8)}
                              </span>
                            </>
                          ) : (
                            <span className="font-medium">{e.entityType}</span>
                          )}
                        </span>
                      ) : (
                        <span className="text-muted-foreground">—</span>
                      )}
                    </TableCell>
                    <TableCell className="max-w-72">
                      {details ? (
                        <span
                          title={details}
                          className="block max-w-72 truncate font-mono text-xs text-muted-foreground"
                        >
                          {details.replace(/\s+/g, " ")}
                        </span>
                      ) : (
                        <span className="text-muted-foreground">—</span>
                      )}
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </div>
      )}

      {data && data.entries.length > 0 && (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4">
          <p className="text-sm text-muted-foreground">
            {data.total} entr{data.total === 1 ? "y" : "ies"} · page {data.page} of {totalPages}
          </p>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={data.page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              <ChevronLeft className="size-4 mr-1" />
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={data.page >= totalPages}
              onClick={() => setPage((p) => p + 1)}
            >
              Next
              <ChevronRight className="size-4 ml-1" />
            </Button>
          </div>
        </div>
      )}

    </div>
  );
}
