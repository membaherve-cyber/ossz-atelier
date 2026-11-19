@extends($print ? 'accounting.print' : 'layouts.app')

@section('title', __('frep.title'))

@section('content')
    {{-- Scoped print rules: the selector bar never prints and the page prints on white. --}}
    <style>
        @media print { .no-print { display: none !important; } body { background: #fff; } }
    </style>

    @php
        /**
         * OSSZ ATELIER — financial reports (GET /accounting/reports, perm
         * accounting.reports; enhancement spec §12).
         *
         * Variables (FinancialReportController@index): $report (fixed
         * contract — title, columns key=>label, rows as key=>PRE-FORMATTED
         * strings, summary label=>formatted or []), $type (selected key),
         * $types (ordered keys), $period (from/to CarbonImmutable + preset),
         * $print (bool — renders through the standalone accounting.print
         * layout instead of the app shell).
         *
         * The same URL streams a UTF-8 BOM CSV via ?export=csv and opens a
         * printable copy via ?print=1.
         */
    @endphp

    <div class="space-y-5">
        @unless ($print)
            {{-- Selector bar (type + period) — GET keeps reports shareable --}}
            <div class="no-print rounded-lg border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                    <form method="GET" action="{{ route('accounting.reports') }}" class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="frep-type" class="block text-xs font-medium text-muted-foreground">{{ __('frep.reportType') }}</label>
                            <select id="frep-type" name="type"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($types as $typeKey)
                                    <option value="{{ $typeKey }}" {{ $type === $typeKey ? 'selected' : '' }}>{{ __('frep.type.'.$typeKey) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="frep-from" class="block text-xs font-medium text-muted-foreground">{{ __('frep.fromDate') }}</label>
                            <input type="date" id="frep-from" name="from" value="{{ $period['from']->toDateString() }}"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <div>
                            <label for="frep-to" class="block text-xs font-medium text-muted-foreground">{{ __('frep.toDate') }}</label>
                            <input type="date" id="frep-to" name="to" value="{{ $period['to']->toDateString() }}"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                                {{ __('frep.run') }}
                            </button>
                        </div>
                    </form>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('accounting.reports', array_merge(request()->query(), ['export' => 'csv'])) }}"
                           class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                            {{ __('frep.exportCsv') }}
                        </a>
                        <a href="{{ route('accounting.reports', array_merge(request()->query(), ['print' => '1'])) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                            {{ __('frep.print') }}
                        </a>
                    </div>
                </div>
            </div>
        @endunless

        {{-- Report card --}}
        <div id="report-area" class="page-enter rounded-xl border bg-card p-4 sm:p-6">
            <h2 class="text-lg font-bold">{{ $report['title'] }}</h2>
            <p class="mt-0.5 text-xs text-muted-foreground">{{ \App\Support\Trans::fill('frep.range', ['from' => $period['from']->locale(app()->getLocale())->translatedFormat('j M Y'), 'to' => $period['to']->locale(app()->getLocale())->translatedFormat('j M Y')]) }}</p>

            @if (empty($report['rows']))
                {{-- Empty state --}}
                <div class="flex flex-col items-center justify-center gap-2 py-10 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    </div>
                    <p class="font-semibold">{{ __('frep.emptyTitle') }}</p>
                    <p class="max-w-sm text-sm text-muted-foreground">{{ __('frep.emptyDesc') }}</p>
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-muted/40 text-left text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                @foreach ($report['columns'] as $columnLabel)
                                    <th class="px-3 py-2">{{ $columnLabel }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['rows'] as $row)
                                <tr class="border-t transition-colors hover:bg-muted/30">
                                    @foreach ($report['columns'] as $columnKey => $columnLabel)
                                        @php
                                            // Numeric detection on the pre-formatted cell (Money/grouped
                                            // digits pass, dates/labels keep the default left alignment).
                                            $cell = (string) ($row[$columnKey] ?? '');
                                            $isNum = is_numeric(preg_replace('/[^0-9.\-]/', '', $cell)) && trim($cell) !== '';
                                        @endphp
                                        <td class="whitespace-nowrap px-3 py-2 {{ $isNum ? 'text-right font-semibold tabular-nums num' : '' }}">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        @if (! empty($report['summary']))
                            {{-- Totals rows --}}
                            <tfoot>
                                @foreach ($report['summary'] as $summaryLabel => $summaryValue)
                                    <tr class="border-t bg-muted/20">
                                        <td colspan="{{ max(1, count($report['columns']) - 1) }}" class="px-3 py-2 text-sm font-semibold">{{ $summaryLabel }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-right text-sm font-bold tabular-nums num text-emerald-700">{{ $summaryValue }}</td>
                                    </tr>
                                @endforeach
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
