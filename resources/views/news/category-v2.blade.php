@extends('layouts.app')

@push('head')
    <x-schema-page
        type="CollectionPage"
        :name="$category->name . ' Haberleri'"
        :description="$category->name . ' kategorisindeki son haberler ve güncel gelişmeler.'"
        :url="\App\Support\SeoUrls::absolute(\App\Support\LocalizedUrl::route('news.category', ['slug' => $category->slug]))"
    />
@endpush

@section('content')
    @php
        $leadArticle = $articles->getCollection()->first();
        $spotlightItems = $articles->getCollection()->slice(1, 4);
        $gridItems = $articles->getCollection()->slice(5);
    @endphp

    <section class="space-y-6 overflow-hidden rounded-[var(--adh-radius)] border border-adh-border bg-white p-4 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue md:p-6">
        <div class="rounded-[var(--adh-radius)] border border-adh-border/80 bg-slate-50/80 px-4 py-5 dark:border-gray-700 dark:bg-adh-navy/30 md:px-5 md:py-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ __('Kategori Dosyası') }}</p>
            <h1 class="mt-2 break-words font-serif text-2xl font-bold text-adh-text dark:text-gray-100 sm:text-3xl md:text-[2.3rem]">{{ $category->name }}</h1>
            <p class="mt-3 max-w-3xl break-words text-sm leading-6 text-adh-gray dark:text-gray-300 md:leading-7">
                {{ __(':count haberlik yayın akışı bu kategoride canlı olarak toplanıyor. Ana gelişmeler üstte, devam okuması alt şeritlerde sunulur.', ['count' => $articles->total()]) }}
            </p>
        </div>

        @if ($leadArticle)
            <div class="grid min-w-0 grid-cols-1 gap-5 lg:grid-cols-12 lg:gap-6">
                <div class="min-w-0 lg:col-span-8">
                    <x-news-card-large :article="$leadArticle" />
                </div>

                <div class="min-w-0 lg:col-span-4">
                    <div class="rounded-[var(--adh-radius)] border border-adh-border/80 bg-slate-50 p-4 dark:border-gray-700 dark:bg-adh-navy/40">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Hızlı Bakış') }}</p>
                        <div class="mt-3 divide-y divide-adh-border dark:divide-gray-700">
                            @foreach ($spotlightItems as $article)
                                <x-news-headline-item :article="$article" :show-summary="true" />
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($gridItems->isNotEmpty())
            <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2 md:gap-5 xl:grid-cols-3">
                @foreach ($gridItems as $article)
                    <x-news-card :article="$article" />
                @endforeach
            </div>
        @endif

        <div>
            {{ $articles->links() }}
        </div>
    </section>
@endsection
