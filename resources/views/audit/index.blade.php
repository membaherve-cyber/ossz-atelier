@extends('layouts.app')

@section('title', __('shell.view.audit'))

@section('content')
    <div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('audit.title') }}</h1>
                <p class="text-sm text-muted-foreground mt-0.5">{{ __('audit.description') }}</p>
            </div>
        </div>

        <section class="rounded-lg border bg-card shadow-sm mb-4">
            <div class="p-4">
                <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                    <div class="space-y-1.5">
                        <label for="audit-action" class="block text-xs font-medium text-muted-foreground">{{ __('audit.actionFilter') }}</label>
                        <select id="audit-action" name="action" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                            <option value="">{{ __('audit.allActions') }}</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" {{ $filters['action'] === $action ? 'selected' : '' }}>{{ __('audit.action.'.$action) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="audit-user" class="block text-xs font-medium text-muted-foreground">{{ __('audit.col.user') }}</label>
                        <select id="audit-user" name="user" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ $filters['user'] === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="audit-from" class="block text-xs font-medium text-muted-foreground">{{ __('audit.from') }}</label>
                        <input type="date" id="audit-from" name="from" value="{{ $filters['from'] }}" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                    </div>

                    <div class="space-y-1.5">
                        <label for="audit-to" class="block text-xs font-medium text-muted-foreground">{{ __('audit.to') }}</label>
                        <input type="date" id="audit-to" name="to" value="{{ $filters['to'] }}" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring">
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-md bg-primary text-primary-foreground text-sm font-medium shadow-sm hover:bg-primary/90 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            {{ __('common.filter') }}
                        </button>
                        <a href="{{ route('audit.index') }}" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-md border bg-background text-sm font-medium shadow-sm hover:bg-muted transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            {{ __('audit.reset') }}
                        </a>
                    </div>
                </form>
            </div>
        </section>

        @if ($logs->isNotEmpty())
            <div class="rounded-lg border overflow-x-auto bg-card shadow-sm">
                <table class="w-full caption-bottom text-sm min-w-[900px]">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th scope="col" class="h-10 px-3 text-left align-middle font-medium text-muted-foreground whitespace-nowrap w-44">{{ __('audit.col.date') }}</th>
                            <th scope="col" class="h-10 px-3 text-left align-middle font-medium text-muted-foreground whitespace-nowrap w-40">{{ __('audit.col.user') }}</th>
                            <th scope="col" class="h-10 px-3 text-left align-middle font-medium text-muted-foreground whitespace-nowrap w-52">{{ __('audit.col.action') }}</th>
                            <th scope="col" class="h-10 px-3 text-left align-middle font-medium text-muted-foreground whitespace-nowrap w-44">{{ __('audit.col.entity') }}</th>
                            <th scope="col" class="h-10 px-3 text-left align-middle font-medium text-muted-foreground">{{ __('audit.col.details') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                // Pretty-print the details JSON for the tooltip and
                                // collapse it to single spaces for the truncated cell
                                // (port of prettyDetails() in audit-view.tsx).
                                $detailsPretty = '';
                                if ($log->details !== null && $log->details !== '') {
                                    $decodedDetails = json_decode((string) $log->details, true);
                                    $detailsPretty = $decodedDetails === null
                                        ? (string) $log->details
                                        : json_encode($decodedDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                }
                            @endphp
                            <tr class="border-t transition-colors hover:bg-muted/50">
                                <td class="px-3 py-2.5 align-middle text-muted-foreground whitespace-nowrap">
                                    {{ $log->created_at->format('M j, Y, H:i') }}
                                </td>
                                <td class="px-3 py-2.5 align-middle font-medium whitespace-nowrap">
                                    {{ $log->user_name ?? __('audit.system') }}
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <span class="inline-flex items-center rounded-full border border-stone-300 bg-stone-50 text-stone-700 font-mono text-xs px-2 py-0.5 whitespace-nowrap">{{ __('audit.action.'.$log->action) }}</span>
                                </td>
                                <td class="px-3 py-2.5 align-middle whitespace-nowrap">
                                    @if ($log->entity_type)
                                        <span class="text-sm">
                                            <span class="font-medium">{{ $log->entity_type }}</span>
                                            @if ($log->entity_id)
                                                <span class="font-mono text-xs text-muted-foreground"> · {{ substr($log->entity_id, 0, 8) }}</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 align-middle max-w-72">
                                    @if ($detailsPretty !== '')
                                        <span title="{{ $detailsPretty }}" class="block max-w-72 truncate font-mono text-xs text-muted-foreground">{{ preg_replace('/\s+/', ' ', $detailsPretty) }}</span>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-4">
                <p class="text-sm text-muted-foreground">
                    {{ \App\Support\Trans::fill($logs->total() === 1 ? 'audit.paginationSingular' : 'audit.paginationPlural', ['total' => $logs->total(), 'page' => $logs->currentPage(), 'totalPages' => $logs->lastPage()]) }}
                </p>
                <div class="flex items-center gap-2">
                    @if ($logs->onFirstPage())
                        <span class="inline-flex items-center gap-1 h-8 px-3 rounded-md border text-sm font-medium opacity-50 cursor-not-allowed" aria-disabled="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            {{ __('audit.previous') }}
                        </span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}"
                           class="inline-flex items-center gap-1 h-8 px-3 rounded-md border border-input bg-card text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            {{ __('audit.previous') }}
                        </a>
                    @endif

                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}"
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
        @else
            <div class="rounded-lg border bg-card shadow-sm">
                <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                    <div class="rounded-full bg-muted p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M15 12h-5"/><path d="M15 8h-5"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"/></svg>
                    </div>
                    <p class="font-medium">{{ __('audit.emptyTitle') }}</p>
                    <p class="text-sm text-muted-foreground max-w-sm">{{ __('audit.emptyDescription') }}</p>
                </div>
            </div>
        @endif
    </div>
@endsection
