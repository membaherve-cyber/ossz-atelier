@php
    /**
     * Priority badge — <x-badges.priority :priority="$priority"/>
     *
     * NORMAL (teal-50/teal-700/teal-300) and HIGH (amber-50/amber-800/amber-300)
     * are taken verbatim from the live captures (tasks/orders/task-show pages).
     * LOW and URGENT never appear as badges in the captures — they follow the
     * same palette system (LOW muted stone, URGENT red, like BLOCKED).
     */
    $priority = $priority ?? '';

    $palettes = [
        'LOW' => 'bg-stone-100 text-stone-500 border-stone-300',
        'NORMAL' => 'bg-teal-50 text-teal-700 border-teal-300',
        'HIGH' => 'bg-amber-50 text-amber-800 border-amber-300',
        'URGENT' => 'bg-red-50 text-red-800 border-red-300',
    ];

    $palette = $palettes[$priority] ?? 'bg-stone-100 text-stone-500 border-stone-300';
@endphp
<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $palette }}">{{ __('labels.priority.'.$priority) }}</span>
