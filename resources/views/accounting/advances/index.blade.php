@extends('layouts.app')

@section('title', __('adv.title'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — staff advances (GET /advances, perm
         * accounting.advances; enhancement spec §9).
         *
         * Variables (AdvanceController@index): $advances (with staff and
         * recoveries.payrollItem.payroll), $staffList, $filters (staff,
         * status), $statuses, $methods, $canApprove.
         *
         * Workflow actions per status: PENDING_APPROVAL → approve / reject
         * (reason modal) / cancel; APPROVED → disburse / cancel. The
         * outstanding column is always derived (amount − amount_recovered)
         * and each row expands into its recovery history trail.
         */

        $filtersActive = ($filters['staff'] ?? '') !== '' || ($filters['status'] ?? '') !== '';

        // Advance status badge palette (PENDING_APPROVAL amber / APPROVED
        // violet / DISBURSED purple / PARTIALLY_RECOVERED orange /
        // FULLY_RECOVERED emerald / CANCELLED zinc).
        $statusPalette = [
            'PENDING_APPROVAL' => 'bg-amber-50 text-amber-700 border-amber-200',
            'APPROVED' => 'bg-violet-50 text-violet-700 border-violet-200',
            'DISBURSED' => 'bg-purple-50 text-purple-700 border-purple-200',
            'PARTIALLY_RECOVERED' => 'bg-orange-50 text-orange-700 border-orange-200',
            'FULLY_RECOVERED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CANCELLED' => 'bg-zinc-50 text-zinc-500 border-zinc-200',
        ];

        $cancellable = ['PENDING_APPROVAL', 'APPROVED'];
    @endphp

    <div class="space-y-5"
         x-data="{ createOpen: {{ $errors->any() && old('formId') === 'advance-create' ? 'true' : 'false' }} }">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('adv.title') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('adv.subtitle') }}</p>
            </div>
            <button type="button" @click="createOpen = true"
                    class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                {{ __('adv.record') }}
            </button>
        </div>

        {{-- Filter bar --}}
        <div class="rounded-lg border bg-card p-3 sm:p-4">
            <form method="GET" action="{{ route('advances.index') }}" class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="adv-staff" class="block text-xs font-medium text-muted-foreground">{{ __('adv.filterStaff') }}</label>
                    <select id="adv-staff" name="staff"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('adv.allStaff') }}</option>
                        @foreach ($staffList as $staffMember)
                            <option value="{{ $staffMember->id }}" {{ ($filters['staff'] ?? '') === $staffMember->id ? 'selected' : '' }}>{{ $staffMember->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="adv-status" class="block text-xs font-medium text-muted-foreground">{{ __('adv.filterStatus') }}</label>
                    <select id="adv-status" name="status"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('adv.allStatuses') }}</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}" {{ ($filters['status'] ?? '') === $statusOption ? 'selected' : '' }}>{{ __('labels.advStatus.'.$statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2 sm:col-span-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        {{ __('common.filter') }}
                    </button>
                    @if ($filtersActive)
                        <a href="{{ route('advances.index') }}"
                           class="inline-flex items-center rounded-md px-4 py-2.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-foreground">
                            {{ __('adv.clear') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if ($advances->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="M2 7v10"/><path d="M2 12h20"/><path d="M22 7v10"/><path d="M2 7h20"/></svg>
                    </div>
                    <p class="font-medium">{{ $filtersActive ? __('adv.emptyFiltersTitle') : __('adv.emptyTitle') }}</p>
                    <p class="max-w-sm text-sm text-muted-foreground">{{ $filtersActive ? __('adv.emptyFiltersDesc') : __('adv.emptyDesc') }}</p>
                </div>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto rounded-lg border bg-card md:block">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('adv.colReference') }}</th>
                            <th class="px-4 py-3">{{ __('adv.colStaff') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('adv.colAmount') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('adv.colRecovered') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('adv.colOutstanding') }}</th>
                            <th class="px-4 py-3">{{ __('adv.colIssued') }}</th>
                            <th class="px-4 py-3">{{ __('adv.colStatus') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('adv.colActions') }}</th>
                        </tr>
                    </thead>
                    @foreach ($advances as $advance)
                        @php
                            $outstanding = max(0.0, (float) $advance->amount - (float) $advance->amount_recovered);
                        @endphp
                        <tbody x-data="{ history: false }">
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $advance->reference }}
                                        <button type="button" @click="history = !history"
                                                aria-label="{{ __('adv.recoveryHistory') }} — {{ $advance->reference }}"
                                                class="inline-flex size-6 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 transition-transform" :class="history ? 'rotate-0' : '-rotate-90'" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $advance->staff_name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($advance->amount) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \App\Support\Money::format($advance->amount_recovered) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $outstanding > 0.004 ? 'text-amber-700' : 'text-emerald-700' }}">{{ \App\Support\Money::format($outstanding) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $advance->date_issued->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$advance->status] ?? $statusPalette['CANCELLED'] }} {{ $advance->status === 'CANCELLED' ? 'line-through' : '' }}">{{ __('labels.advStatus.'.$advance->status) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($canApprove && $advance->status === 'PENDING_APPROVAL')
                                            <form method="POST" action="{{ route('advances.review', $advance->id) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="approve" />
                                                <button type="submit" onclick="return confirm('{{ __('adv.approveConfirm') }}')"
                                                        aria-label="{{ __('adv.reviewApprove') }} — {{ $advance->reference }}"
                                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-emerald-50 hover:text-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                                                </button>
                                            </form>
                                            <button type="button" @click="$dispatch('adv-reject-{{ $advance->id }}')"
                                                    aria-label="{{ __('adv.reviewReject') }} — {{ $advance->reference }}"
                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                            </button>
                                        @endif
                                        @if ($canApprove && $advance->status === 'APPROVED')
                                            <form method="POST" action="{{ route('advances.disburse', $advance->id) }}">
                                                @csrf
                                                <button type="submit" onclick="return confirm('{{ __('adv.disburseConfirm') }}')"
                                                        aria-label="{{ __('adv.disburse') }} — {{ $advance->reference }}"
                                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M2 7v10"/><path d="M2 12h20"/><path d="M22 7v10"/><path d="M2 7h20"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                        @if ($canApprove && in_array($advance->status, $cancellable, true))
                                            <form method="POST" action="{{ route('advances.cancel', $advance->id) }}">
                                                @csrf
                                                <button type="submit" onclick="return confirm('{{ __('adv.cancelConfirm') }}')"
                                                        aria-label="{{ __('adv.cancelAdvance') }} — {{ $advance->reference }}"
                                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="history" style="display: none;" x-cloak>
                                <td colspan="8" class="border-t bg-muted/20 px-4 py-3">
                                    <p class="text-xs font-semibold text-muted-foreground">{{ __('adv.recoveryHistory') }}</p>
                                    @if ($advance->recoveries->isNotEmpty())
                                        <ul class="mt-1.5 space-y-1">
                                            @foreach ($advance->recoveries as $recovery)
                                                <li class="text-xs text-muted-foreground">
                                                    {{ \App\Support\Trans::fill('adv.recoveredThrough', [
                                                        'payroll' => $recovery->payrollItem?->payroll?->label ?? '—',
                                                        'date' => $recovery->recovered_at?->locale(app()->getLocale())->translatedFormat('j M Y') ?? '—',
                                                    ]) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="mt-1.5 text-xs text-muted-foreground">{{ __('adv.noRecoveries') }}</p>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>

                @if ($advances->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $advances->total() }} · {{ $advances->currentPage() }} / {{ $advances->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($advances->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $advances->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($advances->hasMorePages())
                                <a href="{{ $advances->appends(request()->query())->nextPageUrl() }}"
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
                @foreach ($advances as $advance)
                    @php
                        $outstanding = max(0.0, (float) $advance->amount - (float) $advance->amount_recovered);
                    @endphp
                    <div class="rounded-xl border bg-card p-4 card-lift" x-data="{ history: false }">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold">{{ $advance->reference }}</p>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$advance->status] ?? $statusPalette['CANCELLED'] }} {{ $advance->status === 'CANCELLED' ? 'line-through' : '' }}">{{ __('labels.advStatus.'.$advance->status) }}</span>
                        </div>
                        <div class="mt-1.5 text-sm">
                            <p class="font-medium">{{ $advance->staff_name }}</p>
                            <p class="text-muted-foreground">{{ $advance->reason }}</p>
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div class="text-xs text-muted-foreground">
                                <p>{{ $advance->date_issued->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
                                <p class="mt-0.5">{{ __('adv.colRecovered') }}: {{ \App\Support\Money::format($advance->amount_recovered) }}</p>
                                <p class="{{ $outstanding > 0.004 ? 'font-semibold text-amber-700' : 'font-semibold text-emerald-700' }}">{{ __('adv.colOutstanding') }}: {{ \App\Support\Money::format($outstanding) }}</p>
                            </div>
                            <p class="text-lg font-bold tabular-nums">{{ \App\Support\Money::format($advance->amount) }}</p>
                        </div>
                        <div class="mt-3 border-t pt-3">
                            <button type="button" @click="history = !history"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 transition-transform" :class="history ? 'rotate-0' : '-rotate-90'" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                {{ __('adv.recoveryHistory') }}
                            </button>
                            <div x-show="history" style="display: none;" x-cloak x-transition.opacity.duration.200ms class="mt-2">
                                @if ($advance->recoveries->isNotEmpty())
                                    <ul class="space-y-1">
                                        @foreach ($advance->recoveries as $recovery)
                                            <li class="text-xs text-muted-foreground">
                                                {{ \App\Support\Trans::fill('adv.recoveredThrough', [
                                                    'payroll' => $recovery->payrollItem?->payroll?->label ?? '—',
                                                    'date' => $recovery->recovered_at?->locale(app()->getLocale())->translatedFormat('j M Y') ?? '—',
                                                ]) }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-xs text-muted-foreground">{{ __('adv.noRecoveries') }}</p>
                                @endif
                            </div>
                        </div>
                        @if ($canApprove && (in_array($advance->status, $cancellable, true)))
                            <div class="mt-3 flex items-center justify-end gap-1 border-t pt-3">
                                @if ($advance->status === 'PENDING_APPROVAL')
                                    <form method="POST" action="{{ route('advances.review', $advance->id) }}">
                                        @csrf
                                        <input type="hidden" name="decision" value="approve" />
                                        <button type="submit" onclick="return confirm('{{ __('adv.approveConfirm') }}')"
                                                aria-label="{{ __('adv.reviewApprove') }} — {{ $advance->reference }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-emerald-50 hover:text-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                                        </button>
                                    </form>
                                    <button type="button" @click="$dispatch('adv-reject-{{ $advance->id }}')"
                                            aria-label="{{ __('adv.reviewReject') }} — {{ $advance->reference }}"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                @endif
                                @if ($advance->status === 'APPROVED')
                                    <form method="POST" action="{{ route('advances.disburse', $advance->id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('{{ __('adv.disburseConfirm') }}')"
                                                aria-label="{{ __('adv.disburse') }} — {{ $advance->reference }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M2 7v10"/><path d="M2 12h20"/><path d="M22 7v10"/><path d="M2 7h20"/></svg>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('advances.cancel', $advance->id) }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('{{ __('adv.cancelConfirm') }}')"
                                            aria-label="{{ __('adv.cancelAdvance') }} — {{ $advance->reference }}"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Reject modals (reason required) --}}
            @foreach ($advances as $advance)
                @if ($canApprove && $advance->status === 'PENDING_APPROVAL')
                    <div x-data="{ open: false }" @adv-reject-{{ $advance->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('adv.reviewReject') }} — {{ $advance->reference }}"
                             @keydown.escape.window="open = false">
                            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false" aria-hidden="true"
                                 x-transition.opacity.duration.200ms></div>
                            <div class="relative max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-card shadow-xl sm:max-w-md sm:rounded-lg"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                                <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                                    <div class="min-w-0">
                                        <h2 class="text-base font-semibold">{{ __('adv.reviewReject') }}</h2>
                                        <p class="mt-0.5 text-sm text-muted-foreground">{{ $advance->reference }} · {{ $advance->staff_name }}</p>
                                    </div>
                                    <button type="button" @click="open = false"
                                            aria-label="{{ __('common.close') }}"
                                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('advances.review', $advance->id) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="reject" />
                                    <div class="px-5 py-4">
                                        <label for="adv-reject-reason-{{ $advance->id }}" class="mb-1.5 block text-sm font-medium">{{ __('adv.rejectReason') }}</label>
                                        <textarea id="adv-reject-reason-{{ $advance->id }}" name="reason" rows="3" required
                                                  class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('reason') }}</textarea>
                                        @error('reason')
                                            <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                        <button type="button" @click="open = false"
                                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                                            {{ __('common.cancel') }}
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 rounded-md bg-destructive px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-destructive/90 btn-press">
                                            {{ __('adv.reviewReject') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif

        {{-- Calculation note --}}
        <div class="rounded-lg border bg-muted/40 p-4">
            <p class="text-sm text-muted-foreground">{{ __('adv.note') }}</p>
        </div>

        {{-- Record advance modal --}}
        <div x-show="createOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('adv.record') }}"
             @keydown.escape.window="createOpen = false">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="createOpen = false" aria-hidden="true"
                 x-transition.opacity.duration.200ms></div>
            <div class="relative max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-card shadow-xl sm:max-w-lg sm:rounded-lg"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold">{{ __('adv.record') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('adv.subtitle') }}</p>
                    </div>
                    <button type="button" @click="createOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('advances.store') }}">
                    @csrf
                    <input type="hidden" name="formId" value="advance-create" />
                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                        <div>
                            <label for="adv-staff-select" class="mb-1.5 block text-sm font-medium">{{ __('adv.fieldStaff') }}</label>
                            <select id="adv-staff-select" name="staffId" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">{{ __('adv.selectStaff') }}</option>
                                @foreach ($staffList as $staffMember)
                                    <option value="{{ $staffMember->id }}" {{ old('staffId') === $staffMember->id ? 'selected' : '' }}>{{ $staffMember->name }}</option>
                                @endforeach
                            </select>
                            @error('staffId')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="adv-amount" class="mb-1.5 block text-sm font-medium">{{ __('adv.fieldAmount') }}</label>
                            <input type="text" inputmode="decimal" id="adv-amount" name="amount" value="{{ old('amount') }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('amount')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="adv-date" class="mb-1.5 block text-sm font-medium">{{ __('adv.fieldDateIssued') }}</label>
                            <input type="date" id="adv-date" name="dateIssued" value="{{ old('dateIssued', now()->toDateString()) }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('dateIssued')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="adv-method" class="mb-1.5 block text-sm font-medium">{{ __('adv.fieldMethod') }}</label>
                            <select id="adv-method" name="method"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($methods as $methodOption)
                                    <option value="{{ $methodOption }}" {{ old('method', 'CASH') === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="adv-reason" class="mb-1.5 block text-sm font-medium">{{ __('adv.fieldReason') }}</label>
                            <textarea id="adv-reason" name="reason" rows="2" maxlength="500" required
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('reason') }}</textarea>
                            @error('reason')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="adv-repayment" class="mb-1.5 block text-sm font-medium">{{ __('adv.fieldRepaymentPlan') }}</label>
                            <input type="text" id="adv-repayment" name="repaymentPlan" value="{{ old('repaymentPlan') }}" maxlength="120" placeholder="{{ __('adv.repaymentPlaceholder') }}"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="createOpen = false"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('adv.record') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
