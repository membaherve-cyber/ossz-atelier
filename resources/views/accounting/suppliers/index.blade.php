@extends('layouts.app')

@section('title', __('sup.title'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — suppliers and material purchases (GET /suppliers,
         * perm accounting.suppliers; enhancement spec §10).
         *
         * Variables (SupplierController@index): $suppliers (with
         * purchases_count), $purchases (paginated, with supplier + order),
         * $orders, $materialCategories, $paymentStatuses, $methods, $tab.
         *
         * Marking a purchase PAID books exactly ONE ledger expense row
         * (source_type=PURCHASE) automatically — see sup.autoExpenseNote.
         * Suppliers with recorded purchases cannot be deleted.
         */

        // Payment badge palette (UNPAID amber / PARTIAL orange / PAID emerald).
        $paymentPalette = [
            'UNPAID' => 'bg-amber-50 text-amber-700 border-amber-200',
            'PARTIAL' => 'bg-orange-50 text-orange-700 border-orange-200',
            'PAID' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        ];
    @endphp

    <div class="space-y-5"
         x-data="{
            supplierCreateOpen: {{ $errors->any() && old('formId') === 'supplier-create' ? 'true' : 'false' }},
            purchaseCreateOpen: {{ $errors->any() && old('formId') === 'purchase-create' ? 'true' : 'false' }}
         }">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('sup.title') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('sup.subtitle') }}</p>
            </div>
        </div>

        {{-- Tabs (GET links — the server round-trip keeps the URL shareable) --}}
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('suppliers.index', ['tab' => 'suppliers']) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $tab === 'suppliers' ? 'bg-primary text-primary-foreground' : 'border bg-background text-muted-foreground hover:text-foreground' }}"
               @if ($tab === 'suppliers') aria-current="true" @endif>{{ __('sup.tabSuppliers') }}</a>
            <a href="{{ route('suppliers.index', ['tab' => 'purchases']) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $tab === 'purchases' ? 'bg-primary text-primary-foreground' : 'border bg-background text-muted-foreground hover:text-foreground' }}"
               @if ($tab === 'purchases') aria-current="true" @endif>{{ __('sup.tabPurchases') }}</a>
        </div>

        {{-- ================= Suppliers tab ================= --}}
        <div class="space-y-4" x-show="{{ $tab === 'suppliers' ? 'true' : 'false' }}" x-transition.opacity.duration.200ms>
            {{-- Panel header --}}
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <h2 class="text-base font-semibold">{{ __('sup.tabSuppliers') }}</h2>
                <button type="button" @click="supplierCreateOpen = true"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('sup.newSupplier') }}
                </button>
            </div>

            @if ($suppliers->isEmpty())
                {{-- Empty state --}}
                <div class="rounded-lg border bg-card">
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                        <div class="rounded-full bg-muted p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        </div>
                        <p class="font-medium">{{ __('sup.emptySuppliers') }}</p>
                        <p class="max-w-sm text-sm text-muted-foreground">{{ __('sup.emptySuppliersDesc') }}</p>
                    </div>
                </div>
            @else
                {{-- Supplier card grid --}}
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($suppliers as $supplier)
                        <div class="flex flex-col rounded-xl border bg-card p-4 card-lift">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $supplier->name }}</p>
                                    @if ($supplier->phone)
                                        <a href="tel:{{ $supplier->phone }}" class="text-sm font-medium text-primary hover:underline">{{ $supplier->phone }}</a>
                                    @endif
                                    @if ($supplier->email)
                                        <a href="mailto:{{ $supplier->email }}" class="block truncate text-xs text-muted-foreground hover:underline">{{ $supplier->email }}</a>
                                    @endif
                                </div>
                                <span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-semibold text-zinc-600">{{ $supplier->purchases_count }} · {{ __('sup.colPurchases') }}</span>
                            </div>
                            @if ($supplier->address)
                                <p class="mt-2 text-xs text-muted-foreground">{{ $supplier->address }}</p>
                            @endif
                            @if ($supplier->materials)
                                <p class="mt-1 line-clamp-2 text-xs text-muted-foreground" title="{{ $supplier->materials }}">{{ $supplier->materials }}</p>
                            @endif
                            <div class="mt-3 flex items-center justify-end gap-1 border-t pt-3">
                                <button type="button" @click="$dispatch('supplier-edit-{{ $supplier->id }}')"
                                        aria-label="{{ __('common.edit') }} — {{ $supplier->name }}"
                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </button>
                                @if ((int) $supplier->purchases_count > 0)
                                    <span title="{{ __('sup.deleteBlocked') }}"
                                          class="inline-flex size-8 cursor-not-allowed items-center justify-center rounded-md text-muted-foreground opacity-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </span>
                                @else
                                    <button type="button" @click="$dispatch('supplier-delete-{{ $supplier->id }}')"
                                            aria-label="{{ __('common.delete') }} — {{ $supplier->name }}"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Per-card modals (edit + delete) --}}
                @foreach ($suppliers as $supplier)
                    {{-- Edit supplier --}}
                    <div x-data="{ open: {{ $errors->any() && old('formId') === 'supplier-edit-'.$supplier->id ? 'true' : 'false' }} }" @supplier-edit-{{ $supplier->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('sup.editSupplier') }}"
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
                                        <h2 class="text-base font-semibold">{{ __('sup.editSupplier') }}</h2>
                                        <p class="mt-0.5 text-sm text-muted-foreground">{{ $supplier->name }}</p>
                                    </div>
                                    <button type="button" @click="open = false"
                                            aria-label="{{ __('common.close') }}"
                                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('suppliers.update', $supplier->id) }}">
                                    @csrf
                                    <input type="hidden" name="formId" value="supplier-edit-{{ $supplier->id }}" />
                                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label for="sup-edit-name-{{ $supplier->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldName') }}</label>
                                            <input type="text" id="sup-edit-name-{{ $supplier->id }}" name="name" value="{{ old('name', $supplier->name) }}" maxlength="120" required
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('name')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="sup-edit-phone-{{ $supplier->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldPhone') }}</label>
                                            <input type="tel" id="sup-edit-phone-{{ $supplier->id }}" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" maxlength="40"
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('phone')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="sup-edit-email-{{ $supplier->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldEmail') }}</label>
                                            <input type="email" id="sup-edit-email-{{ $supplier->id }}" name="email" value="{{ old('email', $supplier->email ?? '') }}" maxlength="120"
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('email')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="sup-edit-address-{{ $supplier->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldAddress') }}</label>
                                            <textarea id="sup-edit-address-{{ $supplier->id }}" name="address" rows="2" maxlength="200"
                                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('address', $supplier->address ?? '') }}</textarea>
                                            @error('address')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="sup-edit-materials-{{ $supplier->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldMaterials') }}</label>
                                            <textarea id="sup-edit-materials-{{ $supplier->id }}" name="materials" rows="2" maxlength="300"
                                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('materials', $supplier->materials ?? '') }}</textarea>
                                            @error('materials')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="sup-edit-notes-{{ $supplier->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldNotes') }}</label>
                                            <textarea id="sup-edit-notes-{{ $supplier->id }}" name="notes" rows="2" maxlength="4000"
                                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes', $supplier->notes ?? '') }}</textarea>
                                            @error('notes')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
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

                    {{-- Delete supplier --}}
                    <div x-data="{ open: false }" @supplier-delete-{{ $supplier->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('sup.deleteSupplier') }}"
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
                                    <h2 class="text-base font-semibold">{{ __('sup.deleteSupplier') }}</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ __('sup.deleteConfirm') }}</p>
                                    <p class="mt-1 text-sm font-semibold">{{ $supplier->name }}</p>
                                </div>
                                <form method="POST" action="{{ route('suppliers.destroy', $supplier->id) }}">
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
        </div>

        {{-- ================= Purchases tab ================= --}}
        <div class="space-y-4" x-show="{{ $tab === 'purchases' ? 'true' : 'false' }}" x-transition.opacity.duration.200ms>
            {{-- Panel header --}}
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <h2 class="text-base font-semibold">{{ __('sup.tabPurchases') }}</h2>
                <button type="button" @click="purchaseCreateOpen = true"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('sup.newPurchase') }}
                </button>
            </div>

            @if ($purchases->isEmpty())
                {{-- Empty state --}}
                <div class="rounded-lg border bg-card">
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
                        <div class="rounded-full bg-muted p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-muted-foreground" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                        </div>
                        <p class="font-medium">{{ __('sup.emptyPurchases') }}</p>
                        <p class="max-w-sm text-sm text-muted-foreground">{{ __('sup.emptyPurchasesDesc') }}</p>
                    </div>
                </div>
            @else
                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto rounded-lg border bg-card md:block">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">{{ __('sup.colReference') }}</th>
                                <th class="px-4 py-3">{{ __('sup.colDate') }}</th>
                                <th class="px-4 py-3">{{ __('sup.colDescription') }}</th>
                                <th class="px-4 py-3">{{ __('sup.colSupplier') }}</th>
                                <th class="px-4 py-3">{{ __('sup.colOrder') }}</th>
                                <th class="px-4 py-3">{{ __('sup.colPaymentStatus') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('sup.colTotal') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('sup.colActions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchases as $purchase)
                                <tr class="border-t transition-colors hover:bg-muted/30">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $purchase->reference }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $purchase->purchase_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                    <td class="max-w-56 px-4 py-3"><span class="block truncate" title="{{ $purchase->description }}">{{ $purchase->description }}</span></td>
                                    <td class="max-w-36 px-4 py-3"><span class="block truncate">{{ $purchase->supplier_name }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $purchase->order?->order_number ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $paymentPalette[$purchase->payment_status] ?? $paymentPalette['UNPAID'] }}">{{ __('labels.purchPayStatus.'.$purchase->payment_status) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Support\Money::format($purchase->total_amount) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            @if ($purchase->receipt_path)
                                                <a href="{{ route('purchases.receipt', $purchase->id) }}" target="_blank" rel="noopener"
                                                   aria-label="{{ __('sup.viewReceipt') }} — {{ $purchase->reference }}"
                                                   class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                                </a>
                                            @endif
                                            <button type="button" @click="$dispatch('purchase-edit-{{ $purchase->id }}')"
                                                    aria-label="{{ __('common.edit') }} — {{ $purchase->reference }}"
                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                            </button>
                                            <button type="button" @click="$dispatch('purchase-delete-{{ $purchase->id }}')"
                                                    aria-label="{{ __('common.delete') }} — {{ $purchase->reference }}"
                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($purchases->hasPages())
                        <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t px-4 py-3 sm:flex-row">
                            <p class="text-sm text-muted-foreground tabular-nums">{{ $purchases->total() }} · {{ $purchases->currentPage() }} / {{ $purchases->lastPage() }}</p>
                            <div class="flex items-center gap-2">
                                @if ($purchases->onFirstPage())
                                    <span class="inline-flex h-8 items-center gap-1 rounded-md border px-3 text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                    </span>
                                @else
                                    <a href="{{ $purchases->appends(request()->query())->previousPageUrl() }}"
                                       class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                    </a>
                                @endif
                                @if ($purchases->hasMorePages())
                                    <a href="{{ $purchases->appends(request()->query())->nextPageUrl() }}"
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
                    @foreach ($purchases as $purchase)
                        <div class="rounded-xl border bg-card p-4 card-lift">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-semibold">{{ $purchase->reference }}</p>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $paymentPalette[$purchase->payment_status] ?? $paymentPalette['UNPAID'] }}">{{ __('labels.purchPayStatus.'.$purchase->payment_status) }}</span>
                            </div>
                            <div class="mt-1.5 text-sm">
                                <p class="truncate font-medium">{{ $purchase->supplier_name }}</p>
                                <p class="mt-0.5 line-clamp-2 text-muted-foreground">{{ $purchase->description }}</p>
                            </div>
                            <div class="mt-3 flex items-end justify-between gap-3">
                                <p class="text-xs text-muted-foreground">{{ $purchase->purchase_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
                                <p class="text-lg font-bold tabular-nums">{{ \App\Support\Money::format($purchase->total_amount) }}</p>
                            </div>
                            <div class="mt-3 flex items-center justify-end gap-1 border-t pt-3">
                                @if ($purchase->receipt_path)
                                    <a href="{{ route('purchases.receipt', $purchase->id) }}" target="_blank" rel="noopener"
                                       aria-label="{{ __('sup.viewReceipt') }} — {{ $purchase->reference }}"
                                       class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                    </a>
                                @endif
                                <button type="button" @click="$dispatch('purchase-edit-{{ $purchase->id }}')"
                                        aria-label="{{ __('common.edit') }} — {{ $purchase->reference }}"
                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </button>
                                <button type="button" @click="$dispatch('purchase-delete-{{ $purchase->id }}')"
                                        aria-label="{{ __('common.delete') }} — {{ $purchase->reference }}"
                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Per-row modals (edit + delete) --}}
                @foreach ($purchases as $purchase)
                    {{-- Edit purchase --}}
                    <div x-data="{ open: {{ $errors->any() && old('formId') === 'purchase-edit-'.$purchase->id ? 'true' : 'false' }} }" @purchase-edit-{{ $purchase->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('sup.editPurchase') }}"
                             @keydown.escape.window="open = false">
                            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false" aria-hidden="true"
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
                                        <h2 class="text-base font-semibold">{{ __('sup.editPurchase') }}</h2>
                                        <p class="mt-0.5 text-sm text-muted-foreground">{{ $purchase->reference }}</p>
                                    </div>
                                    <button type="button" @click="open = false"
                                            aria-label="{{ __('common.close') }}"
                                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('purchases.update', $purchase->id) }}" enctype="multipart/form-data"
                                      x-data="{items: {{ \Illuminate\Support\Js::from($purchase->items->map(fn ($it) => ['d' => $it->description, 'q' => (float) $it->quantity, 'p' => (float) $it->unit_price])->all()) }}}">
                                    @csrf
                                    <input type="hidden" name="formId" value="purchase-edit-{{ $purchase->id }}" />
                                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label for="pur-edit-supplier-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldSupplier') }}</label>
                                            <select id="pur-edit-supplier-{{ $purchase->id }}" name="supplierId" required
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                <option value="">{{ __('sup.selectSupplier') }}</option>
                                                @foreach ($suppliers as $supplierOption)
                                                    <option value="{{ $supplierOption->id }}" {{ old('supplierId', $purchase->supplier_id) === $supplierOption->id ? 'selected' : '' }}>{{ $supplierOption->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('supplierId')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="pur-edit-date-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldPurchaseDate') }}</label>
                                            <input type="date" id="pur-edit-date-{{ $purchase->id }}" name="purchaseDate" value="{{ old('purchaseDate', $purchase->purchase_date->toDateString()) }}" required
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('purchaseDate')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="pur-edit-method-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldMethod') }}</label>
                                            <select id="pur-edit-method-{{ $purchase->id }}" name="method"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                @foreach ($methods as $methodOption)
                                                    <option value="{{ $methodOption }}" {{ old('method', $purchase->method ?? 'CASH') === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                                                @endforeach
                                            </select>
                                            @error('method')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="pur-edit-desc-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldSummary') }}</label>
                                            <textarea id="pur-edit-desc-{{ $purchase->id }}" name="description" rows="2" maxlength="500" required
                                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('description', $purchase->description) }}</textarea>
                                            @error('description')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="pur-edit-category-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldCategory') }}</label>
                                            <select id="pur-edit-category-{{ $purchase->id }}" name="categoryId"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                <option value="" {{ (old('categoryId', $purchase->expense_category_id ?? '') ?? '') === '' ? 'selected' : '' }}>—</option>
                                                @foreach ($materialCategories as $materialCategory)
                                                    <option value="{{ $materialCategory->id }}" {{ old('categoryId', $purchase->expense_category_id ?? '') === $materialCategory->id ? 'selected' : '' }}>{{ $materialCategory->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('categoryId')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="pur-edit-order-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldOrder') }}</label>
                                            <select id="pur-edit-order-{{ $purchase->id }}" name="orderId"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                <option value="">{{ __('sup.selectOrder') }}</option>
                                                @foreach ($orders as $orderOption)
                                                    <option value="{{ $orderOption->id }}" {{ old('orderId', $purchase->order_id) === $orderOption->id ? 'selected' : '' }}>{{ $orderOption->order_number }}</option>
                                                @endforeach
                                            </select>
                                            @error('orderId')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="pur-edit-status-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldPaymentStatus') }}</label>
                                            <select id="pur-edit-status-{{ $purchase->id }}" name="paymentStatus"
                                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                                @foreach ($paymentStatuses as $statusOption)
                                                    <option value="{{ $statusOption }}" {{ old('paymentStatus', $purchase->payment_status) === $statusOption ? 'selected' : '' }}>{{ __('labels.purchPayStatus.'.$statusOption) }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-2 text-xs text-muted-foreground">{{ __('sup.autoExpenseNote') }}</p>
                                            @error('paymentStatus')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="pur-edit-notes-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldNotes') }}</label>
                                            <textarea id="pur-edit-notes-{{ $purchase->id }}" name="notes" rows="2" maxlength="4000"
                                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes', $purchase->notes ?? '') }}</textarea>
                                            @error('notes')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="pur-edit-receipt-{{ $purchase->id }}" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldReceipt') }}</label>
                                            @if ($purchase->receipt_path)
                                                <a href="{{ route('purchases.receipt', $purchase->id) }}" target="_blank" rel="noopener" class="mb-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                                    {{ __('sup.fieldReceiptCurrent') }}
                                                </a>
                                            @endif
                                            <input type="file" id="pur-edit-receipt-{{ $purchase->id }}" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            @error('receipt')
                                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="space-y-3 border-t px-5 py-4">
                                        <h4 class="text-sm font-semibold">{{ __('sup.itemsTitle') }}</h4>
                                        @error('items')
                                            <span class="block text-xs text-red-600" role="alert">{{ $message }}</span>
                                        @enderror
                                        <div class="hidden grid-cols-12 gap-2 sm:grid">
                                            <p class="col-span-5 text-xs font-medium text-muted-foreground">{{ __('sup.itemDescription') }}</p>
                                            <p class="col-span-2 text-xs font-medium text-muted-foreground">{{ __('sup.itemQuantity') }}</p>
                                            <p class="col-span-3 text-xs font-medium text-muted-foreground">{{ __('sup.itemUnitPrice') }}</p>
                                            <p class="col-span-1 text-right text-xs font-medium text-muted-foreground">{{ __('sup.itemTotal') }}</p>
                                            <p class="col-span-1"></p>
                                        </div>
                                        <template x-for="(it, idx) in items" :key="idx">
                                            <div class="grid grid-cols-12 items-start gap-2">
                                                <input type="text" x-model="it.d" :name="'items['+idx+'][description]'" placeholder="{{ __('sup.itemDescription') }}" maxlength="300" required
                                                       class="col-span-12 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-5" />
                                                <input type="number" x-model.number="it.q" :name="'items['+idx+'][quantity]'" step="any" min="0.01" required aria-label="{{ __('sup.itemQuantity') }}"
                                                       class="col-span-4 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-2" />
                                                <input type="number" x-model.number="it.p" :name="'items['+idx+'][unitPrice]']" step="any" min="0" inputmode="decimal" required aria-label="{{ __('sup.itemUnitPrice') }}"
                                                       class="col-span-5 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-3" />
                                                <p class="col-span-2 pt-3 text-right text-xs tabular-nums sm:col-span-1" x-text="new Intl.NumberFormat().format(Math.round(it.q*it.p))"></p>
                                                <button type="button" @click="items.length > 1 && items.splice(idx,1)" aria-label="{{ __('sup.removeItem') }}"
                                                        class="col-span-1 inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                        <button type="button" @click="items.push({d:'',q:1,p:0})"
                                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-3 py-2 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                            {{ __('sup.addItem') }}
                                        </button>
                                        <div class="flex items-center justify-between gap-3 rounded-lg border bg-muted/30 px-4 py-3">
                                            <p class="text-sm font-semibold">{{ __('sup.purchaseTotal') }}</p>
                                            <p class="text-base font-semibold tabular-nums" x-text="new Intl.NumberFormat().format(Math.round(items.reduce((s,it)=>s+it.q*it.p,0)))"></p>
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

                    {{-- Delete purchase --}}
                    <div x-data="{ open: false }" @purchase-delete-{{ $purchase->id }}.window="open = true">
                        <div x-show="open" style="display: none;" x-cloak
                             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                             role="dialog" aria-modal="true" aria-label="{{ __('sup.deletePurchase') }}"
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
                                    <h2 class="text-base font-semibold">{{ __('sup.deletePurchase') }}</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ __('sup.deleteConfirmPurchase') }}</p>
                                    <p class="mt-1 text-sm font-semibold">{{ $purchase->reference }} · {{ \App\Support\Money::format($purchase->total_amount) }}</p>
                                </div>
                                <form method="POST" action="{{ route('purchases.destroy', $purchase->id) }}">
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
        </div>

        {{-- New supplier modal --}}
        <div x-show="supplierCreateOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('sup.newSupplier') }}"
             @keydown.escape.window="supplierCreateOpen = false">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="supplierCreateOpen = false" aria-hidden="true"
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
                        <h2 class="text-base font-semibold">{{ __('sup.newSupplier') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('sup.subtitle') }}</p>
                    </div>
                    <button type="button" @click="supplierCreateOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('suppliers.store') }}">
                    @csrf
                    <input type="hidden" name="formId" value="supplier-create" />
                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="sup-name" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldName') }}</label>
                            <input type="text" id="sup-name" name="name" value="{{ old('name') }}" maxlength="120" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('name')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="sup-phone" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldPhone') }}</label>
                            <input type="tel" id="sup-phone" name="phone" value="{{ old('phone') }}" maxlength="40"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('phone')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="sup-email" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldEmail') }}</label>
                            <input type="email" id="sup-email" name="email" value="{{ old('email') }}" maxlength="120"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('email')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sup-address" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldAddress') }}</label>
                            <textarea id="sup-address" name="address" rows="2" maxlength="200"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('address') }}</textarea>
                            @error('address')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sup-materials" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldMaterials') }}</label>
                            <textarea id="sup-materials" name="materials" rows="2" maxlength="300"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('materials') }}</textarea>
                            @error('materials')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sup-notes" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldNotes') }}</label>
                            <textarea id="sup-notes" name="notes" rows="2" maxlength="4000"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="supplierCreateOpen = false"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('sup.newSupplier') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- New purchase modal --}}
        <div x-show="purchaseCreateOpen" style="display: none;" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-label="{{ __('sup.newPurchase') }}"
             @keydown.escape.window="purchaseCreateOpen = false">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="purchaseCreateOpen = false" aria-hidden="true"
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
                        <h2 class="text-base font-semibold">{{ __('sup.newPurchase') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('sup.subtitle') }}</p>
                    </div>
                    <button type="button" @click="purchaseCreateOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('purchases.store') }}" enctype="multipart/form-data"
                      x-data="{items:[{d:'',q:1,p:0}]}">
                    @csrf
                    <input type="hidden" name="formId" value="purchase-create" />
                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="pur-supplier" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldSupplier') }}</label>
                            <select id="pur-supplier" name="supplierId" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">{{ __('sup.selectSupplier') }}</option>
                                @foreach ($suppliers as $supplierOption)
                                    <option value="{{ $supplierOption->id }}" {{ old('supplierId') === $supplierOption->id ? 'selected' : '' }}>{{ $supplierOption->name }}</option>
                                @endforeach
                            </select>
                            @error('supplierId')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="pur-date" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldPurchaseDate') }}</label>
                            <input type="date" id="pur-date" name="purchaseDate" value="{{ old('purchaseDate', now()->toDateString()) }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('purchaseDate')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="pur-method" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldMethod') }}</label>
                            <select id="pur-method" name="method"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($methods as $methodOption)
                                    <option value="{{ $methodOption }}" {{ old('method', 'CASH') === $methodOption ? 'selected' : '' }}>{{ __('labels.payMethod.'.$methodOption) }}</option>
                                @endforeach
                            </select>
                            @error('method')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="pur-desc" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldSummary') }}</label>
                            <textarea id="pur-desc" name="description" rows="2" maxlength="500" required
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="pur-category" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldCategory') }}</label>
                            <select id="pur-category" name="categoryId"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="" {{ (old('categoryId') ?? '') === '' ? 'selected' : '' }}>—</option>
                                @foreach ($materialCategories as $materialCategory)
                                    <option value="{{ $materialCategory->id }}" {{ old('categoryId') === $materialCategory->id ? 'selected' : '' }}>{{ $materialCategory->name }}</option>
                                @endforeach
                            </select>
                            @error('categoryId')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="pur-order" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldOrder') }}</label>
                            <select id="pur-order" name="orderId"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">{{ __('sup.selectOrder') }}</option>
                                @foreach ($orders as $orderOption)
                                    <option value="{{ $orderOption->id }}" {{ old('orderId') === $orderOption->id ? 'selected' : '' }}>{{ $orderOption->order_number }}</option>
                                @endforeach
                            </select>
                            @error('orderId')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="pur-status" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldPaymentStatus') }}</label>
                            <select id="pur-status" name="paymentStatus"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($paymentStatuses as $statusOption)
                                    <option value="{{ $statusOption }}" {{ old('paymentStatus', 'UNPAID') === $statusOption ? 'selected' : '' }}>{{ __('labels.purchPayStatus.'.$statusOption) }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-muted-foreground">{{ __('sup.autoExpenseNote') }}</p>
                            @error('paymentStatus')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="pur-notes" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldNotes') }}</label>
                            <textarea id="pur-notes" name="notes" rows="2" maxlength="4000"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="pur-receipt" class="mb-1.5 block text-sm font-medium">{{ __('sup.fieldReceipt') }}</label>
                            <input type="file" id="pur-receipt" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('receipt')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="space-y-3 border-t px-5 py-4">
                        <h4 class="text-sm font-semibold">{{ __('sup.itemsTitle') }}</h4>
                        @error('items')
                            <span class="block text-xs text-red-600" role="alert">{{ $message }}</span>
                        @enderror
                        <div class="hidden grid-cols-12 gap-2 sm:grid">
                            <p class="col-span-5 text-xs font-medium text-muted-foreground">{{ __('sup.itemDescription') }}</p>
                            <p class="col-span-2 text-xs font-medium text-muted-foreground">{{ __('sup.itemQuantity') }}</p>
                            <p class="col-span-3 text-xs font-medium text-muted-foreground">{{ __('sup.itemUnitPrice') }}</p>
                            <p class="col-span-1 text-right text-xs font-medium text-muted-foreground">{{ __('sup.itemTotal') }}</p>
                            <p class="col-span-1"></p>
                        </div>
                        <template x-for="(it, idx) in items" :key="idx">
                            <div class="grid grid-cols-12 items-start gap-2">
                                <input type="text" x-model="it.d" :name="'items['+idx+'][description]'" placeholder="{{ __('sup.itemDescription') }}" maxlength="300" required
                                       class="col-span-12 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-5" />
                                <input type="number" x-model.number="it.q" :name="'items['+idx+'][quantity]']" step="any" min="0.01" required aria-label="{{ __('sup.itemQuantity') }}"
                                       class="col-span-4 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-2" />
                                <input type="number" x-model.number="it.p" :name="'items['+idx+'][unitPrice]']" step="any" min="0" inputmode="decimal" required aria-label="{{ __('sup.itemUnitPrice') }}"
                                       class="col-span-5 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-3" />
                                <p class="col-span-2 pt-3 text-right text-xs tabular-nums sm:col-span-1" x-text="new Intl.NumberFormat().format(Math.round(it.q*it.p))"></p>
                                <button type="button" @click="items.length > 1 && items.splice(idx,1)" aria-label="{{ __('sup.removeItem') }}"
                                        class="col-span-1 inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="items.push({d:'',q:1,p:0})"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-3 py-2 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            {{ __('sup.addItem') }}
                        </button>
                        <div class="flex items-center justify-between gap-3 rounded-lg border bg-muted/30 px-4 py-3">
                            <p class="text-sm font-semibold">{{ __('sup.purchaseTotal') }}</p>
                            <p class="text-base font-semibold tabular-nums" x-text="new Intl.NumberFormat().format(Math.round(items.reduce((s,it)=>s+it.q*it.p,0)))"></p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="purchaseCreateOpen = false"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('sup.newPurchase') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
