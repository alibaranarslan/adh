@props(['article', 'showSummary' => true])

@php
    $story = \App\Support\NewsPresenter::present($article);
@endphp

<article class="group relative min-h-[320px] w-full overflow-hidden rounded-[var(--adh-radius)] bg-slate-900 shadow-[var(--adh-shadow)] lg:min-h-[440px]">
    <a href="{{ $story['url'] }}" class="block h-full">
        <img
            src="{{ $story['image_url'] }}"
            alt="{{ $story['title'] }}"
            width="1200"
            height="760"
            class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
            loading="eager"
        >

        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-black/5"></div>

        <div class="absolute bottom-0 left-0 right-0 p-4 md:p-6">
            @if ($story['category_name'])
                <span class="mb-2 inline-flex rounded-full bg-adh-red px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white">
                    {{ $story['category_name'] }}
                </span>
            @endif

            <h2 class="line-clamp-3 font-serif text-2xl font-bold leading-tight text-white md:text-3xl lg:text-[2.15rem]">
                {{ $story['title'] }}
            </h2>

            @if ($showSummary && $story['summary'])
                <p class="mt-2 hidden max-w-3xl line-clamp-2 text-sm text-gray-200 sm:block md:text-base">
                    {{ $story['summary'] }}
                </p>
            @endif

            <x-news-meta-row :article="$article" compact :show-source="true" class="mt-3 text-white/80 dark:text-white/80" />
        </div>
    </a>
</article>