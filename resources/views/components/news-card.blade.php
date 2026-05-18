@props(['article'])

@php
    $story = \App\Support\NewsPresenter::present($article);
@endphp

<article class="group overflow-hidden rounded-lg border border-adh-border bg-white transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-adh-blue">
    <a href="{{ $story['url'] }}" class="block overflow-hidden">
        <div class="aspect-[16/9] overflow-hidden bg-gradient-to-br from-adh-navy/10 to-adh-red/10">
            @if ($story['has_image'])
                <img
                    src="{{ $story['image_url'] }}"
                    alt="{{ $story['title'] }}"
                    width="800"
                    height="500"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                >
            @else
                <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top_left,rgba(199,31,43,0.18),transparent_38%),linear-gradient(135deg,rgba(10,22,50,0.08),rgba(199,31,43,0.10))] px-4 text-center transition-transform duration-300 group-hover:scale-[1.02] dark:from-adh-navy">
                    <span class="rounded-full border border-adh-red/20 bg-white/85 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] text-adh-red shadow-sm dark:border-red-400/20 dark:bg-adh-blue/85 dark:text-red-200">
                        {{ $story['category_name'] ?: __('Haber') }}
                    </span>
                </div>
            @endif
        </div>
    </a>

    <div class="space-y-1.5 p-3 md:space-y-1.5 md:p-3.5">
        @if ($story['category_name'])
            <span class="text-[10px] font-bold uppercase tracking-wider text-adh-red">
                {{ $story['category_name'] }}
            </span>
        @endif

        <h3 class="line-clamp-2 font-serif text-[15px] font-bold leading-snug text-adh-text dark:text-gray-100 md:text-base">
            <a href="{{ $story['url'] }}" class="transition-colors hover:text-adh-red dark:hover:text-adh-red-light">
                {{ $story['title'] }}
            </a>
        </h3>

        @if ($story['summary'])
            <p class="hidden line-clamp-2 text-[13px] leading-5 text-adh-gray dark:text-gray-400 sm:block">{{ $story['summary'] }}</p>
        @endif

        <x-news-meta-row :article="$article" compact />
    </div>
</article>
