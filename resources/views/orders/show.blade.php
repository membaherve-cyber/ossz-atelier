<div class="divide-y rounded-lg border border-border bg-card text-card-foreground shadow-xl">
    
    <div class="px-5 py-4 pr-12">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="font-display text-lg font-bold">{{ $order->order_number }}</h2>
            <x-badges.priority :priority="$order->priority" />
        </div>
        <p class="mt-1 text-xs text-muted-foreground">
            {{ \App\Support\Trans::fill('orders.detailMeta', ['date' => $order->created_at->locale(app()->getLocale())->translatedFormat('j M Y'), 'name' => $order->customer->name]) }}
        </p>
    </div>

    
    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ __('orders.customerLabel') }}</p>
            <p class="mt-1 text-sm font-medium">{{ $order->customer->name }}</p>
            <p class="text-sm text-muted-foreground">{{ $order->customer->phone }}</p>
            @if ($order->customer->email)
                            <p class="truncate text-sm text-muted-foreground">{{ $order->customer->email }}</p>
                                        @endif
            @if ($order->customer->address)
                <p class="text-sm text-muted-foreground">{{ $order->customer->address }}</p>
            @endif
                    </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ __('orders.statOrderDate') }}</p>
                <p class="mt-0.5 text-sm font-medium">{{ $order->order_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ __('orders.statDelivery') }}</p>
                @if ($order->expected_delivery_date)
                                    <p class="mt-0.5 text-sm font-medium">{{ $order->expected_delivery_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</p>
                                    @else
                    <p class="mt-0.5 text-sm font-medium text-muted-foreground">—</p>
                @endif
                            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ __('orders.statPieces') }}</p>
                <p class="mt-0.5 text-sm font-medium tabular-nums">{{ $order->number_of_pieces }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ __('orders.statCategory') }}</p>
                <p class="mt-0.5 truncate text-sm font-medium">{{ $order->garment_category }}</p>
            </div>
        </div>
    </div>

    
    <div class="space-y-3 px-5 py-4">
        <div>
            <p class="mb-1 text-xs uppercase tracking-wide text-muted-foreground">{{ __('orders.descriptionLabel') }}</p>
            <p class="text-sm">{{ $order->description }}</p>
        </div>
                    @if ($order->instructions)
            <div>
                <p class="mb-1 text-xs uppercase tracking-wide text-muted-foreground">{{ __('orders.instructionsLabel') }}</p>
                <p class="whitespace-pre-line text-sm">{{ $order->instructions }}</p>
            </div>
                    @endif
        @if ($order->notes)
            <div>
                <p class="mb-1 text-xs uppercase tracking-wide text-muted-foreground">{{ __('orders.notesLabel') }}</p>
                <p class="whitespace-pre-line text-sm">{{ $order->notes }}</p>
            </div>
        @endif
                    </div>

    
    <div class="px-5 py-4">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold">{{ __('orders.productionTasks') }}</p>
            @if ($order->tasks->isNotEmpty())
                            <span class="text-xs text-muted-foreground">
                    {{ \App\Support\Trans::fill($order->tasks->where('status', 'DELIVERED')->count() === 1 ? 'orders.summaryDone' : 'orders.summaryDonePlural', ['done' => $order->tasks->where('status', 'DELIVERED')->count(), 'total' => $order->tasks->count(), 'progress' => (int) round($order->tasks->avg('progress'))]) }}
                </span>
                    @endif
        </div>

        @if ($order->tasks->isEmpty())
            <div class="flex flex-col items-center justify-center gap-2 py-8 px-4 text-center">
                <div class="rounded-full bg-muted p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/></svg>
                </div>
                <p class="font-medium">{{ __('orders.noTasksTitle') }}</p>
                <p class="text-sm text-muted-foreground max-w-sm">{{ __('orders.noTasksDesc') }}</p>
                <a href="{{ route('tasks.create', ['order' => $order->id]) }}"
                   class="mt-2 inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('orders.breakIntoTasks') }}
                </a>
            </div>
        @else
                    <div class="overflow-x-auto rounded-lg border">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <th class="px-3 py-2">{{ __('td.meta.piece') }}</th>
                            <th class="px-3 py-2">{{ __('tasks.colAssignee') }}</th>
                            <th class="px-3 py-2">{{ __('tasks.colStatus') }}</th>
                            <th class="px-3 py-2">{{ __('orders.colPriority') }}</th>
                            <th class="px-3 py-2">{{ __('tasks.colProgress') }}</th>
                            <th class="px-3 py-2">{{ __('tasks.colDeadline') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                                                    @foreach ($order->tasks as $task)
                            <tr class="{{ $task->status === 'CANCELLED' ? 'opacity-60' : '' }}">
                                <td class="px-3 py-2">
                                    <p class="max-w-40 truncate font-medium">{{ $task->garment_piece }}</p>
                                    <p class="text-xs text-muted-foreground">{{ $task->code }}</p>
                                </td>
                                <td class="max-w-32 truncate px-3 py-2">
                                    @if ($task->assignedStaff)
                                        <span>{{ $task->assignedStaff->name }}</span>
                                    @else
                                                                            <span class="text-muted-foreground">{{ __('orders.unassigned') }}</span>
                                                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <x-badges.status :status="$task->status" />
                                </td>
                                <td class="px-3 py-2">
                                    <x-badges.priority :priority="$task->priority" />
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex min-w-24 items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                            <div class="h-full rounded-full {{ $task->progress == 100 ? 'bg-emerald-600' : 'bg-primary' }}" style="width: {{ $task->progress }}%"></div>
                                        </div>
                                        <span class="w-9 text-right text-xs font-semibold tabular-nums {{ $task->progress == 100 ? 'text-emerald-700' : '' }}">{{ $task->progress }}%</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2">
                                    @if ($task->deadline === null)
                                        <span class="text-muted-foreground">—</span>
                                    @elseif ($task->isOverdue())
                                        <span class="font-semibold text-red-700">{{ $task->deadline->locale(app()->getLocale())->translatedFormat('j M Y') }}</span>
                                    @else
                                        <span class="text-muted-foreground">{{ $task->deadline->locale(app()->getLocale())->translatedFormat('j M Y') }}</span>
                                    @endif
                                </td>
                            </tr>
                                                    @endforeach
                                            </tbody>
                </table>
            </div>
            @endif
            </div>
</div>
