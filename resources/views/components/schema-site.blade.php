@php
    $branding = \App\Support\SiteBranding::current();
    $siteName = $branding['site_name'] ?? config('app.name');
    $baseUrl = \App\Support\SeoUrls::canonicalBaseUrl();
    $socialLinks = json_decode(\App\Models\Setting::get('social', 'links', '[]'), true) ?: [];
    $sameAs = collect($socialLinks)
        ->pluck('url')
        ->filter(fn ($url) => is_string($url) && str_starts_with($url, 'http'))
        ->values()
        ->all();
    $contactEmail = \App\Models\Setting::get('general', 'contact_email');
    $contactPhone = \App\Models\Setting::get('general', 'contact_phone');

    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => $baseUrl,
        'logo' => \App\Support\SeoUrls::absolute($branding['logo_light_url'] ?? '/images/branding/adh-logo-light.svg'),
        'sameAs' => $sameAs,
        'areaServed' => [
            '@type' => 'AdministrativeArea',
            'name' => 'Adıyaman',
        ],
        'knowsAbout' => [
            'Adıyaman haberleri',
            'Adıyaman son dakika',
            'Adıyaman asayiş',
            'Adıyaman ilçe haberleri',
        ],
    ];

    if (filled($contactEmail) || filled($contactPhone)) {
        $organization['contactPoint'] = array_filter([
            '@type' => 'ContactPoint',
            'contactType' => 'newsroom',
            'areaServed' => 'TR',
            'availableLanguage' => ['Turkish'],
            'email' => $contactEmail ?: null,
            'telephone' => $contactPhone ?: null,
        ]);
    }

    $schema = [
        $organization,
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $baseUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => \App\Support\SeoUrls::absolute('/arama') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
