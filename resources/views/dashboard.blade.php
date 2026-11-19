@extends('layouts.app')

@section('title', __('shell.view.dashboard'))

@section('content')
    @php
        /*
         | Central dashboard — single view for both role families (view
         | contract #2): STAFF renders the personal variant, MANAGEMENT /
         | ADMIN the production-overview variant. All markup below is a 1:1
         | port of the captured live output (dashboard-en / dashboard-fr /
         | staff-dashboard-en): DOM structure, Tailwind classes, filter
         | links, badge components and date formats are byte-faithful.
         */

        $locale = app()->getLocale();

        // Inline SVG atoms (lucide paths, verbatim from the live captures).
        // $icon() wraps the inner paths with this app's standard svg shell.
        $icon = static function (string $paths, string $class): string {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"'
                .' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
                .' class="'.$class.'" aria-hidden="true">'.$paths.'</svg>';
        };

        $iconPaths = [
            // Package — Registered Orders stat.
            'package' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
            // Shirt — Active Tasks stat, Create Tasks button, task empty state.
            'shirt' => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
            // HelpCircle — Unassigned stat.
            'helpCircle' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
            // Loader — Ongoing stat.
            'loader' => '<line x1="12" x2="12" y1="2" y2="6"/><line x1="12" x2="12" y1="18" y2="22"/><line x1="4.93" x2="7.76" y1="4.93" y2="7.76"/><line x1="16.24" x2="19.07" y1="16.24" y2="19.07"/><line x1="2" x2="6" y1="12" y2="12"/><line x1="18" x2="22" y1="12" y2="12"/><line x1="4.93" x2="7.76" y1="19.07" y2="16.24"/><line x1="16.24" x2="19.07" y1="7.76" y2="4.93"/>',
            // ClipboardCheck — Awaiting Inspection stat.
            'clipboardCheck' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
            // ShoppingBag — Ready for Pickup stat.
            'shoppingBag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
            // Truck — Delivered stat.
            'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
            // Ban — Blocked stat.
            'ban' => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
            // AlertTriangle — Overdue stat / overdue due-dates.
            'alertTriangle' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
            // ListTodo — My Active Tasks stat.
            'listTodo' => '<rect x="3" y="5" width="6" height="6" rx="1"/><path d="m3 17 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/>',
            // BadgeCheck — Completed This Month stat / overdue empty state.
            'badgeCheck' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
            // CalendarClock — upcoming due-dates / due-soon empty state.
            'calendarClock' => '<path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/>',
            // Users — workload empty state.
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            // History — recent activity empty state.
            'history' => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
        ];
    @endphp

    <div>
        @if ($me->role === 'STAFF')
            {{-- ============================== STAFF ============================== --}}
            @php
                // Personal stats + the latest six assigned tasks. Cards with a
                // href render the stretched-link overlay; "Completed This
                // Month" is informational only (no filter target).
                $firstName = explode(' ', trim($me->name))[0];

                $staffCards = [
                    ['label' => __('dash.stat.myActive'), 'value' => $staffStats['myActive'], 'tone' => 'text-teal-700', 'href' => route('tasks.index'), 'icon' => 'listTodo'],
                    ['label' => __('dash.stat.awaiting'), 'value' => $staffStats['myAwaiting'], 'tone' => 'text-amber-700', 'href' => route('tasks.index'), 'icon' => 'clipboardCheck'],
                    ['label' => __('dash.stat.completedThisMonth'), 'value' => $staffStats['myCompletedThisMonth'], 'tone' => 'text-emerald-700', 'href' => null, 'icon' => 'badgeCheck'],
                    ['label' => __('dash.stat.overdue'), 'value' => $staffStats['myOverdue'], 'tone' => $staffStats['myOverdue'] > 0 ? 'text-red-700' : 'text-foreground', 'href' => route('tasks.index'), 'icon' => 'alertTriangle'],
                ];
            @endphp

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h1 class="font-display text-2xl font-bold tracking-tight">{{ \App\Support\Trans::fill('dash.welcome', ['name' => $firstName]) }}</h1>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ __('dash.staffDesc') }}</p>
                </div>
            </div>

            <div class="flex gap-2 overflow-x-auto scroll-thin pb-1 lg:hidden mb-4">
                <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 rounded-full border bg-card px-4 py-2 text-sm font-medium btn-press whitespace-nowrap">{!! $icon($iconPaths['listTodo'], 'size-4 text-primary') !!}{{ __('shell.nav.myTasks') }}</a>
                <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-1.5 rounded-full border bg-card px-4 py-2 text-sm font-medium btn-press whitespace-nowrap">{!! $icon($iconPaths['badgeCheck'], 'size-4 text-primary') !!}{{ __('shell.nav.notifications') }}</a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6 stagger">
                @foreach ($staffCards as $card)
                    <div class="relative rounded-lg border bg-card p-4 shadow-sm card-lift {{ $card['href'] ? ' cursor-pointer hover:shadow-md hover:border-primary/40 transition-all ' : '' }}">
                        @if ($card['href'])
                            <a href="{{ $card['href'] }}" class="absolute inset-0 rounded-lg" aria-label="{{ $card['label'] }}"><span class="sr-only">{{ $card['label'] }}</span></a>
                        @endif
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide truncate">{{ $card['label'] }}</p>
                                <p class="text-2xl font-bold mt-1 tabular-nums {{ $card['tone'] }}">{{ $card['value'] }}</p>
                            </div>
                            <div class="shrink-0 rounded-lg bg-primary/10 p-2">{!! $icon($iconPaths[$card['icon']], 'size-4 text-primary') !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <section class="rounded-lg border bg-card shadow-sm">
                <div class="flex items-center justify-between gap-2 p-4 sm:p-6 pb-3">
                    <h2 class="font-semibold text-base">{{ __('dash.myTasks') }}</h2>
                    <a href="{{ route('tasks.index') }}" class="inline-flex items-center h-7 px-2.5 rounded-md text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">{{ __('dash.viewAll') }}</a>
                </div>
                <div class="p-4 sm:p-6 pt-0">
                    @if ($myTasks->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                            <div class="rounded-full bg-muted p-3">{!! $icon($iconPaths['shirt'], 'size-6 text-muted-foreground') !!}</div>
                            <p class="font-medium">{{ __('dash.noTasksTitle') }}</p>
                            <p class="text-sm text-muted-foreground max-w-sm">{{ __('dash.noTasksDesc') }}</p>
                        </div>
                    @else
                        <div class="divide-y rounded-lg border overflow-x-auto">
                            @foreach ($myTasks as $task)
                                @php
                                    $taskDone = $task->progress >= 100 || in_array($task->status, ['APPROVED', 'DELIVERED'], true);
                                    $barWidth = max(0, min(100, $task->progress));
                                @endphp
                                <a href="{{ route('tasks.show', $task->id) }}" class="card-lift w-full grid grid-cols-[1fr_auto] sm:grid-cols-[2fr_2fr_1fr_130px_150px] gap-x-4 items-center px-4 py-3 text-left hover:bg-muted/50 transition-colors min-w-0 sm:min-w-[640px]">
                                    <div class="min-w-0">
                                        <p class="font-medium text-sm truncate">
                                            {{ $task->garment_piece }} <span class="text-muted-foreground">· {{ $task->code }}</span>
                                        </p>
                                        <p class="text-xs text-muted-foreground truncate">
                                            {{ $task->order?->order_number }} — {{ $task->order?->customer?->name }}
                                        </p>
                                    </div>
                                    <div class="hidden sm:block min-w-0">
                                        <x-badges.status :status="$task->status"/>
                                    </div>
                                    <div class="hidden sm:block">
                                        @if ($task->deadline === null)
                                            <span class="text-muted-foreground text-sm">—</span>
                                        @elseif ($task->isOverdue())
                                            <span class="inline-flex items-center gap-1 text-sm whitespace-nowrap text-red-700 font-semibold">
                                                {!! $icon($iconPaths['alertTriangle'], 'size-3.5') !!}
                                                {{ $task->deadline->format('M j, Y') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-sm whitespace-nowrap text-muted-foreground">
                                                {!! $icon($iconPaths['calendarClock'], 'size-3.5 opacity-60') !!}
                                                {{ $task->deadline->format('M j, Y') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 min-w-28">
                                        <div class="h-2 flex-1 rounded-full bg-muted overflow-hidden">
                                            <div class="h-full rounded-full bar-anim {{ $taskDone ? 'bg-emerald-600' : 'bg-primary' }}" style="width: {{ $barWidth }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold tabular-nums w-9 text-right {{ $taskDone ? 'text-emerald-700' : '' }}">{{ $task->progress }}%</span>
                                    </div>
                                    <span class="hidden sm:block text-xs text-muted-foreground text-right">{{ $task->updated_at?->locale($locale)->diffForHumans() }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @else
            {{-- ======================== MANAGEMENT / ADMIN ======================== --}}
            @php
                // Production statistics (tone = captured per-card colouring;
                // "Unassigned" and "Blocked" only highlight when non-zero).
                $managerCards = [
                    ['label' => __('dash.stat.totalOrders'), 'value' => $stats['registeredOrders'], 'tone' => 'text-foreground', 'href' => route('orders.index'), 'icon' => 'package'],
                    ['label' => __('dash.stat.activeTasks'), 'value' => $stats['activeTasks'], 'tone' => 'text-teal-700', 'href' => route('tasks.index'), 'icon' => 'shirt'],
                    ['label' => __('dash.stat.unassigned'), 'value' => $stats['unassigned'], 'tone' => $stats['unassigned'] > 0 ? 'text-amber-700' : 'text-foreground', 'href' => route('tasks.index'), 'icon' => 'helpCircle'],
                    ['label' => __('dash.stat.ongoing'), 'value' => $stats['ongoing'], 'tone' => 'text-foreground', 'href' => route('tasks.index'), 'icon' => 'loader'],
                    ['label' => __('dash.stat.awaiting'), 'value' => $stats['awaitingInspection'], 'tone' => 'text-amber-700', 'href' => route('tasks.index'), 'icon' => 'clipboardCheck'],
                    ['label' => __('dash.stat.readyForPickup'), 'value' => $stats['readyForPickup'], 'tone' => 'text-emerald-700', 'href' => route('tasks.index'), 'icon' => 'shoppingBag'],
                    ['label' => __('dash.stat.delivered'), 'value' => $stats['delivered'], 'tone' => 'text-foreground', 'href' => null, 'icon' => 'truck'],
                    ['label' => __('dash.stat.blocked'), 'value' => $stats['blocked'], 'tone' => $stats['blocked'] > 0 ? 'text-red-700' : 'text-foreground', 'href' => route('tasks.index'), 'icon' => 'ban'],
                ];
            @endphp

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h1 class="font-display text-2xl font-bold tracking-tight">{{ \App\Support\Trans::fill('dash.managerTitle', ['role' => __('labels.role.'.$me->role)]) }}</h1>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ __('dash.managerDesc') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-md bg-primary text-primary-foreground text-sm font-medium shadow-sm hover:bg-primary/90 transition-colors">
                        {!! $icon($iconPaths['shirt'], 'size-4 text-primary') !!}
                        {{ __('dash.createTasks') }}
                    </a>
                </div>
            </div>

            {{-- Mobile quick actions (horizontal chip rail) --}}
            <div class="flex gap-2 overflow-x-auto scroll-thin pb-1 lg:hidden mb-4">
                <a href="{{ route('orders.create') }}" class="inline-flex items-center gap-1.5 rounded-full border bg-card px-4 py-2 text-sm font-medium btn-press whitespace-nowrap">{!! $icon($iconPaths['package'], 'size-4 text-primary') !!}{{ __('orders.newOrder') }}</a>
                <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 rounded-full border bg-card px-4 py-2 text-sm font-medium btn-press whitespace-nowrap">{!! $icon($iconPaths['shirt'], 'size-4 text-primary') !!}{{ __('shell.nav.tasks') }}</a>
                <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-1.5 rounded-full border bg-card px-4 py-2 text-sm font-medium btn-press whitespace-nowrap">{!! $icon($iconPaths['users'], 'size-4 text-primary') !!}{{ __('shell.nav.customers') }}</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 mb-6 stagger">
                @foreach ($managerCards as $card)
                    <div class="relative rounded-lg border bg-card p-4 shadow-sm card-lift {{ $card['href'] ? ' cursor-pointer hover:shadow-md hover:border-primary/40 transition-all ' : '' }}">
                        @if ($card['href'])
                            <a href="{{ $card['href'] }}" class="absolute inset-0 rounded-lg" aria-label="{{ $card['label'] }}"><span class="sr-only">{{ $card['label'] }}</span></a>
                        @endif
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide truncate">{{ $card['label'] }}</p>
                                <p class="text-2xl font-bold mt-1 tabular-nums {{ $card['tone'] }}">{{ $card['value'] }}</p>
                            </div>
                            <div class="shrink-0 rounded-lg bg-primary/10 p-2">{!! $icon($iconPaths[$card['icon']], 'size-4 text-primary') !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid lg:grid-cols-2 gap-4 mb-4">
                {{-- Overdue tasks (deadline passed, non-terminal) --}}
                <section class="rounded-lg border bg-card shadow-sm">
                    <div class="flex items-center justify-between gap-2 p-4 sm:p-6 pb-3">
                        <h2 class="font-semibold text-base">{{ __('dash.overdueTitle') }}</h2>
                        <a href="{{ route('tasks.index') }}" class="inline-flex items-center h-7 px-2.5 rounded-md text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">{{ __('dash.productionBoard') }}</a>
                    </div>
                    <div class="p-4 sm:p-6 pt-0">
                        @if ($overdue->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                                <div class="rounded-full bg-muted p-3">{!! $icon($iconPaths['badgeCheck'], 'size-6 text-muted-foreground') !!}</div>
                                <p class="font-medium">{{ __('dash.overdueEmptyTitle') }}</p>
                                <p class="text-sm text-muted-foreground max-w-sm">{{ __('dash.overdueEmptyDesc') }}</p>
                            </div>
                        @else
                            <div class="divide-y rounded-lg border">
                                @foreach ($overdue as $task)
                                    @php
                                        $taskDone = $task->progress >= 100 || in_array($task->status, ['APPROVED', 'DELIVERED'], true);
                                        $barWidth = max(0, min(100, $task->progress));
                                    @endphp
                                    <a href="{{ route('tasks.show', $task->id) }}" class="card-lift w-full flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 px-4 py-3 sm:py-2.5 text-left hover:bg-muted/50 transition-colors">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium truncate">{{ $task->code }} — {{ $task->garment_piece }}</p>
                                            <p class="text-xs text-muted-foreground truncate">
                                                {{ $task->order?->order_number }} · {{ $task->order?->customer?->name }} · {{ $task->assignedStaff?->name ?? __('dash.unassigned') }}
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                                            <div class="flex items-center gap-2 min-w-28 w-28">
                                                <div class="h-2 flex-1 rounded-full bg-muted overflow-hidden">
                                                    <div class="h-full rounded-full bar-anim {{ $taskDone ? 'bg-emerald-600' : 'bg-primary' }}" style="width: {{ $barWidth }}%"></div>
                                                </div>
                                                <span class="text-xs font-semibold tabular-nums w-9 text-right {{ $taskDone ? 'text-emerald-700' : '' }}">{{ $task->progress }}%</span>
                                            </div>
                                            <span class="inline-flex items-center gap-1 text-sm whitespace-nowrap text-red-700 font-semibold">
                                                {!! $icon($iconPaths['alertTriangle'], 'size-3.5') !!}
                                                {{ $task->deadline->format('M j, Y') }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Deadlines within the next 7 days --}}
                <section class="rounded-lg border bg-card shadow-sm">
                    <div class="flex items-center justify-between gap-2 p-4 sm:p-6 pb-3">
                        <h2 class="font-semibold text-base">{{ __('dash.dueSoonTitle') }}</h2>
                    </div>
                    <div class="p-4 sm:p-6 pt-0">
                        @if ($dueSoon->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                                <div class="rounded-full bg-muted p-3">{!! $icon($iconPaths['calendarClock'], 'size-6 text-muted-foreground') !!}</div>
                                <p class="font-medium">{{ __('dash.dueSoonEmptyTitle') }}</p>
                                <p class="text-sm text-muted-foreground max-w-sm">{{ __('dash.dueSoonEmptyDesc') }}</p>
                            </div>
                        @else
                            <div class="divide-y rounded-lg border">
                                @foreach ($dueSoon as $task)
                                    @php
                                        $taskDone = $task->progress >= 100 || in_array($task->status, ['APPROVED', 'DELIVERED'], true);
                                        $barWidth = max(0, min(100, $task->progress));
                                    @endphp
                                    <a href="{{ route('tasks.show', $task->id) }}" class="card-lift w-full flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 px-4 py-3 sm:py-2.5 text-left hover:bg-muted/50 transition-colors">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium truncate">{{ $task->code }} — {{ $task->garment_piece }}</p>
                                            <p class="text-xs text-muted-foreground truncate">
                                                {{ $task->order?->order_number }} · {{ $task->assignedStaff?->name ?? __('dash.unassigned') }}
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                                            <div class="flex items-center gap-2 min-w-28 w-28">
                                                <div class="h-2 flex-1 rounded-full bg-muted overflow-hidden">
                                                    <div class="h-full rounded-full bar-anim {{ $taskDone ? 'bg-emerald-600' : 'bg-primary' }}" style="width: {{ $barWidth }}%"></div>
                                                </div>
                                                <span class="text-xs font-semibold tabular-nums w-9 text-right {{ $taskDone ? 'text-emerald-700' : '' }}">{{ $task->progress }}%</span>
                                            </div>
                                            <span class="inline-flex items-center gap-1 text-sm whitespace-nowrap text-muted-foreground">
                                                {!! $icon($iconPaths['calendarClock'], 'size-3.5 opacity-60') !!}
                                                {{ $task->deadline->format('M j, Y') }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <div class="grid lg:grid-cols-2 gap-4">
                {{-- Active-task workload per assignable staff account --}}
                <section class="rounded-lg border bg-card shadow-sm">
                    <div class="flex items-center justify-between gap-2 p-4 sm:p-6 pb-3">
                        <h2 class="font-semibold text-base">{{ __('dash.workloadTitle') }}</h2>
                    </div>
                    <div class="p-4 sm:p-6 pt-0">
                        @if (count($workload) === 0)
                            <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                                <div class="rounded-full bg-muted p-3">{!! $icon($iconPaths['users'], 'size-6 text-muted-foreground') !!}</div>
                                <p class="font-medium">{{ __('dash.workloadEmpty') }}</p>
                            </div>
                        @else
                            <div class="divide-y rounded-lg border max-h-96 overflow-y-auto">
                                @foreach ($workload as $row)
                                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium truncate">{{ $row['user']->name }}</p>
                                            <p class="text-xs text-muted-foreground truncate">{{ $row['user']->code }}</p>
                                        </div>
                                        <span class="rounded-full bg-primary/10 text-primary text-sm font-bold px-2.5 py-0.5 tabular-nums shrink-0">{{ $row['activeCount'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Latest audit entries --}}
                <section class="rounded-lg border bg-card shadow-sm">
                    <div class="flex items-center justify-between gap-2 p-4 sm:p-6 pb-3">
                        <h2 class="font-semibold text-base">{{ __('dash.activityTitle') }}</h2>
                    </div>
                    <div class="p-4 sm:p-6 pt-0">
                        @if ($recent->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                                <div class="rounded-full bg-muted p-3">{!! $icon($iconPaths['history'], 'size-6 text-muted-foreground') !!}</div>
                                <p class="font-medium">{{ __('dash.activityEmpty') }}</p>
                            </div>
                        @else
                            <div class="divide-y rounded-lg border max-h-80 overflow-y-auto">
                                @foreach ($recent as $log)
                                    <div class="flex items-center gap-3 px-4 py-2.5">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm truncate">
                                                <span class="font-medium">{{ $log->user?->name ?? $log->user_name ?? __('dash.system') }}</span>
                                                <span class="text-muted-foreground"> — {{ __('audit.action.'.$log->action) }}</span>
                                            </p>
                                            <p class="text-xs text-muted-foreground truncate">{{ $log->entity_type ?? '' }}</p>
                                        </div>
                                        <span class="text-xs text-muted-foreground shrink-0 whitespace-nowrap">{{ $log->created_at?->locale($locale)->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        @endif
    </div>
@endsection
