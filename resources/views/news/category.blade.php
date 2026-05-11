@extends('layouts.app')

@section('content')
    @php
        $leadArticle = $articles->getCollection()->first();
        $spotlightItems = $articles->getCollection()->slice(1, 3);
        $gridItems = $articles->getCollection()->slice(4);
    @endphp

    <section class="space-y-6 rounded-[var(--adh-radius)] border border-adh-border bg-white p-5 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue md:p-6">
        <x-section-heading
            :title="$category->name"
            :subtitle="__(':count haberlik editoryal arşiv ve güncel akış burada toplanıyor.', ['count' => $articles->total()])"
            eyebrow="{{ __('Kategori Dosyası') }}"
            tag="h1"
        />

        @if ($leadArticle)
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <x-news-card-large :article="$leadArticle" />
                </div>

                <div class="lg:col-span-4">
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
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
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