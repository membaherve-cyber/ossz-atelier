@extends('layouts.app')

@section('title', __('prl.title'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — salaries and payroll (GET /payroll, perm
         * accounting.payroll; enhancement spec §8).
         *
         * Variables (PayrollController@index): $payrolls (with items.staff),
         * $profiles (with staff, latest first), $staffList, $remunerationTypes,
         * $preview (PayrollService::previewItems()), $tab, $methods,
         * $canApprove.
         *
         * Payroll periods are collapsible cards (label / status badge /
         * period / line count / net total) with the staff lines, totals and
         * the approve / pay / delete actions inside. Salary profiles are
         * grouped per staff member with the latest profile on top and the
         * older ones in a collapsible history.
         */

        // Payroll status badge palette (DRAFT zinc / PENDING amber /
        // APPROVED emerald / PARTIALLY_PAID orange / PAID teal).
        $statusPalette = [
            'DRAFT' => 'bg-zinc-50 text-zinc-600 border-zinc-200',
            'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200',
            'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'PARTIALLY_PAID' => 'bg-orange-50 text-orange-700 border-orange-200',
            'PAID' => 'bg-teal-50 text-teal-700 border-teal-200',
        ];

        $payableStatuses = ['APPROVED', 'PARTIALLY_PAID'];
        $hasZeroSalary = false;

        foreach ($preview as $entry) {
            if ((float) $entry['salary'] <= 0.004) {
                $hasZeroSalary = true;
            }
        }

        // Salary profiles grouped per staff member (controller sorts by
        // effective_date desc, so the first of each group is the latest).
        $profilesByStaff = $profiles->groupBy('staff_id');
    @endphp

    <div class="space-y-5"
         x-data="{
            createOpen: {{ $errors->any() && old('formId') === 'payroll-create' ? 'true' : 'false' }},
            profileOpen: {{ $errors->any() && old('formId') === 'profile-create' ? 'true' : 'false' }}
         }">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('prl.title') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('prl.subtitle') }}</p>
            </div>
            @if ($tab === 'profiles')
                <button type="button" @click="profileOpen = true"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('prl.newProfile') }}
                </button>
            @else
                <button type="button" @click="createOpen = true"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('prl.createPeriod') }}
                </button>
            @endif
        </div>

        {{-- Tabs (GET links — the server round-trip keeps the URL shareable) --}}
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('payroll.index', ['tab' => 'payroll']) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $tab === 'payroll' ? 'bg-primary text-primary-foreground' : 'border bg-background text-muted-foreground hover:text-foreground' }}"
               @if ($tab === 'payroll') aria-current="true" @endif>{{ __('prl.tabPayroll') }}</a>
            <a href="{{ route('payroll.index', ['tab' => 'profiles']) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $tab === 'profiles' ? 'bg-primary text-primary-foreground' : 'border bg-background text-muted-foreground hover:text-foreground' }}"
               @if ($tab === 'profiles') aria-current="true" @endif>{{ __('prl.tabProfiles') }}</a>
        </div>

        {{-- ================= Payroll periods tab ================= --}}
        <div class="space-y-4" x-show="{{ $tab === 'payroll' ? 'true' : 'false' }}" x-transition.opacity.duration.200ms>
            @if ($payrolls->isEmpty())
                <div class="rounded-lg border bg-card">
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                        <div class="rounded-full bg-muted p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M8 6h8"/><path d="M16 14.5a4 4 0 1 0-8 0"/><path d="M8 18h8"/></svg>
                        </div>
                        <p class="font-medium">{{ __('prl.emptyPeriods') }}</p>
                        <p class="max-w-sm text-sm text-muted-foreground">{{ __('prl.emptyPeriodsDesc') }}</p>
                    </div>
                </div>
            @else
                @foreach ($payrolls as $payroll)
                    @php
                        $netTotal = (float) $payroll->items->sum('net_pay');
                        $hasPaidItems = $payroll->items->contains(fn ($item) => $item->payment_status === 'PAID');
                        $payable = in_array($payroll->status, $payableStatuses, true);
                    @endphp
                    <div class="rounded-xl border bg-card card-lift" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold">{{ $payroll->label }}</p>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$payroll->status] ?? $statusPalette['DRAFT'] }}">{{ __('labels.prlStatus.'.$payroll->status) }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ $payroll->period_start->locale(app()->getLocale())->translatedFormat('j M Y') }} — {{ $payroll->period_end->locale(app()->getLocale())->translatedFormat('j M Y') }} · {{ \App\Support\Trans::fill('prl.items', ['count' => $payroll->items->count()]) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <p class="text-sm font-bold tabular-nums">{{ \App\Support\Money::format($netTotal) }}</p>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-muted-foreground transition-transform" :class="open ? 'rotate-0' : '-rotate-90'" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </button>
                        <div x-show="open" style="display: none;" x-cloak x-transition.opacity.duration.200ms class="border-t px-4 py-3">
                            @if ($payroll->notes)
                                <p class="mb-3 text-xs text-muted-foreground">{{ $payroll->notes }}</p>
                            @endif
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[820px] text-sm">
                                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        <tr>
                                            <th class="px-3 py-2.5">{{ __('prl.colStaff') }}</th>
                                            <th class="px-3 py-2.5 text-right">{{ __('prl.colSalary') }}</th>
                                            <th class="px-3 py-2.5 text-right">{{ __('prl.colAllowances') }}</th>
                                            <th class="px-3 py-2.5 text-right">{{ __('prl.colBonuses') }}</th>
                                            <th class="px-3 py-2.5 text-right">{{ __('prl.colDeductions') }}</th>
                                            <th class="px-3 py-2.5 text-right">{{ __('prl.colAdvanceRecovered') }}</th>
                                            <th class="px-3 py-2.5 text-right">{{ __('prl.colNetPay') }}</th>
                                            <th class="px-3 py-2.5">{{ __('prl.colPaymentStatus') }}</th>
                                            <th class="px-3 py-2.5 text-right">{{ __('prl.colActions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($payroll->items as $item)
                                            <tr class="border-t transition-colors hover:bg-muted/30">
                                                <td class="whitespace-nowrap px-3 py-2.5 font-medium">{{ $item->staff_name }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::amount($item->salary_amount) }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::amount($item->allowances) }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::amount($item->bonuses) }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::amount($item->deductions) }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::amount($item->advance_recovered) }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($item->net_pay) }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5">
                                                    @if ($item->payment_status === 'PAID')
                                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                                                            {{ __('labels.prlPayStatus.PAID') }}{{ $item->payment_date ? ' · '.$item->payment_date->locale(app()->getLocale())->translatedFormat('j M Y') : '' }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">{{ __('labels.prlPayStatus.PENDING') }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    @if ($payable && $item->payment_status === 'PENDING')
                                                        <div class="flex items-center justify-end gap-1">
                                                            <button type="button" @click="$dispatch('prl-item-{{ $item->id }}')"
                                                                    aria-label="{{ __('prl.editLine') }} — {{ $item->staff_name }}"
                                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                                            </button>
                                                            <button type="button" @click="$dispatch('prl-pay-{{ $item->id }}')"
                                                                    aria-label="{{ __('prl.payLine') }} — {{ $item->staff_name }}"
                                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M2 7v10"/><path d="M2 12h20"/><path d="M22 7v10"/><path d="M2 7h20"/></svg>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="border-t bg-muted/30">
                                            <td class="whitespace-nowrap px-3 py-2.5 font-semibold">{{ __('prl.totals') }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($payroll->items->sum('salary_amount')) }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($payroll->items->sum('allowances')) }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($payroll->items->sum('bonuses')) }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($payroll->items->sum('deductions')) }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($payroll->items->sum('advance_recovered')) }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($netTotal) }}</td>
                                            <td class="px-3 py-2.5"></td>
                                            <td class="px-3 py-2.5"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            @if ($payroll->approved_by_name)
                                <p class="mt-2 text-xs text-muted-foreground">{{ __('labels.prlStatus.APPROVED') }} · {{ $payroll->approved_by_name }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t pt-3">
                                @if ($canApprove && $payroll->status === 'PENDING')
                                    <form method="POST" action="{{ route('payroll.approve', $payroll->id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('{{ __('prl.approveConfirm') }}')"
                                                class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 btn-press">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                                            {{ __('prl.approvePeriod') }}
                                        </button>
                                    </form>
                                @endif
                                @if (! $hasPaidItems)
                                    <form method="POST" action="{{ route('payroll.destroy', $payroll->id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('{{ __('prl.deleteConfirm') }}')"
                                                class="inline-flex items-center gap-2 rounded-md border border-red-200 bg-card px-4 py-2 text-sm font-semibold text-red-700 transition-colors hover:bg-red-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            {{ __('common.delete') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Line modals (edit + pay) --}}
                    @foreach ($payroll->items as $item)
                        @if (in_array($payroll->status, $payableStatuses, true) && $item->payment_status === 'PENDING')
                            @php
                                $outstanding = \App\Services\PayrollService::outstandingAdvanceTotal((string) $item->staff_id);
                            @endphp
                            {{-- Edit payroll line --}}
                            <div x-data="{ open: {{ $errors->any() && old('formId') === 'payroll-item-'.$item->id ? 'true' : 'false' }} }"
                                 @prl-item-{{ $item->id }}.window="open = true">
                                <div x-show="open" style="display: none;" x-cloak
                                     class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                                     role="dialog" aria-modal="true" aria-label="{{ __('prl.editLine') }} — {{ $item->staff_name }}"
                                     @keydown.escape.window="open = false">
                                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false" aria-hidden="true"
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
                                                <h2 class="text-base font-semibold">{{ __('prl.editLine') }}</h2>
                                                <p class="mt-0.5 text-sm text-muted-foreground">{{ $item->staff_name }} · {{ $payroll->label }}</p>
                                            </div>
                                            <button type="button" @click="open = false"
                                                    aria-label="{{ __('common.close') }}"
                                                    class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                            </button>
                                        </div>
                                        <form method="POST" action="{{ route('payroll.items.update', $item->id) }}">
                                            @csrf
                                            <input type="hidden" name="formId" value="payroll-item-{{ $item->id }}" />
                                            <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                                                <div>
                                                    <label for="prl-item-salary-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.colSalary') }}</label>
                                                    <input type="text" inputmode="decimal" id="prl-item-salary-{{ $item->id }}" name="salaryAmount" value="{{ old('salaryAmount', $item->salary_amount) }}"
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                    @error('salaryAmount')
                                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label for="prl-item-allowances-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.colAllowances') }}</label>
                                                    <input type="text" inputmode="decimal" id="prl-item-allowances-{{ $item->id }}" name="allowances" value="{{ old('allowances', $item->allowances) }}"
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                    @error('allowances')
                                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label for="prl-item-bonuses-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.colBonuses') }}</label>
                                                    <input type="text" inputmode="decimal" id="prl-item-bonuses-{{ $item->id }}" name="bonuses" value="{{ old('bonuses', $item->bonuses) }}"
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                    @error('bonuses')
                                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label for="prl-item-deductions-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.colDeductions') }}</label>
                                                    <input type="text" inputmode="decimal" id="prl-item-deductions-{{ $item->id }}" name="deductions" value="{{ old('deductions', $item->deductions) }}"
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                    @error('deductions')
                                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <div class="rounded-md border bg-muted/40 px-3 py-2 text-xs">
                                                        @if ($outstanding > 0.004)
                                                            <p class="font-semibold">{{ \App\Support\Trans::fill('prl.outstandingAdvances', ['amount' => \App\Support\Money::format($outstanding)]) }}</p>
                                                        @else
                                                            <p>{{ __('prl.noOutstandingAdvances') }}</p>
                                                        @endif
                                                        <p class="mt-1 text-muted-foreground">{{ __('prl.advanceRecoveryHint') }}</p>
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label for="prl-item-advance-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.advanceRecovery') }}</label>
                                                    <input type="text" inputmode="decimal" id="prl-item-advance-{{ $item->id }}" name="advanceRecovered" value="{{ old('advanceRecovered', $item->advance_recovered) }}"
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                    @error('advanceRecovered')
                                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label for="prl-item-notes-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldNotes') }}</label>
                                                    <textarea id="prl-item-notes-{{ $item->id }}" name="notes" rows="2"
                                                              class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes', $item->notes ?? '') }}</textarea>
                                                </div>
                                            </div>
                                            <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                                <button type="button" @click="open = false"
                                                        class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                                                    {{ __('common.cancel') }}
                                                </button>
                                                <button type="submit"
                                                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                                    {{ __('common.saveChanges') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Record salary payment --}}
                            <div x-data="{ open: false }" @prl-pay-{{ $item->id }}.window="open = true">
                                <div x-show="open" style="display: none;" x-cloak
                                     class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                                     role="dialog" aria-modal="true" aria-label="{{ __('prl.payLine') }} — {{ $item->staff_name }}"
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
                                                <h2 class="text-base font-semibold">{{ __('prl.payLine') }}</h2>
                                                <p class="mt-0.5 text-sm text-muted-foreground">{{ $item->staff_name }} · {{ \App\Support\Money::format($item->net_pay) }}</p>
                                            </div>
                                            <button type="button" @click="open = false"
                                                    aria-label="{{ __('common.close') }}"
                                                    class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                            </button>
                                        </div>
                                        <form method="POST" action="{{ route('payroll.items.pay', $item->id) }}">
                                            @csrf
                                            <div class="space-y-4 px-5 py-4">
                                                <div>
                                                    <label for="prl-pay-date-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldPaymentDate') }}</label>
                                                    <input type="date" id="prl-pay-date-{{ $item->id }}" name="paymentDate" value="{{ old('paymentDate', now()->toDateString()) }}" required
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                    @error('paymentDate')
                                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label for="prl-pay-method-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldPaymentMethod') }}</label>
                                                    <select id="prl-pay-method-{{ $item->id }}" name="method"
                                                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                        @foreach ($methods as $methodOption)
                                                            <option value="{{ $methodOption }}" {{ old('method', 'CASH') === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="prl-pay-txn-{{ $item->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldTxn') }}</label>
                                                    <input type="text" id="prl-pay-txn-{{ $item->id }}" name="txnRef" value="{{ old('txnRef') }}" maxlength="100"
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                </div>
                                            </div>
                                            <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                                <button type="button" @click="open = false"
                                                        class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                                                    {{ __('common.cancel') }}
                                                </button>
                                                <button type="submit"
                                                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                                    {{ __('prl.payLine') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endforeach

                @if ($payrolls->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $payrolls->total() }} · {{ $payrolls->currentPage() }} / {{ $payrolls->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($payrolls->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $payrolls->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($payrolls->hasMorePages())
                                <a href="{{ $payrolls->appends(request()->query())->nextPageUrl() }}"
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
            @endif
        </div>

        {{-- ================= Salary profiles tab ================= --}}
        <div class="space-y-4" x-show="{{ $tab === 'profiles' ? 'true' : 'false' }}" x-transition.opacity.duration.200ms>
            <div class="flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <p class="text-sm font-semibold text-amber-800">{{ __('prl.confidential') }}</p>
            </div>

            @if ($profiles->isEmpty())
                <div class="rounded-lg border bg-card">
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                        <div class="rounded-full bg-muted p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>
                        </div>
                        <p class="font-medium">{{ __('prl.emptyProfiles') }}</p>
                        <p class="max-w-sm text-sm text-muted-foreground">{{ __('prl.emptyProfilesDesc') }}</p>
                    </div>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($profilesByStaff as $staffProfiles)
                        @php
                            $latest = $staffProfiles->first();
                        @endphp
                        <div class="rounded-xl border bg-card p-4 card-lift">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $latest->staff?->name ?? '—' }}</p>
                                    @if ($latest->staff?->job_title)
                                        <p class="text-xs text-muted-foreground">{{ $latest->staff->job_title }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $latest->status === 'ACTIVE' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-zinc-50 text-zinc-500' }}">{{ $latest->status }}</span>
                            </div>
                            <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('prl.colRemuneration') }}</dt>
                                    <dd class="font-medium">{{ __('labels.remuneration.'.$latest->remuneration_type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('prl.colBase') }}</dt>
                                    <dd class="font-semibold tabular-nums">{{ \App\Support\Money::format($latest->base_amount) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('prl.colAllowances') }}</dt>
                                    <dd class="tabular-nums">{{ \App\Support\Money::format($latest->allowances) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('prl.colEffective') }}</dt>
                                    <dd>{{ $latest->effective_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</dd>
                                </div>
                                @if ($latest->frequency)
                                    <div class="col-span-2">
                                        <dt class="text-xs text-muted-foreground">{{ __('prl.colFrequency') }}</dt>
                                        <dd>{{ $latest->frequency }}</dd>
                                    </div>
                                @endif
                            </dl>
                            @if ($latest->remarks)
                                <p class="mt-2 text-xs text-muted-foreground">{{ $latest->remarks }}</p>
                            @endif
                            <div class="mt-3 flex items-center justify-between gap-2 border-t pt-3">
                                @if ($staffProfiles->count() > 1)
                                    <div x-data="{ open: false }">
                                        <button type="button" @click="open = !open" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 transition-transform" :class="open ? 'rotate-0' : '-rotate-90'" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                            {{ __('prl.colEffective') }} ({{ $staffProfiles->count() - 1 }})
                                        </button>
                                        <ul x-show="open" style="display: none;" x-cloak x-transition.opacity.duration.200ms class="mt-2 space-y-2">
                                            @foreach ($staffProfiles->skip(1) as $older)
                                                <li class="rounded-md border bg-muted/30 px-3 py-2 text-xs">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <span class="font-semibold">{{ __('labels.remuneration.'.$older->remuneration_type) }} · {{ \App\Support\Money::format($older->base_amount) }}</span>
                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $older->status === 'ACTIVE' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-zinc-50 text-zinc-500' }}">{{ $older->status }}</span>
                                                    </div>
                                                    <p class="mt-0.5 text-muted-foreground">{{ __('prl.colEffective') }}: {{ $older->effective_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <span></span>
                                @endif
                                <button type="button" @click="$dispatch('profile-edit-{{ $latest->id }}')"
                                        aria-label="{{ __('prl.editProfile') }} — {{ $latest->staff?->name }}"
                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- New payroll period modal (with the proposed lines preview) --}}
        <div x-show="createOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('prl.createPeriod') }}"
             @keydown.escape.window="createOpen = false">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="createOpen = false" aria-hidden="true"
                 x-transition.opacity.duration.200ms></div>
            <div class="relative max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-card shadow-xl sm:max-w-2xl sm:rounded-lg"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold">{{ __('prl.createPeriod') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('prl.subtitle') }}</p>
                    </div>
                    <button type="button" @click="createOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('payroll.store') }}">
                    @csrf
                    <input type="hidden" name="formId" value="payroll-create" />
                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="prl-label" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldLabel') }}</label>
                            <input type="text" id="prl-label" name="label" value="{{ old('label') }}" maxlength="60" placeholder="{{ __('prl.labelPlaceholder') }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('label')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="prl-start" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldPeriodStart') }}</label>
                            <input type="date" id="prl-start" name="periodStart" value="{{ old('periodStart') }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('periodStart')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="prl-end" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldPeriodEnd') }}</label>
                            <input type="date" id="prl-end" name="periodEnd" value="{{ old('periodEnd') }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('periodEnd')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="prl-notes" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldNotes') }}</label>
                            <textarea id="prl-notes" name="notes" rows="2"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <h3 class="text-sm font-semibold">{{ __('prl.previewTitle') }}</h3>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ __('prl.previewHint') }}</p>
                            @if ($hasZeroSalary)
                                <p class="mt-1 text-xs text-amber-700">{{ __('prl.noProfilesHint') }}</p>
                            @endif
                            @if (count($preview) === 0)
                                <p class="mt-2 text-sm text-muted-foreground">{{ __('prl.emptyPeriodsDesc') }}</p>
                            @else
                                <div class="mt-2 overflow-x-auto rounded-md border">
                                    <table class="w-full min-w-[420px] text-sm">
                                        <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            <tr>
                                                <th class="px-3 py-2">{{ __('prl.colStaff') }}</th>
                                                <th class="px-3 py-2 text-right">{{ __('prl.colSalary') }}</th>
                                                <th class="px-3 py-2 text-right">{{ __('prl.colAllowances') }}</th>
                                                <th class="px-3 py-2 text-right">{{ __('prl.colNetPay') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($preview as $entry)
                                                <tr class="border-t">
                                                    <td class="whitespace-nowrap px-3 py-2 font-medium">{{ $entry['staff']->name }}</td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ \App\Support\Money::amount($entry['salary']) }}</td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ \App\Support\Money::amount($entry['allowances']) }}</td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-right font-semibold tabular-nums">{{ \App\Support\Money::amount($entry['net']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="createOpen = false"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('prl.createPeriod') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- New salary profile modal --}}
        <div x-show="profileOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('prl.newProfile') }}"
             @keydown.escape.window="profileOpen = false">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="profileOpen = false" aria-hidden="true"
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
                        <h2 class="text-base font-semibold">{{ __('prl.newProfile') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('prl.confidential') }}</p>
                    </div>
                    <button type="button" @click="profileOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('salary.profiles.store') }}">
                    @csrf
                    <input type="hidden" name="formId" value="profile-create" />
                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                        <div>
                            <label for="prl-profile-staff" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldStaff') }}</label>
                            <select id="prl-profile-staff" name="staffId" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">{{ __('prl.selectStaff') }}</option>
                                @foreach ($staffList as $staffMember)
                                    <option value="{{ $staffMember->id }}" {{ old('staffId') === $staffMember->id ? 'selected' : '' }}>{{ $staffMember->name }}</option>
                                @endforeach
                            </select>
                            @error('staffId')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="prl-profile-type" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldRemuneration') }}</label>
                            <select id="prl-profile-type" name="remunerationType"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($remunerationTypes as $remunerationType)
                                    <option value="{{ $remunerationType }}" {{ old('remunerationType', 'MONTHLY') === $remunerationType ? 'selected' : '' }}>{{ __('labels.remuneration.'.$remunerationType) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="prl-profile-base" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldBaseAmount') }}</label>
                            <input type="text" inputmode="decimal" id="prl-profile-base" name="baseAmount" value="{{ old('baseAmount') }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('baseAmount')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="prl-profile-effective" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldEffectiveDate') }}</label>
                            <input type="date" id="prl-profile-effective" name="effectiveDate" value="{{ old('effectiveDate', now()->toDateString()) }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('effectiveDate')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="prl-profile-frequency" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldFrequency') }}</label>
                            <input type="text" id="prl-profile-frequency" name="frequency" value="{{ old('frequency') }}" maxlength="40"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <div>
                            <label for="prl-profile-allowances" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldAllowances') }}</label>
                            <input type="text" inputmode="decimal" id="prl-profile-allowances" name="allowances" value="{{ old('allowances', 0) }}"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <div>
                            <label for="prl-profile-status" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldProfileStatus') }}</label>
                            <select id="prl-profile-status" name="status"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="ACTIVE" {{ old('status', 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                                <option value="INACTIVE" {{ old('status') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="prl-profile-remarks" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldProfileRemarks') }}</label>
                            <textarea id="prl-profile-remarks" name="remarks" rows="2"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('remarks') }}</textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="profileOpen = false"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('common.create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Per-profile edit modals --}}
        @foreach ($profiles as $profile)
            <div x-data="{ open: {{ $errors->any() && old('formId') === 'profile-edit-'.$profile->id ? 'true' : 'false' }} }"
                 @profile-edit-{{ $profile->id }}.window="open = true">
                <div x-show="open" style="display: none;" x-cloak
                     class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                     role="dialog" aria-modal="true" aria-label="{{ __('prl.editProfile') }} — {{ $profile->staff?->name }}"
                     @keydown.escape.window="open = false">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false" aria-hidden="true"
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
                                <h2 class="text-base font-semibold">{{ __('prl.editProfile') }}</h2>
                                <p class="mt-0.5 text-sm text-muted-foreground">{{ $profile->staff?->name }}</p>
                            </div>
                            <button type="button" @click="open = false"
                                    aria-label="{{ __('common.close') }}"
                                    class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('salary.profiles.update', $profile->id) }}">
                            @csrf
                            <input type="hidden" name="formId" value="profile-edit-{{ $profile->id }}" />
                            <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                                <div>
                                    <label for="prl-edit-staff-{{ $profile->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldStaff') }}</label>
                                    <select id="prl-edit-staff-{{ $profile->id }}" name="staffId"
                                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                        @foreach ($staffList as $staffMember)
                                            <option value="{{ $staffMember->id }}" {{ old('staffId', $profile->staff_id) === $staffMember->id ? 'selected' : '' }}>{{ $staffMember->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('staffId')
                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="prl-edit-type-{{ $profile->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldRemuneration') }}</label>
                                    <select id="prl-edit-type-{{ $profile->id }}" name="remunerationType"
                                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                        @foreach ($remunerationTypes as $remunerationType)
                                            <option value="{{ $remunerationType }}" {{ old('remunerationType', $profile->remuneration_type) === $remunerationType ? 'selected' : '' }}>{{ __('labels.remuneration.'.$remunerationType) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="prl-edit-base-{{ $profile->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldBaseAmount') }}</label>
                                    <input type="text" inputmode="decimal" id="prl-edit-base-{{ $profile->id }}" name="baseAmount" value="{{ old('baseAmount', $profile->base_amount) }}"
                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                    @error('baseAmount')
                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="prl-edit-effective-{{ $profile->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldEffectiveDate') }}</label>
                                    <input type="date" id="prl-edit-effective-{{ $profile->id }}" name="effectiveDate" value="{{ old('effectiveDate', $profile->effective_date->toDateString()) }}"
                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                    @error('effectiveDate')
                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="prl-edit-frequency-{{ $profile->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldFrequency') }}</label>
                                    <input type="text" id="prl-edit-frequency-{{ $profile->id }}" name="frequency" value="{{ old('frequency', $profile->frequency ?? '') }}" maxlength="40"
                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                </div>
                                <div>
                                    <label for="prl-edit-allowances-{{ $profile->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldAllowances') }}</label>
                                    <input type="text" inputmode="decimal" id="prl-edit-allowances-{{ $profile->id }}" name="allowances" value="{{ old('allowances', $profile->allowances) }}"
                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                </div>
                                <div>
                                    <label for="prl-edit-status-{{ $profile->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldProfileStatus') }}</label>
                                    <select id="prl-edit-status-{{ $profile->id }}" name="status"
                                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                        <option value="ACTIVE" {{ old('status', $profile->status) === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                                        <option value="INACTIVE" {{ old('status', $profile->status) === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="prl-edit-remarks-{{ $profile->id }}" class="mb-1.5 block text-sm font-medium">{{ __('prl.fieldProfileRemarks') }}</label>
                                    <textarea id="prl-edit-remarks-{{ $profile->id }}" name="remarks" rows="2"
                                              class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('remarks', $profile->remarks ?? '') }}</textarea>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                <button type="button" @click="open = false"
                                        class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                                    {{ __('common.cancel') }}
                                </button>
                                <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                    {{ __('common.saveChanges') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
