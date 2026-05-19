<?php

namespace App\Support;

class SeoUrls
{
    public static function canonicalBaseUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        if ($baseUrl === '') {
            $baseUrl = rtrim(url('/'), '/');
        }

        if (app()->environment('production') && str_starts_with($baseUrl, 'http://')) {
            $baseUrl = 'https://' . substr($baseUrl, 7);
        }

        return $baseUrl;
    }

    public static function absolute(string $pathOrUrl): string
    {
        if (preg_match('#^https?://#i', $pathOrUrl) === 1) {
            if (app()->environment('production') && str_starts_with($pathOrUrl, 'http://')) {
                return 'https://' . substr($pathOrUrl, 7);
            }

            return $pathOrUrl;
        }

        return self::canonicalBaseUrl() . '/' . ltrim($pathOrUrl, '/');
    }
}
