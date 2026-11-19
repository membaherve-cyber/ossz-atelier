@extends('layouts.app')

@section('title', __('shell.view.taskCreate'))

@section('content')
    @php
        $locale = app()->getLocale();

        // Active (non-terminal) task count per assignable staff member for
        // the "(N active)" hints in the staff selects.
        $activeCounts = \App\Models\ProductionTask::query()
            ->whereNotIn('status', \App\Models\ProductionTask::TERMINAL_STATUSES)
            ->whereNotNull('assigned_staff_id')
            ->groupBy('assigned_staff_id')
            ->selectRaw('assigned_staff_id, count(*) as aggregate')
            ->pluck('aggregate', 'assigned_staff_id');

        // Order payload for the Alpine form (dates pre-formatted; delivery
        // kept as a datetime-local value for the default piece deadline).
        $ordersJson = $orders->map(function ($order) use ($locale) {
            $activeTasks = $order->tasks->where('status', '!==', 'CANCELLED');

            return [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'customerName' => $order->customer?->name ?? '—',
                'customerPhone' => (string) ($order->customer?->phone ?? ''),
                'orderDate' => $order->order_date?->locale($locale)->translatedFormat('d M Y'),
                'expectedDelivery' => $order->expected_delivery_date?->locale($locale)->translatedFormat('d M Y'),
                'deliveryLocal' => $order->expected_delivery_date?->format('Y-m-d\TH:i'),
                'priority' => $order->priority,
                'pieceCount' => $order->number_of_pieces,
                'instructions' => (string) ($order->instructions ?? ''),
                'taskCount' => $activeTasks->count(),
                'completedCount' => $activeTasks->whereIn('status', ['APPROVED', 'READY_FOR_PICKUP', 'DELIVERED'])->count(),
                'avgProgress' => (int) round($activeTasks->avg('progress') ?? 0),
            ];
        })->values()->all();

        // Field-level validation errors keyed by piece index (the server
        // throws on pieces.{i}.garmentPiece for duplicate names).
        $pieceErrors = [];
        foreach (old('pieces', []) as $errorIndex => $oldPiece) {
            if ($errors->has('pieces.'.$errorIndex.'.garmentPiece')) {
                $pieceErrors[$errorIndex] = $errors->first('pieces.'.$errorIndex.'.garmentPiece');
            }
        }
    @endphp

    <div>
        <a href="{{ route('tasks.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors -ml-2 mb-2 px-2 h-8 rounded-md">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            {{ __('tc.backToTasks') }}
        </a>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('tc.title') }}</h1>
                <p class="text-sm text-muted-foreground mt-0.5">{{ __('tc.descriptionFull') }}</p>
            </div>
        </div>

        @if ($orders->isEmpty())
            <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
                <div class="p-0">
                    <div class="flex flex-col items-center justify-center gap-2 py-10 px-4 text-center">
                        <div class="rounded-full bg-muted p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0l-4.38 1.46A2 2 0 0 0 2.5 5.77l2.76 14.42A2 2 0 0 0 7.22 22h9.56a2 2 0 0 0 1.96-1.8l2.76-14.43a2 2 0 0 0-1.12-2.31"/><path d="M9 6v3"/><path d="M15 6v3"/><path d="M9 12h6"/></svg>
                        </div>
                        <p class="font-medium">{{ __('tc.noOrders') }}</p>
                        <p class="text-sm text-muted-foreground max-w-sm">{{ __('tc.noOrdersDesc') }}</p>
                        <div class="mt-2">
                            <a href="{{ route('orders.index') }}"
                               class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium h-8 px-3 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                                {{ __('tc.goToOrders') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('tasks.store') }}"
              x-data='{
                  orders: @json($ordersJson),
                  selectedId: @js(old('orderId', $preselectOrderId ?? '')),
                  pieces: @json(old('pieces', [])),
                  errorsByIndex: @json($pieceErrors),
                  priorityLabels: @json([
                      "LOW" => __('labels.priority.LOW'),
                      "NORMAL" => __('labels.priority.NORMAL'),
                      "HIGH" => __('labels.priority.HIGH'),
                      "URGENT" => __('labels.priority.URGENT'),
                  ]),
                  priorityClasses: @json([
                      "LOW" => "bg-stone-50 text-stone-600 border-stone-300",
                      "NORMAL" => "bg-teal-50 text-teal-700 border-teal-300",
                      "HIGH" => "bg-amber-50 text-amber-800 border-amber-300",
                      "URGENT" => "bg-red-50 text-red-800 border-red-300",
                  ]),
                  templates: @json([
                      "pieceNumber" => __('tc.pieceNumber'),
                      "removePiece" => __('tc.removePiece'),
                      "summaryOne" => __('tc.summaryOne'),
                      "summaryMany" => __('tc.summaryMany'),
                      "createOne" => __('tc.createOne'),
                      "createMany" => __('tc.createMany'),
                      "existing" => __('tc.existingTasksSummary'),
                      "existingProgress" => __('tc.existingTasksSummaryProgress'),
                      "pieceNamePlaceholder" => __('tc.pieceNamePlaceholder'),
                      "instructionsPlaceholder" => __('tc.instructionsPlaceholder'),
                      "categoryForPiece" => __('tc.categoryForPiece'),
                      "staffForPiece" => __('tc.staffForPiece'),
                      "priorityForPiece" => __('tc.priorityForPiece'),
                  ]),
                  init() {
                      if (this.pieces.length === 0) {
                          this.pieces = [this.makeRow(this.findOrder(this.selectedId))];
                      }
                  },
                  findOrder(id) {
                      for (var i = 0; i < this.orders.length; i++) {
                          if (this.orders[i].id === id) {
                              return this.orders[i];
                          }
                      }
                      return null;
                  },
                  get order() {
                      return this.findOrder(this.selectedId);
                  },
                  makeRow(order) {
                      return {
                          garmentPiece: "",
                          category: "FULL",
                          staffId: "",
                          priority: order !== null && order.priority ? order.priority : "NORMAL",
                          deadline: order !== null && order.deliveryLocal ? order.deliveryLocal : "",
                          instructions: ""
                      };
                  },
                  selectOrder(id) {
                      this.selectedId = id;
                      this.pieces = [this.makeRow(this.findOrder(id))];
                  },
                  addPiece() {
                      this.pieces.push(this.makeRow(this.order));
                  },
                  removePiece(index) {
                      if (this.pieces.length > 1) {
                          this.pieces.splice(index, 1);
                      }
                  },
                  existingSummary(order) {
                      if (order === null) {
                          return "";
                      }
                      if (order.taskCount > 0) {
                          return this.templates.existingProgress
                              .replace("{total}", order.taskCount)
                              .replace("{completed}", order.completedCount)
                              .replace("{progress}", order.avgProgress);
                      }
                      return this.templates.existing
                          .replace("{total}", order.taskCount)
                          .replace("{completed}", order.completedCount);
                  }
              }'>
            @csrf
            <div class="rounded-xl border bg-card text-card-foreground shadow-sm mb-4">
                <div class="flex items-center justify-between gap-2 border-b p-6 pb-3">
                    <h2 class="font-semibold text-base">{{ __('tc.customerOrder') }}</h2>
                </div>
                <div class="p-6 pt-4 space-y-4">
                    <div class="space-y-1.5">
                        <label for="order-select" class="text-sm font-medium leading-none">{{ __('tc.order') }}</label>
                        <select id="order-select" name="orderId" x-model="selectedId" @change="selectOrder($el.value)"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <option value="">{{ __('tc.orderPlaceholder') }}</option>
                            @foreach ($orders as $order)
                                <option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->customer?->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <template x-if="order">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 rounded-lg border bg-muted/30 p-4">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.customer') }}</p>
                                <p class="text-sm font-medium mt-0.5" x-text="order.customerName"></p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.phone') }}</p>
                                <p class="text-sm mt-0.5" x-text="order.customerPhone || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.orderDate') }}</p>
                                <p class="text-sm mt-0.5" x-text="order.orderDate"></p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.expectedDelivery') }}</p>
                                <p class="text-sm mt-0.5" x-text="order.expectedDelivery"></p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.priority') }}</p>
                                <div class="mt-1">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap"
                                          :class="priorityClasses[order.priority] || ''"
                                          x-text="priorityLabels[order.priority] || order.priority"></span>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.pieces') }}</p>
                                <p class="text-sm mt-0.5" x-text="order.pieceCount"></p>
                            </div>
                            <div class="col-span-2 md:col-span-3">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.orderInstructions') }}</p>
                                <p class="text-sm mt-0.5 whitespace-pre-wrap" x-text="order.instructions || '—'"></p>
                            </div>
                            <div class="col-span-2 md:col-span-3">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ __('tc.existingTasks') }}</p>
                                <p class="text-sm mt-0.5" x-text="existingSummary(order)"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-xl border bg-card text-card-foreground shadow-sm mb-4">
                <div class="flex items-center justify-between gap-2 border-b p-6 pb-3">
                    <h2 class="font-semibold text-base">{{ __('tc.garmentPieces') }}</h2>
                    <button type="button" @click="addPiece()"
                            class="inline-flex items-center justify-center gap-1.5 rounded-md text-xs font-medium h-8 px-3 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        {{ __('tc.addPiece') }}
                    </button>
                </div>
                <div class="p-6 pt-4 space-y-4">
                    <template x-for="(piece, index) in pieces" :key="index">
                        <div class="rounded-lg border p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold" x-text="templates.pieceNumber.replace('{number}', index + 1)"></p>
                                <template x-if="pieces.length > 1">
                                    <button type="button" @click="removePiece(index)"
                                            :aria-label="templates.removePiece.replace('{number}', index + 1)"
                                            class="inline-flex items-center justify-center size-8 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </template>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="space-y-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium leading-none" :for="'piece-' + index">{{ __('tc.pieceName') }}</label>
                                    <input type="text" :id="'piece-' + index" x-model="piece.garmentPiece" required
                                           :name="'pieces[' + index + '][garmentPiece]'"
                                           :placeholder="templates.pieceNamePlaceholder"
                                           class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <p x-show="errorsByIndex[index]" x-text="errorsByIndex[index]" class="text-xs text-destructive"></p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium leading-none" :for="'category-' + index">{{ __('tc.category') }}</label>
                                    <select :id="'category-' + index" x-model="piece.category"
                                            :name="'pieces[' + index + '][category]'"
                                            :aria-label="templates.categoryForPiece.replace('{number}', index + 1)"
                                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        @foreach (['FULL', 'CUTTING', 'SEWING', 'FINISHING', 'IRONING', 'OTHER'] as $categoryOption)
                                            <option value="{{ $categoryOption }}">{{ __('labels.category.'.$categoryOption) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium leading-none" :for="'staff-' + index">{{ __('tc.assignedStaff') }}</label>
                                    <select :id="'staff-' + index" x-model="piece.staffId"
                                            :name="'pieces[' + index + '][staffId]'"
                                            :aria-label="templates.staffForPiece.replace('{number}', index + 1)"
                                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <option value="">{{ __('tc.leaveUnassigned') }}</option>
                                        @foreach ($staffList as $staffMember)
                                            <option value="{{ $staffMember->id }}">{{ $staffMember->name }} ({{ \App\Support\Trans::fill('tc.activeCount', ['count' => $activeCounts->get($staffMember->id, 0)]) }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium leading-none" :for="'priority-' + index">{{ __('tc.priority') }}</label>
                                    <select :id="'priority-' + index" x-model="piece.priority"
                                            :name="'pieces[' + index + '][priority]'"
                                            :aria-label="templates.priorityForPiece.replace('{number}', index + 1)"
                                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        @foreach (['LOW', 'NORMAL', 'HIGH', 'URGENT'] as $priorityOption)
                                            <option value="{{ $priorityOption }}">{{ __('labels.priority.'.$priorityOption) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium leading-none" :for="'deadline-' + index">{{ __('tc.deadline') }}</label>
                                    <input type="datetime-local" :id="'deadline-' + index" x-model="piece.deadline"
                                           :name="'pieces[' + index + '][deadline]'"
                                           class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                </div>

                                <div class="space-y-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium leading-none" :for="'instructions-' + index">{{ __('tc.instructions') }}</label>
                                    <textarea :id="'instructions-' + index" x-model="piece.instructions"
                                              :name="'pieces[' + index + '][instructions]'"
                                              :placeholder="templates.instructionsPlaceholder"
                                              class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pb-2">
                <p class="text-sm text-muted-foreground"
                   x-text="(pieces.length === 1 ? templates.summaryOne : templates.summaryMany).replace('{count}', pieces.length).replace('{order}', order !== null ? order.orderNumber : '—')"></p>
                <div class="flex flex-col-reverse sm:flex-row gap-2">
                    <a href="{{ route('tasks.index') }}"
                       class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium min-h-[44px] sm:h-9 px-4 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors btn-press">
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" :disabled="selectedId === ''"
                            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md text-sm font-medium min-h-[44px] sm:h-9 px-4 w-full sm:w-auto bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:pointer-events-none disabled:opacity-50 btn-press">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <span x-text="(pieces.length === 1 ? templates.createOne : templates.createMany).replace('{count}', pieces.length)"></span>
                    </button>
                </div>
            </div>
        </form>
        @endif
    </div>
@endsection
