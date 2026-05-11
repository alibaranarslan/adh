@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Bölge Haberleri');
    $subtitle = data_get($settings, "subtitle_override.$locale");
    $leadItems = $regionNews->take(3);
    $listItems = $regionNews->skip(3);
@endphp

@if ($regionNews->isNotEmpty())
    <section class="border-b border-adh-border py-6 dark:border-gray-700">
        <x-section-heading :title="$title" :subtitle="$subtitle" eyebrow="{{ __('Yakın Çevre') }}" />

        <div class="space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($leadItems as $article)
                    <x-news-card :article="$article" />
                @endforeach
            </div>

            @if ($listItems->isNotEmpty())
                <div class="grid grid-cols-1 rounded-[var(--adh-radius)] border border-adh-border/80 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-adh-blue md:grid-cols-3">
                    @foreach ($listItems as $article)
                        <div class="{{ $loop->last ? '' : 'border-b border-adh-border dark:border-gray-700 md:border-b-0 md:border-r' }} md:px-3">
                            <x-news-headline-item :article="$article" :show-summary="true" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
