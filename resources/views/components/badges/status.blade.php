@php
    /**
     * Status badge — <x-badges.status :status="$status"/>
     *
     * Base classes + per-status palette (view contract §Badge partial API;
     * palettes verified against the live captures: ONGOING/DELIVERED/UNASSIGNED
     * from staff-dashboard-en-content.html, tasks-en-content.html).
     */
    $status = $status ?? '';

    $palettes = [
        'UNASSIGNED' => 'bg-stone-100 text-stone-700 border-stone-300',
        'ASSIGNED' => 'bg-teal-50 text-teal-800 border-teal-300',
        'ONGOING' => 'bg-amber-50 text-amber-800 border-amber-300',
        'AWAITING_INSPECTION' => 'bg-orange-50 text-orange-800 border-orange-300',
        'CHANGES_REQUESTED' => 'bg-rose-50 text-rose-800 border-rose-300',
        'APPROVED' => 'bg-green-50 text-green-800 border-green-300',
        'READY_FOR_PICKUP' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
        'DELIVERED' => 'bg-stone-800 text-stone-50 border-stone-800',
        'BLOCKED' => 'bg-red-50 text-red-800 border-red-300',
        'CANCELLED' => 'bg-stone-100 text-stone-500 border-stone-300 line-through',
    ];

    $palette = $palettes[$status] ?? 'bg-stone-100 text-stone-500 border-stone-300';
@endphp
<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $palette }}">{{ __('labels.status.'.$status) }}</span>
