@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'ctaLabel' => null,
    'ctaUrl' => null,
    'tag' => 'h2',
])

<div class="mb-5 flex flex-col gap-3 border-b border-adh-border/80 pb-3 dark:border-gray-700 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ $eyebrow }}</p>
        @endif

        <{{ $tag }} class="mt-1 text-balance font-serif text-xl font-bold text-adh-text dark:text-gray-100 md:text-[1.65rem]">
            {{ $title }}
        </{{ $tag }}>

        @if ($subtitle)
            <p class="mt-1 max-w-3xl text-sm text-adh-gray dark:text-gray-400">{{ $subtitle }}</p>
        @endif
    </div>

    @if (filled($ctaLabel) && filled($ctaUrl))
        <a
            href="{{ $ctaUrl }}"
            class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-adh-red transition hover:text-adh-red-light dark:text-red-300 dark:hover:text-red-200"
        >
            <span>{{ $ctaLabel }}</span>
            <span aria-hidden="true">→</span>
        </a>
    @endif
</div>
