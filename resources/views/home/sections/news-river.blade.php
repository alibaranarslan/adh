@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Haber Akışı');
    $subtitle = data_get($settings, "subtitle_override.$locale") ?: __('Gündem, bölge ve sıcak gelişmelerden derlenen son başlıklar.');
    $items = ($newsRiver ?? collect())->take((int) data_get($settings, 'content_limit', 16))->values();
    $leadItems = $items->take(4);
    $listItems = $items->skip(4);
@endphp

@if ($items->isNotEmpty())
    <section class="border-b border-adh-border py-4 dark:border-gray-700 md:py-5" aria-label="{{ __('Haber Akışı') }}">
        <x-section-heading
            :title="$title"
            :subtitle="$subtitle"
            eyebrow="{{ __('Canlı Yayın Akışı') }}"
            :cta-label="__('Tüm haberler')"
            :cta-url="\App\Support\LocalizedUrl::route('home') . '#son-dakika'"
        />

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <div class="xl:col-span-7">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($leadItems as $article)
                        <x-news-card :article="$article" />
                    @endforeach
                </div>
            </div>

            @if ($listItems->isNotEmpty())
                <div class="xl:col-span-5">
                    <div class="grid grid-cols-1 gap-x-4 divide-y divide-adh-border rounded-[var(--adh-radius)] border border-adh-border/80 bg-white p-2.5 shadow-sm dark:divide-gray-700 dark:border-gray-700 dark:bg-adh-blue md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-1 xl:divide-x-0 xl:divide-y">
                        @foreach ($listItems as $article)
                            <x-news-headline-item :article="$article" :show-summary="$loop->index < 4" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
