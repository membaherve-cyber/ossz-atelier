@extends('layouts.app')

@section('title', __('pay.title'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — income and customer payments (GET /payments, perm
         * accounting.payments; enhancement spec §6).
         *
         * Variables (PaymentController@index): $payments (Payment with
         * order.customer), $orders (REGISTERED orders with paid_total for
         * the outstanding preview), $filters (q, method, from, to) and
         * $methods (Atelier::PAYMENT_METHODS).
         *
         * The record-payment modal carries the order list as Alpine data
         * and live-computes the outstanding balance after the entered
         * amount (order value − already paid − typed amount).
         */

        $filtersActive = ($filters['q'] ?? '') !== ''
            || ($filters['method'] ?? '') !== ''
            || ($filters['from'] ?? '') !== ''
            || ($filters['to'] ?? '') !== '';

        // Method pill palette (cash emerald / momo amber / orange money
        // orange / bank transfer teal / other zinc — no blue accents).
        $methodPalette = [
            'CASH' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'MTN_MOMO' => 'bg-amber-50 text-amber-700 border-amber-200',
            'ORANGE_MONEY' => 'bg-orange-50 text-orange-700 border-orange-200',
            'BANK_TRANSFER' => 'bg-teal-50 text-teal-700 border-teal-200',
            'OTHER' => 'bg-zinc-50 text-zinc-600 border-zinc-200',
        ];
    @endphp

    <div class="space-y-5"
         x-data="{ createOpen: {{ $errors->any() && old('formId') === 'payment-create' ? 'true' : 'false' }} }">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('pay.title') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('pay.subtitle') }}</p>
            </div>
            <button type="button" @click="createOpen = true"
                    class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                {{ __('pay.record') }}
            </button>
        </div>

        {{-- Filter bar --}}
        <div class="rounded-lg border bg-card p-3 sm:p-4">
            <form method="GET" action="{{ route('payments.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <label for="pay-q" class="block text-xs font-medium text-muted-foreground">{{ __('common.search') }}</label>
                    <input type="text" id="pay-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('pay.searchPlaceholder') }}"
                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="pay-method" class="block text-xs font-medium text-muted-foreground">{{ __('pay.filterMethod') }}</label>
                    <select id="pay-method" name="method"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('pay.allMethods') }}</option>
                        @foreach ($methods as $methodOption)
                            <option value="{{ $methodOption }}" {{ ($filters['method'] ?? '') === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="pay-from" class="block text-xs font-medium text-muted-foreground">{{ __('pay.fromDate') }}</label>
                        <input type="date" id="pay-from" name="from" value="{{ $filters['from'] ?? '' }}"
                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="pay-to" class="block text-xs font-medium text-muted-foreground">{{ __('pay.toDate') }}</label>
                        <input type="date" id="pay-to" name="to" value="{{ $filters['to'] ?? '' }}"
                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-4">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        {{ __('common.filter') }}
                    </button>
                    @if ($filtersActive)
                        <a href="{{ route('payments.index') }}"
                           class="inline-flex items-center rounded-md px-4 py-2.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-foreground">
                            {{ __('pay.clear') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if ($payments->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                    </div>
                    <p class="font-medium">{{ $filtersActive ? __('pay.emptyFiltersTitle') : __('pay.emptyTitle') }}</p>
                    <p class="max-w-sm text-sm text-muted-foreground">{{ $filtersActive ? __('pay.emptyFiltersDesc') : __('pay.emptyDesc') }}</p>
                </div>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto rounded-lg border bg-card md:block">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('pay.colReference') }}</th>
                            <th class="px-4 py-3">{{ __('pay.colOrder') }}</th>
                            <th class="px-4 py-3">{{ __('pay.colCustomer') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('pay.colAmount') }}</th>
                            <th class="px-4 py-3">{{ __('pay.colMethod') }}</th>
                            <th class="px-4 py-3">{{ __('pay.colDate') }}</th>
                            <th class="px-4 py-3">{{ __('pay.colTxn') }}</th>
                            <th class="px-4 py-3">{{ __('pay.colRecordedBy') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('pay.colActions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $payment->reference }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $payment->order?->order_number ?? '—' }}</td>
                                <td class="max-w-44 px-4 py-3"><span class="block truncate">{{ $payment->order?->customer?->name ?? '—' }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($payment->amount) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $methodPalette[$payment->method] ?? $methodPalette['OTHER'] }}">{{ __('labels.payMethod.'.$payment->method) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $payment->payment_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                <td class="max-w-36 px-4 py-3"><span class="block truncate text-muted-foreground">{{ $payment->txn_ref ?? '—' }}</span></td>
                                <td class="max-w-36 px-4 py-3"><span class="block truncate text-muted-foreground">{{ $payment->recorded_by_name ?? '—' }}</span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('payments.receipt', $payment->id) }}" target="_blank" rel="noopener"
                                           aria-label="{{ __('sup.receiptLink') }} — {{ $payment->reference }}"
                                           class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                                        </a>
                                        <button type="button" @click="$dispatch('pay-edit-{{ $payment->id }}')"
                                                aria-label="{{ __('common.edit') }} — {{ $payment->reference }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                        </button>
                                        <button type="button" @click="$dispatch('pay-delete-{{ $payment->id }}')"
                                                aria-label="{{ __('common.delete') }} — {{ $payment->reference }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($payments->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $payments->total() }} · {{ $payments->currentPage() }} / {{ $payments->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($payments->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $payments->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($payments->hasMorePages())
                                <a href="{{ $payments->appends(request()->query())->nextPageUrl() }}"
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
                @foreach ($payments as $payment)
                    <div class="rounded-xl border bg-card p-4 card-lift">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold">{{ $payment->reference }}</p>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $methodPalette[$payment->method] ?? $methodPalette['OTHER'] }}">{{ __('labels.payMethod.'.$payment->method) }}</span>
                        </div>
                        <div class="mt-1.5 text-sm">
                            <p class="font-medium">{{ $payment->order?->order_number ?? '—' }}</p>
                            <p class="text-muted-foreground">{{ $payment->order?->customer?->name ?? '—' }}</p>
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div class="text-xs text-muted-foreground">
                                <p>{{ $payment->payment_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
                                @if ($payment->txn_ref)
                                    <p class="mt-0.5">{{ $payment->txn_ref }}</p>
                                @endif
                            </div>
                            <p class="text-lg font-bold tabular-nums">{{ \App\Support\Money::format($payment->amount) }}</p>
                        </div>
                        <div class="mt-3 flex items-center justify-end gap-1 border-t pt-3">
                            <a href="{{ route('payments.receipt', $payment->id) }}" target="_blank" rel="noopener"
                               aria-label="{{ __('sup.receiptLink') }} — {{ $payment->reference }}"
                               class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                            </a>
                            <button type="button" @click="$dispatch('pay-edit-{{ $payment->id }}')"
                                    aria-label="{{ __('common.edit') }} — {{ $payment->reference }}"
                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                            </button>
                            <button type="button" @click="$dispatch('pay-delete-{{ $payment->id }}')"
                                    aria-label="{{ __('common.delete') }} — {{ $payment->reference }}"
                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Per-row modals (edit + delete) — placed outside the
                 hidden-on-mobile table so both breakpoints can open them --}}
            @foreach ($payments as $payment)
                {{-- Edit payment --}}
                <div x-data="{ open: {{ $errors->any() && old('formId') === 'payment-edit-'.$payment->id ? 'true' : 'false' }} }"
                     @pay-edit-{{ $payment->id }}.window="open = true">
                    <div x-show="open" style="display: none;" x-cloak
                         class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                         role="dialog" aria-modal="true" aria-label="{{ __('pay.editPayment') }}"
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
                                    <h2 class="text-base font-semibold">{{ __('pay.editPayment') }}</h2>
                                    <p class="mt-0.5 text-sm text-muted-foreground">{{ $payment->reference }}</p>
                                </div>
                                <button type="button" @click="open = false"
                                        aria-label="{{ __('common.close') }}"
                                        class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('payments.update', $payment->id) }}">
                                @csrf
                                <input type="hidden" name="formId" value="payment-edit-{{ $payment->id }}" />
                                <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldOrder') }}</label>
                                        <p class="rounded-md border bg-muted/40 px-3 py-2 text-sm">
                                            <span class="font-medium">{{ $payment->order?->order_number ?? '—' }}</span>
                                            <span class="text-muted-foreground"> · {{ $payment->order?->customer?->name ?? '—' }}</span>
                                        </p>
                                    </div>
                                    <div>
                                        <label for="payment-edit-amount-{{ $payment->id }}" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldAmount') }}</label>
                                        <input type="text" inputmode="decimal" id="payment-edit-amount-{{ $payment->id }}" name="amount" value="{{ old('amount', $payment->amount) }}"
                                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                        @error('amount')
                                            <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="payment-edit-date-{{ $payment->id }}" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldDate') }}</label>
                                        <input type="date" id="payment-edit-date-{{ $payment->id }}" name="paymentDate" value="{{ old('paymentDate', $payment->payment_date->toDateString()) }}"
                                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                        @error('paymentDate')
                                            <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="payment-edit-method-{{ $payment->id }}" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldMethod') }}</label>
                                        <select id="payment-edit-method-{{ $payment->id }}" name="method"
                                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                            @foreach ($methods as $methodOption)
                                                <option value="{{ $methodOption }}" {{ old('method', $payment->method) === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="payment-edit-txn-{{ $payment->id }}" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldTxn') }}</label>
                                        <input type="text" id="payment-edit-txn-{{ $payment->id }}" name="txnRef" value="{{ old('txnRef', $payment->txn_ref ?? '') }}" maxlength="100"
                                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="payment-edit-notes-{{ $payment->id }}" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldNotes') }}</label>
                                        <textarea id="payment-edit-notes-{{ $payment->id }}" name="notes" rows="2"
                                                  class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes', $payment->notes ?? '') }}</textarea>
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

                {{-- Delete payment --}}
                <div x-data="{ open: false }" @pay-delete-{{ $payment->id }}.window="open = true">
                    <div x-show="open" style="display: none;" x-cloak
                         class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                         role="dialog" aria-modal="true" aria-label="{{ __('pay.deletePayment') }}"
                         @keydown.escape.window="open = false">
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false" aria-hidden="true"
                             x-transition.opacity.duration.200ms></div>
                        <div class="relative w-full rounded-t-2xl bg-card shadow-xl sm:max-w-md sm:rounded-lg"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                            <div class="px-5 pt-5">
                                <h2 class="text-base font-semibold">{{ __('pay.deletePayment') }}</h2>
                                <p class="mt-1 text-sm text-muted-foreground">{{ __('pay.deleteConfirm') }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ __('pay.deleteHasAuditNote') }}</p>
                            </div>
                            <form method="POST" action="{{ route('payments.destroy', $payment->id) }}">
                                @csrf
                                <div class="flex justify-end gap-2 px-5 py-4">
                                    <button type="button" @click="open = false"
                                            class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                                        {{ __('common.cancel') }}
                                    </button>
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 rounded-md bg-destructive px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-destructive/90 btn-press">
                                        {{ __('common.delete') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Record payment modal --}}
        <div x-show="createOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('pay.record') }}"
             @keydown.escape.window="createOpen = false"
             x-data="{
                orders: {{ \Illuminate\Support\Js::from($orders->map(fn ($o) => ['id' => $o->id, 'label' => $o->order_number.' — '.optional($o->customer)->name, 'value' => (float) ($o->total_value ?? 0), 'paid' => (float) ($o->paid_total ?? 0)]) ) }},
                orderId: @js(old('orderId', request()->query('order', ''))),
                amount: @js(old('amount')),
                method: @js(old('method', 'CASH')),
                fmt(v) { return new Intl.NumberFormat('{{ app()->getLocale() }}').format(Math.round(v || 0)) + ' XAF'; },
                get selected() { return this.orders.find(o => String(o.id) === String(this.orderId)) || null; },
                get outstanding() {
                    const s = this.selected;
                    if (!s) { return this.fmt(0); }
                    return this.fmt(Math.max(0, s.value - s.paid - (parseFloat(this.amount) || 0)));
                }
             }">
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
                        <h2 class="text-base font-semibold">{{ __('pay.record') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('pay.subtitle') }}</p>
                    </div>
                    <button type="button" @click="createOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('payments.store') }}">
                    @csrf
                    <input type="hidden" name="formId" value="payment-create" />
                    @php
                        // Treasury accounts for the optional routing select —
                        // empty value = auto-route by payment method (§4.4).
                        $treasuryAccounts = \App\Models\TreasuryAccount::query()->active()->orderBy('name')->get();
                    @endphp
                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="payment-order" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldOrder') }}</label>
                            <select id="payment-order" name="orderId" x-model="orderId" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">{{ __('pay.selectOrder') }}</option>
                                <template x-for="order in orders" :key="order.id">
                                    <option :value="order.id" x-text="order.label"></option>
                                </template>
                            </select>
                            @error('orderId')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <div class="rounded-md border bg-muted/40 px-3 py-2 text-sm" x-show="selected" x-cloak>
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-muted-foreground">{{ __('pay.orderValue') }}</span>
                                    <span class="font-semibold tabular-nums" x-text="fmt(selected ? selected.value : 0)"></span>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-4">
                                    <span class="text-muted-foreground">{{ __('pay.alreadyPaid') }}</span>
                                    <span class="font-semibold tabular-nums" x-text="fmt(selected ? selected.paid : 0)"></span>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-4 border-t pt-1">
                                    <span class="text-muted-foreground">{{ __('pay.outstanding') }}</span>
                                    <span class="font-semibold tabular-nums" x-text="outstanding"></span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-red-600" x-show="selected && selected.value <= 0" x-cloak>{{ __('pay.noValueRecorded') }}</p>
                        </div>
                        <div>
                            <label for="payment-amount" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldAmount') }}</label>
                            <input type="text" inputmode="decimal" id="payment-amount" name="amount" x-model="amount" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('amount')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="payment-date" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldDate') }}</label>
                            <input type="date" id="payment-date" name="paymentDate" value="{{ old('paymentDate', now()->toDateString()) }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('paymentDate')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="payment-method" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldMethod') }}</label>
                            <select id="payment-method" name="method" x-model="method"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($methods as $methodOption)
                                    <option value="{{ $methodOption }}">{{ __('labels.payMethod.'.$methodOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="payment-treasury" class="mb-1.5 block text-sm font-medium">{{ __('sup.treasuryAccount') }}</label>
                            <select id="payment-treasury" name="treasuryAccount"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">— {{ __('gen.generatedBadge') }} —</option>
                                @foreach ($treasuryAccounts as $treasuryAccount)
                                    <option value="{{ $treasuryAccount->id }}" {{ old('treasuryAccount') === $treasuryAccount->id ? 'selected' : '' }}>{{ $treasuryAccount->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="payment-txn" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldTxn') }}</label>
                            <input type="text" id="payment-txn" name="txnRef" value="{{ old('txnRef') }}" maxlength="100"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="payment-notes" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldNotes') }}</label>
                            <textarea id="payment-notes" name="notes" rows="2"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="createOpen = false"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('pay.record') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
