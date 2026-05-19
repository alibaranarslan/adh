@props(['article'])
@php
    $story = \App\Support\NewsPresenter::present($article);
    $siteName = \App\Support\SiteBranding::current()['site_name'] ?? config('app.name');
    $description = trim((string) $article->getTranslation('meta_description', app()->getLocale(), false))
        ?: trim((string) $article->getTranslation('summary', app()->getLocale(), false))
        ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->getTranslation('content', app()->getLocale(), false)), 200);

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $article->getTranslation('title', app()->getLocale()),
        'description' => \Illuminate\Support\Str::limit(strip_tags($description), 200),
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->updated_at?->toIso8601String(),
        'author' => [
            '@type' => 'Organization',
            'name' => $article->author?->name ?: $siteName,
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $siteName,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => \App\Support\SeoUrls::absolute('/images/branding/adh-logo-light.svg'),
            ],
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => url()->current(),
        ],
        'articleSection' => $article->category?->getTranslation('name', app()->getLocale()),
    ];

    if ($story['has_image']) {
        $imageUrl = \App\Support\SeoUrls::absolute($story['image_url']);
        $schema['image'] = [$imageUrl];
        $schema['thumbnailUrl'] = $imageUrl;
    }
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
