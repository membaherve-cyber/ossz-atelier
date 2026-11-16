@extends('layouts.app')

@section('title', __('shell.view.notifications'))

@section('content')
    <div x-data="{ tab: 'all' }">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('notif.title') }}</h1>
                <p class="text-sm text-muted-foreground mt-0.5">{{ __('notif.desc') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('notifications.read') }}">
                    @csrf
                    <input type="hidden" name="all" value="1">
                    <button type="submit" {{ $unread === 0 ? 'disabled' : '' }} class="inline-flex items-center gap-1.5 h-9 px-4 rounded-md border bg-background text-sm font-medium shadow-sm transition-colors hover:bg-muted disabled:opacity-50 disabled:pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/></svg>
                        {{ __('notif.markAll') }}
                    </button>
                </form>
            </div>
        </div>

        <div role="tablist" class="inline-flex items-center gap-1 rounded-lg bg-muted p-1 text-muted-foreground mb-4">
            <button type="button" role="tab" @click="tab = 'all'" x-bind:aria-selected="(tab === 'all').toString()" class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors" x-bind:class="tab === 'all' ? 'bg-background text-foreground shadow-sm' : 'hover:text-foreground'">
                {{ \App\Support\Trans::fill('notif.tabAll', ['count' => $notifications->total()]) }}
            </button>
            <button type="button" role="tab" @click="tab = 'unread'" x-bind:aria-selected="(tab === 'unread').toString()" class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors" x-bind:class="tab === 'unread' ? 'bg-background text-foreground shadow-sm' : 'hover:text-foreground'">
                {{ $unread > 0
                    ? \App\Support\Trans::fill('notif.tabUnreadCount', ['count' => $unread])
                    : __('notif.tabUnread') }}
            </button>
        </div>

        @if ($notifications->isNotEmpty())
            <div class="rounded-lg border divide-y max-h-[60vh] overflow-y-auto bg-card shadow-sm" x-show="tab === 'all' || {{ $unread > 0 ? 'true' : 'false' }}">
                @foreach ($notifications as $notification)
                    @php
                        // Locale title with fallback to the stored English title
                        // (spec §5 Notifier: views translate labels.notifTitle.*).
                        $titleKey = 'labels.notifTitle.'.$notification->type;
                        $titleLine = __($titleKey);
                        $titleLine = $titleLine === $titleKey ? $notification->title : $titleLine;
                    @endphp
                    <div x-show="tab === 'all' || {{ $notification->is_read ? 'false' : 'true' }}" class="{{ $notification->is_read ? '' : 'bg-amber-50/60' }}">
                        @if (! $notification->is_read)
                            <form method="POST" action="{{ route('notifications.read') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="id" value="{{ $notification->id }}">
                                <div class="w-full flex items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/50">
                                    <span class="mt-2 shrink-0" aria-hidden="true">
                                        <span class="block size-2 rounded-full bg-primary"></span>
                                    </span>
                                    <button type="submit" title="{{ __('notif.markOne') }}" class="min-w-0 flex-1 text-left">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm truncate font-semibold">{{ $titleLine }}</span>
                                            <span class="inline-flex items-center rounded-full border border-stone-300 bg-stone-50 text-stone-600 font-mono text-[10px] px-1.5 py-0">{{ $notification->type }}</span>
                                        </span>
                                        @if ($notification->message)
                                            <span class="block text-sm text-muted-foreground mt-0.5 line-clamp-2">{{ $notification->message }}</span>
                                        @endif
                                    </button>
                                    <span class="text-xs text-muted-foreground whitespace-nowrap shrink-0 mt-1">{{ $notification->created_at->format('M j, Y, H:i') }}</span>
                                    @if ($notification->task_id)
                                        <a href="{{ route('tasks.show', $notification->task_id) }}" class="shrink-0 mt-0.5 inline-flex items-center justify-center size-7 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" aria-label="{{ __('common.view') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                                        </a>
                                    @elseif ($notification->order_id)
                                        <a href="{{ route('orders.show', $notification->order_id) }}" class="shrink-0 mt-0.5 inline-flex items-center justify-center size-7 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" aria-label="{{ __('common.view') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </form>
                        @else
                            <div class="w-full flex items-start gap-3 px-4 py-3">
                                <span class="mt-2 shrink-0" aria-hidden="true">
                                    <span class="block size-2 rounded-full bg-transparent"></span>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm truncate font-medium">{{ $titleLine }}</span>
                                        <span class="inline-flex items-center rounded-full border border-stone-300 bg-stone-50 text-stone-600 font-mono text-[10px] px-1.5 py-0">{{ $notification->type }}</span>
                                    </span>
                                    @if ($notification->message)
                                        <span class="block text-sm text-muted-foreground mt-0.5 line-clamp-2">{{ $notification->message }}</span>
                                    @endif
                                </div>
                                <span class="text-xs text-muted-foreground whitespace-nowrap shrink-0 mt-1">{{ $notification->created_at->format('M j, Y, H:i') }}</span>
                                @if ($notification->task_id)
                                    <a href="{{ route('tasks.show', $notification->task_id) }}" class="shrink-0 mt-0.5 inline-flex items-center justify-center size-7 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" aria-label="{{ __('common.view') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                                    </a>
                                @elseif ($notification->order_id)
                                    <a href="{{ route('orders.show', $notification->order_id) }}" class="shrink-0 mt-0.5 inline-flex items-center justify-center size-7 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" aria-label="{{ __('common.view') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($notifications->isNotEmpty() && $unread === 0)
            <div class="rounded-lg border bg-card shadow-sm" x-show="tab === 'unread'" x-cloak>
                <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    </div>
                    <p class="font-medium">{{ __('notif.emptyTitle') }}</p>
                    <p class="text-sm text-muted-foreground max-w-sm">{{ __('notif.emptyUnreadDesc') }}</p>
                </div>
            </div>
        @endif

        @if ($notifications->isEmpty())
            <div class="rounded-lg border bg-card shadow-sm">
                <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    </div>
                    <p class="font-medium">{{ __('notif.emptyTitle') }}</p>
                    <p class="text-sm text-muted-foreground max-w-sm">{{ __('notif.emptyDesc') }}</p>
                </div>
            </div>
        @endif

        @if ($notifications->hasPages())
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4">
                <p class="text-sm text-muted-foreground">
                    {{ \App\Support\Trans::fill($notifications->total() === 1 ? 'audit.paginationSingular' : 'audit.paginationPlural', ['total' => $notifications->total(), 'page' => $notifications->currentPage(), 'totalPages' => $notifications->lastPage()]) }}
                </p>
                <div class="flex items-center gap-2">
                    @if ($notifications->onFirstPage())
                        <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            {{ __('audit.previous') }}
                        </span>
                    @else
                        <a href="{{ $notifications->previousPageUrl() }}"
                           class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-card text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            {{ __('audit.previous') }}
                        </a>
                    @endif

                    @if ($notifications->hasMorePages())
                        <a href="{{ $notifications->nextPageUrl() }}"
                           class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-card text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            {{ __('audit.next') }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                            {{ __('audit.next') }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
