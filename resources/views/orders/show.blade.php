@extends('layouts.app')

@section('title', __('shell.view.orderDetail'))

@section('content')
    @php
        use App\Support\Money;
        use App\Support\Trans;

        $paidTotal = (float) ($order->paid_total ?? 0);
        $outstanding = $order->outstandingBalance();
        $progress = $order->productionProgress();
    @endphp

    <div class="space-y-5 page-enter">
        {{-- ===================== Header ===================== --}}
        <div class="flex flex-col gap-3">
            <a href="{{ route('orders.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground -ml-2 px-2 h-8 rounded-md w-fit">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                {{ __('orders.title') }}
            </a>
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-display text-2xl font-bold tracking-tight">{{ $order->order_number }}</h1>
                        @if ($order->status === 'DRAFT')
                            <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">{{ __('labels.orderStatus.DRAFT') }}</span>
                        @endif
                        <x-badges.priority :priority="$order->priority" />
                    </div>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ $order->description }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($canManage)
                        @if ($order->status === 'DRAFT')
                            <form method="POST" action="{{ route('orders.finalize', $order->id) }}"
                                  onsubmit="return confirm('{{ __('orders.finalizeDesc') }}')">
                                @csrf
                                <button type="submit"
                                        class="inline-flex min-h-[44px] items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 btn-press">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    {{ __('orders.finalize') }}
                                </button>
                            </form>
                        @endif
                        <button type="button" x-data @click="$dispatch('order-edit')"
                                class="inline-flex min-h-[44px] items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                            {{ __('common.edit') }}
                        </button>
                        @if ($order->tasks->isEmpty())
                            <button type="button" x-data @click="$dispatch('order-delete')"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-red-200 bg-card px-4 text-sm font-medium text-red-600 shadow-sm transition-colors hover:bg-red-50 btn-press">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                {{ __('common.delete') }}
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            {{-- ===================== Main column ===================== --}}
            <div class="space-y-5 lg:col-span-2">
                {{-- Order information --}}
                <section class="rounded-xl border bg-card p-4 sm:p-6">
                    <h2 class="mb-3 font-semibold">{{ __('orders.detailOrderInfo') }}</h2>
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('orders.fieldOrderDate') }}</dt>
                            <dd class="mt-0.5 text-sm font-medium">{{ $order->order_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('orders.fieldDelivery') }}</dt>
                            <dd class="mt-0.5 text-sm font-medium">
                                @if ($order->expected_delivery_date)
                                    {{ $order->expected_delivery_date->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('orders.colPieces') }}</dt>
                            <dd class="mt-0.5 text-sm font-medium tabular-nums">{{ $order->number_of_pieces }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('orders.fieldCategory') }}</dt>
                            <dd class="mt-0.5 text-sm font-medium">{{ $order->garment_category }}</dd>
                        </div>
                        @if ($order->instructions)
                            <div class="col-span-2">
                                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('orders.fieldInstructions') }}</dt>
                                <dd class="mt-0.5 whitespace-pre-line text-sm">{{ $order->instructions }}</dd>
                            </div>
                        @endif
                        @if ($order->notes)
                            <div class="col-span-2">
                                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('orders.fieldNotes') }}</dt>
                                <dd class="mt-0.5 whitespace-pre-line text-sm">{{ $order->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                {{-- Garment pieces & tasks --}}
                <section class="rounded-xl border bg-card p-4 sm:p-6">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-semibold">{{ __('orders.detailPieces') }}</h2>
                        @if ($canManage)
                            <a href="{{ route('tasks.create', ['order' => $order->id]) }}"
                               class="inline-flex min-h-[40px] items-center gap-1.5 rounded-md border border-input bg-card px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 btn-press">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                {{ __('orders.detailAddPiece') }}
                            </a>
                        @endif
                    </div>

                    @if ($order->tasks->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <div class="rounded-full bg-muted p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M3.34 15.55 12 21l8.66-5.45"/><path d="M3.34 9.45 12 15l8.66-5.55L12 3z"/></svg>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ __('orders.detailNoTasks') }}</p>
                        </div>
                    @else
                        <div class="space-y-3 stagger">
                            @foreach ($order->tasks as $task)
                                <a href="{{ route('tasks.show', $task->id) }}"
                                   class="block rounded-lg border p-3.5 transition-colors hover:bg-muted/30 card-lift">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                                            <span class="font-mono text-[11px] font-semibold tracking-wide text-muted-foreground">{{ $task->code }}</span>
                                            <span class="font-semibold">{{ $task->garment_piece }}</span>
                                            <span class="text-xs tabular-nums text-muted-foreground">× {{ $task->quantity }}</span>
                                            <x-badges.category :category="$task->category" />
                                            <x-badges.status :status="$task->status" />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if (\App\Support\Atelier::taskIsOverdue($task->deadline, $task->status))
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold whitespace-nowrap text-red-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                                    {{ $task->deadline->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                                </span>
                                            @endif
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 text-muted-foreground" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                                        <span>
                                            @if ($task->assignedStaff)
                                                {{ __('orders.reviewAssignee') }}: <span class="font-medium text-foreground">{{ $task->assignedStaff->name }}</span>
                                            @else
                                                {{ __('orders.reviewNoAssignee') }}
                                            @endif
                                            @if ($task->deadline)
                                                · {{ __('orders.pieceDeadline') }}: {{ $task->deadline->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                            @endif
                                        </span>
                                        <span class="tabular-nums font-medium">{{ $task->progress }}%</span>
                                    </div>
                                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-muted">
                                        <span class="block h-full rounded-full {{ $task->progress >= 100 ? 'bg-emerald-600' : 'bg-primary' }} bar-anim"
                                              style="width: {{ $task->progress }}%"></span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Garments / pieces (Finance spec v1.3 §5.2) --}}
                <section class="rounded-xl border bg-card p-4 sm:p-6">
                    <h2 class="mb-3 font-semibold">{{ __('orders.garmentsTitle') }}</h2>

                    @if ($canManage)
                        <form method="POST" action="{{ route('orders.garments.store', $order->id) }}"
                              class="mb-4 flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('orders.garmentName') }}" maxlength="120" required
                                   class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                            <button type="submit"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 btn-press">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                {{ __('orders.addGarment') }}
                            </button>
                        </form>
                    @endif

                    @if ($order->garments->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <div class="rounded-full bg-muted p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ __('orders.garmentName') }}</p>
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-lg border">
                            <table class="w-full min-w-[560px] text-sm">
                                <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('orders.garmentCode') }}</th>
                                        <th class="px-4 py-3">{{ __('orders.reviewPiece') }}</th>
                                        <th class="px-4 py-3">{{ __('orders.fieldNotes') }}</th>
                                        @if ($canManage)
                                            <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->garments as $garment)
                                        <tr class="border-t transition-colors hover:bg-muted/30">
                                            <td class="whitespace-nowrap px-4 py-3 font-mono text-[11px] font-semibold tracking-wide text-muted-foreground">{{ $garment->code }}</td>
                                            <td class="px-4 py-3 font-medium">{{ $garment->name }}</td>
                                            <td class="max-w-56 px-4 py-3"><span class="block truncate text-muted-foreground" title="{{ $garment->notes }}">{{ $garment->notes ?? '—' }}</span></td>
                                            @if ($canManage)
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <form method="POST" action="{{ route('garments.update', $garment->id) }}"
                                                              class="flex items-center gap-1">
                                                            @csrf
                                                            <input type="hidden" name="notes" value="{{ $garment->notes }}" />
                                                            <input type="text" name="name" value="{{ old('name', $garment->name) }}" maxlength="120" required aria-label="{{ __('orders.garmentName') }}"
                                                                   class="h-8 w-32 rounded-md border border-input bg-card px-2 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                            <button type="submit"
                                                                    aria-label="{{ __('common.save') }} — {{ $garment->code }}"
                                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('garments.destroy', $garment->id) }}"
                                                              onsubmit="return confirm('{{ __('common.delete') }}')">
                                                            @csrf
                                                            <button type="submit"
                                                                    aria-label="{{ __('common.delete') }} — {{ $garment->code }}"
                                                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                @if ($canSeeFinancials)
                    {{-- Materials consumed (Finance spec v1.3 §5.5/§7.4) --}}
                    <section class="rounded-xl border bg-card p-4 sm:p-6">
                        <h2 class="mb-3 font-semibold">{{ __('orders.materialsSection') }}</h2>

                        <div class="flex items-center justify-between gap-2 rounded-lg bg-muted/40 px-3 py-2.5">
                            <p class="text-sm font-medium">{{ __('orders.consumedMaterialsValue') }}</p>
                            <p class="text-sm font-bold tabular-nums">{{ Money::format($order->consumedMaterialsValue()) }}</p>
                        </div>

                        @if ($order->materialIssues->isNotEmpty())
                            <div class="mt-4 overflow-x-auto rounded-lg border">
                                <table class="w-full min-w-[640px] text-sm">
                                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        <tr>
                                            <th class="px-4 py-3">{{ __('sup.colReference') }}</th>
                                            <th class="px-4 py-3">{{ __('sup.colDate') }}</th>
                                            <th class="px-4 py-3">{{ __('tasks.staff') }}</th>
                                            <th class="px-4 py-3">{{ __('tasks.status') }}</th>
                                            <th class="px-4 py-3">{{ __('mat.lines') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->materialIssues as $issue)
                                            @php
                                                $issueLines = $issue->lines->take(2);
                                                $issueMoreLines = max(0, $issue->lines->count() - 2);
                                            @endphp
                                            <tr class="border-t transition-colors hover:bg-muted/30">
                                                <td class="whitespace-nowrap px-4 py-3">
                                                    <a href="{{ route('materials.issues.show', $issue->id) }}"
                                                       class="font-mono text-[11px] font-semibold tracking-wide text-primary hover:underline">{{ $issue->code }}</a>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $issue->issue_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                                <td class="px-4 py-3">{{ $issue->staff?->name ?? '—' }}</td>
                                                <td class="px-4 py-3">
                                                    @if ($issue->status === 'OPEN')
                                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-amber-700">{{ __('mat.st_'.$issue->status) }}</span>
                                                    @elseif ($issue->status === 'CANCELLED')
                                                        <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-red-700">{{ __('mat.st_'.$issue->status) }}</span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full border border-stone-200 bg-stone-100 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-stone-600">{{ __('mat.st_'.$issue->status) }}</span>
                                                    @endif
                                                </td>
                                                <td class="max-w-64 px-4 py-3 text-muted-foreground">
                                                    <span class="block truncate">
                                                        @foreach ($issueLines as $issueLine)
                                                            {{ $issueLine->material_name }} × {{ $issueLine->qty_issued }} {{ $issueLine->unit }}@if (! $loop->last || $issueMoreLines > 0), @endif
                                                        @endforeach
                                                        @if ($issueMoreLines > 0)
                                                            <span class="font-semibold text-foreground">+{{ $issueMoreLines }}</span>
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                @endif

                {{-- Activity history --}}
                <section class="rounded-xl border bg-card p-4 sm:p-6">
                    <h2 class="mb-3 font-semibold">{{ __('orders.detailActivity') }}</h2>
                    @if ($activity->isEmpty())
                        <p class="text-sm text-muted-foreground">—</p>
                    @else
                        <ol class="relative space-y-4 border-l pl-4">
                            @foreach ($activity as $entry)
                                <li class="relative">
                                    <span class="absolute -left-[21px] top-1 size-2 rounded-full bg-primary"></span>
                                    <p class="text-sm font-medium">{{ __('audit.action.'.$entry->action) }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ $entry->user_name ?? __('audit.system') }} ·
                                        {{ $entry->created_at->locale(app()->getLocale())->translatedFormat('j M Y, H:i') }}
                                    </p>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>

            {{-- ===================== Side column ===================== --}}
            <div class="space-y-5">
                {{-- Customer --}}
                <section class="rounded-xl border bg-card p-4 sm:p-6">
                    <h2 class="mb-3 font-semibold">{{ __('orders.detailCustomer') }}</h2>
                    <div class="flex items-start gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-base font-bold text-primary" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($order->customer->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $order->customer->name }}</p>
                            <p class="font-mono text-[11px] tracking-wide text-muted-foreground">{{ $order->customer->code }}</p>
                            <a href="tel:{{ preg_replace('/\s+/', '', $order->customer->phone) }}" class="mt-1 block text-sm font-medium hover:text-primary">{{ $order->customer->phone }}</a>
                            @if ($order->customer->email)
                                <p class="truncate text-sm text-muted-foreground">{{ $order->customer->email }}</p>
                            @endif
                        </div>
                    </div>
                </section>

                @if ($canSeeFinancials)
                    {{-- Financials --}}
                    <section class="rounded-xl border bg-card p-4 sm:p-6 space-y-4">
                        <h2 class="font-semibold">{{ __('orders.detailFinancials') }}</h2>

                        @if ($order->total_value === null)
                            <p class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs font-medium text-amber-700">{{ __('orders.noValueSet') }}</p>
                        @else
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-lg bg-muted/40 p-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{{ __('frep.colValue') }}</p>
                                    <p class="mt-0.5 text-sm font-bold tabular-nums">{{ Money::amount($order->total_value) }}</p>
                                </div>
                                <div class="rounded-lg bg-emerald-50 p-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">{{ __('orders.paidLabel') }}</p>
                                    <p class="mt-0.5 text-sm font-bold tabular-nums text-emerald-700">{{ Money::amount($paidTotal) }}</p>
                                </div>
                                <div class="rounded-lg bg-amber-50 p-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700">{{ __('orders.balanceLabel') }}</p>
                                    <p class="mt-0.5 text-sm font-bold tabular-nums text-amber-700">{{ Money::amount($outstanding) }}</p>
                                </div>
                            </div>
                            <p class="text-right text-[11px] text-muted-foreground">XAF</p>
                        @endif

                        <a href="{{ route('payments.index', ['order' => $order->id]) }}"
                           class="flex min-h-[44px] w-full items-center justify-center gap-1.5 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                            {{ __('orders.recordPayment') }}
                        </a>

                        {{-- Payments --}}
                        <div>
                            <p class="mb-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('orders.paymentsTitle') }}</p>
                            @if ($order->payments->isEmpty())
                                <p class="text-sm text-muted-foreground">{{ __('orders.noPayments') }}</p>
                            @else
                                <ul class="space-y-1.5">
                                    @foreach ($order->payments as $payment)
                                        <li class="flex items-center justify-between gap-2 text-sm">
                                            <span class="min-w-0">
                                                <span class="font-mono text-[11px] font-semibold text-muted-foreground">{{ $payment->reference }}</span>
                                                <span class="block text-xs text-muted-foreground">
                                                    {{ $payment->payment_date->locale(app()->getLocale())->translatedFormat('j M Y') }} · {{ __('labels.payMethod.'.$payment->method) }}
                                                </span>
                                            </span>
                                            <span class="font-semibold tabular-nums">{{ Money::amount($payment->amount) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Cost breakdown --}}
                        <div>
                            <p class="mb-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('orders.costTitle') }}</p>
                            <dl class="space-y-1.5 text-sm">
                                <div class="flex justify-between"><dt class="text-muted-foreground">{{ __('orders.costMaterials') }}</dt><dd class="tabular-nums font-medium">{{ Money::amount($costs['materials']) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-muted-foreground">{{ __('orders.costTransport') }}</dt><dd class="tabular-nums font-medium">{{ Money::amount($costs['transport']) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-muted-foreground">{{ __('orders.costOther') }}</dt><dd class="tabular-nums font-medium">{{ Money::amount($costs['other']) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-muted-foreground">{{ __('orders.costLabour') }}</dt><dd class="tabular-nums font-medium">{{ Money::amount($costs['labour']) }}</dd></div>
                                <div class="flex justify-between border-t pt-1.5"><dt class="font-semibold">{{ __('orders.costTotal') }}</dt><dd class="tabular-nums font-bold">{{ Money::amount($costs['total']) }}</dd></div>
                                @if ($order->total_value !== null)
                                    <div class="flex justify-between rounded-md {{ $order->grossContribution() >= 0 ? 'bg-emerald-50' : 'bg-red-50' }} px-2 py-1.5">
                                        <dt class="font-semibold {{ $order->grossContribution() >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ __('orders.contributionLabel') }}</dt>
                                        <dd class="tabular-nums font-bold {{ $order->grossContribution() >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ Money::amount($order->grossContribution()) }}</dd>
                                    </div>
                                @endif
                            </dl>
                            <p class="mt-1 text-[11px] text-muted-foreground">{{ __('orders.contributionHint') }}</p>
                        </div>

                        @if ($order->labourAllocations->isNotEmpty())
                            <div>
                                <p class="mb-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('orders.costLabour') }}</p>
                                <ul class="space-y-1 text-sm">
                                    @foreach ($order->labourAllocations as $allocation)
                                        <li class="flex justify-between gap-2">
                                            <span class="min-w-0 truncate">{{ $allocation->staff_name }}@if($allocation->note) <span class="text-xs text-muted-foreground">· {{ $allocation->note }}</span>@endif</span>
                                            <span class="tabular-nums font-medium">{{ Money::amount($allocation->amount) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <button type="button" x-data @click="$dispatch('labour-allocate')"
                                class="flex min-h-[44px] w-full items-center justify-center gap-1.5 rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                            {{ __('orders.allocateLabour') }}
                        </button>
                        <p class="text-[11px] text-muted-foreground">{{ __('orders.labourHint') }}</p>
                    </section>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== Edit order modal ===================== --}}
    <div x-data="{ open: false }" @order-edit.window="open = true">
        <div x-show="open" style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
             aria-label="{{ __('orders.editTitle') }}"
             @keydown.escape.window="open = false"
             x-transition.opacity.duration.200ms>
            <div class="fixed inset-0 bg-black/50" @click="open = false" aria-hidden="true"></div>
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
                            <h2 class="text-base font-semibold">{{ __('orders.editTitle') }}</h2>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ $order->order_number }}</p>
                        </div>
                        <button type="button" @click="open = false" aria-label="{{ __('common.close') }}"
                                class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('orders.update', $order->id) }}">
                        @csrf
                        <input type="hidden" name="formId" value="order-edit" />
                        <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label for="oe-number" class="text-sm font-medium">{{ __('orders.fieldOrderNumber') }}</label>
                                <input type="text" id="oe-number" name="orderNumber" value="{{ old('orderNumber', $order->order_number) }}" maxlength="50"
                                       class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('orderNumber')<p class="text-xs text-destructive">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="oe-priority" class="text-sm font-medium">{{ __('orders.fieldPriority') }}</label>
                                <select id="oe-priority" name="priority"
                                        class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority }}" @selected(old('priority', $order->priority) === $priority)>{{ __('labels.priority.'.$priority) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label for="oe-date" class="text-sm font-medium">{{ __('orders.fieldOrderDate') }}</label>
                                <input type="date" id="oe-date" name="orderDate" value="{{ old('orderDate', $order->order_date->toDateString()) }}"
                                       class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('orderDate')<p class="text-xs text-destructive">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="oe-delivery" class="text-sm font-medium">{{ __('orders.fieldDelivery') }}</label>
                                <input type="date" id="oe-delivery" name="expectedDeliveryDate" value="{{ old('expectedDeliveryDate', $order->expected_delivery_date?->toDateString()) }}"
                                       class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                            </div>
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="oe-desc" class="text-sm font-medium">{{ __('orders.fieldDescription') }}</label>
                                <input type="text" id="oe-desc" name="description" value="{{ old('description', $order->description) }}" maxlength="500"
                                       class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('description')<p class="text-xs text-destructive">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="oe-category" class="text-sm font-medium">{{ __('orders.fieldCategory') }}</label>
                                <input type="text" id="oe-category" name="garmentCategory" value="{{ old('garmentCategory', $order->garment_category) }}" maxlength="80"
                                       class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                            </div>
                            <div class="space-y-1.5">
                                <label for="oe-pieces" class="text-sm font-medium">{{ __('orders.fieldPieces') }}</label>
                                <input type="number" id="oe-pieces" name="pieceCount" min="1" max="200" value="{{ old('pieceCount', $order->number_of_pieces) }}"
                                       class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('pieceCount')<p class="text-xs text-destructive">{{ $message }}</p>@enderror
                            </div>
                            @if ($canSeeFinancials)
                                <div class="space-y-1.5 sm:col-span-2">
                                    <label for="oe-value" class="text-sm font-medium">{{ __('orders.fieldTotalValue') }}</label>
                                    <input type="text" inputmode="decimal" id="oe-value" name="totalValue" value="{{ old('totalValue', $order->total_value !== null ? number_format($order->total_value, 2, '.', '') : '') }}"
                                           class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('totalValue')<p class="text-xs text-destructive">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="oe-instructions" class="text-sm font-medium">{{ __('orders.fieldInstructions') }}</label>
                                <textarea id="oe-instructions" name="instructions" rows="3"
                                          class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('instructions', $order->instructions) }}</textarea>
                            </div>
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="oe-notes" class="text-sm font-medium">{{ __('orders.fieldNotes') }}</label>
                                <textarea id="oe-notes" name="notes" rows="2"
                                          class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">{{ old('notes', $order->notes) }}</textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                            <button type="button" @click="open = false"
                                    class="inline-flex h-11 sm:h-9 items-center justify-center rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm hover:bg-muted/60 btn-press">{{ __('common.cancel') }}</button>
                            <button type="submit"
                                    class="inline-flex h-11 sm:h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 btn-press">{{ __('common.saveChanges') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Allocate labour modal ===================== --}}
    @if ($canSeeFinancials)
        <div x-data="{ open: false }" @labour-allocate.window="open = true">
            <div x-show="open" style="display: none;"
                 class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
                 aria-label="{{ __('orders.allocateLabour') }}"
                 @keydown.escape.window="open = false"
                 x-transition.opacity.duration.200ms>
                <div class="fixed inset-0 bg-black/50" @click="open = false" aria-hidden="true"></div>
                <div class="relative flex min-h-full items-end justify-center sm:items-center sm:p-4">
                    <div class="relative w-full max-w-md rounded-t-2xl sm:rounded-lg border border-border bg-card text-card-foreground shadow-xl"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                        <div class="border-b px-5 py-4">
                            <h2 class="text-base font-semibold">{{ __('orders.allocateLabour') }}</h2>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ $order->order_number }} · {{ __('orders.labourHint') }}</p>
                        </div>
                        <form method="POST" action="{{ route('orders.labour', $order->id) }}">
                            @csrf
                            <input type="hidden" name="formId" value="labour-allocate" />
                            <div class="space-y-4 px-5 py-4">
                                <div class="space-y-1.5">
                                    <label for="lb-staff" class="text-sm font-medium">{{ __('orders.labourStaff') }}</label>
                                    <select id="lb-staff" name="staffId" required
                                            class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                        <option value="">{{ __('orders.assignmentSelect') }}</option>
                                        @foreach ($staffList as $staff)
                                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('staffId')<p class="text-xs text-destructive">{{ $message }}</p>@enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label for="lb-amount" class="text-sm font-medium">{{ __('orders.labourAmount') }}</label>
                                    <input type="text" inputmode="decimal" id="lb-amount" name="amount" value="{{ old('amount') }}" required
                                           class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('amount')<p class="text-xs text-destructive">{{ $message }}</p>@enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label for="lb-note" class="text-sm font-medium">{{ __('orders.labourNote') }}</label>
                                    <input type="text" id="lb-note" name="note" value="{{ old('note') }}" maxlength="300"
                                           class="h-11 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                <button type="button" @click="open = false"
                                        class="inline-flex h-11 sm:h-9 items-center justify-center rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm hover:bg-muted/60 btn-press">{{ __('common.cancel') }}</button>
                                <button type="submit"
                                        class="inline-flex h-11 sm:h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 btn-press">{{ __('common.saveChanges') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===================== Delete modal ===================== --}}
    @if ($canManage && $order->tasks->isEmpty())
        <div x-data="{ open: false }" @order-delete.window="open = true">
            <div x-show="open" style="display: none"
                 class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
                 aria-label="{{ __('orders.deleteTitle') }}"
                 @keydown.escape.window="open = false"
                 x-transition.opacity.duration.200ms>
                <div class="fixed inset-0 bg-black/50" @click="open = false" aria-hidden="true"></div>
                <div class="relative flex min-h-full items-end justify-center sm:items-center sm:p-4">
                    <div class="relative w-full max-w-md rounded-t-2xl sm:rounded-lg border border-border bg-card text-card-foreground shadow-xl"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95">
                        <div class="px-5 pt-5">
                            <h2 class="text-base font-semibold">{{ __('orders.deleteTitle') }}</h2>
                            <p class="mt-1 text-sm text-muted-foreground">{{ Trans::fill('orders.deleteDesc', ['number' => $order->order_number]) }}</p>
                        </div>
                        <form method="POST" action="{{ route('orders.destroy', $order->id) }}">
                            @csrf
                            <div class="flex justify-end gap-2 px-5 py-4">
                                <button type="button" @click="open = false"
                                        class="inline-flex h-11 sm:h-9 items-center justify-center rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm hover:bg-muted/60 btn-press">{{ __('common.cancel') }}</button>
                                <button type="submit"
                                        class="inline-flex h-11 sm:h-9 items-center justify-center rounded-md bg-destructive px-4 text-sm font-medium text-white shadow-sm hover:bg-destructive/90 btn-press">{{ __('common.delete') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
