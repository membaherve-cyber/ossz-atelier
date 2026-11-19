@extends('layouts.app')

@section('title', __('shell.view.customers'))

@section('content')
    <div class="space-y-4" x-data="{ registerOpen: {{ $errors->any() && old('formId') === 'cust-create' ? 'true' : 'false' }} }">

    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('cust.title') }}</h1>
            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('cust.subtitle') }}</p>
        </div>
        @if (\App\Services\Permissions::allows($me, 'customers.manage'))
            <button type="button" @click="registerOpen = true"
                    class="inline-flex min-h-[44px] sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                {{ __('cust.register') }}
            </button>
        @endif
    </div>

    <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative max-w-sm flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('cust.searchPlaceholder') }}"
                   aria-label="{{ __('cust.searchLabel') }}"
                   class="h-11 sm:h-9 w-full rounded-md border border-input bg-card py-1 pl-8 pr-3 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit"
                    class="inline-flex h-11 sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                {{ __('common.search') }}
            </button>
        </div>
    </form>

    @if (count($customers) === 0)
        <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
            <div class="rounded-full bg-muted p-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <p class="font-medium">{{ ($filters['q'] ?? '') !== '' ? __('cust.emptySearchTitle') : __('cust.emptyTitle') }}</p>
            <p class="text-sm text-muted-foreground max-w-sm">{{ ($filters['q'] ?? '') !== '' ? __('cust.emptySearchDesc') : __('cust.emptyDesc') }}</p>
        </div>
    @else
        @php
            $canManage = \App\Services\Permissions::allows($me, 'customers.manage');
        @endphp

        {{-- ================= Mobile card list ================= --}}
        <div class="md:hidden space-y-3 stagger">
            @foreach ($customers as $customer)
                <div class="rounded-xl border bg-card p-4 card-lift">
                    <div class="flex items-start gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-base font-bold text-primary"
                             aria-hidden="true">{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate font-semibold">{{ $customer->name }}</p>
                                <span class="inline-flex shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary tabular-nums">{{ $customer->ordersCount }}</span>
                            </div>
                            <p class="font-mono text-[11px] font-semibold tracking-wide text-muted-foreground">{{ $customer->code }}</p>
                            <div class="mt-2 space-y-1 text-sm">
                                <a href="tel:{{ preg_replace('/\s+/', '', $customer->phone) }}" class="flex items-center gap-1.5 font-medium hover:text-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 text-muted-foreground" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    {{ $customer->phone }}
                                </a>
                                @if ($customer->email)
                                    <p class="flex items-center gap-1.5 truncate text-muted-foreground">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 shrink-0" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                        {{ $customer->email }}
                                    </p>
                                @endif
                                <p class="text-xs text-muted-foreground">{{ $customer->created_at->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
                            </div>
                            @if ($canManage)
                                <div class="mt-2 flex justify-end gap-1">
                                    <button type="button" @click="$dispatch('cust-edit-{{ $customer->id }}')"
                                            aria-label="{{ \App\Support\Trans::fill('cust.editAria', ['name' => $customer->name]) }}"
                                            class="inline-flex size-11 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    @if ($customer->ordersCount > 0)
                                        <button type="button" disabled title="{{ __('cust.hasOrdersError') }}"
                                                aria-label="{{ \App\Support\Trans::fill('cust.deleteAria', ['name' => $customer->name]) }}"
                                                class="inline-flex size-11 cursor-not-allowed items-center justify-center rounded-md text-muted-foreground opacity-40">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                        </button>
                                    @else
                                        <button type="button" @click="$dispatch('cust-del-{{ $customer->id }}')"
                                                aria-label="{{ \App\Support\Trans::fill('cust.deleteAria', ['name' => $customer->name]) }}"
                                                class="inline-flex size-11 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ================= Desktop table ================= --}}
        <div class="hidden md:block overflow-x-auto rounded-lg border bg-card">
            <table class="w-full min-w-[860px] text-sm">
                <thead>
                    <tr class="border-b bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <th class="px-4 py-3">{{ __('cust.colCode') }}</th>
                        <th class="px-4 py-3">{{ __('cust.colName') }}</th>
                        <th class="px-4 py-3">{{ __('cust.colPhone') }}</th>
                        <th class="px-4 py-3">{{ __('cust.colEmail') }}</th>
                        <th class="px-4 py-3">{{ __('cust.colAddress') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('cust.colOrders') }}</th>
                        <th class="px-4 py-3">{{ __('cust.colRegistered') }}</th>
                        @if ($canManage)
                            <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($customers as $customer)
                        <tr class="transition-colors hover:bg-muted/30">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold tracking-wide text-muted-foreground">
                                {{ $customer->code }}
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $customer->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $customer->phone }}</td>
                            <td class="max-w-48 truncate px-4 py-3">{{ $customer->email ?: '—' }}</td>
                            <td class="max-w-56 truncate px-4 py-3 text-muted-foreground">{{ $customer->address ?: '—' }}</td>
                            <td class="px-4 py-3 text-center tabular-nums">
                                <span class="inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary">
                                    {{ $customer->ordersCount }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                {{ $customer->created_at->locale(app()->getLocale())->translatedFormat('j M Y') }}
                            </td>
                            @if ($canManage)
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="$dispatch('cust-edit-{{ $customer->id }}')"
                                                aria-label="{{ \App\Support\Trans::fill('cust.editAria', ['name' => $customer->name]) }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                        </button>
                                        @if ($customer->ordersCount > 0)
                                            <button type="button" disabled title="{{ __('cust.hasOrdersError') }}"
                                                    aria-label="{{ \App\Support\Trans::fill('cust.deleteAria', ['name' => $customer->name]) }}"
                                                    class="inline-flex size-8 cursor-not-allowed items-center justify-center rounded-md text-muted-foreground opacity-40">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            </button>
                                        @else
                                            <button type="button" @click="$dispatch('cust-del-{{ $customer->id }}')"
                                                    aria-label="{{ \App\Support\Trans::fill('cust.deleteAria', ['name' => $customer->name]) }}"
                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ============ Per-customer modal blocks (always in DOM) ============ --}}
        @foreach ($customers as $customer)
            <div x-data="{ editOpen: {{ $errors->any() && old('formId') === 'cust-edit-'.$customer->id ? 'true' : 'false' }}, deleteOpen: false }"
                 @cust-edit-{{ $customer->id }}.window="editOpen = true"
                 @cust-del-{{ $customer->id }}.window="deleteOpen = true">
                <div x-show="editOpen" style="display: none;"
                     class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
                     aria-label="{{ __('cust.editTitle') }}"
                     @keydown.escape.window="editOpen = false"
                     x-transition.opacity.duration.200ms>
                    <div class="fixed inset-0 bg-black/50" @click="editOpen = false" aria-hidden="true"></div>
                    <div class="relative flex min-h-full items-end justify-center sm:items-center sm:p-4">
                        <div class="relative w-full max-w-lg rounded-t-2xl sm:rounded-lg border border-border bg-card text-card-foreground shadow-xl max-h-[90vh] overflow-y-auto"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                            <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                                <div class="min-w-0">
                                    <h2 class="text-base font-semibold">{{ __('cust.editTitle') }}</h2>
                                    <p class="mt-0.5 text-sm text-muted-foreground">{{ \App\Support\Trans::fill('cust.editDesc', ['name' => $customer->name, 'code' => $customer->code]) }}</p>
                                </div>
                                <button type="button" @click="editOpen = false"
                                        aria-label="{{ __('common.close') }}"
                                        class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('customers.update', $customer->id) }}">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                                <input type="hidden" name="formId" value="cust-edit-{{ $customer->id }}" />
                                <div class="px-5 py-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="cust-name-{{ $customer->id }}" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldName') }}</label>
                                            <input type="text" id="cust-name-{{ $customer->id }}" name="name" value="{{ old('name', $customer->name) }}" maxlength="120" placeholder="{{ __('cust.namePlaceholder') }}" required
                                                   class="h-11 sm:h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                            @error('name')
                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="cust-phone-{{ $customer->id }}" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldPhone') }}</label>
                                            <input type="text" id="cust-phone-{{ $customer->id }}" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="40" placeholder="+237 6XX XX XX XX" required
                                                   class="h-11 sm:h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                            @error('phone')
                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="cust-email-{{ $customer->id }}" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldEmail') }}</label>
                                            <input type="email" id="cust-email-{{ $customer->id }}" name="email" value="{{ old('email', $customer->email ?? '') }}" placeholder="customer@example.com"
                                                   class="h-11 sm:h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                            @error('email')
                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="cust-address-{{ $customer->id }}" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldAddress') }}</label>
                                            <input type="text" id="cust-address-{{ $customer->id }}" name="address" value="{{ old('address', $customer->address ?? '') }}" maxlength="300" placeholder="{{ __('cust.addressPlaceholder') }}"
                                                   class="h-11 sm:h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                            @error('address')
                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="cust-notes-{{ $customer->id }}" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldNotes') }}</label>
                                            <textarea id="cust-notes-{{ $customer->id }}" name="notes" rows="3" placeholder="{{ __('cust.notesPlaceholder') }}"
                                                      class="min-h-[72px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('notes', $customer->notes ?? '') }}</textarea>
                                            @error('notes')
                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                    <button type="button" @click="editOpen = false"
                                            class="inline-flex h-11 sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                                        {{ __('common.cancel') }}
                                    </button>
                                    <button type="submit"
                                            class="inline-flex h-11 sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                                        {{ __('common.saveChanges') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @if ($customer->ordersCount === 0)
                    <div x-show="deleteOpen" style="display: none"
                         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
                         aria-label="{{ __('cust.deleteTitle') }}"
                         @keydown.escape.window="deleteOpen = false"
                         x-transition.opacity.duration.200ms>
                        <div class="fixed inset-0 bg-black/50" @click="deleteOpen = false" aria-hidden="true"></div>
                        <div class="relative flex min-h-full items-end justify-center sm:items-center sm:p-4">
                            <div class="relative w-full max-w-md rounded-t-2xl sm:rounded-lg border border-border bg-card text-card-foreground shadow-xl"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                                <div class="px-5 pt-5">
                                    <h2 class="text-base font-semibold">{{ __('cust.deleteTitle') }}</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ \App\Support\Trans::fill('cust.deleteDesc', ['name' => $customer->name]) }}</p>
                                </div>
                                <form method="POST" action="{{ route('customers.destroy', $customer->id) }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                                    <div class="flex justify-end gap-2 px-5 py-4">
                                        <button type="button" @click="deleteOpen = false"
                                                class="inline-flex h-11 sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                                            {{ __('common.cancel') }}
                                        </button>
                                        <button type="submit"
                                                class="inline-flex h-11 sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-destructive px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                                            {{ __('common.delete') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    @endif

    {{-- ================= Register modal (bottom sheet on mobile) ================= --}}
    <div x-show="registerOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
         aria-label="{{ __('cust.register') }}"
         @keydown.escape.window="registerOpen = false"
         x-transition.opacity.duration.200ms>
        <div class="fixed inset-0 bg-black/50" @click="registerOpen = false" aria-hidden="true"></div>
        <div class="relative flex min-h-full items-end justify-center sm:items-center sm:p-4">
            <div class="relative w-full max-w-lg rounded-t-2xl sm:rounded-lg border border-border bg-card text-card-foreground shadow-xl max-h-[90vh] overflow-y-auto"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold">{{ __('cust.register') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('cust.registerDesc') }}</p>
                    </div>
                    <button type="button" @click="registerOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('customers.store') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                    <input type="hidden" name="formId" value="cust-create" />
                    <div class="px-5 py-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="cust-name" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldName') }}</label>
                                <input type="text" id="cust-name" name="name" value="{{ old('name') }}" maxlength="120" placeholder="{{ __('cust.namePlaceholder') }}" required
                                       class="h-11 sm:h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('name')
                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="cust-phone" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldPhone') }}</label>
                                <input type="text" id="cust-phone" name="phone" value="{{ old('phone') }}" maxlength="40" placeholder="+237 6XX XX XX XX" required
                                       class="h-11 sm:h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('phone')
                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="cust-email" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldEmail') }}</label>
                                <input type="email" id="cust-email" name="email" value="{{ old('email') }}" placeholder="customer@example.com"
                                       class="h-11 sm:h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('email')
                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="cust-address" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldAddress') }}</label>
                                <input type="text" id="cust-address" name="address" value="{{ old('address') }}" maxlength="300" placeholder="{{ __('cust.addressPlaceholder') }}"
                                       class="h-11 sm:h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('address')
                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="cust-notes" class="mb-1.5 block text-sm font-medium">{{ __('cust.fieldNotes') }}</label>
                                <textarea id="cust-notes" name="notes" rows="3" placeholder="{{ __('cust.notesPlaceholder') }}"
                                          class="min-h-[72px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="registerOpen = false"
                                class="inline-flex h-11 sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex h-11 sm:h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring btn-press">
                            {{ __('cust.register') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection
