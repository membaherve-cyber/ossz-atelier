@extends('layouts.app')

@section('title', __('shell.view.stages'))

@section('content')
                <div class="space-y-4" x-data="{ addOpen: {{ $errors->any() && old('formId') === 'stage-create' ? 'true' : 'false' }} }">


    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('stages.title') }}</h1>
            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('stages.description') }}</p>
        </div>
        <button type="button" @click="addOpen = true"
                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('stages.addStage') }}
        </button>
    </div>


    <div class="flex gap-2 rounded-lg border border-amber-300 bg-amber-50/70 p-3 text-amber-900">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 size-4 shrink-0" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        <div>
            <p class="text-sm font-medium">{{ __('stages.historyTitle') }}</p>
            <p class="mt-0.5 text-xs text-amber-800/90">{{ __('stages.historyDescription') }}</p>
        </div>
    </div>


    @if (count($stages) === 0)
            <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                <div class="rounded-full bg-muted p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>
                </div>
                <p class="font-medium">{{ __('stages.emptyTitle') }}</p>
                <p class="text-sm text-muted-foreground max-w-sm">{{ __('stages.emptyDescription') }}</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="border-b bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <th class="w-20 px-4 py-3">{{ __('stages.col.sort') }}</th>
                        <th class="px-4 py-3">{{ __('stages.col.name') }}</th>
                        <th class="px-4 py-3">{{ __('stages.col.code') }}</th>
                        <th class="px-4 py-3">{{ __('stages.col.appliesTo') }}</th>
                        <th class="w-20 px-4 py-3 text-right">{{ __('stages.col.weight') }}</th>
                        <th class="w-24 px-4 py-3 text-center">{{ __('stages.col.active') }}</th>
                        <th class="w-20 px-4 py-3 text-right">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($stages as $stage)
                        @php
                            $stageCategories = $stage->applies_to === \App\Support\Atelier::APPLIES_TO_ALL
                                ? \App\Support\Atelier::TASK_CATEGORIES
                                : explode(',', $stage->applies_to);
                        @endphp
                                                                    <tr class="{{ $stage->is_active ? '' : 'opacity-60' }} transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 tabular-nums text-muted-foreground">{{ $stage->sort_order }}</td>
                            <td class="px-4 py-3 font-medium">{{ $stage->name }}</td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">{{ $stage->code }}</code>
                            </td>
                            <td class="px-4 py-3">
                                @if ($stage->applies_to === \App\Support\Atelier::APPLIES_TO_ALL)
                                                                    <span class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-amber-900">
                                        {{ __('stages.allCategories') }}
                                    </span>
                                                        @else
                                                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($stageCategories as $category)
                                                                                    <x-badges.category :category="$category" />
                                        @endforeach
                                                                            </div>
                                                        @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">×{{ $stage->weight }}</td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('stages.update', $stage->id) }}" class="inline">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                                    <input type="hidden" name="isActive" value="{{ $stage->is_active ? '0' : '1' }}" />
                                    <button type="submit" role="switch" aria-checked="{{ $stage->is_active ? 'true' : 'false' }}"
                                            aria-label="{{ \App\Support\Trans::fill($stage->is_active ? 'stages.deactivateStageAria' : 'stages.activateStageAria', ['name' => $stage->name]) }}"
                                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $stage->is_active ? 'bg-emerald-600' : 'bg-muted' }}">
                                        <span class="inline-block size-4 transform rounded-full bg-white shadow transition-transform {{ $stage->is_active ? 'translate-x-[18px]' : 'translate-x-[2px]' }}" aria-hidden="true"></span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div x-data="{ open: {{ $errors->any() && old('formId') === 'stage-edit-'.$stage->id ? 'true' : 'false' }} }">
                                    <button type="button" @click="open = true"
                                            aria-label="{{ \App\Support\Trans::fill('stages.editStageAria', ['name' => $stage->name]) }}"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    <div x-show="open" style="display: none;"
                                         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
                                         aria-label="{{ __('stages.editTitle') }}"
                                         @keydown.escape.window="open = false">
                                        <div class="fixed inset-0 bg-black/50" @click="open = false" aria-hidden="true"></div>
                                        <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
                                            <div class="relative w-full max-w-lg rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                                                <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                                                    <div class="min-w-0">
                                                        <h2 class="text-base font-semibold">{{ __('stages.editTitle') }}</h2>
                                                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('stages.editDescription') }}</p>
                                                    </div>
                                                    <button type="button" @click="open = false"
                                                            aria-label="{{ __('common.close') }}"
                                                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                    </button>
                                                </div>
                                                                                                <form method="POST" action="{{ route('stages.update', $stage->id) }}">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                                                    <input type="hidden" name="formId" value="stage-edit-{{ $stage->id }}" />
                                                    <div class="max-h-[calc(85vh-11rem)] overflow-y-auto px-5 py-4">
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label class="mb-1.5 block text-sm font-medium">{{ __('stages.col.code') }}</label>
                                                                <p class="rounded-md border bg-muted/40 px-3 py-2 font-mono text-sm text-muted-foreground">{{ $stage->code }}</p>
                                                            </div>
                                                            <div>
                                                                <label for="stage-name-{{ $stage->id }}" class="mb-1.5 block text-sm font-medium">{{ __('stages.nameLabel') }}</label>
                                                                <input type="text" id="stage-name-{{ $stage->id }}" name="name" value="{{ old('name', $stage->name) }}" maxlength="80" placeholder="{{ __('stages.namePlaceholder') }}" required
                                                                       class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                @error('name')
                                                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                @enderror
                                                            </div>
                                                            <div>
                                                                <span class="mb-1.5 block text-sm font-medium">{{ __('stages.appliesToLabel') }}</span>
                                                                <div class="grid grid-cols-1 gap-2 rounded-lg border p-3 sm:grid-cols-2">
                                                                    @foreach (\App\Support\Atelier::TASK_CATEGORIES as $category)
                                                                        <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm sm:min-h-0">
                                                                            <input type="checkbox" name="appliesTo[]" value="{{ $category }}" {{ in_array($category, old('appliesTo', $stageCategories)) ? 'checked' : '' }} class="size-4 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                                                            {{ __('labels.category.'.$category) }}
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                                <p class="mt-1 text-xs text-muted-foreground">{{ __('stages.appliesToHint') }}</p>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-3">
                                                                <div>
                                                                    <label for="stage-weight-{{ $stage->id }}" class="mb-1.5 block text-sm font-medium">{{ __('stages.weightLabel') }}</label>
                                                                    <input type="number" id="stage-weight-{{ $stage->id }}" name="weight" value="{{ old('weight', $stage->weight) }}" min="0.1" max="10" step="0.1"
                                                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                    @error('weight')
                                                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                    @enderror
                                                                </div>
                                                                <div>
                                                                    <label for="stage-sort-{{ $stage->id }}" class="mb-1.5 block text-sm font-medium">{{ __('stages.sortOrderLabel') }}</label>
                                                                    <input type="number" id="stage-sort-{{ $stage->id }}" name="sortOrder" value="{{ old('sortOrder', $stage->sort_order) }}" min="0" max="999" step="1"
                                                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                    @error('sortOrder')
                                                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <span class="mb-1.5 block text-sm font-medium">{{ __('stages.activeLabel') }}</span>
                                                                <label class="flex min-h-11 items-center gap-2 text-sm">
                                                                    <input type="hidden" name="isActive" value="0" />
                                                                    <input type="checkbox" name="isActive" value="1" {{ old('isActive', $stage->is_active) ? 'checked' : '' }} class="size-4 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                                                    {{ __('stages.activeLabel') }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                                        <button type="button" @click="open = false"
                                                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                            {{ __('common.cancel') }}
                                                        </button>
                                                        <button type="submit"
                                                                class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                            {{ __('common.saveChanges') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                                    @endforeach
                                    </tbody>
            </table>
        </div>
        @endif


    <div x-show="addOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
         aria-label="{{ __('stages.addStage') }}"
         @keydown.escape.window="addOpen = false">
        <div class="fixed inset-0 bg-black/50" @click="addOpen = false" aria-hidden="true"></div>
        <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
            <div class="relative w-full max-w-lg rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold">{{ __('stages.addStage') }}</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ __('stages.addDescription') }}</p>
                    </div>
                    <button type="button" @click="addOpen = false"
                            aria-label="{{ __('common.close') }}"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                                <form method="POST" action="{{ route('stages.store') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                    <input type="hidden" name="formId" value="stage-create" />
                    <div class="max-h-[calc(85vh-11rem)] overflow-y-auto px-5 py-4">
                        <div class="space-y-4">
                            <div>
                                <label for="stage-name" class="mb-1.5 block text-sm font-medium">{{ __('stages.nameLabel') }}</label>
                                <input type="text" id="stage-name" name="name" value="{{ old('name') }}" maxlength="80" placeholder="{{ __('stages.namePlaceholder') }}" required
                                       class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                @error('name')
                                    <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <span class="mb-1.5 block text-sm font-medium">{{ __('stages.appliesToLabel') }}</span>
                                <div class="grid grid-cols-1 gap-2 rounded-lg border p-3 sm:grid-cols-2">
                                    @foreach (\App\Support\Atelier::TASK_CATEGORIES as $category)
                                        <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm sm:min-h-0">
                                            <input type="checkbox" name="appliesTo[]" value="{{ $category }}" {{ in_array($category, old('appliesTo', [])) ? 'checked' : '' }} class="size-4 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                            {{ __('labels.category.'.$category) }}
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">{{ __('stages.appliesToHint') }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="stage-weight" class="mb-1.5 block text-sm font-medium">{{ __('stages.weightLabel') }}</label>
                                    <input type="number" id="stage-weight" name="weight" value="{{ old('weight', '1') }}" min="0.1" max="10" step="0.1"
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('weight')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="stage-sort" class="mb-1.5 block text-sm font-medium">{{ __('stages.sortOrderLabel') }}</label>
                                    <input type="number" id="stage-sort" name="sortOrder" value="{{ old('sortOrder') }}" min="0" max="999" step="1"
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('sortOrder')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div>
                                <span class="mb-1.5 block text-sm font-medium">{{ __('stages.activeLabel') }}</span>
                                <label class="flex min-h-11 items-center gap-2 text-sm">
                                    <input type="hidden" name="isActive" value="0" />
                                    <input type="checkbox" name="isActive" value="1" {{ old('isActive', true) ? 'checked' : '' }} class="size-4 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                    {{ __('stages.activeLabel') }}
                                </label>
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
                            {{ __('stages.createStage') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
            @endsection
