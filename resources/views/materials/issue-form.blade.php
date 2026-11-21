@extends('layouts.app')

@section('title', __('mat.newIssue'))

@section('content')
    @php
        /**
         * OSSZ ATELIER — new material issue / remise form
         * (GET /materials/issues/create, perm materials.issue; Finance &
         * Comptabilité spec v1.3 §5.5-§5.6 workflow A).
         *
         * Variables (MaterialController@createIssue): $orders (REGISTERED
         * orders with garments + tasks), $selectedOrder (optional
         * preselect via ?order=), $materials (active materials with
         * stock_qty / avg_cost), $staff (assignable employees).
         *
         * Garment and task selects are grouped by order with optgroups
         * (code + garment_piece + category on tasks); the lines repeater
         * is Alpine-driven and posts lines[i][material_id] / lines[i][qty].
         */

        $selectedOrderId = old('order_id', $selectedOrder?->id ?? request()->query('order', ''));
    @endphp

    <div class="space-y-5">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('mat.newIssue') }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ __('mat.issuesSubtitle') }}</p>
            </div>
            <a href="{{ route('materials.issues.index') }}"
               class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                {{ __('common.back') }}
            </a>
        </div>

        <form method="POST" action="{{ route('materials.issues.store') }}"
              x-data="{ lines: [{ materialId: @js(old('lines.0.material_id', '')), qty: @js(old('lines.0.qty', '')) }] }">
            @csrf
            <div class="space-y-5">

                {{-- Traceability: order / garment / operation / employee --}}
                <section class="rounded-lg border bg-card">
                    <div class="border-b px-5 py-4">
                        <h2 class="text-base font-semibold">{{ __('mat.issuesTitle') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('mat.issuesSubtitle') }}</p>
                    </div>
                    <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="issue-order" class="mb-1.5 block text-sm font-medium">{{ __('mat.orderColumn') }} <span class="text-red-600" aria-hidden="true">*</span></label>
                            <select id="issue-order" name="order_id" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">{{ __('pay.selectOrder') }}</option>
                                @foreach ($orders as $order)
                                    <option value="{{ $order->id }}" {{ (string) $selectedOrderId === $order->id ? 'selected' : '' }}>{{ $order->order_number }}</option>
                                @endforeach
                            </select>
                            @error('order_id')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="issue-garment" class="mb-1.5 block text-sm font-medium">{{ __('mat.garment') }}</label>
                            <select id="issue-garment" name="garment_id"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">—</option>
                                @foreach ($orders as $order)
                                    @if ($order->garments->isNotEmpty())
                                        <optgroup label="{{ $order->order_number }}">
                                            @foreach ($order->garments as $garment)
                                                <option value="{{ $garment->id }}" {{ old('garment_id') === $garment->id ? 'selected' : '' }}>{{ $garment->code }} — {{ $garment->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            @error('garment_id')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="issue-task" class="mb-1.5 block text-sm font-medium">{{ __('mat.operation') }}</label>
                            <select id="issue-task" name="task_id"
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">—</option>
                                @foreach ($orders as $order)
                                    @if ($order->tasks->isNotEmpty())
                                        <optgroup label="{{ $order->order_number }}">
                                            @foreach ($order->tasks as $task)
                                                <option value="{{ $task->id }}" {{ old('task_id') === $task->id ? 'selected' : '' }}>{{ $task->code }} — {{ $task->garment_piece }} ({{ __('labels.category.'.$task->category) }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            @error('task_id')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="issue-staff" class="mb-1.5 block text-sm font-medium">{{ __('mat.beneficiary') }} <span class="text-red-600" aria-hidden="true">*</span></label>
                            <select id="issue-staff" name="staff_id" required
                                    class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">—</option>
                                @foreach ($staff as $staffMember)
                                    <option value="{{ $staffMember->id }}" {{ old('staff_id') === $staffMember->id ? 'selected' : '' }}>{{ $staffMember->name }} — {{ __('labels.role.'.$staffMember->role) }}</option>
                                @endforeach
                            </select>
                            @error('staff_id')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="issue-date" class="mb-1.5 block text-sm font-medium">{{ __('mat.issueDate') }} <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input type="date" id="issue-date" name="date" value="{{ old('date', now()->toDateString()) }}" required
                                   class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            @error('date')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="issue-note" class="mb-1.5 block text-sm font-medium">{{ __('pay.fieldNotes') }}</label>
                            <textarea id="issue-note" name="note" rows="2"
                                      class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring">{{ old('note') }}</textarea>
                            @error('note')
                                <span class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- Materials and quantities (at least one line) --}}
                <section class="rounded-lg border bg-card">
                    <div class="border-b px-5 py-4">
                        <h2 class="text-base font-semibold">{{ __('mat.lines') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('mat.declareHelp') }}</p>
                    </div>
                    <div class="space-y-3 px-5 py-4">
                        @error('lines')
                            <span class="block text-xs text-red-600" role="alert">{{ $message }}</span>
                        @enderror
                        @error('lines.0.material_id')
                            <span class="block text-xs text-red-600" role="alert">{{ $message }}</span>
                        @enderror
                        @error('lines.0.qty')
                            <span class="block text-xs text-red-600" role="alert">{{ $message }}</span>
                        @enderror

                        <div class="hidden grid-cols-12 gap-2 sm:grid">
                            <p class="col-span-7 text-xs font-medium text-muted-foreground">{{ __('mat.materialName') }}</p>
                            <p class="col-span-3 text-xs font-medium text-muted-foreground">{{ __('mat.transferQty') }}</p>
                            <p class="col-span-2"></p>
                        </div>
                        <template x-for="(line, idx) in lines" :key="idx">
                            <div class="grid grid-cols-12 items-start gap-2">
                                <select x-model="line.materialId" :name="'lines['+idx+'][material_id]'" required
                                        :aria-label="'{{ __('mat.materialName') }} ' + (idx + 1)"
                                        class="col-span-12 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-7">
                                    <option value="">{{ __('mat.materialName') }}…</option>
                                    @foreach ($materials as $material)
                                        <option value="{{ $material->id }}">{{ $material->name }} — {{ $material->stock_qty + 0 }} {{ $material->unit }}@if (! $material->is_client_supplied) · {{ \App\Support\Money::format($material->avg_cost) }}@endif</option>
                                    @endforeach
                                </select>
                                <input type="number" x-model.number="line.qty" :name="'lines['+idx+'][qty]'" step="any" min="0.001" inputmode="decimal" required
                                       :aria-label="'{{ __('mat.transferQty') }} ' + (idx + 1)"
                                       class="col-span-10 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:col-span-3" />
                                <button type="button" @click="lines.length > 1 && lines.splice(idx, 1)"
                                        aria-label="{{ __('common.delete') }}"
                                        class="col-span-2 inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:col-span-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="lines.push({ materialId: '', qty: '' })"
                                class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-3 py-2 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            {{ __('mat.addLine') }}
                        </button>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <a href="{{ route('materials.issues.index') }}"
                           class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60">
                            {{ __('common.cancel') }}
                        </a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('common.create') }}
                        </button>
                    </div>
                </section>
            </div>
        </form>
    </div>
@endsection
