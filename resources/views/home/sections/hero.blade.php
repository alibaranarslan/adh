@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Editoryal Manşet');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $heroStory = $heroMain ? \App\Support\NewsPresenter::present($heroMain) : null;
@endphp

@if ($heroMain && $heroStory)
    <section class="mb-4 border-b-2 border-adh-text pb-4 dark:border-gray-700 md:mb-14 md:pb-12" aria-label="{{ __('Manşet Haberleri') }}">
        <x-section-heading :title="$title" :subtitle="$subtitle" eyebrow="{{ __('Bugünün Editör Seçimi') }}" />

        <div class="grid grid-cols-1 gap-4 md:gap-8 lg:grid-cols-12 lg:items-start lg:gap-x-8">
            <div class="border-adh-border dark:border-gray-700 {{ $heroSide->isNotEmpty() ? 'lg:col-span-8 lg:border-r lg:pr-8' : 'lg:col-span-12' }}">
                <a href="{{ $heroStory['url'] }}" class="group block">
                    <div class="relative overflow-hidden rounded-[var(--adh-radius)] bg-adh-navy shadow-[var(--adh-shadow)]">
                        <img
                            src="{{ $heroStory['image_url'] }}"
                            alt="{{ $heroStory['title'] }}"
                            width="1200"
                            height="760"
                            class="h-[255px] w-full object-cover transition-transform duration-500 group-hover:scale-[1.02] sm:h-[330px] lg:h-[360px]"
                            loading="eager"
                            fetchpriority="high"
                        >
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-adh-navy via-adh-navy/88 to-transparent px-4 pb-4 pt-16 text-white sm:px-6 sm:pb-5 sm:pt-24 lg:px-7 lg:pb-6">
                            <div class="max-w-3xl space-y-2 sm:space-y-2.5">
                                @if ($heroStory['category_name'])
                                    <span class="inline-flex rounded-full bg-adh-red px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-white shadow-sm">
                                        {{ $heroStory['category_name'] }}
                                    </span>
                                @endif

                                <h1 class="line-clamp-2 text-balance font-serif text-[1.22rem] font-bold leading-tight text-white transition-colors group-hover:text-red-100 sm:text-2xl lg:text-[2rem]">
                                    {{ $heroStory['title'] }}
                                </h1>

                                @if ($heroStory['summary'])
                                    <p class="line-clamp-2 max-w-2xl text-[13px] leading-5 text-white/86 sm:text-sm sm:leading-6">
                                        {{ $heroStory['summary'] }}
                                    </p>
                                @endif

                                <x-news-meta-row :article="$heroMain" :show-source="true" class="text-white/75" />
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            @if ($heroSide->isNotEmpty())
            <div class="clear-both mt-4 lg:col-span-4 lg:mt-0 lg:self-start lg:pl-8">
                <div class="relative z-0 rounded-[var(--adh-radius)] border border-adh-border/80 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-adh-blue/80 md:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Hızlı Gündem Akışı') }}</p>
                    <div class="mt-2 divide-y divide-adh-border dark:divide-gray-700 md:mt-3">
                        @foreach ($heroSide as $article)
                            @php $sideStory = \App\Support\NewsPresenter::present($article, 'thumb'); @endphp
                            <article class="group py-2.5 first:pt-0 last:pb-0 md:py-3">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        @if ($sideStory['category_name'])
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-adh-red">{{ $sideStory['category_name'] }}</span>
                                        @endif

                                        <h2 class="mt-0.5 line-clamp-2 font-serif text-[15px] font-bold leading-snug text-adh-text dark:text-gray-100">
                                            <a href="{{ $sideStory['url'] }}" class="transition-colors hover:text-adh-red">
                                                {{ $sideStory['title'] }}
                                            </a>
                                        </h2>

                                        <div class="mt-1">
                                            <x-news-meta-row :article="$article" compact />
                                        </div>
                                    </div>

                                    <a href="{{ $sideStory['url'] }}" class="shrink-0 overflow-hidden rounded-[calc(var(--adh-radius)*0.8)]">
                                        <img
                                            src="{{ $sideStory['image_url'] }}"
                                            alt="{{ $sideStory['title'] }}"
                                            width="160"
                                            height="160"
                                            class="h-20 w-20 object-cover transition-transform duration-300 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </section>
@endif
