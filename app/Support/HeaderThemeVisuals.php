<?php

namespace App\Support;

use App\Models\HeaderTheme;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HeaderThemeVisuals
{
    public static function present(HeaderTheme $theme, string $locale, bool $isPreview = false): array
    {
        $message = $theme->translatedBannerMessage($locale);
        $showFlag = (bool) $theme->show_flag;
        $showAtaturk = HeaderTheme::allowsAtaturkForSlug($theme->slug) && (bool) $theme->show_ataturk;

        return [
            'id' => $theme->slug,
            'is_active' => true,
            'is_preview' => $isPreview,
            'style_variant' => $theme->style_variant ?: self::defaultStyleVariant($theme),
            'tone' => self::tone($theme),
            'header_class' => self::headerClass($theme),
            'banner_message' => $message,
            'message' => $message,
            'banner_layout' => 'editorial_ribbon',
            'illustration_mode' => $theme->illustration_mode ?: 'inline_svg',
            'illustration_asset' => $theme->illustration_asset,
            'visual' => self::visualMarkup($theme, $showFlag, $showAtaturk),
            'visual_markup' => self::visualMarkup($theme, $showFlag, $showAtaturk),
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
        return self::tone($theme) === 'commemoration' ? 'commemoration' : (self::tone($theme) === 'bayram' ? 'bayram' : 'national');
    }

    private static function visualMarkup(HeaderTheme $theme, bool $showFlag, bool $showAtaturk): ?string
    {
        $mode = $theme->illustration_mode ?: 'inline_svg';
        $parts = [];

        if ($mode === 'custom_asset' && filled($theme->illustration_asset)) {
            $url = self::assetUrl($theme->illustration_asset);

            if ($url !== '') {
                $parts[] = sprintf('<img src="%s" alt="" class="adh-theme-custom-visual" loading="lazy" decoding="async">', e($url));
            }
        } elseif ($mode !== 'none') {
            $parts[] = self::accentSvg($theme);
        }

        if ($showFlag) {
            $parts[] = '<svg class="adh-theme-flag" viewBox="0 0 160 96" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect width="160" height="96" rx="14" fill="#C62828"/><circle cx="64" cy="48" r="24" fill="#fff"/><circle cx="72" cy="48" r="19" fill="#C62828"/><path d="M103 48l8.4 2.7-5.2 7 8.7-1.5 1.7 8.5 3.9-7.9 7.7 4-4.5-7.4 7.9-3.6-8.7-.7 1.2-8.6-6.6 5.7-6-6.3 1.5 8.5-8.6.1z" fill="#fff"/></svg>';
        }

        if ($showAtaturk) {
            $parts[] = '<svg class="adh-theme-ataturk" viewBox="0 0 180 220" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M118 18c-14 4-31 18-40 37-11 23-15 31-31 42-11 8-18 20-20 35 9-8 15-11 27-11 4 24 19 45 43 57 15 8 31 12 57 14-10-9-13-18-14-29 14-8 23-19 29-33-10 2-18 2-26-2 18-9 28-24 31-46-10 7-18 9-28 9-3-17-11-31-25-45 1-10 0-18-3-28z" fill="currentColor" opacity=".18"/><path d="M74 111c17-11 28-27 33-49 19 18 25 39 21 62-8 12-19 20-33 24-10-7-17-16-21-28z" fill="currentColor" opacity=".32"/></svg>';
        }

        $parts = array_filter($parts);

        return $parts === [] ? null : implode('', $parts);
    }

    private static function accentSvg(HeaderTheme $theme): string
    {
        $asset = $theme->illustration_asset ?: $theme->slug;

        return match ($asset) {
            '10-kasim' => '<svg class="adh-theme-accent adh-theme-accent-solemn" viewBox="0 0 220 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M0 60h220" stroke="currentColor" stroke-width="2" opacity=".25"/><circle cx="64" cy="60" r="42" fill="none" stroke="currentColor" stroke-width="6" opacity=".15"/><path d="M56 60h16M64 52v16" stroke="currentColor" stroke-width="4" opacity=".28"/></svg>',
            'ramazan-bayrami', 'kurban-bayrami', 'hilal' => '<svg class="adh-theme-accent adh-theme-accent-bayram" viewBox="0 0 220 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M86 26a34 34 0 1024 58 38 38 0 11-24-58z" fill="currentColor" opacity=".22"/><circle cx="136" cy="44" r="6" fill="currentColor" opacity=".3"/><path d="M12 96h196" stroke="currentColor" stroke-width="2" opacity=".16"/></svg>',
            default => '<svg class="adh-theme-accent" viewBox="0 0 220 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M0 60h220" stroke="currentColor" stroke-width="2" opacity=".16"/><path d="M46 60a20 20 0 1032 16 23 23 0 11-32-16z" fill="currentColor" opacity=".18"/><path d="M102 42l4 12 13 .1-10.4 7.5 4 12-10.6-7.3-10.6 7.3 4-12L85 54.1l13-.1 4-12z" fill="currentColor" opacity=".24"/></svg>',
        };
    }

    private static function assetUrl(string $path): string
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
