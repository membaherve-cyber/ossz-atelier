@php
    /**
     * Task category badge — <x-badges.category :category="$category"/>
     *
     * Every captured category (Full Production, Cutting Only, Sewing/Stitching
     * Only, Finishing, Ironing Only) renders the same neutral secondary chip
     * (task-show-en-content.html); OTHER shares it.
     */
    $category = $category ?? '';
@endphp
<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap bg-secondary text-secondary-foreground border-transparent">{{ __('labels.category.'.$category) }}</span>
