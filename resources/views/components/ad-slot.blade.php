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

    $houseAdsEnabled = filter_var(
        \App\Models\Setting::get('advertising', 'house_ads_enabled', '1'),
        FILTER_VALIDATE_BOOL,
        FILTER_NULL_ON_FAILURE
    ) ?? true;
    $canShowHouseAd = $houseAdsEnabled && in_array($position, \App\Support\AdvertisementPlacement::houseAdPositions(), true);
    $contactPhone = trim((string) \App\Models\Setting::get('general', 'contact_phone', ''));
    $contactEmail = trim((string) \App\Models\Setting::get('general', 'contact_email', ''));
    $contactUrl = filled($contactPhone)
        ? 'tel:'.preg_replace('/\s+/', '', $contactPhone)
        : (filled($contactEmail) ? 'mailto:'.$contactEmail : \App\Support\LocalizedUrl::route('contact'));
    $contactLabel = filled($contactPhone)
        ? __('İletişim: :value', ['value' => $contactPhone])
        : (filled($contactEmail) ? __('İletişim: :value', ['value' => $contactEmail]) : __('İletişim sayfası'));
    $positionLabel = __( \App\Support\AdvertisementPlacement::options()[$position] ?? 'Reklam Alanı' );
    $isHomeInventory = in_array($position, ['home-top', 'home-feed', 'home-lower', 'between-news'], true);
    $houseAdHeadline = $isHomeInventory
        ? __('Bu alanda markanızı öne çıkarın')
        : __('Buraya reklam verebilirsiniz');
    $houseAdCopy = $isHomeInventory
        ? __('Ana sayfa haber akışı içinde görünür, ölçülebilir ve profesyonel reklam alanı.')
        : __('Adıyaman Dijital Haber’de işletmenizi görünür kılın.');
@endphp

@if($ad)
<div class="ad-slot {{ $class }}" data-position="{{ $position }}"
     data-impression-url="{{ route('ad.impression', ['ad' => $ad->id]) }}"
     style="--adh-ad-max-height: {{ $slotMeta['max_height'] }}; --adh-ad-mobile-max-height: {{ $slotMeta['mobile_max_height'] }}; --adh-ad-aspect-ratio: {{ $slotMeta['aspect_ratio'] }}; --adh-ad-mobile-aspect-ratio: {{ $slotMeta['mobile_aspect_ratio'] }};"
>
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

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.content;
            const track = (element) => {
                if (element.dataset.impressionTracked === 'true') {
                    return;
                }

                element.dataset.impressionTracked = 'true';
                const headers = { 'Accept': 'application/json' };

                if (csrfToken) {
                    headers['X-CSRF-TOKEN'] = csrfToken;
                }

                fetch(element.dataset.impressionUrl, {
                    method: 'POST',
                    headers,
                    keepalive: true,
                }).catch(() => {});
            };

            const slots = document.querySelectorAll('.ad-slot[data-impression-url]');

            if (! ('IntersectionObserver' in window)) {
                slots.forEach(track);
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (! entry.isIntersecting) {
                        return;
                    }

                    track(entry.target);
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.2 });

            slots.forEach((slot) => observer.observe(slot));
        });
    </script>
@endonce
@elseif($canShowHouseAd)
    <aside
        class="adh-house-ad {{ $class }}"
        data-position="{{ $position }}"
        aria-label="{{ __('Reklam alanı') }}"
    >
        <div class="adh-house-ad__inner">
            <span class="adh-house-ad__label">{{ $positionLabel }}</span>
            <div class="adh-house-ad__copy">
                <strong>{{ $houseAdHeadline }}</strong>
                <span>{{ $houseAdCopy }}</span>
            </div>
            <a href="{{ $contactUrl }}" class="adh-house-ad__cta">
                {{ $contactLabel }}
            </a>
        </div>
    </aside>
@endif
