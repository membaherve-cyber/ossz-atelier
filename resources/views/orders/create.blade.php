@extends('layouts.app')

@section('title', __('shell.view.orderCreate'))

@section('content')
    @php
        use Illuminate\Support\Js;
        use App\Support\Trans;

        // On validation failure, reopen the wizard at the step holding the
        // first error (customer=1, order=2, pieces=3).
        $stepOnError = 1;
        if ($errors->any()) {
            foreach ($errors->keys() as $key) {
                if (str_starts_with($key, 'pieces')) { $stepOnError = 3; break; }
                if (in_array($key, ['orderNumber', 'orderDate', 'expectedDeliveryDate', 'description', 'garmentCategory', 'priority', 'totalValue', 'instructions', 'notes'])) { $stepOnError = 2; }
            }
        }

        $oldPieces = old('pieces');
        $initialPieces = [];
        if (is_array($oldPieces)) {
            foreach ($oldPieces as $p) {
                $assignments = [];
                foreach ((array) ($p['assignments'] ?? []) as $a) {
                    if (! empty($a['staffId'])) {
                        $assignments[] = ['staffId' => (string) $a['staffId'], 'note' => (string) ($a['note'] ?? '')];
                    }
                }
                $initialPieces[] = [
                    'name' => (string) ($p['name'] ?? ''),
                    'description' => (string) ($p['description'] ?? ''),
                    'quantity' => (int) ($p['quantity'] ?? 1),
                    'category' => (string) ($p['category'] ?? 'FULL'),
                    'priority' => (string) ($p['priority'] ?? ''),
                    'deadline' => (string) ($p['deadline'] ?? ''),
                    'instructions' => (string) ($p['instructions'] ?? ''),
                    'assignments' => $assignments !== [] ? $assignments : [['staffId' => '', 'note' => '']],
                ];
            }
        }
        if ($initialPieces === []) {
            $initialPieces = [[
                'name' => '', 'description' => '', 'quantity' => 1, 'category' => 'FULL',
                'priority' => '', 'deadline' => '', 'instructions' => '',
                'assignments' => [['staffId' => '', 'note' => '']],
            ]];
        }
    @endphp

    <div class="space-y-5"
         x-data="{
            step: {{ $errors->any() ? $stepOnError : 1 }},
            customerMode: '{{ old('customerMode', 'existing') }}',
            customerId: '{{ old('customerId', $preselectCustomer) }}',
            newCustomer: { name: '{{ old('newCustomerName') }}', phone: '{{ old('newCustomerPhone') }}', email: '{{ old('newCustomerEmail') }}', address: '{{ old('newCustomerAddress') }}' },
            order: {
                number: '{{ old('orderNumber') }}',
                date: '{{ old('orderDate', now()->toDateString()) }}',
                delivery: '{{ old('expectedDeliveryDate') }}',
                description: '{{ old('description') }}',
                category: '{{ old('garmentCategory') }}',
                priority: '{{ old('priority', 'NORMAL') }}',
                value: '{{ old('totalValue') }}',
                instructions: `{{ old('instructions') }}`,
                notes: `{{ old('notes') }}`
            },
            pieces: {{ Js::from($initialPieces) }},
            staffList: {{ Js::from($staffList->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'role' => $s->role, 'title' => $s->job_title])) }},
            customers: {{ Js::from($customers->map(fn ($c) => ['id' => $c->id, 'label' => $c->name.' · '.$c->phone])) }},
            submitting: false,

            pieceCount() { return this.pieces.length },
            taskCount() {
                let n = 0;
                for (const p of this.pieces) {
                    n += Math.max(1, p.assignments.filter(a => a.staffId !== '').length);
                }
                return n;
            },
            quantityTotal() { return this.pieces.reduce((s, p) => s + (parseInt(p.quantity) || 1), 0) },
            addPiece() {
                this.pieces.push({ name: '', description: '', quantity: 1, category: 'FULL', priority: '', deadline: '', instructions: '', assignments: [{ staffId: '', note: '' }] });
                this.$nextTick(() => { const cards = document.querySelectorAll('[data-piece-card]'); cards[cards.length - 1]?.scrollIntoView({ behavior: 'smooth', block: 'start' }); document.querySelector('[data-piece-card]:last-of-type input')?.focus(); });
            },
            removePiece(i) { this.pieces.splice(i, 1) },
            addAssignment(i) { this.pieces[i].assignments.push({ staffId: '', note: '' }) },
            removeAssignment(i, j) {
                this.pieces[i].assignments.splice(j, 1);
                if (this.pieces[i].assignments.length === 0) this.pieces[i].assignments.push({ staffId: '', note: '' });
            },
            staffName(id) { const s = this.staffList.find(x => x.id === id); return s ? s.name : '' },
            customerLabel() { const c = this.customers.find(x => x.id === this.customerId); return c ? c.label : '' },
            canContinue() {
                if (this.step === 1) return this.customerMode === 'new' ? (this.newCustomer.name.trim() !== '' && this.newCustomer.phone.trim() !== '') : this.customerId !== '';
                if (this.step === 2) return this.order.date !== '' && this.order.description.trim() !== '';
                if (this.step === 3) return this.pieces.every(p => p.name.trim() !== '');
                return true;
            },
            next() { if (this.canContinue() && this.step < 4) this.step++ },
            back() { if (this.step > 1) this.step-- },
         }">

        {{-- ===================== Header ===================== --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <a href="{{ route('orders.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground -ml-2 mb-1 px-2 h-8 rounded-md">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    {{ __('orders.title') }}
                </a>
                <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('orders.createTitle') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('orders.createSubtitle') }}</p>
            </div>
        </div>

        {{-- ===================== Step indicator ===================== --}}
        <nav class="rounded-xl border bg-card p-4" aria-label="{{ __('orders.createTitle') }}">
            <ol class="flex items-center gap-1 sm:gap-2">
                @php
                    $steps = [
                        1 => ['key' => 'stepCustomer', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
                        2 => ['key' => 'stepOrder', 'icon' => 'M21 8V21H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4'],
                        3 => ['key' => 'stepPieces', 'icon' => 'M3.34 15.55 12 21l8.66-5.45"/><path d="M3.34 9.45 12 15l8.66-5.55L12 3z"'],
                        4 => ['key' => 'stepReview', 'icon' => 'M20 6 9 17l-5-5'],
                    ];
                @endphp
                @foreach ($steps as $n => $s)
                    <li class="flex flex-1 items-center gap-2 last:flex-none">
                        <button type="button"
                                @click="if ({{ $n }} < step) step = {{ $n }}"
                                :class="step >= {{ $n }} ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-background text-muted-foreground'"
                                :aria-current="step === {{ $n }} ? 'step' : null"
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border-2 transition-all btn-press"
                                :disabled="{{ $n }} >= step">
                            @if ($n < 4)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="{{ $s['icon'] }}"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </button>
                        <span class="hidden text-xs font-semibold sm:block"
                              :class="step >= {{ $n }} ? 'text-foreground' : 'text-muted-foreground'">{{ __('orders.'.$s['key']) }}</span>
                        @if ($n < 4)
                            <span class="h-0.5 flex-1 rounded-full transition-colors duration-300"
                                  :class="step > {{ $n }} ? 'bg-primary' : 'bg-border'"></span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <form method="POST" action="{{ route('orders.store') }}" @submit="submitting = true" class="space-y-5">
            @csrf
            <input type="hidden" name="formId" value="order-wizard" />

            {{-- ===================== STEP 1 — Customer (Section A) ===================== --}}
            <section x-show="step === 1" x-transition.opacity.duration.250ms class="space-y-4">
                <div class="rounded-xl border bg-card p-4 sm:p-6 space-y-4 page-enter">
                    <h2 class="font-semibold">{{ __('orders.stepCustomer') }}</h2>

                    <div class="grid grid-cols-2 gap-2 rounded-lg bg-muted/40 p-1">
                        <button type="button" @click="customerMode = 'existing'"
                                :class="customerMode === 'existing' ? 'bg-card shadow-sm font-semibold' : 'text-muted-foreground'"
                                class="rounded-md px-3 py-2.5 text-sm transition-all btn-press">{{ __('orders.customerModeExisting') }}</button>
                        <button type="button" @click="customerMode = 'new'"
                                :class="customerMode === 'new' ? 'bg-card shadow-sm font-semibold' : 'text-muted-foreground'"
                                class="rounded-md px-3 py-2.5 text-sm transition-all btn-press">{{ __('orders.customerModeNew') }}</button>
                    </div>

                    <div x-show="customerMode === 'existing'" x-transition.opacity class="space-y-1.5">
                        <label for="wz-customer" class="text-sm font-medium">{{ __('orders.reviewCustomer') }}</label>
                        <select id="wz-customer" name="customerId" x-model="customerId"
                                :required="customerMode === 'existing'"
                                class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            <option value="">{{ __('orders.customerSelect') }}</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->phone }}</option>
                            @endforeach
                        </select>
                        @error('customerId')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-muted-foreground" x-show="customerId !== ''" x-text="customerLabel()"></p>
                    </div>

                    <div x-show="customerMode === 'new'" x-transition.opacity class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="wz-nc-name" class="text-sm font-medium">{{ __('orders.fieldCustomerName') }} *</label>
                            <input type="text" id="wz-nc-name" name="newCustomerName" x-model="newCustomer.name" maxlength="120"
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('newCustomerName')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="wz-nc-phone" class="text-sm font-medium">{{ __('orders.fieldCustomerPhone') }} *</label>
                            <input type="tel" id="wz-nc-phone" name="newCustomerPhone" x-model="newCustomer.phone" maxlength="40" placeholder="+237 6XX XX XX XX"
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('newCustomerPhone')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="wz-nc-email" class="text-sm font-medium">{{ __('orders.fieldCustomerEmail') }}</label>
                            <input type="email" id="wz-nc-email" name="newCustomerEmail" x-model="newCustomer.email"
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('newCustomerEmail')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="wz-nc-address" class="text-sm font-medium">{{ __('orders.fieldCustomerAddress') }}</label>
                            <input type="text" id="wz-nc-address" name="newCustomerAddress" x-model="newCustomer.address" maxlength="300"
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('newCustomerAddress')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== STEP 2 — Order information (Section B) ===================== --}}
            <section x-show="step === 2" x-transition.opacity.duration.250ms class="space-y-4">
                <div class="rounded-xl border bg-card p-4 sm:p-6 space-y-4 page-enter">
                    <h2 class="font-semibold">{{ __('orders.stepOrder') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="wz-number" class="text-sm font-medium">{{ __('orders.fieldOrderNumber') }}</label>
                            <input type="text" id="wz-number" name="orderNumber" x-model="order.number" maxlength="50" placeholder="{{ __('orders.numberPlaceholder') }}"
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('orderNumber')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="wz-priority" class="text-sm font-medium">{{ __('orders.fieldPriority') }}</label>
                            <select id="wz-priority" name="priority" x-model="order.priority"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority }}">{{ __('labels.priority.'.$priority) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label for="wz-date" class="text-sm font-medium">{{ __('orders.fieldOrderDate') }} *</label>
                            <input type="date" id="wz-date" name="orderDate" x-model="order.date" required
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('orderDate')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="wz-delivery" class="text-sm font-medium">{{ __('orders.fieldDelivery') }}</label>
                            <input type="date" id="wz-delivery" name="expectedDeliveryDate" x-model="order.delivery"
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('expectedDeliveryDate')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label for="wz-desc" class="text-sm font-medium">{{ __('orders.fieldDescription') }} *</label>
                            <input type="text" id="wz-desc" name="description" x-model="order.description" maxlength="500" placeholder="{{ __('orders.descPlaceholder') }}" required
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('description')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="wz-category" class="text-sm font-medium">{{ __('orders.fieldCategory') }}</label>
                            <input type="text" id="wz-category" name="garmentCategory" x-model="order.category" maxlength="80" placeholder="{{ __('orders.categoryPlaceholder') }}"
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('garmentCategory')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label for="wz-value" class="text-sm font-medium">{{ __('orders.fieldTotalValue') }}</label>
                            <input type="text" inputmode="decimal" id="wz-value" name="totalValue" x-model="order.value" placeholder="{{ __('orders.valuePlaceholder') }}"
                                   class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('totalValue')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            <p class="text-xs text-muted-foreground">{{ __('orders.valueHint') }}</p>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label for="wz-instructions" class="text-sm font-medium">{{ __('orders.fieldInstructions') }}</label>
                            <textarea id="wz-instructions" name="instructions" rows="3" x-model="order.instructions" placeholder="{{ __('orders.instructionsPlaceholder') }}"
                                      class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"></textarea>
                            @error('instructions')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label for="wz-notes" class="text-sm font-medium">{{ __('orders.fieldNotes') }}</label>
                            <textarea id="wz-notes" name="notes" rows="2" x-model="order.notes" placeholder="{{ __('orders.notesPlaceholder') }}"
                                      class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"></textarea>
                            @error('notes')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== STEP 3 — Pieces & assignment (Section C) ===================== --}}
            <section x-show="step === 3" x-transition.opacity.duration.250ms class="space-y-4">
                <div class="rounded-xl border bg-card p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="font-semibold">{{ __('orders.piecesTitle') }}</h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ __('orders.piecesHint') }}</p>
                        </div>
                        <span class="text-xs font-semibold text-muted-foreground tabular-nums"
                              x-text="`${quantityTotal()} × · ${taskCount()} {{ __('orders.detailPieces') }}`"></span>
                    </div>

                    @error('pieces')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('pieces.0.name')
                        <p class="mt-2 text-sm text-red-600">{{ __('orders.errPiecesRequired') }}</p>
                    @enderror
                </div>

                <template x-for="(piece, pidx) in pieces" :key="pidx">
                    <div data-piece-card class="rounded-xl border bg-card p-4 sm:p-5 space-y-4 page-enter">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-semibold text-sm flex items-center gap-2">
                                <span class="flex size-6 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary" x-text="pidx + 1"></span>
                                {{ __('orders.reviewPiece') }}
                            </h3>
                            <button type="button" @click="removePiece(pidx)" x-show="pieces.length > 1"
                                    :aria-label="'{{ __('orders.removePiece') }}'"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 btn-press">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                {{ __('orders.removePiece') }}
                            </button>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-12">
                            <div class="space-y-1.5 sm:col-span-5">
                                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground" x-text="`{{ __('orders.pieceName') }} *`"></label>
                                <input type="text" x-model="piece.name" :name="'pieces['+pidx+'][name]'" maxlength="120" placeholder="{{ __('orders.pieceNamePlaceholder') }}" required
                                       class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            </div>
                            <div class="space-y-1.5 sm:col-span-2">
                                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('orders.pieceQuantity') }}</label>
                                <input type="number" x-model.number="piece.quantity" :name="'pieces['+pidx+'][quantity]'" min="1" max="500"
                                       class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            </div>
                            <div class="space-y-1.5 sm:col-span-3">
                                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('orders.pieceCategory') }}</label>
                                <select x-model="piece.category" :name="'pieces['+pidx+'][category]'"
                                        class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category }}">{{ __('labels.category.'.$category) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1.5 sm:col-span-2">
                                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('orders.pieceDeadline') }}</label>
                                <input type="date" x-model="piece.deadline" :name="'pieces['+pidx+'][deadline]'"
                                       class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                <p class="text-[10px] text-muted-foreground">{{ __('orders.pieceDeadlineDefault') }}</p>
                            </div>
                            <div class="space-y-1.5 sm:col-span-8">
                                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('orders.pieceDescription') }}</label>
                                <input type="text" x-model="piece.description" :name="'pieces['+pidx+'][description]'" maxlength="500"
                                       class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            </div>
                            <div class="space-y-1.5 sm:col-span-4">
                                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('orders.piecePriority') }}</label>
                                <select x-model="piece.priority" :name="'pieces['+pidx+'][priority]'"
                                        class="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option value="">{{ __('orders.piecePriorityDefault') }}</option>
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority }}">{{ __('labels.priority.'.$priority) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1.5 sm:col-span-12">
                                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('orders.pieceInstructions') }}</label>
                                <textarea rows="2" x-model="piece.instructions" :name="'pieces['+pidx+'][instructions]'"
                                          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"></textarea>
                            </div>
                        </div>

                        {{-- Assignments --}}
                        <div class="rounded-lg bg-muted/30 p-3 space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('orders.assignmentsTitle') }}</p>
                                <button type="button" @click="addAssignment(pidx)"
                                        class="inline-flex items-center gap-1 rounded-md bg-card border px-2.5 py-1.5 text-xs font-semibold shadow-sm transition-colors hover:bg-accent btn-press">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                                    {{ __('orders.addAssignment') }}
                                </button>
                            </div>
                            <template x-for="(assignment, aidx) in piece.assignments" :key="aidx">
                                <div class="grid grid-cols-12 gap-2 items-start">
                                    <div class="col-span-12 sm:col-span-5 space-y-1">
                                        <select x-model="assignment.staffId" :name="'pieces['+pidx+'][assignments]['+aidx+'][staffId]'"
                                                class="h-11 w-full rounded-md border border-input bg-card px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                            <option value="">{{ __('orders.unassignedPiece') }}</option>
                                            <template x-for="s in staffList" :key="s.id">
                                                <option :value="s.id" x-text="s.name + (s.title ? ' — ' + s.title : '')"></option>
                                            </template>
                                        </select>
                                        <p class="text-[10px] text-muted-foreground" x-show="assignment.staffId === ''" x-text="'{{ __('orders.unassignedHint') }}'"></p>
                                    </div>
                                    <div class="col-span-10 sm:col-span-5">
                                        <input type="text" x-model="assignment.note" :name="'pieces['+pidx+'][assignments]['+aidx+'][note]'" maxlength="200" placeholder="{{ __('orders.assignmentNote') }}"
                                               class="h-11 w-full rounded-md border border-input bg-card px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                    </div>
                                    <div class="col-span-2 sm:col-span-2 flex justify-end">
                                        <button type="button" @click="removeAssignment(pidx, aidx)" x-show="piece.assignments.length > 1"
                                                :aria-label="'{{ __('orders.removeAssignment') }}'"
                                                class="inline-flex size-11 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600 btn-press">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <button type="button" @click="addPiece()"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-border bg-card/50 px-4 py-3.5 text-sm font-semibold text-muted-foreground transition-colors hover:border-primary hover:text-primary btn-press">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('orders.addPiece') }}
                </button>
            </section>

            {{-- ===================== STEP 4 — Review & confirm (Section D) ===================== --}}
            <section x-show="step === 4" x-transition.opacity.duration.250ms class="space-y-4">
                <div class="rounded-xl border bg-card p-4 sm:p-6 space-y-5 page-enter">
                    <h2 class="font-semibold">{{ __('orders.reviewTitle') }}</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg bg-muted/30 p-4 space-y-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('orders.reviewCustomer') }}</p>
                            <template x-if="customerMode === 'existing'">
                                <p class="text-sm font-semibold" x-text="customerLabel()"></p>
                            </template>
                            <template x-if="customerMode === 'new'">
                                <div class="text-sm">
                                    <p class="font-semibold" x-text="newCustomer.name"></p>
                                    <p class="text-muted-foreground" x-text="newCustomer.phone"></p>
                                </div>
                            </template>
                        </div>
                        <div class="rounded-lg bg-muted/30 p-4 space-y-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('orders.reviewOrderInfo') }}</p>
                            <p class="text-sm font-semibold" x-text="(order.number || '—') + ' · ' + order.description"></p>
                            <p class="text-xs text-muted-foreground">
                                <span x-text="order.date"></span>
                                <span x-show="order.delivery !== ''"> → <span x-text="order.delivery"></span></span>
                                · <span x-text="'{{ __('orders.fieldPriority') }}: ' + order.priority"></span>
                                <span x-show="order.value !== '' && order.value !== null"> · <span x-text="new Intl.NumberFormat().format(parseInt(order.value) || 0) + ' XAF'"></span></span>
                            </p>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('orders.reviewPieces') }}</p>
                        <div class="overflow-x-auto rounded-lg border">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        <th class="px-3 py-2">{{ __('orders.reviewPiece') }}</th>
                                        <th class="px-3 py-2">{{ __('orders.pieceQuantity') }}</th>
                                        <th class="px-3 py-2">{{ __('orders.reviewAssignee') }}</th>
                                        <th class="px-3 py-2">{{ __('orders.pieceDeadline') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <template x-for="(piece, pidx) in pieces" :key="'r'+pidx">
                                        <template x-for="(assignment, aidx) in (piece.assignments.length ? piece.assignments : [{staffId:'',note:''}])" :key="'r'+pidx+'-'+aidx">
                                            <tr class="hover:bg-muted/20">
                                                <td class="px-3 py-2 font-medium" x-text="piece.name"></td>
                                                <td class="px-3 py-2 tabular-nums" x-text="aidx === 0 ? piece.quantity : ''"></td>
                                                <td class="px-3 py-2">
                                                    <span x-text="assignment.staffId !== '' ? staffName(assignment.staffId) : '{{ __('orders.reviewNoAssignee') }}'"></span>
                                                    <span class="block text-xs text-muted-foreground" x-show="assignment.note" x-text="assignment.note"></span>
                                                </td>
                                                <td class="px-3 py-2 text-muted-foreground" x-text="piece.deadline || (order.delivery || '—')"></td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground tabular-nums"
                           x-text="'{{ __('orders.reviewTotals') }}'.replace('{pieces}', pieceCount()).replace('{tasks}', taskCount())"></p>
                    </div>

                    <div class="rounded-lg border border-primary/30 bg-primary/5 p-4">
                        <p class="text-xs text-muted-foreground">{{ __('orders.draftHint') }}</p>
                    </div>
                </div>
            </section>

            {{-- ===================== Wizard navigation ===================== --}}
            <div class="sticky bottom-16 lg:bottom-4 z-30 rounded-xl border bg-card/95 backdrop-blur p-3 shadow-lg">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" @click="back()" x-show="step > 1" x-transition.opacity
                            class="inline-flex h-11 items-center justify-center gap-1.5 rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 btn-press">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                        {{ __('orders.stepBack') }}
                    </button>
                    <div class="flex flex-col sm:flex-row gap-2 sm:ml-auto">
                        <button type="button" @click="next()" x-show="step < 4" x-transition.opacity
                                :disabled="!canContinue()"
                                class="inline-flex h-11 items-center justify-center gap-1.5 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 btn-press">
                            {{ __('orders.stepContinue') }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </button>

                        <div x-show="step === 4" x-transition.opacity class="flex flex-col sm:flex-row gap-2">
                            <button type="submit" name="draft" value="1"
                                    class="inline-flex h-11 items-center justify-center gap-1.5 rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 btn-press">
                                {{ __('orders.saveAsDraft') }}
                            </button>
                            <button type="submit" name="draft" value="0" :disabled="submitting"
                                    class="inline-flex h-11 items-center justify-center gap-1.5 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-60 btn-press">
                                <svg x-show="!submitting" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                <svg x-show="submitting" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 animate-spin" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                <span x-text="submitting ? '{{ __('orders.wizardSubmitting') }}' : '{{ __('orders.saveAndAssign') }}'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
