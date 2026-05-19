<?php

namespace App\Support;

use App\Models\Page;

class PagePublicUrl
{
    private const CANONICAL_PATHS = [
        'iletisim' => '/iletisim',
        'hakkimizda' => '/hakkimizda',
        'yayin-ilkeleri' => '/yayin-ilkeleri',
        'gizlilik-politikasi' => '/gizlilik-politikasi',
        'kvkk-aydinlatma' => '/kvkk',
        'cerez-politikasi' => '/cerez-politikasi',
    ];

    public static function pathForSlug(string $slug): string
    {
        return self::CANONICAL_PATHS[$slug] ?? '/sayfa/' . ltrim($slug, '/');
    }

    public static function path(Page $page): string
    {
        return self::pathForSlug((string) $page->slug);
    }

    public static function url(Page $page): string
    {
        return url(self::path($page));
    }
}
