<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteBranding
{
    public static function current(): array
    {
        $siteName = self::resolveLocalizedText(
            Setting::get('general', 'site_name', ''),
            __('Adıyaman Dijital Haber'),
        );

        if ($siteName === '' || Str::lower($siteName) === 'laravel') {
            $siteName = __('Adıyaman Dijital Haber');
        }

        $siteTagline = self::resolveLocalizedText(
            Setting::get('general', 'site_tagline', ''),
            __('Adıyaman’ın güncel ve güvenilir haber kaynağı.'),
        );
        $customLightLogo = trim((string) Setting::get('general', 'logo_path', ''));
        $customDarkLogo = trim((string) Setting::get('general', 'dark_logo_path', ''));
        $socialLinks = self::socialLinks();

        return [
            'site_name' => $siteName,
            'site_tagline' => $siteTagline,
            'footer_description' => $siteTagline !== ''
                ? $siteTagline
                : __('Adıyaman’ın güvenilir dijital haber kaynağı. Yerel gelişmeleri, gündem haberlerini ve kültürel etkinlikleri takip edin.'),
            'logo_light_url' => self::resolveMediaUrl(
                $customLightLogo,
                'images/branding/adh-logo-light.svg',
            ),
            'logo_dark_url' => self::resolveMediaUrl(
                $customDarkLogo !== '' ? $customDarkLogo : $customLightLogo,
                'images/branding/adh-logo-dark.svg',
            ),
            'has_custom_light_logo' => $customLightLogo !== '',
            'has_custom_dark_logo' => $customDarkLogo !== '',
            'favicon_url' => self::resolveMediaUrl(
                Setting::get('general', 'favicon_path', ''),
                'images/branding/favicon.svg',
            ),
            'social_links' => $socialLinks,
            'social_profiles' => collect($socialLinks)
                ->map(fn (string $url, string $platform) => [
                    'platform' => $platform,
                    'label' => self::socialLabel($platform),
                    'url' => $url,
                ])
                ->filter(fn (array $profile) => filled($profile['url']))
                ->values()
                ->all(),
        ];
    }

    private static function socialLinks(): array
    {
        $defaults = [
            'twitter' => 'https://twitter.com/adiyamanhaber',
            'facebook' => 'https://facebook.com/adiyamanhaber',
            'instagram' => 'https://instagram.com/adiyamanhaber',
            'youtube' => 'https://youtube.com/@adiyamanhaber',
            'linkedin' => '',
        ];

        $configured = json_decode((string) Setting::get('social', 'links', '[]'), true) ?? [];

        foreach ($configured as $item) {
            $platform = Str::lower(trim((string) data_get($item, 'platform', '')));
            $url = trim((string) data_get($item, 'url', ''));

            if ($platform === '' || ! array_key_exists($platform, $defaults)) {
                continue;
            }

            $defaults[$platform] = $url;
        }

        return $defaults;
    }

    private static function socialLabel(string $platform): string
    {
        return match ($platform) {
            'twitter' => 'X',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'linkedin' => 'LinkedIn',
            default => Str::headline($platform),
        };
    }

    private static function resolveMediaUrl(?string $value, string $fallback): string
    {
        $path = trim((string) $value);

        if ($path === '') {
            return asset($fallback);
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (Str::startsWith($path, ['/'])) {
            return url($path);
        }

        if (Str::startsWith($path, ['images/', 'storage/', 'favicon'])) {
            return asset($path);
        }

        return Storage::disk(config('filament.default_filesystem_disk', 'public'))->url($path);
    }

    private static function resolveLocalizedText(mixed $value, string $fallback): string
    {
        if (is_array($value)) {
            return self::pickLocaleValue($value, $fallback);
        }

        $text = trim((string) $value);

        if ($text === '') {
            return $fallback;
        }

        $decoded = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return self::pickLocaleValue($decoded, $fallback);
        }

        return $text;
    }

    private static function pickLocaleValue(array $values, string $fallback): string
    {
        $locale = app()->getLocale();

        foreach ([$locale, 'tr', 'en', 'ku'] as $candidate) {
            $value = trim((string) ($values[$candidate] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        foreach ($values as $value) {
            $resolved = trim((string) $value);

            if ($resolved !== '') {
                return $resolved;
            }
        }

        return $fallback;
    }
}
