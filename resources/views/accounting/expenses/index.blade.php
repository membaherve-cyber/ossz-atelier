@extends('layouts.app')

@section('title', __('exp.title'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — workshop cost ledger (GET /expenses, perm
         * accounting.expenses; enhancement spec §7).
         *
         * Variables (ExpenseController@index): $expenses (with category,
         * order, responsibleStaff), $categories (ACTIVE categories),
         * $groups, $statuses, $orders, $staff, $filters, $canApprove,
         * $methods.
         *
         * Auto-generated rows (source_type set: incentive payouts, paid
         * purchases) carry an "auto" chip and cannot be edited/deleted
         * here; the review modal drives approve / reject / mark-paid.
         */

        $filtersActive = ($filters['q'] ?? '') !== ''
            || ($filters['category'] ?? '') !== ''
            || ($filters['group'] ?? '') !== ''
            || ($filters['status'] ?? '') !== ''
            || ($filters['from'] ?? '') !== ''
            || ($filters['to'] ?? '') !== '';

        // Status badge palette (DRAFT zinc / PENDING amber / APPROVED
        // emerald / REJECTED red / PAID teal).
        $statusPalette = [
            'DRAFT' => 'bg-zinc-50 text-zinc-600 border-zinc-200',
            'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200',
            'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'REJECTED' => 'bg-red-50 text-red-700 border-red-200',
            'PAID' => 'bg-teal-50 text-teal-700 border-teal-200',
        ];

        // Expense statuses the review actions still apply to.
        $reviewable = static fn (string $status): bool => in_array($status, ['DRAFT', 'PENDING', 'APPROVED'], true);
    @endphp

    <div class="space-y-5"
         x-data="{
            createOpen: {{ $errors->any() && old('formId') === 'expense-create' ? 'true' : 'false' }},
            categoriesOpen: {{ $errors->any() && old('formId') === 'category-create' ? 'true' : 'false' }},
            categoryMap: {{ \Illuminate\Support\Js::from($categories->mapWithKeys(fn ($c) => [$c->id => $c->group])) }},
            categoryId: @js(old('categoryId')),
            get categoryGroup() { return this.categoryMap[this.categoryId] || ''; }
         }">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('exp.title') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('exp.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="categoriesOpen = true"
                        class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                    {{ __('exp.manageCategories') }}
                </button>
                <button type="button" @click="createOpen = true"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('exp.record') }}
                </button>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="rounded-lg border bg-card p-3 sm:p-4">
            <form method="GET" action="{{ route('expenses.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="exp-q" class="block text-xs font-medium text-muted-foreground">{{ __('common.search') }}</label>
                    <input type="text" id="exp-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('exp.searchPlaceholder') }}"
                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="exp-category" class="block text-xs font-medium text-muted-foreground">{{ __('exp.filterCategory') }}</label>
                    <select id="exp-category" name="category"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('exp.allCategories') }}</option>
                        @foreach ($groups as $group)
                            @if ($categories->where('group', $group)->isNotEmpty())
                                <optgroup label="{{ __('labels.expGroup.'.$group) }}">
                                    @foreach ($categories->where('group', $group) as $category)
                                        <option value="{{ $category->id }}" {{ ($filters['category'] ?? '') === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="exp-group" class="block text-xs font-medium text-muted-foreground">{{ __('exp.filterGroup') }}</label>
                    <select id="exp-group" name="group"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('exp.allGroups') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group }}" {{ ($filters['group'] ?? '') === $group ? 'selected' : '' }}>{{ __('labels.expGroup.'.$group) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="exp-status" class="block text-xs font-medium text-muted-foreground">{{ __('exp.filterStatus') }}</label>
                    <select id="exp-status" name="status"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('exp.allStatuses') }}</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}" {{ ($filters['status'] ?? '') === $statusOption ? 'selected' : '' }}>{{ __('labels.expStatus.'.$statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="exp-from" class="block text-xs font-medium text-muted-foreground">{{ __('exp.fromDate') }}</label>
                        <input type="date" id="exp-from" name="from" value="{{ $filters['from'] ?? '' }}"
                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="exp-to" class="block text-xs font-medium text-muted-foreground">{{ __('exp.toDate') }}</label>
                        <input type="date" id="exp-to" name="to" value="{{ $filters['to'] ?? '' }}"
                               class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-4">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        {{ __('common.filter') }}
                    </button>
                    @if ($filtersActive)
                        <a href="{{ route('expenses.index') }}"
                           class="inline-flex items-center rounded-md px-4 py-2.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-foreground">
                            {{ __('exp.clear') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <p class="text-xs text-muted-foreground">{{ __('exp.approvalNote') }}</p>

        @if ($expenses->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </div>
                    <p class="font-medium">{{ $filtersActive ? __('exp.emptyFiltersTitle') : __('exp.emptyTitle') }}</p>
                    <p class="max-w-sm text-sm text-muted-foreground">{{ $filtersActive ? __('exp.emptyFiltersDesc') : __('exp.emptyDesc') }}</p>
                </div>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto rounded-lg border bg-card md:block">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('exp.colReference') }}</th>
                            <th class="px-4 py-3">{{ __('exp.colCategory') }}</th>
                            <th class="px-4 py-3">{{ __('exp.colDescription') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('exp.colAmount') }}</th>
                            <th class="px-4 py-3">{{ __('exp.colDate') }}</th>
                            <th class="px-4 py-3">{{ __('exp.colStatus') }}</th>
                            <th class="px-4 py-3">{{ __('exp.colPayee') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('exp.colActions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expenses as $expense)
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">
                                    <span class="inline-flex items-center gap-1.5">{{ $expense->reference }}
                                        @if ($expense->source_type !== null)
                                            <span title="{{ __('exp.purchaseHint') }}" class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-1.5 py-0.5 text-[10px] font-bold uppercase">auto</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="whitespace-nowrap">{{ $expense->category?->name ?? '—' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ __('labels.expGroup.'.$expense->category?->group) }}</p>
                                </td>
                                <td class="max-w-56 px-4 py-3"><span class="block truncate" title="{{ $expense->description }}">{{ $expense->description }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($expense->amount) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $expense->date_incurred->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$expense->status] ?? $statusPalette['DRAFT'] }}">{{ __('labels.expStatus.'.$expense->status) }}</span>
                                </td>
                                <td class="max-w-36 px-4 py-3"><span class="block truncate">{{ $expense->payee ?? '—' }}</span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($expense->receipt_path)
                                            <a href="{{ route('expenses.receipt', $expense->id) }}" target="_blank" rel="noopener"
                                               aria-label="{{ __('exp.viewReceipt') }} — {{ $expense->reference }}"
                                               class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                            </a>
                                        @endif
                                        @if ($canApprove && $reviewable($expense->status))
                                            <button type="button" @click="$dispatch('exp-review-{{ $expense->id }}')"
                                                    aria-label="{{ __('exp.colStatus') }} — {{ $expense->reference }}"
                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                            </button>
                                        @endif
                                        @if ($expense->source_type === null)
                                            <button type="button" @click="$dispatch('exp-edit-{{ $expense->id }}')"
                                                    aria-label="{{ __('common.edit') }} — {{ $expense->reference }}"
                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                            </button>
                                            <button type="button" @click="$dispatch('exp-delete-{{ $expense->id }}')"
                                                    aria-label="{{ __('common.delete') }} — {{ $expense->reference }}"
                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($expenses->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $expenses->total() }} · {{ $expenses->currentPage() }} / {{ $expenses->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($expenses->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $expenses->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($expenses->hasMorePages())
                                <a href="{{ $expenses->appends(request()->query())->nextPageUrl() }}"
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
                @foreach ($expenses as $expense)
                    <div class="rounded-xl border bg-card p-4 card-lift">
                        <div class="flex items-start justify-between gap-3">
                            <p class="inline-flex items-center gap-1.5 font-semibold">{{ $expense->reference }}
                                @if ($expense->source_type !== null)
                                    <span title="{{ __('exp.purchaseHint') }}" class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-1.5 py-0.5 text-[10px] font-bold uppercase">auto</span>
                                @endif
                            </p>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$expense->status] ?? $statusPalette['DRAFT'] }}">{{ __('labels.expStatus.'.$expense->status) }}</span>
                        </div>
                        <div class="mt-1.5 text-sm">
                            <p class="truncate font-medium">{{ $expense->description }}</p>
                            <p class="text-muted-foreground">{{ $expense->category?->name ?? '—' }} · {{ $expense->payee ?? '—' }}</p>
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <p class="text-xs text-muted-foreground">{{ $expense->date_incurred->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
                            <p class="text-lg font-bold tabular-nums">{{ \App\Support\Money::format($expense->amount) }}</p>
                        </div>
                        <div class="mt-3 flex items-center justify-end gap-1 border-t pt-3">
                            @if ($expense->receipt_path)
                                <a href="{{ route('expenses.receipt', $expense->id) }}" target="_blank" rel="noopener"
                                   aria-label="{{ __('exp.viewReceipt') }} — {{ $expense->reference }}"
                                   class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                </a>
                            @endif
                            @if ($canApprove && $reviewable($expense->status))
                                <button type="button" @click="$dispatch('exp-review-{{ $expense->id }}')"
                                        aria-label="{{ __('exp.colStatus') }} — {{ $expense->reference }}"
                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            @endif
                            @if ($expense->source_type === null)
                                <button type="button" @click="$dispatch('exp-edit-{{ $expense->id }}')"
                                        aria-label="{{ __('common.edit') }} — {{ $expense->reference }}"
                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </button>
                                <button type="button" @click="$dispatch('exp-delete-{{ $expense->id }}')"
                                        aria-label="{{ __('common.delete') }} — {{ $expense->reference }}"
                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Per-row modals (edit + review + delete) --}}
            @foreach ($expenses as $expense)
                @if ($expense->source_type === null)
                    {{-- Edit expense --}}
                    <div x-data="{ open: {{ $errors->any() && old('formId') === 'expense-edit-'.$expense->id ? 'true' : 'false' }}, group: categoryMap['{{ old('categoryId', $expense->expense_category_id) }}'] || '' }"
                         @exp-edit-{{ $expense->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('exp.editExpense') }}"
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
                                        <h2 class="text-base font-semibold">{{ __('exp.editExpense') }}</h2>
                                        <p class="mt-0.5 text-sm text-muted-foreground">{{ $expense->reference }}</p>
                                    </div>
                                    <button type="button" @click="open = false"
                                            aria-label="{{ __('common.close') }}"
                                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('expenses.update', $expense->id) }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="formId" value="expense-edit-{{ $expense->id }}" />
                                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label for="exp-edit-category-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldCategory') }}</label>
                                            <select id="exp-edit-category-{{ $expense->id }}" name="categoryId" required
                                                    @change="group = $event.target.selectedOptions[0].dataset.group || ''"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                @foreach ($groups as $group)
                                                    @if ($categories->where('group', $group)->isNotEmpty())
                                                        <optgroup label="{{ __('labels.expGroup.'.$group) }}">
                                                            @foreach ($categories->where('group', $group) as $category)
                                                                <option value="{{ $category->id }}" data-group="{{ $category->group }}" {{ old('categoryId', $expense->expense_category_id) === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('categoryId')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                            <p class="mt-2 text-xs text-muted-foreground" x-show="group === 'MATERIALS'" x-cloak>{{ __('exp.purchaseHint') }}</p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="exp-edit-desc-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldDescription') }}</label>
                                            <textarea id="exp-edit-desc-{{ $expense->id }}" name="description" rows="2" maxlength="500" required
                                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('description', $expense->description) }}</textarea>
                                            @error('description')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="exp-edit-amount-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldAmount') }}</label>
                                            <input type="text" inputmode="decimal" id="exp-edit-amount-{{ $expense->id }}" name="amount" value="{{ old('amount', $expense->amount) }}" required
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('amount')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="exp-edit-incurred-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldDateIncurred') }}</label>
                                            <input type="date" id="exp-edit-incurred-{{ $expense->id }}" name="dateIncurred" value="{{ old('dateIncurred', $expense->date_incurred->toDateString()) }}" required
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('dateIncurred')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="exp-edit-paid-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldDatePaid') }}</label>
                                            <input type="date" id="exp-edit-paid-{{ $expense->id }}" name="datePaid" value="{{ old('datePaid', $expense->date_paid?->toDateString() ?? '') }}"
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                        </div>
                                        <div>
                                            <label for="exp-edit-method-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldMethod') }}</label>
                                            <select id="exp-edit-method-{{ $expense->id }}" name="method"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                @foreach ($methods as $methodOption)
                                                    <option value="{{ $methodOption }}" {{ old('method', $expense->method ?? 'CASH') === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="exp-edit-payee-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldPayee') }}</label>
                                            <input type="text" id="exp-edit-payee-{{ $expense->id }}" name="payee" value="{{ old('payee', $expense->payee ?? '') }}" maxlength="120"
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                        </div>
                                        <div>
                                            <label for="exp-edit-order-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldOrder') }}</label>
                                            <select id="exp-edit-order-{{ $expense->id }}" name="orderId"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                <option value="">{{ __('exp.selectOrder') }}</option>
                                                @foreach ($orders as $order)
                                                    <option value="{{ $order->id }}" {{ old('orderId', $expense->order_id) === $order->id ? 'selected' : '' }}>{{ $order->order_number }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="exp-edit-staff-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldStaff') }}</label>
                                            <select id="exp-edit-staff-{{ $expense->id }}" name="staffId"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                <option value="">{{ __('exp.selectStaff') }}</option>
                                                @foreach ($staff as $staffMember)
                                                    <option value="{{ $staffMember->id }}" {{ old('staffId', $expense->responsible_staff_id) === $staffMember->id ? 'selected' : '' }}>{{ $staffMember->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="exp-edit-receipt-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldReceipt') }}</label>
                                            @if ($expense->receipt_path)
                                                <a href="{{ route('expenses.receipt', $expense->id) }}" target="_blank" rel="noopener" class="mb-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                                    {{ __('exp.fieldReceiptCurrent') }}
                                                </a>
                                            @endif
                                            <input type="file" id="exp-edit-receipt-{{ $expense->id }}" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('receipt')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="exp-edit-remarks-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldRemarks') }}</label>
                                            <textarea id="exp-edit-remarks-{{ $expense->id }}" name="remarks" rows="2"
                                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('remarks', $expense->remarks ?? '') }}</textarea>
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

                    {{-- Delete expense --}}
                    <div x-data="{ open: false }" @exp-delete-{{ $expense->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('exp.deleteExpense') }}"
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
                                    <h2 class="text-base font-semibold">{{ __('exp.deleteExpense') }}</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ __('exp.deleteConfirm') }}</p>
                                </div>
                                <form method="POST" action="{{ route('expenses.destroy', $expense->id) }}">
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
                @endif

                @if ($canApprove && $reviewable($expense->status))
                    {{-- Review expense (approve / mark paid / reject) --}}
                    <div x-data="{ open: false, showReason: false }" @exp-review-{{ $expense->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('exp.colStatus') }} — {{ $expense->reference }}"
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
                                        <h2 class="text-base font-semibold">{{ $expense->reference }}</h2>
                                        <p class="mt-0.5 text-sm text-muted-foreground">{{ $expense->description }}</p>
                                        <p class="mt-1 text-sm font-semibold tabular-nums">{{ \App\Support\Money::format($expense->amount) }}</p>
                                    </div>
                                    <button type="button" @click="open = false"
                                            aria-label="{{ __('common.close') }}"
                                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                                <div class="space-y-4 px-5 py-4">
                                    @if (in_array($expense->status, ['DRAFT', 'PENDING'], true))
                                        <form method="POST" action="{{ route('expenses.review', $expense->id) }}">
                                            @csrf
                                            <input type="hidden" name="decision" value="approve" />
                                            <p class="text-xs text-muted-foreground">{{ __('exp.approveConfirm') }}</p>
                                            <button type="submit"
                                                    class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 btn-press">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                                                {{ __('exp.reviewApprove') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if ($expense->status === 'APPROVED')
                                        <form method="POST" action="{{ route('expenses.review', $expense->id) }}">
                                            @csrf
                                            <input type="hidden" name="decision" value="markPaid" />
                                            <p class="text-xs text-muted-foreground">{{ __('exp.markPaidConfirm') }}</p>
                                            <button type="submit"
                                                    class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-md bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-teal-700 btn-press">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M2 7v10"/><path d="M2 12h20"/><path d="M22 7v10"/><path d="M2 7h20"/></svg>
                                                {{ __('exp.reviewMarkPaid') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if (in_array($expense->status, ['DRAFT', 'PENDING'], true))
                                        <form method="POST" action="{{ route('expenses.review', $expense->id) }}">
                                            @csrf
                                            <input type="hidden" name="decision" value="reject" />
                                            <div x-show="showReason" x-cloak>
                                                <label for="exp-reject-reason-{{ $expense->id }}" class="mb-1.5 block text-sm font-medium">{{ __('exp.rejectReason') }}</label>
                                                <textarea id="exp-reject-reason-{{ $expense->id }}" name="reason" rows="2" required
                                                          class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('reason') }}</textarea>
                                                @error('reason')
                                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                                @enderror
                                                <button type="submit"
                                                        class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-md bg-destructive px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-destructive/90 btn-press">
                                                    {{ __('exp.rejectConfirm') }}
                                                </button>
                                            </div>
                                            <button type="button" x-show="!showReason" @click="showReason = true"
                                                    class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold text-red-700 transition-colors hover:bg-red-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                {{ __('exp.reviewReject') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif

        {{-- Record expense modal --}}
        <div x-show="createOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('exp.record') }}"
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
                        <h2 class="text-base font-semibold">{{ __('exp.record') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('exp.subtitle') }}</p>
                    </div>
                    <button type="button" @click="createOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="formId" value="expense-create" />
                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="exp-category" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldCategory') }}</label>
                            <select id="exp-category" name="categoryId" x-model="categoryId" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($groups as $group)
                                    @if ($categories->where('group', $group)->isNotEmpty())
                                        <optgroup label="{{ __('labels.expGroup.'.$group) }}">
                                            @foreach ($categories->where('group', $group) as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            @error('categoryId')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                            <p class="mt-2 text-xs text-muted-foreground" x-show="categoryGroup === 'MATERIALS'" x-cloak>{{ __('exp.purchaseHint') }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="exp-desc" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldDescription') }}</label>
                            <textarea id="exp-desc" name="description" rows="2" maxlength="500" required
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="exp-amount" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldAmount') }}</label>
                            <input type="text" inputmode="decimal" id="exp-amount" name="amount" value="{{ old('amount') }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('amount')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="exp-incurred" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldDateIncurred') }}</label>
                            <input type="date" id="exp-incurred" name="dateIncurred" value="{{ old('dateIncurred', now()->toDateString()) }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('dateIncurred')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="exp-paid" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldDatePaid') }}</label>
                            <input type="date" id="exp-paid" name="datePaid" value="{{ old('datePaid') }}"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <div>
                            <label for="exp-method" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldMethod') }}</label>
                            <select id="exp-method" name="method"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($methods as $methodOption)
                                    <option value="{{ $methodOption }}" {{ old('method', 'CASH') === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="exp-payee" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldPayee') }}</label>
                            <input type="text" id="exp-payee" name="payee" value="{{ old('payee') }}" maxlength="120"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                        </div>
                        <div>
                            <label for="exp-order" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldOrder') }}</label>
                            <select id="exp-order" name="orderId"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">{{ __('exp.selectOrder') }}</option>
                                @foreach ($orders as $order)
                                    <option value="{{ $order->id }}" {{ old('orderId') === $order->id ? 'selected' : '' }}>{{ $order->order_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="exp-staff" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldStaff') }}</label>
                            <select id="exp-staff" name="staffId"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">{{ __('exp.selectStaff') }}</option>
                                @foreach ($staff as $staffMember)
                                    <option value="{{ $staffMember->id }}" {{ old('staffId') === $staffMember->id ? 'selected' : '' }}>{{ $staffMember->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="exp-receipt" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldReceipt') }}</label>
                            <input type="file" id="exp-receipt" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('receipt')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="exp-remarks" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldRemarks') }}</label>
                            <textarea id="exp-remarks" name="remarks" rows="2"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('remarks') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="exp-status" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldStatus') }}</label>
                            <select id="exp-status" name="status"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($statuses as $statusOption)
                                    <option value="{{ $statusOption }}" {{ old('status', 'PENDING') === $statusOption ? 'selected' : '' }}>{{ __('labels.expStatus.'.$statusOption) }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-muted-foreground">{{ __('exp.draftHint') }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="createOpen = false"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('exp.record') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Categories manager modal --}}
        <div x-show="categoriesOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('exp.categoriesTitle') }}"
             @keydown.escape.window="categoriesOpen = false">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="categoriesOpen = false" aria-hidden="true"
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
                        <h2 class="text-base font-semibold">{{ __('exp.categoriesTitle') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('exp.categoriesSubtitle') }}</p>
                    </div>
                    <button type="button" @click="categoriesOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-4">
                    @if ($categories->isEmpty())
                        <p class="py-4 text-center text-sm text-muted-foreground">{{ __('exp.noCategories') }}</p>
                    @else
                        <ul class="divide-y">
                            @foreach ($categories as $category)
                                <li class="flex items-center justify-between gap-3 py-2.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">
                                            {{ $category->name }}
                                            <span class="ml-1.5 inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-zinc-500">{{ $category->is_system ? __('exp.systemCategory') : __('exp.customCategory') }}</span>
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ __('labels.expGroup.'.$category->group) }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('expenses.categories.update', $category->id) }}" class="shrink-0">
                                        @csrf
                                        <input type="hidden" name="isActive" value="{{ $category->is_active ? '0' : '1' }}" />
                                        <button type="submit" aria-label="{{ __('exp.fieldActive') }} — {{ $category->name }}"
                                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-colors {{ $category->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-zinc-200 bg-zinc-50 text-zinc-500 hover:bg-zinc-100' }}">
                                            <span class="inline-block size-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-600' : 'bg-zinc-400' }}"></span>
                                            {{ __('exp.fieldActive') }}
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('expenses.categories.store') }}" class="mt-4 grid gap-3 border-t pt-4 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="formId" value="category-create" />
                        <div>
                            <label for="exp-new-category-name" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldName') }}</label>
                            <input type="text" id="exp-new-category-name" name="name" value="{{ old('name') }}" maxlength="120" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('name')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="exp-new-category-group" class="mb-1.5 block text-sm font-medium">{{ __('exp.fieldGroup') }}</label>
                            <select id="exp-new-category-group" name="group"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($groups as $group)
                                    <option value="{{ $group }}" {{ old('group', 'MATERIALS') === $group ? 'selected' : '' }}>{{ __('labels.expGroup.'.$group) }}</option>
                                @endforeach
                            </select>
                            @error('group')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                {{ __('exp.newCategory') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
