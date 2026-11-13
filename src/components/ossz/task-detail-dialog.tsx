"use client";

import { useEffect, useMemo, useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Skeleton } from "@/components/ui/skeleton";
import { Slider } from "@/components/ui/slider";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Send,
  AlertTriangle,
  CheckCircle2,
  Undo2,
  XCircle,
  UserCheck,
  CalendarClock,
  ShieldCheck,
  Loader2,
  RotateCcw,
  MessageSquareWarning,
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { api } from "./api-client";
import { can, useAppStore } from "./store";
import { useNav } from "./use-nav";
import { formatDate, formatDateTime, formatFCFA, timeAgo } from "@/lib/format";
import { TaskStatusBadge } from "./status-badge";
import { EVENT_TYPE_LABELS, type TaskDetail } from "./types";

function prettyMeasurements(json: string | null): Array<[string, string]> {
  if (!json) return [];
  try {
    return Object.entries(JSON.parse(json));
  } catch {
    return [];
  }
}

export function TaskDetailDialog() {
  const user = useAppStore((s) => s.user)!;
  const { get, navigate } = useNav();
  const taskId = get("task");
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const [progress, setProgress] = useState(0);
  const [note, setNote] = useState("");
  const [issueReason, setIssueReason] = useState("");
  const [blockReason, setBlockReason] = useState("");
  const [correctionReason, setCorrectionReason] = useState("");
  const [inspectResult, setInspectResult] = useState<"APPROVED" | "CHANGES_REQUESTED">("APPROVED");
  const [inspectionNotes, setInspectionNotes] = useState("");
  const [correctionInstructions, setCorrectionInstructions] = useState("");
  const [reopenReason, setReopenReason] = useState("");
  const [cancelReason, setCancelReason] = useState("");
  const [assignStaffId, setAssignStaffId] = useState("");
  const [assignDueAt, setAssignDueAt] = useState("");
  const [assignReason, setAssignReason] = useState("");
  const [actionMode, setActionMode] = useState<"none" | "assign" | "inspect" | "reopen" | "cancel" | "deadline">("none");
  const [deadlineValue, setDeadlineValue] = useState("");
  const [deadlineReason, setDeadlineReason] = useState("");

  const detailQuery = useQuery({
    queryKey: ["task", taskId],
    queryFn: () => api.get<TaskDetail>(`/api/production/tasks/${taskId}`),
    enabled: !!taskId,
  });

  const staffQuery = useQuery({
    queryKey: ["staff-assignable"],
    queryFn: () =>
      api.get<{ users: Array<{ id: string; name: string; role: string; activeTaskCount: number }> }>(
        "/api/users?assignable=1"
      ),
    enabled: !!taskId && can(user, "production.assign"),
  });

  const detail = detailQuery.data;
  const task = detail?.task;

  useEffect(() => {
    if (task) {
      setProgress(task.progressPercentage);
      setAssignStaffId(task.assignedStaff?.id ?? "");
      setAssignDueAt(task.dueAt ? task.dueAt.slice(0, 10) : "");
      setDeadlineValue(task.dueAt ? task.dueAt.slice(0, 10) : "");
    }
  }, [task?.id, task?.progressPercentage, task?.assignedStaff?.id, task?.dueAt, task]);

  function invalidate() {
    queryClient.invalidateQueries({ queryKey: ["task", taskId] });
    queryClient.invalidateQueries({ queryKey: ["production-tasks"] });
    queryClient.invalidateQueries({ queryKey: ["my-tasks"] });
    queryClient.invalidateQueries({ queryKey: ["order"] });
    queryClient.invalidateQueries({ queryKey: ["orders"] });
    queryClient.invalidateQueries({ queryKey: ["production-summary"] });
    queryClient.invalidateQueries({ queryKey: ["workload"] });
    queryClient.invalidateQueries({ queryKey: ["notifications"] });
    queryClient.invalidateQueries({ queryKey: ["reports"] });
  }

  function useAction(url: string, successTitle: string, reset?: () => void) {
    return useMutation({
      mutationFn: (payload: unknown) => api.post(`/api/production/tasks/${taskId}/${url}`, payload),
      onSuccess: () => {
        toast({ title: successTitle });
        invalidate();
        setActionMode("none");
        setBlockReason(""); setNote(""); setIssueReason(""); setCorrectionReason("");
        setInspectionNotes(""); setCorrectionInstructions(""); setReopenReason(""); setCancelReason("");
        reset?.();
      },
      onError: (err: Error) => {
        toast({ title: "Action failed", description: err.message, variant: "destructive" });
      },
    });
  }

  const progressMutation = useAction("progress", "Progress updated");
  const submitMutation = useAction("submit", "Submitted for inspection");
  const blockMutation = useAction("block", "Problem reported — task blocked");
  const resolveMutation = useAction("resolve", "Blocker resolved — production resumed");
  const inspectMutation = useAction("inspect", "Inspection recorded");
  const reopenMutation = useAction("reopen", "Task reopened");
  const cancelMutation = useAction("cancel", "Task cancelled");
  const assignMutation = useAction("assign", "Assignment updated");
  const deadlineMutation = useAction("deadline", "Deadline updated");

  const close = () => navigate({ task: null });

  const isAssignedStaff = detail?.viewerIsAssignedStaff ?? false;
  const canManage = can(user, "production.view.all");
  const canInspect = can(user, "production.inspect");
  const canAssign = can(user, "production.assign");
  const canCorrect = can(user, "production.correct.progress");

  const editableStatuses = ["ASSIGNED", "IN_PROGRESS", "CHANGES_REQUESTED"];
  const canUpdateProgress = (isAssignedStaff || canCorrect) && editableStatuses.includes(task?.status ?? "");
  const canSubmit =
    (isAssignedStaff || canManage) &&
    editableStatuses.includes(task?.status ?? "") &&
    task?.progressPercentage === 100;
  const canBlock = (isAssignedStaff || canManage) && editableStatuses.includes(task?.status ?? "");

  const measurements = useMemo(() => prettyMeasurements(detail?.item.measurements ?? null), [detail?.item.measurements]);

  return (
    <Dialog open={!!taskId} onOpenChange={(open) => !open && close()}>
      <DialogContent className="max-w-3xl max-h-[92vh] overflow-y-auto">
        <DialogHeader>
          <div className="flex items-center gap-2 flex-wrap pr-6">
            <DialogTitle className="font-display text-xl font-semibold">
              {task ? task.pieceName : "Production task"}
            </DialogTitle>
            {task && <TaskStatusBadge status={task.status} />}
            {task?.overdue && (
              <Badge className="bg-red-600/10 text-red-700 border border-red-600/30">Overdue</Badge>
            )}
          </div>
          <DialogDescription>
            {task && detail ? (
              <>
                Order{" "}
                <button
                  className="font-mono text-primary hover:underline"
                  onClick={() => navigate({ view: canManage ? "order" : "mytasks", order: detail.order.id, task: null })}
                >
                  {detail.order.reference}
                </button>{" "}
                · {detail.item.productName} · Customer: {detail.customer.name}
              </>
            ) : (
              "Loading task details…"
            )}
          </DialogDescription>
        </DialogHeader>
        {detailQuery.isLoading || !task || !detail ? (
          <div className="space-y-4 py-4">
            <Skeleton className="h-8 w-64" />
            <Skeleton className="h-24" />
            <Skeleton className="h-48" />
          </div>
        ) : (
          <>
            {/* Progress header */}
            <div>
              <div className="flex items-center justify-between mb-1.5">
                <p className="text-sm font-medium">Completion</p>
                <p className="text-sm font-bold tabular-nums">{task.progressPercentage}%</p>
              </div>
              <Progress value={task.progressPercentage} className="h-3" />
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3 text-xs">
                <div className="rounded-lg border bg-muted/30 p-2">
                  <p className="text-muted-foreground">Assigned to</p>
                  <p className="font-semibold mt-0.5">{task.assignedStaff?.name ?? "—"}</p>
                </div>
                <div className="rounded-lg border bg-muted/30 p-2">
                  <p className="text-muted-foreground">Assigned by</p>
                  <p className="font-semibold mt-0.5">{task.assignedBy?.name ?? "—"}</p>
                </div>
                <div className="rounded-lg border bg-muted/30 p-2">
                  <p className="text-muted-foreground">Assignment date</p>
                  <p className="font-semibold mt-0.5">{formatDate(task.assignedAt)}</p>
                </div>
                <div className="rounded-lg border bg-muted/30 p-2">
                  <p className="text-muted-foreground">Deadline</p>
                  <p className={`font-semibold mt-0.5 ${task.overdue ? "text-red-600" : ""}`}>
                    {formatDate(task.dueAt)}
                  </p>
                </div>
              </div>
            </div>

            {/* Blocked banner */}
            {task.status === "BLOCKED" && task.blockedReason && (
              <div className="flex items-start gap-2 text-sm text-red-700 bg-red-600/5 border border-red-600/20 rounded-lg p-3">
                <AlertTriangle className="size-4 shrink-0 mt-0.5" />
                <div>
                  <p className="font-semibold">Production blocked</p>
                  <p className="text-red-700/90">{task.blockedReason}</p>
                  {task.blockedAt && (
                    <p className="text-xs text-red-700/70 mt-1">Reported {formatDateTime(task.blockedAt)}</p>
                  )}
                </div>
              </div>
            )}

            {/* Last inspection feedback */}
            {task.status === "CHANGES_REQUESTED" && task.lastInspection && (
              <div className="flex items-start gap-2 text-sm text-orange-700 bg-orange-600/5 border border-orange-600/25 rounded-lg p-3">
                <MessageSquareWarning className="size-4 shrink-0 mt-0.5" />
                <div>
                  <p className="font-semibold">Corrections requested by {task.lastInspection.inspector.name}</p>
                  {task.lastInspection.correctionInstructions && (
                    <p className="mt-0.5">{task.lastInspection.correctionInstructions}</p>
                  )}
                  {task.lastInspection.inspectionNotes && (
                    <p className="text-xs text-orange-700/80 mt-1">Notes: {task.lastInspection.inspectionNotes}</p>
                  )}
                  <p className="text-xs text-orange-700/70 mt-1">
                    {formatDateTime(task.lastInspection.inspectedAt)} — update your progress to resume work.
                  </p>
                </div>
              </div>
            )}

            {/* Production specs */}
            <div className="grid sm:grid-cols-2 gap-3 text-sm">
              {measurements.length > 0 && (
                <div className="rounded-lg border p-3">
                  <p className="text-xs font-semibold text-muted-foreground mb-1.5">Customer measurements</p>
                  <div className="flex flex-wrap gap-1">
                    {measurements.map(([k, v]) => (
                      <Badge key={k} variant="secondary" className="font-normal">
                        {k}: {String(v)}
                      </Badge>
                    ))}
                  </div>
                </div>
              )}
              {detail.item.instructions && (
                <div className="rounded-lg border p-3">
                  <p className="text-xs font-semibold text-muted-foreground mb-1">Tailoring instructions</p>
                  <p className="text-sm">{detail.item.instructions}</p>
                </div>
              )}
              {task.productionInstructions && (
                <div className="rounded-lg border p-3">
                  <p className="text-xs font-semibold text-muted-foreground mb-1">Production instructions</p>
                  <p className="text-sm">{task.productionInstructions}</p>
                </div>
              )}
              {canManage && detail.order.paymentStatus && (
                <div className="rounded-lg border p-3">
                  <p className="text-xs font-semibold text-muted-foreground mb-1">Order payment (management only)</p>
                  <p className="text-sm">
                    {detail.order.paymentStatus.replace("_", " ")} · {formatFCFA(detail.order.totalAmount ?? 0)}
                  </p>
                </div>
              )}
            </div>

            {/* ---- Actions ---- */}
            {(canUpdateProgress || canBlock || canSubmit || canAssign || canInspect) && task.status !== "CANCELLED" && (
              <div className="border rounded-xl p-4 space-y-4">
                <p className="text-sm font-semibold">Actions</p>

                {/* Progress update (staff or manager correction) */}
                {canUpdateProgress && (
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <Label htmlFor="progress-slider" className="text-sm font-medium">
                        {isAssignedStaff ? "Update progress" : "Correct progress (manager)"}
                      </Label>
                      <span className="text-sm font-bold tabular-nums text-primary">{progress}%</span>
                    </div>
                    <Slider
                      id="progress-slider"
                      value={[progress]}
                      min={0}
                      max={100}
                      step={5}
                      onValueChange={(v) => setProgress(v[0] ?? 0)}
                      disabled={progressMutation.isPending}
                    />
                    <Textarea
                      placeholder="Progress note (optional) — e.g. “Cutting completed”"
                      rows={2}
                      value={note}
                      onChange={(e) => setNote(e.target.value)}
                    />
                    <Input
                      placeholder="Reason for delay or problem (optional)"
                      value={issueReason}
                      onChange={(e) => setIssueReason(e.target.value)}
                      className="text-sm"
                    />
                    {!isAssignedStaff && canCorrect && (
                      <Input
                        placeholder="Correction reason (required for manager corrections)"
                        value={correctionReason}
                        onChange={(e) => setCorrectionReason(e.target.value)}
                        className="text-sm"
                      />
                    )}
                    <Button
                      className="w-full sm:w-auto"
                      disabled={
                        progressMutation.isPending ||
                        progress === task.progressPercentage ||
                        (!isAssignedStaff && correctionReason.trim().length < 3)
                      }
                      onClick={() =>
                        progressMutation.mutate({
                          newProgress: progress,
                          expectedProgress: task.progressPercentage,
                          note: note.trim() || undefined,
                          issueReason: issueReason.trim() || undefined,
                          correctionReason: correctionReason.trim() || undefined,
                        })
                      }
                    >
                      {progressMutation.isPending && <Loader2 className="size-4 animate-spin" />}
                      {isAssignedStaff ? `Save progress (${task.progressPercentage}% → ${progress}%)` : `Correct progress to ${progress}%`}
                    </Button>
                  </div>
                )}

                <div className="flex flex-wrap gap-2">
                  {/* Submit for inspection */}
                  {canSubmit && (
                    <Button
                      variant="default"
                      disabled={submitMutation.isPending}
                      onClick={() => submitMutation.mutate({})}
                    >
                      <Send className="size-4" /> Submit for inspection
                    </Button>
                  )}
                  {isAssignedStaff && editableStatuses.includes(task.status) && task.progressPercentage < 100 && (
                    <p className="text-xs text-muted-foreground w-full">
                      Reach 100% progress to submit this piece for inspection.
                    </p>
                  )}

                  {/* Block */}
                  {canBlock && task.status !== "BLOCKED" && (
                    <Button
                      variant="outline"
                      className="text-red-700 border-red-600/30 hover:bg-red-600/5"
                      disabled={blockMutation.isPending}
                      onClick={() => {
                        if (blockReason.trim().length < 3) {
                          toast({ title: "Describe the problem first (min 3 characters)", variant: "destructive" });
                          return;
                        }
                        blockMutation.mutate({ reason: blockReason.trim() });
                      }}
                    >
                      <AlertTriangle className="size-4" /> Report problem
                    </Button>
                  )}
                  {canBlock && task.status !== "BLOCKED" && (
                    <Input
                      placeholder="Problem description (required to block)"
                      value={blockReason}
                      onChange={(e) => setBlockReason(e.target.value)}
                      className="flex-1 min-w-56 text-sm"
                    />
                  )}

                  {/* Resolve blocker */}
                  {task.status === "BLOCKED" && can(user, "production.resolve.blockers") && (
                    <Button
                      variant="outline"
                      className="text-green-700 border-green-600/30 hover:bg-green-600/5"
                      disabled={resolveMutation.isPending}
                      onClick={() => resolveMutation.mutate({ note: "Blocker resolved by management." })}
                    >
                      <CheckCircle2 className="size-4" /> Resolve blocker & resume
                    </Button>
                  )}

                  {/* Assign / reassign */}
                  {canAssign && !["APPROVED", "CANCELLED"].includes(task.status) && actionMode !== "assign" && (
                    <Button variant="outline" onClick={() => setActionMode("assign")}>
                      <UserCheck className="size-4" /> {task.assignedStaff ? "Reassign" : "Assign"}
                    </Button>
                  )}

                  {/* Change deadline */}
                  {can(user, "production.manage.deadlines") && !["APPROVED", "CANCELLED"].includes(task.status) && actionMode !== "deadline" && (
                    <Button variant="outline" onClick={() => setActionMode("deadline")}>
                      <CalendarClock className="size-4" /> Deadline
                    </Button>
                  )}

                  {/* Inspect */}
                  {canInspect && task.status === "AWAITING_INSPECTION" && actionMode !== "inspect" && (
                    <Button className="bg-teal-700 hover:bg-teal-800" onClick={() => setActionMode("inspect")}>
                      <ShieldCheck className="size-4" /> Inspect & decide
                    </Button>
                  )}

                  {/* Reopen */}
                  {can(user, "production.reopen") && task.status === "APPROVED" && actionMode !== "reopen" && (
                    <Button variant="outline" onClick={() => setActionMode("reopen")}>
                      <RotateCcw className="size-4" /> Reopen
                    </Button>
                  )}

                  {/* Cancel */}
                  {can(user, "production.cancel") && task.status !== "CANCELLED" && actionMode !== "cancel" && (
                    <Button
                      variant="ghost"
                      className="text-red-700 hover:bg-red-600/5"
                      onClick={() => setActionMode("cancel")}
                    >
                      <XCircle className="size-4" /> Cancel task
                    </Button>
                  )}
                </div>

                {/* Assign form */}
                {actionMode === "assign" && (
                  <div className="border rounded-lg p-3 space-y-3 bg-muted/20">
                    <p className="text-sm font-semibold flex items-center gap-1.5">
                      <UserCheck className="size-4" /> {task.assignedStaff ? "Reassign piece" : "Assign piece"}
                    </p>
                    <div className="grid sm:grid-cols-3 gap-2">
                      <div className="space-y-1">
                        <Label className="text-xs">Staff member</Label>
                        <Select value={assignStaffId} onValueChange={setAssignStaffId}>
                          <SelectTrigger className="text-sm"><SelectValue placeholder="Select staff…" /></SelectTrigger>
                          <SelectContent>
                            {(staffQuery.data?.users ?? []).map((st) => (
                              <SelectItem key={st.id} value={st.id}>
                                {st.name} ({st.activeTaskCount} active)
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-1">
                        <Label className="text-xs">Deadline</Label>
                        <Input type="date" className="text-sm" value={assignDueAt} onChange={(e) => setAssignDueAt(e.target.value)} />
                      </div>
                      <div className="space-y-1">
                        <Label className="text-xs">Reason (optional)</Label>
                        <Input className="text-sm" placeholder="Why this assignment…" value={assignReason} onChange={(e) => setAssignReason(e.target.value)} />
                      </div>
                    </div>
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        disabled={!assignStaffId || assignMutation.isPending}
                        onClick={() =>
                          assignMutation.mutate({
                            staffId: assignStaffId,
                            dueAt: assignDueAt ? new Date(assignDueAt + "T17:00:00").toISOString() : null,
                            reason: assignReason.trim() || undefined,
                          })
                        }
                      >
                        {assignMutation.isPending && <Loader2 className="size-4 animate-spin" />}
                        Confirm assignment
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => setActionMode("none")}>Close</Button>
                    </div>
                  </div>
                )}

                {/* Deadline form */}
                {actionMode === "deadline" && (
                  <div className="border rounded-lg p-3 space-y-3 bg-muted/20">
                    <p className="text-sm font-semibold flex items-center gap-1.5">
                      <CalendarClock className="size-4" /> Change deadline
                    </p>
                    <div className="grid sm:grid-cols-2 gap-2">
                      <div className="space-y-1">
                        <Label className="text-xs">New deadline</Label>
                        <Input type="date" className="text-sm" value={deadlineValue} onChange={(e) => setDeadlineValue(e.target.value)} />
                      </div>
                      <div className="space-y-1">
                        <Label className="text-xs">Reason (optional)</Label>
                        <Input className="text-sm" value={deadlineReason} onChange={(e) => setDeadlineReason(e.target.value)} />
                      </div>
                    </div>
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        disabled={deadlineMutation.isPending}
                        onClick={() =>
                          deadlineMutation.mutate({
                            dueAt: deadlineValue ? new Date(deadlineValue + "T17:00:00").toISOString() : null,
                            reason: deadlineReason.trim() || undefined,
                          })
                        }
                      >
                        Save deadline
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => setActionMode("none")}>Close</Button>
                    </div>
                  </div>
                )}

                {/* Inspect form */}
                {actionMode === "inspect" && (
                  <div className="border rounded-lg p-3 space-y-3 bg-muted/20">
                    <p className="text-sm font-semibold flex items-center gap-1.5">
                      <ShieldCheck className="size-4" /> Inspection decision
                    </p>
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        variant={inspectResult === "APPROVED" ? "default" : "outline"}
                        className={inspectResult === "APPROVED" ? "bg-green-700 hover:bg-green-800" : ""}
                        onClick={() => setInspectResult("APPROVED")}
                      >
                        <CheckCircle2 className="size-4" /> Approve
                      </Button>
                      <Button
                        size="sm"
                        variant={inspectResult === "CHANGES_REQUESTED" ? "default" : "outline"}
                        className={inspectResult === "CHANGES_REQUESTED" ? "bg-orange-600 hover:bg-orange-700" : ""}
                        onClick={() => setInspectResult("CHANGES_REQUESTED")}
                      >
                        <Undo2 className="size-4" /> Request changes
                      </Button>
                    </div>
                    <Textarea
                      placeholder={inspectResult === "APPROVED" ? "Inspection notes (optional)" : "Inspection notes (optional)"}
                      rows={2}
                      value={inspectionNotes}
                      onChange={(e) => setInspectionNotes(e.target.value)}
                    />
                    {inspectResult === "CHANGES_REQUESTED" && (
                      <Textarea
                        placeholder="Correction instructions for the staff member (required)…"
                        rows={2}
                        value={correctionInstructions}
                        onChange={(e) => setCorrectionInstructions(e.target.value)}
                      />
                    )}
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        className={inspectResult === "APPROVED" ? "bg-green-700 hover:bg-green-800" : "bg-orange-600 hover:bg-orange-700"}
                        disabled={
                          inspectMutation.isPending ||
                          (inspectResult === "CHANGES_REQUESTED" && correctionInstructions.trim().length < 3)
                        }
                        onClick={() =>
                          inspectMutation.mutate({
                            result: inspectResult,
                            inspectionNotes: inspectionNotes.trim() || undefined,
                            correctionInstructions: correctionInstructions.trim() || undefined,
                          })
                        }
                      >
                        {inspectMutation.isPending && <Loader2 className="size-4 animate-spin" />}
                        Record decision
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => setActionMode("none")}>Close</Button>
                    </div>
                  </div>
                )}

                {/* Reopen form */}
                {actionMode === "reopen" && (
                  <div className="border rounded-lg p-3 space-y-3 bg-muted/20">
                    <p className="text-sm font-semibold flex items-center gap-1.5">
                      <RotateCcw className="size-4" /> Reopen approved piece
                    </p>
                    <Textarea
                      placeholder="Reason for reopening (required)…"
                      rows={2}
                      value={reopenReason}
                      onChange={(e) => setReopenReason(e.target.value)}
                    />
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        disabled={reopenMutation.isPending || reopenReason.trim().length < 3}
                        onClick={() => reopenMutation.mutate({ reason: reopenReason.trim() })}
                      >
                        {reopenMutation.isPending && <Loader2 className="size-4 animate-spin" />}
                        Reopen task
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => setActionMode("none")}>Close</Button>
                    </div>
                  </div>
                )}

                {/* Cancel form */}
                {actionMode === "cancel" && (
                  <div className="border rounded-lg p-3 space-y-3 bg-muted/20">
                    <p className="text-sm font-semibold flex items-center gap-1.5">
                      <XCircle className="size-4 text-red-600" /> Cancel production task
                    </p>
                    <Textarea
                      placeholder="Reason for cancellation (required). History is retained."
                      rows={2}
                      value={cancelReason}
                      onChange={(e) => setCancelReason(e.target.value)}
                    />
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        variant="destructive"
                        disabled={cancelMutation.isPending || cancelReason.trim().length < 3}
                        onClick={() => cancelMutation.mutate({ reason: cancelReason.trim() })}
                      >
                        {cancelMutation.isPending && <Loader2 className="size-4 animate-spin" />}
                        Confirm cancellation
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => setActionMode("none")}>Close</Button>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* ---- History ---- */}
            <div>
              <p className="text-sm font-semibold mb-2">History & audit trail</p>
              <Tabs defaultValue="progress">
                <TabsList className="flex-wrap h-auto">
                  <TabsTrigger value="progress" className="text-xs">Progress ({detail.history.progressUpdates.length})</TabsTrigger>
                  <TabsTrigger value="inspections" className="text-xs">Inspections ({detail.history.inspections.length})</TabsTrigger>
                  <TabsTrigger value="assignments" className="text-xs">Assignments ({detail.history.assignments.length})</TabsTrigger>
                  <TabsTrigger value="events" className="text-xs">All events ({detail.history.events.length})</TabsTrigger>
                </TabsList>

                <TabsContent value="progress" className="mt-3 max-h-72 overflow-y-auto space-y-2">
                  {detail.history.progressUpdates.length === 0 ? (
                    <p className="text-sm text-muted-foreground py-4 text-center">No progress updates yet.</p>
                  ) : (
                    detail.history.progressUpdates.map((u) => (
                      <div key={u.id} className={`border rounded-lg p-3 text-sm ${u.isCorrection ? "border-amber-600/40 bg-amber-600/5" : ""}`}>
                        <div className="flex items-center justify-between gap-2 flex-wrap">
                          <p className="font-semibold">
                            {u.previousProgress}% → {u.newProgress}%
                            <span className="font-normal text-muted-foreground"> · {u.staff.name}</span>
                          </p>
                          <p className="text-xs text-muted-foreground">{formatDateTime(u.createdAt)}</p>
                        </div>
                        {u.isCorrection && (
                          <Badge className="mt-1 bg-amber-600/10 text-amber-700 border border-amber-600/30 text-[10px]">
                            Manager correction{u.correctionReason ? ` — ${u.correctionReason}` : ""}
                          </Badge>
                        )}
                        {u.note && <p className="mt-1 text-muted-foreground">“{u.note}”</p>}
                        {u.issueReason && <p className="mt-1 text-orange-700 text-xs">Issue: {u.issueReason}</p>}
                      </div>
                    ))
                  )}
                </TabsContent>

                <TabsContent value="inspections" className="mt-3 max-h-72 overflow-y-auto space-y-2">
                  {detail.history.inspections.length === 0 ? (
                    <p className="text-sm text-muted-foreground py-4 text-center">No inspections recorded yet.</p>
                  ) : (
                    detail.history.inspections.map((i) => (
                      <div key={i.id} className="border rounded-lg p-3 text-sm">
                        <div className="flex items-center justify-between gap-2 flex-wrap">
                          <p className="font-semibold">
                            <Badge
                              variant="outline"
                              className={
                                i.result === "APPROVED"
                                  ? "bg-green-600/10 text-green-700 border-green-600/30"
                                  : "bg-orange-600/10 text-orange-700 border-orange-600/30"
                              }
                            >
                              {i.result === "APPROVED" ? "Approved" : "Changes requested"}
                            </Badge>{" "}
                            <span className="font-normal text-muted-foreground">by {i.inspector.name}</span>
                          </p>
                          <p className="text-xs text-muted-foreground">{formatDateTime(i.inspectedAt)}</p>
                        </div>
                        {i.inspectionNotes && <p className="mt-1 text-muted-foreground">{i.inspectionNotes}</p>}
                        {i.correctionInstructions && (
                          <p className="mt-1 text-orange-700">Corrections: {i.correctionInstructions}</p>
                        )}
                      </div>
                    ))
                  )}
                </TabsContent>

                <TabsContent value="assignments" className="mt-3 max-h-72 overflow-y-auto space-y-2">
                  {detail.history.assignments.length === 0 ? (
                    <p className="text-sm text-muted-foreground py-4 text-center">No assignments recorded yet.</p>
                  ) : (
                    detail.history.assignments.map((a) => (
                      <div key={a.id} className="border rounded-lg p-3 text-sm">
                        <div className="flex items-center justify-between gap-2 flex-wrap">
                          <p className="font-semibold">
                            {a.previousStaff ? `${a.previousStaff.name} → ` : ""}
                            {a.newStaff.name}
                            <span className="font-normal text-muted-foreground"> · by {a.assignedBy.name}</span>
                          </p>
                          <p className="text-xs text-muted-foreground">{formatDateTime(a.createdAt)}</p>
                        </div>
                        {a.reason && <p className="mt-1 text-muted-foreground">Reason: {a.reason}</p>}
                      </div>
                    ))
                  )}
                </TabsContent>

                <TabsContent value="events" className="mt-3 max-h-72 overflow-y-auto">
                  <div className="space-y-0">
                    {detail.history.events.length === 0 ? (
                      <p className="text-sm text-muted-foreground py-4 text-center">No events recorded.</p>
                    ) : (
                      detail.history.events.map((e) => (
                        <div key={e.id} className="flex gap-3 text-sm py-2 border-b last:border-0">
                          <div className="w-32 shrink-0 text-xs text-muted-foreground pt-0.5">
                            {formatDateTime(e.createdAt)}
                          </div>
                          <div className="min-w-0">
                            <p>
                              <span className="font-medium">{EVENT_TYPE_LABELS[e.eventType] ?? e.eventType}</span>{" "}
                              <span className="text-muted-foreground">— {e.actor.name}</span>
                            </p>
                            {(e.previousValue || e.newValue) && (
                              <p className="text-xs text-muted-foreground">
                                {e.previousValue ? `${e.previousValue} → ` : ""}
                                {e.newValue ?? ""}
                              </p>
                            )}
                            {e.reason && <p className="text-xs text-muted-foreground/90 italic mt-0.5">“{e.reason}”</p>}
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                </TabsContent>
              </Tabs>
            </div>
          </>
        )}
      </DialogContent>
    </Dialog>
  );
}
