@extends('layouts.app')

@section('title', __('shell.view.incentives'))

@section('content')
    @php
        $canPay = \App\Services\Permissions::allows($me, 'incentives.payment');
        $statusBadge = [
            'PENDING' => 'bg-amber-50 text-amber-800 border-amber-300',
            'APPROVED' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            'REJECTED' => 'bg-red-50 text-red-800 border-red-300',
        ];
    @endphp
                <div class="space-y-4"
         x-data="{
             tab: '{{ request()->query('tab') === 'records' ? 'records' : 'schemes' }}',
             schemeModal: false,
             editingSchemeId: null,
             genModal: false,
             reviewId: null,
             paymentId: null
         }">
        <p class="max-w-3xl text-sm text-muted-foreground">{{ __('inc.subtitle') }}</p>


        <div class="inline-flex rounded-lg border bg-muted/50 p-1" role="tablist" aria-label="{{ __('inc.title') }}">
            <button type="button" role="tab" x-on:click="tab = 'schemes'" :aria-selected="tab === 'schemes'"
                    :class="tab === 'schemes' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/></svg>
                {{ __('inc.tab.schemes') }}
            </button>
            <button type="button" role="tab" x-on:click="tab = 'records'" :aria-selected="tab === 'records'"
                    :class="tab === 'records' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                {{ __('inc.tab.evaluations') }}
            </button>
        </div>


        <div x-show="tab === 'schemes'">
            <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="inc-schemes-heading">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3 sm:px-6">
                    <h2 id="inc-schemes-heading" class="font-display text-base font-semibold">{{ __('inc.schemes.title') }}</h2>
                    @if ($canManageSchemes)
                        <button type="button" x-on:click="schemeModal = true"
                                class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            {{ __('inc.schemes.new') }}
                        </button>
                    @endif
                </div>

                @if (count($schemes) === 0)
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                        <div class="rounded-full bg-muted p-3">
                            <svg class="size-6 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/></svg>
                        </div>
                        <p class="font-medium">{{ __('inc.schemes.emptyTitle') }}</p>
                        <p class="max-w-sm text-sm text-muted-foreground">{{ $canManageSchemes ? __('inc.schemes.emptyDescManage') : __('inc.schemes.emptyDescView') }}</p>
                        @if ($canManageSchemes)
                            <div class="mt-2">
                                <button type="button" x-on:click="schemeModal = true"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                    {{ __('inc.schemes.new') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[960px] text-sm">
                            <thead class="bg-muted/50 text-xs font-medium text-muted-foreground">
                                <tr class="border-b">
                                    <th scope="col" class="px-4 py-2.5 text-left">{{ __('inc.schemes.col.name') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-left">{{ __('inc.schemes.col.periodType') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-center">{{ __('inc.schemes.col.minScore') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">{{ __('inc.schemes.col.amount') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-center">{{ __('inc.schemes.col.records') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-center">{{ __('inc.schemes.col.active') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-left">{{ __('inc.schemes.col.createdBy') }}</th>
                                    @if ($canManageSchemes)
                                        <th scope="col" class="px-3 py-2.5 text-right">{{ __('common.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($schemes as $scheme)
                                    <tr class="border-b transition-colors last:border-b-0 hover:bg-muted/30 {{ $scheme->is_active ? '' : 'opacity-60' }}">
                                        <td class="px-4 py-3">
                                            <p class="font-medium">{{ $scheme->name }}</p>
                                            @if ($scheme->description)
                                                <p class="max-w-md truncate text-xs text-muted-foreground">{{ $scheme->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center whitespace-nowrap rounded-full bg-secondary px-2 py-0.5 text-[11px] font-semibold text-secondary-foreground">
                                                {{ __('labels.incentivePeriod.'.$scheme->period_type) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-center tabular-nums">
                                            @if ($scheme->min_score !== null)
                                                ≥ {{ $scheme->min_score }}
                                            @else
                                                <span class="text-muted-foreground">{{ __('inc.schemes.anyScore') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right font-medium tabular-nums">
                                            @if ($scheme->amount !== null)
                                                {{ number_format($scheme->amount) }} FCFA
                                            @else
                                                <span class="text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center tabular-nums">{{ $scheme->recordsCount }}</td>
                                        <td class="px-3 py-3 text-center">
                                            @if ($canManageSchemes)
                                                <form method="POST" action="{{ route('incentives.schemes.update', $scheme->id) }}" class="inline-flex">
                                                    @csrf
                                                    @if ($scheme->is_active)
                                                        <input type="hidden" name="isActive" value="0">
                                                        <button type="submit" role="switch" aria-checked="true"
                                                                aria-label="{{ \App\Support\Trans::fill('inc.schemes.toggleAria', ['name' => $scheme->name]) }}"
                                                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-ring bg-emerald-600">
                                                            <span class="inline-block size-4 rounded-full bg-white shadow transition-transform translate-x-[18px]"></span>
                                                        </button>
                                                    @else
                                                        <input type="hidden" name="isActive" value="1">
                                                        <button type="submit" role="switch" aria-checked="false"
                                                                aria-label="{{ \App\Support\Trans::fill('inc.schemes.toggleAria', ['name' => $scheme->name]) }}"
                                                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-ring bg-stone-300">
                                                            <span class="inline-block size-4 rounded-full bg-white shadow transition-transform translate-x-0.5"></span>
                                                        </button>
                                                    @endif
                                                </form>
                                            @else
                                                <span class="inline-flex items-center whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $scheme->is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-stone-100 text-stone-500 border-stone-300' }}">
                                                    {{ $scheme->is_active ? __('inc.schemes.active') : __('inc.schemes.inactive') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-muted-foreground">{{ $scheme->created_by_name ?? '—' }}</td>
                                        @if ($canManageSchemes)
                                            <td class="px-3 py-3 text-right">
                                                <button type="button" x-on:click="editingSchemeId = '{{ $scheme->id }}'"
                                                        aria-label="{{ \App\Support\Trans::fill('inc.schemes.editAria', ['name' => $scheme->name]) }}"
                                                        class="inline-flex h-8 items-center gap-1 rounded-md px-2.5 text-sm font-medium text-muted-foreground hover:bg-muted hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                                    {{ __('common.edit') }}
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>


        <div x-show="tab === 'records'">
            <section class="rounded-xl border bg-card shadow-sm" aria-labelledby="inc-records-heading">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3 sm:px-6">
                    <h2 id="inc-records-heading" class="font-display text-base font-semibold">{{ __('inc.records.title') }}</h2>
                    <div class="flex items-center gap-3">
                        <p class="hidden text-xs text-muted-foreground sm:block">
                            {{ \App\Support\Trans::fill('inc.records.total', ['count' => $records->total()]) }}
                        </p>
                        @if ($canPrepare)
                            <button type="button" x-on:click="genModal = true"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14a3.474 3.474 0 0 0-.6 2.8 3.474 3.474 0 0 0 2.8-.6A2 2 0 0 0 9.937 15.5z"/><path d="M12 12a6 6 0 0 1 6-6"/><path d="M14 10a4 4 0 0 1 4-4"/><path d="M20.976 17.217A9 9 0 0 0 21 16a9 9 0 0 0-9-9 9 9 0 0 0-1.217.024"/><path d="M3 21l3.5-3.5"/></svg>
                                {{ __('inc.gen.title') }}
                            </button>
                        @endif
                    </div>
                </div>


                <form method="GET" action="{{ route('incentives.index') }}" class="flex flex-wrap items-end gap-3 border-b px-4 py-4 sm:px-6">
                    <input type="hidden" name="tab" value="records">
                    <div class="w-40 space-y-1.5">
                        <label for="filter-status" class="text-sm font-medium">{{ __('inc.records.col.status') }}</label>
                        <select id="filter-status" name="status"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            <option value="">{{ __('inc.records.allStatuses') }}</option>
                            <option value="PENDING" {{ $filters['status'] === 'PENDING' ? 'selected' : '' }}>{{ __('labels.incStatus.PENDING') }}</option>
                            <option value="APPROVED" {{ $filters['status'] === 'APPROVED' ? 'selected' : '' }}>{{ __('labels.incStatus.APPROVED') }}</option>
                            <option value="REJECTED" {{ $filters['status'] === 'REJECTED' ? 'selected' : '' }}>{{ __('labels.incStatus.REJECTED') }}</option>
                        </select>
                    </div>
                    <div class="w-44 space-y-1.5">
                        <label for="filter-period" class="text-sm font-medium">{{ __('inc.records.col.period') }}</label>
                        <input id="filter-period" name="period" value="{{ $filters['period'] }}" placeholder="2026-11"
                               class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>
                    <div class="w-52 space-y-1.5">
                        <label for="filter-staff" class="text-sm font-medium">{{ __('inc.records.col.staff') }}</label>
                        <select id="filter-staff" name="staff"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            <option value="">{{ __('inc.records.allStaff') }}</option>
                            @foreach ($staffList as $staff)
                                <option value="{{ $staff->id }}" {{ $filters['staff'] === $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="inline-flex h-9 items-center rounded-md border bg-background px-4 text-sm font-medium hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                        {{ __('inc.records.filter') }}
                    </button>
                </form>

                @if (count($records) === 0)
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                        <div class="rounded-full bg-muted p-3">
                            <svg class="size-6 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                        </div>
                        <p class="font-medium">{{ __('inc.records.emptyTitle') }}</p>
                        <p class="max-w-sm text-sm text-muted-foreground">{{ __('inc.records.emptyDesc') }}</p>
                    </div>
                @else
                    <div class="max-h-[560px] overflow-auto">
                        <table class="w-full min-w-[1250px] text-sm">
                            <thead class="sticky top-0 bg-muted text-xs font-medium text-muted-foreground">
                                <tr class="border-b">
                                    <th scope="col" class="px-4 py-2.5 text-left">{{ __('inc.records.col.staff') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-left">{{ __('inc.records.col.scheme') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-left">{{ __('inc.records.col.period') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-center">{{ __('inc.records.col.score') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-center">{{ __('inc.records.col.eligible') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">{{ __('inc.records.col.proposed') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-left">{{ __('inc.records.col.status') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">{{ __('inc.records.col.approved') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-left">{{ __('inc.records.col.approvedBy') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-left">{{ __('inc.records.col.payment') }}</th>
                                    <th scope="col" class="px-3 py-2.5 text-left">{{ __('inc.records.col.comments') }}</th>
                                    @if ($canApprove || $canPay)
                                        <th scope="col" class="px-3 py-2.5 text-right">{{ __('common.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($records as $record)
                                    <tr class="border-b transition-colors last:border-b-0 hover:bg-muted/30">
                                        <td class="px-4 py-3 font-medium">{{ $record->user?->name ?? $record->staff_name }}</td>
                                        <td class="px-3 py-3 text-muted-foreground">{{ $record->scheme?->name ?? $record->scheme_name }}</td>
                                        <td class="px-3 py-3 tabular-nums">{{ $record->period }}</td>
                                        <td class="px-3 py-3 text-center font-semibold tabular-nums">{{ $record->performance_score }}</td>
                                        <td class="px-3 py-3 text-center">
                                            @if ($record->eligible)
                                                <span class="mx-auto inline-flex size-4 text-emerald-600" role="img" aria-label="{{ __('inc.records.col.eligible') }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                                </span>
                                            @else
                                                <span class="mx-auto inline-flex size-4 text-stone-400" role="img" aria-label="{{ __('inc.records.notEligible') }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums">{{ number_format($record->proposed_amount) }} FCFA</td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $statusBadge[$record->status] ?? '' }}">
                                                {{ __('labels.incStatus.'.$record->status) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums">
                                            {{ $record->approved_amount !== null ? number_format($record->approved_amount) . ' FCFA' : '—' }}
                                        </td>
                                        <td class="px-3 py-3">
                                            @if ($record->approved_by_name)
                                                <p class="text-muted-foreground">{{ $record->approvedBy?->name ?? $record->approved_by_name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ $record->updated_at->locale(app()->getLocale())->translatedFormat('M j, Y') }}</p>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-col gap-0.5">
                                                @if ($record->payment_status === 'PAID')
                                                    <span class="inline-flex items-center whitespace-nowrap rounded-full border border-emerald-300 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                                                        {{ __('labels.paymentStatus.PAID') }}
                                                    </span>
                                                    @if ($record->payment_date)
                                                        <span class="whitespace-nowrap text-[11px] text-muted-foreground">{{ $record->payment_date->locale(app()->getLocale())->translatedFormat('M j, Y') }}</span>
                                                    @endif
                                                @else
                                                    <span class="inline-flex items-center whitespace-nowrap rounded-full border border-stone-300 bg-stone-100 px-2 py-0.5 text-[11px] font-semibold text-stone-600">
                                                        {{ __('labels.paymentStatus.UNPAID') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="max-w-[220px] px-3 py-3">
                                            @if ($record->approval_comments)
                                                <p class="truncate text-xs italic text-muted-foreground/70" title="{{ $record->approval_comments }}">{{ $record->approval_comments }}</p>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        @if ($canApprove || $canPay)
                                            <td class="px-3 py-3 text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    @if ($canApprove && $record->status === 'PENDING')
                                                        <button type="button" x-on:click="reviewId = '{{ $record->id }}'"
                                                                class="inline-flex h-8 items-center gap-1 rounded-md border bg-background px-3 text-sm font-medium hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                                            {{ __('inc.records.review') }}
                                                        </button>
                                                    @endif
                                                    @if ($canPay && $record->status === 'APPROVED' && $record->payment_status === 'UNPAID')
                                                        <button type="button" x-on:click="paymentId = '{{ $record->id }}'"
                                                                class="inline-flex h-8 items-center gap-1 rounded-md border bg-background px-3 text-sm font-medium hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                                            {{ __('inc.records.markPaid') }}
                                                        </button>
                                                    @endif
                                                    @if (! ($canApprove && $record->status === 'PENDING') && ! ($canPay && $record->status === 'APPROVED' && $record->payment_status === 'UNPAID'))
                                                        <span class="text-sm text-muted-foreground">—</span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t px-4 py-3 sm:px-6">
                        <p class="text-xs text-muted-foreground">
                            {{ \App\Support\Trans::fill('inc.records.total', ['count' => $records->total()]) }}
                        </p>
                        @if ($records->hasPages())
                            <div class="flex items-center gap-2">
                                @if ($records->onFirstPage())
                                    <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-background text-xs font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                        {{ __('inc.records.previous') }}
                                    </span>
                                @else
                                    <a href="{{ $records->previousPageUrl() }}"
                                       class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-background text-xs font-medium hover:bg-accent hover:text-accent-foreground transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                        {{ __('inc.records.previous') }}
                                    </a>
                                @endif

                                <span class="text-xs text-muted-foreground tabular-nums whitespace-nowrap px-1">
                                    {{ \App\Support\Trans::fill('inc.records.pageOf', ['page' => $records->currentPage(), 'totalPages' => $records->lastPage()]) }}
                                </span>

                                @if ($records->hasMorePages())
                                    <a href="{{ $records->nextPageUrl() }}"
                                       class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-background text-xs font-medium hover:bg-accent hover:text-accent-foreground transition-colors">
                                        {{ __('inc.records.next') }}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-background text-xs font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                        {{ __('inc.records.next') }}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </section>
        </div>


        @if ($canManageSchemes)
                    <template x-if="schemeModal">
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ __('inc.schemeDialog.newTitle') }}">
                    <div class="absolute inset-0 bg-stone-900/50" x-on:click="schemeModal = false"></div>
                    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border bg-card shadow-lg" x-on:keydown.escape.window="schemeModal = false">
                        <form method="POST" action="{{ route('incentives.schemes.store') }}">
                            @csrf                            <div class="flex items-start justify-between gap-3 border-b px-5 py-4">
                                <div>
                                    <h3 class="font-display text-base font-semibold">{{ __('inc.schemeDialog.newTitle') }}</h3>
                                    <p class="mt-0.5 text-sm text-muted-foreground">{{ __('inc.schemeDialog.description') }}</p>
                                </div>
                                <button type="button" x-on:click="schemeModal = false" aria-label="{{ __('common.close') }}"
                                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>

                            <div class="space-y-4 px-5 py-4">
                                <div class="space-y-1.5">
                                    <label for="scheme-name" class="text-sm font-medium">{{ __('inc.schemeDialog.name') }} <span class="text-red-600">*</span></label>
                                    <input id="scheme-name" name="name" maxlength="120" required value="{{ old('name') }}"
                                           placeholder="{{ __('inc.schemeDialog.namePlaceholder') }}"
                                           class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                                    </div>

                                <div class="space-y-1.5">
                                    <label for="scheme-period-type" class="text-sm font-medium">{{ __('inc.schemeDialog.periodType') }}</label>
                                    <select id="scheme-period-type" name="periodType"
                                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                        @foreach (\App\Support\Atelier::INCENTIVE_PERIOD_TYPES as $type)
                                            <option value="{{ $type }}" {{ old('periodType') === $type ? 'selected' : '' }}>{{ __('labels.incentivePeriod.'.$type) }}</option>
                                        @endforeach
                                    </select>
                                                                    </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1.5">
                                        <label for="scheme-min-score" class="text-sm font-medium">{{ __('inc.schemeDialog.minScore') }} <span class="text-xs font-normal text-muted-foreground">({{ __('common.optional') }})</span></label>
                                        <input id="scheme-min-score" type="number" min="0" max="100" name="minScore" value="{{ old('minScore') }}"
                                               placeholder="{{ __('common.optional') }}"
                                               class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                                            </div>
                                    <div class="space-y-1.5">
                                        <label for="scheme-amount" class="text-sm font-medium">{{ __('inc.schemeDialog.amount') }} <span class="text-xs font-normal text-muted-foreground">({{ __('common.optional') }})</span></label>
                                        <input id="scheme-amount" type="number" min="0" step="any" name="amount" value="{{ old('amount') }}"
                                               placeholder="{{ __('common.optional') }}"
                                               class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                                            </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label for="scheme-description" class="text-sm font-medium">{{ __('inc.schemeDialog.descriptionLabel') }} <span class="text-xs font-normal text-muted-foreground">({{ __('common.optional') }})</span></label>
                                    <textarea id="scheme-description" name="description" rows="3"
                                              placeholder="{{ __('inc.schemeDialog.descPlaceholder') }}"
                                              class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('description') }}</textarea>
                                                                    </div>
                            </div>

                            <div class="flex justify-end gap-2 border-t px-5 py-4">
                                <button type="button" x-on:click="schemeModal = false"
                                        class="inline-flex h-9 items-center rounded-md border bg-background px-4 text-sm font-medium hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                    {{ __('common.cancel') }}
                                </button>
                                <button type="submit"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 0 2.8L10.4 19.2a2 2 0 0 1-1.4.6H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 .6-1.4L14.2 3.6A2 2 0 0 1 15.2 3z"/><path d="M16 8l-8 8"/></svg>
                                    {{ __('inc.schemeDialog.create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>

            @foreach ($schemes as $scheme)
                            <template x-if="editingSchemeId === '{{ $scheme->id }}'">
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ __('inc.schemeDialog.editTitle') }}">
                        <div class="absolute inset-0 bg-stone-900/50" x-on:click="editingSchemeId = null"></div>
                        <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border bg-card shadow-lg" x-on:keydown.escape.window="editingSchemeId = null">
                            <form method="POST" action="{{ route('incentives.schemes.update', $scheme->id) }}">
                                @csrf                                <div class="flex items-start justify-between gap-3 border-b px-5 py-4">
                                    <div>
                                        <h3 class="font-display text-base font-semibold">{{ __('inc.schemeDialog.editTitle') }}</h3>
                                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('inc.schemeDialog.description') }}</p>
                                    </div>
                                    <button type="button" x-on:click="editingSchemeId = null" aria-label="{{ __('common.close') }}"
                                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>

                                <div class="space-y-4 px-5 py-4">
                                    <div class="space-y-1.5">
                                        <label for="edit-scheme-name-{{ $loop->index }}" class="text-sm font-medium">{{ __('inc.schemeDialog.name') }} <span class="text-red-600">*</span></label>
                                        <input id="edit-scheme-name-{{ $loop->index }}" name="name" maxlength="120" required value="{{ old('name', $scheme->name) }}"
                                               placeholder="{{ __('inc.schemeDialog.namePlaceholder') }}"
                                               class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    </div>

                                    <div class="space-y-1.5">
                                        <label for="edit-scheme-period-{{ $loop->index }}" class="text-sm font-medium">{{ __('inc.schemeDialog.periodType') }}</label>
                                        <select id="edit-scheme-period-{{ $loop->index }}" name="periodType"
                                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                            @foreach (\App\Support\Atelier::INCENTIVE_PERIOD_TYPES as $type)
                                                <option value="{{ $type }}" {{ old('periodType', $scheme->period_type) === $type ? 'selected' : '' }}>{{ __('labels.incentivePeriod.'.$type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <label for="edit-scheme-min-{{ $loop->index }}" class="text-sm font-medium">{{ __('inc.schemeDialog.minScore') }} <span class="text-xs font-normal text-muted-foreground">({{ __('common.optional') }})</span></label>
                                            <input id="edit-scheme-min-{{ $loop->index }}" type="number" min="0" max="100" name="minScore"
                                                   value="{{ old('minScore', $scheme->min_score) }}"
                                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                        </div>
                                        <div class="space-y-1.5">
                                            <label for="edit-scheme-amount-{{ $loop->index }}" class="text-sm font-medium">{{ __('inc.schemeDialog.amount') }} <span class="text-xs font-normal text-muted-foreground">({{ __('common.optional') }})</span></label>
                                            <input id="edit-scheme-amount-{{ $loop->index }}" type="number" min="0" step="any" name="amount"
                                                   value="{{ old('amount', $scheme->amount) }}"
                                                   class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label for="edit-scheme-desc-{{ $loop->index }}" class="text-sm font-medium">{{ __('inc.schemeDialog.descriptionLabel') }} <span class="text-xs font-normal text-muted-foreground">({{ __('common.optional') }})</span></label>
                                        <textarea id="edit-scheme-desc-{{ $loop->index }}" name="description" rows="3"
                                                  placeholder="{{ __('inc.schemeDialog.descPlaceholder') }}"
                                                  class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('description', $scheme->description ?? '') }}</textarea>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2 border-t px-5 py-4">
                                    <button type="button" x-on:click="editingSchemeId = null"
                                            class="inline-flex h-9 items-center rounded-md border bg-background px-4 text-sm font-medium hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                        {{ __('common.cancel') }}
                                    </button>
                                    <button type="submit"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 0 2.8L10.4 19.2a2 2 0 0 1-1.4.6H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 .6-1.4L14.2 3.6A2 2 0 0 1 15.2 3z"/><path d="M16 8l-8 8"/></svg>
                                        {{ __('common.saveChanges') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            @endforeach
        @endif

        @if ($canPrepare)
                    <template x-if="genModal">
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ __('inc.gen.title') }}">
                    <div class="absolute inset-0 bg-stone-900/50" x-on:click="genModal = false"></div>
                    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border bg-card shadow-lg" x-on:keydown.escape.window="genModal = false">
                        <form method="POST" action="{{ route('incentives.generate') }}"
                              x-data="{ periodType: 'MONTHLY' }">
                            @csrf                            <div class="flex items-start justify-between gap-3 border-b px-5 py-4">
                                <div>
                                    <h3 class="font-display text-base font-semibold">{{ __('inc.gen.title') }}</h3>
                                </div>
                                <button type="button" x-on:click="genModal = false" aria-label="{{ __('common.close') }}"
                                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>

                            <div class="space-y-4 px-5 py-4">
                                <div class="space-y-1.5">
                                    <label for="gen-scheme" class="text-sm font-medium">{{ __('inc.gen.schemeLabel') }}</label>
                                    <select id="gen-scheme" name="schemeId"
                                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                        <option value="">{{ __('inc.gen.allSchemes') }}</option>
                                        @foreach ($schemes as $scheme)
                                            @if ($scheme->is_active)
                                                <option value="{{ $scheme->id }}" {{ old('schemeId') === $scheme->id ? 'selected' : '' }}>{{ $scheme->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-1.5">
                                    <label for="gen-period-type" class="text-sm font-medium">{{ __('inc.schemeDialog.periodType') }}</label>
                                    <select id="gen-period-type" name="periodType" x-model="periodType"
                                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                        @foreach (\App\Support\Atelier::INCENTIVE_PERIOD_TYPES as $type)
                                            <option value="{{ $type }}" {{ old('periodType', 'MONTHLY') === $type ? 'selected' : '' }}>{{ __('labels.incentivePeriod.'.$type) }}</option>
                                        @endforeach
                                    </select>
                                                                    </div>

                                <div class="space-y-1.5">
                                    <label for="gen-period-value" class="text-sm font-medium">{{ __('inc.gen.periodLabel') }} <span class="text-red-600">*</span></label>
                                    <input id="gen-period-value" name="periodValue" required value="{{ old('periodValue', now()->format('Y-m')) }}"
                                           :placeholder="periodType === 'QUARTERLY' ? '2026-Q4' : (periodType === 'ANNUAL' ? '2026' : '2026-11')"
                                           class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:w-44">
                                                                    </div>

                                <div class="space-y-1 text-xs text-muted-foreground">
                                    <p x-show="periodType === 'MONTHLY' || periodType === 'QUALITY' || periodType === 'ON_TIME' || periodType === 'CUSTOM'">{{ __('inc.gen.hintMonth') }}</p>
                                    <p x-show="periodType === 'QUARTERLY'">{{ __('inc.gen.hintQuarter') }}</p>
                                    <p x-show="periodType === 'ANNUAL'">{{ __('inc.gen.hintYear') }}</p>
                                    <p>{{ __('inc.gen.hintScores') }}</p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 border-t px-5 py-4">
                                <button type="button" x-on:click="genModal = false"
                                        class="inline-flex h-9 items-center rounded-md border bg-background px-4 text-sm font-medium hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                    {{ __('common.cancel') }}
                                </button>
                                <button type="submit"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14a3.474 3.474 0 0 0-.6 2.8 3.474 3.474 0 0 0 2.8-.6A2 2 0 0 0 9.937 15.5z"/><path d="M12 12a6 6 0 0 1 6-6"/><path d="M14 10a4 4 0 0 1 4-4"/><path d="M20.976 17.217A9 9 0 0 0 21 16a9 9 0 0 0-9-9 9 9 0 0 0-1.217.024"/><path d="M3 21l3.5-3.5"/></svg>
                                    {{ __('inc.gen.title') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        @endif

        @foreach ($records as $record)
            @if ($canApprove && $record->status === 'PENDING')
                            <template x-if="reviewId === '{{ $record->id }}'">
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ __('inc.review.title') }}">
                        <div class="absolute inset-0 bg-stone-900/50" x-on:click="reviewId = null"></div>
                        <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border bg-card shadow-lg" x-on:keydown.escape.window="reviewId = null">
                            <form method="POST" action="{{ route('incentives.review', $record->id) }}">
                                @csrf                                <div class="flex items-start justify-between gap-3 border-b px-5 py-4">
                                    <div>
                                        <h3 class="font-display text-base font-semibold">{{ __('inc.review.title') }}</h3>
                                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('inc.review.description') }}</p>
                                    </div>
                                    <button type="button" x-on:click="reviewId = null" aria-label="{{ __('common.close') }}"
                                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>

                                <div class="space-y-4 px-5 py-4">
                                    <div class="space-y-1 rounded-lg border bg-muted/40 p-3 text-sm">
                                        <p class="font-medium">{{ $record->user?->name ?? $record->staff_name }} — {{ $record->scheme?->name ?? $record->scheme_name }}</p>
                                        <p class="text-muted-foreground">{{ \App\Support\Trans::fill('inc.review.periodScore', ['period' => $record->period, 'score' => $record->performance_score]) }} · {{ $record->eligible ? __('inc.review.eligible') : __('inc.review.notEligible') }}</p>
                                        <p>{{ __('inc.review.proposedAmount') }} <span class="font-semibold">{{ number_format($record->proposed_amount) }} FCFA</span></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label for="review-amount-{{ $record->id }}" class="text-sm font-medium">{{ __('inc.review.approvedAmount') }}</label>
                                        <input id="review-amount-{{ $record->id }}" type="number" min="0" step="any" name="approvedAmount" value="{{ old('approvedAmount', $record->proposed_amount) }}"
                                               class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    </div>

                                    <div class="space-y-1.5">
                                        <label for="review-reason-{{ $record->id }}" class="text-sm font-medium">{{ __('inc.review.rejectionReason') }}</label>
                                        <textarea id="review-reason-{{ $record->id }}" name="rejectionReason" rows="3" maxlength="2000"
                                                  placeholder="{{ __('inc.review.rejectionPlaceholder') }}"
                                                  class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('rejectionReason') }}</textarea>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label for="review-comments-{{ $record->id }}" class="text-sm font-medium">{{ __('inc.review.comments') }}</label>
                                        <textarea id="review-comments-{{ $record->id }}" name="comments" rows="3" maxlength="2000"
                                                  placeholder="{{ __('inc.review.commentsPlaceholder') }}"
                                                  class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('comments') }}</textarea>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2 border-t px-5 py-4">
                                    <button type="button" x-on:click="reviewId = null"
                                            class="inline-flex h-9 items-center rounded-md border bg-background px-4 text-sm font-medium hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                        {{ __('common.cancel') }}
                                    </button>
                                    <button type="submit" name="decision" value="reject"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-md bg-destructive px-4 text-sm font-medium text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                                        {{ __('inc.review.reject') }}
                                    </button>
                                    <button type="submit" name="decision" value="approve"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                        {{ __('inc.review.approve') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            @endif

            @if ($canPay && $record->status === 'APPROVED' && $record->payment_status === 'UNPAID')
                            <template x-if="paymentId === '{{ $record->id }}'">
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ __('inc.records.markPaid') }}">
                        <div class="absolute inset-0 bg-stone-900/50" x-on:click="paymentId = null"></div>
                        <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border bg-card shadow-lg" x-on:keydown.escape.window="paymentId = null">
                            <form method="POST" action="{{ route('incentives.payment', $record->id) }}">
                                @csrf                                <div class="flex items-start justify-between gap-3 border-b px-5 py-4">
                                    <div>
                                        <h3 class="font-display text-base font-semibold">{{ __('inc.records.markPaid') }}</h3>
                                    </div>
                                    <button type="button" x-on:click="paymentId = null" aria-label="{{ __('common.close') }}"
                                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>

                                <div class="space-y-4 px-5 py-4">
                                    <div class="space-y-1 rounded-lg border bg-muted/40 p-3 text-sm">
                                        <p class="font-medium">{{ $record->user?->name ?? $record->staff_name }} — {{ $record->scheme?->name ?? $record->scheme_name }}</p>
                                        <p class="text-muted-foreground">{{ $record->period }}</p>
                                        <p>{{ __('inc.review.approvedAmount') }} <span class="font-semibold">{{ $record->approved_amount !== null ? number_format($record->approved_amount) . ' FCFA' : '—' }}</span></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label for="paid-at-{{ $record->id }}" class="text-sm font-medium">{{ __('inc.records.paidOn') }}</label>
                                        <input type="date" id="paid-at-{{ $record->id }}" name="paidAt" value="{{ old('paidAt') }}"
                                               class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:w-44">
                                        <p class="text-xs text-muted-foreground">{{ __('inc.records.paidAtHint') }}</p>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2 border-t px-5 py-4">
                                    <button type="button" x-on:click="paymentId = null"
                                            class="inline-flex h-9 items-center rounded-md border bg-background px-4 text-sm font-medium hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring">
                                        {{ __('common.cancel') }}
                                    </button>
                                    <button type="submit"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                        {{ __('inc.records.markPaid') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            @endif
        @endforeach
                    </div>
@endsection
