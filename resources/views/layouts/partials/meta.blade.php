<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<x-seo-meta
    :title="$metaTitle ?? null"
    :description="$metaDescription ?? null"
    :image="$ogImage ?? null"
    :type="$ogType ?? 'website'"
    :canonical="$canonical ?? null"
    :noindex="$noindex ?? false"
    :article="$article ?? null"
/>
@php
    $branding = \App\Support\SiteBranding::current();
    $faviconUrl = $branding['favicon_url'] ?? asset('images/branding/favicon.svg');
@endphp
<link rel="icon" href="{{ $faviconUrl }}" sizes="any">
<link rel="icon" href="{{ $faviconUrl }}" sizes="32x32">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
@stack('schema')
