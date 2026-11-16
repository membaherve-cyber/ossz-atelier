@extends('layouts.app')

@section('title', __('shell.view.orders'))

@section('content')
                <div class="space-y-4"
     x-data="{
        registerOpen: {{ $errors->any() && old('formId') === 'order-create' ? 'true' : 'false' }},
        detailOpen: false,
        detailLoading: false,
        detailHtml: '',
        openDetail(url) {
            this.detailOpen = true;
            this.detailLoading = true;
            this.detailHtml = '';
            fetch(url)
                .then(r => r.text())
                .then(html => { this.detailHtml = html; this.detailLoading = false; })
                .catch(() => { this.detailOpen = false; this.detailLoading = false; });
        },
        closeDetail() {
            this.detailOpen = false;
            this.detailLoading = false;
            this.detailHtml = '';
        }
     }">

    
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('orders.title') }}</h1>
            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('orders.subtitle') }}</p>
        </div>
        @if (\App\Services\Permissions::allows($me, 'orders.manage'))
                    <button type="button" @click="registerOpen = true"
                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                {{ __('orders.register') }}
            </button>
            @endif
    </div>

    
    <form method="GET" action="{{ route('orders.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative max-w-sm flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('orders.searchPlaceholder') }}"
                   aria-label="{{ __('orders.searchLabel') }}"
                   class="h-9 w-full rounded-md border border-input bg-card py-1 pl-8 pr-3 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select name="priority" aria-label="{{ __('orders.filterPriorityLabel') }}"
                    class="h-9 w-[150px] rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                <option value="">{{ __('orders.allPriorities') }}</option>
                                    <option value="LOW" {{ ($filters['priority'] ?? '') === 'LOW' ? 'selected' : '' }}>{{ __('labels.priority.LOW') }}</option>
                                    <option value="NORMAL" {{ ($filters['priority'] ?? '') === 'NORMAL' ? 'selected' : '' }}>{{ __('labels.priority.NORMAL') }}</option>
                                    <option value="HIGH" {{ ($filters['priority'] ?? '') === 'HIGH' ? 'selected' : '' }}>{{ __('labels.priority.HIGH') }}</option>
                                    <option value="URGENT" {{ ($filters['priority'] ?? '') === 'URGENT' ? 'selected' : '' }}>{{ __('labels.priority.URGENT') }}</option>
                            </select>
            <div class="flex items-center gap-1.5">
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="{{ __('orders.fromDateLabel') }}"
                       class="h-9 w-[145px] rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                <span class="text-sm text-muted-foreground">{{ __('orders.toLabel') }}</span>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="{{ __('orders.toDateLabel') }}"
                       class="h-9 w-[145px] rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
            </div>
            <button type="submit"
                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                {{ __('common.filter') }}
            </button>
            @if (($filters['q'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '')
                <a href="{{ route('orders.index') }}"
                   class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md px-4 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('orders.clear') }}
                </a>
            @endif
                    </div>
    </form>

    
            <div class="rounded-lg border bg-card">
            @if ($orders->isEmpty())
                <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </div>
                    <p class="font-medium">{{ ($filters['q'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '' ? __('orders.emptyFiltersTitle') : __('orders.emptyTitle') }}</p>
                    <p class="text-sm text-muted-foreground max-w-sm">{{ ($filters['q'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '' ? __('orders.emptyFiltersDesc') : __('orders.emptyDesc') }}</p>
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <th class="px-4 py-3">{{ __('orders.colOrder') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colCustomer') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colGarment') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('orders.colPieces') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colPriority') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colDelivery') }}</th>
                            <th class="min-w-56 px-4 py-3">{{ __('orders.colProduction') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colRegistered') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($orders as $order)
                            @php
                                $taskTotal = $order->tasks->count();
                                $taskDone = $order->tasks->where('status', 'DELIVERED')->count();
                                $taskAvg = $taskTotal > 0 ? (int) round($order->tasks->avg('progress')) : 0;
                            @endphp
                                                                                <tr class="transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">
                                    <button type="button" @click="openDetail('{{ route('orders.show', $order->id) }}')"
                                            aria-label="{{ \App\Support\Trans::fill('orders.viewAria', ['number' => $order->order_number]) }}"
                                            class="rounded-sm text-left underline-offset-2 transition-colors hover:text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        {{ $order->order_number }}
                                    </button>
                                </td>
                                <td class="max-w-44 px-4 py-3">
                                    <p class="truncate font-medium">{{ $order->customer->name }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ $order->customer->phone }}</p>
                                </td>
                                <td class="max-w-56 px-4 py-3">
                                    <p class="truncate">{{ $order->description }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ $order->garment_category }}</p>
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">{{ $order->number_of_pieces }}</td>
                                <td class="px-4 py-3">
                                    <x-badges.priority :priority="$order->priority" />
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($order->expected_delivery_date && $order->expected_delivery_date->isPast())
                                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-red-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                            {{ $order->expected_delivery_date->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                        </span>
                                    @else
                                        <span class="text-sm text-muted-foreground">{{ $order->expected_delivery_date ? $order->expected_delivery_date->locale(app()->getLocale())->translatedFormat('j M Y') : '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex min-w-28 items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                            <div class="h-full rounded-full {{ $taskAvg == 100 ? 'bg-emerald-600' : 'bg-primary' }}" style="width: {{ $taskAvg }}%"></div>
                                        </div>
                                        <span class="w-9 text-right text-xs font-semibold tabular-nums {{ $taskAvg == 100 ? 'text-emerald-700' : '' }}">{{ $taskAvg }}%</span>
                                    </div>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        @if ($taskTotal === 0)
                                            {{ __('orders.noTasks') }}
                                        @else
                                            {{ \App\Support\Trans::fill($taskDone === 1 ? 'orders.tasksDone' : 'orders.tasksDonePlural', ['done' => $taskDone, 'total' => $taskTotal]) }}
                                        @endif
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                    {{ $order->created_at->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openDetail('{{ route('orders.show', $order->id) }}')"
                                                aria-label="{{ \App\Support\Trans::fill('orders.viewAria', ['number' => $order->order_number]) }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>

                                        
                                            <div x-data="{ open: {{ $errors->any() && old('formId') === 'order-edit-'.$order->id ? 'true' : 'false' }} }">
                                                <button type="button" @click="open = true"
                                                        aria-label="{{ \App\Support\Trans::fill('orders.editAria', ['number' => $order->order_number]) }}"
                                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                                </button>
                                                <div x-show="open" style="display: none;"
                                                     class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
                                                     aria-label="{{ \App\Support\Trans::fill('orders.editTitle', ['number' => $order->order_number]) }}"
                                                     @keydown.escape.window="open = false">
                                                    <div class="fixed inset-0 bg-black/50" @click="open = false" aria-hidden="true"></div>
                                                    <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
                                                        <div class="relative w-full max-w-2xl rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                                                            <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                                                                <div class="min-w-0">
                                                                    <h2 class="text-base font-semibold">{{ \App\Support\Trans::fill('orders.editTitle', ['number' => $order->order_number]) }}</h2>
                                                                    <p class="mt-0.5 text-sm text-muted-foreground">{{ __('orders.editDesc') }}</p>
                                                                </div>
                                                                <button type="button" @click="open = false"
                                                                        aria-label="{{ __('common.close') }}"
                                                                        class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                                </button>
                                                            </div>
                                                            <form method="POST" action="{{ route('orders.update', $order->id) }}">
                                                                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                                                                <input type="hidden" name="formId" value="order-edit-{{ $order->id }}" />
                                                                <div class="max-h-[calc(85vh-11rem)] overflow-y-auto px-5 py-4">
                                                                    <div class="grid gap-4 sm:grid-cols-2">
                                                                        <div class="sm:col-span-2">
                                                                            <label class="mb-1.5 block text-sm font-medium">{{ __('orders.customerLabel') }}</label>
                                                                            <p class="rounded-md border bg-muted/40 px-3 py-2 text-sm">
                                                                                <span class="font-medium">{{ $order->customer->name }}</span>
                                                                                <span class="text-muted-foreground"> · {{ $order->customer->phone }}</span>
                                                                            </p>
                                                                        </div>
                                                                        <div>
                                                                            <label for="order-number-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldOrderNumber') }}</label>
                                                                            <input type="text" id="order-number-{{ $order->id }}" name="orderNumber" value="{{ old('orderNumber', $order->order_number) }}" maxlength="50" placeholder="{{ __('orders.numberPlaceholder') }}"
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                            @error('orderNumber')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div>
                                                                            <label for="order-priority-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldPriority') }}</label>
                                                                            <select id="order-priority-{{ $order->id }}" name="priority"
                                                                                    class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                                                                <option value="LOW" {{ old('priority', $order->priority) === 'LOW' ? 'selected' : '' }}>{{ __('labels.priority.LOW') }}</option>
                                                                                <option value="NORMAL" {{ old('priority', $order->priority) === 'NORMAL' ? 'selected' : '' }}>{{ __('labels.priority.NORMAL') }}</option>
                                                                                <option value="HIGH" {{ old('priority', $order->priority) === 'HIGH' ? 'selected' : '' }}>{{ __('labels.priority.HIGH') }}</option>
                                                                                <option value="URGENT" {{ old('priority', $order->priority) === 'URGENT' ? 'selected' : '' }}>{{ __('labels.priority.URGENT') }}</option>
                                                                            </select>
                                                                            @error('priority')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div>
                                                                            <label for="order-date-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldOrderDate') }}</label>
                                                                            <input type="date" id="order-date-{{ $order->id }}" name="orderDate" value="{{ old('orderDate', $order->order_date->format('Y-m-d')) }}"
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                            @error('orderDate')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div>
                                                                            <label for="order-delivery-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldDelivery') }}</label>
                                                                            <input type="date" id="order-delivery-{{ $order->id }}" name="expectedDeliveryDate" value="{{ old('expectedDeliveryDate', $order->expected_delivery_date?->format('Y-m-d') ?? '') }}"
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                            @error('expectedDeliveryDate')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div class="sm:col-span-2">
                                                                            <label for="order-desc-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldDescription') }}</label>
                                                                            <textarea id="order-desc-{{ $order->id }}" name="description" rows="2" maxlength="500" placeholder="{{ __('orders.descPlaceholder') }}"
                                                                                      class="min-h-[64px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('description', $order->description) }}</textarea>
                                                                            @error('description')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div>
                                                                            <label for="order-category-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldCategory') }}</label>
                                                                            <input type="text" id="order-category-{{ $order->id }}" name="garmentCategory" value="{{ old('garmentCategory', $order->garment_category) }}" maxlength="80" placeholder="{{ __('orders.categoryPlaceholder') }}"
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                            @error('garmentCategory')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div>
                                                                            <label for="order-pieces-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldPieces') }}</label>
                                                                            <input type="number" id="order-pieces-{{ $order->id }}" name="pieceCount" value="{{ old('pieceCount', $order->number_of_pieces) }}" min="1" max="200" step="1"
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                            @error('pieceCount')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div class="sm:col-span-2">
                                                                            <label for="order-instructions-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldInstructions') }}</label>
                                                                            <textarea id="order-instructions-{{ $order->id }}" name="instructions" rows="3" placeholder="{{ __('orders.instructionsPlaceholder') }}"
                                                                                      class="min-h-[72px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('instructions', $order->instructions ?? '') }}</textarea>
                                                                            @error('instructions')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div class="sm:col-span-2">
                                                                            <label for="order-notes-{{ $order->id }}" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldNotes') }}</label>
                                                                            <textarea id="order-notes-{{ $order->id }}" name="notes" rows="2" placeholder="{{ __('orders.notesPlaceholder') }}"
                                                                                      class="min-h-[56px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('notes', $order->notes ?? '') }}</textarea>
                                                                            @error('notes')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                                                    <button type="button" @click="open = false"
                                                                            class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                        {{ __('common.cancel') }}
                                                                    </button>
                                                                    <button type="submit"
                                                                            class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                        {{ __('common.saveChanges') }}
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            
                                            @if ($order->tasks->isNotEmpty())
                                                                                            <button type="button" disabled title="{{ __('orders.hasTasksError') }}"
                                                        aria-label="{{ \App\Support\Trans::fill('orders.deleteAria', ['number' => $order->order_number]) }}"
                                                        class="inline-flex size-8 cursor-not-allowed items-center justify-center rounded-md text-muted-foreground opacity-40">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                                </button>
                                            @else
                                                <div x-data="{ open: false }">
                                                    <button type="button" @click="open = true"
                                                            aria-label="{{ \App\Support\Trans::fill('orders.deleteAria', ['number' => $order->order_number]) }}"
                                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                                    </button>
                                                    <div x-show="open" style="display: none"
                                                         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
                                                         aria-label="{{ __('orders.deleteTitle') }}"
                                                         @keydown.escape.window="open = false">
                                                        <div class="fixed inset-0 bg-black/50" @click="open = false" aria-hidden="true"></div>
                                                        <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
                                                            <div class="relative w-full max-w-md rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                                                                <div class="px-5 pt-5">
                                                                    <h2 class="text-base font-semibold">{{ __('orders.deleteTitle') }}</h2>
                                                                    <p class="mt-1 text-sm text-muted-foreground">{{ \App\Support\Trans::fill('orders.deleteDesc', ['number' => $order->order_number]) }}</p>
                                                                </div>
                                                                <form method="POST" action="{{ route('orders.destroy', $order->id) }}">
                                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                                                                    <div class="flex justify-end gap-2 px-5 py-4">
                                                                        <button type="button" @click="open = false"
                                                                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                            {{ __('common.cancel') }}
                                                                        </button>
                                                                        <button type="submit"
                                                                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-destructive px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                            {{ __('common.delete') }}
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                                                                                                        </div>
                                </td>
                            </tr>
                        @endforeach
                                            </tbody>
                </table>
            </div>

            
            @if ($orders->hasPages())
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4">
                    <p class="text-sm text-muted-foreground">
                        {{ \App\Support\Trans::fill('orders.pagination', ['total' => $orders->total(), 'page' => $orders->currentPage(), 'totalPages' => $orders->lastPage()]) }}
                    </p>
                    <div class="flex items-center gap-2">
                        @if ($orders->onFirstPage())
                            <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                {{ __('orders.previous') }}
                            </span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}"
                               class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-card text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                {{ __('orders.previous') }}
                            </a>
                        @endif

                        @if ($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}"
                               class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-card text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {{ __('orders.next') }}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                                {{ __('orders.next') }}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </span>
                        @endif
                                    </div>
                </div>
            @endif
                    </div>
        @endif
    
    
            <div x-show="registerOpen" style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
             aria-label="{{ __('orders.register') }}"
             @keydown.escape.window="registerOpen = false">
            <div class="fixed inset-0 bg-black/50" @click="registerOpen = false" aria-hidden="true"></div>
            <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
                <div class="relative w-full max-w-2xl rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                    <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold">{{ __('orders.register') }}</h2>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('orders.registerDesc') }}</p>
                        </div>
                        <button type="button" @click="registerOpen = false"
                                aria-label="{{ __('common.close') }}"
                                class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('orders.store') }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                        <input type="hidden" name="formId" value="order-create" />
                        <div class="max-h-[calc(85vh-11rem)] overflow-y-auto px-5 py-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="order-customer" class="mb-1.5 block text-sm font-medium">{{ __('orders.selectCustomer') }}</label>
                                    <select id="order-customer" name="customerId" required
                                            class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                        <option value="" disabled {{ old('customerId') ? '' : 'selected' }}>{{ __('orders.selectCustomerPlaceholder') }}</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customerId') === $customer->id ? 'selected' : '' }}>{{ $customer->name }} — {{ $customer->phone }}</option>
                                        @endforeach
                                    </select>
                                    @error('customerId')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div>
                                    <label for="order-number" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldOrderNumber') }}</label>
                                    <input type="text" id="order-number" name="orderNumber" value="{{ old('orderNumber') }}" maxlength="50" placeholder="{{ __('orders.numberPlaceholder') }}"
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('orderNumber')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div>
                                    <label for="order-priority" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldPriority') }}</label>
                                    <select id="order-priority" name="priority"
                                            class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                        <option value="LOW" {{ old('priority', 'NORMAL') === 'LOW' ? 'selected' : '' }}>{{ __('labels.priority.LOW') }}</option>
                                        <option value="NORMAL" {{ old('priority', 'NORMAL') === 'NORMAL' ? 'selected' : '' }}>{{ __('labels.priority.NORMAL') }}</option>
                                        <option value="HIGH" {{ old('priority', 'NORMAL') === 'HIGH' ? 'selected' : '' }}>{{ __('labels.priority.HIGH') }}</option>
                                        <option value="URGENT" {{ old('priority', 'NORMAL') === 'URGENT' ? 'selected' : '' }}>{{ __('labels.priority.URGENT') }}</option>
                                    </select>
                                    @error('priority')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div>
                                    <label for="order-date" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldOrderDate') }}</label>
                                    <input type="date" id="order-date" name="orderDate" value="{{ old('orderDate', now()->format('Y-m-d')) }}" required
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('orderDate')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div>
                                    <label for="order-delivery" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldDelivery') }}</label>
                                    <input type="date" id="order-delivery" name="expectedDeliveryDate" value="{{ old('expectedDeliveryDate') }}"
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('expectedDeliveryDate')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div class="sm:col-span-2">
                                    <label for="order-desc" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldDescription') }}</label>
                                    <textarea id="order-desc" name="description" rows="2" maxlength="500" required placeholder="{{ __('orders.descPlaceholder') }}"
                                              class="min-h-[64px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('description') }}</textarea>
                                    @error('description')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div>
                                    <label for="order-category" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldCategory') }}</label>
                                    <input type="text" id="order-category" name="garmentCategory" value="{{ old('garmentCategory') }}" maxlength="80" required placeholder="{{ __('orders.categoryPlaceholder') }}"
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('garmentCategory')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div>
                                    <label for="order-pieces" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldPieces') }}</label>
                                    <input type="number" id="order-pieces" name="pieceCount" value="{{ old('pieceCount', 1) }}" min="1" max="200" step="1" required
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('pieceCount')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div class="sm:col-span-2">
                                    <label for="order-instructions" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldInstructions') }}</label>
                                    <textarea id="order-instructions" name="instructions" rows="3" placeholder="{{ __('orders.instructionsPlaceholder') }}"
                                              class="min-h-[72px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('instructions') }}</textarea>
                                    @error('instructions')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                                <div class="sm:col-span-2">
                                    <label for="order-notes" class="mb-1.5 block text-sm font-medium">{{ __('orders.fieldNotes') }}</label>
                                    <textarea id="order-notes" name="notes" rows="2" placeholder="{{ __('orders.notesPlaceholder') }}"
                                              class="min-h-[56px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                                                    </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                            <button type="button" @click="registerOpen = false"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {{ __('common.cancel') }}
                            </button>
                            <button type="submit"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {{ __('orders.register') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
    
    <div x-show="detailOpen" style="display: none"
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
         aria-label="{{ __('orders.title') }}"
         @keydown.escape.window="closeDetail()">
        <div class="fixed inset-0 bg-black/50" @click="closeDetail()" aria-hidden="true"></div>
        <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
            <div class="relative w-full max-w-2xl">
                <button type="button" @click="closeDetail()"
                        aria-label="{{ __('common.close') }}"
                        class="absolute right-0 top-0 z-10 rounded-md bg-card p-1.5 text-muted-foreground shadow-sm transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
                <div x-show="detailLoading" class="space-y-3 rounded-lg border border-border bg-card p-5 shadow-xl">
                    <div class="h-7 w-56 animate-pulse rounded-md bg-muted"></div>
                    <div class="h-24 w-full animate-pulse rounded-md bg-muted"></div>
                    <div class="h-32 w-full animate-pulse rounded-md bg-muted"></div>
                </div>
                <div x-show="! detailLoading" x-html="detailHtml"></div>
            </div>
        </div>
    </div>
</div>
            @endsection
