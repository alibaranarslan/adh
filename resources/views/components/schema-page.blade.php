@props([
    'type' => 'WebPage',
    'name' => null,
    'description' => null,
    'url' => null,
])

@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $type,
        'name' => $name,
        'url' => $url ?: \App\Support\SeoUrls::absolute(request()->path()),
    ];

    if (filled($description)) {
        $schema['description'] = \Illuminate\Support\Str::limit(strip_tags((string) $description), 220);
    }
@endphp

<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
