@extends('layouts.app')

@section('title', __('mat.validationTitle'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — declarations validation queue
         * (GET /materials/validation, perm materials.validate; Finance &
         * Comptabilité spec v1.3 §5.8).
         *
         * Variables (MaterialController@validationQueue): $pending
         * (paginated PENDING MaterialDeclaration with line,
         * line.issue, line.issue.order, declaredBy).
         *
         * Each row shows the handed-out quantity against the declared
         * one; "remaining after" going negative is an anomaly (amber).
         * Approve feeds the returns back to stock, Reject keeps the row
         * with its motif — never deleted (§5.8).
         */
    @endphp

    <div class="space-y-5">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('mat.validationTitle') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('mat.validationSubtitle') }}</p>
            </div>
            <a href="{{ route('materials.issues.index') }}"
               class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                {{ __('mat.issuesTitle') }}
            </a>
        </div>

        @if ($pending->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                    </div>
                    <p class="font-medium">{{ __('mat.declarations') }}</p>
                    <p class="max-w-sm text-sm text-muted-foreground">{{ __('mat.validationSubtitle') }}</p>
                </div>
            </div>
        @else
            {{-- Queue --}}
            <div class="overflow-x-auto rounded-lg border bg-card">
                <table class="w-full min-w-[1100px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('mat.issueDate') }}</th>
                            <th class="px-4 py-3">{{ __('mat.issueCode') }}</th>
                            <th class="px-4 py-3">{{ __('mat.orderColumn') }}</th>
                            <th class="px-4 py-3">{{ __('mat.materialName') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.qtyIssued') }}</th>
                            <th class="px-4 py-3">{{ __('pay.colRecordedBy') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.qtyConsumed') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.qtyReturned') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.toReconcile') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pending as $declaration)
                            @php
                                $line = $declaration->line;
                                $remaining = (float) $line->qty_issued - $line->declaredTotal();
                            @endphp
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $declaration->declared_at->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium">
                                    @if ($line?->issue)
                                        <a href="{{ route('materials.issues.show', $line->issue->id) }}"
                                           class="rounded-sm underline-offset-2 transition-colors hover:text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            {{ $line->issue->code }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $line?->issue?->order?->order_number ?? '—' }}</td>
                                <td class="max-w-44 px-4 py-3"><span class="block truncate">{{ $line?->material_name ?? '—' }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ ($line?->qty_issued ?? 0) + 0 }} {{ $line?->unit }}</td>
                                <td class="max-w-36 px-4 py-3"><span class="block truncate">{{ $declaration->declared_by_name ?? $declaration->declaredBy?->name ?? '—' }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $declaration->qty_consumed + 0 }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $declaration->qty_returned + 0 }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $remaining < 0 ? 'text-amber-700' : '' }}">{{ $remaining + 0 }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('materials.declarations.review', $declaration->id) }}"
                                          class="flex flex-wrap items-center justify-end gap-1.5">
                                        @csrf
                                        <input type="text" name="review_note" placeholder="{{ __('mat.reviewNote') }}" maxlength="500"
                                               class="w-36 rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                        <button type="submit" name="decision" value="APPROVED"
                                                class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                            {{ __('mat.approve') }}
                                        </button>
                                        <button type="submit" name="decision" value="REJECTED"
                                                class="inline-flex items-center rounded-md bg-destructive px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-destructive/90 btn-press">
                                            {{ __('mat.reject') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($pending->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $pending->total() }} · {{ $pending->currentPage() }} / {{ $pending->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($pending->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $pending->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($pending->hasMorePages())
                                <a href="{{ $pending->appends(request()->query())->nextPageUrl() }}"
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
        @endif
    </div>
@endsection
