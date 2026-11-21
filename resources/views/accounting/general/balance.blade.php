@extends('layouts.app')

@section('title', __('gen.balanceTitle'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — trial balance (GET /accounting/balance, perm
         * accounting.journal; Finance & Comptabilité spec v1.3 §6).
         *
         * Variables (GeneralAccountingController@balance): $trial with
         * 'rows' (code, name, type, debit, credit, balance), 'totals'
         * (debit, credit) and 'balanced' (debits equal credits), and
         * $result (revenue, expense, net — accrual view).
         *
         * A print button triggers window.print(); the header and
         * button carry the no-print class so the table prints clean.
         */
    @endphp

    <style>
        @media print { .no-print { display: none !important; } body { background: #fff; } }
    </style>

    <div class="space-y-5">

        {{-- Page header --}}
        <div class="no-print flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('gen.balanceTitle') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('gen.indexSubtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('accounting.general') }}"
                   class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    {{ __('common.back') }}
                </a>
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                    {{ __('frep.print') }}
                </button>
            </div>
        </div>

        {{-- Result KPI cards --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-lg border bg-card p-4">
                <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{{ __('gen.revenue') }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums">{{ \App\Support\Money::format($result['revenue']) }}</p>
            </div>
            <div class="rounded-lg border bg-card p-4">
                <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{{ __('gen.expense') }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums">{{ \App\Support\Money::format($result['expense']) }}</p>
            </div>
            <div class="rounded-lg border bg-card p-4">
                <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{{ __('gen.netResult') }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums {{ $result['net'] < 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($result['net']) }}</p>
            </div>
        </div>

        {{-- Trial balance table --}}
        <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full min-w-[800px] text-sm">
                <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">{{ __('gen.code') }}</th>
                        <th class="px-4 py-3">{{ __('gen.accountName') }}</th>
                        <th class="px-4 py-3">{{ __('gen.type') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('gen.debit') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('gen.credit') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('treas.balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trial['rows'] as $row)
                        <tr class="border-t transition-colors hover:bg-muted/30">
                            <td class="whitespace-nowrap px-4 py-3 font-mono font-medium">{{ $row['code'] }}</td>
                            <td class="px-4 py-3">{{ $row['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ __('gen.accountType_'.$row['type']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['debit'] > 0 ? \App\Support\Money::format($row['debit']) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['credit'] > 0 ? \App\Support\Money::format($row['credit']) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $row['balance'] < 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr class="border-t">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">—</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t bg-muted/20">
                        <td class="px-4 py-3 text-sm font-semibold" colspan="3">{{ __('gen.total') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($trial['totals']['debit']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($trial['totals']['credit']) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $trial['balanced'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">{{ __('gen.balanced') }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
