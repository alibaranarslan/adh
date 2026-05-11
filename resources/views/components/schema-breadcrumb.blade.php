@props(['items'])
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        @foreach ($items as $i => $item)
        {
            "@type": "ListItem",
            "position": {{ $i + 1 }},
            "name": @json($item['name'] ?? $item['label'] ?? ''),
            "item": @json($item['url'] ?? request()->url())
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
