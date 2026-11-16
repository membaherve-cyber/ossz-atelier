@extends('layouts.app')

@section('title', __('shell.view.performance'))

@section('content')
    @php
        $upperType = strtoupper($rangeType);

        if ($upperType === 'QUARTER') {
            [$qYear, $qNumber] = array_map('intval', explode('-Q', $rangeValue));
            $rangeLabel = \App\Support\Trans::fill('perf.rangeQuarter', ['quarter' => $qNumber, 'year' => $qYear]);
            $prevValue = $qNumber === 1 ? sprintf('%d-Q4', $qYear - 1) : sprintf('%d-Q%d', $qYear, $qNumber - 1);
            $nextValue = $qNumber === 4 ? sprintf('%d-Q1', $qYear + 1) : sprintf('%d-Q%d', $qYear, $qNumber + 1);
        } elseif ($upperType === 'YEAR') {
            $rangeLabel = \App\Support\Trans::fill('perf.rangeYear', ['year' => $rangeValue]);
            $prevValue = (string) ((int) $rangeValue - 1);
            $nextValue = (string) ((int) $rangeValue + 1);
        } else {
            $monthStart = \Carbon\Carbon::parse($rangeValue.'-01');
            $rangeLabel = \App\Support\Trans::fill('perf.rangeMonth', [
                'month' => $monthStart->translatedFormat('F'),
                'year' => $monthStart->format('Y'),
            ]);
            $prevValue = $monthStart->copy()->subMonth()->format('Y-m');
            $nextValue = $monthStart->copy()->addMonth()->format('Y-m');
        }

        $scoreClass = static function ($score): string {
            if ($score === null) {
                return 'text-muted-foreground';
            }

            if ($score >= 75) {
                return 'text-emerald-700';
            }

            return $score >= 50 ? 'text-amber-700' : 'text-red-700';
        };

        $canConfigure = \App\Services\Permissions::allows($me, 'performance.configure');
        $canAssess = \App\Services\Permissions::allows($me, 'performance.assess');

        $weightTotal = (int) (
            (int) old('productivityWeight', $config->productivity_weight)
            + (int) old('timelyWeight', $config->timely_weight)
            + (int) old('qualityWeight', $config->quality_weight)
            + (int) old('reliabilityWeight', $config->reliability_weight)
            + (int) old('managementWeight', $config->management_weight)
        );
    @endphp

    <div class="space-y-4">
        <p class="max-w-3xl text-sm text-muted-foreground">{{ __('perf.subtitle') }}</p>

        <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="perf-period-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3 sm:px-6">
                <h2 id="perf-period-heading" class="font-display text-base font-semibold">{{ __('perf.reportingPeriod') }}</h2>
                <p class="text-sm font-medium text-muted-foreground">{{ $rangeLabel }}</p>
            </div>

            <form method="GET" action="{{ route('performance.index') }}" class="flex flex-wrap items-end gap-3 px-4 py-4 sm:px-6">
                <div class="w-40 space-y-1.5">
                    <label for="perf-type" class="text-sm font-medium">{{ __('perf.periodType') }}</label>
                    <select id="perf-type" name="type"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="MONTH" {{ $upperType === 'MONTH' ? 'selected' : '' }}>{{ __('perf.monthly') }}</option>
                        <option value="QUARTER" {{ $upperType === 'QUARTER' ? 'selected' : '' }}>{{ __('perf.quarterly') }}</option>
                        <option value="YEAR" {{ $upperType === 'YEAR' ? 'selected' : '' }}>{{ __('perf.annual') }}</option>
                    </select>
                </div>

                <div class="w-44 space-y-1.5">
                    <label for="perf-value" class="text-sm font-medium">
                        {{ __('perf.'.$rangeType) }}
                    </label>
                    <input id="perf-value" name="value" value="{{ $rangeValue }}" placeholder="2026-11"
                           class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                </div>

                <button type="submit"
                        class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                    {{ __('perf.apply') }}
                </button>

                <div class="flex items-center gap-1.5 pb-0.5">
                    <a href="{{ route('performance.index', ['type' => $upperType, 'value' => $prevValue]) }}"
                       aria-label="{{ __('perf.prevPeriod') }}"
                       class="inline-flex size-9 items-center justify-center rounded-md border bg-background hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                    <a href="{{ route('performance.index', ['type' => $upperType, 'value' => $nextValue]) }}"
                       aria-label="{{ __('perf.nextPeriod') }}"
                       class="inline-flex size-9 items-center justify-center rounded-md border bg-background hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
            </form>
        </section>

        @if ($canConfigure)
            <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="perf-weights-heading">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3 sm:px-6">
                    <h2 id="perf-weights-heading" class="font-display text-base font-semibold">{{ __('perf.weightsTitle') }}</h2>
                    <button type="submit" form="perf-weights-form"
                            class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 0 2.8L10.4 19.2a2 2 0 0 1-1.4.6H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 .6-1.4L14.2 3.6A2 2 0 0 1 15.2 3z"/><path d="M16 8l-8 8"/></svg>
                        {{ __('perf.saveWeights') }}
                    </button>
                </div>

                <form id="perf-weights-form" method="POST" action="{{ route('performance.config') }}" class="px-4 py-4 sm:px-6">
                    @csrf
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <div class="space-y-1.5">
                            <label for="w-productivity" class="text-sm font-medium">{{ __('perf.weightProductivity') }}</label>
                            <input id="w-productivity" type="number" min="0" max="100" name="productivityWeight"
                                   value="{{ old('productivityWeight', $config->productivity_weight) }}"
                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            @error('productivityWeight')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="w-timely" class="text-sm font-medium">{{ __('perf.weightTimely') }}</label>
                            <input id="w-timely" type="number" min="0" max="100" name="timelyWeight"
                                   value="{{ old('timelyWeight', $config->timely_weight) }}"
                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            @error('timelyWeight')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="w-quality" class="text-sm font-medium">{{ __('perf.weightQuality') }}</label>
                            <input id="w-quality" type="number" min="0" max="100" name="qualityWeight"
                                   value="{{ old('qualityWeight', $config->quality_weight) }}"
                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            @error('qualityWeight')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="w-reliability" class="text-sm font-medium">{{ __('perf.weightReliability') }}</label>
                            <input id="w-reliability" type="number" min="0" max="100" name="reliabilityWeight"
                                   value="{{ old('reliabilityWeight', $config->reliability_weight) }}"
                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            @error('reliabilityWeight')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="w-management" class="text-sm font-medium">{{ __('perf.weightManagement') }}</label>
                            <input id="w-management" type="number" min="0" max="100" name="managementWeight"
                                   value="{{ old('managementWeight', $config->management_weight) }}"
                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            @error('managementWeight')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="w-piece-target" class="text-sm font-medium">{{ __('perf.pieceTargetLabel') }}</label>
                            <input id="w-piece-target" type="number" min="1" max="500" name="pieceTarget"
                                   value="{{ old('pieceTarget', $config->piece_target) }}"
                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            @error('pieceTarget')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-medium {{ $weightTotal === 100 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ \App\Support\Trans::fill('perf.totalWeight', ['total' => $weightTotal]) }}
                            {{ $weightTotal === 100 ? __('perf.balanced') : __('perf.mustEqual100') }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ \App\Support\Trans::fill('perf.pieceTargetHint', ['target' => old('pieceTarget', $config->piece_target)]) }}
                        </p>
                    </div>
                </form>
            </section>
        @endif

        @if (count($rows) > 0)
            <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="perf-staff-heading">
                <div class="border-b px-4 py-3 sm:px-6">
                    <h2 id="perf-staff-heading" class="font-display text-base font-semibold">
                        {{ \App\Support\Trans::fill('perf.staffTableTitle', ['period' => $rangeLabel]) }}
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1150px] text-sm">
                        <thead class="bg-muted/50 text-xs font-medium text-muted-foreground">
                            <tr class="border-b">
                                <th rowspan="2" scope="col" class="px-4 py-2.5 text-left">{{ __('perf.colStaff') }}</th>
                                <th colspan="8" scope="colgroup" class="border-l px-3 py-2.5 text-center">{{ __('perf.colRecordedMetrics') }}</th>
                                <th colspan="5" scope="colgroup" class="border-l px-3 py-2.5 text-center">{{ __('perf.colScores') }}</th>
                                <th rowspan="2" scope="col" class="border-l px-3 py-2.5 text-center">{{ __('perf.colOverall') }}</th>
                                <th rowspan="2" scope="col" class="border-l min-w-[130px] px-3 py-2.5 text-left">{{ __('perf.colMgmtAssessment') }}</th>
                            </tr>
                            <tr class="border-b">
                                <th scope="col" class="border-l px-3 py-2.5 text-center">{{ __('perf.colAssigned') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colCompleted') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colOnTime') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colOverdue') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colAvgHours') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colQualityPct') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colCorrections') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colBlocked') }}</th>
                                <th scope="col" class="border-l px-3 py-2.5 text-center">{{ __('perf.colProductivity') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colTimeliness') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colQuality') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colReliability') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center">{{ __('perf.colMgmt') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $qualityPct = $row['inspections'] > 0
                                        ? (int) round($row['approved'] / $row['inspections'] * 100)
                                        : 0;
                                @endphp
                                <tr class="border-b transition-colors last:border-b-0 hover:bg-muted/30">
                                    <td class="px-4 py-3">
                                        <div class="min-w-[160px]">
                                            <p class="font-medium">{{ $row['user']->name }}</p>
                                            <p class="truncate text-xs text-muted-foreground">{{ $row['user']->code }} · {{ $row['user']->email }}</p>
                                        </div>
                                    </td>
                                    <td class="border-l px-3 py-3 text-center tabular-nums">{{ $row['assigned'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums">
                                        {{ $row['completed'] }}<span class="text-muted-foreground">/{{ $row['pieceTarget'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center tabular-nums">{{ $row['onTime'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums {{ $row['overdue'] > 0 ? 'font-semibold text-red-700' : '' }}">{{ $row['overdue'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums">{{ $row['avgHours'] !== null ? $row['avgHours'] : '—' }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums">{{ $qualityPct }}%</td>
                                    <td class="px-3 py-3 text-center tabular-nums">{{ $row['corrections'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums">{{ $row['blockedCount'] }}</td>
                                    <td class="border-l px-3 py-3 text-center tabular-nums {{ $scoreClass($row['productivity']) }}">{{ $row['productivity'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums {{ $scoreClass($row['timeliness']) }}">{{ $row['timeliness'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums {{ $scoreClass($row['quality']) }}">{{ $row['quality'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums {{ $scoreClass($row['reliability']) }}">{{ $row['reliability'] }}</td>
                                    <td class="px-3 py-3 text-center tabular-nums {{ $scoreClass($row['management']) }}">{{ $row['management'] !== null ? $row['management'] : '—' }}</td>
                                    <td class="border-l px-3 py-3 text-center">
                                        <span class="font-bold tabular-nums {{ $scoreClass($row['overall']) }}">{{ $row['overall'] !== null ? $row['overall'] : '—' }}</span>
                                    </td>
                                    <td class="border-l px-3 py-3">
                                        @if ($canAssess)
                                            <form method="POST" action="{{ route('performance.assess') }}"
                                                  class="flex items-center gap-1"
                                                  aria-label="{{ \App\Support\Trans::fill('perf.assessmentAria', ['name' => $row['user']->name]) }}">
                                                @csrf
                                                <input type="hidden" name="staffId" value="{{ $row['user']->id }}">
                                                <input type="hidden" name="periodValue" value="{{ $rangeValue }}">
                                                <label for="assess-{{ $row['user']->id }}" class="sr-only">{{ __('perf.assessScoreLabel') }}</label>
                                                <input id="assess-{{ $row['user']->id }}" type="number" min="0" max="100" name="score"
                                                       value="{{ old('score', $row['assessment']?->score) }}"
                                                       class="h-8 w-[76px] rounded-md border border-input bg-background px-2 text-center text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                <button type="submit"
                                                        aria-label="{{ \App\Support\Trans::fill('perf.saveAssessmentAria', ['name' => $row['user']->name]) }}"
                                                        class="inline-flex size-8 items-center justify-center rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 0 2.8L10.4 19.2a2 2 0 0 1-1.4.6H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 .6-1.4L14.2 3.6A2 2 0 0 1 15.2 3z"/><path d="M16 8l-8 8"/></svg>
                                                </button>
                                            </form>
                                        @elseif ($row['assessment'] !== null)
                                            <span class="inline-flex shrink-0 items-center rounded-full border border-current {{ $scoreClass($row['assessment']->score) }} bg-muted/40 px-2 py-0.5 text-xs font-semibold tabular-nums">
                                                {{ $row['assessment']->score }}/100
                                            </span>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="border-t px-4 py-3 text-xs text-muted-foreground sm:px-6">{{ __('perf.formulaNote') }}</p>
            </section>

            <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><path d="M16 7h6v6"/></svg>
                {{ __('perf.colorNote') }}
            </p>
        @else
            <section class="rounded-xl border bg-card px-6 py-16 text-center shadow-sm">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <p class="mt-4 font-medium">{{ __('perf.noStaff') }}</p>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('perf.noStaffDesc') }}</p>
            </section>
        @endif

        @if ($canAssess)
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="perf-assess-heading">
                    <div class="border-b px-4 py-3 sm:px-6">
                        <h2 id="perf-assess-heading" class="font-display text-base font-semibold">{{ __('perf.assessSectionTitle') }}</h2>
                    </div>

                    <form method="POST" action="{{ route('performance.assess') }}" class="space-y-4 px-4 py-4 sm:px-6">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label for="assess-staff" class="text-sm font-medium">{{ __('perf.assessStaffLabel') }} <span class="text-red-600">*</span></label>
                                <select id="assess-staff" name="staffId" required
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option value="" disabled selected>— {{ __('perf.assessStaffLabel') }} —</option>
                                    @foreach ($staffList as $staff)
                                        <option value="{{ $staff->id }}" {{ old('staffId') === $staff->id ? 'selected' : '' }}>
                                            {{ $staff->name }} ({{ $staff->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('staffId')
                                    <p class="text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="assess-score" class="text-sm font-medium">{{ __('perf.assessScoreLabel') }} <span class="text-red-600">*</span></label>
                                <input id="assess-score" type="number" min="0" max="100" name="score" required value="{{ old('score') }}"
                                       class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @error('score')
                                    <p class="text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label for="assess-period" class="text-sm font-medium">{{ __('perf.assessPeriodLabel') }}</label>
                            <input id="assess-period" name="periodValue" value="{{ old('periodValue', $rangeValue) }}" placeholder="2026-11"
                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:w-44">
                            @error('periodValue')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="assess-notes" class="text-sm font-medium">{{ __('perf.assessNotesLabel') }} <span class="text-xs font-normal text-muted-foreground">(optional)</span></label>
                            <textarea id="assess-notes" name="notes" rows="3" placeholder="{{ __('perf.assessNotesPlaceholder') }}"
                                      class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"></textarea>
                            @error('notes')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs text-muted-foreground">{{ __('perf.invalidScoreDesc') }}</p>
                            <button type="submit"
                                    class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                {{ __('perf.assessSubmit') }}
                            </button>
                        </div>
                    </form>
                </section>

                <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="perf-assess-recent-heading">
                    <div class="border-b px-4 py-3 sm:px-6">
                        <h2 id="perf-assess-recent-heading" class="font-display text-base font-semibold">{{ __('perf.assessRecentTitle') }}</h2>
                    </div>

                    @if (count($assessments) > 0)
                        <ul class="max-h-96 divide-y overflow-y-auto px-4 sm:px-6">
                            @foreach ($assessments as $assessment)
                                <li class="flex items-start justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium">{{ $assessment->user?->name ?? $assessment->staff_name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ $assessment->period }} · {{ optional($assessment->created_at)->format('M j, Y') }}
                                        </p>
                                        @if ($assessment->comments)
                                            <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ $assessment->comments }}</p>
                                        @endif
                                    </div>
                                    <span class="inline-flex shrink-0 items-center rounded-full border border-current {{ $scoreClass(min(100, max(0, (int) $assessment->score))) }} bg-muted/40 px-2 py-0.5 text-xs font-semibold tabular-nums">
                                        {{ $assessment->score }}/100
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="px-4 py-10 text-center text-sm text-muted-foreground sm:px-6">{{ __('perf.assessNone') }}</p>
                    @endif
                </section>
            </div>
        @endif
    </div>
@endsection
