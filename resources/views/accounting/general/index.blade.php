@extends('layouts.app')

@section('title', __('gen.indexTitle'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — general accounting (GET /accounting/general, perm
         * accounting.journal; Finance & Comptabilité spec v1.3 §6,
         * SYSCOHADA).
         *
         * Variables (GeneralAccountingController@index): $accounts (the
         * chart), $entries (paginated JournalEntry with lines.account),
         * $journals (Atelier::JOURNALS), $filters (journal).
         *
         * Entries generated from operations are read-only and carry the
         * "auto" chip; the user never picks an account for them. Manual
         * OD entries (Comptable) post parallel arrays code[] / debit[]
         * / credit[] from the repeating form.
         */
    @endphp

    <div class="space-y-5"
         x-data="{ accountOpen: {{ $errors->any() && old('formId') === 'accounting-account-create' ? 'true' : 'false' }} }">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('gen.indexTitle') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('gen.indexSubtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('accounting.balance') }}"
                   class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    {{ __('gen.balanceTitle') }}
                </a>
                <form method="POST" action="{{ route('accounting.general.generate') }}"
                      onsubmit="return confirm('{{ __('gen.generate') }} ?');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="M20 12V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8"/></svg>
                        {{ __('gen.generate') }}
                    </button>
                </form>
            </div>
        </div>

        <p class="text-xs text-muted-foreground">{{ __('gen.generateHelp') }}</p>

        {{-- Chart of accounts + add-account button --}}
        <section class="rounded-lg border bg-card">
            <div class="flex flex-col justify-between gap-3 border-b px-5 py-4 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-base font-semibold">{{ __('gen.chart') }}</h2>
                </div>
                <button type="button" @click="accountOpen = true"
                        class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('gen.addAccount') }}
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('gen.code') }}</th>
                            <th class="px-4 py-3">{{ __('gen.accountName') }}</th>
                            <th class="px-4 py-3">{{ __('gen.type') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-3 font-mono font-medium">{{ $account->code }}</td>
                                <td class="px-4 py-3">{{ $account->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ __('gen.accountType_'.$account->type) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('accounting.ledger', $account->id) }}"
                                       class="inline-flex items-center gap-1.5 rounded-md border border-input bg-card px-3 py-1.5 text-xs font-semibold shadow-sm transition-colors hover:bg-muted/60">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>
                                        {{ __('gen.grandLivre') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t">
                                <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">—</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Manual entry (OD) --}}
        <section class="rounded-lg border bg-card">
            <div class="border-b px-5 py-4">
                <h2 class="text-base font-semibold">{{ __('gen.manualEntry') }}</h2>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('gen.manualEntryHelp') }}</p>
            </div>
            <form method="POST" action="{{ route('accounting.general.entry') }}"
                  x-data="{ lines: [{ code: '', debit: '', credit: '' }, { code: '', debit: '', credit: '' }] }">
                @csrf
                <div class="space-y-4 px-5 py-4">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label for="od-date" class="block text-xs font-medium text-muted-foreground">{{ __('gen.entryDate') }} <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input type="date" id="od-date" name="date" value="{{ old('date', now()->toDateString()) }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('date')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="od-description" class="block text-xs font-medium text-muted-foreground">{{ __('gen.entryDescription') }} <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input type="text" id="od-description" name="description" value="{{ old('description') }}" maxlength="300" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('description')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label for="od-reference" class="block text-xs font-medium text-muted-foreground">{{ __('pay.colReference') }}</label>
                        <input type="text" id="od-reference" name="reference" value="{{ old('reference') }}" maxlength="60"
                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        @error('reference')
                            <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    @error('code')
                        <span class="block text-xs text-red-600" role="alert">{{ $message }}</span>
                    @enderror
                    @error('debit')
                        <span class="block text-xs text-red-600" role="alert">{{ $message }}</span>
                    @enderror
                    @error('credit')
                        <span class="block text-xs text-red-600" role="alert">{{ $message }}</span>
                    @enderror

                    <div class="space-y-2">
                        <div class="hidden grid-cols-12 gap-2 sm:grid">
                            <p class="col-span-6 text-xs font-medium text-muted-foreground">{{ __('gen.code') }}</p>
                            <p class="col-span-2 text-xs font-medium text-muted-foreground">{{ __('gen.debit') }}</p>
                            <p class="col-span-2 text-xs font-medium text-muted-foreground">{{ __('gen.credit') }}</p>
                            <p class="col-span-2"></p>
                        </div>
                        <template x-for="(line, idx) in lines" :key="idx">
                            <div class="grid grid-cols-12 items-start gap-2">
                                <select x-model="line.code" :name="'code['+idx+']'" required
                                        :aria-label="'{{ __('gen.code') }} ' + (idx + 1)"
                                        class="col-span-12 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-6">
                                    <option value="">{{ __('gen.code') }}…</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->code }}">{{ $account->code }} — {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <input type="number" x-model.number="line.debit" :name="'debit['+idx+']'" step="any" min="0" inputmode="decimal"
                                       :aria-label="'{{ __('gen.debit') }} ' + (idx + 1)"
                                       class="col-span-5 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-2" />
                                <input type="number" x-model.number="line.credit" :name="'credit['+idx+']'" step="any" min="0" inputmode="decimal"
                                       :aria-label="'{{ __('gen.credit') }} ' + (idx + 1)"
                                       class="col-span-5 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-2" />
                                <button type="button" @click="lines.splice(idx, 1)"
                                        aria-label="{{ __('common.delete') }}"
                                        class="col-span-2 inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:col-span-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="lines.push({ code: '', debit: '', credit: '' })"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-3 py-2 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            {{ __('gen.addLine') }}
                        </button>
                        <p class="text-xs text-muted-foreground">{{ __('gen.unbalancedEntry') }}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        {{ __('gen.manualEntry') }}
                    </button>
                </div>
            </form>
        </section>

        {{-- Journal filter --}}
        <div class="rounded-lg border bg-card p-3 sm:p-4">
            <form method="GET" action="{{ route('accounting.general') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="gen-journal" class="block text-xs font-medium text-muted-foreground">{{ __('gen.journal') }}</label>
                    <select id="gen-journal" name="journal"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($journals as $journalOption)
                            <option value="{{ $journalOption }}" {{ ($filters['journal'] ?? '') === $journalOption ? 'selected' : '' }}>{{ __('gen.j_'.$journalOption) }}</option>
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

        {{-- Journal entries --}}
        @if ($entries->isEmpty())
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    </div>
                    <p class="font-medium">{{ __('gen.journal') }}</p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border bg-card">
                <table class="w-full min-w-[1000px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('gen.entryDate') }}</th>
                            <th class="px-4 py-3">{{ __('gen.journal') }}</th>
                            <th class="px-4 py-3">{{ __('gen.description') }}</th>
                            <th class="px-4 py-3">{{ __('gen.code') }} / {{ __('gen.debit') }} / {{ __('gen.credit') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('gen.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            @php
                                $totalDebit = (float) $entry->lines->sum('debit');
                                $totalCredit = (float) $entry->lines->sum('credit');
                                $balanced = abs($totalDebit - $totalCredit) < 0.005;
                            @endphp
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $entry->entry_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border border-stone-300 bg-stone-50 px-2 py-0.5 text-[11px] font-semibold text-stone-700">{{ __('gen.j_'.$entry->journal) }}</span>
                                </td>
                                <td class="max-w-56 px-4 py-3">
                                    <p class="truncate font-medium" title="{{ $entry->description }}">{{ $entry->description }}</p>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                        @if ($entry->reference)
                                            <span class="text-xs text-muted-foreground">{{ $entry->reference }}</span>
                                        @endif
                                        <span class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $entry->is_generated ? 'border-violet-200 bg-violet-50 text-violet-700' : 'border-zinc-200 bg-zinc-50 text-zinc-600' }}">
                                            {{ $entry->is_generated ? __('gen.generatedBadge') : __('gen.manualBadge') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="max-w-96 px-4 py-3">
                                    <table class="w-full text-xs">
                                        <tbody>
                                            @foreach ($entry->lines as $line)
                                                <tr>
                                                    <td class="w-16 py-0.5 pr-2 font-mono">{{ $line->account_code }}</td>
                                                    <td class="py-0.5 pr-2 text-muted-foreground">{{ $line->label ?? '—' }}</td>
                                                    <td class="w-28 py-0.5 text-right tabular-nums">{{ $line->debit > 0 ? \App\Support\Money::format($line->debit) : '—' }}</td>
                                                    <td class="w-28 py-0.5 text-right tabular-nums">{{ $line->credit > 0 ? \App\Support\Money::format($line->credit) : '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <p class="font-semibold tabular-nums">{{ \App\Support\Money::format($totalDebit) }}</p>
                                    <span class="mt-0.5 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap {{ $balanced ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">{{ __('gen.balanced') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($entries->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $entries->total() }} · {{ $entries->currentPage() }} / {{ $entries->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($entries->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $entries->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($entries->hasMorePages())
                                <a href="{{ $entries->appends(request()->query())->nextPageUrl() }}"
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

        {{-- Add-account modal --}}
        <div x-show="accountOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('gen.addAccount') }}"
             @keydown.escape.window="accountOpen = false">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="accountOpen = false" aria-hidden="true"
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
                        <h2 class="text-base font-semibold">{{ __('gen.addAccount') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('gen.chart') }}</p>
                    </div>
                    <button type="button" @click="accountOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('accounting.accounts.store') }}">
                    @csrf
                    <input type="hidden" name="formId" value="accounting-account-create" />
                    <div class="grid gap-4 px-5 py-4">
                        <div>
                            <label for="account-code" class="mb-1.5 block text-sm font-medium">{{ __('gen.code') }}</label>
                            <input type="text" id="account-code" name="code" value="{{ old('code') }}" maxlength="12" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('code')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="account-name" class="mb-1.5 block text-sm font-medium">{{ __('gen.accountName') }}</label>
                            <input type="text" id="account-name" name="name" value="{{ old('name') }}" maxlength="190" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('name')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="account-type" class="mb-1.5 block text-sm font-medium">{{ __('gen.type') }}</label>
                            <select id="account-type" name="type"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach (\App\Support\Atelier::ACCOUNT_TYPES as $typeOption)
                                    <option value="{{ $typeOption }}" {{ old('type') === $typeOption ? 'selected' : '' }}>{{ __('gen.accountType_'.$typeOption) }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="accountOpen = false"
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
    </div>
@endsection
