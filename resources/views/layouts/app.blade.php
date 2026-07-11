<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data x-init="$store.darkMode.init()">
<head>
    @php
        $layoutAppearance = data_get($layoutState ?? [], 'appearance')
            ?: app(\App\Services\LayoutConfigService::class)->getPublishedState()['appearance'];
        $layoutCssVariables = app(\App\Services\LayoutConfigService::class)->getAppearanceCssVariables($layoutAppearance);
        $sidebarPosition = $layoutAppearance['sidebar_position'] ?? 'right';
    @endphp
    <script>
    (function() {
        var stored = localStorage.getItem('darkMode');
        if (stored === null) {
            @php
                $serverDarkModeDefault = (bool) filter_var(
                    \App\Models\Setting::get('appearance', 'dark_mode_default', false),
                    FILTER_VALIDATE_BOOLEAN
                );
            @endphp
            var serverDefault = @json($serverDarkModeDefault);
            if (serverDefault) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            }
        } else if (stored === 'true') {
            document.documentElement.classList.add('dark');
        }
    })();
    </script>
    @include('layouts.partials.meta')
    @php
        $gaId = trim((string) (\App\Models\Setting::get('integration', 'google_analytics_id')
            ?: \App\Models\Setting::get('seo', 'google_analytics_id')
            ?: config('services.google_analytics.measurement_id')));
        $gscCode = \App\Models\Setting::get('seo', 'google_search_console_code');
    @endphp
    @if($gaId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ e($gaId) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ e($gaId) }}', {
            anonymize_ip: true,
            cookie_flags: 'SameSite=None;Secure'
        });
    </script>
    @endif
    @if($gscCode)
    <meta name="google-site-verification" content="{{ e($gscCode) }}">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,700;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            @foreach($layoutCssVariables as $cssKey => $cssValue)
                {{ $cssKey }}: {{ $cssValue }};
            @endforeach
            --adh-page-bg: var(--adh-surface-bg);
        }

        .dark {
            --adh-page-bg: #1A1A2E;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @php $adsenseId = \App\Models\Setting::get('integration', 'adsense_client_id') ?: config('services.adsense.client_id'); @endphp
    @if($adsenseId)
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $adsenseId }}" crossorigin="anonymous"></script>
    @endif
</head>
<body class="bg-adh-bg dark:bg-adh-navy text-adh-text dark:text-gray-200 antialiased" style="background-color: var(--adh-page-bg); font-family: var(--adh-font-family);">
    <a href="#main-content" class="skip-link">{{ __("Ana içeriğe geç") }}</a>
    @include('layouts.partials.header')
    @include('layouts.partials.breaking-bar')
    @include('layouts.partials.nav')
    <x-ad-slot position="header" class="mx-auto mt-3 w-full max-w-7xl px-4 md:mt-4" />

    <main id="main-content" tabindex="-1" class="mx-auto w-full max-w-full overflow-x-clip px-4 py-0 focus:outline-none md:py-2" style="max-width: var(--adh-content-width);">
        @hasSection('hero')
            @yield('hero')
        @endif

        @hasSection('fullwidth')
            @yield('fullwidth')
        @else
            @if($sidebarPosition === 'none')
                @yield('content')
            @else
                <div class="grid min-w-0 grid-cols-1 gap-6 lg:grid-cols-3">
                    @if($sidebarPosition === 'left')
                        <aside class="min-w-0 lg:col-span-1">
                            @include('layouts.partials.sidebar')
                        </aside>
                    @endif

                    <div class="min-w-0 {{ $sidebarPosition === 'left' ? 'lg:col-span-2' : 'lg:col-span-2' }}">
                        @yield('content')
                    </div>

                    @if($sidebarPosition !== 'left')
                        <aside class="min-w-0 lg:col-span-1">
                            @include('layouts.partials.sidebar')
                        </aside>
                    @endif
                </div>
            @endif
        @endif
    </main>

    <x-ad-slot position="footer" class="mx-auto my-6 w-full max-w-7xl px-4" />
    @include('layouts.partials.footer')
    @include('cookie-consent')
    @livewireScripts
    @stack('scripts')
</body>
</html>
