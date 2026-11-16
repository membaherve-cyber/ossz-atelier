@php
    /**
     * Role badge — <x-badges.role :role="$role"/>
     *
     * Palettes taken verbatim from the live captures (users page):
     * MANAGEMENT bg-primary/10 text-primary border-primary/40,
     * ADMIN teal, STAFF stone.
     */
    $role = $role ?? '';

    $palettes = [
        'MANAGEMENT' => 'bg-primary/10 text-primary border-primary/40',
        'ADMIN' => 'bg-teal-50 text-teal-800 border-teal-300',
        'STAFF' => 'bg-stone-100 text-stone-700 border-stone-300',
    ];

    $palette = $palettes[$role] ?? 'bg-stone-100 text-stone-700 border-stone-300';
@endphp
<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $palette }}">{{ __('labels.role.'.$role) }}</span>
