@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Bölge Haberleri');
    $subtitle = data_get($settings, "subtitle_override.$locale");
@endphp

@if ($regionNews->isNotEmpty())
    <section class="border-b border-adh-border py-6 dark:border-gray-700">
        <x-section-heading :title="$title" :subtitle="$subtitle" eyebrow="{{ __('Yakın Çevre') }}" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($regionNews as $article)
                <x-news-card :article="$article" />
            @endforeach
        </div>
    </section>
@endif