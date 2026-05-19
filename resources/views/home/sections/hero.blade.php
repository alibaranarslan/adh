@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Editoryal Manşet');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $heroStory = $heroMain ? \App\Support\NewsPresenter::present($heroMain) : null;
    $sideStories = $heroSide->take(5)->values();
    $leadSideArticle = $sideStories->first();
    $leadSideStory = $leadSideArticle ? \App\Support\NewsPresenter::present($leadSideArticle, 'medium') : null;
    $listSideArticles = $sideStories
        ->reject(fn ($article) => $leadSideArticle && data_get($article, 'id') === data_get($leadSideArticle, 'id'))
        ->values();
@endphp

@if ($heroMain && $heroStory)
    <section class="mb-1 border-b border-adh-red/20 pb-1 dark:border-red-400/20 md:mb-3 md:border-b-2 md:border-adh-text md:pb-3 md:dark:border-gray-700" aria-label="{{ __('Manşet Haberleri') }}" data-testid="editorial-hero-section">
        <div class="mb-2 flex flex-col gap-1.5 border-b border-adh-border/80 pb-2 dark:border-gray-700 md:mb-4 md:flex-row md:items-end md:justify-between md:gap-2 md:pb-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-adh-red">{{ __('Bugünün Editör Seçimi') }}</p>
                <h2 class="mt-1 font-serif text-[1.55rem] font-bold leading-tight text-adh-text dark:text-gray-100 md:text-3xl">
                    {{ $title }}
                </h2>
                @if ($subtitle)
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-adh-gray dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="hidden items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-adh-gray dark:text-gray-400 md:flex">
                <span class="h-px w-10 bg-adh-red/50" aria-hidden="true"></span>
                <span>{{ __('Redaksiyon Vitrini') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:gap-5 lg:grid-cols-12 lg:items-stretch lg:gap-x-6">
            <div class="{{ $sideStories->isNotEmpty() ? 'lg:col-span-7 xl:col-span-8' : 'lg:col-span-12' }}">
                <a href="{{ $heroStory['url'] }}" class="group block h-full">
                    <div class="relative h-full overflow-hidden rounded-[calc(var(--adh-radius)*1.1)] border border-adh-border bg-adh-navy shadow-[var(--adh-shadow)] dark:border-gray-700" data-testid="editorial-hero-card">
                        @if ($heroStory['has_image'])
                            <img
                                src="{{ $heroStory['image_url'] }}"
                                alt="{{ $heroStory['title'] }}"
                                width="1200"
                                height="760"
                                class="h-[186px] w-full object-cover transition-transform duration-500 group-hover:scale-[1.025] min-[390px]:h-[198px] sm:h-[320px] lg:h-[430px]"
                                loading="eager"
                                fetchpriority="high"
                            >
                        @else
                            <span class="flex h-[186px] w-full items-center justify-center bg-[radial-gradient(circle_at_18%_18%,rgba(255,255,255,0.16),transparent_28%),linear-gradient(135deg,#0a1632,#172554_52%,#7f1d1d)] px-6 text-center text-sm font-black uppercase tracking-[0.22em] text-white/88 transition-transform duration-500 group-hover:scale-[1.01] min-[390px]:h-[198px] sm:h-[320px] lg:h-[430px]">
                                {{ $heroStory['category_name'] ?: __('Haber') }}
                            </span>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-adh-navy via-adh-navy/90 to-transparent px-4 pb-3 pt-12 text-white sm:px-6 sm:pb-6 sm:pt-28 lg:px-7 lg:pb-7">
                            <div class="max-w-3xl space-y-1.5 sm:space-y-3">
                                @if ($heroStory['category_name'])
                                    <span class="inline-flex rounded-sm bg-adh-red px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-white shadow-sm">
                                        {{ $heroStory['category_name'] }}
                                    </span>
                                @endif

                                <h1 class="line-clamp-2 text-balance font-serif text-[1.08rem] font-bold leading-tight text-white transition-colors group-hover:text-red-100 min-[390px]:text-[1.15rem] sm:line-clamp-3 sm:text-3xl lg:text-[2.45rem]">
                                    {{ $heroStory['title'] }}
                                </h1>

                                @if ($heroStory['summary'])
                                    <p class="line-clamp-1 max-w-2xl text-[12px] leading-5 text-white/86 min-[390px]:text-[13px] sm:line-clamp-2 sm:text-[15px] sm:leading-6">
                                        {{ $heroStory['summary'] }}
                                    </p>
                                @endif

                                <div class="flex flex-wrap items-center gap-2.5">
                                    <x-news-meta-row :article="$heroMain" :show-source="true" class="text-white/75" />
                                    <span class="hidden h-1 w-1 rounded-full bg-white/40 sm:inline-block" aria-hidden="true"></span>
                                    <span class="hidden text-xs font-semibold uppercase tracking-[0.16em] text-white/80 sm:inline">
                                        {{ __('Haberi oku') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            @if ($sideStories->isNotEmpty())
                <div class="clear-both mt-3 lg:col-span-5 lg:mt-0 xl:col-span-4">
                    <div class="relative z-0 flex h-full flex-col rounded-[calc(var(--adh-radius)*1.1)] border border-adh-border/90 bg-white shadow-sm dark:border-gray-700 dark:bg-adh-blue/80">
                        <div class="border-b border-adh-border px-3.5 py-3 dark:border-gray-700 md:px-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-adh-red">{{ __('Gündem Masası') }}</p>
                                    <h3 class="mt-0.5 font-serif text-lg font-bold leading-tight text-adh-text dark:text-gray-100">
                                        {{ __('Hızlı Haber Akışı') }}
                                    </h3>
                                </div>
                                <span class="rounded-full border border-adh-red/20 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-adh-red dark:border-red-400/25 dark:text-red-300">
                                    {{ $sideStories->count() }} {{ __('haber') }}
                                </span>
                            </div>
                        </div>

                        @if ($leadSideStory && $leadSideArticle)
                            <article class="group border-b border-adh-border p-3.5 dark:border-gray-700 md:p-4">
                                <a href="{{ $leadSideStory['url'] }}" class="block overflow-hidden rounded-[calc(var(--adh-radius)*0.85)] bg-slate-100 ring-1 ring-adh-border/80 dark:bg-slate-800 dark:ring-gray-700">
                                    @if ($leadSideStory['has_image'])
                                        <img
                                            src="{{ $leadSideStory['image_url'] }}"
                                            alt="{{ $leadSideStory['title'] }}"
                                            width="640"
                                            height="360"
                                            class="h-32 w-full object-cover transition-transform duration-300 group-hover:scale-[1.03] lg:h-36"
                                            loading="lazy"
                                        >
                                    @else
                                        <span class="flex h-32 w-full items-center justify-center bg-adh-red/10 px-3 text-center text-xs font-black uppercase tracking-[0.18em] text-adh-red dark:bg-red-400/10 dark:text-red-300 lg:h-36">
                                            {{ $leadSideStory['category_name'] ?: __('Haber') }}
                                        </span>
                                    @endif
                                </a>

                                <div class="mt-2.5">
                                    @if ($leadSideStory['category_name'])
                                        <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-adh-red">{{ $leadSideStory['category_name'] }}</span>
                                    @endif

                                    <h3 class="mt-1 line-clamp-2 font-serif text-base font-bold leading-snug text-adh-text dark:text-gray-100">
                                        <a href="{{ $leadSideStory['url'] }}" class="transition-colors hover:text-adh-red">
                                            {{ $leadSideStory['title'] }}
                                        </a>
                                    </h3>

                                    <div class="mt-1.5">
                                        <x-news-meta-row :article="$leadSideArticle" compact />
                                    </div>
                                </div>
                            </article>
                        @endif

                        <div class="divide-y divide-adh-border dark:divide-gray-700">
                            @foreach ($listSideArticles as $article)
                                @php $sideStory = \App\Support\NewsPresenter::present($article, 'thumb'); @endphp
                                <article class="group overflow-hidden px-3.5 py-2.5 md:px-4">
                                    <div class="flex items-start gap-2.5 md:gap-3">
                                        <a href="{{ $sideStory['url'] }}" class="shrink-0 overflow-hidden rounded-[calc(var(--adh-radius)*0.8)] bg-slate-100 ring-1 ring-adh-border/70 dark:bg-slate-800 dark:ring-gray-700">
                                            @if ($sideStory['has_image'])
                                                <img
                                                    src="{{ $sideStory['image_url'] }}"
                                                    alt="{{ $sideStory['title'] }}"
                                                    width="160"
                                                    height="160"
                                                    class="h-12 w-12 object-cover transition-transform duration-300 group-hover:scale-105 md:h-14 md:w-14"
                                                    loading="lazy"
                                                >
                                            @else
                                                <span class="flex h-12 w-12 items-center justify-center bg-adh-red/10 px-1 text-center text-[8px] font-black uppercase leading-tight tracking-[0.1em] text-adh-red dark:bg-red-400/10 dark:text-red-300 md:h-14 md:w-14">
                                                    {{ $sideStory['category_name'] ?: __('Haber') }}
                                                </span>
                                            @endif
                                        </a>

                                        <div class="adh-visual-news-text">
                                            @if ($sideStory['category_name'])
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-adh-red">{{ $sideStory['category_name'] }}</span>
                                            @endif

                                            <h2 class="mt-0.5 line-clamp-2 overflow-hidden whitespace-normal break-words text-[13px] font-bold leading-snug text-adh-text dark:text-gray-100 md:text-[13.5px]">
                                                <a href="{{ $sideStory['url'] }}" class="block whitespace-normal break-words transition-colors hover:text-adh-red">
                                                    {{ $sideStory['title'] }}
                                                </a>
                                            </h2>

                                            <div class="mt-1">
                                                <x-news-meta-row :article="$article" compact />
                                            </div>
                                        </div>
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
