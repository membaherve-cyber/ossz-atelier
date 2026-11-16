@php
    /**
     * Stage state chip — <x-badges.stage-state :state="$state"/>
     *
     * No stage-state badge appears in the captured pages (stage states render
     * as selects/history text there), so the palettes mirror the status color
     * language of the design system: not started = stone, in progress = amber
     * (like ONGOING), completed = emerald, not applicable = muted stone.
     */
    $state = $state ?? '';

    $palettes = [
        'NOT_STARTED' => 'bg-stone-100 text-stone-700 border-stone-300',
        'IN_PROGRESS' => 'bg-amber-50 text-amber-800 border-amber-300',
        'COMPLETED' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
        'NOT_APPLICABLE' => 'bg-stone-100 text-stone-500 border-stone-300',
    ];

    $palette = $palettes[$state] ?? 'bg-stone-100 text-stone-500 border-stone-300';
@endphp
<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $palette }}">{{ __('labels.stageState.'.$state) }}</span>
