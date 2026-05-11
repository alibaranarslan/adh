@props(['article'])

@php
    $story = \App\Support\NewsPresenter::present($article, 'thumb');
@endphp

<article class="group -mx-2 flex items-start gap-3 rounded px-2 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-adh-navy/40">
    <a href="{{ $story['url'] }}" class="shrink-0 overflow-hidden rounded">
        <img
            src="{{ $story['image_url'] }}"
            alt="{{ $story['title'] }}"
            width="224"
            height="160"
            class="h-[72px] w-24 object-cover transition-transform duration-200 group-hover:scale-105 sm:h-20 sm:w-28"
            loading="lazy"
        >
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            @if ($story['category_name'])
                <span class="text-[10px] font-bold uppercase tracking-wider text-adh-red">{{ $story['category_name'] }}</span>
            @endif

            @if (($article->status ?? null) === 'archived')
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ __('Arşiv') }}
                </span>
            @endif
        </div>

        <h3 class="mt-0.5 line-clamp-2 font-serif text-sm font-semibold leading-snug text-adh-text dark:text-gray-100">
            <a href="{{ $story['url'] }}" class="transition-colors hover:text-adh-red dark:hover:text-adh-red-light">
                {{ $story['title'] }}
            </a>
        </h3>

        @if ($story['summary'])
            <p class="mt-1 hidden line-clamp-2 text-xs text-adh-gray dark:text-gray-400 sm:block">{{ $story['summary'] }}</p>
        @endif

        <div class="mt-1">
            <x-news-meta-row :article="$article" compact />
        </div>
    </div>
</article>
