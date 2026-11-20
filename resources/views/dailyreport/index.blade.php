@extends('layouts.app')

@section('title', __('shell.view.dailyReport'))

@section('content')
                <div class="space-y-4" x-data="{ addOpen: {{ $errors->any() && old('formId') === 'recipient-create' ? 'true' : 'false' }} , sendOpen: false }">

    {{-- ============ Page header ============ --}}
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('dailyreport.title') }}</h1>
            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('dailyreport.description') }}</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <form method="POST" action="{{ route('dailyreport.sendTest') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                <button type="submit"
                        class="inline-flex h-9 w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><rect width="20" height="16" x="2" y="4" rx="2"/></svg>
                    {{ __('dailyreport.sendTest') }}
                </button>
            </form>
            <button type="button" @click="sendOpen = true"
                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/></svg>
                {{ __('dailyreport.sendNow') }}
            </button>
        </div>
    </div>

    {{-- ============ Cron requirement note ============ --}}
    <div class="flex gap-2 rounded-lg border border-amber-300 bg-amber-50/70 p-3 text-amber-900">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 size-4 shrink-0" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <div>
            <p class="text-sm font-medium">{{ __('dailyreport.cronNoteTitle') }}</p>
            <p class="mt-0.5 text-xs text-amber-800/90">{{ __('dailyreport.cronNote') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- ============ Left column: preview + recipients + history ============ --}}
        <div class="space-y-4 lg:col-span-2">

            {{-- ---- Report preview ---- --}}
            <section class="rounded-lg border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('dailyreport.previewTitle') }}</h2>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ __('dailyreport.previewDescription') }}</p>
                </div>
                @if ($preview === null)
                    <div class="px-5 py-6 text-sm text-muted-foreground">{{ __('dailyreport.previewUnavailable') }}</div>
                @else
                    @php
                        $money = static fn ($v): string => number_format((float) ($v ?? 0), 0).' XAF';
                    @endphp
                    <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-4">
                        <div class="rounded-lg border bg-muted/30 p-3">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.kpiNewOrders') }}</p>
                            <p class="mt-1 text-xl font-bold tabular-nums">{{ (int) $preview['today']['ordersRegistered'] }}</p>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-3">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.kpiPiecesCompleted') }}</p>
                            <p class="mt-1 text-xl font-bold tabular-nums">{{ (int) $preview['today']['piecesCompleted'] }}</p>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-3">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.kpiPaymentsIn') }}</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-emerald-700">{{ $money($preview['today']['paymentsIn']) }}</p>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-3">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.kpiExpensesOut') }}</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-red-700">{{ $money($preview['today']['expensesOut']) }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 border-t px-5 py-4 text-sm sm:grid-cols-4">
                        @if (is_array($preview['production']))
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.prodOrdersInProduction') }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums">{{ (int) $preview['production']['ordersInProduction'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.prodActiveTasks') }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums">{{ (int) $preview['production']['activeTasks'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.prodOverdue') }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums {{ $preview['production']['overdue'] > 0 ? 'text-red-700' : '' }}">{{ (int) $preview['production']['overdue'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.prodOnTime') }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums">{{ $preview['production']['onTimeRate30d'] === null ? '—' : (int) $preview['production']['onTimeRate30d'].'%' }}</p>
                            </div>
                        @endif
                        @if (is_array($preview['financial']))
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.finPaymentsMtd') }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums">{{ $money($preview['financial']['paymentsMtd']) }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.finOutstanding') }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums text-amber-700">{{ $money($preview['financial']['outstandingBalances']) }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.finExpensesMtd') }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums">{{ $money($preview['financial']['expensesMtd']) }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.finNetCash') }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums {{ $preview['financial']['cashMovementMtd'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $money($preview['financial']['cashMovementMtd']) }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </section>

            {{-- ---- Recipients ---- --}}
            <section class="rounded-lg border bg-card">
                <div class="flex flex-col justify-between gap-3 border-b px-5 py-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-base font-semibold">{{ __('dailyreport.recipientsTitle') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('dailyreport.recipientsDescription') }}</p>
                    </div>
                    <button type="button" @click="addOpen = true"
                            class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        {{ __('dailyreport.addRecipient') }}
                    </button>
                </div>

                @if (count($recipients) === 0)
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-10 text-center">
                        <p class="font-medium">{{ __('dailyreport.emptyTitle') }}</p>
                        <p class="text-sm text-muted-foreground max-w-sm">{{ __('dailyreport.emptyDescription') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr class="border-b bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    <th class="px-5 py-3">{{ __('dailyreport.colRecipient') }}</th>
                                    <th class="w-24 px-4 py-3 text-center">{{ __('dailyreport.colStatus') }}</th>
                                    <th class="px-4 py-3">{{ __('dailyreport.colAddedBy') }}</th>
                                    <th class="w-20 px-5 py-3 text-right">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($recipients as $recipient)
                                    <tr class="{{ $recipient->is_active ? '' : 'opacity-60' }} transition-colors hover:bg-muted/30">
                                        <td class="px-5 py-3">
                                            <p class="font-medium break-all">{{ $recipient->email }}</p>
                                            @if ($recipient->name)
                                                <p class="text-xs text-muted-foreground">{{ $recipient->name }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <form method="POST" action="{{ route('dailyreport.recipients.update', $recipient->id) }}" class="inline">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                                                <button type="submit" role="switch" aria-checked="{{ $recipient->is_active ? 'true' : 'false' }}"
                                                        aria-label="{{ \App\Support\Trans::fill('dailyreport.toggleAria', ['email' => $recipient->email]) }}"
                                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $recipient->is_active ? 'bg-emerald-600' : 'bg-muted' }}">
                                                    <span class="inline-block size-4 transform rounded-full bg-white shadow transition-transform {{ $recipient->is_active ? 'translate-x-[18px]' : 'translate-x-[2px]' }}" aria-hidden="true"></span>
                                                </button>
                                            </form>
                                            <span class="mt-1 block text-[11px] {{ $recipient->is_active ? 'text-emerald-700' : 'text-muted-foreground' }}">
                                                {{ $recipient->is_active ? __('dailyreport.active') : __('dailyreport.inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-muted-foreground">{{ $recipient->added_by_name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-right">
                                            <form method="POST" action="{{ route('dailyreport.recipients.destroy', $recipient->id) }}"
                                                  onsubmit="return confirm('{{ \App\Support\Trans::fill('dailyreport.removeConfirm', ['email' => e($recipient->email)]) }}');">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                                                <button type="submit"
                                                        aria-label="{{ \App\Support\Trans::fill('dailyreport.removeAria', ['email' => $recipient->email]) }}"
                                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- ---- Send history ---- --}}
            <section class="rounded-lg border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('dailyreport.historyTitle') }}</h2>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ __('dailyreport.historyDescription') }}</p>
                </div>
                @if (count($logs) === 0)
                    <div class="px-5 py-6 text-sm text-muted-foreground">{{ __('dailyreport.historyEmpty') }}</div>
                @else
                    <div class="max-h-96 overflow-y-auto">
                        <table class="w-full min-w-[520px] text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr class="border-b bg-muted/60 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground backdrop-blur">
                                    <th class="px-5 py-3">{{ __('dailyreport.colDate') }}</th>
                                    <th class="px-4 py-3">{{ __('dailyreport.colTrigger') }}</th>
                                    <th class="px-4 py-3">{{ __('dailyreport.colStatus') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('dailyreport.colResult') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($logs as $log)
                                    <tr class="transition-colors hover:bg-muted/30">
                                        <td class="px-5 py-3 whitespace-nowrap text-muted-foreground">{{ $log->sent_at?->format('d M Y H:i') }}</td>
                                        <td class="px-4 py-3">
                                            @if ($log->trigger_type === 'SCHEDULED')
                                                <span class="inline-flex items-center rounded-full border border-stone-300 bg-stone-50 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-stone-700">{{ __('dailyreport.triggerScheduled') }}</span>
                                            @elseif ($log->trigger_type === 'MANUAL')
                                                <span class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-amber-900">{{ __('dailyreport.triggerManual') }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-emerald-800">{{ __('dailyreport.triggerTest') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($log->status === 'SENT')
                                                <span class="font-medium text-emerald-700">{{ __('dailyreport.statusSent') }}</span>
                                            @elseif ($log->status === 'PARTIAL')
                                                <span class="font-medium text-amber-700">{{ __('dailyreport.statusPartial') }}</span>
                                            @else
                                                <span class="font-medium text-red-700">{{ __('dailyreport.statusFailed') }}</span>
                                            @endif
                                            @if ($log->status !== 'SENT' && $log->error)
                                                <p class="mt-0.5 max-w-xs truncate text-xs text-muted-foreground" title="{{ $log->error }}">{{ \Illuminate\Support\Str::limit($log->error, 60) }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap tabular-nums text-muted-foreground">
                                            {{ (int) $log->success_count }}/{{ (int) $log->recipient_count }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        {{-- ============ Right column: schedule & content settings ============ --}}
        <div class="space-y-4">
            <section class="rounded-lg border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('dailyreport.settingsTitle') }}</h2>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ __('dailyreport.settingsDescription') }}</p>
                </div>
                <form method="POST" action="{{ route('dailyreport.settings') }}" class="space-y-5 px-5 py-4">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                    <input type="hidden" name="formId" value="report-settings">

                    {{-- Enabled switch --}}
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <label class="text-sm font-medium">{{ __('dailyreport.enabled') }}</label>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ __('dailyreport.enabledHint') }}</p>
                        </div>
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" {{ old('enabled', $settings->enabled) ? 'checked' : '' }}
                               class="mt-1 size-4 shrink-0 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    </div>

                    {{-- Send time + timezone --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="report-send-time" class="mb-1.5 block text-sm font-medium">{{ __('dailyreport.sendTime') }}</label>
                            <input type="time" id="report-send-time" name="sendTime" value="{{ old('sendTime', $settings->sendTime()) }}" required
                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                            @error('sendTime')
                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="report-timezone" class="mb-1.5 block text-sm font-medium">{{ __('dailyreport.timezone') }}</label>
                            <select id="report-timezone" name="reportTimezone"
                                    class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                @foreach ($timezones as $zone)
                                    <option value="{{ $zone }}" {{ old('reportTimezone', $settings->report_timezone) === $zone ? 'selected' : '' }}>{{ $zone }}</option>
                                @endforeach
                            </select>
                            @error('reportTimezone')
                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Reply-to --}}
                    <div>
                        <label for="report-reply-to" class="mb-1.5 block text-sm font-medium">{{ __('dailyreport.replyTo') }} <span class="font-normal text-muted-foreground">({{ __('common.optional') }})</span></label>
                        <input type="email" id="report-reply-to" name="replyTo" value="{{ old('replyTo', $settings->reply_to) }}" placeholder="info@osszcollection.com" maxlength="190"
                               class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                        @error('replyTo')
                            <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Sections --}}
                    <div>
                        <span class="mb-2 block text-sm font-medium">{{ __('dailyreport.sections') }}</span>
                        <div class="space-y-2 rounded-lg border p-3">
                            @php
                                $sectionFlags = [
                                    'includeProduction' => ['dailyreport.sectionProduction', $settings->include_production],
                                    'includeFinancial' => ['dailyreport.sectionFinancial', $settings->include_financial],
                                    'includeStaff' => ['dailyreport.sectionStaff', $settings->include_staff],
                                    'includeAlerts' => ['dailyreport.sectionAlerts', $settings->include_alerts],
                                ];
                            @endphp
                            @foreach ($sectionFlags as $field => [$label, $value])
                                <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm sm:min-h-0">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $value) ? 'checked' : '' }}
                                           class="size-4 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                    {{ __($label) }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end border-t pt-4">
                        <button type="submit"
                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            {{ __('common.saveChanges') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- ---- Last sent status ---- --}}
            <section class="rounded-lg border bg-card px-5 py-4">
                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ __('dailyreport.lastSent') }}</p>
                <p class="mt-1 text-sm font-semibold">{{ $settings->last_sent_at?->format('d M Y H:i') ?? __('dailyreport.never') }}</p>
                @if ($settings->last_sent_at)
                    <p class="mt-0.5 text-xs text-muted-foreground">({{ $settings->last_sent_at->diffForHumans() }})</p>
                @endif
            </section>
        </div>
    </div>

    {{-- ============ Add-recipient modal ============ --}}
    <div x-show="addOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
         aria-label="{{ __('dailyreport.addRecipient') }}"
         @keydown.escape.window="addOpen = false">
        <div class="fixed inset-0 bg-black/50" @click="addOpen = false" aria-hidden="true"></div>
        <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
            <div class="relative w-full max-w-lg rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold">{{ __('dailyreport.addRecipient') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('dailyreport.addDescription') }}</p>
                    </div>
                    <button type="button" @click="addOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('dailyreport.recipients.store') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                    <input type="hidden" name="formId" value="recipient-create" />
                    <div class="max-h-[calc(85vh-11rem)] overflow-y-auto px-5 py-4">
                        <div class="space-y-4">
                            <div>
                                <label for="recipient-email" class="mb-1.5 block text-sm font-medium">{{ __('dailyreport.email') }}</label>
                                <input type="email" id="recipient-email" name="email" value="{{ old('email') }}" maxlength="190" placeholder="name@example.com" required autofocus
                                       class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('email')
                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="recipient-name" class="mb-1.5 block text-sm font-medium">{{ __('dailyreport.nameLabel') }} <span class="font-normal text-muted-foreground">({{ __('common.optional') }})</span></label>
                                <input type="text" id="recipient-name" name="name" value="{{ old('name') }}" maxlength="120"
                                       class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('name')
                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="addOpen = false"
                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            {{ __('dailyreport.addRecipient') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============ Send-now confirm modal ============ --}}
    <div x-show="sendOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
         aria-label="{{ __('dailyreport.sendConfirmTitle') }}"
         @keydown.escape.window="sendOpen = false">
        <div class="fixed inset-0 bg-black/50" @click="sendOpen = false" aria-hidden="true"></div>
        <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
            <div class="relative w-full max-w-md rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                    <h2 class="text-base font-semibold">{{ __('dailyreport.sendConfirmTitle') }}</h2>
                    <button type="button" @click="sendOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('dailyreport.send') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">
                    <div class="px-5 py-4">
                        <p class="text-sm text-muted-foreground">
                            {{ \App\Support\Trans::fill('dailyreport.sendConfirm', ['count' => $recipients->where('is_active', true)->count()]) }}
                        </p>
                    </div>
                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                        <button type="button" @click="sendOpen = false"
                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            {{ __('dailyreport.confirmSend') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
            @endsection
