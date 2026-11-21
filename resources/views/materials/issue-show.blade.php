@extends('layouts.app')

@section('title', $issue->code)

@section('content')
    @php
        /**
         * OSSZ ATELIER — material issue detail (GET /materials/issues/{id};
         * Finance & Comptabilité spec v1.3 §5.5-§5.10).
         *
         * Variables (MaterialController@showIssue): $issue (with order,
         * garment, task, staff, lines.material, lines.declarations,
         * lines.losses), $canDeclare (beneficiary or issue holder),
         * $canValidate (manager).
         *
         * While the issue is OPEN, the unexplained balance reads
         * "à rapprocher" — never "perte définitive"; the loss is only
         * computed at closure (§5.10). Declaration sums are read from
         * the eager-loaded collection to avoid extra queries.
         */

        // Issue status badge palette (OPEN amber / CLOSED zinc /
        // CANCELLED red).
        $statusPalette = [
            'OPEN' => 'bg-amber-50 text-amber-700 border-amber-200',
            'CLOSED' => 'bg-zinc-50 text-zinc-600 border-zinc-200',
            'CANCELLED' => 'bg-red-50 text-red-700 border-red-200',
        ];

        // Declaration status badge palette.
        $declarationPalette = [
            'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200',
            'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'REJECTED' => 'bg-red-50 text-red-700 border-red-200',
        ];

        $isOpen = $issue->status === 'OPEN';
    @endphp

    <div class="space-y-5">

        {{-- Page header --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-bold tracking-tight sm:text-2xl">{{ $issue->code }}</h1>
                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusPalette[$issue->status] ?? $statusPalette['OPEN'] }}">{{ __('mat.st_'.$issue->status) }}</span>
                </div>
                <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm text-muted-foreground">
                    @if ($issue->order)
                        <a href="{{ route('orders.show', $issue->order_id) }}" class="font-medium text-primary hover:underline">{{ $issue->order->order_number }}</a>
                        <span aria-hidden="true">·</span>
                    @endif
                    @if ($issue->garment?->code)
                        <span>{{ $issue->garment->code }}</span>
                        <span aria-hidden="true">·</span>
                    @endif
                    <span>{{ $issue->staff_name }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $issue->issue_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</span>
                </p>
            </div>
            <a href="{{ route('materials.issues.index') }}"
               class="inline-flex items-center gap-2 rounded-md border border-input bg-card px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors hover:bg-muted/60 btn-press">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                {{ __('common.back') }}
            </a>
        </div>

        {{-- Info box: à rapprocher vs perte (OPEN only, §5.10) --}}
        @if ($isOpen)
            <div class="flex gap-2 rounded-lg border border-amber-300 bg-amber-50/70 p-3 text-amber-900">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 size-4 shrink-0" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <div>
                    <p class="text-sm font-medium">{{ __('mat.toReconcile') }}</p>
                    <p class="mt-0.5 text-xs text-amber-800/90">{{ __('mat.declareHelp') }}</p>
                </div>
            </div>
        @endif

        {{-- Lines --}}
        <section class="rounded-lg border bg-card">
            <div class="border-b px-5 py-4">
                <h2 class="text-base font-semibold">{{ __('mat.lines') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('mat.materialName') }}</th>
                            <th class="px-4 py-3">{{ __('mat.unit') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.qtyIssued') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.qtyConsumed') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('mat.qtyReturned') }}</th>
                            @if ($isOpen)
                                <th class="px-4 py-3 text-right">{{ __('mat.toReconcile') }}</th>
                            @else
                                <th class="px-4 py-3 text-right">{{ __('mat.loss') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.lossValue') }}</th>
                            @endif
                            <th class="px-4 py-3 text-right">{{ __('mat.avgCost') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('sup.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($issue->lines as $line)
                            @php
                                $approvedConsumed = (float) $line->declarations->where('status', 'APPROVED')->sum('qty_consumed');
                                $approvedReturned = (float) $line->declarations->where('status', 'APPROVED')->sum('qty_returned');
                                $declaredTotal = (float) $line->declarations->sum(static fn ($d): float => (float) $d->qty_consumed + (float) $d->qty_returned);
                                $toReconcile = (float) $line->qty_issued - $declaredTotal;
                                $clientSupplied = (bool) $line->material?->is_client_supplied;
                                $loss = $line->losses->first();
                            @endphp
                            <tr class="border-t transition-colors hover:bg-muted/30">
                                <td class="max-w-56 px-4 py-3">
                                    <p class="truncate font-medium">{{ $line->material_name }}</p>
                                    @if ($clientSupplied)
                                        <span class="mt-0.5 inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[10px] font-semibold text-zinc-600">{{ __('mat.clientSupplied') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $line->unit }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $line->qty_issued + 0 }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $approvedConsumed + 0 }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $approvedReturned + 0 }}</td>
                                @if ($isOpen)
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $toReconcile > 0 ? 'text-amber-700' : '' }}">{{ $toReconcile + 0 }}</td>
                                @else
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ ($loss?->qty_lost ?? 0) > 0 ? 'text-red-700' : '' }}">{{ $loss ? ($loss->qty_lost + 0) : '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums {{ ($loss?->value ?? 0) > 0 ? 'text-red-700' : '' }}">{{ $loss ? \App\Support\Money::format($loss->value) : '—' }}</td>
                                @endif
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $clientSupplied ? '—' : \App\Support\Money::format($line->unit_cost) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ $clientSupplied ? '—' : \App\Support\Money::format((float) $line->qty_issued * (float) $line->unit_cost) }}</td>
                            </tr>
                            @if (! $isOpen && $loss?->justification)
                                <tr class="border-t bg-muted/20">
                                    <td colspan="9" class="px-4 py-2 text-xs text-muted-foreground">
                                        {{ __('mat.justification') }} — {{ $line->material_name }}: {{ $loss->justification }}
                                    </td>
                                </tr>
                            @endif
                            @if ($canDeclare && $isOpen)
                                <tr class="border-t bg-muted/20">
                                    <td colspan="9" class="px-4 py-3">
                                        <form method="POST" action="{{ route('materials.declare', $line->id) }}" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            <div>
                                                <label for="declare-consumed-{{ $line->id }}" class="block text-xs font-medium text-muted-foreground">{{ __('mat.declareConsumed') }}</label>
                                                <input type="number" step="any" min="0" inputmode="decimal" id="declare-consumed-{{ $line->id }}" name="qty_consumed" value="{{ old('qty_consumed') }}"
                                                       class="mt-1 w-28 rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                @error('qty_consumed')
                                                    <span class="mt-1 block text-xs text-red-600" role="alert">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="declare-returned-{{ $line->id }}" class="block text-xs font-medium text-muted-foreground">{{ __('mat.declareReturned') }}</label>
                                                <input type="number" step="any" min="0" inputmode="decimal" id="declare-returned-{{ $line->id }}" name="qty_returned" value="{{ old('qty_returned') }}"
                                                       class="mt-1 w-28 rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                @error('qty_returned')
                                                    <span class="mt-1 block text-xs text-red-600" role="alert">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="min-w-40 flex-1">
                                                <label for="declare-note-{{ $line->id }}" class="block text-xs font-medium text-muted-foreground">{{ __('pay.fieldNotes') }}</label>
                                                <input type="text" id="declare-note-{{ $line->id }}" name="note" value="{{ old('note') }}" maxlength="500"
                                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                            </div>
                                            <button type="submit"
                                                    class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                                {{ __('mat.declareTitle') }}
                                            </button>
                                        </form>
                                        <p class="mt-1.5 text-xs text-muted-foreground">{{ __('mat.declareHelp') }}</p>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Manager closure (§5.10) + cancellation --}}
        @if ($canValidate && $isOpen)
            <section class="rounded-lg border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('mat.closeIssue') }}</h2>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <form method="POST" action="{{ route('materials.issues.close', $issue->id) }}" class="space-y-3">
                        @csrf
                        @foreach ($issue->lines as $line)
                            <div>
                                <label for="justification-{{ $line->id }}" class="block text-xs font-medium text-muted-foreground">
                                    {{ $line->material_name }} — {{ __('mat.justification') }}
                                </label>
                                <input type="text" id="justification-{{ $line->id }}" name="justifications[{{ $line->id }}]" value="{{ old('justifications.'.$line->id) }}" maxlength="500"
                                       class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                            </div>
                        @endforeach
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                            {{ __('mat.closeIssue') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('materials.issues.cancel', $issue->id) }}"
                          onsubmit="return confirm('{{ __('mat.cancelIssue') }} — {{ $issue->code }} ?');">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-destructive px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-destructive/90 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            {{ __('mat.cancelIssue') }}
                        </button>
                    </form>
                </div>
            </section>
        @endif

        {{-- Declarations history (§5.7/§5.8) --}}
        <section class="rounded-lg border bg-card">
            <div class="border-b px-5 py-4">
                <h2 class="text-base font-semibold">{{ __('mat.declarations') }}</h2>
            </div>
            @php
                $declarationCount = $issue->lines->sum(static fn ($line): int => $line->declarations->count());
            @endphp
            @if ($declarationCount === 0)
                <div class="px-5 py-8 text-center text-sm text-muted-foreground">—</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px] text-sm">
                        <thead class="bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">{{ __('mat.issueDate') }}</th>
                                <th class="px-4 py-3">{{ __('mat.materialName') }}</th>
                                <th class="px-4 py-3">{{ __('pay.colRecordedBy') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.qtyConsumed') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('mat.qtyReturned') }}</th>
                                <th class="px-4 py-3">{{ __('pay.fieldNotes') }}</th>
                                <th class="px-4 py-3">{{ __('mat.status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($issue->lines as $line)
                                @foreach ($line->declarations as $declaration)
                                    <tr class="border-t transition-colors hover:bg-muted/30">
                                        <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $declaration->declared_at->locale(app()->getLocale())->translatedFormat('j M Y') }}</td>
                                        <td class="max-w-44 px-4 py-3"><span class="block truncate">{{ $line->material_name }}</span></td>
                                        <td class="max-w-36 px-4 py-3"><span class="block truncate">{{ $declaration->declared_by_name ?? $declaration->declaredBy?->name ?? '—' }}</span></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $declaration->qty_consumed + 0 }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $declaration->qty_returned + 0 }}</td>
                                        <td class="max-w-40 px-4 py-3">
                                            @if ($declaration->note)
                                                <span class="block truncate text-muted-foreground" title="{{ $declaration->note }}">{{ $declaration->note }}</span>
                                            @else
                                                <span class="text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $declarationPalette[$declaration->status] ?? $declarationPalette['PENDING'] }}">{{ __('mat.ds_'.$declaration->status) }}</span>
                                            @if ($declaration->reviewed_by_name)
                                                <p class="mt-1 text-xs text-muted-foreground">{{ $declaration->reviewed_by_name }}</p>
                                                @if ($declaration->review_note)
                                                    <p class="max-w-44 truncate text-xs text-muted-foreground" title="{{ $declaration->review_note }}">{{ $declaration->review_note }}</p>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($canValidate && $declaration->status === 'PENDING')
                                                <form method="POST" action="{{ route('materials.declarations.review', $declaration->id) }}"
                                                      class="flex flex-wrap items-center justify-end gap-1.5">
                                                    @csrf
                                                    <input type="text" name="review_note" placeholder="{{ __('mat.reviewNote') }}" maxlength="500"
                                                           class="w-36 rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                                                    <button type="submit" name="decision" value="APPROVED"
                                                            class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground transition-colors hover:bg-primary/90 btn-press">
                                                        {{ __('mat.approve') }}
                                                    </button>
                                                    <button type="submit" name="decision" value="REJECTED"
                                                            class="inline-flex items-center rounded-md bg-destructive px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-destructive/90 btn-press">
                                                        {{ __('mat.reject') }}
                                                    </button>
                                                </form>
                                            @else
                                                <p class="text-right text-muted-foreground">—</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
