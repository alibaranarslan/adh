@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'canonical' => null,
    'noindex' => false,
    'article' => null,
])

@php
    $siteSettings = \Illuminate\Support\Facades\Cache::remember('site_settings_' . app()->getLocale(), 3600, fn () => \App\Models\Setting::query()
        ->pluck('value', 'key'));
    $branding = \App\Support\SiteBranding::current();
    $siteName = $branding['site_name'] ?? config('app.name');
    $defaultDescription = $siteSettings->get('meta_description', '');
    $defaultImage = $siteSettings->get('og_image', '');

    $metaTitle = $title
        ? (str_contains((string) $title, (string) $siteName) ? (string) $title : $title . ' | ' . $siteName)
        : $siteName;
    $metaDescription = $description ?? $defaultDescription;
    $metaImage = $image ?? $defaultImage;
    $metaImageUrl = $metaImage ? (str_starts_with($metaImage, 'http') ? $metaImage : asset($metaImage)) : null;
    $canonicalUrl = $canonical ?? \App\Support\LocalizedUrl::current(app()->getLocale());
    $locale = app()->getLocale() === 'tr' ? 'tr_TR' : (app()->getLocale() === 'en' ? 'en_US' : 'ku_TR');
@endphp

<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 160) }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

@if($noindex)
<meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 200) }}">
@if($metaImageUrl ?? false)
<meta property="og:image" content="{{ $metaImageUrl }}">
@endif
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $locale }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 200) }}">
@if($metaImageUrl)
<meta name="twitter:image" content="{{ $metaImageUrl }}">
@endif

@php
    $defaultLocale = \App\Support\LocalizedUrl::DEFAULT_LOCALE;
    $localizedHref = fn (string $lang): string => \App\Support\LocalizedUrl::current($lang);
@endphp
@foreach (['tr', 'en', 'ku'] as $lang)
@continue($article && ! \App\Support\LocalizedUrl::articleHasLocaleContent($article, $lang))
<link rel="alternate" hreflang="{{ $lang }}" href="{{ $localizedHref($lang) }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $localizedHref($defaultLocale) }}">
