"use client";

// OSSZ ATELIER — task detail screen: progress & production checklist,
// workflow actions, comments/issues and a merged history timeline.

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import type { ReactNode } from "react";
import { format } from "date-fns";
import type { LucideIcon } from "lucide-react";
import { ApiClientError, api } from "../client";
import {
  inspectionLabel,
  priorityLabel,
  stageStateLabel,
  statusLabel,
  type Lang,
} from "../i18n/core";
import { useI18n, type TranslateFn } from "../i18n/use-i18n";
import { isSupervisor, useAppStore } from "../store";
import type { TaskDetail, UserRecord } from "../types";
import {
  CategoryBadge,
  DueDate,
  EmptyState,
  PriorityBadge,
  ProgressCell,
  SectionCard,
  StageStateChip,
  StatusBadge,
} from "../ui-bits";
import {
  INSPECTION_DECISIONS,
  PRIORITIES,
  STAGE_STATES,
  type StageState,
} from "@/lib/atelier/constants";
import { formatDate, formatDateTime, timeAgo } from "@/lib/format";
import { cn } from "@/lib/utils";
import { useToast } from "@/hooks/use-toast";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
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
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";
import {
  AlertTriangle,
  ArrowLeft,
  ArrowRightLeft,
  Ban,
  ClipboardCheck,
  Inbox,
  ListChecks,
  Loader2,
  MessageSquare,
  Pencil,
  RotateCcw,
  Save,
  ShieldCheck,
  ShoppingBag,
  TrendingUp,
  Truck,
  Undo2,
  UserPlus,
  XCircle,
} from "lucide-react";

// --- Types ---------------------------------------------------------------------

type DialogKind =
  | "assign"
  | "edit"
  | "submit"
  | "inspect"
  | "deliver"
  | "reopen"
  | "block"
  | "unblock"
  | "cancel"
  | null;

type HistoryKind = "assignment" | "status" | "stage" | "progress" | "inspection" | "comment";

type HistoryTab = "all" | "progress" | "checklist" | "status" | "inspections";

type HistoryEntry = {
  id: string;
  kind: HistoryKind;
  createdAt: string;
  userName: string;
  text: string;
  detail?: string | null;
  badge?: string | null;
  icon: LucideIcon;
  iconClass: string;
};

const WORK_STATUSES = ["ASSIGNED", "ONGOING", "CHANGES_REQUESTED"];

/** Selectable checklist stage states, localized on demand. */
function stageStateOptions(lang: Lang): { value: StageState; label: string }[] {
  return STAGE_STATES.map((s) => ({
    value: s,
    label: s === "NOT_APPLICABLE" ? "N/A" : stageStateLabel(s, lang),
  }));
}

/** Display label for a progress-history source (falls back to the raw value). */
function progressSourceLabel(source: string, t: TranslateFn): string {
  const known: Record<string, string> = {
    MANUAL: t("td.source.MANUAL"),
    STAGE: t("td.source.STAGE"),
    INSPECTION: t("td.source.INSPECTION"),
  };
  return known[source] ?? source;
}

/** Convert an ISO timestamp to a `datetime-local` input value (local time). */
function toDatetimeLocal(iso: string | null): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  return format(d, "yyyy-MM-dd'T'HH:mm");
}

function MetaRow({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex items-start justify-between gap-3 py-2">
      <span className="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">
        {label}
      </span>
      <span className="text-sm text-right min-w-0">{children}</span>
    </div>
  );
}

// --- Component -------------------------------------------------------------------

export function TaskDetailView() {
  const { t, lang } = useI18n();
  // Latest translator for stable callbacks — avoids re-creating `load` (and
  // re-fetching the task) when the language changes.
  const tRef = useRef(t);
  useEffect(() => {
    tRef.current = t;
  }, [t]);
  const user = useAppStore((s) => s.user);
  const viewParams = useAppStore((s) => s.viewParams);
  const navigate = useAppStore((s) => s.navigate);
  const { toast } = useToast();
  const taskId = viewParams.taskId;

  const [detail, setDetail] = useState<TaskDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [fatal, setFatal] = useState<string | null>(null);
  const [fetchError, setFetchError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  // Dialog state
  const [dialog, setDialog] = useState<DialogKind>(null);
  const [staffId, setStaffId] = useState("");
  const [note, setNote] = useState("");
  const [reason, setReason] = useState("");
  const [comments, setComments] = useState("");
  const [editForm, setEditForm] = useState({
    garmentPiece: "",
    deadline: "",
    priority: "",
    instructions: "",
  });

  // Progress & checklist state
  const [progressInput, setProgressInput] = useState("");
  const [progressNote, setProgressNote] = useState("");
  const [stageChanges, setStageChanges] = useState<Record<string, StageState>>({});
  const [stageComment, setStageComment] = useState("");

  // Comments & history state
  const [commentText, setCommentText] = useState("");
  const [historyTab, setHistoryTab] = useState<HistoryTab>("all");

  // Staff options for the assign dialog (supervisors only).
  const [staff, setStaff] = useState<UserRecord[]>([]);

  // Reset transient state when switching to another task.
  useEffect(() => {
    setStageChanges({});
    setStageComment("");
    setProgressInput("");
    setProgressNote("");
    setCommentText("");
    setHistoryTab("all");
    setDialog(null);
  }, [taskId]);

  const load = useCallback(
    async (silent = false) => {
      if (!taskId) return;
      if (!silent) setLoading(true);
      try {
        const res = await api.get<TaskDetail>(`/api/atelier/tasks/${taskId}`);
        if (res && res.task) {
          setDetail(res);
          setFatal(null);
          setFetchError(null);
        } else {
          setFatal(tRef.current("td.loadFailed"));
        }
      } catch (e) {
        if (e instanceof ApiClientError && (e.status === 403 || e.status === 404)) {
          setFatal(e.message);
        } else {
          setFetchError(e instanceof Error ? e.message : tRef.current("td.loadFailed"));
        }
      } finally {
        if (!silent) setLoading(false);
      }
    },
    [taskId]
  );

  useEffect(() => {
    load();
  }, [load]);

  // ---- Derived values (hooks must run before any early return) ------------------

  const stages = useMemo(() => {
    return [...(detail?.stages ?? [])].sort((a, b) => a.sortOrder - b.sortOrder);
  }, [detail]);

  const history: HistoryEntry[] = useMemo(() => {
    if (!detail) return [];
    const entries: HistoryEntry[] = [];
    for (const a of detail.assignments) {
      entries.push({
        id: a.id,
        kind: "assignment",
        createdAt: a.createdAt,
        userName: a.assignedByName,
        text:
          a.type === "REASSIGN"
            ? t("td.hist.reassigned", { name: a.staffName })
            : t("td.hist.assigned", { name: a.staffName }),
        detail: a.note,
        icon: UserPlus,
        iconClass: "bg-teal-50 text-teal-700",
      });
    }
    for (const s of detail.statusChanges) {
      entries.push({
        id: s.id,
        kind: "status",
        createdAt: s.createdAt,
        userName: s.userName,
        text: t("td.hist.statusChange", {
          from: statusLabel(s.fromStatus, lang),
          to: statusLabel(s.toStatus, lang),
        }),
        detail: s.reason,
        icon: ArrowRightLeft,
        iconClass: "bg-amber-50 text-amber-700",
      });
    }
    for (const s of detail.stageUpdates) {
      entries.push({
        id: s.id,
        kind: "stage",
        createdAt: s.createdAt,
        userName: s.userName,
        text: t("td.hist.stageChange", {
          stage: s.stageName,
          from: stageStateLabel(s.previousState, lang),
          to: stageStateLabel(s.newState, lang),
        }),
        detail: s.comment,
        icon: ListChecks,
        iconClass: "bg-stone-100 text-stone-600",
      });
    }
    for (const p of detail.progressHistory) {
      entries.push({
        id: p.id,
        kind: "progress",
        createdAt: p.createdAt,
        userName: p.userName,
        text: t("td.hist.progress", { from: p.previousProgress, to: p.newProgress }),
        detail: p.note,
        badge: progressSourceLabel(p.source, t),
        icon: TrendingUp,
        iconClass: "bg-emerald-50 text-emerald-700",
      });
    }
    for (const i of detail.inspections) {
      entries.push({
        id: i.id,
        kind: "inspection",
        createdAt: i.createdAt,
        userName: i.inspectorName,
        text: t("td.hist.inspection", {
          decision: (INSPECTION_DECISIONS as readonly string[]).includes(i.decision)
            ? inspectionLabel(i.decision, lang)
            : i.decision,
        }),
        detail: i.comments,
        icon: ShieldCheck,
        iconClass:
          i.decision === "APPROVED"
            ? "bg-emerald-50 text-emerald-700"
            : i.decision === "CHANGES_REQUESTED"
              ? "bg-amber-50 text-amber-700"
              : "bg-stone-100 text-stone-600",
      });
    }
    for (const c of detail.comments) {
      entries.push({
        id: c.id,
        kind: "comment",
        createdAt: c.createdAt,
        userName: c.userName,
        text: c.isIssue ? t("td.hist.issue") : t("td.hist.note"),
        detail: c.message,
        icon: MessageSquare,
        iconClass: c.isIssue ? "bg-red-50 text-red-700" : "bg-stone-100 text-stone-600",
      });
    }
    return entries.sort(
      (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    );
  }, [detail, t, lang]);

  const url = taskId ? `/api/atelier/tasks/${taskId}` : "";

  /** Run a workflow mutation, refresh the detail and toast the outcome. */
  const runAction = useCallback(
    async (label: string, fn: () => Promise<unknown>) => {
      setBusy(true);
      try {
        await fn();
        await load(true);
        setDialog(null);
        toast({ title: label });
      } catch (e) {
        toast({
          title: t("td.actionFailed"),
          description: e instanceof Error ? e.message : t("td.unexpectedError"),
          variant: "destructive",
        });
      } finally {
        setBusy(false);
      }
    },
    [load, toast, t]
  );

  function openDialog(kind: Exclude<DialogKind, null>) {
    if (!detail) return;
    // Never prefill the current assignee — the server rejects no-op assignments.
    setStaffId("");
    setNote("");
    setReason("");
    setComments("");
    if (kind === "edit") {
      setEditForm({
        garmentPiece: detail.task.garmentPiece,
        deadline: toDatetimeLocal(detail.task.deadline),
        priority: detail.task.priority,
        instructions: detail.task.instructions ?? "",
      });
    }
    setDialog(kind);
    if (kind === "assign" && isSupervisor(user) && staff.length === 0) {
      api
        .get<{ users: UserRecord[] }>("/api/atelier/users?role=STAFF")
        .then((res) => setStaff(res.users.filter((u) => u.isActive)))
        .catch(() => {
          // Options stay empty; assignment can be retried later.
        });
    }
  }

  // ---- Guards ------------------------------------------------------------------

  if (!taskId) {
    return (
      <EmptyState
        title={t("td.noTask.title")}
        description={t("td.noTask.desc")}
        action={
          <Button variant="outline" size="sm" onClick={() => navigate("tasks")}>
            <ArrowLeft className="size-4" />
            {t("td.goToTasks")}
          </Button>
        }
      />
    );
  }

  if (fatal) {
    return (
      <EmptyState
        icon={AlertTriangle}
        title={t("td.taskUnavailable")}
        description={fatal}
        action={
          <Button variant="outline" size="sm" onClick={() => navigate("tasks")}>
            <ArrowLeft className="size-4" />
            {t("td.backToTasks")}
          </Button>
        }
      />
    );
  }

  if (loading && !detail) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-36" />
        <Skeleton className="h-10 w-2/3" />
        <Skeleton className="h-6 w-1/2" />
        <div className="grid lg:grid-cols-3 gap-4">
          <div className="lg:col-span-2 space-y-4">
            <Skeleton className="h-80" />
            <Skeleton className="h-64" />
          </div>
          <div className="space-y-4">
            <Skeleton className="h-72" />
            <Skeleton className="h-56" />
          </div>
        </div>
      </div>
    );
  }

  if (!detail) {
    return (
      <EmptyState
        icon={AlertTriangle}
        title={t("td.couldNotLoad")}
        description={fetchError ?? t("td.unknownError")}
        action={
          <Button variant="outline" size="sm" onClick={() => load()}>
            {t("common.tryAgain")}
          </Button>
        }
      />
    );
  }

  // ---- Derived values ------------------------------------------------------------

  const task = detail.task;
  const supervisor = isSupervisor(user);
  const isAssignee = !!user && task.assignedStaffId === user.id;
  const terminal = task.status === "DELIVERED" || task.status === "CANCELLED";
  const inWork = WORK_STATUSES.includes(task.status);

  const mayUpdateProgress = (supervisor || isAssignee) && inWork;
  const mayEditChecklist =
    (supervisor && !terminal) || (isAssignee && inWork);
  const mayComment = (supervisor || isAssignee) && !terminal;

  const canAssign = supervisor && !terminal;
  const canEdit = supervisor && !terminal;
  const canSubmit = (supervisor || isAssignee) && inWork;
  const staffSubmitBlocked = isAssignee && !supervisor && task.progress < 100;
  const canInspect = supervisor && task.status === "AWAITING_INSPECTION";
  const canReady = supervisor && task.status === "APPROVED";
  const canDeliver = supervisor && task.status === "READY_FOR_PICKUP";
  const canReopen = supervisor && (task.status === "APPROVED" || task.status === "READY_FOR_PICKUP");
  const canBlock = (supervisor || isAssignee) && inWork;
  const canUnblock = (supervisor || isAssignee) && task.status === "BLOCKED";
  const canCancel = supervisor && !terminal;
  const noActions =
    !canAssign &&
    !canEdit &&
    !canSubmit &&
    !canInspect &&
    !canReady &&
    !canDeliver &&
    !canReopen &&
    !canBlock &&
    !canUnblock &&
    !canCancel;

  const hasWeights = stages.some((s) => s.weight > 1);
  const changesCount = Object.keys(stageChanges).length;

  const progressValue = Number(progressInput);
  const progressValid =
    progressInput !== "" &&
    Number.isInteger(progressValue) &&
    progressValue >= 0 &&
    progressValue <= 100 &&
    progressValue !== task.progress;

  // ---- History timeline filter ------------------------------------------------------------

  const filteredHistory =
    historyTab === "all"
      ? history
      : history.filter((e) => {
          if (historyTab === "progress") return e.kind === "progress";
          if (historyTab === "checklist") return e.kind === "stage";
          if (historyTab === "status") return e.kind === "status";
          return e.kind === "inspection";
        });

  // ---- Mutations ---------------------------------------------------------------------

  function requireStaff() {
    if (!staffId) {
      toast({
        title: t("td.toast.selectStaff"),
        description: t("td.toast.selectStaffDesc"),
        variant: "destructive",
      });
      return false;
    }
    return true;
  }

  function requireReason(field: string, min = 3) {
    if (reason.trim().length < min) {
      toast({
        title: t("td.toast.reasonRequired"),
        description: t("td.toast.reasonRequiredDesc", { field, min }),
        variant: "destructive",
      });
      return false;
    }
    return true;
  }

  const doAssign = () => {
    if (!requireStaff()) return;
    const target = staff.find((s) => s.id === staffId);
    runAction(
      task.assignedStaffId
        ? t("td.toast.reassignedTo", { name: target?.name ?? t("td.staffMember") })
        : t("td.toast.assignedTo", { name: target?.name ?? t("td.staffMember") }),
      () =>
        api.patch(url, {
          action: "ASSIGN",
          staffId,
          note: note.trim() || undefined,
        })
    );
  };

  const doEdit = () => {
    const piece = editForm.garmentPiece.trim();
    if (!piece) {
      toast({ title: t("td.toast.pieceRequired"), variant: "destructive" });
      return;
    }
    const body: Record<string, unknown> = { action: "UPDATE" };
    const deadlineIso = editForm.deadline ? new Date(editForm.deadline).toISOString() : null;
    if (deadlineIso !== task.deadline) body.deadline = deadlineIso;
    if (editForm.priority !== task.priority) body.priority = editForm.priority;
    if (editForm.instructions.trim() !== (task.instructions ?? "")) {
      body.instructions = editForm.instructions.trim();
    }
    if (piece !== task.garmentPiece) body.garmentPiece = piece;
    if (Object.keys(body).length === 1) {
      toast({
        title: t("td.toast.nothingToUpdate"),
        description: t("td.toast.nothingToUpdateDesc"),
      });
      return;
    }
    runAction(t("td.toast.detailsUpdated"), () => api.patch(url, body));
  };

  const doSubmit = () => {
    runAction(t("td.toast.submitted"), () =>
      api.patch(url, { action: "SUBMIT", note: note.trim() || undefined })
    );
  };

  const doApprove = () => {
    runAction(t("td.toast.inspectionApproved"), () =>
      api.patch(url, {
        action: "INSPECT",
        decision: "APPROVED",
        comments: comments.trim() || undefined,
      })
    );
  };

  const doRequestChanges = () => {
    if (comments.trim().length < 3) {
      toast({
        title: t("td.toast.correctionsRequired"),
        description: t("td.toast.correctionsRequiredDesc"),
        variant: "destructive",
      });
      return;
    }
    runAction(t("td.toast.correctionsRequested"), () =>
      api.patch(url, {
        action: "INSPECT",
        decision: "CHANGES_REQUESTED",
        comments: comments.trim(),
      })
    );
  };

  const doReady = () => {
    runAction(t("td.toast.ready"), () => api.patch(url, { action: "READY" }));
  };

  const doDeliver = () => {
    runAction(t("td.toast.delivered"), () =>
      api.patch(url, { action: "DELIVER", note: note.trim() || undefined })
    );
  };

  const doReopen = () => {
    if (comments.trim().length < 3) {
      toast({
        title: t("td.toast.reopenCommentsRequired"),
        description: t("td.toast.reopenCommentsRequiredDesc"),
        variant: "destructive",
      });
      return;
    }
    runAction(t("td.toast.reopened"), () =>
      api.patch(url, { action: "INSPECT", decision: "REOPENED", comments: comments.trim() })
    );
  };

  const doBlock = () => {
    if (!requireReason(t("td.reason.block"))) return;
    runAction(t("td.toast.blocked"), () =>
      api.patch(url, { action: "BLOCK", reason: reason.trim() })
    );
  };

  const doUnblock = () => {
    runAction(t("td.toast.unblocked"), () =>
      api.patch(url, { action: "UNBLOCK", note: note.trim() || undefined })
    );
  };

  const doCancel = () => {
    if (!requireReason(t("td.reason.cancel"))) return;
    runAction(t("td.toast.cancelled"), () =>
      api.patch(url, { action: "CANCEL", reason: reason.trim() })
    );
  };

  const doProgressUpdate = async () => {
    if (!progressValid) return;
    setBusy(true);
    try {
      await api.post(`${url}/progress`, {
        progress: progressValue,
        note: progressNote.trim() || null,
      });
      setProgressInput("");
      setProgressNote("");
      await load(true);
      toast({ title: t("td.toast.progressUpdated", { value: progressValue }) });
    } catch (e) {
      toast({
        title: t("td.toast.progressFailed"),
        description: e instanceof Error ? e.message : t("td.unexpectedError"),
        variant: "destructive",
      });
    } finally {
      setBusy(false);
    }
  };

  const saveChecklist = async () => {
    const updates = Object.entries(stageChanges).map(([stageId, state]) => ({
      stageId,
      state,
      comment: stageComment.trim() || null,
    }));
    if (updates.length === 0) return;
    setBusy(true);
    try {
      await api.post(`${url}/stages`, { updates });
      setStageChanges({});
      setStageComment("");
      await load(true);
      toast({
        title: t("td.toast.checklistUpdated"),
        description: t("td.toast.checklistUpdatedDesc"),
      });
    } catch (e) {
      toast({
        title: t("td.toast.checklistFailed"),
        description: e instanceof Error ? e.message : t("td.unexpectedError"),
        variant: "destructive",
      });
    } finally {
      setBusy(false);
    }
  };

  const postComment = async (isIssue: boolean) => {
    const message = commentText.trim();
    if (!message) {
      toast({
        title: t("td.toast.emptyComment"),
        description: isIssue ? t("td.toast.emptyCommentIssue") : t("td.toast.emptyCommentNote"),
        variant: "destructive",
      });
      return;
    }
    setBusy(true);
    try {
      await api.post(`${url}/comments`, { message, isIssue });
      setCommentText("");
      await load(true);
      toast({
        title: isIssue ? t("td.toast.issueReported") : t("td.toast.noteAdded"),
        description: isIssue ? t("td.toast.issueReportedDesc") : undefined,
      });
    } catch (e) {
      toast({
        title: t("td.toast.commentFailed"),
        description: e instanceof Error ? e.message : t("td.unexpectedError"),
        variant: "destructive",
      });
    } finally {
      setBusy(false);
    }
  };

  const dialogOpen = (kind: Exclude<DialogKind, null>) => dialog === kind;
  const closeDialog = (open: boolean) => {
    if (!open && !busy) setDialog(null);
  };

  // ---- Render ---------------------------------------------------------------------

  return (
    <div>
      <Button variant="ghost" size="sm" className="-ml-2 mb-2" onClick={() => navigate("tasks")}>
        <ArrowLeft className="size-4" />
        {t("td.backToTasks")}
      </Button>

      {/* ---- Header ---- */}
      <div className="mb-4">
        <div className="flex flex-col lg:flex-row lg:items-start justify-between gap-3">
          <div className="min-w-0">
            <h1 className="font-display text-2xl font-bold tracking-tight truncate">
              {task.code} — {task.garmentPiece}
            </h1>
            <div className="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-1.5 text-sm text-muted-foreground">
              <span>
                {task.orderNumber} · {task.customerName}
              </span>
              <span>
                {t("td.assigneeColon")}{" "}
                <span className="text-foreground font-medium">
                  {task.assignedStaffName ?? t("td.unassigned")}
                </span>
              </span>
              <DueDate deadline={task.deadline} overdue={task.overdue} />
              <span>{t("td.createdOn", { date: formatDate(task.createdAt) })}</span>
            </div>
          </div>
          <div className="flex flex-wrap gap-1.5 shrink-0">
            <StatusBadge status={task.status} />
            <PriorityBadge priority={task.priority} />
            <CategoryBadge category={task.category} />
          </div>
        </div>

        {task.status === "BLOCKED" && task.blockedReason && (
          <Alert variant="destructive" className="mt-3">
            <AlertTriangle />
            <AlertTitle>{t("td.blockedAlert")}</AlertTitle>
            <AlertDescription className="text-destructive/90">
              {task.blockedReason}
            </AlertDescription>
          </Alert>
        )}
        {task.status === "CANCELLED" && (
          <Alert className="mt-3 border-stone-300 bg-stone-50 text-stone-700">
            <Ban />
            <AlertTitle>{t("td.cancelledAlert")}</AlertTitle>
            <AlertDescription className="text-stone-600">
              {task.cancelReason ?? t("td.cancelledFallback")}
            </AlertDescription>
          </Alert>
        )}
        {task.status === "CHANGES_REQUESTED" && (
          <Alert className="mt-3 border-amber-300 bg-amber-50 text-amber-800">
            <AlertTriangle />
            <AlertTitle>{t("td.correctionsAlert")}</AlertTitle>
            <AlertDescription className="text-amber-700">
              {t("td.correctionsAlertDesc")}
            </AlertDescription>
          </Alert>
        )}
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        {/* ---- Progress & checklist (left) ---- */}
        <SectionCard title={t("td.progressChecklist")} className="lg:col-span-2">
          <div className="space-y-4">
            <ProgressCell value={task.progress} status={task.status} />
            <p className="text-xs text-muted-foreground -mt-2">
              {t("td.currentProgress")} <span className="font-semibold">{task.progress}%</span>
            </p>

            {mayUpdateProgress && (
              <div className="rounded-lg border bg-muted/30 p-3 space-y-2">
                <p className="text-sm font-medium">{t("td.updateProgressManually")}</p>
                <div className="flex flex-col sm:flex-row gap-2">
                  <Input
                    type="number"
                    min={0}
                    max={100}
                    step={1}
                    className="sm:w-28"
                    placeholder={`${task.progress}`}
                    value={progressInput}
                    onChange={(e) => setProgressInput(e.target.value)}
                    aria-label={t("td.aria.newProgress")}
                  />
                  <Input
                    className="flex-1"
                    placeholder={t("td.optionalNote")}
                    value={progressNote}
                    onChange={(e) => setProgressNote(e.target.value)}
                    aria-label={t("td.aria.progressNote")}
                  />
                  <Button disabled={!progressValid || busy} onClick={doProgressUpdate}>
                    {t("td.update")}
                  </Button>
                </div>
                {supervisor && (
                  <p className="text-xs text-muted-foreground">
                    {t("td.manualProgressNote", { source: t("td.source.MANUAL") })}
                  </p>
                )}
              </div>
            )}

            <Separator />

            <div>
              <div className="flex items-center justify-between gap-2">
                <p className="text-sm font-medium">{t("td.checklistTitle")}</p>
                {changesCount > 0 && (
                  <Badge variant="outline" className="border-amber-300 bg-amber-50 text-amber-800">
                    {changesCount === 1
                      ? t("td.pendingChange")
                      : t("td.pendingChanges", { count: changesCount })}
                  </Badge>
                )}
              </div>
              <p className="text-xs text-muted-foreground mt-0.5">
                {t("td.checklistHelp", {
                  weights: hasWeights ? t("td.checklistHelpWeights") : "",
                })}
              </p>

              <div className="mt-2 divide-y rounded-lg border">
                {stages.length === 0 ? (
                  <EmptyState
                    icon={Inbox}
                    title={t("td.noStages.title")}
                    description={t("td.noStages.desc")}
                  />
                ) : (
                  stages.map((s) => {
                    const pending = stageChanges[s.id];
                    const effective = pending ?? s.state;
                    return (
                      <div
                        key={s.id}
                        className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-4 py-3"
                      >
                        <div className="min-w-0">
                          <p className="text-sm font-medium flex items-center gap-2 flex-wrap">
                            {s.name}
                            {s.weight > 1 && (
                              <Badge variant="secondary" className="text-xs">
                                {t("td.stageWeight", { weight: s.weight })}
                              </Badge>
                            )}
                          </p>
                          <div className="mt-1 flex items-center gap-2">
                            <StageStateChip state={effective} />
                            {pending && pending !== s.state && (
                              <span className="text-xs text-amber-700">
                                {t("td.wasState", { state: stageStateLabel(s.state, lang) })}
                              </span>
                            )}
                          </div>
                        </div>
                        {mayEditChecklist ? (
                          <div
                            className="flex flex-wrap gap-1 shrink-0"
                            role="group"
                            aria-label={t("td.aria.stateFor", { stage: s.name })}
                          >
                            {stageStateOptions(lang).map((o) => {
                              const active = effective === o.value;
                              return (
                                <button
                                  key={o.value}
                                  type="button"
                                  aria-pressed={active}
                                  aria-label={t("td.aria.setState", {
                                    stage: s.name,
                                    state: o.label,
                                  })}
                                  onClick={() =>
                                    setStageChanges((prev) => {
                                      const next = { ...prev };
                                      if (o.value === s.state) {
                                        // Clicking the saved state reverts any pending change.
                                        delete next[s.id];
                                      } else {
                                        next[s.id] = o.value;
                                      }
                                      return next;
                                    })
                                  }
                                  className={cn(
                                    "h-8 px-2.5 rounded-md text-xs font-medium border transition-colors",
                                    active
                                      ? "bg-primary text-primary-foreground border-primary"
                                      : "bg-background text-muted-foreground border-input hover:bg-accent hover:text-foreground"
                                  )}
                                >
                                  {o.label}
                                </button>
                              );
                            })}
                          </div>
                        ) : null}
                      </div>
                    );
                  })
                )}
              </div>

              {mayEditChecklist && stages.length > 0 && (
                <div className="mt-3 space-y-2">
                  <Input
                    placeholder={t("td.stageCommentPlaceholder")}
                    value={stageComment}
                    onChange={(e) => setStageComment(e.target.value)}
                    aria-label={t("td.aria.stageComment")}
                  />
                  <div className="flex justify-end">
                    <Button
                      size="sm"
                      disabled={changesCount === 0 || busy}
                      onClick={saveChecklist}
                    >
                      {busy ? (
                        <Loader2 className="size-4 animate-spin" />
                      ) : (
                        <Save className="size-4" />
                      )}
                      {t("td.saveChecklist")}
                      {changesCount > 0 ? ` (${changesCount})` : ""}
                    </Button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </SectionCard>

        {/* ---- Actions (right) ---- */}
        <SectionCard title={t("td.workflowActions")}>
          <div className="space-y-2">
            {canSubmit && (
              <div>
                <Button
                  className="w-full"
                  disabled={busy || staffSubmitBlocked}
                  onClick={() => openDialog("submit")}
                >
                  <ClipboardCheck className="size-4" />
                  {t("td.submitForInspection")}
                </Button>
                {staffSubmitBlocked && (
                  <p className="text-xs text-muted-foreground mt-1">{t("td.reach100")}</p>
                )}
              </div>
            )}
            {canInspect && (
              <Button className="w-full" disabled={busy} onClick={() => openDialog("inspect")}>
                <ShieldCheck className="size-4" />
                {t("td.inspectResult")}
              </Button>
            )}
            {canReady && (
              <Button className="w-full" disabled={busy} onClick={doReady}>
                <ShoppingBag className="size-4" />
                {t("td.markReady")}
              </Button>
            )}
            {canDeliver && (
              <Button className="w-full" disabled={busy} onClick={() => openDialog("deliver")}>
                <Truck className="size-4" />
                {t("td.markDelivered")}
              </Button>
            )}

            {(canAssign || canEdit) && <Separator className="my-3" />}

            {canAssign && (
              <Button
                variant="outline"
                className="w-full"
                disabled={busy}
                onClick={() => openDialog("assign")}
              >
                <UserPlus className="size-4" />
                {task.assignedStaffId ? t("td.reassignStaff") : t("td.assignStaff")}
              </Button>
            )}
            {canEdit && (
              <Button
                variant="outline"
                className="w-full"
                disabled={busy}
                onClick={() => openDialog("edit")}
              >
                <Pencil className="size-4" />
                {t("td.editDetails")}
              </Button>
            )}

            {(canBlock || canUnblock || canReopen || canCancel) && <Separator className="my-3" />}

            {canBlock && (
              <Button
                variant="outline"
                className="w-full border-amber-300 text-amber-800 hover:bg-amber-50 hover:text-amber-900"
                disabled={busy}
                onClick={() => openDialog("block")}
              >
                <Ban className="size-4" />
                {t("td.blockTask")}
              </Button>
            )}
            {canUnblock && (
              <Button
                variant="outline"
                className="w-full"
                disabled={busy}
                onClick={() => openDialog("unblock")}
              >
                <RotateCcw className="size-4" />
                {t("td.unblockTask")}
              </Button>
            )}
            {canReopen && (
              <Button
                variant="outline"
                className="w-full"
                disabled={busy}
                onClick={() => openDialog("reopen")}
              >
                <Undo2 className="size-4" />
                {t("td.reopenTask")}
              </Button>
            )}
            {canCancel && (
              <Button
                variant="outline"
                className="w-full border-destructive/40 text-destructive hover:bg-destructive/10 hover:text-destructive"
                disabled={busy}
                onClick={() => openDialog("cancel")}
              >
                <XCircle className="size-4" />
                {t("td.cancelTask")}
              </Button>
            )}

            {noActions && (
              <p className="text-sm text-muted-foreground">
                {t("td.noActions")}
                {terminal ? t("td.noActionsClosed") : ""}
              </p>
            )}
          </div>
        </SectionCard>

        {/* ---- Comments & issues (left) ---- */}
        <SectionCard
          title={t("td.commentsIssues")}
          className="lg:col-span-2"
          action={
            <span className="text-xs text-muted-foreground">
              {t("td.commentsTotal", { count: detail.comments.length })}
            </span>
          }
        >
          <div className="space-y-4">
            {detail.comments.length === 0 ? (
              <EmptyState
                icon={MessageSquare}
                title={t("td.noComments.title")}
                description={t("td.noComments.desc")}
              />
            ) : (
              <ScrollArea className="max-h-96 -mx-1 px-1">
                <div className="space-y-3">
                  {detail.comments.map((c) => (
                    <div
                      key={c.id}
                      className={cn(
                        "rounded-lg border p-3",
                        c.isIssue ? "border-l-4 border-l-red-400 bg-red-50/50" : "bg-card"
                      )}
                    >
                      <div className="flex items-center justify-between gap-2">
                        <p className="text-sm font-medium flex items-center gap-1.5 min-w-0">
                          {c.isIssue && <AlertTriangle className="size-3.5 text-red-600 shrink-0" />}
                          <span className="truncate">{c.userName}</span>
                        </p>
                        <span
                          className="text-xs text-muted-foreground shrink-0"
                          title={formatDateTime(c.createdAt)}
                        >
                          {timeAgo(c.createdAt)}
                        </span>
                      </div>
                      <p className="text-sm text-muted-foreground mt-1 whitespace-pre-wrap break-words">
                        {c.message}
                      </p>
                    </div>
                  ))}
                </div>
              </ScrollArea>
            )}

            {mayComment && (
              <div className="space-y-2">
                <Textarea
                  placeholder={t("td.commentPlaceholder")}
                  value={commentText}
                  onChange={(e) => setCommentText(e.target.value)}
                  aria-label={t("td.aria.newComment")}
                />
                <div className="flex flex-col sm:flex-row sm:justify-end gap-2">
                  <Button variant="outline" size="sm" disabled={busy} onClick={() => postComment(false)}>
                    <MessageSquare className="size-4" />
                    {t("td.addNote")}
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    className="border-destructive/40 text-destructive hover:bg-destructive/10 hover:text-destructive"
                    disabled={busy}
                    onClick={() => postComment(true)}
                  >
                    <AlertTriangle className="size-4" />
                    {t("td.reportIssue")}
                  </Button>
                </div>
              </div>
            )}
          </div>
        </SectionCard>

        {/* ---- Details (right) ---- */}
        <SectionCard title={t("td.taskDetails")}>
          <div className="divide-y">
            <MetaRow label={t("td.meta.order")}>
              <span className="font-medium">
                {task.orderNumber} — {task.customerName}
              </span>
            </MetaRow>
            <MetaRow label={t("td.meta.piece")}>
              <span className="font-medium">{task.garmentPiece}</span>
            </MetaRow>
            <MetaRow label={t("td.meta.assignee")}>
              {task.assignedStaffName ?? (
                <span className="text-muted-foreground">{t("td.unassigned")}</span>
              )}
            </MetaRow>
            <MetaRow label={t("td.meta.category")}>
              <CategoryBadge category={task.category} />
            </MetaRow>
            <MetaRow label={t("td.meta.priority")}>
              <PriorityBadge priority={task.priority} />
            </MetaRow>
            <MetaRow label={t("td.meta.deadline")}>
              <DueDate deadline={task.deadline} overdue={task.overdue} />
            </MetaRow>
            <MetaRow label={t("td.meta.created")}>{formatDate(task.createdAt)}</MetaRow>
            <MetaRow label={t("td.meta.completed")}>
              {task.completedAt ? formatDate(task.completedAt) : "—"}
            </MetaRow>
            <MetaRow label={t("td.meta.delivered")}>
              {task.deliveredAt ? formatDate(task.deliveredAt) : "—"}
            </MetaRow>
          </div>
          {task.instructions && (
            <div className="mt-3">
              <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                {t("td.instructions")}
              </p>
              <p className="text-sm mt-1 rounded-lg border bg-muted/30 p-3 whitespace-pre-wrap">
                {task.instructions}
              </p>
            </div>
          )}
        </SectionCard>

        {/* ---- History (full width) ---- */}
        <SectionCard
          title={t("td.history")}
          className="lg:col-span-3"
          action={
            <span className="text-xs text-muted-foreground">
              {t("td.eventsCount", { count: history.length })}
            </span>
          }
        >
          <Tabs value={historyTab} onValueChange={(v) => setHistoryTab(v as HistoryTab)}>
            <TabsList className="flex-wrap h-auto">
              <TabsTrigger value="all" className="h-8">
                {t("common.all")}
              </TabsTrigger>
              <TabsTrigger value="progress" className="h-8">
                {t("td.tab.progress")}
              </TabsTrigger>
              <TabsTrigger value="checklist" className="h-8">
                {t("td.tab.checklist")}
              </TabsTrigger>
              <TabsTrigger value="status" className="h-8">
                {t("td.tab.status")}
              </TabsTrigger>
              <TabsTrigger value="inspections" className="h-8">
                {t("td.tab.inspections")}
              </TabsTrigger>
            </TabsList>
            <TabsContent value={historyTab} className="mt-2">
              {filteredHistory.length === 0 ? (
                <EmptyState icon={Inbox} title={t("td.noEvents")} />
              ) : (
                <ScrollArea className="max-h-96 rounded-lg border">
                  <div className="divide-y px-4 py-1">
                    {filteredHistory.map((e) => (
                      <div key={e.id} className="flex gap-3 py-2.5">
                        <div
                          className={cn(
                            "rounded-full p-1.5 shrink-0 mt-0.5",
                            e.iconClass
                          )}
                        >
                          <e.icon className="size-3.5" />
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="text-sm leading-snug">
                            <span className="font-medium">{e.userName}</span>{" "}
                            <span className="text-muted-foreground">{e.text}</span>
                            {e.badge && (
                              <Badge
                                variant="outline"
                                className="ml-1.5 text-[10px] px-1.5 py-0 align-middle"
                              >
                                {e.badge}
                              </Badge>
                            )}
                          </p>
                          {e.detail && (
                            <p className="text-xs text-muted-foreground mt-0.5 whitespace-pre-wrap break-words">
                              {e.detail}
                            </p>
                          )}
                          <p
                            className="text-xs text-muted-foreground/80 mt-0.5"
                            title={formatDateTime(e.createdAt)}
                          >
                            {timeAgo(e.createdAt)}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                </ScrollArea>
              )}
            </TabsContent>
          </Tabs>
        </SectionCard>
      </div>

      {/* ---- Dialogs ---- */}

      {/* Assign / Reassign */}
      <Dialog open={dialogOpen("assign")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {task.assignedStaffId ? t("td.dialog.reassignTitle") : t("td.dialog.assignTitle")}
            </DialogTitle>
            <DialogDescription>
              {t("td.dialog.assignDesc", { piece: task.garmentPiece, code: task.code })}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label>{t("td.dialog.staffMember")}</Label>
              <Select value={staffId} onValueChange={setStaffId}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder={t("td.dialog.selectStaff")} />
                </SelectTrigger>
                <SelectContent>
                  {staff.length === 0 && (
                    <SelectItem value="loading" disabled>
                      {t("td.dialog.noStaff")}
                    </SelectItem>
                  )}
                  {staff.map((u) => (
                    <SelectItem key={u.id} value={u.id} disabled={u.id === task.assignedStaffId}>
                      {u.name}
                      {u.activeTaskCount != null
                        ? ` ${t("td.dialog.activeTasks", { count: u.activeTaskCount })}`
                        : ""}
                      {u.id === task.assignedStaffId ? t("td.dialog.currentAssignee") : ""}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="assign-note">{t("td.dialog.noteOptional")}</Label>
              <Input
                id="assign-note"
                placeholder={t("td.dialog.assignNotePlaceholder")}
                value={note}
                onChange={(e) => setNote(e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              {t("common.cancel")}
            </Button>
            <Button disabled={busy || !staffId} onClick={doAssign}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              {task.assignedStaffId ? t("td.dialog.reassignAction") : t("td.dialog.assignAction")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit details */}
      <Dialog open={dialogOpen("edit")} onOpenChange={closeDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>{t("td.dialog.editTitle")}</DialogTitle>
            <DialogDescription>{t("td.dialog.editDesc")}</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label htmlFor="edit-piece">{t("td.meta.piece")}</Label>
              <Input
                id="edit-piece"
                value={editForm.garmentPiece}
                onChange={(e) => setEditForm((f) => ({ ...f, garmentPiece: e.target.value }))}
              />
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label htmlFor="edit-deadline">{t("td.meta.deadline")}</Label>
                <Input
                  id="edit-deadline"
                  type="datetime-local"
                  value={editForm.deadline}
                  onChange={(e) => setEditForm((f) => ({ ...f, deadline: e.target.value }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label>{t("td.meta.priority")}</Label>
                <Select
                  value={editForm.priority}
                  onValueChange={(v) => setEditForm((f) => ({ ...f, priority: v }))}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {PRIORITIES.map((p) => (
                      <SelectItem key={p} value={p}>
                        {priorityLabel(p, lang)}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="edit-instructions">{t("td.instructions")}</Label>
              <Textarea
                id="edit-instructions"
                placeholder={t("td.dialog.instructionsPlaceholder")}
                value={editForm.instructions}
                onChange={(e) => setEditForm((f) => ({ ...f, instructions: e.target.value }))}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              {t("common.cancel")}
            </Button>
            <Button disabled={busy} onClick={doEdit}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              {t("common.saveChanges")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Submit for inspection */}
      <Dialog open={dialogOpen("submit")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("td.submitForInspection")}</DialogTitle>
            <DialogDescription>
              {supervisor ? t("td.dialog.submitDescSupervisor") : t("td.dialog.submitDescStaff")}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="submit-note">{t("td.dialog.noteOptional")}</Label>
            <Textarea
              id="submit-note"
              placeholder={t("td.dialog.submitNotePlaceholder")}
              value={note}
              onChange={(e) => setNote(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              {t("common.cancel")}
            </Button>
            <Button disabled={busy} onClick={doSubmit}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              {t("td.dialog.submitAction")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Inspect */}
      <Dialog open={dialogOpen("inspect")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("td.dialog.inspectTitle")}</DialogTitle>
            <DialogDescription>{t("td.dialog.inspectDesc")}</DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="inspect-comments">{t("td.dialog.inspectionComments")}</Label>
            <Textarea
              id="inspect-comments"
              placeholder={t("td.dialog.inspectPlaceholder")}
              value={comments}
              onChange={(e) => setComments(e.target.value)}
            />
          </div>
          <DialogFooter className="sm:justify-between">
            <Button
              variant="outline"
              className="border-amber-300 text-amber-800 hover:bg-amber-50 hover:text-amber-900"
              disabled={busy}
              onClick={doRequestChanges}
            >
              {t("td.dialog.requestChanges")}
            </Button>
            <Button disabled={busy} onClick={doApprove}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              <ShieldCheck className="size-4" />
              {t("td.dialog.approve")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Deliver */}
      <Dialog open={dialogOpen("deliver")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("td.markDelivered")}</DialogTitle>
            <DialogDescription>
              {t("td.dialog.deliverDesc", {
                customer: task.customerName,
                piece: task.garmentPiece,
              })}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="deliver-note">{t("td.dialog.noteOptional")}</Label>
            <Input
              id="deliver-note"
              placeholder={t("td.dialog.deliverNotePlaceholder")}
              value={note}
              onChange={(e) => setNote(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              {t("common.cancel")}
            </Button>
            <Button disabled={busy} onClick={doDeliver}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              <Truck className="size-4" />
              {t("td.dialog.markDeliveredAction")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Reopen */}
      <Dialog open={dialogOpen("reopen")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("td.reopenTask")}</DialogTitle>
            <DialogDescription>{t("td.dialog.reopenDesc")}</DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="reopen-comments">{t("td.dialog.comments")}</Label>
            <Textarea
              id="reopen-comments"
              placeholder={t("td.dialog.reopenPlaceholder")}
              value={comments}
              onChange={(e) => setComments(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              {t("common.cancel")}
            </Button>
            <Button disabled={busy} onClick={doReopen}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              <Undo2 className="size-4" />
              {t("td.dialog.reopenAction")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Block */}
      <Dialog open={dialogOpen("block")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("td.blockTask")}</DialogTitle>
            <DialogDescription>{t("td.dialog.blockDesc")}</DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="block-reason">{t("td.dialog.reasonRequired")}</Label>
            <Textarea
              id="block-reason"
              placeholder={t("td.dialog.blockPlaceholder")}
              value={reason}
              onChange={(e) => setReason(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              {t("common.cancel")}
            </Button>
            <Button
              className="bg-red-600 text-white hover:bg-red-700"
              disabled={busy || reason.trim().length < 3}
              onClick={doBlock}
            >
              {busy && <Loader2 className="size-4 animate-spin" />}
              <Ban className="size-4" />
              {t("td.blockTask")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Unblock */}
      <Dialog open={dialogOpen("unblock")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("td.unblockTask")}</DialogTitle>
            <DialogDescription>{t("td.dialog.unblockDesc")}</DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="unblock-note">{t("td.dialog.noteOptional")}</Label>
            <Input
              id="unblock-note"
              placeholder={t("td.dialog.unblockPlaceholder")}
              value={note}
              onChange={(e) => setNote(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              {t("common.cancel")}
            </Button>
            <Button disabled={busy} onClick={doUnblock}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              <RotateCcw className="size-4" />
              {t("td.dialog.unblockAction")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Cancel (destructive confirmation) */}
      <AlertDialog open={dialogOpen("cancel")} onOpenChange={closeDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{t("td.dialog.cancelTitle")}</AlertDialogTitle>
            <AlertDialogDescription>
              {t("td.dialog.cancelDesc", {
                code: task.code,
                piece: task.garmentPiece,
                order: task.orderNumber,
              })}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="cancel-reason">{t("td.dialog.reasonRequired")}</Label>
            <Textarea
              id="cancel-reason"
              placeholder={t("td.dialog.cancelPlaceholder")}
              value={reason}
              onChange={(e) => setReason(e.target.value)}
            />
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={busy}>{t("td.dialog.keepTask")}</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-white hover:bg-destructive/90"
              disabled={busy || reason.trim().length < 3}
              onClick={(e) => {
                e.preventDefault();
                doCancel();
              }}
            >
              {busy && <Loader2 className="size-4 animate-spin" />}
              <XCircle className="size-4" />
              {t("td.cancelTask")}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
