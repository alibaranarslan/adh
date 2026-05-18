@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Günün Önemli Gelişmeleri');
    $subtitle = data_get($settings, "subtitle_override.$locale");
@endphp

@if ($highlights->isNotEmpty())
    <section class="border-b border-adh-border py-4 dark:border-gray-700 md:py-5">
        <x-section-heading :title="$title" :subtitle="$subtitle" eyebrow="{{ __('Editör Radarında') }}" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($highlights as $article)
                <x-news-card :article="$article" />
            @endforeach
        </div>
    </section>
@endif
