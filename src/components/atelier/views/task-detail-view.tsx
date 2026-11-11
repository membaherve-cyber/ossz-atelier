"use client";

// OSSZ ATELIER — task detail screen: progress & production checklist,
// workflow actions, comments/issues and a merged history timeline.

import { useCallback, useEffect, useMemo, useState } from "react";
import type { ReactNode } from "react";
import { format } from "date-fns";
import type { LucideIcon } from "lucide-react";
import { ApiClientError, api } from "../client";
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
  INSPECTION_DECISION_LABELS,
  PRIORITIES,
  PRIORITY_LABELS,
  STAGE_STATES,
  STAGE_STATE_LABELS,
  STATUS_LABELS,
  type StageState,
  type TaskStatus,
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

const STAGE_STATE_OPTIONS = STAGE_STATES.map((s) => ({
  value: s,
  label: s === "NOT_APPLICABLE" ? "N/A" : STAGE_STATE_LABELS[s],
}));

/** Convert an ISO timestamp to a `datetime-local` input value (local time). */
function toDatetimeLocal(iso: string | null): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  return format(d, "yyyy-MM-dd'T'HH:mm");
}

function statusLabel(status: string): string {
  return STATUS_LABELS[status as TaskStatus] ?? status;
}

function stageLabel(state: string): string {
  return STAGE_STATE_LABELS[state as StageState] ?? state;
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
        setDetail(res);
        setFatal(null);
        setFetchError(null);
      } catch (e) {
        if (e instanceof ApiClientError && (e.status === 403 || e.status === 404)) {
          setFatal(e.message);
        } else {
          setFetchError(e instanceof Error ? e.message : "Failed to load task.");
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
            ? `reassigned the task to ${a.staffName}`
            : `assigned the task to ${a.staffName}`,
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
        text: `changed status from ${statusLabel(s.fromStatus)} to ${statusLabel(s.toStatus)}`,
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
        text: `${s.stageName}: ${stageLabel(s.previousState)} → ${stageLabel(s.newState)}`,
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
        text: `updated progress ${p.previousProgress}% → ${p.newProgress}%`,
        detail: p.note,
        badge: p.source,
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
        text: `inspection decision — ${INSPECTION_DECISION_LABELS[i.decision as never] ?? i.decision}`,
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
        text: c.isIssue ? "reported an issue" : "added a note",
        detail: c.message,
        icon: MessageSquare,
        iconClass: c.isIssue ? "bg-red-50 text-red-700" : "bg-stone-100 text-stone-600",
      });
    }
    return entries.sort(
      (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    );
  }, [detail]);

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
          title: "Action failed",
          description: e instanceof Error ? e.message : "Unexpected error.",
          variant: "destructive",
        });
      } finally {
        setBusy(false);
      }
    },
    [load, toast]
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
        title="No task selected"
        description="Open a task from the production board to see its details."
        action={
          <Button variant="outline" size="sm" onClick={() => navigate("tasks")}>
            <ArrowLeft className="size-4" />
            Go to tasks
          </Button>
        }
      />
    );
  }

  if (fatal) {
    return (
      <EmptyState
        icon={AlertTriangle}
        title="Task unavailable"
        description={fatal}
        action={
          <Button variant="outline" size="sm" onClick={() => navigate("tasks")}>
            <ArrowLeft className="size-4" />
            Back to tasks
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
        title="Could not load task"
        description={fetchError ?? "Unknown error."}
        action={
          <Button variant="outline" size="sm" onClick={() => load()}>
            Retry
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
        title: "Select a staff member",
        description: "Choose the account responsible for this garment piece.",
        variant: "destructive",
      });
      return false;
    }
    return true;
  }

  function requireReason(field: string, min = 3) {
    if (reason.trim().length < min) {
      toast({
        title: "A reason is required",
        description: `Provide ${field} (at least ${min} characters).`,
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
      `${task.assignedStaffId ? "Reassigned" : "Assigned"} to ${target?.name ?? "staff member"}`,
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
      toast({ title: "Garment piece name is required", variant: "destructive" });
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
      toast({ title: "Nothing to update", description: "No field was changed." });
      return;
    }
    runAction("Task details updated", () => api.patch(url, body));
  };

  const doSubmit = () => {
    runAction("Submitted for inspection", () =>
      api.patch(url, { action: "SUBMIT", note: note.trim() || undefined })
    );
  };

  const doApprove = () => {
    runAction("Inspection approved", () =>
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
        title: "Correction instructions required",
        description: "Describe what needs to be corrected (at least 3 characters).",
        variant: "destructive",
      });
      return;
    }
    runAction("Corrections requested", () =>
      api.patch(url, {
        action: "INSPECT",
        decision: "CHANGES_REQUESTED",
        comments: comments.trim(),
      })
    );
  };

  const doReady = () => {
    runAction("Marked ready for pickup", () => api.patch(url, { action: "READY" }));
  };

  const doDeliver = () => {
    runAction("Marked as delivered/collected", () =>
      api.patch(url, { action: "DELIVER", note: note.trim() || undefined })
    );
  };

  const doReopen = () => {
    if (comments.trim().length < 3) {
      toast({
        title: "Comments are required",
        description: "Explain why this task is being reopened (at least 3 characters).",
        variant: "destructive",
      });
      return;
    }
    runAction("Task reopened", () =>
      api.patch(url, { action: "INSPECT", decision: "REOPENED", comments: comments.trim() })
    );
  };

  const doBlock = () => {
    if (!requireReason("the reason production is blocked")) return;
    runAction("Task blocked", () => api.patch(url, { action: "BLOCK", reason: reason.trim() }));
  };

  const doUnblock = () => {
    runAction("Task unblocked", () =>
      api.patch(url, { action: "UNBLOCK", note: note.trim() || undefined })
    );
  };

  const doCancel = () => {
    if (!requireReason("the cancellation reason")) return;
    runAction("Task cancelled", () => api.patch(url, { action: "CANCEL", reason: reason.trim() }));
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
      toast({ title: `Progress updated to ${progressValue}%` });
    } catch (e) {
      toast({
        title: "Could not update progress",
        description: e instanceof Error ? e.message : "Unexpected error.",
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
        title: "Checklist updated",
        description: "Progress was recalculated from the completed stages.",
      });
    } catch (e) {
      toast({
        title: "Could not save checklist",
        description: e instanceof Error ? e.message : "Unexpected error.",
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
        title: "Write a message first",
        description: isIssue ? "Describe the problem you encountered." : "Type a note before sending.",
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
        title: isIssue ? "Issue reported" : "Note added",
        description: isIssue ? "Management has been notified." : undefined,
      });
    } catch (e) {
      toast({
        title: "Could not add comment",
        description: e instanceof Error ? e.message : "Unexpected error.",
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
        Back to tasks
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
                Assignee:{" "}
                <span className="text-foreground font-medium">
                  {task.assignedStaffName ?? "Unassigned"}
                </span>
              </span>
              <DueDate deadline={task.deadline} overdue={task.overdue} />
              <span>Created {formatDate(task.createdAt)}</span>
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
            <AlertTitle>Task blocked</AlertTitle>
            <AlertDescription className="text-destructive/90">
              {task.blockedReason}
            </AlertDescription>
          </Alert>
        )}
        {task.status === "CANCELLED" && (
          <Alert className="mt-3 border-stone-300 bg-stone-50 text-stone-700">
            <Ban />
            <AlertTitle>Task cancelled</AlertTitle>
            <AlertDescription className="text-stone-600">
              {task.cancelReason ?? "This task was cancelled."}
            </AlertDescription>
          </Alert>
        )}
        {task.status === "CHANGES_REQUESTED" && (
          <Alert className="mt-3 border-amber-300 bg-amber-50 text-amber-800">
            <AlertTriangle />
            <AlertTitle>Corrections requested</AlertTitle>
            <AlertDescription className="text-amber-700">
              Corrections were requested — see inspection history below for details.
            </AlertDescription>
          </Alert>
        )}
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        {/* ---- Progress & checklist (left) ---- */}
        <SectionCard title="Progress & Checklist" className="lg:col-span-2">
          <div className="space-y-4">
            <ProgressCell value={task.progress} status={task.status} />
            <p className="text-xs text-muted-foreground -mt-2">
              Current progress: <span className="font-semibold">{task.progress}%</span>
            </p>

            {mayUpdateProgress && (
              <div className="rounded-lg border bg-muted/30 p-3 space-y-2">
                <p className="text-sm font-medium">Update progress manually</p>
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
                    aria-label="New progress percentage"
                  />
                  <Input
                    className="flex-1"
                    placeholder="Optional note"
                    value={progressNote}
                    onChange={(e) => setProgressNote(e.target.value)}
                    aria-label="Progress note"
                  />
                  <Button disabled={!progressValid || busy} onClick={doProgressUpdate}>
                    Update
                  </Button>
                </div>
                {supervisor && (
                  <p className="text-xs text-muted-foreground">
                    Supervisor correction — manual values are recorded in the history with source
                    &quot;MANUAL&quot;.
                  </p>
                )}
              </div>
            )}

            <Separator />

            <div>
              <div className="flex items-center justify-between gap-2">
                <p className="text-sm font-medium">Production checklist</p>
                {changesCount > 0 && (
                  <Badge variant="outline" className="border-amber-300 bg-amber-50 text-amber-800">
                    {changesCount} pending change{changesCount === 1 ? "" : "s"}
                  </Badge>
                )}
              </div>
              <p className="text-xs text-muted-foreground mt-0.5">
                Progress is recalculated from completed stages (in-progress counts half
                {hasWeights ? "; heavier stages count proportionally more" : ""}).
              </p>

              <div className="mt-2 divide-y rounded-lg border">
                {stages.length === 0 ? (
                  <EmptyState
                    icon={Inbox}
                    title="No checklist stages"
                    description="No production stages apply to this task category."
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
                                weight ×{s.weight}
                              </Badge>
                            )}
                          </p>
                          <div className="mt-1 flex items-center gap-2">
                            <StageStateChip state={effective} />
                            {pending && pending !== s.state && (
                              <span className="text-xs text-amber-700">was {stageLabel(s.state)}</span>
                            )}
                          </div>
                        </div>
                        {mayEditChecklist ? (
                          <div className="flex flex-wrap gap-1 shrink-0" role="group" aria-label={`State for ${s.name}`}>
                            {STAGE_STATE_OPTIONS.map((o) => {
                              const active = effective === o.value;
                              return (
                                <button
                                  key={o.value}
                                  type="button"
                                  aria-pressed={active}
                                  aria-label={`Set ${s.name} to ${o.label}`}
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
                    placeholder="Optional comment attached to the next checklist save"
                    value={stageComment}
                    onChange={(e) => setStageComment(e.target.value)}
                    aria-label="Checklist save comment"
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
                      Save Checklist{changesCount > 0 ? ` (${changesCount})` : ""}
                    </Button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </SectionCard>

        {/* ---- Actions (right) ---- */}
        <SectionCard title="Workflow Actions">
          <div className="space-y-2">
            {canSubmit && (
              <div>
                <Button
                  className="w-full"
                  disabled={busy || staffSubmitBlocked}
                  onClick={() => openDialog("submit")}
                >
                  <ClipboardCheck className="size-4" />
                  Submit for Inspection
                </Button>
                {staffSubmitBlocked && (
                  <p className="text-xs text-muted-foreground mt-1">
                    Reach 100% progress first.
                  </p>
                )}
              </div>
            )}
            {canInspect && (
              <Button className="w-full" disabled={busy} onClick={() => openDialog("inspect")}>
                <ShieldCheck className="size-4" />
                Inspect Result
              </Button>
            )}
            {canReady && (
              <Button className="w-full" disabled={busy} onClick={doReady}>
                <ShoppingBag className="size-4" />
                Mark Ready for Pickup
              </Button>
            )}
            {canDeliver && (
              <Button className="w-full" disabled={busy} onClick={() => openDialog("deliver")}>
                <Truck className="size-4" />
                Mark Delivered/Collected
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
                {task.assignedStaffId ? "Reassign Staff" : "Assign Staff"}
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
                Edit Details
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
                Block Task
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
                Unblock Task
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
                Reopen Task
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
                Cancel Task
              </Button>
            )}

            {noActions && (
              <p className="text-sm text-muted-foreground">
                No actions available for this task{terminal ? " — it is closed." : ""}
              </p>
            )}
          </div>
        </SectionCard>

        {/* ---- Comments & issues (left) ---- */}
        <SectionCard
          title="Comments & Issues"
          className="lg:col-span-2"
          action={
            <span className="text-xs text-muted-foreground">
              {detail.comments.length} total
            </span>
          }
        >
          <div className="space-y-4">
            {detail.comments.length === 0 ? (
              <EmptyState
                icon={MessageSquare}
                title="No notes yet"
                description="Production notes and reported issues will appear here."
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
                  placeholder="Write a production note or describe a problem..."
                  value={commentText}
                  onChange={(e) => setCommentText(e.target.value)}
                  aria-label="New comment"
                />
                <div className="flex flex-col sm:flex-row sm:justify-end gap-2">
                  <Button variant="outline" size="sm" disabled={busy} onClick={() => postComment(false)}>
                    <MessageSquare className="size-4" />
                    Add Note
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    className="border-destructive/40 text-destructive hover:bg-destructive/10 hover:text-destructive"
                    disabled={busy}
                    onClick={() => postComment(true)}
                  >
                    <AlertTriangle className="size-4" />
                    Report Issue
                  </Button>
                </div>
              </div>
            )}
          </div>
        </SectionCard>

        {/* ---- Details (right) ---- */}
        <SectionCard title="Task Details">
          <div className="divide-y">
            <MetaRow label="Order">
              <span className="font-medium">
                {task.orderNumber} — {task.customerName}
              </span>
            </MetaRow>
            <MetaRow label="Garment piece">
              <span className="font-medium">{task.garmentPiece}</span>
            </MetaRow>
            <MetaRow label="Assignee">
              {task.assignedStaffName ?? <span className="text-muted-foreground">Unassigned</span>}
            </MetaRow>
            <MetaRow label="Category">
              <CategoryBadge category={task.category} />
            </MetaRow>
            <MetaRow label="Priority">
              <PriorityBadge priority={task.priority} />
            </MetaRow>
            <MetaRow label="Deadline">
              <DueDate deadline={task.deadline} overdue={task.overdue} />
            </MetaRow>
            <MetaRow label="Created">{formatDate(task.createdAt)}</MetaRow>
            <MetaRow label="Completed">
              {task.completedAt ? formatDate(task.completedAt) : "—"}
            </MetaRow>
            <MetaRow label="Delivered">
              {task.deliveredAt ? formatDate(task.deliveredAt) : "—"}
            </MetaRow>
          </div>
          {task.instructions && (
            <div className="mt-3">
              <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Instructions
              </p>
              <p className="text-sm mt-1 rounded-lg border bg-muted/30 p-3 whitespace-pre-wrap">
                {task.instructions}
              </p>
            </div>
          )}
        </SectionCard>

        {/* ---- History (full width) ---- */}
        <SectionCard
          title="History"
          className="lg:col-span-3"
          action={
            <span className="text-xs text-muted-foreground">{history.length} events</span>
          }
        >
          <Tabs value={historyTab} onValueChange={(v) => setHistoryTab(v as HistoryTab)}>
            <TabsList className="flex-wrap h-auto">
              <TabsTrigger value="all" className="h-8">
                All
              </TabsTrigger>
              <TabsTrigger value="progress" className="h-8">
                Progress
              </TabsTrigger>
              <TabsTrigger value="checklist" className="h-8">
                Checklist
              </TabsTrigger>
              <TabsTrigger value="status" className="h-8">
                Status
              </TabsTrigger>
              <TabsTrigger value="inspections" className="h-8">
                Inspections
              </TabsTrigger>
            </TabsList>
            <TabsContent value={historyTab} className="mt-2">
              {filteredHistory.length === 0 ? (
                <EmptyState icon={Inbox} title="No events recorded" />
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
            <DialogTitle>{task.assignedStaffId ? "Reassign Task" : "Assign Task"}</DialogTitle>
            <DialogDescription>
              Select the staff member responsible for {task.garmentPiece} ({task.code}).
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label>Staff member</Label>
              <Select value={staffId} onValueChange={setStaffId}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select staff member" />
                </SelectTrigger>
                <SelectContent>
                  {staff.length === 0 && (
                    <SelectItem value="loading" disabled>
                      No staff accounts available
                    </SelectItem>
                  )}
                  {staff.map((u) => (
                    <SelectItem key={u.id} value={u.id} disabled={u.id === task.assignedStaffId}>
                      {u.name}
                      {u.activeTaskCount != null ? ` (${u.activeTaskCount} active)` : ""}
                      {u.id === task.assignedStaffId ? " — current" : ""}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="assign-note">Note (optional)</Label>
              <Input
                id="assign-note"
                placeholder="Note sent with the assignment notification"
                value={note}
                onChange={(e) => setNote(e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              Cancel
            </Button>
            <Button disabled={busy || !staffId} onClick={doAssign}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              {task.assignedStaffId ? "Reassign" : "Assign"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit details */}
      <Dialog open={dialogOpen("edit")} onOpenChange={closeDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Edit Task Details</DialogTitle>
            <DialogDescription>
              Only the fields you change are submitted.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label htmlFor="edit-piece">Garment piece</Label>
              <Input
                id="edit-piece"
                value={editForm.garmentPiece}
                onChange={(e) => setEditForm((f) => ({ ...f, garmentPiece: e.target.value }))}
              />
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label htmlFor="edit-deadline">Deadline</Label>
                <Input
                  id="edit-deadline"
                  type="datetime-local"
                  value={editForm.deadline}
                  onChange={(e) => setEditForm((f) => ({ ...f, deadline: e.target.value }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label>Priority</Label>
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
                        {PRIORITY_LABELS[p]}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="edit-instructions">Instructions</Label>
              <Textarea
                id="edit-instructions"
                placeholder="Production instructions for this piece"
                value={editForm.instructions}
                onChange={(e) => setEditForm((f) => ({ ...f, instructions: e.target.value }))}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              Cancel
            </Button>
            <Button disabled={busy} onClick={doEdit}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              Save Changes
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Submit for inspection */}
      <Dialog open={dialogOpen("submit")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Submit for Inspection</DialogTitle>
            <DialogDescription>
              {supervisor
                ? "The task moves to Awaiting Inspection for quality control."
                : "Your work will be reviewed by quality control. Make sure the checklist is complete."}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="submit-note">Note (optional)</Label>
            <Textarea
              id="submit-note"
              placeholder="Anything the inspector should know"
              value={note}
              onChange={(e) => setNote(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              Cancel
            </Button>
            <Button disabled={busy} onClick={doSubmit}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              Submit
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Inspect */}
      <Dialog open={dialogOpen("inspect")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Quality Inspection</DialogTitle>
            <DialogDescription>
              Approve passes the garment to the pickup flow. Request changes returns it to the
              worker with correction instructions.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="inspect-comments">Inspection comments</Label>
            <Textarea
              id="inspect-comments"
              placeholder={
                "Required when requesting changes — describe the corrections needed"
              }
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
              Request Changes
            </Button>
            <Button disabled={busy} onClick={doApprove}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              <ShieldCheck className="size-4" />
              Approve
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Deliver */}
      <Dialog open={dialogOpen("deliver")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Mark Delivered/Collected</DialogTitle>
            <DialogDescription>
              Confirm that {task.customerName} collected {task.garmentPiece}.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="deliver-note">Note (optional)</Label>
            <Input
              id="deliver-note"
              placeholder="e.g. Collected by the customer in person"
              value={note}
              onChange={(e) => setNote(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              Cancel
            </Button>
            <Button disabled={busy} onClick={doDeliver}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              <Truck className="size-4" />
              Mark Delivered
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Reopen */}
      <Dialog open={dialogOpen("reopen")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Reopen Task</DialogTitle>
            <DialogDescription>
              The task returns to Ongoing so production can resume. Comments are required.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="reopen-comments">Comments</Label>
            <Textarea
              id="reopen-comments"
              placeholder="Why is this task being reopened?"
              value={comments}
              onChange={(e) => setComments(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              Cancel
            </Button>
            <Button disabled={busy} onClick={doReopen}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              <Undo2 className="size-4" />
              Reopen
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Block */}
      <Dialog open={dialogOpen("block")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Block Task</DialogTitle>
            <DialogDescription>
              Report what is blocking production. Management is notified immediately.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="block-reason">Reason (required)</Label>
            <Textarea
              id="block-reason"
              placeholder="e.g. Missing customer-provided fabric"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              Cancel
            </Button>
            <Button
              className="bg-red-600 text-white hover:bg-red-700"
              disabled={busy || reason.trim().length < 3}
              onClick={doBlock}
            >
              {busy && <Loader2 className="size-4 animate-spin" />}
              <Ban className="size-4" />
              Block Task
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Unblock */}
      <Dialog open={dialogOpen("unblock")} onOpenChange={closeDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Unblock Task</DialogTitle>
            <DialogDescription>
              The blocker is cleared and production can resume.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="unblock-note">Note (optional)</Label>
            <Input
              id="unblock-note"
              placeholder="How the blocker was resolved"
              value={note}
              onChange={(e) => setNote(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" disabled={busy} onClick={() => setDialog(null)}>
              Cancel
            </Button>
            <Button disabled={busy} onClick={doUnblock}>
              {busy && <Loader2 className="size-4 animate-spin" />}
              <RotateCcw className="size-4" />
              Unblock
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Cancel (destructive confirmation) */}
      <AlertDialog open={dialogOpen("cancel")} onOpenChange={closeDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Cancel this task?</AlertDialogTitle>
            <AlertDialogDescription>
              {task.code} — {task.garmentPiece} ({task.orderNumber}) will be permanently closed.
              This cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="cancel-reason">Reason (required)</Label>
            <Textarea
              id="cancel-reason"
              placeholder="e.g. Cancelled by the customer"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
            />
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={busy}>Keep Task</AlertDialogCancel>
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
              Cancel Task
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
