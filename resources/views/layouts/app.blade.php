<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — OSSZ ATELIER</title>
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/alpine.min.js" defer></script>
    <style>[x-cloak]{display:none !important;}</style>
</head>
<body class="bg-background text-foreground antialiased">

@php
    /**
     * OSSZ ATELIER — application layout (view contract: every authenticated
     * page @extends this file). Mirrors the captured live pages exactly:
     * desktop sidebar + mobile drawer + centered-logo header + footer.
     *
     * $me and $unread are shared with every view by AppServiceProvider.
     */
    $can = static fn (string $permission): bool => \App\Services\Permissions::allows($me, $permission);

    // Nav items highlight ONLY on their index route (task/order detail pages
    // keep the nav neutral, exactly like the captured task-show page).
    $navActive = [
        'dashboard' => $request->routeIs('dashboard'),
        'tasks' => $request->routeIs('tasks.index'),
        'create' => $request->routeIs('tasks.create'),
        'orders' => $request->routeIs('orders.index'),
        'customers' => $request->routeIs('customers.index'),
        'performance' => $request->routeIs('performance.index'),
        'incentives' => $request->routeIs('incentives.index'),
        'reports' => $request->routeIs('reports.index'),
        'users' => $request->routeIs('users.index'),
        'stages' => $request->routeIs('stages.index'),
        'audit' => $request->routeIs('audit.index'),
        'notifications' => $request->routeIs('notifications.index'),
    ];

    // Avatar initials ("Aminata Sow" -> "AS").
    $initials = strtoupper(collect(explode(' ', trim((string) $me->name)))
        ->filter()
        ->map(static fn (string $word): string => mb_substr($word, 0, 1))
        ->take(2)
        ->implode(''));
@endphp

<div class="min-h-screen flex flex-col bg-background" x-data="{ nav: false }" @keydown.escape.window="nav = false">
    <div class="flex flex-1 min-h-0">

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:flex w-60 shrink-0 flex-col bg-sidebar text-sidebar-foreground border-r border-sidebar-border sticky top-0 h-screen">
            <div class="flex items-center justify-center px-5 h-16 border-b border-sidebar-border">
                <div class="text-center min-w-0">
                    <p class="font-display font-bold leading-none">OSSZ ATELIER</p>
                    <p class="text-[9px] tracking-[0.2em] text-stone-400 mt-1.5">{{ __('shell.brandTag') }}</p>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto">
                <nav class="flex flex-col gap-5 px-3 py-4" aria-label="{{ __('shell.navAria') }}">
                    @if ($me->role === 'STAFF')
                        <div>
                            <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.workspace') }}</p>
                            <div class="flex flex-col gap-0.5">
                                <a href="{{ route('dashboard') }}" @click="nav = false" @if ($navActive['dashboard']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['dashboard'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                                    <span class="truncate">{{ __('shell.nav.myDashboard') }}</span>
                                </a>
                                <a href="{{ route('tasks.index') }}" @click="nav = false" @if ($navActive['tasks']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['tasks'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                                    <span class="truncate">{{ __('shell.nav.myTasks') }}</span>
                                </a>
                                <a href="{{ route('notifications.index') }}" @click="nav = false" @if ($navActive['notifications']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['notifications'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                                    <span class="truncate">{{ __('shell.nav.notifications') }}</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <div>
                            <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.overview') }}</p>
                            <div class="flex flex-col gap-0.5">
                                <a href="{{ route('dashboard') }}" @click="nav = false" @if ($navActive['dashboard']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['dashboard'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                                    <span class="truncate">{{ __('shell.nav.dashboard') }}</span>
                                </a>
                            </div>
                        </div>
                        <div>
                            <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.production') }}</p>
                            <div class="flex flex-col gap-0.5">
                                <a href="{{ route('tasks.index') }}" @click="nav = false" @if ($navActive['tasks']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['tasks'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg>
                                    <span class="truncate">{{ __('shell.nav.tasks') }}</span>
                                </a>
                                @if ($can('production.create.tasks'))
                                    <a href="{{ route('tasks.create') }}" @click="nav = false" @if ($navActive['create']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['create'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                                        <span class="truncate">{{ __('shell.nav.taskCreate') }}</span>
                                    </a>
                                @endif
                                @if ($can('orders.view'))
                                    <a href="{{ route('orders.index') }}" @click="nav = false" @if ($navActive['orders']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['orders'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                        <span class="truncate">{{ __('shell.nav.orders') }}</span>
                                    </a>
                                @endif
                                @if ($can('customers.view'))
                                    <a href="{{ route('customers.index') }}" @click="nav = false" @if ($navActive['customers']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['customers'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        <span class="truncate">{{ __('shell.nav.customers') }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                        @if ($can('performance.view') || $can('incentives.view') || $can('production.reports'))
                            <div>
                                <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.insights') }}</p>
                                <div class="flex flex-col gap-0.5">
                                    @if ($can('performance.view'))
                                        <a href="{{ route('performance.index') }}" @click="nav = false" @if ($navActive['performance']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['performance'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                                            <span class="truncate">{{ __('shell.nav.performance') }}</span>
                                        </a>
                                    @endif
                                    @if ($can('incentives.view'))
                                        <a href="{{ route('incentives.index') }}" @click="nav = false" @if ($navActive['incentives']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['incentives'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
                                            <span class="truncate">{{ __('shell.nav.incentives') }}</span>
                                        </a>
                                    @endif
                                    @if ($can('production.reports'))
                                        <a href="{{ route('reports.index') }}" @click="nav = false" @if ($navActive['reports']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['reports'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13v2"/><path d="M12 13v4"/><path d="M16 13v2"/></svg>
                                            <span class="truncate">{{ __('shell.nav.reports') }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if ($can('users.view') || $can('production.stages.configure') || $can('audit.view'))
                            <div>
                                <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.administration') }}</p>
                                <div class="flex flex-col gap-0.5">
                                    @if ($can('users.view'))
                                        <a href="{{ route('users.index') }}" @click="nav = false" @if ($navActive['users']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['users'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/><path d="M8 10v4"/><path d="M12 10v2"/><path d="M16 10v4"/></svg>
                                            <span class="truncate">{{ $me->role === 'ADMIN' ? __('shell.nav.staffAccounts') : __('shell.nav.users') }}</span>
                                        </a>
                                    @endif
                                    @if ($can('production.stages.configure'))
                                        <a href="{{ route('stages.index') }}" @click="nav = false" @if ($navActive['stages']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['stages'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                                            <span class="truncate">{{ __('shell.nav.stages') }}</span>
                                        </a>
                                    @endif
                                    @if ($can('audit.view'))
                                        <a href="{{ route('audit.index') }}" @click="nav = false" @if ($navActive['audit']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['audit'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M15 12h-5"/><path d="M15 8h-5"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"/></svg>
                                            <span class="truncate">{{ __('shell.nav.audit') }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </nav>
            </div>
            <div class="p-4 border-t border-sidebar-border text-[11px] text-stone-500">
                {{ __('shell.independent') }}
            </div>
        </aside>

        {{-- Mobile drawer --}}
        <div x-show="nav" x-cloak x-transition.opacity.duration.200ms class="fixed inset-0 z-40 bg-black/60 lg:hidden" @click="nav = false" aria-hidden="true"></div>
        <aside id="mobile-nav" x-show="nav" x-cloak
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed left-0 top-0 z-50 h-full w-72 max-w-[85vw] flex flex-col bg-sidebar text-sidebar-foreground border-r border-sidebar-border lg:hidden"
               role="dialog" aria-modal="true" aria-label="{{ __('shell.navigation') }}">
            <div class="relative flex items-center justify-center px-5 h-16 border-b border-sidebar-border">
                <div class="text-center min-w-0">
                    <p class="font-display font-bold leading-none">OSSZ ATELIER</p>
                    <p class="text-[9px] tracking-[0.2em] text-stone-400 mt-1.5">{{ __('shell.brandTag') }}</p>
                </div>
                <button type="button" @click="nav = false" class="absolute right-3 inline-flex items-center justify-center size-8 rounded-md text-stone-400 hover:text-white hover:bg-white/5 transition-colors" aria-label="{{ __('common.close') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto flex flex-col gap-5 px-3 py-4" aria-label="{{ __('shell.navAria') }}">
                @if ($me->role === 'STAFF')
                    <div>
                        <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.workspace') }}</p>
                        <div class="flex flex-col gap-0.5">
                            <a href="{{ route('dashboard') }}" @click="nav = false" @if ($navActive['dashboard']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['dashboard'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                                <span class="truncate">{{ __('shell.nav.myDashboard') }}</span>
                            </a>
                            <a href="{{ route('tasks.index') }}" @click="nav = false" @if ($navActive['tasks']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['tasks'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                                <span class="truncate">{{ __('shell.nav.myTasks') }}</span>
                            </a>
                            <a href="{{ route('notifications.index') }}" @click="nav = false" @if ($navActive['notifications']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['notifications'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                                <span class="truncate">{{ __('shell.nav.notifications') }}</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div>
                        <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.overview') }}</p>
                        <div class="flex flex-col gap-0.5">
                            <a href="{{ route('dashboard') }}" @click="nav = false" @if ($navActive['dashboard']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['dashboard'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                                <span class="truncate">{{ __('shell.nav.dashboard') }}</span>
                            </a>
                        </div>
                    </div>
                    <div>
                        <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.production') }}</p>
                        <div class="flex flex-col gap-0.5">
                            <a href="{{ route('tasks.index') }}" @click="nav = false" @if ($navActive['tasks']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['tasks'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg>
                                <span class="truncate">{{ __('shell.nav.tasks') }}</span>
                            </a>
                            @if ($can('production.create.tasks'))
                                <a href="{{ route('tasks.create') }}" @click="nav = false" @if ($navActive['create']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['create'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                                    <span class="truncate">{{ __('shell.nav.taskCreate') }}</span>
                                </a>
                            @endif
                            @if ($can('orders.view'))
                                <a href="{{ route('orders.index') }}" @click="nav = false" @if ($navActive['orders']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['orders'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                    <span class="truncate">{{ __('shell.nav.orders') }}</span>
                                </a>
                            @endif
                            @if ($can('customers.view'))
                                <a href="{{ route('customers.index') }}" @click="nav = false" @if ($navActive['customers']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['customers'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <span class="truncate">{{ __('shell.nav.customers') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                    @if ($can('performance.view') || $can('incentives.view') || $can('production.reports'))
                        <div>
                            <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.insights') }}</p>
                            <div class="flex flex-col gap-0.5">
                                @if ($can('performance.view'))
                                    <a href="{{ route('performance.index') }}" @click="nav = false" @if ($navActive['performance']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['performance'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                                        <span class="truncate">{{ __('shell.nav.performance') }}</span>
                                    </a>
                                @endif
                                @if ($can('incentives.view'))
                                    <a href="{{ route('incentives.index') }}" @click="nav = false" @if ($navActive['incentives']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['incentives'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
                                        <span class="truncate">{{ __('shell.nav.incentives') }}</span>
                                    </a>
                                @endif
                                @if ($can('production.reports'))
                                    <a href="{{ route('reports.index') }}" @click="nav = false" @if ($navActive['reports']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['reports'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13v2"/><path d="M12 13v4"/><path d="M16 13v2"/></svg>
                                        <span class="truncate">{{ __('shell.nav.reports') }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if ($can('users.view') || $can('production.stages.configure') || $can('audit.view'))
                        <div>
                            <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ __('shell.section.administration') }}</p>
                            <div class="flex flex-col gap-0.5">
                                @if ($can('users.view'))
                                    <a href="{{ route('users.index') }}" @click="nav = false" @if ($navActive['users']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['users'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/><path d="M8 10v4"/><path d="M12 10v2"/><path d="M16 10v4"/></svg>
                                        <span class="truncate">{{ $me->role === 'ADMIN' ? __('shell.nav.staffAccounts') : __('shell.nav.users') }}</span>
                                    </a>
                                @endif
                                @if ($can('production.stages.configure'))
                                    <a href="{{ route('stages.index') }}" @click="nav = false" @if ($navActive['stages']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['stages'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                                        <span class="truncate">{{ __('shell.nav.stages') }}</span>
                                    </a>
                                @endif
                                @if ($can('audit.view'))
                                    <a href="{{ route('audit.index') }}" @click="nav = false" @if ($navActive['audit']) aria-current="page" @endif class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive['audit'] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M15 12h-5"/><path d="M15 8h-5"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"/></svg>
                                        <span class="truncate">{{ __('shell.nav.audit') }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-30 relative flex items-center gap-2 sm:gap-3 h-16 px-4 sm:px-6 border-b bg-background/95 backdrop-blur">
                {{-- Mobile hamburger --}}
                <button type="button" class="lg:hidden inline-flex items-center justify-center size-9 -ml-1.5 rounded-md hover:bg-muted transition-colors" @click="nav = true" aria-label="{{ __('shell.openMenu') }}" aria-controls="mobile-nav">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>

                {{-- Centered clickable logo --}}
                <a href="{{ route('dashboard') }}" aria-label="{{ __('shell.logoAria') }}" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-10 shrink-0 rounded-md max-[359px]:static max-[359px]:translate-x-0 max-[359px]:translate-y-0 max-[359px]:mx-auto">
                    <img src="/img/ossz-logo.png" alt="{{ __('login.logoAlt') }}" class="h-7 sm:h-9 w-auto rounded-md ring-1 ring-black/10">
                </a>

                {{-- Page title --}}
                <h1 class="hidden sm:block font-display text-lg font-semibold truncate min-w-0 max-w-[calc(50%-6.5rem)]">@yield('title')</h1>

                {{-- Right cluster --}}
                <div class="ml-auto max-[359px]:ml-0 flex items-center gap-1 sm:gap-1.5">
                    {{-- Language pills (title attribute intentionally shows the
                         OTHER locale's label, exactly like the live pages) --}}
                    <div role="group" aria-label="{{ __('shell.languageAria') }}" title="{{ __('shell.languageTitle', [], app()->getLocale() === 'fr' ? 'en' : 'fr') }}" class="flex items-center gap-0.5 rounded-full border bg-background p-0.5 select-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden sm:block size-3.5 ml-1 text-muted-foreground" aria-hidden="true"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>
                        <a href="{{ route('locale.switch', 'en') }}" class="rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide transition-colors {{ app()->getLocale() === 'en' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground' }}" @if (app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
                        <a href="{{ route('locale.switch', 'fr') }}" class="rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide transition-colors {{ app()->getLocale() === 'fr' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground' }}" @if (app()->getLocale() === 'fr') aria-current="true" @endif>FR</a>
                    </div>

                    {{-- Notification bell --}}
                    <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center justify-center size-8 sm:size-9 rounded-md hover:bg-muted transition-colors" aria-label="{{ __('shell.bellAria') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 sm:size-5" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        @if ($unread > 0)
                            <span class="absolute -top-0.5 -right-0.5 min-w-4.5 h-4.5 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center">{{ $unread }}</span>
                        @endif
                    </a>

                    {{-- Account menu --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.outside="open = false" @keydown.escape.window="open = false" :aria-expanded="open.toString()" aria-haspopup="menu" aria-label="{{ __('shell.accountMenu') }}" class="flex items-center gap-2 rounded-full hover:bg-muted p-0 sm:p-1 sm:pr-2 transition-colors">
                            <span class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ $initials }}</span>
                            <span class="hidden sm:block text-left">
                                <span class="block text-sm font-medium leading-none">{{ $me->name }}</span>
                                <span class="block text-[11px] text-muted-foreground mt-0.5">{{ $me->code }} · {{ __('labels.role.'.$me->role) }}</span>
                            </span>
                        </button>
                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-60 rounded-lg border bg-card shadow-lg py-1 z-50" role="menu">
                            <div class="px-3 py-2">
                                <p class="text-sm font-medium">{{ $me->name }}</p>
                                <p class="text-xs text-muted-foreground font-normal truncate">{{ $me->email }}</p>
                                <p class="text-xs text-muted-foreground font-normal mt-0.5">{{ __('labels.role.'.$me->role) }}</p>
                                @if ($me->code)
                                    <p class="text-xs text-muted-foreground font-normal mt-0.5 font-mono">{{ $me->code }}</p>
                                @endif
                            </div>
                            <div class="h-px bg-border my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" role="menuitem" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-700 hover:bg-muted transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                                    {{ __('shell.user.signOut') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Session toast (success / error flash; no toast markup was captured
                 because the live pages carried no flash at capture time) --}}
            @if (session('success') || session('error'))
                <div x-data="{ visible: true }"
                     x-init="setTimeout(() => visible = false, 5000)"
                     x-show="visible" x-cloak
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-2"
                     class="fixed bottom-4 right-4 z-[60] w-full max-w-sm rounded-lg border bg-card shadow-lg px-4 py-3 text-sm"
                     role="status" aria-live="polite">
                    @if (session('success'))
                        <p class="flex items-start gap-2 text-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 mt-0.5 shrink-0 text-emerald-600" aria-hidden="true"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                            <span>{{ session('success') }}</span>
                        </p>
                    @else
                        <p class="flex items-start gap-2 text-red-700" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 mt-0.5 shrink-0 text-red-600" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            <span>{{ session('error') }}</span>
                        </p>
                    @endif
                </div>
            @endif

            <main class="flex-1 px-4 sm:px-6 py-6 w-full max-w-[1400px] mx-auto">
                @yield('content')
            </main>

            <footer class="mt-auto border-t bg-background">
                <div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-3 flex flex-col sm:flex-row items-center justify-between gap-1 text-xs text-muted-foreground" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
                    <p>{{ \App\Support\Trans::fill('shell.footer.left', ['year' => now()->year]) }}</p>
                    <p>{{ __('shell.footer.right') }}</p>
                </div>
            </footer>
        </div>
    </div>
</div>

</body>
</html>
