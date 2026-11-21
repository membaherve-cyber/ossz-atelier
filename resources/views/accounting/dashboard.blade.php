@extends('layouts.app')

@section('title', __('fin.title'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — financial dashboard (GET /accounting, perm
         * accounting.view; enhancement spec §5).
         *
         * Variables (DashboardController): $dash (revenue, paymentsReceived,
         * outstandingBalances, expenses, salaries, advancesOutstanding,
         * advancesDisbursed, netResult, cashMovement, monthRevenue,
         * monthExpenses, series [{key,label,revenue,payments,expenses,
         * salaries,net}], categoryBreakdown [{name,group,amount,color}]),
         * $period (from/to CarbonImmutable + preset) and $presets.
         *
         * Charts are pure server-rendered inline SVG (no JS chart library —
         * no build step); bar heights are scaled to each chart's max value.
         */

        $money = [\App\Support\Money::class, 'format'];

        // Period pill labels (fin.period*), keyed by AccountingService preset.
        $presetLabels = [
            'today' => __('fin.periodToday'),
            'week' => __('fin.periodWeek'),
            'month' => __('fin.periodMonth'),
            'last_month' => __('fin.periodLastMonth'),
            'year' => __('fin.periodYear'),
            'all' => __('fin.periodAll'),
            'custom' => __('fin.periodCustom'),
        ];

        // Sign-aware tone for the derived figures (emerald positive /
        // red negative — the app palette avoids pure blue).
        $tone = static fn (float $value): string => $value > 0.004 ? 'text-emerald-600' : ($value < -0.004 ? 'text-red-600' : '');

        // ---- Summary cards (spec §5 — the nine headline figures) --------
        $cards = [
            ['label' => __('fin.cardRevenue'), 'hint' => __('fin.cardRevenueHint'), 'value' => \App\Support\Money::format($dash['revenue']), 'tone' => ''],
            ['label' => __('fin.cardPayments'), 'hint' => __('fin.cardPaymentsHint'), 'value' => \App\Support\Money::format($dash['paymentsReceived']), 'tone' => ''],
            ['label' => __('fin.cardExpenses'), 'hint' => __('fin.cardExpensesHint'), 'value' => \App\Support\Money::format($dash['expenses']), 'tone' => ''],
            ['label' => __('fin.cardSalaries'), 'hint' => __('fin.cardSalariesHint'), 'value' => \App\Support\Money::format($dash['salaries']), 'tone' => ''],
            ['label' => __('fin.cardOutstanding'), 'hint' => __('fin.cardOutstandingHint'), 'value' => \App\Support\Money::format($dash['outstandingBalances']), 'tone' => ''],
            ['label' => __('fin.cardAdvances'), 'hint' => __('fin.cardAdvancesHint'), 'value' => \App\Support\Money::format($dash['advancesOutstanding']), 'tone' => ''],
            ['label' => __('fin.cardTreasury'), 'hint' => __('fin.cardTreasuryHint'), 'value' => \App\Support\Money::format($dash['treasuryTotal'] ?? 0), 'tone' => ''],
            ['label' => __('fin.cardSupplierDebts'), 'hint' => __('fin.cardSupplierDebtsHint'), 'value' => \App\Support\Money::format($dash['supplierDebts'] ?? 0), 'tone' => ''],
            ['label' => __('fin.cardNetResult'), 'hint' => __('fin.cardNetResultHint'), 'value' => \App\Support\Money::format($dash['netResult']), 'tone' => $tone((float) $dash['netResult'])],
            ['label' => __('fin.cardMonthRevenue'), 'hint' => '', 'value' => \App\Support\Money::format($dash['monthRevenue']), 'tone' => ''],
            ['label' => __('fin.cardMonthExpenses'), 'hint' => '', 'value' => \App\Support\Money::format($dash['monthExpenses']), 'tone' => ''],
        ];

        // ---- Chart series geometry ---------------------------------------
        $series = $dash['series'];
        $monthCount = count($series);
        $maxRevenue = 0.0;
        $maxExpenses = 0.0;

        foreach ($series as $bucket) {
            $maxRevenue = max($maxRevenue, (float) $bucket['revenue']);
            $maxExpenses = max($maxExpenses, (float) $bucket['expenses']);
        }

        $maxCompare = max($maxRevenue, $maxExpenses);
        $hasRevenue = $maxRevenue > 0.004;
        $hasExpenses = $maxExpenses > 0.004;
        $hasCompare = $maxCompare > 0.004;

        // SVG canvas: 600x220, bars grow up to a 150px ceiling above the
        // y=170 baseline; month labels sit below (every bar when <= 12
        // months, every other bar beyond that).
        $chartW = 600.0;
        $baseline = 170.0;
        $barCeiling = 150.0;
        $labelEvery = $monthCount <= 12 ? 1 : 2;

        $barHeight = static function (float $value, float $max) use ($barCeiling): float {
            if ($max <= 0.004) {
                return 2.0;
            }

            return max(2.0, round($value / $max * $barCeiling));
        };

        // ---- Expense category donut (r=60, stroke-width 28) --------------
        $categories = $dash['categoryBreakdown'];
        $categoryTotal = 0.0;

        foreach ($categories as $category) {
            $categoryTotal += (float) $category['amount'];
        }

        $circumference = 2 * pi() * 60;
    @endphp

    <div class="space-y-5">
        <style>
            .chart-bar{transform-box:fill-box;transform-origin:bottom;animation:growBar .5s ease-out both}
            @keyframes growBar{from{transform:scaleY(0)}}
            @media(prefers-reduced-motion:reduce){.chart-bar{animation:none}}
            .card-anim{opacity:0;animation:cardIn .4s ease-out forwards}
            @keyframes cardIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
            @media(prefers-reduced-motion:reduce){.card-anim{animation:none;opacity:1}}
        </style>

        {{-- Page header + quick links into the ledger screens --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('fin.title') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('fin.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('expenses.index') }}"
                   class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    {{ __('fin.viewExpenses') }}
                </a>
                <a href="{{ route('payments.index') }}"
                   class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M2 7v10"/><path d="M2 12h20"/><path d="M22 7v10"/><path d="M2 7h20"/></svg>
                    {{ __('fin.viewPayments') }}
                </a>
            </div>
        </div>

        {{-- Period selector: preset pills + custom from/to range --}}
        <div class="rounded-lg border bg-card p-3 sm:p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach ($presets as $preset)
                        <a href="{{ route('accounting.dashboard', ['period' => $preset]) }}"
                           class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $period['preset'] === $preset ? 'bg-primary text-primary-foreground' : 'border bg-background text-muted-foreground hover:text-foreground' }}"
                           @if ($period['preset'] === $preset) aria-current="true" @endif>{{ $presetLabels[$preset] }}</a>
                    @endforeach
                </div>
                <p class="hidden text-xs text-muted-foreground tabular-nums sm:block">{{ $period['from']->locale(app()->getLocale())->translatedFormat('j M Y') }} — {{ $period['to']->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
            </div>
            @if ($period['preset'] === 'custom')
                <form method="GET" action="{{ route('accounting.dashboard') }}" class="mt-3 flex flex-wrap items-end gap-2 border-t pt-3">
                    <input type="hidden" name="period" value="custom" />
                    <div>
                        <label for="fin-from" class="block text-xs font-medium text-muted-foreground">{{ __('fin.from') }}</label>
                        <input type="date" id="fin-from" name="from" value="{{ $period['from']->toDateString() }}"
                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="fin-to" class="block text-xs font-medium text-muted-foreground">{{ __('fin.to') }}</label>
                        <input type="date" id="fin-to" name="to" value="{{ $period['to']->toDateString() }}"
                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        {{ __('fin.apply') }}
                    </button>
                </form>
            @endif
        </div>

        {{-- Nine headline cards --}}
        <div class="grid grid-cols-2 gap-3 stagger sm:gap-4 lg:grid-cols-3">
            @foreach ($cards as $i => $card)
                <div class="card-anim rounded-xl border bg-card p-4 card-lift" style="animation-delay: {{ $i * 40 }}ms">
                    <p class="text-xs font-semibold text-muted-foreground">{{ $card['label'] }}</p>
                    <p class="mt-1.5 text-xl font-bold tabular-nums sm:text-2xl {{ $card['tone'] }}">{{ $card['value'] }}</p>
                    @if ($card['hint'] !== '')
                        <p class="mt-1 text-[11px] text-muted-foreground">{{ $card['hint'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Net cash movement strip --}}
        <div class="card-anim rounded-xl border bg-card p-4 card-lift" style="animation-delay: 360ms">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <p class="text-xs font-semibold text-muted-foreground">{{ __('fin.cashMovement') }}</p>
                <p class="text-xl font-bold tabular-nums sm:text-2xl {{ $tone((float) $dash['cashMovement']) }}">{{ \App\Support\Money::format($dash['cashMovement']) }}</p>
            </div>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <p class="text-sm"><span class="text-muted-foreground">{{ __('fin.cashIn') }}: </span><span class="font-semibold tabular-nums">{{ \App\Support\Money::format($dash['paymentsReceived']) }}</span></p>
                <p class="text-sm"><span class="text-muted-foreground">{{ __('fin.cashOut') }}: </span><span class="font-semibold tabular-nums">{{ \App\Support\Money::format((float) $dash['expenses'] + (float) $dash['salaries'] + (float) $dash['advancesDisbursed']) }}</span></p>
            </div>
        </div>

        {{-- Revenue over time (SVG bars) --}}
        <div class="grid gap-3 sm:gap-4 lg:grid-cols-2">
            <div class="card-anim rounded-xl border bg-card p-4" style="animation-delay: 400ms">
                <h3 class="text-sm font-semibold">{{ __('fin.chartRevenue') }}</h3>
                @if ($hasRevenue)
                    <svg viewBox="0 0 600 220" class="mt-3 h-44 w-full" role="img" aria-label="{{ __('fin.chartRevenue') }}">
                        <title>{{ __('fin.chartRevenue') }}</title>
                        <line x1="0" y1="{{ $baseline }}" x2="{{ $chartW }}" y2="{{ $baseline }}" stroke="#e4e4e7" stroke-width="1" />
                        @foreach ($series as $i => $bucket)
                            @php
                                $slot = $chartW / max(1, $monthCount);
                                $barW = min(48.0, $slot * 0.55);
                                $x = $i * $slot + ($slot - $barW) / 2;
                                $h = $barHeight((float) $bucket['revenue'], $maxRevenue);
                            @endphp
                            <rect class="chart-bar" x="{{ round($x, 1) }}" y="{{ round($baseline - $h, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($h, 1) }}" rx="3" fill="#5533aa" style="animation-delay: {{ $i * 30 }}ms">
                                <title>{{ $bucket['label'] }} — {{ \App\Support\Money::format($bucket['revenue']) }}</title>
                            </rect>
                            @if ($i % $labelEvery === 0)
                                <text x="{{ round($i * $slot + $slot / 2, 1) }}" y="190" text-anchor="middle" style="fill:#6b6b76;font-size:10px">{{ $bucket['label'] }}</text>
                            @endif
                        @endforeach
                    </svg>
                @else
                    <p class="mt-6 text-sm text-muted-foreground">{{ __('fin.noChartData') }}</p>
                @endif
            </div>

            {{-- Expenses over time (SVG bars) --}}
            <div class="card-anim rounded-xl border bg-card p-4" style="animation-delay: 440ms">
                <h3 class="text-sm font-semibold">{{ __('fin.chartExpenses') }}</h3>
                @if ($hasExpenses)
                    <svg viewBox="0 0 600 220" class="mt-3 h-44 w-full" role="img" aria-label="{{ __('fin.chartExpenses') }}">
                        <title>{{ __('fin.chartExpenses') }}</title>
                        <line x1="0" y1="{{ $baseline }}" x2="{{ $chartW }}" y2="{{ $baseline }}" stroke="#e4e4e7" stroke-width="1" />
                        @foreach ($series as $i => $bucket)
                            @php
                                $slot = $chartW / max(1, $monthCount);
                                $barW = min(48.0, $slot * 0.55);
                                $x = $i * $slot + ($slot - $barW) / 2;
                                $h = $barHeight((float) $bucket['expenses'], $maxExpenses);
                            @endphp
                            <rect class="chart-bar" x="{{ round($x, 1) }}" y="{{ round($baseline - $h, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($h, 1) }}" rx="3" fill="#be123c" style="animation-delay: {{ $i * 30 }}ms">
                                <title>{{ $bucket['label'] }} — {{ \App\Support\Money::format($bucket['expenses']) }}</title>
                            </rect>
                            @if ($i % $labelEvery === 0)
                                <text x="{{ round($i * $slot + $slot / 2, 1) }}" y="190" text-anchor="middle" style="fill:#6b6b76;font-size:10px">{{ $bucket['label'] }}</text>
                            @endif
                        @endforeach
                    </svg>
                @else
                    <p class="mt-6 text-sm text-muted-foreground">{{ __('fin.noChartData') }}</p>
                @endif
            </div>

            {{-- Revenue vs expenses (grouped pairs) --}}
            <div class="card-anim rounded-xl border bg-card p-4" style="animation-delay: 480ms">
                <h3 class="text-sm font-semibold">{{ __('fin.chartComparison') }}</h3>
                @if ($hasCompare)
                    <div class="mt-3 flex items-center gap-4 text-xs text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full" style="background-color:#5533aa"></span>{{ __('fin.legendRevenue') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full" style="background-color:#be123c"></span>{{ __('fin.legendExpenses') }}</span>
                    </div>
                    <svg viewBox="0 0 600 220" class="mt-2 h-44 w-full" role="img" aria-label="{{ __('fin.chartComparison') }}">
                        <title>{{ __('fin.chartComparison') }}</title>
                        <line x1="0" y1="{{ $baseline }}" x2="{{ $chartW }}" y2="{{ $baseline }}" stroke="#e4e4e7" stroke-width="1" />
                        @foreach ($series as $i => $bucket)
                            @php
                                $slot = $chartW / max(1, $monthCount);
                                $pairW = $slot * 0.62;
                                $barW = ($pairW - 4) / 2;
                                $x1 = $i * $slot + ($slot - $pairW) / 2;
                                $x2 = $x1 + $barW + 4;
                                $hRev = $barHeight((float) $bucket['revenue'], $maxCompare);
                                $hExp = $barHeight((float) $bucket['expenses'], $maxCompare);
                            @endphp
                            <rect class="chart-bar" x="{{ round($x1, 1) }}" y="{{ round($baseline - $hRev, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($hRev, 1) }}" rx="2" fill="#5533aa" style="animation-delay: {{ $i * 30 }}ms">
                                <title>{{ $bucket['label'] }} — {{ __('fin.legendRevenue') }}: {{ \App\Support\Money::format($bucket['revenue']) }}</title>
                            </rect>
                            <rect class="chart-bar" x="{{ round($x2, 1) }}" y="{{ round($baseline - $hExp, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($hExp, 1) }}" rx="2" fill="#be123c" style="animation-delay: {{ $i * 30 + 40 }}ms">
                                <title>{{ $bucket['label'] }} — {{ __('fin.legendExpenses') }}: {{ \App\Support\Money::format($bucket['expenses']) }}</title>
                            </rect>
                            @if ($i % $labelEvery === 0)
                                <text x="{{ round($i * $slot + $slot / 2, 1) }}" y="190" text-anchor="middle" style="fill:#6b6b76;font-size:10px">{{ $bucket['label'] }}</text>
                            @endif
                        @endforeach
                    </svg>
                @else
                    <p class="mt-6 text-sm text-muted-foreground">{{ __('fin.noChartData') }}</p>
                @endif
            </div>

            {{-- Expense breakdown by category (SVG donut + legend) --}}
            <div class="card-anim rounded-xl border bg-card p-4" style="animation-delay: 520ms">
                <h3 class="text-sm font-semibold">{{ __('fin.chartCategories') }}</h3>
                @if ($categoryTotal > 0.004)
                    <div class="mt-3 flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                        <svg viewBox="0 0 160 160" class="h-40 w-40 shrink-0" role="img" aria-label="{{ __('fin.chartCategories') }}">
                            <title>{{ __('fin.chartCategories') }}</title>
                            @php
                                $start = 0.0;
                            @endphp
                            @foreach ($categories as $category)
                                @php
                                    $len = ((float) $category['amount'] / $categoryTotal) * $circumference;
                                @endphp
                                <circle cx="80" cy="80" r="60" fill="none" stroke="{{ $category['color'] }}" stroke-width="28"
                                        stroke-dasharray="{{ round($len, 2) }} {{ round($circumference, 2) }}" stroke-dashoffset="{{ round(-$start, 2) }}"
                                        transform="rotate(-90 80 80)">
                                    <title>{{ $category['name'] }} — {{ \App\Support\Money::format($category['amount']) }}</title>
                                </circle>
                                @php
                                    $start += $len;
                                @endphp
                            @endforeach
                            <text x="80" y="77" text-anchor="middle" style="fill:#26262c;font-size:15px;font-weight:700">{{ \App\Support\Money::amount($categoryTotal) }}</text>
                            <text x="80" y="94" text-anchor="middle" style="fill:#6b6b76;font-size:10px">XAF</text>
                        </svg>
                        <ul class="w-full min-w-0 space-y-1.5 text-sm">
                            @foreach ($categories as $category)
                                <li class="flex items-center justify-between gap-2">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="inline-block size-2.5 shrink-0 rounded-full" style="background-color: {{ $category['color'] }}"></span>
                                        <span class="truncate">{{ $category['name'] }}</span>
                                    </span>
                                    <span class="whitespace-nowrap text-muted-foreground tabular-nums">{{ \App\Support\Money::format($category['amount']) }} · {{ round((float) $category['amount'] / $categoryTotal * 100, 1) }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="mt-6 text-sm text-muted-foreground">{{ __('fin.noChartData') }}</p>
                @endif
            </div>
        </div>

        {{-- Monthly financial performance table --}}
        <div class="card-anim overflow-hidden rounded-xl border bg-card" style="animation-delay: 560ms">
            <div class="p-4 pb-0">
                <h3 class="text-sm font-semibold">{{ __('fin.chartMonthly') }}</h3>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('fin.colMonth') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('fin.colRevenue') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('fin.colPayments') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('fin.colExpenses') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('fin.colSalaries') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('fin.colNet') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($series as $bucket)
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-2.5 font-medium">{{ $bucket['label'] }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($bucket['revenue']) }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ \App\Support\Money::amount($bucket['payments']) }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ \App\Support\Money::amount($bucket['expenses']) }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ \App\Support\Money::amount($bucket['salaries']) }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums {{ $tone((float) $bucket['net']) }}">{{ \App\Support\Money::amount($bucket['net']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="border-t bg-muted/30">
                            <td class="whitespace-nowrap px-4 py-2.5 font-semibold">{{ __('fin.totalRow') }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($dash['revenue']) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($dash['paymentsReceived']) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($dash['expenses']) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($dash['salaries']) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums {{ $tone((float) $dash['netResult']) }}">{{ \App\Support\Money::format($dash['netResult']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Calculation rules note --}}
        <div class="card-anim rounded-lg border bg-muted/40 p-4" style="animation-delay: 600ms">
            <p class="text-sm font-semibold">{{ __('fin.rulesTitle') }}</p>
            <p class="mt-1 text-sm text-muted-foreground">{{ __('fin.rulesBody') }}</p>
        </div>
    </div>
@endsection
