<?php

namespace App\Support;

final class AdvertisementPlacement
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function metadata(): array
    {
        return [
            'header' => [
                'desktop' => '1200x150 veya 970x90',
                'mobile' => '720x220 veya 640x200',
                'aspect_ratio' => '8 / 1',
                'mobile_aspect_ratio' => '3.3 / 1',
                'max_height' => '180px',
                'mobile_max_height' => '150px',
                'purpose' => 'Header altında premium yatay görünürlük.',
            ],
            'sidebar-top' => [
                'desktop' => '520x320, 336x280 veya 300x250',
                'mobile' => '720x260 veya 640x240',
                'aspect_ratio' => '1.25 / 1',
                'mobile_aspect_ratio' => '2.8 / 1',
                'max_height' => '360px',
                'mobile_max_height' => '260px',
                'purpose' => 'Sağ blokta yerel işletme ve sponsorluk alanı.',
            ],
            'sidebar-bottom' => [
                'desktop' => '520x320, 336x280 veya 300x250',
                'mobile' => '720x260 veya 640x240',
                'aspect_ratio' => '1.25 / 1',
                'mobile_aspect_ratio' => '2.8 / 1',
                'max_height' => '360px',
                'mobile_max_height' => '260px',
                'purpose' => 'Sağ blokta ikincil kampanya veya devam reklamı.',
            ],
            'article-top' => [
                'desktop' => '900x150 veya 728x90',
                'mobile' => '720x220 veya 640x200',
                'aspect_ratio' => '6 / 1',
                'mobile_aspect_ratio' => '3.3 / 1',
                'max_height' => '180px',
                'mobile_max_height' => '150px',
                'purpose' => 'Haber detayında metin öncesi yüksek niyetli trafik.',
            ],
            'article-bottom' => [
                'desktop' => '900x150 veya 728x90',
                'mobile' => '720x220 veya 640x200',
                'aspect_ratio' => '6 / 1',
                'mobile_aspect_ratio' => '3.3 / 1',
                'max_height' => '180px',
                'mobile_max_height' => '150px',
                'purpose' => 'Haber okuması sonrası teklif veya CTA alanı.',
            ],
            'footer' => [
                'desktop' => '1200x140 veya 970x90',
                'mobile' => '720x200 veya 640x180',
                'aspect_ratio' => '8 / 1',
                'mobile_aspect_ratio' => '3.6 / 1',
                'max_height' => '170px',
                'mobile_max_height' => '140px',
                'purpose' => 'Footer öncesi kurumsal alt sponsorluk.',
            ],
            'between-news' => [
                'desktop' => '1200x180 veya 970x250',
                'mobile' => '720x260 veya 640x240',
                'aspect_ratio' => '5 / 1',
                'mobile_aspect_ratio' => '2.8 / 1',
                'max_height' => '260px',
                'mobile_max_height' => '220px',
                'purpose' => 'Ana sayfa haber akışı içinde geniş kampanya alanı.',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function placementMeta(string $position): array
    {
        return self::metadata()[$position] ?? [
            'desktop' => 'Responsive banner',
            'mobile' => 'Opsiyonel mobil kreatif',
            'aspect_ratio' => '5 / 1',
            'mobile_aspect_ratio' => '2.8 / 1',
            'max_height' => '260px',
            'mobile_max_height' => '220px',
            'purpose' => 'Genel reklam alanı.',
        ];
    }

    public static function guidance(string $position): string
    {
        $meta = self::placementMeta($position);

        return sprintf(
            '%s Desktop önerisi: %s. Mobil önerisi: %s. Public sınır: desktop %s, mobil %s. Oran: desktop %s, mobil %s.',
            $meta['purpose'],
            $meta['desktop'],
            $meta['mobile'],
            $meta['max_height'],
            $meta['mobile_max_height'],
            $meta['aspect_ratio'],
            $meta['mobile_aspect_ratio'],
        );
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'header' => 'Header',
            'sidebar-top' => 'Sidebar Üst',
            'sidebar-bottom' => 'Sidebar Alt',
            'article-top' => 'Haber Üstü',
            'article-bottom' => 'Haber Altı',
            'footer' => 'Footer',
            'between-news' => 'Haberler Arası',
        ];
    }

    /**
     * Positions rendered by the homepage ads module itself.
     *
     * Header/footer/article slots are rendered by global/detail templates and
     * should not force the homepage "Sponsorlu Alanlar" module to appear.
     *
     * @return array<int, string>
     */
    public static function homeModulePositions(): array
    {
        return [
            'between-news',
            'sidebar-top',
            'sidebar-bottom',
        ];
    }
}
