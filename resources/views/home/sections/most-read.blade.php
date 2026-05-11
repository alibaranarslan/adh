@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('En Çok Okunan');
    $subtitle = data_get($settings, "subtitle_override.$locale");
@endphp

@if ($mostRead->isNotEmpty())
    <section class="border-b border-adh-border py-6 dark:border-gray-700">
        <x-section-heading :title="$title" :subtitle="$subtitle" eyebrow="{{ __('Okur Gündemi') }}" />

        <ol class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($mostRead as $index => $article)
                <li class="rounded-[var(--adh-radius)] border border-adh-border bg-white p-4 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue">
                    <div class="flex gap-3">
                        <span class="w-7 shrink-0 font-serif text-2xl font-bold leading-none text-adh-red/35">{{ $index + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="line-clamp-3 font-serif text-[14px] font-semibold leading-snug text-adh-text dark:text-gray-100">
                                <a href="{{ route('news.show', ['slug' => $article->slug, 'locale' => app()->getLocale()]) }}" class="transition-colors hover:text-adh-red">
                                    {{ $article->title }}
                                </a>
                            </h3>
                            <div class="mt-2">
                                <x-news-meta-row :article="$article" compact :show-views="true" :show-read-time="false" />
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endif