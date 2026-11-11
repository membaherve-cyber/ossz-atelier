"use client";

// OSSZ ATELIER — shared visual atoms used across all module views.

import { cn } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import {
  CATEGORY_LABELS,
  PRIORITY_LABELS,
  STATUS_LABELS,
  STAGE_STATE_LABELS,
  type TaskCategory,
  type Priority,
  type TaskStatus,
} from "@/lib/atelier/constants";
import { formatDate } from "@/lib/format";
import { AlertTriangle, CalendarClock, Inbox } from "lucide-react";

// --- Status -------------------------------------------------------------------

const STATUS_STYLES: Record<string, string> = {
  UNASSIGNED: "bg-stone-100 text-stone-700 border-stone-300",
  ASSIGNED: "bg-teal-50 text-teal-800 border-teal-300",
  ONGOING: "bg-amber-50 text-amber-800 border-amber-300",
  AWAITING_INSPECTION: "bg-orange-50 text-orange-800 border-orange-300",
  CHANGES_REQUESTED: "bg-rose-50 text-rose-800 border-rose-300",
  APPROVED: "bg-green-50 text-green-800 border-green-300",
  READY_FOR_PICKUP: "bg-emerald-50 text-emerald-800 border-emerald-300",
  DELIVERED: "bg-stone-800 text-stone-50 border-stone-800",
  BLOCKED: "bg-red-50 text-red-800 border-red-300",
  CANCELLED: "bg-stone-100 text-stone-500 border-stone-300 line-through",
};

export function StatusBadge({ status, className }: { status: string; className?: string }) {
  return (
    <Badge variant="outline" className={cn("font-medium whitespace-nowrap", STATUS_STYLES[status] ?? "", className)}>
      {STATUS_LABELS[status as TaskStatus] ?? status}
    </Badge>
  );
}

// --- Priority -----------------------------------------------------------------

const PRIORITY_STYLES: Record<string, string> = {
  LOW: "bg-stone-50 text-stone-600 border-stone-300",
  NORMAL: "bg-teal-50 text-teal-700 border-teal-300",
  HIGH: "bg-amber-50 text-amber-800 border-amber-300",
  URGENT: "bg-red-50 text-red-800 border-red-300",
};

export function PriorityBadge({ priority, className }: { priority: string; className?: string }) {
  return (
    <Badge variant="outline" className={cn("whitespace-nowrap", PRIORITY_STYLES[priority] ?? "", className)}>
      {PRIORITY_LABELS[priority as Priority] ?? priority}
    </Badge>
  );
}

// --- Category ------------------------------------------------------------------

export function CategoryBadge({ category, className }: { category: string; className?: string }) {
  return (
    <Badge variant="secondary" className={cn("whitespace-nowrap", className)}>
      {CATEGORY_LABELS[category as TaskCategory] ?? category}
    </Badge>
  );
}

// --- Roles -----------------------------------------------------------------------

const ROLE_STYLES: Record<string, string> = {
  MANAGEMENT: "bg-amber-100 text-amber-900 border-amber-400",
  ADMIN: "bg-teal-50 text-teal-800 border-teal-300",
  STAFF: "bg-stone-100 text-stone-700 border-stone-300",
};

export function RoleBadge({ role, className }: { role: string; className?: string }) {
  return (
    <Badge variant="outline" className={cn(ROLE_STYLES[role] ?? "", className)}>
      {role === "MANAGEMENT" ? "Management" : role === "ADMIN" ? "Admin" : "Staff"}
    </Badge>
  );
}

// --- Progress ----------------------------------------------------------------------

export function ProgressCell({
  value,
  status,
  className,
}: {
  value: number;
  status?: string;
  className?: string;
}) {
  const done = value >= 100 || status === "APPROVED" || status === "DELIVERED";
  return (
    <div className={cn("flex items-center gap-2 min-w-28", className)}>
      <Progress
        value={Math.max(0, Math.min(100, value))}
        className={cn("h-2 flex-1", done && "[&>div]:bg-emerald-600")}
      />
      <span className={cn("text-xs font-semibold tabular-nums w-9 text-right", done && "text-emerald-700")}>
        {value}%
      </span>
    </div>
  );
}

// --- Due date ------------------------------------------------------------------------

export function DueDate({
  deadline,
  overdue,
  className,
}: {
  deadline: string | null;
  overdue?: boolean;
  className?: string;
}) {
  if (!deadline) return <span className="text-muted-foreground text-sm">—</span>;
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 text-sm whitespace-nowrap",
        overdue ? "text-red-700 font-semibold" : "text-muted-foreground",
        className
      )}
    >
      {overdue ? <AlertTriangle className="size-3.5" /> : <CalendarClock className="size-3.5 opacity-60" />}
      {formatDate(deadline)}
    </span>
  );
}

// --- Stat card --------------------------------------------------------------------------

export function StatCard({
  label,
  value,
  hint,
  icon: Icon,
  tone = "default",
  onClick,
}: {
  label: string;
  value: number | string;
  hint?: string;
  icon?: React.ComponentType<{ className?: string }>;
  tone?: "default" | "warning" | "danger" | "success" | "info";
  onClick?: () => void;
}) {
  const tones: Record<string, string> = {
    default: "text-foreground",
    warning: "text-amber-700",
    danger: "text-red-700",
    success: "text-emerald-700",
    info: "text-teal-700",
  };
  return (
    <Card
      className={cn(
        "shadow-sm",
        onClick && "cursor-pointer hover:shadow-md hover:border-primary/40 transition-all"
      )}
      onClick={onClick}
    >
      <CardContent className="p-4 flex items-start justify-between gap-2">
        <div className="min-w-0">
          <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide truncate">{label}</p>
          <p className={cn("text-2xl font-bold mt-1 tabular-nums", tones[tone])}>{value}</p>
          {hint && <p className="text-xs text-muted-foreground mt-0.5 truncate">{hint}</p>}
        </div>
        {Icon && (
          <div className="shrink-0 rounded-lg bg-primary/10 p-2">
            <Icon className="size-4 text-primary" />
          </div>
        )}
      </CardContent>
    </Card>
  );
}

// --- Empty state ---------------------------------------------------------------------------

export function EmptyState({
  icon: Icon = Inbox,
  title,
  description,
  action,
}: {
  icon?: React.ComponentType<{ className?: string }>;
  title: string;
  description?: string;
  action?: React.ReactNode;
}) {
  return (
    <div className="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
      <div className="rounded-full bg-muted p-3">
        <Icon className="size-6 text-muted-foreground" />
      </div>
      <p className="font-medium">{title}</p>
      {description && <p className="text-sm text-muted-foreground max-w-sm">{description}</p>}
      {action && <div className="mt-2">{action}</div>}
    </div>
  );
}

// --- Section card -----------------------------------------------------------------------------

export function SectionCard({
  title,
  action,
  children,
  className,
  contentClassName,
}: {
  title: string;
  action?: React.ReactNode;
  children: React.ReactNode;
  className?: string;
  contentClassName?: string;
}) {
  return (
    <Card className={cn("shadow-sm", className)}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between gap-2">
          <CardTitle className="text-base">{title}</CardTitle>
          {action}
        </div>
      </CardHeader>
      <CardContent className={cn(contentClassName)}>{children}</CardContent>
    </Card>
  );
}

// --- Stage state chip ---------------------------------------------------------------------------

const STAGE_STATE_STYLES: Record<string, string> = {
  NOT_STARTED: "bg-stone-100 text-stone-600",
  IN_PROGRESS: "bg-amber-100 text-amber-800",
  COMPLETED: "bg-emerald-100 text-emerald-800",
  NOT_APPLICABLE: "bg-muted text-muted-foreground",
};

export function StageStateChip({ state }: { state: string }) {
  return (
    <span className={cn("rounded-full px-2 py-0.5 text-xs font-medium", STAGE_STATE_STYLES[state] ?? "")}>
      {STAGE_STATE_LABELS[state as never] ?? state}
    </span>
  );
}

// --- Page header ----------------------------------------------------------------------------------

export function PageHeader({
  title,
  description,
  children,
}: {
  title: string;
  description?: string;
  children?: React.ReactNode;
}) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h1 className="font-display text-2xl font-bold tracking-tight">{title}</h1>
        {description && <p className="text-sm text-muted-foreground mt-0.5">{description}</p>}
      </div>
      {children && <div className="flex flex-wrap items-center gap-2">{children}</div>}
    </div>
  );
}
