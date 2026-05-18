@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Son Dakika');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $breakingCount = $breakingNews->count();
    $itemsWrapperClass = 'w-full';
@endphp

@if ($breakingNews->isNotEmpty())
    <section class="mt-0 rounded-[var(--adh-radius)] border border-adh-red/20 bg-gradient-to-b from-adh-red/[0.045] to-white px-4 py-3 dark:border-red-400/20 dark:from-red-400/10 dark:to-adh-blue/60 md:bg-adh-red/[0.03] md:py-3 md:dark:bg-red-400/5" aria-label="{{ __('Son Dakika') }}">
        <div class="mb-2 flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-adh-red md:hidden">
            <span class="h-px flex-1 bg-adh-red/25" aria-hidden="true"></span>
            <span>{{ __('Gündem akışının devamı') }}</span>
            <span class="h-px flex-1 bg-adh-red/25" aria-hidden="true"></span>
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex flex-col border-l-4 border-adh-red pl-3 sm:flex-row sm:items-end sm:gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ __('Acil Gündem') }}</p>
                <h2 class="mt-1 font-serif text-lg font-bold leading-tight text-adh-text dark:text-gray-100 sm:mt-0 md:text-xl">
                    {{ $title }}
                </h2>

                @if ($subtitle)
                    <p class="mt-1 text-sm text-adh-gray dark:text-gray-400 sm:mt-0">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="{{ $itemsWrapperClass }}">
                <div class="grid grid-cols-1 gap-2 md:[grid-template-columns:repeat(auto-fit,minmax(16rem,1fr))] xl:[grid-template-columns:repeat(auto-fit,minmax(18rem,1fr))]">
                    @foreach ($breakingNews as $article)
                        @php $story = \App\Support\NewsPresenter::present($article, 'thumb'); @endphp
                        <a
                            href="{{ $story['url'] }}"
                            class="group flex min-h-[4.5rem] items-center gap-3 overflow-hidden rounded-[var(--adh-radius)] border border-adh-red/15 bg-white/90 p-2.5 text-adh-text transition hover:border-adh-red hover:shadow-sm dark:border-red-400/10 dark:bg-adh-blue/80 dark:text-gray-100 md:p-3"
                        >
                            <span class="shrink-0 overflow-hidden rounded-[calc(var(--adh-radius)*0.75)] bg-slate-100 ring-1 ring-adh-border/70 dark:bg-slate-800 dark:ring-gray-700">
                                <img
                                    src="{{ $story['image_url'] }}"
                                    alt="{{ $story['title'] }}"
                                    width="120"
                                    height="120"
                                    class="h-14 w-14 object-cover transition-transform duration-300 group-hover:scale-105 md:h-16 md:w-16"
                                    loading="lazy"
                                >
                            </span>

                            <span class="adh-visual-news-text adh-visual-news-text--breaking">
                                @if ($story['category_name'])
                                    <span class="block text-[9px] font-bold uppercase tracking-[0.16em] text-adh-red">
                                        {{ $story['category_name'] }}
                                    </span>
                                @endif

                                <span class="mt-0.5 block line-clamp-2 whitespace-normal break-words text-sm font-semibold leading-5 transition-colors group-hover:text-adh-red md:text-[15px]">
                                    {{ $story['title'] }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
