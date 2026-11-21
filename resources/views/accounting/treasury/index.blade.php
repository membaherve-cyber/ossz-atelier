@extends('layouts.app')

@section('title', __('treas.indexTitle'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — treasury (GET /treasury, perm treasury.view;
         * Finance & Comptabilité spec v1.3 §4.4).
         *
         * Variables (TreasuryController@index): $overview (accounts with
         * derived balance + total), $accounts, $movements (paginated),
         * $journal (days: date, in, out, movements), $kinds
         * (Atelier::TREASURY_MOVEMENT_KINDS), $filters (account, kind).
         *
         * Balances are always derived (opening + Σ IN − Σ OUT); write
         * forms (transfer, cash in/out, new account, reconcile) are
         * treasury.manage-gated. An internal transfer is neither a
         * receipt nor an expense (§4.4).
         */

        $canManage = \App\Services\Permissions::allows(auth()->user(), 'treasury.manage');
        $activeAccounts = $accounts->where('is_active', true);
    @endphp

    <div class="space-y-5">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('treas.indexTitle') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('treas.indexSubtitle') }}</p>
            </div>
        </div>

        {{-- Total balance + per-account cards --}}
        <div class="rounded-lg border bg-card p-4 sm:p-6">
            <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{{ __('treas.totalBalance') }}</p>
            <p class="mt-1 text-2xl font-bold tabular-nums sm:text-3xl {{ $overview['total'] < 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($overview['total']) }}</p>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($overview['accounts'] as $account)
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-start justify-between gap-2">
                        <p class="min-w-0 truncate font-semibold">{{ $account->name }}</p>
                        @if ($account->is_active)
                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs text-emerald-700">
                                <span class="size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                {{ __('users.active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs text-red-600">
                                <span class="size-1.5 rounded-full bg-red-500" aria-hidden="true"></span>
                                {{ __('users.inactive') }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ __('treas.tk_'.$account->kind) }} · {{ __('labels.payMethod.'.$account->method) }}</p>
                    <p class="mt-2 text-lg font-bold tabular-nums {{ $account->balance < 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($account->balance) }}</p>
                </div>
            @endforeach
        </div>

        @if ($canManage)
            {{-- Transfer / manual movement / new account --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <section class="rounded-lg border bg-card">
                    <div class="border-b px-5 py-4">
                        <h2 class="text-base font-semibold">{{ __('treas.transfer') }}</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ __('treas.transferHelp') }}</p>
                    </div>
                    <form method="POST" action="{{ route('treasury.transfer') }}" class="space-y-3 px-5 py-4">
                        @csrf
                        <div>
                            <label for="transfer-from" class="block text-xs font-medium text-muted-foreground">{{ __('treas.transferFrom') }}</label>
                            <select id="transfer-from" name="from_account" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">—</option>
                                @foreach ($activeAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('from_account') === $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('from_account')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="transfer-to" class="block text-xs font-medium text-muted-foreground">{{ __('treas.transferTo') }}</label>
                            <select id="transfer-to" name="to_account" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">—</option>
                                @foreach ($activeAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('to_account') === $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('to_account')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="transfer-amount" class="block text-xs font-medium text-muted-foreground">{{ __('sup.amount') }}</label>
                                <input type="text" inputmode="decimal" id="transfer-amount" name="amount" value="{{ old('amount') }}" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('amount')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="transfer-date" class="block text-xs font-medium text-muted-foreground">{{ __('mat.issueDate') }}</label>
                                <input type="date" id="transfer-date" name="date" value="{{ old('date', now()->toDateString()) }}" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('date')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="transfer-note" class="block text-xs font-medium text-muted-foreground">{{ __('treas.transferNote') }}</label>
                            <input type="text" id="transfer-note" name="note" value="{{ old('note') }}" maxlength="500"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('treas.transfer') }}
                        </button>
                    </form>
                </section>

                <section class="rounded-lg border bg-card">
                    <div class="border-b px-5 py-4">
                        <h2 class="text-base font-semibold">{{ __('treas.manualMovement') }}</h2>
                    </div>
                    <form method="POST" action="{{ route('treasury.movement') }}" class="space-y-3 px-5 py-4">
                        @csrf
                        <div>
                            <label for="movement-account" class="block text-xs font-medium text-muted-foreground">{{ __('treas.account') }}</label>
                            <select id="movement-account" name="account" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">—</option>
                                @foreach ($activeAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('account') === $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('account')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="movement-direction" class="block text-xs font-medium text-muted-foreground">{{ __('treas.kind') }}</label>
                            <select id="movement-direction" name="direction" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="IN" {{ old('direction') === 'IN' ? 'selected' : '' }}>{{ __('treas.movementIn') }}</option>
                                <option value="OUT" {{ old('direction') === 'OUT' ? 'selected' : '' }}>{{ __('treas.movementOut') }}</option>
                            </select>
                            @error('direction')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="movement-amount" class="block text-xs font-medium text-muted-foreground">{{ __('sup.amount') }}</label>
                                <input type="text" inputmode="decimal" id="movement-amount" name="amount" value="{{ old('amount') }}" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('amount')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="movement-date" class="block text-xs font-medium text-muted-foreground">{{ __('mat.issueDate') }}</label>
                                <input type="date" id="movement-date" name="date" value="{{ old('date', now()->toDateString()) }}" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('date')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="movement-note" class="block text-xs font-medium text-muted-foreground">{{ __('treas.transferNote') }}</label>
                            <input type="text" id="movement-note" name="note" value="{{ old('note') }}" maxlength="500"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('treas.manualMovement') }}
                        </button>
                    </form>
                </section>

                <section class="rounded-lg border bg-card">
                    <div class="border-b px-5 py-4">
                        <h2 class="text-base font-semibold">{{ __('treas.addAccount') }}</h2>
                    </div>
                    <form method="POST" action="{{ route('treasury.accounts.store') }}" class="space-y-3 px-5 py-4">
                        @csrf
                        <div>
                            <label for="account-name" class="block text-xs font-medium text-muted-foreground">{{ __('gen.accountName') }}</label>
                            <input type="text" id="account-name" name="name" value="{{ old('name') }}" maxlength="120" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('name')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="account-kind" class="block text-xs font-medium text-muted-foreground">{{ __('treas.kind') }}</label>
                                <select id="account-kind" name="kind" required
                                        class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    @foreach (\App\Support\Atelier::TREASURY_KINDS as $kindOption)
                                        <option value="{{ $kindOption }}" {{ old('kind') === $kindOption ? 'selected' : '' }}>{{ __('treas.tk_'.$kindOption) }}</option>
                                    @endforeach
                                </select>
                                @error('kind')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="account-method" class="block text-xs font-medium text-muted-foreground">{{ __('pay.colMethod') }}</label>
                                <select id="account-method" name="method" required
                                        class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    @foreach (\App\Support\Atelier::PAYMENT_METHODS as $methodOption)
                                        <option value="{{ $methodOption }}" {{ old('method') === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                                    @endforeach
                                </select>
                                @error('method')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="account-opening" class="block text-xs font-medium text-muted-foreground">{{ __('treas.openingBalance') }}</label>
                            <input type="text" inputmode="decimal" id="account-opening" name="opening_balance" value="{{ old('opening_balance') }}"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('opening_balance')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="account-notes" class="block text-xs font-medium text-muted-foreground">{{ __('pay.fieldNotes') }}</label>
                            <textarea id="account-notes" name="notes" rows="2"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('treas.addAccount') }}
                        </button>
                    </form>
                </section>
            </div>
        @endif

        {{-- Cash journal, grouped by day (clôture journalière) --}}
        @if ($journal !== [])
            <section class="rounded-lg border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('treas.journal') }}</h2>
                </div>
                <div class="divide-y">
                    @foreach ($journal as $day)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold">{{ \Carbon\Carbon::parse($day['date'])->locale(app()->getLocale())->translatedFormat('l j F Y') }}</h3>
                                <p class="text-sm tabular-nums">
                                    <span class="font-medium text-emerald-700">{{ __('treas.dayIn') }} +{{ \App\Support\Money::format($day['in']) }}</span>
                                    <span class="mx-1 text-muted-foreground" aria-hidden="true">/</span>
                                    <span class="font-medium text-red-700">{{ __('treas.dayOut') }} −{{ \App\Support\Money::format($day['out']) }}</span>
                                </p>
                            </div>
                            <div class="mt-2 overflow-x-auto">
                                <table class="w-full min-w-[560px] text-sm">
                                    <tbody>
                                        @foreach ($day['movements'] as $movement)
                                            <tr class="border-t transition-colors hover:bg-muted/30">
                                                <td class="whitespace-nowrap py-2 pr-3 text-muted-foreground">{{ $movement->account?->name ?? '—' }}</td>
                                                <td class="whitespace-nowrap py-2 pr-3">{{ __('treas.k_'.$movement->kind) }}</td>
                                                <td class="max-w-40 py-2 pr-3"><span class="block truncate text-muted-foreground">{{ $movement->counterparty ?? '—' }}</span></td>
                                                <td class="max-w-44 py-2 pr-3"><span class="block truncate text-muted-foreground">{{ $movement->note ?? '' }}</span></td>
                                                <td class="whitespace-nowrap py-2 text-right font-semibold tabular-nums {{ $movement->direction === 'IN' ? 'text-emerald-700' : 'text-red-700' }}">
                                                    {{ $movement->direction === 'IN' ? '+' : '−' }}{{ \App\Support\Money::format($movement->amount) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Movements filter --}}
        <div class="rounded-lg border bg-card p-3 sm:p-4">
            <form method="GET" action="{{ route('treasury.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="treas-account" class="block text-xs font-medium text-muted-foreground">{{ __('treas.account') }}</label>
                    <select id="treas-account" name="account"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" {{ ($filters['account'] ?? '') === $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="treas-kind" class="block text-xs font-medium text-muted-foreground">{{ __('treas.kind') }}</label>
                    <select id="treas-kind" name="kind"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($kinds as $kindOption)
                            <option value="{{ $kindOption }}" {{ ($filters['kind'] ?? '') === $kindOption ? 'selected' : '' }}>{{ __('treas.k_'.$kindOption) }}</option>
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

        @if ($movements->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                    </div>
                    <p class="font-medium">{{ __('treas.movements') }}</p>
                </div>
            </div>
        @else
            {{-- Movements table --}}
            <div class="overflow-x-auto rounded-lg border bg-card">
                <table class="w-full min-w-[1000px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('mat.issueDate') }}</th>
                            <th class="px-4 py-3">{{ __('treas.account') }}</th>
                            <th class="px-4 py-3">{{ __('treas.kind') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('sup.amount') }}</th>
                            <th class="px-4 py-3">{{ __('treas.counterparty') }}</th>
                            <th class="px-4 py-3">{{ __('pay.fieldNotes') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('treas.reconciled') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $movement->movement_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $movement->account?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $movement->direction === 'IN' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                                        {{ $movement->direction === 'IN' ? '+' : '−' }} {{ __('treas.k_'.$movement->kind) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $movement->direction === 'IN' ? 'text-emerald-700' : 'text-red-700' }}">
                                    {{ $movement->direction === 'IN' ? '+' : '−' }}{{ \App\Support\Money::format($movement->amount) }}
                                </td>
                                <td class="max-w-36 px-4 py-3"><span class="block truncate">{{ $movement->counterparty ?? '—' }}</span></td>
                                <td class="max-w-44 px-4 py-3"><span class="block truncate text-muted-foreground">{{ $movement->note ?? '—' }}</span></td>
                                <td class="px-4 py-3 text-center">
                                    @if ($canManage)
                                        <form method="POST" action="{{ route('treasury.reconcile', $movement->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $movement->reconciled ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-zinc-50 text-zinc-600 hover:bg-muted' }}">
                                                {{ $movement->reconciled ? __('treas.reconciled') : __('treas.reconcile') }}
                                            </button>
                                        </form>
                                    @elseif ($movement->reconciled)
                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-emerald-700">{{ __('treas.reconciled') }}</span>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($movements->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $movements->total() }} · {{ $movements->currentPage() }} / {{ $movements->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($movements->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $movements->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($movements->hasMorePages())
                                <a href="{{ $movements->appends(request()->query())->nextPageUrl() }}"
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
