@props(['article'])
@php
    $story = \App\Support\NewsPresenter::present($article);
    $schemaImage = $story['has_image'] ? $story['image_url'] : null;
@endphp
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "NewsArticle",
    "headline": @json($article->getTranslation('title', app()->getLocale())),
    "description": @json(\Illuminate\Support\Str::limit(strip_tags($article->getTranslation('summary', app()->getLocale())), 200)),
    "image": @json($schemaImage),
    "datePublished": @json($article->published_at?->toIso8601String()),
    "dateModified": @json($article->updated_at?->toIso8601String()),
    "author": {
        "@type": "Organization",
        "name": "Adıyaman Dijital Haber"
    },
    "publisher": {
        "@type": "Organization",
        "name": "Adıyaman Dijital Haber",
        "logo": {
            "@type": "ImageObject",
            "url": @json(asset('images/branding/adh-logo-light.svg'))
        }
    },
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": @json(url()->current())
    },
    "articleSection": @json($article->category?->getTranslation('name', app()->getLocale()))
}
</script>
