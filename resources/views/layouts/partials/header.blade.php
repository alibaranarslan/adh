@php
    $branding = \App\Support\SiteBranding::current();
    $siteName = $branding['site_name'] ?? 'Adıyaman Dijital Haber';
    $siteTagline = $branding['site_tagline'] ?? null;
    $socialProfiles = collect($branding['social_profiles'] ?? []);
    $headerTheme = $activeHeaderTheme ?? null;
@endphp

<header id="adh-site-header" x-data="{ mobileOpen: false, searchOpen: false }" class="border-b-2 border-adh-border dark:border-adh-blue {{ data_get($headerTheme, 'header_class') }}">
    @if (data_get($headerTheme, 'message'))
        <div class="adh-theme-banner">
            <div class="max-w-7xl mx-auto px-4 adh-theme-banner__inner">
                <span>{{ data_get($headerTheme, 'message') }}</span>
                @if (data_get($headerTheme, 'is_preview'))
                    <span class="adh-theme-preview-badge">{{ __('Önizleme') }}</span>
                @endif
            </div>
        </div>
    @endif

    <div class="bg-adh-navy dark:bg-adh-navy text-white">
        <div class="max-w-7xl mx-auto px-4 py-1.5 flex items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-3">
                <a href="{{ route('search') }}"
                   class="flex items-center gap-1.5 hover:text-adh-red-light transition-colors"
                   aria-label="{{ __('Arama') }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span class="hidden sm:inline">{{ __('Ara') }}</span>
                </a>
                <span class="hidden md:inline text-white/30">|</span>
                <a href="{{ route('home') }}"
                   class="hidden md:inline hover:text-adh-red-light transition-colors">{{ __('Günün Haberleri') }}</a>
                <a href="{{ route('home') }}"
                   class="hidden md:inline hover:text-adh-red-light transition-colors">{{ __('Günün Gazetesi') }}</a>
                <a href="{{ route('news.category', ['slug' => 'gundem']) }}"
                   class="hidden md:inline hover:text-adh-red-light transition-colors">{{ __('Gündem') }}</a>
                <a href="{{ route('news.category', ['slug' => 'siyaset']) }}"
                   class="hidden md:inline hover:text-adh-red-light transition-colors">{{ __('Siyaset') }}</a>
                <a href="{{ route('news.category', ['slug' => 'ekonomi']) }}"
                   class="hidden lg:inline hover:text-adh-red-light transition-colors">{{ __('Ekonomi') }}</a>
                <a href="{{ route('news.category', ['slug' => 'spor']) }}"
                   class="hidden lg:inline hover:text-adh-red-light transition-colors">{{ __('Spor') }}</a>
            </div>

            <div class="hidden md:flex items-center gap-2.5">
                @foreach ($socialProfiles as $profile)
                    <a href="{{ $profile['url'] }}" target="_blank" rel="noopener"
                       aria-label="{{ $profile['label'] }}" class="hover:text-adh-red-light transition-colors">
                        @switch($profile['platform'])
                            @case('facebook')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                @break
                            @case('instagram')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                                @break
                            @case('youtube')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                                @break
                            @case('linkedin')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.983 3.5C4.983 4.88 3.862 6 2.483 6S0 4.88 0 3.5 1.12 1 2.483 1 4.983 2.12 4.983 3.5zM.5 8h4v13h-4V8zm7 0h3.83v1.78h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.77 2.65 4.77 6.1V21h-4v-6.3c0-1.5-.03-3.43-2.08-3.43-2.08 0-2.4 1.62-2.4 3.32V21h-4V8z"/>
                                </svg>
                                @break
                            @default
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.742l7.736-8.843L2.139 2.25H8.48l4.265 5.634 5.499-5.634zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                        @endswitch
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-1 sm:gap-2">
                <x-language-switcher />
                <button
                    type="button"
                    class="hidden sm:inline-flex items-center justify-center gap-1 rounded border border-white/30 px-2 py-1 text-xs"
                    @click="$store.darkMode.toggle()"
                    :aria-label="$store.darkMode.on ? '{{ __('Açık Mod') }}' : '{{ __('Koyu Mod') }}'"
                    :title="$store.darkMode.on ? '{{ __('Açık Mod') }}' : '{{ __('Koyu Mod') }}'"
                >
                    <svg x-show="!$store.darkMode.on" class="h-3.5 w-3.5 md:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9 9 0 1012 21a9 9 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="$store.darkMode.on" x-cloak class="h-3.5 w-3.5 md:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2.25M12 18.75V21M4.97 4.97l1.59 1.59M17.44 17.44l1.59 1.59M3 12h2.25M18.75 12H21M4.97 19.03l1.59-1.59M17.44 6.56l1.59-1.59M12 7.5A4.5 4.5 0 1112 16.5 4.5 4.5 0 0112 7.5z"/>
                    </svg>
                    <span class="sr-only md:not-sr-only" x-show="!$store.darkMode.on">{{ __('Koyu Mod') }}</span>
                    <span class="sr-only md:not-sr-only" x-show="$store.darkMode.on" x-cloak>{{ __('Açık Mod') }}</span>
                </button>
                <button class="md:hidden ml-0.5 p-1 rounded hover:bg-white/10"
                        @click="mobileOpen = !mobileOpen"
                        aria-label="{{ __('Menü') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              x-show="!mobileOpen" d="M4 6h16M4 12h16M4 18h16"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              x-show="mobileOpen" d="M6 18L18 6M6 6l12 12" style="display:none"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="adh-masthead" class="border-b border-adh-border bg-white dark:border-adh-blue dark:bg-adh-blue">
        <div class="max-w-7xl mx-auto px-4 py-4 md:py-6">
            <div class="relative grid grid-cols-3 items-center gap-3 overflow-hidden rounded-sm sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:gap-6">
                @if (data_get($headerTheme, 'visual_markup'))
                    <div class="adh-theme-visual" aria-hidden="true">
                        {!! data_get($headerTheme, 'visual_markup') !!}
                    </div>
                @endif

                <div class="hidden sm:block text-left">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-adh-text dark:text-gray-200 leading-snug">
                        {{ now()->locale(app()->getLocale())->translatedFormat('d F Y') }}
                    </p>
                    <p class="mt-1 text-xs text-adh-gray dark:text-gray-400">
                        {{ now()->locale(app()->getLocale())->translatedFormat('l') }}
                    </p>
                    <a href="{{ route('home') }}"
                       class="mt-2 inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-adh-red transition hover:text-red-700">{{ __('Günün Seçkisi') }}</a>
                </div>

                <div class="sm:hidden">
                    <a href="{{ route('search') }}" aria-label="{{ __('Arama') }}"
                       class="p-1.5 inline-block text-adh-text dark:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </a>
                </div>

                <div class="col-span-1 text-center">
                    <a href="{{ route('home') }}" class="inline-block" aria-label="{{ $siteName }}">
                        @if (($branding['has_custom_light_logo'] ?? false) || ($branding['has_custom_dark_logo'] ?? false))
                            <div class="mb-3 flex justify-center">
                                <img src="{{ $branding['logo_light_url'] }}" alt="{{ $siteName }}" class="h-10 w-auto dark:hidden">
                                <img src="{{ $branding['logo_dark_url'] }}" alt="{{ $siteName }}" class="hidden h-10 w-auto dark:block">
                            </div>
                        @endif
                        <p class="font-serif font-bold tracking-[0.08em] text-adh-navy dark:text-white
                                  text-[1.75rem] sm:text-3xl md:text-[2.7rem] lg:text-[3rem] leading-[0.94] uppercase text-balance">
                            {{ $siteName }}
                        </p>
                        <div class="relative my-2">
                            <div class="h-px bg-adh-red w-full"></div>
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-2 h-2 bg-adh-red rounded-sm"></div>
                        </div>
                        @if ($siteTagline)
                            <p class="mx-auto max-w-md text-[11px] sm:text-xs text-adh-gray dark:text-gray-300 tracking-[0.12em] uppercase">
                                {{ $siteTagline }}
                            </p>
                        @endif
                    </a>
                </div>

                <div class="hidden sm:block text-right">
                    <div class="inline-flex min-w-[10rem] flex-col items-end gap-1 rounded-sm border border-adh-border/80 bg-slate-50/80 px-4 py-3 dark:border-gray-700 dark:bg-adh-navy/40">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-adh-gray dark:text-gray-400">{{ __('Adıyaman Havası') }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ $weather['icon'] ?? '🌤️' }}</span>
                            <span class="font-bold text-2xl text-adh-text dark:text-gray-100 leading-none">{!! e($weather['temp'] ?? '--') !!}&deg;</span>
                        </div>
                        <span class="text-xs text-adh-gray dark:text-gray-400">{{ $weather['description'] ?? __('Güncel durum') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="mobileOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
        class="bg-white dark:bg-adh-blue border-t border-adh-border dark:border-adh-blue md:hidden"
         style="display: none;">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col gap-1">
            <button
                type="button"
                class="mb-2 inline-flex items-center justify-center gap-2 rounded border border-adh-border bg-adh-gray-light px-3 py-2 text-sm font-medium text-adh-text dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                @click="$store.darkMode.toggle()"
            >
                <span x-show="!$store.darkMode.on">{{ __('Koyu Mod') }}</span>
                <span x-show="$store.darkMode.on" x-cloak>{{ __('Açık Mod') }}</span>
            </button>
            <a href="{{ route('home') }}"
               class="py-2 px-2 text-sm font-medium border-b border-adh-border dark:border-gray-700 dark:text-gray-100
                      {{ request()->routeIs('home') ? 'text-adh-red' : 'text-adh-text' }}">
                {{ __('Anasayfa') }}
            </a>
            @foreach ($navCategories ?? collect() as $item)
                <a href="{{ route('news.category', ['slug' => data_get($item, 'slug')]) }}"
                   class="py-2 px-2 text-sm border-b border-adh-border dark:border-gray-700 dark:text-gray-100
                          {{ request()->route('slug') === data_get($item, 'slug') ? 'text-adh-red font-semibold' : 'text-adh-text hover:text-adh-red' }}">
                    {{ data_get($item, 'name') }}
                </a>
            @endforeach
        </div>
    </div>
</header>
