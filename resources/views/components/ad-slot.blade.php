@props(['position', 'class' => ''])

@php
    $slotMeta = \App\Support\AdvertisementPlacement::placementMeta($position);
    $adsenseClientId = \App\Models\Setting::get('integration', 'adsense_client_id')
        ?: config('services.adsense.client_id');

    $ad = \App\Models\Advertisement::query()
        ->active()
        ->position($position)
        ->orderBy('sort_order')
        ->get()
        ->first(fn (\App\Models\Advertisement $candidate): bool => $candidate->isRenderable($adsenseClientId));

    $imageUrl = $ad?->desktopImageUrl();
    $mobileImageUrl = $ad?->mobileImageUrl();
    $linkUrl = filled($ad?->link_url) ? $ad->link_url : null;
    $pictureClass = 'block w-full overflow-hidden rounded-lg aspect-[var(--adh-ad-mobile-aspect-ratio)] md:aspect-[var(--adh-ad-aspect-ratio)] max-h-[var(--adh-ad-mobile-max-height)] md:max-h-[var(--adh-ad-max-height)]';
    $imageClass = 'h-full w-full rounded-lg object-contain';
@endphp

@if($ad)
<div class="ad-slot {{ $class }}" data-position="{{ $position }}"
     style="--adh-ad-max-height: {{ $slotMeta['max_height'] }}; --adh-ad-mobile-max-height: {{ $slotMeta['mobile_max_height'] }}; --adh-ad-aspect-ratio: {{ $slotMeta['aspect_ratio'] }}; --adh-ad-mobile-aspect-ratio: {{ $slotMeta['mobile_aspect_ratio'] }};"
     x-data
     x-intersect.once="fetch('/api/ad-impression/{{ $ad->id }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'Accept': 'application/json' } })">
    @if($ad->type === \App\Models\Advertisement::TYPE_ADSENSE)
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="{{ $adsenseClientId }}"
             data-ad-slot="{{ $ad->adsense_slot }}"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    @elseif ($ad->type === \App\Models\Advertisement::TYPE_BANNER && $imageUrl)
        @if ($linkUrl)
            <a href="{{ $linkUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               class="block"
               onclick="fetch('/api/ad-click/{{ $ad->id }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'Accept': 'application/json' } })">
                <picture class="{{ $pictureClass }}">
                    @if($mobileImageUrl)
                        <source media="(max-width: 767px)" srcset="{{ $mobileImageUrl }}">
                    @endif
                    <img src="{{ $imageUrl }}"
                         alt="{{ $ad->name }}"
                         loading="lazy"
                         class="{{ $imageClass }}" />
                </picture>
            </a>
        @else
            <picture class="{{ $pictureClass }}">
                @if($mobileImageUrl)
                    <source media="(max-width: 767px)" srcset="{{ $mobileImageUrl }}">
                @endif
                <img src="{{ $imageUrl }}"
                     alt="{{ $ad->name }}"
                     loading="lazy"
                     class="{{ $imageClass }}" />
            </picture>
        @endif
    @endif
</div>
@endif
