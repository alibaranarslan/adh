@extends('layouts.app')

@push('head')
    <x-schema-page
        type="CollectionPage"
        :name="$cityName . ' Haberleri'"
        :description="$cityName . ' haberleri, son dakika gelişmeleri ve yerel gündem başlıkları.'"
        :url="\App\Support\SeoUrls::absolute(\App\Support\LocalizedUrl::route('city.show', ['slug' => $slug]))"
    />
@endpush

@section('content')
    @php
        $leadArticle = $articles->getCollection()->first();
        $railItems = $articles->getCollection()->slice(1, 4);
        $gridItems = $articles->getCollection()->slice(5);
    @endphp

    <section class="space-y-6 rounded-[var(--adh-radius)] border border-adh-border bg-white p-5 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue md:p-6">
        <div class="rounded-[var(--adh-radius)] border border-adh-border/80 bg-slate-50/80 px-5 py-6 dark:border-gray-700 dark:bg-adh-navy/30">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 flex-shrink-0 text-adh-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ __('Şehir Dosyası') }}</p>
            </div>
            <h1 class="mt-2 font-serif text-3xl font-bold text-adh-text dark:text-gray-100 md:text-[2.3rem]">{{ $cityName }} {{ __('Haberleri') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-adh-gray dark:text-gray-300">
                {{ __('Bu şehir hattında yayınlanan :count haber, hızlı başlıklar ve devam akışıyla birlikte tek yüzeyde sunuluyor.', ['count' => $articles->total()]) }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2 border-b border-adh-border pb-5 dark:border-gray-700">
            @foreach ($allCities as $citySlug => $cityLabel)
                <a href="{{ \App\Support\LocalizedUrl::route('city.show', ['slug' => $citySlug]) }}"
                   class="rounded-full border px-3 py-1.5 text-xs transition-colors
                          {{ $citySlug === $slug
                              ? 'border-adh-red bg-adh-red text-white'
                              : 'border-adh-border text-adh-text hover:border-adh-red hover:text-adh-red dark:border-gray-600 dark:text-gray-300 dark:hover:border-red-400 dark:hover:text-red-300' }}">
                    {{ $cityLabel }}
                </a>
            @endforeach
        </div>

        @if ($articles->isEmpty())
            <p class="py-12 text-center text-adh-gray dark:text-gray-400">
                {{ $cityName }} {{ __('için henüz haber bulunmuyor. Yeni yayınlar bu akışta otomatik olarak görünür.') }}
            </p>
        @else
            @if ($leadArticle)
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-8">
                        <x-news-card-large :article="$leadArticle" />
                    </div>

                    <div class="lg:col-span-4">
                        <div class="rounded-[var(--adh-radius)] border border-adh-border/80 bg-slate-50 p-4 dark:border-gray-700 dark:bg-adh-navy/40">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Şehir Akışı') }}</p>
                            <div class="mt-3 divide-y divide-adh-border dark:divide-gray-700">
                                @foreach ($railItems as $article)
                                    <x-news-headline-item :article="$article" :show-summary="true" />
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($gridItems->isNotEmpty())
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @foreach ($gridItems as $article)
                        <x-news-card :article="$article" />
                    @endforeach
                </div>
            @endif

            <div class="mt-6">
                {{ $articles->links() }}
            </div>
        @endif
    </section>
@endsection
