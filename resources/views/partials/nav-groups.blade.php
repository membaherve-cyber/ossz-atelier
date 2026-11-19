@php
    /**
     * OSSZ ATELIER — navigation groups (shared nav tree, rendered ONCE).
     *
     * Included from layouts/app for BOTH chrome contexts:
     *   @include('partials.nav-groups', ['navContext' => 'sidebar'])
     *   @include('partials.nav-groups', ['navContext' => 'drawer'])   ← links also close the drawer
     *
     * $me (and $unread) are shared with every view — partials included —
     * by the AppServiceProvider view composer. Permission checks use the
     * same Permissions::allows($me, '…') matrix the original layout used;
     * STAFF gets the personal workspace tree, MANAGEMENT/ADMIN the full
     * role-aware tree (accounting only for accounting.view holders).
     *
     * Nav items highlight ONLY on their index route (task/order detail
     * pages keep the nav neutral, exactly like the captured task-show page).
     */
    $isDrawer = ($navContext ?? 'sidebar') === 'drawer';

    $can = static fn (string $permission): bool => \App\Services\Permissions::allows($me, $permission);

    $navActive = [
        'dashboard' => request()->routeIs('dashboard'),
        'tasks' => request()->routeIs('tasks.index'),
        'create' => request()->routeIs('tasks.create'),
        'orders' => request()->routeIs('orders.index'),
        'orders.show' => request()->routeIs('orders.show'),
        'customers' => request()->routeIs('customers.index'),
        'payments' => request()->routeIs('payments.index'),
        'expenses' => request()->routeIs('expenses.index'),
        'payroll' => request()->routeIs('payroll.index'),
        'advances' => request()->routeIs('advances.index'),
        'suppliers' => request()->routeIs('suppliers.index'),
        'accounting.dashboard' => request()->routeIs('accounting.dashboard'),
        'accounting.reports' => request()->routeIs('accounting.reports'),
        'performance' => request()->routeIs('performance.index'),
        'incentives' => request()->routeIs('incentives.index'),
        'reports' => request()->routeIs('reports.index'),
        'users' => request()->routeIs('users.index'),
        'stages' => request()->routeIs('stages.index'),
        'audit' => request()->routeIs('audit.index'),
        'notifications' => request()->routeIs('notifications.index'),
    ];

    // Inline SVG icon map (lucide paths; the $icon() helper wraps them in
    // this app's standard svg shell, stroke currentColor / width 2).
    $iconPaths = [
        'home' => '<path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V10"/><path d="M10 21v-6h4v6"/>',
        'gauge' => '<path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'package' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'shirt' => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
        'chart' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'wallet' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
        'banknote' => '<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01"/><path d="M18 12h.01"/>',
        'coins' => '<circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/>',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'fileChart' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13v2"/><path d="M12 13v4"/><path d="M16 13v2"/>',
        'trending' => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        'award' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>',
        'barChart' => '<path d="M3 3v18h18"/><path d="M7 15v-4"/><path d="M11 15V9"/><path d="M15 15v-3"/><path d="M19 15V6"/>',
        'usersRound' => '<path d="M18 21a8 8 0 0 0-16 0"/><circle cx="10" cy="8" r="5"/><path d="M22 20c0-3.5-2.5-5.5-6-6"/><path d="M6 3.5a5 5 0 0 1 9 3"/>',
        'layers' => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
        'scroll' => '<path d="M15 12h-5"/><path d="M15 8h-5"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"/>',
        'clipboard' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
    ];

    $icon = static fn (string $name): string => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"'
        .' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
        .' class="size-4 shrink-0" aria-hidden="true">'.$iconPaths[$name].'</svg>';

    // Role-aware nav tree (sections in order; item keys: route, active,
    // label, icon and an optional 'can' permission gate).
    $navTree = [];

    if ($me->role === 'STAFF') {
        $navTree[] = [
            'label' => __('shell.section.workspace'),
            'items' => [
                ['route' => 'dashboard', 'active' => 'dashboard', 'label' => __('shell.nav.myDashboard'), 'icon' => 'home'],
                ['route' => 'tasks.index', 'active' => 'tasks', 'label' => __('shell.nav.myTasks'), 'icon' => 'clipboard'],
                ['route' => 'notifications.index', 'active' => 'notifications', 'label' => __('shell.nav.notifications'), 'icon' => 'bell'],
            ],
        ];
    } else {
        $navTree[] = [
            'label' => __('shell.section.overview'),
            'items' => [
                ['route' => 'dashboard', 'active' => 'dashboard', 'label' => __('shell.nav.dashboard'), 'icon' => 'gauge'],
            ],
        ];

        $navTree[] = [
            'label' => __('shell.section.production'),
            'items' => [
                ['route' => 'customers.index', 'active' => 'customers', 'label' => __('shell.nav.customers'), 'icon' => 'users', 'can' => 'customers.view'],
                ['route' => 'orders.index', 'active' => 'orders', 'label' => __('shell.nav.orders'), 'icon' => 'package', 'can' => 'orders.view'],
                ['route' => 'tasks.index', 'active' => 'tasks', 'label' => __('shell.nav.tasks'), 'icon' => 'shirt', 'can' => 'production.view.all'],
            ],
        ];

        // Confidential accounting module — Management only (the permission
        // matrix never grants accounting.* to ADMIN).
        if ($can('accounting.view')) {
            $navTree[] = [
                'label' => __('shell.section.accounting'),
                'items' => [
                    ['route' => 'accounting.dashboard', 'active' => 'accounting.dashboard', 'label' => __('shell.nav.accountingDashboard'), 'icon' => 'chart'],
                    ['route' => 'payments.index', 'active' => 'payments', 'label' => __('shell.nav.payments'), 'icon' => 'wallet'],
                    ['route' => 'expenses.index', 'active' => 'expenses', 'label' => __('shell.nav.expenses'), 'icon' => 'receipt'],
                    ['route' => 'payroll.index', 'active' => 'payroll', 'label' => __('shell.nav.payroll'), 'icon' => 'banknote'],
                    ['route' => 'advances.index', 'active' => 'advances', 'label' => __('shell.nav.advances'), 'icon' => 'coins'],
                    ['route' => 'suppliers.index', 'active' => 'suppliers', 'label' => __('shell.nav.suppliers'), 'icon' => 'truck'],
                    ['route' => 'accounting.reports', 'active' => 'accounting.reports', 'label' => __('shell.nav.financialReports'), 'icon' => 'fileChart'],
                ],
            ];
        }

        $insights = array_values(array_filter([
            ['route' => 'performance.index', 'active' => 'performance', 'label' => __('shell.nav.performance'), 'icon' => 'trending', 'can' => 'performance.view'],
            ['route' => 'incentives.index', 'active' => 'incentives', 'label' => __('shell.nav.incentives'), 'icon' => 'award', 'can' => 'incentives.view'],
            ['route' => 'reports.index', 'active' => 'reports', 'label' => __('shell.nav.reports'), 'icon' => 'barChart', 'can' => 'production.reports'],
        ], static fn (array $item): bool => ! isset($item['can']) || $can($item['can'])));

        if ($insights !== []) {
            $navTree[] = ['label' => __('shell.section.insights'), 'items' => $insights];
        }

        $administration = array_values(array_filter([
            ['route' => 'users.index', 'active' => 'users', 'label' => $me->role === 'ADMIN' ? __('shell.nav.staffAccounts') : __('shell.nav.users'), 'icon' => 'usersRound', 'can' => 'users.view'],
            ['route' => 'stages.index', 'active' => 'stages', 'label' => __('shell.nav.stages'), 'icon' => 'layers', 'can' => 'production.stages.configure'],
            ['route' => 'audit.index', 'active' => 'audit', 'label' => __('shell.nav.audit'), 'icon' => 'scroll', 'can' => 'audit.view'],
        ], static fn (array $item): bool => ! isset($item['can']) || $can($item['can'])));

        if ($administration !== []) {
            $navTree[] = ['label' => __('shell.section.administration'), 'items' => $administration];
        }
    }
@endphp

@foreach ($navTree as $section)
    <div>
        <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">{{ $section['label'] }}</p>
        <div class="flex flex-col gap-0.5">
            @foreach ($section['items'] as $item)
                @continue (isset($item['can']) && ! $can($item['can']))
                <a href="{{ route($item['route']) }}"
                   @if ($isDrawer) @click="nav = false" @endif
                   @if ($navActive[$item['active']]) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left {{ $navActive[$item['active']] ? 'bg-sidebar-primary/20 text-sidebar-primary' : 'text-stone-300 hover:bg-white/5 hover:text-white' }}">
                    {!! $icon($item['icon']) !!}
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endforeach
