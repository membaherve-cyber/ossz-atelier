<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InspectionRecord;
use App\Models\ProductionStage;
use App\Models\ProductionTask;
use App\Models\TaskAssignment;
use App\Models\TaskComment;
use App\Models\TaskProgressHistory;
use App\Models\TaskStageState;
use App\Models\TaskStageUpdate;
use App\Models\TaskStatusChange;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\Permissions;
use App\Services\ProgressService;
use App\Support\Atelier;
use App\Support\Trans;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Task workflow actions (spec §3: POST /tasks/{id}/assign|status|progress|
 * stages|inspect|comment|block|unblock|update).
 *
 * Every action is a 1:1 port of the Next.js reference implementation:
 * - PATCH src/app/api/atelier/tasks/[id]/route.ts — assign, update, cancel,
 *   block, unblock, submit, inspect, ready, deliver (status-transition
 *   gates, notifications, audit codes ported verbatim);
 * - POST src/app/api/atelier/tasks/[id]/progress/route.ts — progress;
 * - POST src/app/api/atelier/tasks/[id]/stages/route.ts — checklist;
 * - POST src/app/api/atelier/tasks/[id]/comments/route.ts — notes/issues.
 * The `status` endpoint additionally offers "start" and "reopen" verbs per
 * the controller/view contract.
 *
 * Access rules: a task may only be touched by its assignee or a supervisor
 * (MANAGEMENT / ADMIN); STAFF users reaching another user's task get 403.
 * Terminal tasks (DELIVERED / CANCELLED) reject every action, like the
 * outer gate of the Next.js PATCH handler.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class TaskActionController
{
    /**
     * Statuses in which the checklist / progress can be updated by the
     * assignee (supervisors may always correct, mirroring route.ts).
     */
    private const IN_WORK_STATUSES = ['ASSIGNED', 'ONGOING', 'CHANGES_REQUESTED'];

    /**
     * POST /tasks/{id}/assign — assign or reassign the task (route perm
     * production.assign: MANAGEMENT / ADMIN only).
     *
     * Ported from the ASSIGN case of tasks/[id]/route.ts: the new status
     * only moves UNASSIGNED -> ASSIGNED; the assignment history row is
     * typed ASSIGN or REASSIGN; the new assignee is notified (message
     * mirrors the reference implementation exactly — the old assignee is
     * NOT notified, matching route.ts).
     */
    public function assign(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        if ($task->isTerminal()) {
            return $this->closedTaskError($task);
        }

        $data = $request->validate([
            'staffId' => [
                'required',
                'string',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $staff = User::query()->findOrFail($data['staffId']);

        if (! in_array($staff->role, Permissions::assignableRoles(), true)) {
            return $this->actionError($task, __('td.toast.selectStaff'));
        }

        if ($staff->id === $task->assigned_staff_id) {
            return $this->actionError($task, __('td.toast.selectStaff'));
        }

        $reassign = $task->assigned_staff_id !== null;
        $fromStatus = $task->status;
        $newStatus = $fromStatus === 'UNASSIGNED' ? 'ASSIGNED' : $fromStatus;
        $note = trim((string) ($data['note'] ?? ''));

        DB::transaction(function () use ($task, $staff, $me, $reassign, $newStatus, $note): void {
            $task->forceFill([
                'assigned_staff_id' => $staff->id,
                'status' => $newStatus,
            ])->save();

            TaskAssignment::query()->create([
                'task_id' => $task->id,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'assigned_by_id' => $me->id,
                'assigned_by_name' => $me->name,
                'type' => $reassign ? 'REASSIGN' : 'ASSIGN',
                'note' => $note !== '' ? $note : null,
            ]);
        });

        if ($newStatus !== $fromStatus) {
            $this->recordStatusChange($task, $fromStatus, $newStatus, $me, $note !== '' ? $note : null);
        }

        $message = $this->taskLabel($task).' has been '.($reassign ? 'reassigned' : 'assigned')
            .' to you.'.($note !== '' ? ' Note: '.$note : '');

        Notifier::users(
            [$staff->id],
            $reassign ? 'TASK_REASSIGNED' : 'TASK_ASSIGNED',
            $message,
            $task->id,
            $task->order_id
        );

        Audit::log($me, $reassign ? 'TASK_REASSIGNED' : 'TASK_ASSIGNED', 'TASK', $task->id, [
            'staff' => $staff->name,
            'note' => $note !== '' ? $note : null,
        ]);

        return redirect()
            ->route('tasks.show', $task->id)
            ->with('success', Trans::fill($reassign ? 'td.toast.reassignedTo' : 'td.toast.assignedTo', ['name' => $staff->name]));
    }

    /**
     * POST /tasks/{id}/status — workflow transitions driven by the hidden
     * `action` field: start|submit|ready|deliver|reopen|cancel (view
     * contract). Gates, side effects, notifications and audit codes are
     * ported from the SUBMIT / READY / DELIVER / CANCEL cases of
     * tasks/[id]/route.ts; "start" and "reopen" implement the contract
     * additions (reopen mirrors the REOPENED inspection decision).
     */
    public function status(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        if ($task->isTerminal()) {
            return $this->closedTaskError($task);
        }

        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['start', 'submit', 'ready', 'deliver', 'reopen', 'cancel'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = (string) $data['action'];
        $note = trim((string) ($data['note'] ?? ''));
        $supervisor = $this->isSupervisor($me);

        switch ($action) {
            case 'start': {
                // Contract: assignee or supervisor, from ASSIGNED or
                // CHANGES_REQUESTED -> ONGOING.
                if (! in_array($task->status, ['ASSIGNED', 'CHANGES_REQUESTED'], true)) {
                    return $this->actionError($task, __('td.actionFailed'));
                }

                $fromStatus = $task->status;
                $task->forceFill(['status' => 'ONGOING'])->save();
                $this->recordStatusChange($task, $fromStatus, 'ONGOING', $me, $note !== '' ? $note : null);

                return redirect()
                    ->route('tasks.show', $task->id)
                    ->with('success', Trans::fill('td.hist.statusChange', [
                        'from' => __('labels.status.'.$fromStatus),
                        'to' => __('labels.status.ONGOING'),
                    ]));
            }

            case 'submit': {
                if (! in_array($task->status, self::IN_WORK_STATUSES, true)) {
                    return $this->actionError($task, __('td.actionFailed'));
                }

                // Assignees must reach 100% (or complete every applicable
                // stage) before submitting; supervisors may force-submit.
                if (! $supervisor && ! $this->readyForInspection($task)) {
                    return $this->actionError($task, __('td.reach100'));
                }

                $fromStatus = $task->status;
                $task->forceFill(['status' => 'AWAITING_INSPECTION'])->save();
                $this->recordStatusChange($task, $fromStatus, 'AWAITING_INSPECTION', $me, $note !== '' ? $note : null);

                if ($note !== '') {
                    TaskComment::query()->create([
                        'task_id' => $task->id,
                        'user_id' => $me->id,
                        'user_name' => $me->name,
                        'message' => 'Submitted for inspection: '.$note,
                    ]);
                }

                Notifier::management(
                    'SUBMITTED_FOR_INSPECTION',
                    $me->name.' submitted '.$this->taskLabel($task).' for inspection.',
                    $task->id,
                    $task->order_id
                );

                Audit::log($me, 'SUBMITTED_FOR_INSPECTION', 'TASK', $task->id, [
                    'note' => $note !== '' ? $note : null,
                ]);

                return redirect()
                    ->route('tasks.show', $task->id)
                    ->with('success', __('td.toast.submitted'));
            }

            case 'ready': {
                if (! $supervisor || ! Permissions::allows($me, 'production.inspect')) {
                    abort(403);
                }

                if ($task->status !== 'APPROVED') {
                    return $this->actionError($task, __('td.actionFailed'));
                }

                $fromStatus = $task->status;
                $task->forceFill(['status' => 'READY_FOR_PICKUP'])->save();
                $this->recordStatusChange($task, $fromStatus, 'READY_FOR_PICKUP', $me);

                Notifier::management(
                    'READY_FOR_PICKUP',
                    $this->taskLabel($task).' is approved and ready for customer pickup.',
                    $task->id,
                    $task->order_id
                );

                if ($task->assigned_staff_id !== null) {
                    Notifier::users(
                        [$task->assigned_staff_id],
                        'READY_FOR_PICKUP',
                        $this->taskLabel($task).' is ready for customer pickup.',
                        $task->id,
                        $task->order_id
                    );
                }

                Audit::log($me, 'TASK_READY_FOR_PICKUP', 'TASK', $task->id);

                return redirect()
                    ->route('tasks.show', $task->id)
                    ->with('success', __('td.toast.ready'));
            }

            case 'deliver': {
                if (! $supervisor || ! Permissions::allows($me, 'production.inspect')) {
                    abort(403);
                }

                if ($task->status !== 'READY_FOR_PICKUP') {
                    return $this->actionError($task, __('td.actionFailed'));
                }

                $fromStatus = $task->status;
                $task->forceFill([
                    'status' => 'DELIVERED',
                    'delivered_at' => now(),
                ])->save();
                $this->recordStatusChange($task, $fromStatus, 'DELIVERED', $me, $note !== '' ? $note : null);

                $message = $this->taskLabel($task).' has been delivered/collected by the customer.';

                Notifier::management('TASK_DELIVERED', $message, $task->id, $task->order_id);

                if ($task->assigned_staff_id !== null) {
                    Notifier::users([$task->assigned_staff_id], 'TASK_DELIVERED', $message, $task->id, $task->order_id);
                }

                Audit::log($me, 'TASK_DELIVERED', 'TASK', $task->id, [
                    'note' => $note !== '' ? $note : null,
                ]);

                return redirect()
                    ->route('tasks.show', $task->id)
                    ->with('success', __('td.toast.delivered'));
            }

            case 'reopen': {
                if (! $supervisor) {
                    abort(403);
                }

                if (! in_array($task->status, ['APPROVED', 'READY_FOR_PICKUP', 'AWAITING_INSPECTION', 'CHANGES_REQUESTED'], true)) {
                    return $this->actionError($task, __('td.actionFailed'));
                }

                $reason = trim((string) $request->input('reason', ''));

                if ($reason === '' || mb_strlen($reason) < 3) {
                    throw ValidationException::withMessages([
                        'reason' => Trans::fill('td.toast.reasonRequiredDesc', [
                            'field' => __('td.dialog.reopenPlaceholder'),
                            'min' => 3,
                        ]),
                    ]);
                }

                $fromStatus = $task->status;
                $task->forceFill([
                    'status' => 'ONGOING',
                    'completed_at' => null,
                ])->save();
                $this->recordStatusChange($task, $fromStatus, 'ONGOING', $me, $reason);

                if ($task->assigned_staff_id !== null) {
                    Notifier::users(
                        [$task->assigned_staff_id],
                        'TASK_REOPENED',
                        $this->taskLabel($task).' has been reopened for production.',
                        $task->id,
                        $task->order_id
                    );
                }

                return redirect()
                    ->route('tasks.show', $task->id)
                    ->with('success', __('td.toast.reopened'));
            }

            case 'cancel': {
                if (! $supervisor || ! Permissions::allows($me, 'production.cancel')) {
                    abort(403);
                }

                $reason = trim((string) $request->input('reason', ''));

                if ($reason === '' || mb_strlen($reason) < 3) {
                    throw ValidationException::withMessages([
                        'reason' => Trans::fill('td.toast.reasonRequiredDesc', [
                            'field' => __('td.reason.cancel'),
                            'min' => 3,
                        ]),
                    ]);
                }

                $fromStatus = $task->status;
                $task->forceFill([
                    'status' => 'CANCELLED',
                    'cancel_reason' => $reason,
                ])->save();
                $this->recordStatusChange($task, $fromStatus, 'CANCELLED', $me, $reason);

                if ($task->assigned_staff_id !== null) {
                    Notifier::users(
                        [$task->assigned_staff_id],
                        'TASK_CANCELLED',
                        $this->taskLabel($task).' has been cancelled. Reason: '.$reason,
                        $task->id,
                        $task->order_id
                    );
                }

                Audit::log($me, 'TASK_CANCELLED', 'TASK', $task->id, ['reason' => $reason]);

                return redirect()
                    ->route('tasks.show', $task->id)
                    ->with('success', __('td.toast.cancelled'));
            }
        }

        return $this->actionError($task, __('td.actionFailed'));
    }

    /**
     * POST /tasks/{id}/progress — manual numerical progress update
     * (ported from tasks/[id]/progress/route.ts). 100% never auto-approves:
     * inspection submission stays an explicit action. The first positive
     * value on an ASSIGNED task moves it to ONGOING.
     */
    public function progress(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        $supervisor = $this->isSupervisor($me);

        if ($task->isTerminal()) {
            return $this->closedTaskError($task);
        }

        if (! $supervisor && ! in_array($task->status, self::IN_WORK_STATUSES, true)) {
            return $this->actionError($task, __('td.toast.progressFailed'));
        }

        // View contract names the field "value"; fall back to the
        // reference implementation's "progress" name just in case.
        if ($request->missing('value') && $request->has('progress')) {
            $request->merge(['value' => $request->input('progress')]);
        }

        $data = $request->validate([
            'value' => ['required', 'integer', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $value = (int) $data['value'];
        $previous = (int) $task->progress;

        if ($value === $previous) {
            return $this->actionError($task, __('td.toast.progressFailed'));
        }

        $note = trim((string) ($data['note'] ?? ''));
        $fromStatus = $task->status;
        $newStatus = $fromStatus === 'ASSIGNED' && $value > 0 ? 'ONGOING' : $fromStatus;

        DB::transaction(function () use ($task, $me, $value, $previous, $newStatus, $note): void {
            $task->forceFill([
                'progress' => $value,
                'status' => $newStatus,
            ])->save();

            TaskProgressHistory::query()->create([
                'task_id' => $task->id,
                'user_id' => $me->id,
                'user_name' => $me->name,
                'previous_progress' => $previous,
                'new_progress' => $value,
                'source' => 'MANUAL',
                'note' => $note !== '' ? $note : null,
            ]);
        });

        if ($newStatus !== $fromStatus) {
            $this->recordStatusChange($task, $fromStatus, $newStatus, $me, 'Production started');
        }

        Audit::log($me, 'PROGRESS_UPDATED', 'TASK', $task->id, [
            'from' => $previous,
            'to' => $value,
            'note' => $note !== '' ? $note : null,
            'correction' => $supervisor,
        ]);

        return redirect()
            ->route('tasks.show', $task->id)
            ->with('success', Trans::fill('td.toast.progressUpdated', ['value' => $value]));
    }

    /**
     * POST /tasks/{id}/stages — production checklist update (ported from
     * tasks/[id]/stages/route.ts). Every change is recorded immutably;
     * progress is recalculated via ProgressService (never decreases); the
     * first change on an ASSIGNED task moves it to ONGOING.
     */
    public function stages(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        $supervisor = $this->isSupervisor($me);

        if ($task->isTerminal()) {
            return $this->closedTaskError($task);
        }

        if (! $supervisor && ! in_array($task->status, self::IN_WORK_STATUSES, true)) {
            return $this->actionError($task, __('td.toast.checklistFailed'));
        }

        $data = $request->validate([
            'updates' => ['required', 'array', 'min:1', 'max:30'],
            'updates.*.stageId' => ['required', 'string'],
            'updates.*.state' => ['required', 'string', Rule::in(Atelier::STAGE_STATES)],
            'updates.*.comment' => ['nullable', 'string', 'max:500'],
        ]);

        // Only stages applicable to this task's category may be updated.
        $applicable = ProductionStage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(
                static fn (ProductionStage $stage): bool => Atelier::stageAppliesTo(
                    (string) $stage->applies_to,
                    (string) $task->category
                )
            )
            ->keyBy('id');

        foreach ($data['updates'] as $update) {
            if (! $applicable->has((string) $update['stageId'])) {
                return $this->actionError($task, __('td.toast.checklistFailed'));
            }
        }

        $existing = TaskStageState::query()
            ->where('task_id', $task->id)
            ->get()
            ->keyBy('stage_id');

        $applied = [];

        DB::transaction(function () use ($data, $task, $me, $applicable, $existing, &$applied): void {
            foreach ($data['updates'] as $update) {
                /** @var ProductionStage $stage */
                $stage = $applicable->get((string) $update['stageId']);
                $newState = (string) $update['state'];
                $previous = $existing->has($stage->id)
                    ? (string) $existing->get($stage->id)->state
                    : 'NOT_STARTED';

                if ($previous === $newState) {
                    continue;
                }

                $comment = trim((string) ($update['comment'] ?? ''));

                TaskStageUpdate::query()->create([
                    'task_id' => $task->id,
                    'stage_id' => $stage->id,
                    'stage_name' => $stage->name,
                    'user_id' => $me->id,
                    'user_name' => $me->name,
                    'previous_state' => $previous,
                    'new_state' => $newState,
                    'comment' => $comment !== '' ? $comment : null,
                ]);

                if ($existing->has($stage->id)) {
                    $existing->get($stage->id)->forceFill([
                        'state' => $newState,
                        'updated_by_id' => $me->id,
                        'updated_by_name' => $me->name,
                    ])->save();
                } else {
                    TaskStageState::query()->create([
                        'task_id' => $task->id,
                        'stage_id' => $stage->id,
                        'state' => $newState,
                        'updated_by_id' => $me->id,
                        'updated_by_name' => $me->name,
                    ]);
                }

                $applied[] = [
                    'stageName' => $stage->name,
                    'previousState' => $previous,
                    'newState' => $newState,
                ];
            }
        });

        if ($applied === []) {
            return $this->actionError($task, __('td.toast.checklistFailed'));
        }

        // Recalculate numerical progress from the checklist.
        ProgressService::recompute($task);

        // First update moves an assigned task into Ongoing.
        if ($task->status === 'ASSIGNED') {
            $task->forceFill(['status' => 'ONGOING'])->save();
            $this->recordStatusChange($task, 'ASSIGNED', 'ONGOING', $me, 'Production started');
        }

        Audit::log($me, 'STAGES_UPDATED', 'TASK', $task->id, ['changes' => $applied]);

        return redirect()
            ->route('tasks.show', $task->id)
            ->with('success', __('td.toast.checklistUpdated'));
    }

    /**
     * POST /tasks/{id}/inspect — quality inspection (route perm
     * production.inspect: MANAGEMENT / ADMIN only). Ported from the INSPECT
     * case of tasks/[id]/route.ts.
     */
    public function inspect(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        if ($task->isTerminal()) {
            return $this->closedTaskError($task);
        }

        $data = $request->validate([
            'decision' => ['required', 'string', Rule::in(Atelier::INSPECTION_DECISIONS)],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $decision = (string) $data['decision'];
        $comments = trim((string) ($data['comments'] ?? ''));

        if (in_array($decision, ['APPROVED', 'CHANGES_REQUESTED'], true) && $task->status !== 'AWAITING_INSPECTION') {
            return $this->actionError($task, __('td.actionFailed'));
        }

        if ($decision === 'REOPENED' && ! in_array($task->status, ['APPROVED', 'READY_FOR_PICKUP'], true)) {
            return $this->actionError($task, __('td.actionFailed'));
        }

        if (in_array($decision, ['CHANGES_REQUESTED', 'REOPENED'], true) && mb_strlen($comments) < 3) {
            return $this->actionError($task, __('td.toast.correctionsRequired'));
        }

        $fromStatus = $task->status;
        $previousProgress = (int) $task->progress;

        if ($decision === 'APPROVED') {
            DB::transaction(function () use ($task, $me, $previousProgress): void {
                $task->forceFill([
                    'status' => 'APPROVED',
                    'completed_at' => $task->completed_at ?? now(),
                    'progress' => 100,
                ])->save();

                if ($previousProgress < 100) {
                    TaskProgressHistory::query()->create([
                        'task_id' => $task->id,
                        'user_id' => $me->id,
                        'user_name' => $me->name,
                        'previous_progress' => $previousProgress,
                        'new_progress' => 100,
                        'source' => 'INSPECTION',
                        'note' => 'Set to 100% on approval',
                    ]);
                }
            });

            $this->recordStatusChange($task, $fromStatus, 'APPROVED', $me, $comments !== '' ? $comments : null);

            if ($task->assigned_staff_id !== null) {
                Notifier::users(
                    [$task->assigned_staff_id],
                    'TASK_APPROVED',
                    $this->taskLabel($task).' passed quality inspection. Well done!',
                    $task->id,
                    $task->order_id
                );
            }

            $toast = 'td.toast.inspectionApproved';
        } elseif ($decision === 'CHANGES_REQUESTED') {
            $task->forceFill(['status' => 'CHANGES_REQUESTED'])->save();
            $this->recordStatusChange($task, $fromStatus, 'CHANGES_REQUESTED', $me, $comments);

            if ($task->assigned_staff_id !== null) {
                Notifier::users(
                    [$task->assigned_staff_id],
                    'CORRECTIONS_REQUESTED',
                    'Corrections were requested on '.$this->taskLabel($task).': '.$comments,
                    $task->id,
                    $task->order_id
                );
            }

            $toast = 'td.toast.correctionsRequested';
        } else {
            $task->forceFill([
                'status' => 'ONGOING',
                'completed_at' => null,
            ])->save();
            $this->recordStatusChange($task, $fromStatus, 'ONGOING', $me, $comments !== '' ? $comments : null);

            if ($task->assigned_staff_id !== null) {
                Notifier::users(
                    [$task->assigned_staff_id],
                    'TASK_REOPENED',
                    $this->taskLabel($task).' has been reopened for production.',
                    $task->id,
                    $task->order_id
                );
            }

            $toast = 'td.toast.reopened';
        }

        InspectionRecord::query()->create([
            'task_id' => $task->id,
            'inspector_id' => $me->id,
            'inspector_name' => $me->name,
            'decision' => $decision,
            'comments' => $comments !== '' ? $comments : null,
        ]);

        Audit::log($me, 'INSPECTION_COMPLETED', 'TASK', $task->id, [
            'decision' => $decision,
            'comments' => $comments !== '' ? $comments : null,
        ]);

        return redirect()
            ->route('tasks.show', $task->id)
            ->with('success', __($toast));
    }

    /**
     * POST /tasks/{id}/comment — production note or reported issue
     * (ported from tasks/[id]/comments/route.ts). Assignee or supervisor
     * while the task is not terminal; issues notify Management.
     */
    public function comment(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        if ($task->isTerminal()) {
            return $this->closedTaskError($task);
        }

        // View contract names the field "body"; fall back to the
        // reference implementation's "message" name just in case.
        if ($request->missing('body') && $request->has('message')) {
            $request->merge(['body' => $request->input('message')]);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'isIssue' => ['nullable', 'boolean'],
        ]);

        $body = trim((string) $data['body']);

        if ($body === '') {
            return $this->actionError($task, __('td.toast.emptyCommentNote'));
        }

        $isIssue = $request->boolean('isIssue');

        TaskComment::query()->create([
            'task_id' => $task->id,
            'user_id' => $me->id,
            'user_name' => $me->name,
            'message' => $body,
            'is_issue' => $isIssue,
        ]);

        if ($isIssue) {
            Notifier::management(
                'ISSUE_REPORTED',
                $me->name.' reported an issue on '.$task->code.' — '.$task->garment_piece
                    .' ('.$task->order->order_number.'): '.$body,
                $task->id,
                $task->order_id
            );

            Audit::log($me, 'ISSUE_REPORTED', 'TASK', $task->id, ['message' => $body]);

            return redirect()
                ->route('tasks.show', $task->id)
                ->with('success', __('td.toast.issueReported'));
        }

        Audit::log($me, 'COMMENT_ADDED', 'TASK', $task->id);

        return redirect()
            ->route('tasks.show', $task->id)
            ->with('success', __('td.toast.noteAdded'));
    }

    /**
     * POST /tasks/{id}/block — report a production blocker (ported from the
     * BLOCK case of tasks/[id]/route.ts): only in-work tasks, reason
     * required, Management notified immediately.
     */
    public function block(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        if ($task->isTerminal()) {
            return $this->closedTaskError($task);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if (! in_array($task->status, self::IN_WORK_STATUSES, true)) {
            return $this->actionError($task, __('td.actionFailed'));
        }

        $reason = trim((string) $data['reason']);

        $fromStatus = $task->status;
        $task->forceFill([
            'status' => 'BLOCKED',
            'blocked_reason' => $reason,
        ])->save();
        $this->recordStatusChange($task, $fromStatus, 'BLOCKED', $me, $reason);

        Notifier::management(
            'TASK_BLOCKED',
            $me->name.' blocked '.$this->taskLabel($task).'. Reason: '.$reason,
            $task->id,
            $task->order_id
        );

        Audit::log($me, 'TASK_BLOCKED', 'TASK', $task->id, ['reason' => $reason]);

        return redirect()
            ->route('tasks.show', $task->id)
            ->with('success', __('td.toast.blocked'));
    }

    /**
     * POST /tasks/{id}/unblock — clear a blocker (supervisor only, per the
     * view contract; ported from the UNBLOCK case of tasks/[id]/route.ts):
     * the task returns to Ongoing when progress exists, ASSIGNED otherwise.
     */
    public function unblock(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        if (! $this->isSupervisor($me)) {
            abort(403);
        }

        if ($task->status !== 'BLOCKED') {
            return $this->actionError($task, __('td.actionFailed'));
        }

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $note = trim((string) ($data['note'] ?? ''));

        $fromStatus = $task->status;
        $newStatus = (int) $task->progress > 0 ? 'ONGOING' : 'ASSIGNED';

        $task->forceFill([
            'status' => $newStatus,
            'blocked_reason' => null,
        ])->save();
        $this->recordStatusChange($task, $fromStatus, $newStatus, $me, $note !== '' ? $note : null);

        if ($task->assigned_staff_id !== null) {
            Notifier::users(
                [$task->assigned_staff_id],
                'BLOCK_RESOLVED',
                $this->taskLabel($task).' is no longer blocked. You can resume production.',
                $task->id,
                $task->order_id
            );
        }

        Audit::log($me, 'BLOCK_RESOLVED', 'TASK', $task->id, ['note' => $note !== '' ? $note : null]);

        return redirect()
            ->route('tasks.show', $task->id)
            ->with('success', __('td.toast.unblocked'));
    }

    /**
     * POST /tasks/{id}/update — edit task details (route perm
     * production.manage.details: MANAGEMENT / ADMIN only). Ported from the
     * UPDATE case of tasks/[id]/route.ts with form semantics: only fields
     * whose submitted value differs from the current one are applied
     * (deadline / priority / instructions / garment piece); deadline and
     * instruction changes notify the assignee.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $task = $this->loadTask($id);
        $this->authorize($me, $task);

        if ($task->isTerminal()) {
            return $this->closedTaskError($task);
        }

        $data = $request->validate([
            'deadline' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', Rule::in(Atelier::PRIORITIES)],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'garmentPiece' => ['nullable', 'string', 'max:100'],
        ]);

        $update = [];
        $changes = [];
        $deadlineChanged = false;
        $instructionsChanged = false;

        // The edit dialog always submits every field, so a present-but-empty
        // deadline is an EXPLICIT clear (not "keep the old value").
        if (array_key_exists('deadline', $data)) {
            $deadlineInput = trim((string) ($data['deadline'] ?? ''));

            if ($deadlineInput === '') {
                if ($task->deadline !== null) {
                    $update['deadline'] = null;
                    $changes['deadline'] = [
                        'from' => $task->deadline->toIso8601String(),
                        'to' => null,
                    ];
                    $deadlineChanged = true;
                }
            } else {
                $newDeadline = Carbon::parse($deadlineInput);

                if ($task->deadline === null
                    || $newDeadline->format('YmdHi') !== $task->deadline->format('YmdHi')) {
                    $update['deadline'] = $newDeadline;
                    $changes['deadline'] = [
                        'from' => $task->deadline?->toIso8601String(),
                        'to' => $newDeadline->toIso8601String(),
                    ];
                    $deadlineChanged = true;
                }
            }
        }

        $priorityInput = trim((string) ($data['priority'] ?? ''));

        if ($priorityInput !== '' && $priorityInput !== (string) $task->priority) {
            $update['priority'] = $priorityInput;
            $changes['priority'] = ['from' => $task->priority, 'to' => $priorityInput];
        }

        // Same explicit-clear semantics for the instructions field.
        if (array_key_exists('instructions', $data)) {
            $instructionsInput = trim((string) ($data['instructions'] ?? ''));

            if ($instructionsInput === '') {
                if ((string) $task->instructions !== '') {
                    $update['instructions'] = null;
                    $changes['instructions'] = 'cleared';
                    $instructionsChanged = true;
                }
            } elseif ($instructionsInput !== (string) $task->instructions) {
                $update['instructions'] = $instructionsInput;
                $changes['instructions'] = 'updated';
                $instructionsChanged = true;
            }
        }

        $garmentPieceInput = trim((string) ($data['garmentPiece'] ?? ''));

        if ($garmentPieceInput !== '' && $garmentPieceInput !== (string) $task->garment_piece) {
            $clash = ProductionTask::query()
                ->where('order_id', $task->order_id)
                ->where('garment_piece', $garmentPieceInput)
                ->where('status', '!=', 'CANCELLED')
                ->where('id', '!=', $task->id)
                ->exists();

            if ($clash) {
                throw ValidationException::withMessages([
                    'garmentPiece' => 'Piece "'.$garmentPieceInput.'" already exists on this order.',
                ]);
            }

            $update['garment_piece'] = $garmentPieceInput;
        }

        if ($update === []) {
            return $this->actionError($task, __('td.toast.nothingToUpdate'));
        }

        $task->forceFill($update)->save();

        if ($deadlineChanged && $task->assigned_staff_id !== null) {
            Notifier::users(
                [$task->assigned_staff_id],
                'DEADLINE_CHANGED',
                'The deadline of '.$this->taskLabel($task).' has been changed.',
                $task->id,
                $task->order_id
            );
        }

        if ($instructionsChanged && $task->assigned_staff_id !== null) {
            Notifier::users(
                [$task->assigned_staff_id],
                'NEW_INSTRUCTIONS',
                'New production instructions were added to '.$this->taskLabel($task).'.',
                $task->id,
                $task->order_id
            );
        }

        Audit::log($me, 'TASK_UPDATED', 'TASK', $task->id, $changes);

        return redirect()
            ->route('tasks.show', $task->id)
            ->with('success', __('td.toast.detailsUpdated'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Load the task (404 when missing) with its order + customer for the
     * notification labels.
     */
    private function loadTask(string $id): ProductionTask
    {
        return ProductionTask::query()->with('order.customer')->findOrFail($id);
    }

    /**
     * Assignee or supervisor only (= canAccessTask in tasks.ts); anything
     * else is a hard 403.
     */
    private function authorize(User $user, ProductionTask $task): void
    {
        if (! $this->isSupervisor($user) && $task->assigned_staff_id !== $user->id) {
            abort(403);
        }
    }

    /** MANAGEMENT / ADMIN accounts. */
    private function isSupervisor(User $user): bool
    {
        return in_array($user->role, ['MANAGEMENT', 'ADMIN'], true);
    }

    /** "TASK-0001 — Jacket (ORD-0001)" label used in notifications. */
    private function taskLabel(ProductionTask $task): string
    {
        return $task->code.' — '.$task->garment_piece.' ('.$task->order->order_number.')';
    }

    /**
     * Write the immutable status-change row + the generic
     * TASK_STATUS_CHANGED audit entry (= changeTaskStatus in tasks.ts).
     */
    private function recordStatusChange(
        ProductionTask $task,
        string $fromStatus,
        string $toStatus,
        User $user,
        ?string $reason = null
    ): void {
        TaskStatusChange::query()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
        ]);

        Audit::log($user, 'TASK_STATUS_CHANGED', 'TASK', $task->id, [
            'fromStatus' => $fromStatus,
            'toStatus' => $toStatus,
            'reason' => $reason,
        ]);
    }

    /**
     * Whether an assignee may submit the task for inspection: manual
     * progress reached 100% OR every applicable stage is COMPLETED (view
     * contract; supervisors bypass this check like in route.ts).
     */
    private function readyForInspection(ProductionTask $task): bool
    {
        if ((int) $task->progress >= 100) {
            return true;
        }

        $stages = ProductionStage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(
                static fn (ProductionStage $stage): bool => Atelier::stageAppliesTo(
                    (string) $stage->applies_to,
                    (string) $task->category
                )
            );

        if ($stages->isEmpty()) {
            return false;
        }

        $states = TaskStageState::query()
            ->where('task_id', $task->id)
            ->get()
            ->keyBy('stage_id');

        foreach ($stages as $stage) {
            $state = $states->has($stage->id)
                ? (string) $states->get($stage->id)->state
                : 'NOT_STARTED';

            if ($state !== 'COMPLETED') {
                return false;
            }
        }

        return true;
    }

    /** Business-rule failure: back to the task page with an error flash. */
    private function actionError(ProductionTask $task, string $message): RedirectResponse
    {
        return redirect()
            ->route('tasks.show', $task->id)
            ->with('error', $message);
    }

    /** Terminal task: no action is possible any more (route.ts gate). */
    private function closedTaskError(ProductionTask $task): RedirectResponse
    {
        return $this->actionError($task, __('td.noActions').__('td.noActionsClosed'));
    }
}
