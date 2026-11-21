@extends('layouts.app')

@section('title', __('mat.issuesTitle'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — material issues / remises (GET /materials/issues;
         * Finance & Comptabilité spec v1.3 §5.5-§5.6).
         *
         * Variables (MaterialController@issues): $issues (paginated
         * MaterialIssue with order, garment, staff, lines — scoped to
         * the beneficiary for STAFF), $statuses
         * (Atelier::MATERIAL_ISSUE_STATUSES), $filters (status, mine),
         * $canIssue, $canValidate.
         *
         * Each row opens the issue detail (declarations, validation,
         * closure) — the whole row is clickable, the code is the
         * keyboard-accessible link.
         */

        // Issue status badge palette (OPEN amber / CLOSED zinc /
        // CANCELLED red).
        $statusPalette = [
            'OPEN' => 'bg-amber-50 text-amber-700 border-amber-200',
            'CLOSED' => 'bg-zinc-50 text-zinc-600 border-zinc-200',
            'CANCELLED' => 'bg-red-50 text-red-700 border-red-200',
        ];
    @endphp

    <div class="space-y-5">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('mat.issuesTitle') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('mat.issuesSubtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canValidate)
                    <a href="{{ route('materials.validation') }}"
                       class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                        {{ __('mat.validationTitle') }}
                    </a>
                @endif
                @if ($canIssue)
                    <a href="{{ route('materials.issues.create') }}"
                       class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        {{ __('mat.newIssue') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="rounded-lg border bg-card p-3 sm:p-4">
            <form method="GET" action="{{ route('materials.issues.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="issues-status" class="block text-xs font-medium text-muted-foreground">{{ __('mat.status') }}</label>
                    <select id="issues-status" name="status"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}" {{ ($filters['status'] ?? '') === $statusOption ? 'selected' : '' }}>{{ __('mat.st_'.$statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        {{ __('common.filter') }}
                    </button>
                </div>
            </form>
        </div>

        @if ($issues->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>
                    </div>
                    <p class="font-medium">{{ __('mat.noIssues') }}</p>
                </div>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto rounded-lg border bg-card md:block">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('mat.issueCode') }}</th>
                            <th class="px-4 py-3">{{ __('mat.issueDate') }}</th>
                            <th class="px-4 py-3">{{ __('mat.orderColumn') }}</th>
                            <th class="px-4 py-3">{{ __('mat.garment') }}</th>
                            <th class="px-4 py-3">{{ __('mat.beneficiary') }}</th>
                            <th class="px-4 py-3">{{ __('mat.status') }}</th>
                            <th class="px-4 py-3">{{ __('mat.lines') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.qtyIssued') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($issues as $issue)
                            @php
                                $lineSummary = $issue->lines->take(2)
                                    ->map(static fn ($line): string => $line->material_name.' '.($line->qty_issued + 0).' '.$line->unit)
                                    ->implode(', ');
                                $extraLines = max(0, $issue->lines->count() - 2);
                                $totalQty = $issue->lines->sum('qty_issued');
                            @endphp
                            <tr class="cursor-pointer border-t transition-colors hover:bg-muted/30"
                                onclick="window.location.href='{{ route('materials.issues.show', $issue->id) }}'">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">
                                    <a href="{{ route('materials.issues.show', $issue->id) }}"
                                       aria-label="{{ $issue->code }}"
                                       class="rounded-sm underline-offset-2 transition-colors hover:text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        {{ $issue->code }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $issue->issue_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $issue->order?->order_number ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $issue->garment?->code ?? '—' }}</td>
                                <td class="max-w-36 px-4 py-3"><span class="block truncate">{{ $issue->staff_name }}</span></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$issue->status] ?? $statusPalette['OPEN'] }}">{{ __('mat.st_'.$issue->status) }}</span>
                                </td>
                                <td class="max-w-56 px-4 py-3">
                                    <p class="truncate" title="{{ $lineSummary }}">{{ $lineSummary !== '' ? $lineSummary : '—' }}</p>
                                    @if ($extraLines > 0)
                                        <p class="text-xs text-muted-foreground">+{{ $extraLines }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ $totalQty + 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($issues->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $issues->total() }} · {{ $issues->currentPage() }} / {{ $issues->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($issues->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $issues->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($issues->hasMorePages())
                                <a href="{{ $issues->appends(request()->query())->nextPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            @else
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Mobile card list --}}
            <div class="space-y-3 md:hidden">
                @foreach ($issues as $issue)
                    @php
                        $lineSummary = $issue->lines->take(2)
                            ->map(static fn ($line): string => $line->material_name.' '.($line->qty_issued + 0).' '.$line->unit)
                            ->implode(', ');
                        $extraLines = max(0, $issue->lines->count() - 2);
                        $totalQty = $issue->lines->sum('qty_issued');
                    @endphp
                    <a href="{{ route('materials.issues.show', $issue->id) }}"
                       class="block rounded-xl border bg-card p-4 card-lift transition-colors hover:bg-muted/30">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $issue->code }}</p>
                                <p class="mt-0.5 text-sm text-muted-foreground">{{ $issue->order?->order_number ?? '—' }}@if ($issue->garment?->code) · {{ $issue->garment->code }}@endif</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$issue->status] ?? $statusPalette['OPEN'] }}">{{ __('mat.st_'.$issue->status) }}</span>
                        </div>
                        <div class="mt-2 text-sm">
                            <p class="font-medium">{{ $issue->staff_name }}</p>
                            <p class="text-xs text-muted-foreground">{{ $issue->issue_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3 border-t pt-3">
                            <div class="min-w-0 text-xs text-muted-foreground">
                                <p class="truncate">{{ $lineSummary !== '' ? $lineSummary : '—' }}@if ($extraLines > 0) +{{ $extraLines }}@endif</p>
                                <p class="mt-0.5">{{ __('mat.qtyIssued') }}: <span class="font-medium text-foreground tabular-nums">{{ $totalQty + 0 }}</span></p>
                            </div>
                            <span class="text-sm font-semibold text-primary">{{ __('mat.view') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
