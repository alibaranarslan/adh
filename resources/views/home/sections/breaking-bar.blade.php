@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Son Dakika');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $breakingCount = $breakingNews->count();
    $itemsWrapperClass = $breakingCount <= 1 ? 'w-full lg:max-w-xl' : 'flex-1';
@endphp

@if ($breakingNews->isNotEmpty())
    <section class="mt-2 rounded-[var(--adh-radius)] border border-adh-red/20 bg-adh-red/[0.03] px-4 py-3 dark:border-red-400/20 dark:bg-red-400/5 md:mt-6 md:py-4" aria-label="{{ __('Son Dakika') }}">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:gap-6">
            <div class="shrink-0 border-l-4 border-adh-red pl-3 lg:w-52">
                <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ __('Acil Gündem') }}</p>
                <h2 class="mt-1 font-serif text-xl font-bold text-adh-text dark:text-gray-100">
                    {{ $title }}
                </h2>

                @if ($subtitle)
                    <p class="mt-1 text-sm text-adh-gray dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="{{ $itemsWrapperClass }}">
                <div class="grid grid-cols-1 gap-2 md:[grid-template-columns:repeat(auto-fit,minmax(15rem,1fr))]">
                    @foreach ($breakingNews as $article)
                        <a
                            href="{{ \App\Support\LocalizedUrl::route('news.show', ['slug' => $article->slug]) }}"
                            class="rounded-[var(--adh-radius)] border border-adh-red/15 bg-white/90 px-4 py-3 text-sm font-semibold leading-6 text-adh-text transition hover:border-adh-red hover:text-adh-red dark:border-red-400/10 dark:bg-adh-blue/80 dark:text-gray-100"
                        >
                            {{ $article->getTranslation('title', app()->getLocale(), false) ?: $article->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
