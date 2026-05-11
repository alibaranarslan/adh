@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Editoryal Manşet');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $heroStory = $heroMain ? \App\Support\NewsPresenter::present($heroMain) : null;
@endphp

@if ($heroMain && $heroStory)
    <section class="mb-12 border-b-2 border-adh-text pb-10 dark:border-gray-700 md:mb-14 md:pb-12" aria-label="{{ __('Manşet Haberleri') }}">
        <x-section-heading :title="$title" :subtitle="$subtitle" eyebrow="{{ __('Bugünün Editör Seçimi') }}" />

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start lg:gap-x-8">
            <div class="border-adh-border dark:border-gray-700 {{ $heroSide->isNotEmpty() ? 'lg:col-span-8 lg:border-r lg:pr-8' : 'lg:col-span-12' }}">
                <a href="{{ $heroStory['url'] }}" class="group block">
                    <div class="overflow-hidden rounded-[var(--adh-radius)] shadow-[var(--adh-shadow)]">
                        <img
                            src="{{ $heroStory['image_url'] }}"
                            alt="{{ $heroStory['title'] }}"
                            width="1200"
                            height="760"
                            class="aspect-[16/10] h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
                            loading="eager"
                            fetchpriority="high"
                        >
                    </div>

                    <div class="mt-5 space-y-4 pb-3">
                        @if ($heroStory['category_name'])
                            <span class="text-[10px] font-bold uppercase tracking-[0.24em] text-adh-red">{{ $heroStory['category_name'] }}</span>
                        @endif

                        <h1 class="text-balance font-serif text-2xl font-bold leading-tight text-adh-text transition-colors group-hover:text-adh-red dark:text-gray-100 md:text-3xl lg:text-[2.7rem]">
                            {{ $heroStory['title'] }}
                        </h1>

                        @if ($heroStory['summary'])
                            <p class="max-w-3xl text-sm leading-7 text-adh-gray dark:text-gray-400 md:text-base">
                                {{ $heroStory['summary'] }}
                            </p>
                        @endif

                        <x-news-meta-row :article="$heroMain" :show-source="true" />
                    </div>
                </a>
            </div>

            @if ($heroSide->isNotEmpty())
            <div class="lg:col-span-4 lg:self-start lg:pl-8">
                <div class="rounded-[var(--adh-radius)] border border-adh-border/80 bg-white/80 p-4 shadow-sm dark:border-gray-700 dark:bg-adh-blue/80">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Hızlı Gündem Akışı') }}</p>
                    <div class="mt-3 divide-y divide-adh-border dark:divide-gray-700">
                        @foreach ($heroSide as $article)
                            @php $sideStory = \App\Support\NewsPresenter::present($article, 'thumb'); @endphp
                            <article class="group py-3 first:pt-0 last:pb-0">
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
