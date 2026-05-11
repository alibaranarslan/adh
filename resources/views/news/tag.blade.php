@extends('layouts.app')

@section('content')
    @php
        $leadArticle = $articles->getCollection()->first();
        $remaining = $articles->getCollection()->slice(1);
    @endphp

    <section class="space-y-6 rounded-[var(--adh-radius)] border border-adh-border bg-white p-5 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue md:p-6">
        <x-section-heading
            :title="'#' . $tag->name"
            :subtitle="__('Bu etiketle ilişkilendirilmiş haberlerin güncel ve arşiv akışı.')"
            eyebrow="{{ __('Etiket Arşivi') }}"
            tag="h1"
        />

        @if ($leadArticle)
            <x-news-card-large :article="$leadArticle" :show-summary="true" />
        @endif

        @if ($remaining->isNotEmpty())
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($remaining as $article)
                    <x-news-card :article="$article" />
                @endforeach
            </div>
        @endif

        <div>
            {{ $articles->links() }}
        </div>
    </section>
@endsection