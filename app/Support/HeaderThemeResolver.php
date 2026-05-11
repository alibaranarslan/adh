<?php

namespace App\Support;

use App\Models\HeaderTheme;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class HeaderThemeResolver
{
    public function resolve(?Request $request = null): ?array
    {
        if (! $this->tableIsReady()) {
            return null;
        }

        $request ??= request();
        $locale = app()->getLocale();
        $previewTheme = $request?->route('headerTheme');
        $previewDate = $previewTheme instanceof HeaderTheme
            ? $this->resolvePreviewDate($request)
            : CarbonImmutable::now(config('app.timezone'))->startOfDay();

        if ($previewTheme instanceof HeaderTheme) {
            return HeaderThemeVisuals::present($previewTheme, $locale, true) + [
                'date_range' => [
                    'preview_date' => $previewDate->toDateString(),
                ],
            ];
        }

        $themes = HeaderTheme::query()
            ->forSite()
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $manualTheme = $themes
            ->first(fn (HeaderTheme $theme) => $theme->is_enabled && $theme->mode === HeaderTheme::MODE_MANUAL_ON);

        if ($manualTheme) {
            return HeaderThemeVisuals::present($manualTheme, $locale);
        }

        $automatic = $themes->first(fn (HeaderTheme $theme) => $theme->is_enabled
            && $theme->mode === HeaderTheme::MODE_AUTOMATIC
            && $theme->matchesDate($previewDate));

        return $automatic ? HeaderThemeVisuals::present($automatic, $locale) : null;
    }

    public function getPreviewUrl(HeaderTheme $theme, string $locale = 'tr', ?string $previewDate = null): string
    {
        $date = $previewDate ?: $theme->previewDate()->toDateString();

        return URL::temporarySignedRoute(
            'header-theme.preview.home',
            now()->addMinutes(30),
            [
                'headerTheme' => $theme->getKey(),
                'locale' => $locale,
                'preview_date' => $date,
            ],
        );
    }

    private function resolvePreviewDate(?Request $request): CarbonImmutable
    {
        $input = trim((string) $request?->query('preview_date', ''));

        if ($input !== '') {
            try {
                return CarbonImmutable::parse($input, config('app.timezone'))->startOfDay();
            } catch (\Throwable) {
            }
        }

        return CarbonImmutable::now(config('app.timezone'))->startOfDay();
    }

    private function tableIsReady(): bool
    {
        try {
            return Schema::hasTable('header_themes');
        } catch (\Throwable) {
            return false;
        }
    }
}
