@extends('layouts.app')

@php
    $moduleMap = collect($layoutSections ?? [])->keyBy('key');
    $heroSection = $moduleMap->get('hero');
    $bodySections = collect($layoutSections ?? [])->reject(fn ($section) => $section['key'] === 'hero')->values();
    $backgroundClasses = [
        'surface' => '',
        'muted' => 'bg-slate-50/80 dark:bg-slate-900/35',
        'contrast' => 'border-y border-adh-border bg-slate-100/80 dark:border-gray-700 dark:bg-slate-950/35',
    ];
    $paddingClasses = [
        'compact' => 'py-2.5 md:py-2',
        'regular' => 'py-3 md:py-2.5',
        'relaxed' => 'py-3.5 md:py-4 lg:py-5',
    ];
    $containerClasses = [
        'content' => 'mx-auto w-full max-w-7xl px-4 sm:px-6',
        'wide' => 'mx-auto w-full max-w-[92rem] px-4 sm:px-6',
        'full' => 'w-full',
    ];
@endphp

@once
    <style>
        .layout-visibility {
            display: none;
        }

        @media (max-width: 639px) {
            .layout-visibility[data-mobile="1"] {
                display: block;
            }
        }

        @media (min-width: 640px) and (max-width: 1023px) {
            .layout-visibility[data-tablet="1"] {
                display: block;
            }
        }

        @media (min-width: 1024px) {
            .layout-visibility[data-desktop="1"] {
                display: block;
            }
        }
    </style>
@endonce

@section('hero')
    @if ($layoutPreviewRevision)
        <div class="mb-5 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-sm dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">{{ __('Önizleme modu aktif') }}</p>
                    <p class="mt-1 text-xs opacity-80">
                        {{ __('Revizyon') }}: {{ $layoutPreviewRevision->name ?? __('Taslak') }}
                        @if($layoutPreviewRevision->published_at)
                            • {{ $layoutPreviewRevision->published_at->format('d.m.Y H:i') }}
                        @endif
                    </p>
                </div>
                <a
                    href="{{ \App\Support\LocalizedUrl::route('home') }}"
                    class="inline-flex items-center rounded-full border border-current px-3 py-1.5 text-xs font-semibold transition hover:bg-amber-900/10 dark:hover:bg-white/10"
                >
                    {{ __('Canlı sayfaya dön') }}
                </a>
            </div>
        </div>
    @endif

    @if ($heroSection)
        @php
            $heroSettings = $heroSection['settings'] ?? [];
            $heroWrapperClasses = trim(($backgroundClasses[data_get($heroSettings, 'background_tone', 'surface')] ?? '').' '.($paddingClasses[data_get($heroSettings, 'padding_scale', 'regular')] ?? 'py-6'));
        @endphp

        <div
            class="layout-visibility {{ $heroWrapperClasses }}"
            data-mobile="{{ data_get($heroSettings, 'show_on_mobile', true) ? 1 : 0 }}"
            data-tablet="{{ data_get($heroSettings, 'show_on_tablet', true) ? 1 : 0 }}"
            data-desktop="{{ data_get($heroSettings, 'show_on_desktop', true) ? 1 : 0 }}"
        >
            @include('home.sections.hero', [
                'settings' => $heroSettings,
                'heroMain' => $heroMain,
                'heroSide' => $heroSide,
            ])
        </div>
    @endif
@endsection

@section('fullwidth')
    <div class="space-y-3 md:space-y-3">
        @if ($showFallbackNotice ?? false)
            @include('home.sections.fallback-notice')
        @endif

        @foreach ($bodySections as $section)
            @php
                $settings = $section['settings'] ?? [];
                $wrapperClasses = trim(($backgroundClasses[data_get($settings, 'background_tone', 'surface')] ?? '').' '.($paddingClasses[data_get($settings, 'padding_scale', 'regular')] ?? 'py-6'));
                $wrapperContainerClass = $containerClasses[data_get($settings, 'container_width', 'content')] ?? $containerClasses['content'];
                $ctaLabel = data_get($settings, 'cta_label.'.app()->getLocale());
                $ctaUrl = data_get($settings, 'cta_url');
                $showSectionCta = data_get($settings, 'cta_enabled', false) && filled($ctaLabel) && filled($ctaUrl);
            @endphp

            <div
                class="layout-visibility {{ $wrapperClasses }}"
                data-mobile="{{ data_get($settings, 'show_on_mobile', true) ? 1 : 0 }}"
                data-tablet="{{ data_get($settings, 'show_on_tablet', true) ? 1 : 0 }}"
                data-desktop="{{ data_get($settings, 'show_on_desktop', true) ? 1 : 0 }}"
            >
                <div class="{{ $wrapperContainerClass }}">
                    @if ($showSectionCta)
                        <div class="mb-3 flex justify-end">
                            <a href="{{ $ctaUrl }}" class="inline-flex items-center rounded-full border border-adh-red/20 px-4 py-2 text-xs font-semibold text-adh-red transition hover:border-adh-red hover:bg-adh-red/5 dark:border-red-400/20 dark:text-red-300 dark:hover:bg-red-400/10">
                                {{ $ctaLabel }}
                            </a>
                        </div>
                    @endif

                    @switch($section['key'])
                        @case('breaking_bar')
                            @include('home.sections.breaking-bar', ['settings' => $settings, 'breakingNews' => $breakingNews])
                            @break

                        @case('local_news')
                            @include('home.sections.local-news', ['settings' => $settings, 'localNews' => $localNews])
                            @break

                        @case('highlights')
                            @include('home.sections.highlights', ['settings' => $settings, 'highlights' => $highlights])
                            @break

                        @case('most_read')
                            @include('home.sections.most-read', ['settings' => $settings, 'mostRead' => $mostRead])
                            @break

                        @case('region_news')
                            @include('home.sections.region-news-v2', ['settings' => $settings, 'regionNews' => $regionNews])
                            @break

                        @case('latest_news')
                            @include('home.sections.latest-news', ['settings' => $settings, 'latestNews' => $latestNews])
                            @break

                        @case('category_shortcuts')
                            @include('home.sections.category-shortcuts', ['settings' => $settings, 'categories' => $categories])
                            @break

                        @case('sidebar_widgets')
                            @include('home.sections.sidebar-widgets', ['settings' => $settings])
                            @break

                        @case('ads')
                            @include('home.sections.ads', ['settings' => $settings, 'ads' => $ads])
                            @break
                    @endswitch
                </div>
            </div>
        @endforeach
    </div>
@endsection
