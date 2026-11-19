@extends('layouts.app')

@section('title', __('shell.view.orders'))

@section('content')
    <div class="space-y-4">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('orders.title') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('orders.subtitle') }}</p>
            </div>
            @if (\App\Services\Permissions::allows($me, 'orders.manage'))
                <a href="{{ route('orders.create') }}"
                   class="inline-flex min-h-[44px] items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('orders.newOrder') }}
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('orders.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="relative max-w-sm flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('orders.searchPlaceholder') }}"
                       aria-label="{{ __('orders.searchLabel') }}"
                       class="h-11 sm:h-9 w-full rounded-md border border-input bg-card py-1 pl-8 pr-3 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select name="priority" aria-label="{{ __('orders.filterPriorityLabel') }}"
                        class="h-11 sm:h-9 w-[150px] rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                    <option value="">{{ __('orders.allPriorities') }}</option>
                    @foreach (\App\Support\Atelier::PRIORITIES as $priority)
                        <option value="{{ $priority }}" {{ ($filters['priority'] ?? '') === $priority ? 'selected' : '' }}>{{ __('labels.priority.'.$priority) }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-1.5">
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="{{ __('orders.fromDateLabel') }}"
                           class="h-11 sm:h-9 w-[145px] rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                    <span class="text-sm text-muted-foreground">{{ __('orders.toLabel') }}</span>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="{{ __('orders.toDateLabel') }}"
                           class="h-11 sm:h-9 w-[145px] rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                </div>
                <button type="submit"
                        class="inline-flex h-11 sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 btn-press">
                    {{ __('common.filter') }}
                </button>
                @if (($filters['q'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '')
                    <a href="{{ route('orders.index') }}"
                       class="inline-flex h-11 sm:h-9 items-center whitespace-nowrap rounded-md px-4 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                        {{ __('orders.clear') }}
                    </a>
                @endif
            </div>
        </form>

        @if ($orders->isEmpty())
            <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                <div class="rounded-full bg-muted p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                </div>
                <p class="font-medium">{{ ($filters['q'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '' ? __('orders.emptyFiltersTitle') : __('orders.emptyTitle') }}</p>
                <p class="text-sm text-muted-foreground max-w-sm">{{ ($filters['q'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '' ? __('orders.emptyFiltersDesc') : __('orders.emptyDesc') }}</p>
            </div>
        @else
            @php
                $hasFilters = ($filters['q'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '';
            @endphp

            {{-- ================= Mobile card list ================= --}}
            <div class="md:hidden space-y-3 stagger">
                @foreach ($orders as $order)
                    @php
                        $taskTotal = $order->tasks->count();
                        $taskDone = $order->tasks->where('status', 'DELIVERED')->count();
                        $taskAvg = $taskTotal > 0 ? (int) round($order->tasks->avg('progress')) : 0;
                        $paidTotal = (float) ($order->paid_total ?? 0);
                    @endphp
                    <a href="{{ route('orders.show', $order->id) }}"
                       aria-label="{{ \App\Support\Trans::fill('orders.viewAria', ['number' => $order->order_number]) }}"
                       class="block rounded-xl border bg-card p-4 card-lift">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                <span class="font-semibold">{{ $order->order_number }}</span>
                                @if ($order->status === 'DRAFT')
                                    <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">{{ __('labels.orderStatus.DRAFT') }}</span>
                                @endif
                                <x-badges.priority :priority="$order->priority" />
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                        <p class="mt-1 truncate text-sm text-muted-foreground">{{ $order->description }}</p>
                        <p class="text-xs text-muted-foreground">{{ $order->customer->name }} · {{ $order->customer->phone }}</p>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <span class="block h-full rounded-full {{ $taskAvg >= 100 ? 'bg-emerald-600' : 'bg-primary' }} bar-anim" style="width: {{ $taskAvg }}%"></span>
                            </div>
                            <span class="text-xs font-semibold tabular-nums">{{ $taskAvg }}%</span>
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center justify-between gap-1 text-xs text-muted-foreground">
                            <span>{{ $taskDone }}/{{ $taskTotal }} · {{ $order->number_of_pieces }} {{ __('orders.colPieces') }}</span>
                            @if ($canSeeFinancials && $order->total_value !== null)
                                <span class="tabular-nums font-medium">
                                    <span class="text-emerald-700">{{ \App\Support\Money::amount($paidTotal) }}</span> / {{ \App\Support\Money::amount($order->total_value) }}
                                </span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- ================= Desktop table ================= --}}
            <div class="hidden md:block overflow-x-auto rounded-lg border bg-card">
                <table class="w-full min-w-[1080px] text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <th class="px-4 py-3">{{ __('orders.colOrder') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colCustomer') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colGarment') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('orders.colPieces') }}</th>
                            @if ($canSeeFinancials)
                                <th class="px-4 py-3 text-right">{{ __('orders.paidLabel') }} / {{ __('frep.colValue') }}</th>
                            @endif
                            <th class="px-4 py-3">{{ __('orders.colPriority') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colDelivery') }}</th>
                            <th class="min-w-56 px-4 py-3">{{ __('orders.colProduction') }}</th>
                            <th class="px-4 py-3">{{ __('orders.colRegistered') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($orders as $order)
                            @php
                                $taskTotal = $order->tasks->count();
                                $taskDone = $order->tasks->where('status', 'DELIVERED')->count();
                                $taskAvg = $taskTotal > 0 ? (int) round($order->tasks->avg('progress')) : 0;
                                $paidTotal = (float) ($order->paid_total ?? 0);
                            @endphp
                            <tr class="cursor-pointer transition-colors hover:bg-muted/30"
                                onclick="window.location.href='{{ route('orders.show', $order->id) }}'">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">
                                    <a href="{{ route('orders.show', $order->id) }}"
                                       aria-label="{{ \App\Support\Trans::fill('orders.viewAria', ['number' => $order->order_number]) }}"
                                       class="rounded-sm underline-offset-2 transition-colors hover:text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        {{ $order->order_number }}
                                    </a>
                                    @if ($order->status === 'DRAFT')
                                        <span class="ml-1.5 rounded-full border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">{{ __('labels.orderStatus.DRAFT') }}</span>
                                    @endif
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
                                @if ($canSeeFinancials)
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">
                                        @if ($order->total_value !== null)
                                            <span class="font-semibold text-emerald-700">{{ \App\Support\Money::amount($paidTotal) }}</span>
                                            <span class="text-muted-foreground"> / {{ \App\Support\Money::amount($order->total_value) }}</span>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-4 py-3"><x-badges.priority :status="$order->priority" /></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($order->expected_delivery_date)
                                        {{ $order->expected_delivery_date->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-28 overflow-hidden rounded-full bg-muted">
                                            <span class="block h-full rounded-full {{ $taskAvg >= 100 ? 'bg-emerald-600' : 'bg-primary' }} bar-anim" style="width: {{ $taskAvg }}%"></span>
                                        </div>
                                        <span class="text-xs font-semibold tabular-nums w-9 text-right{{ $taskAvg >= 100 ? ' text-emerald-700' : '' }}">{{ $taskAvg }}%</span>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-muted-foreground tabular-nums">{{ $taskDone }}/{{ $taskTotal }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                    {{ $order->order_date->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ================= Pagination ================= --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    {{ \App\Support\Trans::fill('orders.pagination', ['total' => $orders->total(), 'page' => $orders->currentPage(), 'totalPages' => $orders->lastPage()]) }}
                </p>
                <div class="flex items-center gap-2">
                    @if ($orders->previousPageUrl())
                        <a href="{{ $orders->appends(request()->query())->previousPageUrl() }}"
                           class="inline-flex h-11 items-center gap-1.5 rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                            {{ __('orders.previous') }}
                        </a>
                    @else
                        <span aria-disabled="true" class="inline-flex h-11 items-center gap-1.5 rounded-md border border-input bg-card px-4 text-sm font-medium text-muted-foreground opacity-50">
                            {{ __('orders.previous') }}
                        </span>
                    @endif
                    @if ($orders->nextPageUrl())
                        <a href="{{ $orders->appends(request()->query())->nextPageUrl() }}"
                           class="inline-flex h-11 items-center gap-1.5 rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            {{ __('orders.next') }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    @else
                        <span aria-disabled="true" class="inline-flex h-11 items-center gap-1.5 rounded-md border border-input bg-card px-4 text-sm font-medium text-muted-foreground opacity-50">
                            {{ __('orders.next') }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
