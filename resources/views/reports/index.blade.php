@extends('layouts.app')

@section('title', __('shell.view.reports'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — production reports (GET /reports, perm
         * production.reports). Period selector + creation-based stat cards +
         * by-status / by-category bars + completion-based top staff.
         *
         * Variables (ReportController): $period ('month'|'quarter'|'year'),
         * $stats (totalTasks, completed, delivered, cancelled, blocked,
         * overdue, avgProgress, totalPieces, byStatus [10 statuses],
         * byCategory [6 categories], topStaff [<= 5]), $start (inclusive) and
         * $end (EXCLUSIVE) Carbon boundaries.
         */

        // Displayed range — $end is the first instant AFTER the period, so
        // the last covered day is $end - 1 ("Nov 1, 2026 — Nov 30, 2026").
        $rangeText = $start->format('M j, Y') . ' — ' . $end->copy()->subDay()->format('M j, Y');

        // Dot + bar palettes for the breakdowns. UNASSIGNED (stone-300),
        // ONGOING (amber-400) and FULL (teal-400) are verbatim from the live
        // capture; the remaining hues follow each entry's badge family
        // (x-badges.status palette) at bar intensity.
        $statusColors = [
            'UNASSIGNED' => 'bg-stone-300',
            'ASSIGNED' => 'bg-teal-400',
            'ONGOING' => 'bg-amber-400',
            'AWAITING_INSPECTION' => 'bg-orange-400',
            'CHANGES_REQUESTED' => 'bg-rose-400',
            'APPROVED' => 'bg-green-400',
            'READY_FOR_PICKUP' => 'bg-emerald-400',
            'DELIVERED' => 'bg-stone-800',
            'BLOCKED' => 'bg-red-400',
            'CANCELLED' => 'bg-stone-400',
        ];

        $categoryColors = [
            'FULL' => 'bg-teal-400',
            'CUTTING' => 'bg-orange-400',
            'SEWING' => 'bg-violet-400',
            'FINISHING' => 'bg-sky-400',
            'IRONING' => 'bg-amber-400',
            'OTHER' => 'bg-stone-400',
        ];

        // Tone convention shared with the performance view (perf.colorNote:
        // emerald 75+, amber 50+, red below 50).
        $scoreTone = static function (float $value): string {
            if ($value >= 75) {
                return 'text-emerald-700';
            }

            return $value >= 50 ? 'text-amber-700' : 'text-red-700';
        };
    @endphp

    <div class="space-y-4">
        <p class="max-w-3xl text-sm text-muted-foreground">{{ __('rep.subtitle') }}</p>

        <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="rep-period-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3 sm:px-6">
                <h2 id="rep-period-heading" class="font-display text-base font-semibold">{{ __('rep.reportingPeriod') }}</h2>
                <p class="text-sm font-medium text-muted-foreground">{{ $rangeText }}</p>
            </div>

            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3 px-4 py-4 sm:px-6">
                <div class="w-48 space-y-1.5">
                    <label for="rep-period" class="text-sm font-medium">{{ __('rep.reportType') }}</label>
                    <select id="rep-period" name="period"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>{{ __('rep.monthlyReport') }}</option>
                        <option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>{{ __('rep.quarterlyReport') }}</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>{{ __('rep.annualReport') }}</option>
                    </select>
                </div>
                <button type="submit"
                        class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                    {{ __('common.filter') }}
                </button>
            </form>
        </section>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs font-medium text-muted-foreground">{{ __('rep.totalTasks') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums">{{ $stats['totalTasks'] }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('rep.totalTasksHint') }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs font-medium text-muted-foreground">{{ __('rep.tasksCompleted') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums">{{ $stats['completed'] }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('rep.tasksCompletedHint') }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs font-medium text-muted-foreground">{{ __('rep.tasksDelivered') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums">{{ $stats['delivered'] }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('rep.tasksDeliveredHint') }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs font-medium text-muted-foreground">{{ __('rep.piecesCompleted') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums">{{ $stats['totalPieces'] }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('rep.piecesCompletedHint') }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs font-medium text-muted-foreground">{{ __('rep.avgProgress') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums {{ $scoreTone((float) $stats['avgProgress']) }}">{{ $stats['avgProgress'] }}%</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ $rangeText }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs font-medium text-muted-foreground">{{ __('rep.overdue') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums {{ $stats['overdue'] > 0 ? 'text-red-700' : '' }}">{{ $stats['overdue'] }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ $rangeText }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs font-medium text-muted-foreground">{{ __('rep.blocked') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums {{ $stats['blocked'] > 0 ? 'text-red-700' : '' }}">{{ $stats['blocked'] }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ $rangeText }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <p class="text-xs font-medium text-muted-foreground">{{ __('rep.cancelled') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold tabular-nums{{ $stats['cancelled'] > 0 ? ' text-red-700' : '' }}">{{ $stats['cancelled'] }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ $rangeText }}</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="rep-status-heading">
                <div class="border-b px-4 py-3 sm:px-6">
                    <h2 id="rep-status-heading" class="font-display text-base font-semibold">{{ __('rep.byStatusTitle') }}</h2>
                </div>
                <ul class="space-y-3 px-4 py-4 sm:px-6">
                    @foreach ($stats['byStatus'] as $status => $statusCount)
                        @if ($statusCount > 0)
                            @php
                                $statusPct = (int) round($statusCount / $stats['totalTasks'] * 100);
                            @endphp
                            <li>
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="inline-block size-2.5 shrink-0 rounded-full {{ $statusColors[$status] ?? 'bg-stone-300' }}" aria-hidden="true"></span>
                                        <span class="truncate">{{ __('labels.status.'.$status) }}</span>
                                    </span>
                                    <span class="shrink-0 font-medium tabular-nums">{{ $statusCount }} <span class="text-xs font-normal text-muted-foreground">({{ $statusPct }}%)</span></span>
                                </div>
                                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div class="h-full rounded-full {{ $statusColors[$status] ?? 'bg-stone-300' }}" style="width: {{ $statusPct }}%"></div>
                                </div>
                            </li>
                        @endif
                    @endforeach
                    @if ($stats['totalTasks'] === 0)
                        <li class="py-4 text-center text-sm text-muted-foreground">{{ __('rep.noData') }}</li>
                    @endif
                </ul>
            </section>

            <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="rep-category-heading">
                <div class="border-b px-4 py-3 sm:px-6">
                    <h2 id="rep-category-heading" class="font-display text-base font-semibold">{{ __('rep.byCategoryTitle') }}</h2>
                </div>
                <ul class="space-y-3 px-4 py-4 sm:px-6">
                    @foreach ($stats['byCategory'] as $category => $categoryCount)
                        @if ($categoryCount > 0)
                            @php
                                $categoryPct = (int) round($categoryCount / $stats['totalTasks'] * 100);
                            @endphp
                            <li>
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="inline-block size-2.5 shrink-0 rounded-full {{ $categoryColors[$category] ?? 'bg-stone-400' }}" aria-hidden="true"></span>
                                        <span class="truncate">{{ __('labels.category.'.$category) }}</span>
                                    </span>
                                    <span class="shrink-0 font-medium tabular-nums">{{ $categoryCount }} <span class="text-xs font-normal text-muted-foreground">({{ $categoryPct }}%)</span></span>
                                </div>
                                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div class="h-full rounded-full {{ $categoryColors[$category] ?? 'bg-stone-400' }}" style="width: {{ $categoryPct }}%"></div>
                                </div>
                            </li>
                        @endif
                    @endforeach
                    @if ($stats['totalTasks'] === 0)
                        <li class="py-4 text-center text-sm text-muted-foreground">{{ __('rep.noData') }}</li>
                    @endif
                </ul>
            </section>
        </div>

        <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="rep-top-staff-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3 sm:px-6">
                <h2 id="rep-top-staff-heading" class="font-display text-base font-semibold">{{ __('rep.topStaffTitle') }}</h2>
                <p class="text-xs text-muted-foreground">{{ $rangeText }}</p>
            </div>

            @if ($stats['topStaff'] !== [])
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[480px] text-sm">
                        <thead class="bg-muted/50 text-xs font-medium text-muted-foreground">
                            <tr class="border-b">
                                <th scope="col" class="px-4 py-2.5 text-left">{{ __('rep.colStaff') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('rep.colCompleted') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('rep.colPieces') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['topStaff'] as $top)
                                <tr class="border-b transition-colors last:border-b-0 hover:bg-muted/30">
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ $top['user']->name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ $top['user']->code }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-center font-semibold tabular-nums">{{ $top['completed'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums">{{ $top['pieces'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="px-4 py-8 text-center text-sm text-muted-foreground sm:px-6">{{ __('rep.topStaffNone') }}</p>
            @endif
        </section>

        <p class="text-xs text-muted-foreground">{{ __('rep.footnote') }}</p>
    </div>
@endsection
