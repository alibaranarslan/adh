<?php

namespace App\Support;

use Illuminate\Support\Arr;

class LocalizedUrl
{
    public const DEFAULT_LOCALE = 'tr';

    public const SUPPORTED_LOCALES = ['tr', 'en', 'ku'];

    private const STATIC_PAGE_PATHS = [
        'hakkimizda' => 'hakkimizda',
        'gizlilik-politikasi' => 'gizlilik-politikasi',
        'kvkk' => 'kvkk',
        'kvkk-aydinlatma' => 'kvkk',
        'cerez-politikasi' => 'cerez-politikasi',
    ];

    public static function to(string $path = '', ?string $locale = null, array $query = []): string
    {
        $url = url(self::path($path, $locale));

        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }

    public static function path(string $path = '', ?string $locale = null): string
    {
        $locale = self::normalizeLocale($locale);
        $path = self::stripLocale($path);

        $localizedPath = $locale === self::DEFAULT_LOCALE
            ? $path
            : trim($locale . '/' . $path, '/');

        return $localizedPath === '' ? '/' : '/' . $localizedPath;
    }

    public static function route(string $name, array|string|int|null $parameters = [], ?string $locale = null, array $query = []): string
    {
        $parameters = self::normalizeParameters($parameters);
        $path = self::pathForRoute($name, $parameters);

        if ($path === null) {
            return route($name, $parameters);
        }

        return self::to($path, $locale, $query);
    }

    public static function current(?string $locale = null, array $query = []): string
    {
        return self::to(request()->path(), $locale, $query);
    }

    public static function stripLocale(string $path): string
    {
        $path = trim($path, '/');

        return trim((string) preg_replace('#^(tr|en|ku)(/|$)#', '', $path), '/');
    }

    public static function normalizeLocale(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return in_array($locale, self::SUPPORTED_LOCALES, true)
            ? $locale
            : self::DEFAULT_LOCALE;
    }

    public static function articleHasLocaleContent(mixed $article, string $locale): bool
    {
        if ($locale === self::DEFAULT_LOCALE || ! is_object($article) || ! method_exists($article, 'getTranslation')) {
            return true;
        }

        return trim((string) $article->getTranslation('title', $locale, false)) !== ''
            && trim((string) $article->getTranslation('content', $locale, false)) !== '';
    }

    private static function normalizeParameters(array|string|int|null $parameters): array
    {
        if ($parameters === null) {
            return [];
        }

        if (is_array($parameters)) {
            return Arr::except($parameters, ['locale']);
        }

        return ['slug' => (string) $parameters];
    }

    private static function pathForRoute(string $name, array $parameters): ?string
    {
        $slug = (string) ($parameters['slug'] ?? '');

        return match ($name) {
            'home' => '',
            'search' => 'arama',
            'news.archive' => 'arsiv',
            'contact', 'contact.submit' => 'iletisim',
            'page.about' => 'hakkimizda',
            'page.privacy' => 'gizlilik-politikasi',
            'page.kvkk' => 'kvkk',
            'page.cookies' => 'cerez-politikasi',
            'news.category' => 'kategori/' . $slug,
            'city.index' => 'iller',
            'city.show' => 'il/' . $slug,
            'news.tag' => 'etiket/' . $slug,
            'news.show' => $slug,
            'page.show' => self::pagePath($slug),
            default => null,
        };
    }

    private static function pagePath(string $slug): string
    {
        return self::STATIC_PAGE_PATHS[$slug] ?? 'sayfa/' . $slug;
    }
}
