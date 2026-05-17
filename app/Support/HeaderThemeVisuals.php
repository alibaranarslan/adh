<?php

namespace App\Support;

use App\Models\HeaderTheme;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HeaderThemeVisuals
{
    private const MAX_EVENT_MESSAGE_LENGTH = 90;

    private static ?array $assetManifest = null;

    public static function present(HeaderTheme $theme, string $locale, bool $isPreview = false): array
    {
        $message = self::eventMessage($theme, $locale);
        $showFlag = (bool) $theme->show_flag && ! HeaderTheme::isHolidaySlug($theme->slug);
        $showAtaturk = HeaderTheme::allowsAtaturkForSlug($theme->slug) && (bool) $theme->show_ataturk;

        return [
            'id' => $theme->slug,
            'is_active' => true,
            'is_preview' => $isPreview,
            'style_variant' => $theme->style_variant ?: self::defaultStyleVariant($theme),
            'tone' => self::tone($theme),
            'header_class' => self::headerClass($theme),
            'event_label' => self::eventLabel($theme, $locale),
            'event_badge_markup' => self::eventBadgeMarkup($theme),
            'banner_message' => $message,
            'message' => $message,
            'banner_layout' => 'editorial_event_strip',
            'illustration_mode' => $theme->illustration_mode ?: 'preset_asset',
            'illustration_asset' => $theme->illustration_asset,
            'visual' => null,
            'visual_markup' => null,
            'show_flag' => $showFlag,
            'show_ataturk' => $showAtaturk,
            'decor_intensity' => $theme->decor_intensity ?: 'medium',
            'date_range' => [
                'starts_at' => optional($theme->starts_at)?->toDateString(),
                'ends_at' => optional($theme->ends_at)?->toDateString(),
            ],
            'preview_state' => $isPreview ? 'preview' : 'live',
            'flags' => [
                'show_flag' => $showFlag,
                'show_ataturk' => $showAtaturk,
            ],
        ];
    }

    public static function headerClass(HeaderTheme $theme): string
    {
        return trim(sprintf(
            'adh-header-theme adh-theme-%s adh-tone-%s decor-%s',
            $theme->slug,
            self::tone($theme),
            $theme->decor_intensity ?: 'medium',
        ));
    }

    public static function assetDefinition(string $key): ?array
    {
        $manifest = self::assetManifest();

        return $manifest[$key] ?? null;
    }

    public static function assetManifest(): array
    {
        if (self::$assetManifest !== null) {
            return self::$assetManifest;
        }

        $path = base_path('resources/data/header-theme-assets.json');

        if (! is_file($path)) {
            return self::$assetManifest = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return self::$assetManifest = [];
        }

        return self::$assetManifest = collect($decoded)
            ->filter(fn ($asset): bool => is_array($asset) && filled($asset['key'] ?? null))
            ->keyBy('key')
            ->all();
    }

    public static function resetAssetManifestCache(): void
    {
        self::$assetManifest = null;
    }

    private static function tone(HeaderTheme $theme): string
    {
        return match ($theme->slug) {
            '10-kasim' => 'commemoration',
            'ramazan-bayrami', 'kurban-bayrami' => 'bayram',
            default => 'national',
        };
    }

    private static function defaultStyleVariant(HeaderTheme $theme): string
    {
        return match (self::tone($theme)) {
            'commemoration' => 'commemoration',
            'bayram' => 'bayram',
            default => 'national',
        };
    }

    private static function eventLabel(HeaderTheme $theme, string $locale): string
    {
        return match ($theme->slug) {
            '23-nisan' => '23 Nisan',
            '19-mayis' => '19 Mayıs',
            '30-agustos' => '30 Ağustos',
            '29-ekim' => '29 Ekim',
            '10-kasim' => '10 Kasım',
            'ramazan-bayrami' => 'Ramazan Bayramı',
            'kurban-bayrami' => 'Kurban Bayramı',
            default => $theme->translatedName($locale),
        };
    }

    private static function eventMessage(HeaderTheme $theme, string $locale): string
    {
        $message = trim($theme->translatedBannerMessage($locale));

        return Str::limit($message, self::MAX_EVENT_MESSAGE_LENGTH, '');
    }

    private static function eventBadgeMarkup(HeaderTheme $theme): ?string
    {
        return match ($theme->slug) {
            '23-nisan' => self::sealMarkup('turkish-flag-official', 'adh-event-seal--flag adh-event-seal--children', 'adh-event-badge__img adh-event-badge__img--flag', 'Türk bayrağı'),
            '19-mayis' => self::sealMarkup('ataturk-portrait-cutout', 'adh-event-seal--portrait adh-event-seal--youth', 'adh-event-badge__img adh-event-badge__img--portrait', 'Mustafa Kemal Atatürk'),
            '30-agustos' => self::sealMarkup('turkish-flag-official', 'adh-event-seal--flag adh-event-seal--victory', 'adh-event-badge__img adh-event-badge__img--flag', 'Türk bayrağı'),
            '29-ekim' => self::sealMarkup('turkish-flag-official', 'adh-event-seal--flag adh-event-seal--republic', 'adh-event-badge__img adh-event-badge__img--flag', 'Türk bayrağı'),
            '10-kasim' => self::sealMarkup('ataturk-portrait-cutout', 'adh-event-seal--portrait adh-event-seal--remembrance', 'adh-event-badge__img adh-event-badge__img--portrait adh-event-badge__img--mono', 'Mustafa Kemal Atatürk'),
            'ramazan-bayrami' => self::sealMarkup('bayram-crescent', 'adh-event-seal--crescent adh-event-seal--ramazan', 'adh-event-badge__img adh-event-badge__img--crescent', 'Bayram hilali'),
            'kurban-bayrami' => self::sealMarkup('bayram-crescent', 'adh-event-seal--crescent adh-event-seal--kurban', 'adh-event-badge__img adh-event-badge__img--crescent', 'Bayram hilali'),
            default => null,
        };
    }

    private static function sealMarkup(string $key, string $sealModifiers, string $imageClass, string $alt): ?string
    {
        $asset = self::assetDefinition($key);
        $path = trim((string) data_get($asset, 'local_path'));

        if ($path === '' || ! is_file(base_path($path))) {
            return null;
        }

        return sprintf(
            '<span class="adh-event-seal %s">%s<span class="adh-event-seal__accent"></span></span>',
            e($sealModifiers),
            self::imageMarkup(asset(Str::after($path, 'public/')), $imageClass, $alt),
        );
    }

    private static function imageMarkup(string $url, string $class, string $alt): string
    {
        return sprintf(
            '<img src="%s" alt="%s" class="%s" loading="eager" decoding="async">',
            e($url),
            e($alt),
            e($class),
        );
    }

    private static function uploadedAssetUrl(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return url($path);
        }

        if (Str::startsWith($path, ['images/', 'storage/'])) {
            return asset($path);
        }

        return Storage::disk(config('filament.default_filesystem_disk', 'public'))->url($path);
    }
}
