@extends('layouts.app')

@push('schema')
    @php
        $locale = app()->getLocale();
        $localizedPath = function (string $path = '') use ($locale): string {
            $normalizedPath = ltrim($path, '/');
            $prefix = in_array($locale, ['en', 'ku'], true) ? $locale . '/' : '';

            return $normalizedPath === ''
                ? url('/' . $prefix)
                : url('/' . $prefix . $normalizedPath);
        };
    @endphp
    <x-schema-news-article :article="$article" />
    <x-schema-breadcrumb :items="[
        ['name' => __('Anasayfa'), 'url' => $localizedPath()],
        ['name' => $article->category?->name, 'url' => $article->category?->slug ? $localizedPath('kategori/' . $article->category->slug) : null],
        ['name' => $article->title, 'url' => url()->current()],
    ]" />
@endpush

@push('head')
    <style>
        .dark .adh-article-body-card {
            background-color: #16213e !important;
            color: #f3f4f6;
        }

        .dark .adh-article-body-card .prose {
            --tw-prose-body: #e5e7eb;
            --tw-prose-headings: #f9fafb;
            --tw-prose-links: #fca5a5;
            --tw-prose-bold: #ffffff;
            --tw-prose-counters: #d1d5db;
            --tw-prose-bullets: #d1d5db;
            --tw-prose-hr: #374151;
            --tw-prose-quotes: #f3f4f6;
            --tw-prose-quote-borders: #4b5563;
            --tw-prose-captions: #d1d5db;
            --tw-prose-code: #ffffff;
            --tw-prose-pre-code: #e5e7eb;
            --tw-prose-pre-bg: #111827;
            --tw-prose-th-borders: #4b5563;
            --tw-prose-td-borders: #374151;
            color: #e5e7eb;
        }
    </style>
@endpush

@section('fullwidth')
    @php
        $locale = app()->getLocale();
        $localizedPath = function (string $path = '') use ($locale): string {
            $normalizedPath = ltrim($path, '/');
            $prefix = in_array($locale, ['en', 'ku'], true) ? $locale . '/' : '';

            return $normalizedPath === ''
                ? url('/' . $prefix)
                : url('/' . $prefix . $normalizedPath);
        };
        $story = \App\Support\NewsPresenter::present($article);
        $authorName = trim((string) ($article->author?->name ?? ''));
        $updatedAt = $article->updated_at;
        $showUpdated = $updatedAt && (! $article->published_at || $updatedAt->gt($article->published_at->copy()->addMinute()));
        $requestedLocale = app()->getLocale();
        $localizedContent = trim((string) $article->getTranslation('content', $requestedLocale, false));
        $localizedSummary = trim((string) $article->getTranslation('summary', $requestedLocale, false));
        $fallbackContent = $requestedLocale !== 'tr' ? trim((string) $article->getTranslation('content', 'tr', false)) : '';
        $fallbackSummary = $requestedLocale !== 'tr' ? trim((string) $article->getTranslation('summary', 'tr', false)) : '';
        $articleBodySource = $localizedContent !== ''
            ? $localizedContent
            : ($localizedSummary !== ''
                ? $localizedSummary
                : ($fallbackContent !== '' ? $fallbackContent : $fallbackSummary));
        $bodySource = $localizedContent !== ''
            ? 'content'
            : ($localizedSummary !== ''
                ? 'summary'
                : ($fallbackContent !== '' ? 'content' : ($fallbackSummary !== '' ? 'summary' : null)));
        $activeCities = \App\Services\IhaCategoryMapper::getActiveCities();
        $cityLabel = $article->city_slug ? ($activeCities[$article->city_slug] ?? null) : null;

        if ($bodySource === 'content') {
            $articleBody = \App\Support\ArticleBodyRenderer::render($article, $articleBodySource);
        } elseif ($bodySource === 'summary') {
            $articleBody = '<p>' . e($articleBodySource) . '</p>';
        } else {
            $articleBody = '';
        }
    @endphp

    <x-breadcrumb :items="[
        ['label' => __('Anasayfa'), 'url' => $localizedPath()],
        ['label' => $article->category?->name, 'url' => $article->category?->slug ? $localizedPath('kategori/' . $article->category->slug) : null],
        ['label' => $article->title],
    ]" />

    <article class="overflow-hidden rounded-[var(--adh-radius)] border border-adh-border bg-white shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue">
        <header class="border-b border-adh-border/80 px-4 py-5 dark:border-gray-700 md:px-8 md:py-10">
            <div class="mx-auto max-w-4xl">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($story['category_name'])
                        <span class="inline-flex rounded-full bg-adh-red/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.24em] text-adh-red dark:bg-red-500/10 dark:text-red-300">
                            {{ $story['category_name'] }}
                        </span>
                    @endif

                    @if ($cityLabel)
                        <span class="inline-flex rounded-full border border-adh-border bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-adh-gray dark:border-gray-700 dark:bg-adh-navy/40 dark:text-gray-300">
                            {{ $cityLabel }}
                        </span>
                    @endif
                </div>

                <h1 class="mt-4 text-balance font-serif text-[1.62rem] font-bold leading-[1.1] text-adh-text dark:text-gray-100 sm:text-3xl md:text-4xl xl:text-[3.2rem]">
                    {{ $article->title }}
                </h1>

                @if ($article->summary)
                    <p class="mt-4 max-w-3xl text-sm leading-6 text-adh-gray dark:text-gray-300 md:text-lg md:leading-8">
                        {{ $article->summary }}
                    </p>
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-adh-border/70 pt-4 text-sm dark:border-gray-700">
                    <x-news-meta-row :article="$article" :show-source="true" :show-views="true" />

                    @if ($authorName !== '')
                        <span class="inline-flex items-center rounded-full border border-adh-border bg-slate-50 px-3 py-1 text-xs font-medium dark:border-gray-700 dark:bg-adh-navy/40">
                            {{ __('Yazar') }}: {{ $authorName }}
                        </span>
                    @endif

                    @if ($showUpdated)
                        <time datetime="{{ $updatedAt?->toIso8601String() }}" class="inline-flex items-center rounded-full border border-adh-border bg-slate-50 px-3 py-1 text-xs font-medium dark:border-gray-700 dark:bg-adh-navy/40">
                            {{ __('Güncellendi') }}: {{ $updatedAt?->locale(app()->getLocale())->isoFormat('D MMMM YYYY, HH:mm') }}
                        </time>
                    @endif
                </div>

                @if ($article->status === 'archived')
                    <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        <span>{{ __('Arşiv İçeriği') }}</span>
                        <span class="text-amber-600/70 dark:text-amber-200/70">•</span>
                        <span>{{ __('Bu haber yayından kaldırılmadı; arşivde saklanıyor.') }}</span>
                    </div>
                @endif
            </div>
        </header>

        @if ($story['has_image'])
            <figure class="border-b border-adh-border/80 dark:border-gray-700">
                <img
                    src="{{ $story['image_url'] }}"
                    alt="{{ $story['title'] }}"
                    width="1200"
                    height="760"
                    class="aspect-[16/9] w-full object-cover"
                    fetchpriority="high"
                >
            </figure>
        @endif

        <x-ad-slot position="article-top" class="mx-auto mt-4 max-w-3xl px-4 md:mt-6 md:px-0" />

        <div class="px-4 py-6 md:px-8 md:py-10">
            <div class="mx-auto max-w-3xl">
                @if ($articleBody !== '')
                    <div class="adh-article-body-card rounded-[var(--adh-radius)] border border-adh-border/70 bg-white px-4 py-5 dark:border-gray-700 dark:bg-adh-blue md:px-8 md:py-8">
                        @if ($bodySource === 'summary')
                            <div class="mb-6 rounded border border-adh-red/20 bg-adh-red/5 px-4 py-3 text-sm leading-6 text-adh-text dark:border-red-500/20 dark:bg-red-500/5 dark:text-gray-200">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Haber Özeti') }}</p>
                                <p class="mt-2">{{ __('Bu yayında ana metin yerine doğrulanmış özet akış gösterilmektedir.') }}</p>
                            </div>
                        @endif

                        <div class="prose prose-slate max-w-none dark:prose-invert prose-headings:font-serif prose-headings:text-adh-text dark:prose-headings:text-gray-100 prose-p:text-[1.06rem] prose-p:leading-8 md:prose-p:text-[1.13rem]">
                            {!! $articleBody !!}
                        </div>
                    </div>
                @else
                    <div class="rounded-[var(--adh-radius)] border border-dashed border-adh-border bg-slate-50 px-5 py-6 text-sm leading-7 text-adh-gray dark:border-gray-700 dark:bg-adh-navy/30 dark:text-gray-300">
                        {{ __('Bu haber için ayrıntılı içerik henüz paylaşılmadı.') }}
                    </div>
                @endif

                <div class="mt-8 rounded-[var(--adh-radius)] border border-adh-border/80 bg-slate-50/80 p-5 dark:border-gray-700 dark:bg-adh-navy/30">
                    <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Haber Künyesi') }}</p>
                            <div class="mt-3">
                                <x-news-meta-row :article="$article" :show-source="true" :show-views="true" />
                            </div>
                        </div>

                        <div class="md:max-w-xs">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Bu Haberi Paylaş') }}</p>
                            <div class="mt-3">
                                <x-share-buttons :title="$article->title" />
                            </div>
                        </div>
                    </div>

                    @if($article->tags->isNotEmpty())
                        <div class="mt-5 border-t border-adh-border pt-5 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-adh-gray dark:text-gray-400">{{ __('Etiketler') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($article->tags as $tag)
                                    <a href="{{ $localizedPath('etiket/' . $tag->slug) }}" class="rounded-full border border-adh-border px-3 py-1 text-xs font-medium transition hover:border-adh-red hover:text-adh-red dark:border-gray-700 dark:text-gray-200 dark:hover:border-red-400 dark:hover:text-red-300">#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <x-ad-slot position="article-bottom" class="mt-8" />
            </div>

            @if ($related->isNotEmpty())
                <div class="mx-auto mt-10 max-w-5xl">
                    <section class="rounded-[var(--adh-radius)] border border-adh-border bg-white p-5 dark:border-gray-700 dark:bg-adh-blue">
                        <x-section-heading
                            :title="__('İlgili Haberler')"
                            :subtitle="__('Aynı gündem hattından devam okunacak başlıklar.')"
                            eyebrow="{{ __('Devam Et') }}"
                        />
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($related as $item)
                                <x-news-card :article="$item" />
                            @endforeach
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </article>
@endsection
