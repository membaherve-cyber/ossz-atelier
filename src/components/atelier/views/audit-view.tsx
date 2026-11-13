"use client";

// OSSZ ATELIER — Audit trail browser (Management only). Read-only: entries
// are permanent and can never be edited or deleted.

import { useEffect, useState } from "react";
import { api, ApiClientError, qs } from "../client";
import type { AuditRecord } from "../types";
import { useI18n, type TranslateFn } from "../i18n/use-i18n";
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

/**
 * Bilingual label for an audit action code (see "audit.action.*" entries).
 * Unknown codes — e.g. actions introduced after this build — fall back to
 * the prettified raw code.
 */
function actionDisplay(action: string, t: TranslateFn): string {
  const key = `audit.action.${action}`;
  const label = t(key);
  return label === key ? actionLabel(action) : label;
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
  const { t } = useI18n();
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
        // Empty string marks a generic failure; the translated message is
        // rendered at display time so it follows the active language.
        setError(err instanceof ApiClientError ? err.message : "");
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

  if (error !== null) {
    return (
      <EmptyState
        icon={AlertTriangle}
        title={t("audit.errorTitle")}
        description={error || t("audit.unexpectedError")}
        action={
          <Button variant="outline" size="sm" onClick={retry}>
            {t("common.tryAgain")}
          </Button>
        }
      />
    );
  }

  return (
    <div>
      <PageHeader
        title={t("audit.title")}
        description={t("audit.description")}
      />

      <SectionCard title={t("audit.filters")} className="mb-4">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="audit-action">
              {t("audit.actionFilter")}
            </label>
            <Select value={filters.action} onValueChange={(v) => setFilter({ action: v })}>
              <SelectTrigger id="audit-action" className="w-full">
                <SelectValue placeholder={t("audit.allActions")} />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">{t("audit.allActions")}</SelectItem>
                {(data?.actions ?? []).map((a) => (
                  <SelectItem key={a} value={a}>
                    {actionDisplay(a, t)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="audit-entity">
              {t("audit.entityTypeFilter")}
            </label>
            <Select value={filters.entityType} onValueChange={(v) => setFilter({ entityType: v })}>
              <SelectTrigger id="audit-entity" className="w-full">
                <SelectValue placeholder={t("audit.allTypes")} />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">{t("audit.allTypes")}</SelectItem>
                {ENTITY_TYPES.map((type) => (
                  <SelectItem key={type} value={type}>
                    {type}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="audit-from">
              {t("audit.from")}
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
              {t("audit.to")}
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
            {t("audit.reset")}
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
        <SectionCard title={t("audit.entries")}>
          <EmptyState
            icon={ScrollText}
            title={t("audit.emptyTitle")}
            description={t("audit.emptyDescription")}
          />
        </SectionCard>
      ) : (
        <div className="rounded-lg border overflow-x-auto">
          <Table className="min-w-[900px]">
            <TableHeader>
              <TableRow>
                <TableHead className="w-44">{t("audit.col.date")}</TableHead>
                <TableHead className="w-40">{t("audit.col.user")}</TableHead>
                <TableHead className="w-52">{t("audit.col.action")}</TableHead>
                <TableHead className="w-44">{t("audit.col.entity")}</TableHead>
                <TableHead>{t("audit.col.details")}</TableHead>
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
                      {e.userName ?? t("audit.system")}
                    </TableCell>
                    <TableCell>
                      <Badge
                        variant="outline"
                        className="font-mono text-xs border-stone-300 bg-stone-50 text-stone-700"
                      >
                        {actionDisplay(e.action, t)}
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
            {data.total === 1
              ? t("audit.paginationSingular", { total: data.total, page: data.page, totalPages })
              : t("audit.paginationPlural", { total: data.total, page: data.page, totalPages })}
          </p>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={data.page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              <ChevronLeft className="size-4 mr-1" />
              {t("audit.previous")}
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={data.page >= totalPages}
              onClick={() => setPage((p) => p + 1)}
            >
              {t("audit.next")}
              <ChevronRight className="size-4 ml-1" />
            </Button>
          </div>
        </div>
      )}

    </div>
  );
}
