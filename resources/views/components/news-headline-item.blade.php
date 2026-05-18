@props(['article', 'size' => 'base', 'showSummary' => false])

@php
    $story = \App\Support\NewsPresenter::present($article, 'thumb');
@endphp

<article class="group py-2.5 md:py-3">
    @if ($story['category_name'])
        <span class="text-[10px] font-bold uppercase tracking-wider leading-none text-adh-red">
            {{ $story['category_name'] }}
        </span>
    @endif

    <h3 class="mt-0.5 line-clamp-2 font-serif font-semibold leading-snug text-adh-text dark:text-gray-100 {{ $size === 'sm' ? 'text-[13px]' : 'text-sm' }}">
        <a href="{{ $story['url'] }}" class="transition-colors hover:text-adh-red dark:hover:text-adh-red-light">
            {{ $story['title'] }}
        </a>
    </h3>

    @if ($showSummary && $story['summary'])
        <p class="mt-1 line-clamp-2 text-xs text-adh-gray dark:text-gray-400">{{ $story['summary'] }}</p>
    @endif

    <div class="mt-1">
        <x-news-meta-row :article="$article" compact />
    </div>
</article>
