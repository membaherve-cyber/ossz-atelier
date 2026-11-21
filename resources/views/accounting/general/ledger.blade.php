@extends('layouts.app')

@section('title', __('gen.ledgerOf', ['code' => $ledger['account']->code]))

@section('content')
    @php
        /**
         * OSSZ ATELIER — general ledger of one account
         * (GET /accounting/ledger/{id}, perm accounting.journal; Finance
         * & Comptabilité spec v1.3 §6).
         *
         * Variables (GeneralAccountingController@ledger): $ledger with
         * 'account' (AccountingAccount), 'lines' (date, journal,
         * description, reference, label, debit, credit, running balance)
         * and 'totals' (debit, credit).
         *
         * The running balance reads debit − credit; negative balances
         * show in red. Dates arrive as raw Y-m-d strings from the
         * ledger query, so they are parsed here for locale formatting.
         */
    @endphp

    <div class="space-y-5">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('gen.ledgerOf', ['code' => $ledger['account']->code]) }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ $ledger['account']->name }} · {{ __('gen.accountType_'.$ledger['account']->type) }}</p>
            </div>
            <a href="{{ route('accounting.general') }}"
               class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                {{ __('common.back') }}
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">{{ __('gen.entryDate') }}</th>
                        <th class="px-4 py-3">{{ __('gen.journal') }}</th>
                        <th class="px-4 py-3">{{ __('gen.description') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('gen.debit') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('gen.credit') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('treas.balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ledger['lines'] as $line)
                        <tr class="border-t transition-colors hover:bg-muted/30">
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ \Carbon\Carbon::parse($line['date'])->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center rounded-full border border-stone-300 bg-stone-50 px-2 py-0.5 text-[11px] font-semibold text-stone-700">{{ __('gen.j_'.$line['journal']) }}</span>
                            </td>
                            <td class="max-w-72 px-4 py-3">
                                <p class="truncate" title="{{ $line['description'] }}">{{ $line['description'] }}</p>
                                @if ($line['label'])
                                    <p class="truncate text-xs text-muted-foreground">{{ $line['label'] }}</p>
                                @endif
                                @if ($line['reference'])
                                    <p class="truncate text-xs text-muted-foreground">{{ $line['reference'] }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $line['debit'] > 0 ? \App\Support\Money::format($line['debit']) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $line['credit'] > 0 ? \App\Support\Money::format($line['credit']) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $line['balance'] < 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($line['balance']) }}</td>
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
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($ledger['totals']['debit']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($ledger['totals']['credit']) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ abs($ledger['totals']['debit'] - $ledger['totals']['credit']) < 0.005 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">{{ __('gen.balanced') }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
