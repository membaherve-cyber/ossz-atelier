@extends('layouts.app')

@section('title', __('bud.indexTitle'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — budgets & forecasts (GET /budgets, perm
         * budgets.view; Finance & Comptabilité spec v1.3 §8).
         *
         * Variables (BudgetController@index): $budgets (paginated, each
         * carrying a ->comparison attribute from BudgetService —
         * revenue / expense_total / per-line planned vs actual / alerts)
         * and $categories (active expense categories).
         *
         * Actuals are always derived from the recorded payments and
         * expenses of the budget's period, so planned-vs-real never
         * goes stale. Overrun alerts render as red banners on ACTIVE
         * budgets; writes are budgets.manage-gated.
         */

        $canManage = \App\Services\Permissions::allows(auth()->user(), 'budgets.manage');

        $statusPalette = [
            'ACTIVE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CLOSED' => 'bg-zinc-50 text-zinc-600 border-zinc-200',
        ];
    @endphp

    <div class="space-y-5"
         x-data="{ createOpen: {{ $errors->any() && old('formId') === 'budget-create' ? 'true' : 'false' }} }">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('bud.indexTitle') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('bud.indexSubtitle') }}</p>
            </div>
            @if ($canManage)
                <button type="button" @click="createOpen = true"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('bud.newBudget') }}
                </button>
            @endif
        </div>

        @if ($budgets->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m12 8 4 4-4 4"/><path d="m8 12 4 4 4-4"/></svg>
                    </div>
                    <p class="font-medium">{{ __('bud.noBudgets') }}</p>
                </div>
            </div>
        @else
            {{-- Budget cards --}}
            <div class="space-y-4">
                @foreach ($budgets as $budget)
                    @php($comparison = $budget->comparison)
                    <section class="rounded-lg border bg-card">
                        <div class="flex flex-col justify-between gap-3 border-b px-5 py-4 sm:flex-row sm:items-start">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-semibold">{{ $budget->label }}</h2>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$budget->status] ?? $statusPalette['CLOSED'] }}">{{ __('bud.st_'.$budget->status) }}</span>
                                </div>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    {{ __('bud.period') }}: {{ $budget->period_start->locale(app()->getLocale())->translatedFormat('j M Y') }} → {{ $budget->period_end->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                </p>
                                @if ($budget->notes)
                                    <p class="mt-1 max-w-xl text-xs text-muted-foreground">{{ $budget->notes }}</p>
                                @endif
                            </div>
                            @if ($canManage && $budget->status === 'ACTIVE')
                                <form method="POST" action="{{ route('budgets.close', $budget->id) }}"
                                      onsubmit="return confirm('{{ __('bud.close') }} — {{ $budget->label }} ?');">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                                        {{ __('bud.close') }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- Overrun alerts (§8) --}}
                        @if ($comparison['alerts'] !== [])
                            <div class="space-y-2 px-5 pt-4">
                                @foreach ($comparison['alerts'] as $alert)
                                    <div class="flex gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-red-800" role="alert">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 size-4 shrink-0" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                        <p class="text-sm font-medium">{{ $alert }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Planned vs actual --}}
                        <div class="overflow-x-auto px-5 py-4">
                            <table class="w-full min-w-[640px] text-sm">
                                <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th class="px-3 py-2"></th>
                                        <th class="px-3 py-2 text-right">{{ __('bud.planned') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('bud.actual') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('bud.variance') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-t transition-colors hover:bg-muted/30">
                                        <td class="px-3 py-2.5 font-medium">{{ __('gen.revenue') }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::format($comparison['revenue']['planned']) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::format($comparison['revenue']['actual']) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums {{ $comparison['revenue']['variance'] < 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($comparison['revenue']['variance']) }}</td>
                                    </tr>
                                    @foreach ($comparison['lines'] as $row)
                                        @php
                                            $rowLabel = $row['category']?->name
                                                ?? ($row['line']->note !== null && $row['line']->note !== '' ? $row['line']->note : __('gen.expense'));
                                        @endphp
                                        <tr class="border-t transition-colors hover:bg-muted/30">
                                            <td class="max-w-56 px-3 py-2.5"><span class="block truncate">{{ $rowLabel }}</span></td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::format($row['planned']) }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ \App\Support\Money::format($row['actual']) }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums {{ $row['actual'] > $row['planned'] ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($row['actual'] - $row['planned']) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-t bg-muted/20">
                                        <td class="px-3 py-2.5 font-semibold">{{ __('gen.expense') }} — {{ __('gen.total') }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($comparison['expense_total']['planned']) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($comparison['expense_total']['actual']) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums {{ $comparison['expense_total']['variance'] > 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($comparison['expense_total']['variance']) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Add a planned line (ACTIVE + manage) --}}
                        @if ($canManage && $budget->status === 'ACTIVE')
                            <div class="border-t px-5 py-4" x-data="{ kind: 'EXPENSE' }">
                                <form method="POST" action="{{ route('budgets.lines.store', $budget->id) }}"
                                      class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <div>
                                        <label for="budget-kind-{{ $budget->id }}" class="block text-xs font-medium text-muted-foreground">{{ __('treas.kind') }}</label>
                                        <select id="budget-kind-{{ $budget->id }}" name="kind" x-model="kind"
                                                class="mt-1 w-36 rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                            <option value="EXPENSE">{{ __('gen.expense') }}</option>
                                            <option value="REVENUE">{{ __('gen.revenue') }}</option>
                                        </select>
                                        @error('kind')
                                            <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div x-show="kind === 'EXPENSE'">
                                        <label for="budget-category-{{ $budget->id }}" class="block text-xs font-medium text-muted-foreground">{{ __('mat.category') }}</label>
                                        <select id="budget-category-{{ $budget->id }}" name="expense_category_id"
                                                class="mt-1 w-48 rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                            <option value="">—</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('expense_category_id')
                                            <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="budget-planned-{{ $budget->id }}" class="block text-xs font-medium text-muted-foreground">{{ __('bud.planned') }}</label>
                                        <input type="text" inputmode="decimal" id="budget-planned-{{ $budget->id }}" name="planned" value="{{ old('planned') }}" required
                                               class="mt-1 w-36 rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                        @error('planned')
                                            <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                        {{ __('bud.addLine') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($budgets->hasPages())
                <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p class="text-sm text-muted-foreground tabular-nums">{{ $budgets->total() }} · {{ $budgets->currentPage() }} / {{ $budgets->lastPage() }}</p>
                    <div class="flex items-center gap-2">
                        @if ($budgets->onFirstPage())
                            <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </span>
                        @else
                            <a href="{{ $budgets->appends(request()->query())->previousPageUrl() }}"
                               class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </a>
                        @endif
                        @if ($budgets->hasMorePages())
                            <a href="{{ $budgets->appends(request()->query())->nextPageUrl() }}"
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

        {{-- New budget modal --}}
        @if ($canManage)
            <div x-show="createOpen" style="display: none;" x-cloak
                 class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                 role="dialog" aria-modal="true" aria-label="{{ __('bud.newBudget') }}"
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
                            <h2 class="text-base font-semibold">{{ __('bud.newBudget') }}</h2>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('bud.indexSubtitle') }}</p>
                        </div>
                        <button type="button" @click="createOpen = false"
                                aria-label="{{ __('common.close') }}"
                                class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('budgets.store') }}">
                        @csrf
                        <input type="hidden" name="formId" value="budget-create" />
                        <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="budget-label" class="mb-1.5 block text-sm font-medium">{{ __('bud.label') }}</label>
                                <input type="text" id="budget-label" name="label" value="{{ old('label') }}" maxlength="120" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('label')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="budget-period-start" class="mb-1.5 block text-sm font-medium">{{ __('bud.periodStart') }}</label>
                                <input type="date" id="budget-period-start" name="period_start" value="{{ old('period_start') }}" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('period_start')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="budget-period-end" class="mb-1.5 block text-sm font-medium">{{ __('bud.periodEnd') }}</label>
                                <input type="date" id="budget-period-end" name="period_end" value="{{ old('period_end') }}" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('period_end')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="budget-planned-revenue" class="mb-1.5 block text-sm font-medium">{{ __('bud.plannedRevenue') }}</label>
                                <input type="text" inputmode="decimal" id="budget-planned-revenue" name="planned_revenue" value="{{ old('planned_revenue') }}"
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('planned_revenue')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="budget-notes" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldNotes') }}</label>
                                <textarea id="budget-notes" name="notes" rows="2"
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
                                {{ __('common.create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
