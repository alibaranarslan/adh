<?php

namespace App\Support;

use Illuminate\Support\Str;

class SeoUrls
{
    private const PRODUCTION_BASE_URL = 'https://adiyamandijitalhaber.com.tr';

    public static function canonicalBaseUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        if ($baseUrl === '') {
            $baseUrl = rtrim(url('/'), '/');
        }

        if (app()->environment('production')) {
            if (Str::contains($baseUrl, ['localhost', '127.0.0.1', '::1'])) {
                return self::PRODUCTION_BASE_URL;
            }

            if (str_starts_with($baseUrl, 'http://')) {
                $baseUrl = 'https://' . substr($baseUrl, 7);
            }
        }

        return $baseUrl;
    }

    public static function sanitizeXml(string $xml): string
    {
        return str_replace([
            'http://localhost:8000',
            'https://localhost:8000',
            'http://127.0.0.1:8000',
            'https://127.0.0.1:8000',
            'http://localhost',
            'https://localhost',
            'http://127.0.0.1',
            'https://127.0.0.1',
        ], self::canonicalBaseUrl(), $xml);
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
