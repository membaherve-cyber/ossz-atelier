@extends('layouts.app')

@section('title', __('mat.indexTitle'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — materials & stock (GET /materials, perm
         * materials.view; Finance & Comptabilité spec v1.3 §5).
         *
         * Variables (MaterialController@index): $materials (paginated
         * master list), $movements (latest stock-ledger rows),
         * $transfers (employee-to-employee transfers), $categories
         * (Atelier::MATERIAL_CATEGORIES), $dashboard (per-employee rows
         * from MaterialService::employeeDashboard), $filters (q,
         * category), $canManage, $canIssue.
         *
         * Editing a material and adjusting its stock share one modal
         * (POST materials.update); creating a material opens the header
         * modal (POST materials.store). Client-supplied materials stay
         * traceable but are never valued (§5.15).
         */
    @endphp

    <div class="space-y-5"
         x-data="{ createOpen: {{ $errors->any() && old('formId') === 'material-create' ? 'true' : 'false' }} }">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('mat.indexTitle') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('mat.indexSubtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canIssue)
                    <a href="{{ route('materials.issues.create') }}"
                       class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m7 9 1.4-2.8A2 2 0 0 1 10.2 5h3.6a2 2 0 0 1 1.8 1.2L17 9"/><path d="M3.6 9h16.8a1.2 1.2 0 0 1 1.2 1.2v2.4a1.2 1.2 0 0 1-1.2 1.2h-3.1l-1.6-2.4-2 4-1.6-2.4H3.6a1.2 1.2 0 0 1-1.2-1.2v-2.4A1.2 1.2 0 0 1 3.6 9Z"/><path d="M5 13.8v5A1.2 1.2 0 0 0 6.2 20h11.6a1.2 1.2 0 0 0 1.2-1.2v-5"/></svg>
                        {{ __('mat.newIssue') }}
                    </a>
                @endif
                @if ($canManage)
                    <button type="button" @click="createOpen = true"
                            class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        {{ __('mat.addMaterial') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="rounded-lg border bg-card p-3 sm:p-4">
            <form method="GET" action="{{ route('materials.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <label for="mat-q" class="block text-xs font-medium text-muted-foreground">{{ __('common.search') }}</label>
                    <input type="text" id="mat-q" name="q" value="{{ $filters['q'] ?? '' }}"
                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="mat-category" class="block text-xs font-medium text-muted-foreground">{{ __('mat.category') }}</label>
                    <select id="mat-category" name="category"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($categories as $categoryOption)
                            <option value="{{ $categoryOption }}" {{ ($filters['category'] ?? '') === $categoryOption ? 'selected' : '' }}>{{ __('mat.cat_'.$categoryOption) }}</option>
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

        @if ($materials->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-lg border bg-card">
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </div>
                    <p class="font-medium">—</p>
                </div>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto rounded-lg border bg-card md:block">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('mat.materialName') }}</th>
                            <th class="px-4 py-3">{{ __('mat.category') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.stockQty') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.avgCost') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.stockValue') }}</th>
                            @if ($canManage)
                                <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($materials as $material)
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="max-w-56 px-4 py-3">
                                    <p class="truncate font-medium">{{ $material->name }}</p>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                        @if ($material->reference)
                                            <span class="text-xs text-muted-foreground">{{ $material->reference }}</span>
                                        @endif
                                        @if ($material->is_client_supplied)
                                            <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[10px] font-semibold text-zinc-600 whitespace-nowrap">{{ __('mat.clientSupplied') }}</span>
                                        @endif
                                        @if (! $material->is_active)
                                            <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 whitespace-nowrap">{{ __('users.inactive') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ __('mat.cat_'.$material->category) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $material->stock_qty + 0 }} {{ $material->unit }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">
                                    {{ $material->is_client_supplied ? '—' : \App\Support\Money::format($material->avg_cost) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($material->stockValue()) }}</td>
                                @if ($canManage)
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" @click="$dispatch('mat-edit-{{ $material->id }}')"
                                                    aria-label="{{ __('common.edit') }} — {{ $material->name }}"
                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($materials->hasPages())
                    <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                        <p class="text-sm text-muted-foreground tabular-nums">{{ $materials->total() }} · {{ $materials->currentPage() }} / {{ $materials->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($materials->onFirstPage())
                                <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </span>
                            @else
                                <a href="{{ $materials->appends(request()->query())->previousPageUrl() }}"
                                   class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </a>
                            @endif
                            @if ($materials->hasMorePages())
                                <a href="{{ $materials->appends(request()->query())->nextPageUrl() }}"
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
                @foreach ($materials as $material)
                    <div class="rounded-xl border bg-card p-4 card-lift">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $material->name }}</p>
                                <p class="text-xs text-muted-foreground">{{ __('mat.cat_'.$material->category) }}@if ($material->reference) · {{ $material->reference }}@endif</p>
                            </div>
                            @if ($canManage)
                                <button type="button" @click="$dispatch('mat-edit-{{ $material->id }}')"
                                        aria-label="{{ __('common.edit') }} — {{ $material->name }}"
                                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </button>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            @if ($material->is_client_supplied)
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[10px] font-semibold text-zinc-600">{{ __('mat.clientSupplied') }}</span>
                            @endif
                            @if (! $material->is_active)
                                <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">{{ __('users.inactive') }}</span>
                            @endif
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3 border-t pt-3">
                            <div class="text-xs text-muted-foreground">
                                <p>{{ __('mat.stockQty') }}: <span class="font-medium text-foreground tabular-nums">{{ $material->stock_qty + 0 }} {{ $material->unit }}</span></p>
                                <p class="mt-0.5">{{ __('mat.avgCost') }}: <span class="font-medium text-foreground">{{ $material->is_client_supplied ? '—' : \App\Support\Money::format($material->avg_cost) }}</span></p>
                            </div>
                            <p class="text-lg font-bold tabular-nums">{{ \App\Support\Money::format($material->stockValue()) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Per-row edit modals (master data + stock adjustment) --}}
            @if ($canManage)
                @foreach ($materials as $material)
                    <div x-data="{ open: {{ $errors->any() && old('formId') === 'material-edit-'.$material->id ? 'true' : 'false' }} }"
                         @mat-edit-{{ $material->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('mat.editMaterial') }}"
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
                                        <h2 class="text-base font-semibold">{{ __('mat.editMaterial') }}</h2>
                                        <p class="mt-0.5 truncate text-sm text-muted-foreground">{{ $material->name }}</p>
                                    </div>
                                    <button type="button" @click="open = false"
                                            aria-label="{{ __('common.close') }}"
                                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('materials.update', $material->id) }}">
                                    @csrf
                                    <input type="hidden" name="formId" value="material-edit-{{ $material->id }}" />
                                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label for="mat-edit-name-{{ $material->id }}" class="mb-1.5 block text-sm font-medium">{{ __('mat.materialName') }}</label>
                                            <input type="text" id="mat-edit-name-{{ $material->id }}" name="name" value="{{ old('name', $material->name) }}" maxlength="190" required
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('name')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="mat-edit-ref-{{ $material->id }}" class="mb-1.5 block text-sm font-medium">{{ __('mat.reference') }}</label>
                                            <input type="text" id="mat-edit-ref-{{ $material->id }}" name="reference" value="{{ old('reference', $material->reference ?? '') }}" maxlength="60"
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                        </div>
                                        <div>
                                            <label for="mat-edit-category-{{ $material->id }}" class="mb-1.5 block text-sm font-medium">{{ __('mat.category') }}</label>
                                            <select id="mat-edit-category-{{ $material->id }}" name="category"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                @foreach ($categories as $categoryOption)
                                                    <option value="{{ $categoryOption }}" {{ old('category', $material->category) === $categoryOption ? 'selected' : '' }}>{{ __('mat.cat_'.$categoryOption) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="mat-edit-unit-{{ $material->id }}" class="mb-1.5 block text-sm font-medium">{{ __('mat.unit') }}</label>
                                            <input type="text" id="mat-edit-unit-{{ $material->id }}" name="unit" value="{{ old('unit', $material->unit) }}" maxlength="12" required
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('unit')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <span class="mb-1.5 block text-sm font-medium">{{ __('mat.clientSupplied') }}</span>
                                            <label class="flex min-h-11 items-center gap-2 text-sm">
                                                <input type="checkbox" name="is_client_supplied" value="1" {{ old('is_client_supplied', $material->is_client_supplied) ? 'checked' : '' }}
                                                       class="size-4 rounded border-input text-primary accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                                {{ __('common.yes') }}
                                            </label>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <span class="mb-1.5 block text-sm font-medium">{{ __('users.isActiveLabel') }}</span>
                                            <label class="flex min-h-11 items-center gap-2 text-sm">
                                                <input type="hidden" name="is_active" value="0" />
                                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $material->is_active) ? 'checked' : '' }}
                                                       class="size-4 rounded border-input text-primary accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                                {{ __('users.active') }}
                                            </label>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="mat-edit-notes-{{ $material->id }}" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldNotes') }}</label>
                                            <textarea id="mat-edit-notes-{{ $material->id }}" name="notes" rows="2"
                                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes', $material->notes ?? '') }}</textarea>
                                        </div>
                                        <div class="rounded-lg border bg-muted/30 p-3 sm:col-span-2">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('mat.adjustQty') }}</p>
                                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label for="mat-edit-adjust-qty-{{ $material->id }}" class="mb-1.5 block text-xs font-medium text-muted-foreground">{{ __('mat.transferQty') }}</label>
                                                    <input type="text" inputmode="decimal" id="mat-edit-adjust-qty-{{ $material->id }}" name="adjust_qty" value="{{ old('adjust_qty') }}" placeholder="0"
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                    @error('adjust_qty')
                                                        <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label for="mat-edit-adjust-note-{{ $material->id }}" class="mb-1.5 block text-xs font-medium text-muted-foreground">{{ __('mat.adjustNote') }}</label>
                                                    <input type="text" id="mat-edit-adjust-note-{{ $material->id }}" name="adjust_note" value="{{ old('adjust_note') }}" maxlength="500"
                                                           class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                </div>
                                            </div>
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
            @endif
        @endif

        {{-- Add material modal --}}
        @if ($canManage)
            <div x-show="createOpen" style="display: none;" x-cloak
                 class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                 role="dialog" aria-modal="true" aria-label="{{ __('mat.addMaterial') }}"
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
                            <h2 class="text-base font-semibold">{{ __('mat.addMaterial') }}</h2>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('mat.indexSubtitle') }}</p>
                        </div>
                        <button type="button" @click="createOpen = false"
                                aria-label="{{ __('common.close') }}"
                                class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('materials.store') }}">
                        @csrf
                        <input type="hidden" name="formId" value="material-create" />
                        <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="mat-create-name" class="mb-1.5 block text-sm font-medium">{{ __('mat.materialName') }}</label>
                                <input type="text" id="mat-create-name" name="name" value="{{ old('name') }}" maxlength="190" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('name')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="mat-create-ref" class="mb-1.5 block text-sm font-medium">{{ __('mat.reference') }}</label>
                                <input type="text" id="mat-create-ref" name="reference" value="{{ old('reference') }}" maxlength="60"
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            </div>
                            <div>
                                <label for="mat-create-category" class="mb-1.5 block text-sm font-medium">{{ __('mat.category') }}</label>
                                <select id="mat-create-category" name="category"
                                        class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    @foreach ($categories as $categoryOption)
                                        <option value="{{ $categoryOption }}" {{ old('category') === $categoryOption ? 'selected' : '' }}>{{ __('mat.cat_'.$categoryOption) }}</option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="mat-create-unit" class="mb-1.5 block text-sm font-medium">{{ __('mat.unit') }}</label>
                                <input type="text" id="mat-create-unit" name="unit" value="{{ old('unit') }}" maxlength="12" required
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('unit')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <span class="mb-1.5 block text-sm font-medium">{{ __('mat.clientSupplied') }}</span>
                                <label class="flex min-h-11 items-center gap-2 text-sm">
                                    <input type="checkbox" name="is_client_supplied" value="1" {{ old('is_client_supplied') ? 'checked' : '' }}
                                           class="size-4 rounded border-input text-primary accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                    {{ __('common.yes') }}
                                </label>
                            </div>
                            <div>
                                <label for="mat-create-opening-qty" class="mb-1.5 block text-sm font-medium">{{ __('mat.openingQty') }}</label>
                                <input type="text" inputmode="decimal" id="mat-create-opening-qty" name="opening_qty" value="{{ old('opening_qty') }}"
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('opening_qty')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="mat-create-opening-cost" class="mb-1.5 block text-sm font-medium">{{ __('mat.openingCost') }}</label>
                                <input type="text" inputmode="decimal" id="mat-create-opening-cost" name="opening_cost" value="{{ old('opening_cost') }}"
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                @error('opening_cost')
                                    <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="mat-create-notes" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldNotes') }}</label>
                                <textarea id="mat-create-notes" name="notes" rows="2"
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

        {{-- Per-employee analytical dashboard (§5.14) --}}
        @if ($dashboard !== [])
            <section class="rounded-lg border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('mat.employeeDashboard') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-sm">
                        <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">{{ __('mat.employee') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.issuesCount') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.received') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.qtyConsumed') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.qtyReturned') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.loss') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.consumedValue') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.lossValue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dashboard as $row)
                                <tr class="border-t transition-colors hover:bg-muted/30">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $row['staff_name'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ (int) $row['issues'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['received'] + 0 }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['consumed'] + 0 }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['returned'] + 0 }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $row['loss'] > 0 ? 'text-red-700' : '' }}">{{ $row['loss'] + 0 }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \App\Support\Money::format($row['consumed_value']) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums {{ $row['loss_value'] > 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::format($row['loss_value']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Stock movements ledger (§5.9) --}}
        @if ($movements->isNotEmpty())
            <section class="rounded-lg border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('mat.ledger') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">{{ __('mat.issueDate') }}</th>
                                <th class="px-4 py-3">{{ __('mat.materialName') }}</th>
                                <th class="px-4 py-3">{{ __('treas.kind') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.transferQty') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('sup.amount') }}</th>
                                <th class="px-4 py-3">{{ __('pay.colRecordedBy') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($movements as $movement)
                                <tr class="border-t transition-colors hover:bg-muted/30">
                                    <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $movement->created_at->locale(app()->getLocale())->translatedFormat('j M Y, H:i') }}</td>
                                    <td class="max-w-44 px-4 py-3"><span class="block truncate">{{ $movement->material?->name ?? '—' }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ __('mat.mk_'.$movement->kind) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $movement->quantity < 0 ? 'text-red-700' : '' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity + 0 }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \App\Support\Money::format($movement->value) }}</td>
                                    <td class="max-w-36 px-4 py-3"><span class="block truncate text-muted-foreground">{{ $movement->created_by_name ?? '—' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Employee-to-employee transfers (§5.11) --}}
        @if ($transfers->isNotEmpty())
            <section class="rounded-lg border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('mat.transfersTitle') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-sm">
                        <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">{{ __('mat.issueDate') }}</th>
                                <th class="px-4 py-3">{{ __('mat.materialName') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.transferQty') }}</th>
                                <th class="px-4 py-3">{{ __('mat.transfersTitle') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfers as $transfer)
                                <tr class="border-t transition-colors hover:bg-muted/30">
                                    <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $transfer->created_at->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                    <td class="max-w-44 px-4 py-3"><span class="block truncate">{{ $transfer->material?->name ?? '—' }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $transfer->qty + 0 }} {{ $transfer->material?->unit }}</td>
                                    <td class="max-w-56 px-4 py-3">
                                        <p class="truncate">{{ $transfer->from_staff_name }} → {{ $transfer->to_staff_name }}</p>
                                        @if ($transfer->note)
                                            <p class="truncate text-xs text-muted-foreground">{{ $transfer->note }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection
