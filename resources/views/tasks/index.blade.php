@extends('layouts.app')

@section('title', __('shell.view.tasks'))

@section('content')
    @php
        $locale = app()->getLocale();
        $terminal = \App\Models\ProductionTask::TERMINAL_STATUSES;

        // Active (non-terminal) task count per assignable staff member for
        // the "(N active)" hints in the staff filter.
        $activeCounts = \App\Models\ProductionTask::query()
            ->whereNotIn('status', $terminal)
            ->whereNotNull('assigned_staff_id')
            ->groupBy('assigned_staff_id')
            ->selectRaw('assigned_staff_id, count(*) as aggregate')
            ->pluck('aggregate', 'assigned_staff_id');

        $hasFilters = filled($filters['q'] ?? null)
            || filled($filters['staff'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['priority'] ?? null)
            || ! empty($filters['overdue'])
            || filled($filters['deadlineFrom'] ?? null)
            || filled($filters['deadlineTo'] ?? null);

        if ($isStaff) {
            $emptyTitle = __('tasks.emptyStaffTitle');
            $emptyDescription = __('tasks.emptyStaffDesc');
        } elseif ($hasFilters) {
            $emptyTitle = __('tasks.emptyFilteredTitle');
            $emptyDescription = __('tasks.emptyFilteredDesc');
        } else {
            $emptyTitle = __('tasks.emptyTitle');
            $emptyDescription = __('tasks.emptyDesc');
        }
    @endphp

    <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-bold tracking-tight">
                    {{ $isStaff ? __('tasks.myTitle') : __('tasks.title') }}
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">
                    {{ $isStaff ? __('tasks.myDescription') : __('tasks.description') }}
                </p>
            </div>
            @if (! $isStaff)
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="{{ route('tasks.create') }}"
                       class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-3 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        {{ __('tasks.createTasks') }}
                    </a>
                </div>
            @endif
        </div>

        <div class="rounded-xl border bg-card text-card-foreground shadow-sm mb-4">
            <div class="p-4">
                <form method="GET" action="{{ route('tasks.index') }}" class="grid gap-3 md:grid-cols-2 lg:grid-cols-4" role="search">
                    <div class="space-y-1.5 lg:col-span-2">
                        <label for="tasks-search" class="text-sm font-medium leading-none">{{ __('common.search') }}</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" id="tasks-search" name="q" value="{{ old('q', $filters['q'] ?? '') }}"
                                   placeholder="{{ __('tasks.searchPlaceholder') }}"
                                   class="flex h-9 w-full rounded-md border border-input bg-background pl-8 pr-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="tasks-status" class="text-sm font-medium leading-none">{{ __('tasks.status') }}</label>
                        <select id="tasks-status" name="status" aria-label="{{ __('tasks.filterByStatus') }}"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <option value="">{{ __('tasks.allStatuses') }}</option>
                            @foreach (['UNASSIGNED', 'ASSIGNED', 'ONGOING', 'AWAITING_INSPECTION', 'CHANGES_REQUESTED', 'APPROVED', 'READY_FOR_PICKUP', 'DELIVERED', 'BLOCKED', 'CANCELLED'] as $statusOption)
                                <option value="{{ $statusOption }}" {{ ($filters['status'] ?? null) === $statusOption ? 'selected' : '' }}>{{ __('labels.status.'.$statusOption) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="tasks-sort" class="text-sm font-medium leading-none">{{ __('tasks.sortBy') }}</label>
                        <select id="tasks-sort" name="sort" aria-label="{{ __('tasks.sortAria') }}"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            @foreach (['updated' => __('tasks.sortUpdated'), 'deadline' => __('tasks.sortDeadline'), 'progress' => __('tasks.sortProgress'), 'created' => __('tasks.sortCreated')] as $sortValue => $sortLabel)
                                <option value="{{ $sortValue }}" {{ ($filters['sort'] ?? 'updated') === $sortValue ? 'selected' : '' }}>{{ $sortLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (! $isStaff)
                        <div class="space-y-1.5">
                            <label for="tasks-staff" class="text-sm font-medium leading-none">{{ __('tasks.staff') }}</label>
                            <select id="tasks-staff" name="staff" aria-label="{{ __('tasks.filterByStaff') }}"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <option value="">{{ __('tasks.allStaff') }}</option>
                                <option value="unassigned" {{ ($filters['staff'] ?? null) === 'unassigned' ? 'selected' : '' }}>{{ __('tasks.unassignedFilter') }}</option>
                                @foreach ($staffList as $staffMember)
                                    <option value="{{ $staffMember->id }}" {{ ($filters['staff'] ?? null) === $staffMember->id ? 'selected' : '' }}>
                                        {{ $staffMember->name }} ({{ \App\Support\Trans::fill('tasks.activeCount', ['count' => $activeCounts->get($staffMember->id, 0)]) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label for="tasks-priority" class="text-sm font-medium leading-none">{{ __('tasks.priority') }}</label>
                            <select id="tasks-priority" name="priority" aria-label="{{ __('tasks.filterByPriority') }}"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <option value="">{{ __('tasks.allPriorities') }}</option>
                                @foreach (['LOW', 'NORMAL', 'HIGH', 'URGENT'] as $priorityOption)
                                    <option value="{{ $priorityOption }}" {{ ($filters['priority'] ?? null) === $priorityOption ? 'selected' : '' }}>{{ __('labels.priority.'.$priorityOption) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label for="tasks-from" class="text-sm font-medium leading-none">{{ __('tasks.deadlineFrom') }}</label>
                            <input type="date" id="tasks-from" name="deadlineFrom" value="{{ old('deadlineFrom', $filters['deadlineFrom'] ?? '') }}"
                                   class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        </div>

                        <div class="space-y-1.5">
                            <label for="tasks-to" class="text-sm font-medium leading-none">{{ __('tasks.deadlineTo') }}</label>
                            <input type="date" id="tasks-to" name="deadlineTo" value="{{ old('deadlineTo', $filters['deadlineTo'] ?? '') }}"
                                   class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3 pt-1 lg:pt-6">
                        <label for="tasks-overdue" class="inline-flex items-center gap-2.5 text-sm font-medium cursor-pointer">
                            <input type="checkbox" id="tasks-overdue" name="overdue" value="1"
                                   {{ ! empty($filters['overdue']) ? 'checked' : '' }}
                                   class="size-4 rounded border-input accent-primary">
                            {{ __('tasks.overdueOnly') }}
                        </label>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-9 px-4 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                            {{ __('common.filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[900px]">
                    <caption class="sr-only">{{ $isStaff ? __('tasks.myTitle') : __('tasks.title') }}</caption>
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th scope="col" class="h-10 px-4 text-start font-medium text-muted-foreground w-[220px]">{{ __('tasks.colTask') }}</th>
                            <th scope="col" class="h-10 px-4 text-start font-medium text-muted-foreground w-[200px]">{{ __('tasks.colOrder') }}</th>
                            <th scope="col" class="h-10 px-4 text-start font-medium text-muted-foreground w-[150px]">{{ __('tasks.colAssignee') }}</th>
                            <th scope="col" class="h-10 px-4 text-start font-medium text-muted-foreground w-[160px]">{{ __('tasks.colStatus') }}</th>
                            @if (! $isStaff)
                                <th scope="col" class="h-10 px-4 text-start font-medium text-muted-foreground w-[120px]">{{ __('tasks.priority') }}</th>
                            @endif
                            <th scope="col" class="h-10 px-4 text-start font-medium text-muted-foreground w-[170px]">{{ __('tasks.colProgress') }}</th>
                            <th scope="col" class="h-10 px-4 text-start font-medium text-muted-foreground w-[140px]">{{ __('tasks.colDeadline') }}</th>
                            <th scope="col" class="h-10 px-4 text-right font-medium text-muted-foreground">{{ __('tasks.colLastUpdate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tasks as $task)
                            @php
                                $taskUrl = route('tasks.show', $task->id);
                                $taskDone = $task->progress >= 100 || in_array($task->status, ['APPROVED', 'DELIVERED'], true);
                            @endphp
                            <tr tabindex="0" role="link"
                                aria-label="{{ \App\Support\Trans::fill('tasks.openTaskAria', ['code' => $task->code, 'piece' => $task->garment_piece]) }}"
                                class="cursor-pointer outline-none border-b transition-colors hover:bg-muted/50 focus-visible:bg-muted/60 last:border-b-0"
                                onclick="window.location.href='{{ $taskUrl }}'"
                                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='{{ $taskUrl }}'}">
                                <td class="px-4 py-3 align-middle">
                                    <a href="{{ $taskUrl }}" class="block focus-visible:outline-none">
                                        <p class="font-medium text-sm leading-tight">{{ $task->garment_piece }}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5">{{ $task->code }}</p>
                                    </a>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <p class="text-sm leading-tight">{{ $task->order?->order_number }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5 truncate max-w-44">{{ $task->order?->customer?->name }}</p>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    @if ($task->assignedStaff)
                                        <span class="text-sm">{{ $task->assignedStaff->name }}</span>
                                    @else
                                        <span class="text-sm text-muted-foreground italic">{{ __('tasks.unassigned') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <x-badges.status :status="$task->status"/>
                                </td>
                                @if (! $isStaff)
                                    <td class="px-4 py-3 align-middle">
                                        <x-badges.priority :priority="$task->priority"/>
                                    </td>
                                @endif
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-2 min-w-28">
                                        <div class="h-1.5 w-full rounded bg-muted overflow-hidden">
                                            <div class="h-full rounded transition-all {{ $taskDone ? 'bg-emerald-600' : 'bg-primary' }}"
                                                 style="width: {{ $task->progress }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold tabular-nums w-9 text-right{{ $taskDone ? ' text-emerald-700' : '' }}">{{ $task->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    @if ($task->deadline === null)
                                        <span class="text-muted-foreground text-sm">—</span>
                                    @elseif ($task->isOverdue())
                                        <span class="inline-flex items-center gap-1 text-sm whitespace-nowrap text-red-700 font-semibold">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                            {{ $task->deadline->format('M j, Y') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-sm whitespace-nowrap text-muted-foreground">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 opacity-60" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></svg>
                                            {{ $task->deadline->format('M j, Y') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle text-right text-xs text-muted-foreground whitespace-nowrap" title="{{ $task->updated_at?->locale($locale)->translatedFormat('d M Y, H:i') }}">
                                    {{ $task->updated_at?->locale($locale)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isStaff ? 7 : 8 }}">
                                    <div class="flex flex-col items-center justify-center gap-2 py-10 px-4 text-center">
                                        <div class="rounded-full bg-muted p-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0l-4.38 1.46A2 2 0 0 0 2.5 5.77l2.76 14.42A2 2 0 0 0 7.22 22h9.56a2 2 0 0 0 1.96-1.8l2.76-14.43a2 2 0 0 0-1.12-2.31"/><path d="M9 6v3"/><path d="M15 6v3"/><path d="M9 12h6"/></svg>
                                        </div>
                                        <p class="font-medium">{{ $emptyTitle }}</p>
                                        <p class="text-sm text-muted-foreground max-w-sm">{{ $emptyDescription }}</p>
                                        @if (! $isStaff && $hasFilters)
                                            <div class="mt-2">
                                                <a href="{{ route('tasks.index') }}"
                                                   class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-8 px-3 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                                                    {{ __('tasks.clearFilters') }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 border-t px-4 py-3">
                <p class="text-xs text-muted-foreground">
                    {{ \App\Support\Trans::fill($tasks->total() === 1 ? 'tasks.totalOne' : 'tasks.totalMany', ['count' => $tasks->total()]) }}
                </p>
                <div class="flex items-center gap-2">
                    @if ($tasks->onFirstPage())
                        <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-background text-xs font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            {{ __('tasks.previous') }}
                        </span>
                    @else
                        <a href="{{ $tasks->appends(request()->query())->previousPageUrl() }}"
                           class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-background text-xs font-medium hover:bg-accent hover:text-accent-foreground transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            {{ __('tasks.previous') }}
                        </a>
                    @endif

                    <span class="text-xs text-muted-foreground tabular-nums whitespace-nowrap px-1">
                        {{ \App\Support\Trans::fill('tasks.pageOf', ['page' => $tasks->currentPage(), 'totalPages' => $tasks->lastPage()]) }}
                    </span>

                    @if ($tasks->hasMorePages())
                        <a href="{{ $tasks->appends(request()->query())->nextPageUrl() }}"
                           class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-background text-xs font-medium hover:bg-accent hover:text-accent-foreground transition-colors">
                            {{ __('tasks.next') }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-background text-xs font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                            {{ __('tasks.next') }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
