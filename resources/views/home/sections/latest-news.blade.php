@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Son Haberler');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $leadItems = $latestNews->take(3);
    $listItems = $latestNews->skip(3)->take(5);
@endphp

@if ($latestNews->isNotEmpty())
    <section class="border-b border-adh-border py-6 dark:border-gray-700">
        <x-section-heading :title="$title" :subtitle="$subtitle" eyebrow="{{ __('Canlı Akış') }}" />

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="{{ $listItems->isNotEmpty() ? 'xl:col-span-7' : 'xl:col-span-12' }}">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($leadItems as $article)
                        <x-news-card :article="$article" />
                    @endforeach
                </div>
            </div>

            @if ($listItems->isNotEmpty())
            <div class="xl:col-span-5">
                <div class="divide-y divide-adh-border rounded-[var(--adh-radius)] border border-adh-border/80 bg-white p-3 shadow-sm dark:divide-gray-700 dark:border-gray-700 dark:bg-adh-blue">
                    @foreach ($listItems as $article)
                        <x-news-headline-item :article="$article" :show-summary="true" />
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </section>
@endif
