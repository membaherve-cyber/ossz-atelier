@extends('layouts.app')

@section('title', __('shell.view.taskDetail'))

@section('content')
    @php
        $locale = app()->getLocale();

        // Derived workflow flags (1:1 port of task-detail-view.tsx).
        $terminal = $task->isTerminal();
        $inWork = in_array($task->status, ['ASSIGNED', 'ONGOING', 'CHANGES_REQUESTED'], true);

        $mayUpdateProgress = ($isSupervisor || $isAssignee) && $inWork;
        $mayEditChecklist = ($isSupervisor && ! $terminal) || ($isAssignee && $inWork);
        $mayComment = ($isSupervisor || $isAssignee) && ! $terminal;

        $canAssign = $isSupervisor && ! $terminal;
        $canEdit = $isSupervisor && ! $terminal;
        $canSubmit = ($isSupervisor || $isAssignee) && $inWork;
        $staffSubmitBlocked = $isAssignee && ! $isSupervisor && (int) $task->progress < 100;
        $canInspect = $isSupervisor && $task->status === 'AWAITING_INSPECTION';
        $canReady = $isSupervisor && $task->status === 'APPROVED';
        $canDeliver = $isSupervisor && $task->status === 'READY_FOR_PICKUP';
        $canReopen = $isSupervisor && in_array($task->status, ['APPROVED', 'READY_FOR_PICKUP'], true);
        $canBlock = ($isSupervisor || $isAssignee) && $inWork;
        $canUnblock = ($isSupervisor || $isAssignee) && $task->status === 'BLOCKED';
        $canCancel = $isSupervisor && ! $terminal;
        $noActions = ! $canAssign && ! $canEdit && ! $canSubmit && ! $canInspect && ! $canReady
            && ! $canDeliver && ! $canReopen && ! $canBlock && ! $canUnblock && ! $canCancel;

        $taskDone = (int) $task->progress >= 100 || in_array($task->status, ['APPROVED', 'DELIVERED'], true);
        $taskOverdue = $task->isOverdue();

        // Checklist stages applicable to this task's category (active only),
        // plus the saved state map for the Alpine engine.
        $checklistStages = $stages->filter(
            static fn ($stage) => $stage->is_active
                && \App\Support\Atelier::stageAppliesTo((string) $stage->applies_to, (string) $task->category)
        )->values();
        $savedStates = $task->stageStates->pluck('state', 'stage_id')->all();
        $hasWeights = $checklistStages->contains(static fn ($stage) => (float) $stage->weight > 1);

        // Active (non-terminal) task count per assignable staff member for
        // the "(N active)" hints in the assign dialog.
        $activeCounts = \App\Models\ProductionTask::query()
            ->whereNotIn('status', \App\Support\Atelier::terminalStatuses())
            ->whereNotNull('assigned_staff_id')
            ->groupBy('assigned_staff_id')
            ->selectRaw('assigned_staff_id, count(*) as aggregate')
            ->pluck('aggregate', 'assigned_staff_id');

        // Merged history timeline (assignments, status changes, stage
        // updates, progress, inspections, comments), newest first.
        $historyEntries = collect();
        foreach ($task->assignments as $assignment) {
            $historyEntries->push(['at' => $assignment->created_at, 'kind' => 'assignment', 'entry' => $assignment]);
        }
        foreach ($task->statusChanges as $statusChange) {
            $historyEntries->push(['at' => $statusChange->created_at, 'kind' => 'status', 'entry' => $statusChange]);
        }
        foreach ($task->stageUpdates as $stageUpdate) {
            $historyEntries->push(['at' => $stageUpdate->created_at, 'kind' => 'stage', 'entry' => $stageUpdate]);
        }
        foreach ($task->progressHistories as $progressEntry) {
            $historyEntries->push(['at' => $progressEntry->created_at, 'kind' => 'progress', 'entry' => $progressEntry]);
        }
        foreach ($task->inspections as $inspection) {
            $historyEntries->push(['at' => $inspection->created_at, 'kind' => 'inspection', 'entry' => $inspection]);
        }
        foreach ($task->comments as $commentEntry) {
            $historyEntries->push(['at' => $commentEntry->created_at, 'kind' => 'comment', 'entry' => $commentEntry]);
        }
        $historyEntries = $historyEntries
            ->sortByDesc(static fn ($item) => $item['at']?->getTimestamp() ?? 0)
            ->values();
    @endphp

    <div x-data='{
            dialog: null,
            staffId: "",
            reason: ""
         }'
         @keydown.escape.window="dialog = null">
        <a href="{{ route('tasks.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors -ml-2 mb-2 px-2 h-8 rounded-md">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            {{ __('td.backToTasks') }}
        </a>

        <div class="mb-4">
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="font-display text-2xl font-bold tracking-tight truncate">{{ $task->code }} — {{ $task->garment_piece }}</h1>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-1.5 text-sm text-muted-foreground">
                        <span class="whitespace-nowrap">
                            @if ($task->order)
                                <a href="{{ route('orders.show', $task->order->id) }}" class="font-medium hover:text-foreground hover:underline">{{ $task->order->order_number }}</a>
                            @endif
                            · {{ $task->order?->customer?->name }}
                        </span>
                        <span class="whitespace-nowrap">
                            {{ __('td.assigneeColon') }}
                            <span class="text-foreground font-medium">{{ $task->assignedStaff?->name ?? __('td.unassigned') }}</span>
                        </span>
                        @if ($task->deadline === null)
                            <span class="text-muted-foreground text-sm">—</span>
                        @elseif ($taskOverdue)
                            <span class="inline-flex items-center gap-1 whitespace-nowrap text-red-700 font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                {{ $task->deadline->locale($locale)->translatedFormat('d M Y') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 opacity-60" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></svg>
                                {{ $task->deadline->locale($locale)->translatedFormat('d M Y') }}
                            </span>
                        @endif
                        <span>{{ \App\Support\Trans::fill('td.createdOn', ['date' => $task->created_at?->locale($locale)->translatedFormat('d M Y')]) }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 shrink-0">
                    <x-badges.status :status="$task->status"/>
                    <x-badges.priority :priority="$task->priority"/>
                    <x-badges.category :category="$task->category"/>
                </div>
            </div>

            @if ($task->status === 'BLOCKED' && $task->blocked_reason)
                <div role="alert"
                     class="relative w-full rounded-lg border px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current text-destructive bg-card [&>svg]:text-current *:data-[slot=alert-description]:text-destructive/90 mt-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    <div class="col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight">{{ __('td.blockedAlert') }}</div>
                    <div class="text-destructive/90 col-start-2 grid justify-items-start gap-1 text-sm [&_p]:leading-relaxed">{{ $task->blocked_reason }}</div>
                </div>
            @endif
            @if ($task->status === 'CANCELLED')
                <div role="alert"
                     class="relative w-full rounded-lg border border-stone-300 bg-stone-50 text-stone-700 px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current mt-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>
                    <div class="col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight">{{ __('td.cancelledAlert') }}</div>
                    <div class="text-stone-600 col-start-2 grid justify-items-start gap-1 text-sm [&_p]:leading-relaxed">{{ $task->cancel_reason ?? __('td.cancelledFallback') }}</div>
                </div>
            @endif
            @if ($task->status === 'CHANGES_REQUESTED')
                <div role="alert"
                     class="relative w-full rounded-lg border border-amber-300 bg-amber-50 text-amber-800 px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current mt-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    <div class="col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight">{{ __('td.correctionsAlert') }}</div>
                    <div class="text-amber-700 col-start-2 grid justify-items-start gap-1 text-sm [&_p]:leading-relaxed">{{ __('td.correctionsAlertDesc') }}</div>
                </div>
            @endif
        </div>

        <div class="grid lg:grid-cols-3 gap-4 items-start">
            {{-- ---- Progress & checklist (left) ---- --}}
            <section class="rounded-xl border bg-card text-card-foreground shadow-sm lg:col-span-2" aria-labelledby="task-progress-title">
                <div class="flex items-center justify-between gap-2 border-b p-6 pb-3">
                    <h2 id="task-progress-title" class="font-semibold text-base">{{ __('td.progressChecklist') }}</h2>
                </div>
                <div class="p-6 pt-4 space-y-4">
                    <div class="flex items-center gap-2 min-w-28">
                        <div class="h-2 flex-1 rounded bg-muted overflow-hidden">
                            <div class="h-full rounded transition-all {{ $taskDone ? 'bg-emerald-600' : 'bg-primary' }}"
                                 style="width: {{ $task->progress }}%"></div>
                        </div>
                        <span class="text-xs font-semibold tabular-nums w-9 text-right{{ $taskDone ? ' text-emerald-700' : '' }}">{{ $task->progress }}%</span>
                    </div>
                    <p class="text-xs text-muted-foreground -mt-2">
                        {{ __('td.currentProgress') }} <span class="font-semibold">{{ $task->progress }}%</span>
                    </p>

                    @if ($mayUpdateProgress)
                        <form method="POST" action="{{ route('tasks.progress', $task->id) }}" class="rounded-lg border bg-muted/30 p-3 space-y-2">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                        <p class="text-sm font-medium">{{ __('td.updateProgressManually') }}</p>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input type="number" name="value" min="0" max="100" step="1" inputmode="numeric" required
                                       placeholder="{{ $task->progress }}" aria-label="{{ __('td.aria.newProgress') }}"
                                       class="flex h-9 sm:w-28 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <input type="text" name="note" maxlength="500" placeholder="{{ __('td.optionalNote') }}" aria-label="{{ __('td.aria.progressNote') }}"
                                       class="flex h-9 flex-1 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                                    {{ __('td.update') }}
                                </button>
                            </div>
                            @if ($isSupervisor)
                                <p class="text-xs text-muted-foreground">{{ \App\Support\Trans::fill('td.manualProgressNote', ['source' => __('td.source.MANUAL')]) }}</p>
                            @endif
                        </form>
                    @endif

                    <div class="h-px bg-border" role="separator"></div>

                    <form method="POST" action="{{ route('tasks.stages', $task->id) }}"
                          x-data='{
                              saved: @json($savedStates),
                              changes: {},
                              comment: "",
                              labels: @json([
                                  "NOT_STARTED" => __('labels.stageState.NOT_STARTED'),
                                  "IN_PROGRESS" => __('labels.stageState.IN_PROGRESS'),
                                  "COMPLETED" => __('labels.stageState.COMPLETED'),
                                  "NOT_APPLICABLE" => __('labels.stageState.NOT_APPLICABLE'),
                              ]),
                              templates: @json([
                                  "was" => __('td.wasState'),
                                  "pendingOne" => __('td.pendingChange'),
                                  "pendingMany" => __('td.pendingChanges'),
                              ]),
                              setState(stageId, state) {
                                  if (this.saved[stageId] === state) {
                                      delete this.changes[stageId];
                                  } else {
                                      this.changes[stageId] = state;
                                  }
                              },
                              effective(stageId) {
                                  if (this.changes[stageId] !== undefined) {
                                      return this.changes[stageId];
                                  }
                                  if (this.saved[stageId] !== undefined) {
                                      return this.saved[stageId];
                                  }
                                  return "NOT_STARTED";
                              },
                              get changesCount() {
                                  return Object.keys(this.changes).length;
                              },
                              pendingBadge() {
                                  if (this.changesCount === 1) {
                                      return this.templates.pendingOne;
                                  }
                                  return this.templates.pendingMany.replace("{count}", this.changesCount);
                              },
                              wasText(stageId) {
                                  var state = this.saved[stageId] !== undefined ? this.saved[stageId] : "NOT_STARTED";
                                  return this.templates.was.replace("{state}", this.labels[state] || state);
                              },
                              chipClass(state) {
                                  var map = {
                                      NOT_STARTED: "bg-stone-100 text-stone-600",
                                      IN_PROGRESS: "bg-amber-100 text-amber-800",
                                      COMPLETED: "bg-emerald-100 text-emerald-800",
                                      NOT_APPLICABLE: "bg-muted text-muted-foreground"
                                  };
                                  return map[state] || "";
                              }
                          }'>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-medium">{{ __('td.checklistTitle') }}</p>
                            <template x-if="changesCount > 0">
                                <span class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 text-amber-800 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap"
                                      x-text="pendingBadge()"></span>
                            </template>
                        </div>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            {{ \App\Support\Trans::fill('td.checklistHelp', ['weights' => $hasWeights ? __('td.checklistHelpWeights') : '']) }}
                        </p>

                        <div class="mt-2 rounded-lg border divide-y">
                            @if ($checklistStages->isEmpty())
                                <div class="flex flex-col items-center justify-center gap-2 py-10 px-4 text-center">
                                    <div class="rounded-full bg-muted p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                                    </div>
                                    <p class="font-medium">{{ __('td.noStages.title') }}</p>
                                    <p class="text-sm text-muted-foreground max-w-sm">{{ __('td.noStages.desc') }}</p>
                                </div>
                            @else
                                @foreach ($checklistStages as $stage)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-4 py-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium flex items-center gap-2 flex-wrap">
                                                {{ $stage->name }}
                                                @if ((float) $stage->weight > 1)
                                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap bg-secondary text-secondary-foreground border-transparent">{{ \App\Support\Trans::fill('td.stageWeight', ['weight' => $stage->weight]) }}</span>
                                                @endif
                                            </p>
                                            <div class="mt-1 flex items-center gap-2 flex-wrap">
                                                <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                      :class="chipClass(effective('{{ $stage->id }}'))"
                                                      x-text="labels[effective('{{ $stage->id }}')] || effective('{{ $stage->id }}')"></span>
                                                <template x-if="changes['{{ $stage->id }}'] !== undefined && changes['{{ $stage->id }}'] !== saved['{{ $stage->id }}']">
                                                    <span class="text-xs text-amber-700" x-text="wasText('{{ $stage->id }}')"></span>
                                                </template>
                                            </div>
                                        </div>

                                        @if ($mayEditChecklist)
                                            <div class="flex flex-wrap gap-1 shrink-0" role="group"
                                                 aria-label="{{ \App\Support\Trans::fill('td.aria.stateFor', ['stage' => $stage->name]) }}">
                                                @foreach (['NOT_STARTED', 'IN_PROGRESS', 'COMPLETED', 'NOT_APPLICABLE'] as $stateOption)
                                                    <button type="button"
                                                            :aria-pressed="effective('{{ $stage->id }}') === '{{ $stateOption }}'"
                                                            aria-label="{{ \App\Support\Trans::fill('td.aria.setState', ['stage' => $stage->name, 'state' => $stateOption === 'NOT_APPLICABLE' ? 'N/A' : __('labels.stageState.'.$stateOption)]) }}"
                                                            @click="setState('{{ $stage->id }}', '{{ $stateOption }}')"
                                                            class="h-8 px-2.5 rounded-md text-xs font-medium border transition-colors"
                                                            :class="effective('{{ $stage->id }}') === '{{ $stateOption }}'
                                                                ? 'bg-primary text-primary-foreground border-primary'
                                                                : 'bg-background text-muted-foreground border-input hover:bg-accent hover:text-foreground'">
                                                        {{ $stateOption === 'NOT_APPLICABLE' ? 'N/A' : __('labels.stageState.'.$stateOption) }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @if ($mayEditChecklist && $checklistStages->isNotEmpty())
                            <div class="mt-3 space-y-2">
                                <input type="text" x-model="comment" maxlength="500"
                                       aria-label="{{ __('td.aria.stageComment') }}"
                                       placeholder="{{ __('td.stageCommentPlaceholder') }}"
                                       class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <div class="flex justify-end">
                                    <button type="submit" :disabled="changesCount === 0"
                                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-8 px-3 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:pointer-events-none disabled:opacity-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                                        {{ __('td.saveChecklist') }}<span x-show="changesCount > 0" class="tabular-nums" x-text="'(' + changesCount + ')'"></span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <template x-for="(entry, i) in Object.entries(changes)" :key="entry[0]">
                            <span>
                                <input type="hidden" :name="'updates[' + i + '][stageId]'" :value="entry[0]">
                                <input type="hidden" :name="'updates[' + i + '][state]'" :value="entry[1]">
                                <input type="hidden" :name="'updates[' + i + '][comment]'" :value="comment">
                            </span>
                        </template>
                    </form>
                </div>
            </section>

            {{-- ---- Workflow actions (right) ---- --}}
            <section class="rounded-xl border bg-card text-card-foreground shadow-sm" aria-labelledby="task-actions-title">
                <div class="flex items-center justify-between gap-2 border-b p-6 pb-3">
                    <h2 id="task-actions-title" class="font-semibold text-base">{{ __('td.workflowActions') }}</h2>
                </div>
                <div class="p-6 pt-4">
                    <div class="space-y-2">
                        @if ($canSubmit)
                            <div>
                                <button type="button" @click="dialog = 'submit'"
                                        {{ $staffSubmitBlocked ? 'disabled' : '' }}
                                        class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:pointer-events-none disabled:opacity-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
                                    {{ __('td.submitForInspection') }}
                                </button>
                                @if ($staffSubmitBlocked)
                                    <p class="text-xs text-muted-foreground mt-1">{{ __('td.reach100') }}</p>
                                @endif
                            </div>
                        @endif
                        @if ($canInspect)
                            <button type="button" @click="dialog = 'inspect'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1 1 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                                {{ __('td.inspectResult') }}
                            </button>
                        @endif
                        @if ($canReady)
                            <form method="POST" action="{{ route('tasks.status', $task->id) }}">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                                <input type="hidden" name="action" value="ready">
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                    {{ __('td.markReady') }}
                                </button>
                            </form>
                        @endif
                        @if ($canDeliver)
                            <button type="button" @click="dialog = 'deliver'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18h-5"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                                {{ __('td.markDelivered') }}
                            </button>
                        @endif

                        @if ($canAssign || $canEdit)
                            <div class="h-px bg-border my-3" role="separator"></div>
                        @endif

                        @if ($canAssign)
                            <button type="button" @click="staffId = ''; dialog = 'assign'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                                {{ $task->assigned_staff_id !== null ? __('td.reassignStaff') : __('td.assignStaff') }}
                            </button>
                        @endif
                        @if ($canEdit)
                            <button type="button" @click="dialog = 'edit'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                {{ __('td.editDetails') }}
                            </button>
                        @endif

                        @if ($canBlock || $canUnblock || $canReopen || $canCancel)
                            <div class="h-px bg-border my-3" role="separator"></div>
                        @endif

                        @if ($canBlock)
                            <button type="button" @click="reason = ''; dialog = 'block'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-amber-300 text-amber-800 hover:bg-amber-50 hover:text-amber-900 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>
                                {{ __('td.blockTask') }}
                            </button>
                        @endif
                        @if ($canUnblock)
                            <button type="button" @click="dialog = 'unblock'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                {{ __('td.unblockTask') }}
                            </button>
                        @endif
                        @if ($canReopen)
                            <button type="button" @click="reason = ''; dialog = 'reopen'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>
                                {{ __('td.reopenTask') }}
                            </button>
                        @endif
                        @if ($canCancel)
                            <button type="button" @click="reason = ''; dialog = 'cancel'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-destructive/40 text-destructive hover:bg-destructive/10 hover:text-destructive transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                                {{ __('td.cancelTask') }}
                            </button>
                        @endif

                        @if ($noActions)
                            <p class="text-sm text-muted-foreground">{{ __('td.noActions') }}{{ $terminal ? __('td.noActionsClosed') : '' }}</p>
                        @endif
                    </div>
                </div>
            </section>

            {{-- ---- Comments & issues (left) ---- --}}
            <section class="rounded-xl border bg-card text-card-foreground shadow-sm lg:col-span-2" aria-labelledby="task-comments-title">
                <div class="flex items-center justify-between gap-2 border-b p-6 pb-3">
                    <h2 id="task-comments-title" class="font-semibold text-base">{{ __('td.commentsIssues') }}</h2>
                    <span class="text-xs text-muted-foreground">{{ \App\Support\Trans::fill('td.commentsTotal', ['count' => $task->comments->count()]) }}</span>
                </div>
                <div class="p-6 pt-4 space-y-4">
                    @if ($task->comments->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-2 py-10 px-4 text-center">
                            <div class="rounded-full bg-muted p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </div>
                            <p class="font-medium">{{ __('td.noComments.title') }}</p>
                            <p class="text-sm text-muted-foreground max-w-sm">{{ __('td.noComments.desc') }}</p>
                        </div>
                    @else
                        <div class="max-h-96 -mx-1 px-1 overflow-y-auto [scrollbar-width:thin] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-border [&::-webkit-scrollbar-track]:transparent">
                            <div class="space-y-3">
                                @foreach ($task->comments as $comment)
                                    <div class="rounded-lg border p-3 {{ $comment->is_issue ? 'border-l-4 border-l-red-400 bg-red-50/50' : 'bg-card' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium flex items-center gap-1.5 min-w-0">
                                                @if ($comment->is_issue)
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 text-red-600 shrink-0" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                                @endif
                                                <span class="truncate">{{ $comment->user_name }}</span>
                                            </p>
                                            <span class="text-xs text-muted-foreground shrink-0"
                                                  title="{{ $comment->created_at?->locale($locale)->translatedFormat('d M Y, H:i') }}">{{ $comment->created_at?->locale($locale)->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-sm text-muted-foreground mt-1 whitespace-pre-wrap break-words">{{ $comment->message }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($mayComment)
                        <form method="POST" action="{{ route('tasks.comment', $task->id) }}" class="space-y-2">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                        <textarea name="body" required maxlength="1000" aria-label="{{ __('td.aria.newComment') }}"
                                      placeholder="{{ __('td.commentPlaceholder') }}"
                                      class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                            <div class="flex flex-col sm:flex-row sm:justify-end gap-2">
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-xs font-medium h-8 px-3 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    {{ __('td.addNote') }}
                                </button>
                                <button type="submit" name="isIssue" value="1"
                                        class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-xs font-medium h-8 px-3 border border-destructive/40 text-destructive hover:bg-destructive/10 hover:text-destructive transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                    {{ __('td.reportIssue') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </section>

            {{-- ---- Task details (right) ---- --}}
            <section class="rounded-xl border bg-card text-card-foreground shadow-sm" aria-labelledby="task-details-title">
                <div class="flex items-center justify-between gap-2 border-b p-6 pb-3">
                    <h2 id="task-details-title" class="font-semibold text-base">{{ __('td.taskDetails') }}</h2>
                </div>
                <div class="p-6 pt-4">
                    <div class="divide-y">
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.order') }}</span>
                            <span class="text-sm text-right min-w-0 font-medium">
                                @if ($task->order)
                                    <a href="{{ route('orders.show', $task->order->id) }}" class="hover:underline">{{ $task->order->order_number }}</a>
                                @endif
                                — {{ $task->order?->customer?->name }}
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('tc.customer') }}</span>
                            <span class="text-sm text-right min-w-0">{{ $task->order?->customer?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('tc.phone') }}</span>
                            <span class="text-sm text-right min-w-0">{{ $task->order?->customer?->phone ?? '—' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.piece') }}</span>
                            <span class="text-sm text-right min-w-0 font-medium">{{ $task->garment_piece }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.assignee') }}</span>
                            <span class="text-sm text-right min-w-0">
                                @if ($task->assignedStaff)
                                    {{ $task->assignedStaff->name }}
                                @else
                                    <span class="text-muted-foreground">{{ __('td.unassigned') }}</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.category') }}</span>
                            <span class="text-right min-w-0"><x-badges.category :category="$task->category"/>
</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.priority') }}</span>
                            <span class="text-right min-w-0"><x-badges.priority :priority="$task->priority"/>
</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.deadline') }}</span>
                            <span class="text-sm text-right min-w-0">
                                @if ($task->deadline === null)
                                    <span class="text-muted-foreground text-sm">—</span>
                                @elseif ($taskOverdue)
                                    <span class="inline-flex items-center gap-1 whitespace-nowrap text-red-700 font-semibold">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                        {{ $task->deadline->locale($locale)->translatedFormat('d M Y') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 whitespace-nowrap text-muted-foreground">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 opacity-60" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></svg>
                                        {{ $task->deadline->locale($locale)->translatedFormat('d M Y') }}
                                    </span>
                                @endif
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.created') }}</span>
                            <span class="text-sm text-right min-w-0">{{ $task->created_at?->locale($locale)->translatedFormat('d M Y') }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.creator') }}</span>
                            <span class="text-sm text-right min-w-0">{{ $task->creator?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.completed') }}</span>
                            <span class="text-sm text-right min-w-0">{{ $task->completed_at !== null ? $task->completed_at->locale($locale)->translatedFormat('d M Y') : '—' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 py-2">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide pt-0.5">{{ __('td.meta.delivered') }}</span>
                            <span class="text-sm text-right min-w-0">{{ $task->delivered_at !== null ? $task->delivered_at->locale($locale)->translatedFormat('d M Y') : '—' }}</span>
                        </div>
                    </div>

                    @if ($task->instructions)
                        <div class="mt-3">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('td.instructions') }}</p>
                            <p class="text-sm mt-1 rounded-lg border bg-muted/30 p-3 whitespace-pre-wrap">{{ $task->instructions }}</p>
                        </div>
                    @endif

                    @if ($task->order?->instructions)
                        <div class="mt-3">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.orderInstructions') }}</p>
                            <p class="text-sm mt-1 rounded-lg border bg-muted/30 p-3 whitespace-pre-wrap">{{ $task->order->instructions }}</p>
                        </div>
                    @endif
                </div>
            </section>

            {{-- ---- History (full width) ---- --}}
            <section class="rounded-xl border bg-card text-card-foreground shadow-sm lg:col-span-3" aria-labelledby="task-history-title" x-data="{ tab: 'all' }">
                <div class="flex items-center justify-between gap-2 border-b p-6 pb-3">
                    <h2 id="task-history-title" class="font-semibold text-base">{{ __('td.history') }}</h2>
                    <span class="text-xs text-muted-foreground">{{ \App\Support\Trans::fill('td.eventsCount', ['count' => $historyEntries->count()]) }}</span>
                </div>
                <div class="p-6 pt-4">
                    <div class="flex flex-wrap gap-1 rounded-lg bg-muted/50 p-1" role="tablist" aria-label="{{ __('td.history') }}">
                        <button type="button" role="tab"
                                :aria-selected="tab === 'all'"
                                @click="tab = 'all'"
                                class="h-8 px-3 rounded-md text-sm font-medium transition-colors"
                                :class="tab === 'all' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                            {{ __('common.all') }}
                        </button>
                        <button type="button" role="tab"
                                :aria-selected="tab === 'progress'"
                                @click="tab = 'progress'"
                                class="h-8 px-3 rounded-md text-sm font-medium transition-colors"
                                :class="tab === 'progress' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                            {{ __('td.tab.progress') }}
                        </button>
                        <button type="button" role="tab"
                                :aria-selected="tab === 'checklist'"
                                @click="tab = 'checklist'"
                                class="h-8 px-3 rounded-md text-sm font-medium transition-colors"
                                :class="tab === 'checklist' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                            {{ __('td.tab.checklist') }}
                        </button>
                        <button type="button" role="tab"
                                :aria-selected="tab === 'status'"
                                @click="tab = 'status'"
                                class="h-8 px-3 rounded-md text-sm font-medium transition-colors"
                                :class="tab === 'status' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                            {{ __('td.tab.status') }}
                        </button>
                        <button type="button" role="tab"
                                :aria-selected="tab === 'inspections'"
                                @click="tab = 'inspections'"
                                class="h-8 px-3 rounded-md text-sm font-medium transition-colors"
                                :class="tab === 'inspections' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                            {{ __('td.tab.inspections') }}
                        </button>
                    </div>

                    @if ($historyEntries->isEmpty())
                        <div class="mt-2 flex flex-col items-center justify-center gap-2 py-10 px-4 text-center">
                            <div class="rounded-full bg-muted p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                            </div>
                            <p class="font-medium">{{ __('td.noEvents') }}</p>
                        </div>
                    @else
                        <div class="mt-2 max-h-96 overflow-y-auto [scrollbar-width:thin] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-border [&::-webkit-scrollbar-track]:transparent rounded-lg border">
                            <div class="divide-y px-4 py-1">
                                @foreach ($historyEntries as $historyEntry)
                                    @php
                                        $entry = $historyEntry['entry'];

                                        if ($historyEntry['kind'] === 'assignment') {
                                            $entryShow = "tab === 'all'";
                                            $entryIconClass = 'bg-teal-50 text-teal-700';
                                            $entryIcon = '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>';
                                            $entryUser = $entry->assigned_by_name;
                                            $entryText = \App\Support\Trans::fill(
                                                $entry->type === 'REASSIGN' ? 'td.hist.reassigned' : 'td.hist.assigned',
                                                ['name' => $entry->staff_name]
                                            );
                                            $entryDetail = $entry->note;
                                            $entryBadge = null;
                                        } elseif ($historyEntry['kind'] === 'status') {
                                            $entryShow = "tab === 'all' || tab === 'status'";
                                            $entryIconClass = 'bg-amber-50 text-amber-700';
                                            $entryIcon = '<path d="m16 3 4 4-4 4"/><path d="M20 7H4"/><path d="m8 21-4-4 4-4"/><path d="M4 17h16"/>';
                                            $entryUser = $entry->user_name;
                                            $entryText = \App\Support\Trans::fill('td.hist.statusChange', [
                                                'from' => __('labels.status.'.$entry->from_status),
                                                'to' => __('labels.status.'.$entry->to_status),
                                            ]);
                                            $entryDetail = $entry->reason;
                                            $entryBadge = null;
                                        } elseif ($historyEntry['kind'] === 'stage') {
                                            $entryShow = "tab === 'all' || tab === 'checklist'";
                                            $entryIconClass = 'bg-stone-100 text-stone-600';
                                            $entryIcon = '<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/>';
                                            $entryUser = $entry->user_name;
                                            $entryText = \App\Support\Trans::fill('td.hist.stageChange', [
                                                'stage' => $entry->stage_name,
                                                'from' => __('labels.stageState.'.$entry->previous_state),
                                                'to' => __('labels.stageState.'.$entry->new_state),
                                            ]);
                                            $entryDetail = $entry->comment;
                                            $entryBadge = null;
                                        } elseif ($historyEntry['kind'] === 'progress') {
                                            $entryShow = "tab === 'all' || tab === 'progress'";
                                            $entryIconClass = 'bg-emerald-50 text-emerald-700';
                                            $entryIcon = '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>';
                                            $entryUser = $entry->user_name;
                                            $entryText = \App\Support\Trans::fill('td.hist.progress', [
                                                'from' => $entry->previous_progress,
                                                'to' => $entry->new_progress,
                                            ]);
                                            $entryDetail = $entry->note;
                                            $entryBadge = in_array($entry->source, ['MANUAL', 'STAGE', 'INSPECTION'], true)
                                                ? __('td.source.'.$entry->source)
                                                : (string) $entry->source;
                                        } elseif ($historyEntry['kind'] === 'inspection') {
                                            $entryShow = "tab === 'all' || tab === 'inspections'";
                                            $entryIconClass = $entry->decision === 'APPROVED'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : ($entry->decision === 'CHANGES_REQUESTED' ? 'bg-amber-50 text-amber-700' : 'bg-stone-100 text-stone-600');
                                            $entryIcon = '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1 1 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>';
                                            $entryUser = $entry->inspector_name;
                                            $entryText = \App\Support\Trans::fill('td.hist.inspection', [
                                                'decision' => in_array($entry->decision, ['APPROVED', 'CHANGES_REQUESTED', 'REOPENED'], true)
                                                    ? __('labels.inspection.'.$entry->decision)
                                                    : (string) $entry->decision,
                                            ]);
                                            $entryDetail = $entry->comments;
                                            $entryBadge = null;
                                        } else {
                                            $entryShow = "tab === 'all'";
                                            $entryIconClass = $entry->is_issue ? 'bg-red-50 text-red-700' : 'bg-stone-100 text-stone-600';
                                            $entryIcon = '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>';
                                            $entryUser = $entry->user_name;
                                            $entryText = $entry->is_issue ? __('td.hist.issue') : __('td.hist.note');
                                            $entryDetail = $entry->message;
                                            $entryBadge = null;
                                        }
                                    @endphp
                                    <div class="flex gap-3 py-2.5" x-show="{{ $entryShow }}">
                                        <div class="rounded-full p-1.5 shrink-0 mt-0.5 {{ $entryIconClass }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true">{!! $entryIcon !!}</svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm leading-snug">
                                                <span class="font-medium">{{ $entryUser }}</span>
                                                <span class="text-muted-foreground">{{ $entryText }}</span>
                                                @if ($entryBadge !== null)
                                                    <span class="ml-1.5 inline-flex items-center rounded-full border px-1.5 py-0 text-[10px] font-medium align-middle">{{ $entryBadge }}</span>
                                                @endif
                                            </p>
                                            @if ($entryDetail !== null && $entryDetail !== '')
                                                <p class="text-xs text-muted-foreground mt-0.5 whitespace-pre-wrap break-words">{{ $entryDetail }}</p>
                                            @endif
                                            <p class="text-xs text-muted-foreground/80 mt-0.5"
                                               title="{{ $historyEntry['at']?->locale($locale)->translatedFormat('d M Y, H:i') }}">{{ $historyEntry['at']?->locale($locale)->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        {{-- ---- Assign / Reassign dialog ---- --}}
        <div x-show="dialog === 'assign'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="assign-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.assign', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <div>
                    <h3 id="assign-dialog-title" class="font-semibold text-lg">{{ $task->assigned_staff_id !== null ? __('td.dialog.reassignTitle') : __('td.dialog.assignTitle') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ \App\Support\Trans::fill('td.dialog.assignDesc', ['piece' => $task->garment_piece, 'code' => $task->code]) }}</p>
                </div>
                <div class="space-y-1.5">
                    <label for="assign-staff" class="text-sm font-medium leading-none">{{ __('td.dialog.staffMember') }}</label>
                    <select id="assign-staff" name="staffId" x-model="staffId"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="">{{ __('td.dialog.selectStaff') }}</option>
                        @if ($staffList->isEmpty())
                            <option value="" disabled>{{ __('td.dialog.noStaff') }}</option>
                        @endif
                        @foreach ($staffList as $staffMember)
                            <option value="{{ $staffMember->id }}" {{ $staffMember->id === $task->assigned_staff_id ? 'disabled' : '' }}>
                                {{ $staffMember->name }} ({{ \App\Support\Trans::fill('td.dialog.activeTasks', ['count' => $activeCounts->get($staffMember->id, 0)]) }}){{ $staffMember->id === $task->assigned_staff_id ? __('td.dialog.currentAssignee') : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label for="assign-note" class="text-sm font-medium leading-none">{{ __('td.dialog.noteOptional') }}</label>
                    <input type="text" id="assign-note" name="note" maxlength="1000"
                           placeholder="{{ __('td.dialog.assignNotePlaceholder') }}"
                           class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" :disabled="staffId === ''"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:pointer-events-none disabled:opacity-50">
                        {{ $task->assigned_staff_id !== null ? __('td.dialog.reassignAction') : __('td.dialog.assignAction') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ---- Edit details dialog ---- --}}
        <div x-show="dialog === 'edit'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="edit-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.update', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <div>
                    <h3 id="edit-dialog-title" class="font-semibold text-lg">{{ __('td.dialog.editTitle') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ __('td.dialog.editDesc') }}</p>
                </div>
                <div class="space-y-1.5">
                    <label for="edit-piece" class="text-sm font-medium leading-none">{{ __('td.meta.piece') }}</label>
                    <input type="text" id="edit-piece" name="garmentPiece" maxlength="100" required
                           value="{{ $task->garment_piece }}"
                           class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="edit-deadline" class="text-sm font-medium leading-none">{{ __('td.meta.deadline') }}</label>
                        <input type="datetime-local" id="edit-deadline" name="deadline"
                               value="{{ $task->deadline?->format('Y-m-d\TH:i') }}"
                               class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    </div>
                    <div class="space-y-1.5">
                        <label for="edit-priority" class="text-sm font-medium leading-none">{{ __('td.meta.priority') }}</label>
                        <select id="edit-priority" name="priority"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            @foreach (['LOW', 'NORMAL', 'HIGH', 'URGENT'] as $priorityOption)
                                <option value="{{ $priorityOption }}" {{ (string) $task->priority === $priorityOption ? 'selected' : '' }}>{{ __('labels.priority.'.$priorityOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label for="edit-instructions" class="text-sm font-medium leading-none">{{ __('td.instructions') }}</label>
                    <textarea id="edit-instructions" name="instructions" maxlength="2000"
                              placeholder="{{ __('td.dialog.instructionsPlaceholder') }}"
                              class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{{ $task->instructions ?? '' }}</textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        {{ __('common.saveChanges') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ---- Submit for inspection dialog ---- --}}
        <div x-show="dialog === 'submit'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="submit-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.status', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <input type="hidden" name="action" value="submit">
                <div>
                    <h3 id="submit-dialog-title" class="font-semibold text-lg">{{ __('td.submitForInspection') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ $isSupervisor ? __('td.dialog.submitDescSupervisor') : __('td.dialog.submitDescStaff') }}</p>
                </div>
                <div class="space-y-1.5">
                    <label for="submit-note" class="text-sm font-medium leading-none">{{ __('td.dialog.noteOptional') }}</label>
                    <textarea id="submit-note" name="note" maxlength="1000"
                              placeholder="{{ __('td.dialog.submitNotePlaceholder') }}"
                              class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        {{ __('td.dialog.submitAction') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ---- Quality inspection dialog ---- --}}
        <div x-show="dialog === 'inspect'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="inspect-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.inspect', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <div>
                    <h3 id="inspect-dialog-title" class="font-semibold text-lg">{{ __('td.dialog.inspectTitle') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ __('td.dialog.inspectDesc') }}</p>
                </div>
                <div class="space-y-2" role="radiogroup" aria-label="{{ __('td.dialog.inspectTitle') }}">
                    @foreach (['APPROVED', 'CHANGES_REQUESTED', 'REOPENED'] as $decisionIndex => $decisionOption)
                        <label class="flex items-center gap-2.5 rounded-lg border p-3 cursor-pointer hover:bg-muted/50 transition-colors has-[:checked]:border-primary has-[:checked]:bg-accent/50">
                            <input type="radio" name="decision" value="{{ $decisionOption }}" {{ $decisionIndex === 0 ? 'checked' : '' }} class="size-4 accent-primary">
                            <span class="text-sm font-medium">{{ __('labels.inspection.'.$decisionOption) }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="space-y-1.5">
                    <label for="inspect-comments" class="text-sm font-medium leading-none">{{ __('td.dialog.inspectionComments') }}</label>
                    <textarea id="inspect-comments" name="comments" maxlength="2000"
                              placeholder="{{ __('td.dialog.inspectPlaceholder') }}"
                              class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1 1 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                        {{ __('td.dialog.submitAction') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ---- Mark delivered dialog ---- --}}
        <div x-show="dialog === 'deliver'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="deliver-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.status', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <input type="hidden" name="action" value="deliver">
                <div>
                    <h3 id="deliver-dialog-title" class="font-semibold text-lg">{{ __('td.markDelivered') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ \App\Support\Trans::fill('td.dialog.deliverDesc', ['customer' => $task->order?->customer?->name, 'piece' => $task->garment_piece]) }}</p>
                </div>
                <div class="space-y-1.5">
                    <label for="deliver-note" class="text-sm font-medium leading-none">{{ __('td.dialog.noteOptional') }}</label>
                    <input type="text" id="deliver-note" name="note" maxlength="1000"
                           placeholder="{{ __('td.dialog.deliverNotePlaceholder') }}"
                           class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18h-5"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                        {{ __('td.dialog.markDeliveredAction') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ---- Reopen dialog ---- --}}
        <div x-show="dialog === 'reopen'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="reopen-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.status', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <input type="hidden" name="action" value="reopen">
                <div>
                    <h3 id="reopen-dialog-title" class="font-semibold text-lg">{{ __('td.reopenTask') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ __('td.dialog.reopenDesc') }}</p>
                </div>
                <div class="space-y-1.5">
                    <label for="reopen-comments" class="text-sm font-medium leading-none">{{ __('td.dialog.comments') }}</label>
                    <textarea id="reopen-comments" name="reason" x-model="reason" maxlength="1000" required
                              placeholder="{{ __('td.dialog.reopenPlaceholder') }}"
                              class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                            </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" :disabled="reason.trim().length < 3"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:pointer-events-none disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>
                        {{ __('td.dialog.reopenAction') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ---- Block dialog ---- --}}
        <div x-show="dialog === 'block'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="block-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.block', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <div>
                    <h3 id="block-dialog-title" class="font-semibold text-lg">{{ __('td.blockTask') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ __('td.dialog.blockDesc') }}</p>
                </div>
                <div class="space-y-1.5">
                    <label for="block-reason" class="text-sm font-medium leading-none">{{ __('td.dialog.reasonRequired') }}</label>
                    <textarea id="block-reason" name="reason" x-model="reason" maxlength="1000" required
                              placeholder="{{ __('td.dialog.blockPlaceholder') }}"
                              class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                            </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" :disabled="reason.trim().length < 3"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-red-600 text-white shadow-sm hover:bg-red-700 transition-colors disabled:pointer-events-none disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>
                        {{ __('td.blockTask') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ---- Unblock dialog ---- --}}
        <div x-show="dialog === 'unblock'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="unblock-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.unblock', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <div>
                    <h3 id="unblock-dialog-title" class="font-semibold text-lg">{{ __('td.unblockTask') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ __('td.dialog.unblockDesc') }}</p>
                </div>
                <div class="space-y-1.5">
                    <label for="unblock-note" class="text-sm font-medium leading-none">{{ __('td.dialog.noteOptional') }}</label>
                    <input type="text" id="unblock-note" name="note" maxlength="1000"
                           placeholder="{{ __('td.dialog.unblockPlaceholder') }}"
                           class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        {{ __('td.dialog.unblockAction') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ---- Cancel dialog ---- --}}
        <div x-show="dialog === 'cancel'" x-cloak
             class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="cancel-dialog-title"
             @click.self="dialog = null">
            <form method="POST" action="{{ route('tasks.status', $task->id) }}"
                  class="w-full max-w-lg rounded-xl border bg-card text-card-foreground shadow-lg p-6 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">            <input type="hidden" name="action" value="cancel">
                <div>
                    <h3 id="cancel-dialog-title" class="font-semibold text-lg">{{ __('td.dialog.cancelTitle') }}</h3>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ \App\Support\Trans::fill('td.dialog.cancelDesc', ['code' => $task->code, 'piece' => $task->garment_piece, 'order' => $task->order?->order_number]) }}</p>
                </div>
                <div class="space-y-1.5">
                    <label for="cancel-reason" class="text-sm font-medium leading-none">{{ __('td.dialog.reasonRequired') }}</label>
                    <textarea id="cancel-reason" name="reason" x-model="reason" maxlength="1000" required
                              placeholder="{{ __('td.dialog.cancelPlaceholder') }}"
                              class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                            </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="dialog = null"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        {{ __('td.dialog.keepTask') }}
                    </button>
                    <button type="submit" :disabled="reason.trim().length < 3"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-destructive text-white shadow-sm hover:bg-destructive/90 transition-colors disabled:pointer-events-none disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                        {{ __('td.cancelTask') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
