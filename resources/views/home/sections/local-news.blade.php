@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Adıyaman Gündemi');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $leadArticle = $localNews->first();
    $sideItems = $localNews->slice(1);
@endphp

@if ($localNews->isNotEmpty())
    <section class="border-b border-adh-border py-6 dark:border-gray-700" aria-label="{{ __('Adıyaman Gündemi') }}">
        <x-section-heading
            :title="$title"
            :subtitle="$subtitle"
            :cta-label="__('Tümünü Gör')"
            :cta-url="\App\Support\LocalizedUrl::route('news.category', ['slug' => 'gundem'])"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="{{ $sideItems->isNotEmpty() ? 'lg:col-span-6' : 'lg:col-span-12' }}">
                <x-news-card :article="$leadArticle" />
            </div>

            @if ($sideItems->isNotEmpty())
            <div class="lg:col-span-6">
                <div class="divide-y divide-adh-border rounded-[var(--adh-radius)] border border-adh-border/80 bg-white p-3 shadow-sm dark:divide-gray-700 dark:border-gray-700 dark:bg-adh-blue">
                    @foreach ($sideItems as $article)
                        <x-news-headline-item :article="$article" :show-summary="true" />
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </section>
@endif
