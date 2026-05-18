@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Son Dakika');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $items = $breakingNews->take(6);
@endphp

@if ($items->isNotEmpty())
    <section class="rounded-[var(--adh-radius)] border border-adh-red/20 bg-gradient-to-b from-adh-red/[0.04] to-white px-3 py-3 dark:border-red-400/20 dark:from-red-400/10 dark:to-adh-blue/60 md:px-4" aria-label="{{ __('Son Dakika') }}">
        <div class="mb-2 flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-adh-red md:hidden">
            <span class="h-px flex-1 bg-adh-red/25" aria-hidden="true"></span>
            <span>{{ __('Gündem akışının devamı') }}</span>
            <span class="h-px flex-1 bg-adh-red/25" aria-hidden="true"></span>
        </div>

        <div class="flex flex-col gap-2.5">
            <div class="flex flex-col border-l-4 border-adh-red pl-3 sm:flex-row sm:items-end sm:gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ __('Acil Gündem') }}</p>
                <h2 class="mt-0.5 font-serif text-lg font-bold leading-tight text-adh-text dark:text-gray-100 sm:mt-0 md:text-xl">
                    {{ $title }}
                </h2>

                @if ($subtitle)
                    <p class="mt-1 text-sm text-adh-gray dark:text-gray-400 sm:mt-0">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($items as $article)
                    @php $story = \App\Support\NewsPresenter::present($article, 'thumb'); @endphp
                    <a
                        href="{{ $story['url'] }}"
                        class="group flex min-h-[3.8rem] items-center gap-2.5 overflow-hidden rounded-[var(--adh-radius)] border border-adh-red/15 bg-white/92 p-2 text-adh-text transition hover:border-adh-red hover:shadow-sm dark:border-red-400/10 dark:bg-adh-blue/80 dark:text-gray-100 md:min-h-[4.25rem] md:p-2.5"
                    >
                        @if ($story['has_image'])
                            <span class="shrink-0 overflow-hidden rounded-[calc(var(--adh-radius)*0.75)] bg-slate-100 ring-1 ring-adh-border/70 dark:bg-slate-800 dark:ring-gray-700">
                                <img
                                    src="{{ $story['image_url'] }}"
                                    alt="{{ $story['title'] }}"
                                    width="120"
                                    height="120"
                                    class="h-12 w-12 object-cover transition-transform duration-300 group-hover:scale-105 md:h-14 md:w-14"
                                    loading="lazy"
                                >
                            </span>
                        @else
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[calc(var(--adh-radius)*0.75)] border border-adh-red/20 bg-adh-red/10 px-1 text-center text-[9px] font-black uppercase leading-tight tracking-[0.12em] text-adh-red dark:border-red-400/25 dark:bg-red-400/10 dark:text-red-300 md:h-14 md:w-14">
                                {{ $story['category_name'] ?: __('Haber') }}
                            </span>
                        @endif

                        <span class="adh-visual-news-text adh-visual-news-text--breaking">
                            @if ($story['category_name'])
                                <span class="block text-[9px] font-bold uppercase tracking-[0.16em] text-adh-red">
                                    {{ $story['category_name'] }}
                                </span>
                            @endif

                            <span class="mt-0.5 block line-clamp-2 whitespace-normal break-words text-[13px] font-semibold leading-5 transition-colors group-hover:text-adh-red md:text-sm">
                                {{ $story['title'] }}
                            </span>

                            <span class="mt-0.5 block">
                                <x-news-meta-row :article="$article" compact :show-freshness="false" />
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
