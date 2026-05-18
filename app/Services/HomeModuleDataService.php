<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Support\AdvertisementPlacement;
use Illuminate\Support\Collection;

class HomeModuleDataService
{
    private const NON_EDITORIAL_MODULE_KEYS = [
        'sidebar_widgets',
        'ads',
    ];

    public function collect(array $layoutState): array
    {
        $moduleMap = collect($layoutState['modules'] ?? [])->keyBy('key');
        $editorialOrder = 'editorial_score DESC, published_at DESC';

        $heroSideLimit = max(1, (int) data_get($moduleMap, 'hero.settings.content_limit', 5));
        $heroMain = NewsArticle::published()
            ->with('category')
            ->whereNotNull('featured_image')
            ->where('featured_image', '!=', '')
            ->orderByRaw($editorialOrder)
            ->first();

        $heroSide = $this->diversifyByCategory(
            NewsArticle::published()
                ->with('category')
                ->whereNot('id', $heroMain?->id ?? 0)
                ->orderByRaw($editorialOrder)
                ->take(max($heroSideLimit * 4, 20))
                ->get(),
            $heroSideLimit,
            2
        );

        $heroIds = collect([$heroMain?->id])
            ->merge($heroSide->pluck('id'))
            ->filter()
            ->values()
            ->toArray();

        $localNews = NewsArticle::published()
            ->with('category')
            ->where(function ($query) {
                $query->where('city_slug', 'adiyaman')
                    ->orWhere(function ($fallbackQuery) {
                        $fallbackQuery->whereNull('city_slug')
                            ->where('city_code', 3);
                    });
            })
            ->orderByRaw($editorialOrder)
            ->take((int) data_get($moduleMap, 'local_news.settings.content_limit', 6))
            ->get();

        $highlights = $this->diversifyByCategory(
            NewsArticle::published()
                ->with('category')
                ->whereNotIn('id', $heroIds)
                ->whereNotNull('featured_image')
                ->where('featured_image', '!=', '')
                ->orderByRaw($editorialOrder)
                ->take(24)
                ->get(),
            (int) data_get($moduleMap, 'highlights.settings.content_limit', 4),
            1
        );

        $usedIds = array_merge($heroIds, $highlights->pluck('id')->toArray());

        $mostReadLimit = (int) data_get($moduleMap, 'most_read.settings.content_limit', 5);
        $mostRead = NewsArticle::published()
            ->with('category')
            ->where('published_at', '>=', now()->subDays(7))
            ->orderByDesc('view_count')
            ->take($mostReadLimit)
            ->get();

        if ($mostRead->isEmpty()) {
            $mostRead = NewsArticle::published()
                ->with('category')
                ->orderByDesc('view_count')
                ->orderByRaw($editorialOrder)
                ->take($mostReadLimit)
                ->get();
        }

        if ($mostRead->isEmpty()) {
            $mostRead = NewsArticle::published()
                ->with('category')
                ->orderByRaw($editorialOrder)
                ->take($mostReadLimit)
                ->get();
        }

        $regionNews = NewsArticle::published()
            ->with('category')
            ->where('city_code', 2)
            ->whereNotIn('id', $usedIds)
            ->orderByRaw($editorialOrder)
            ->take((int) data_get($moduleMap, 'region_news.settings.content_limit', 6))
            ->get();

        $usedIds = array_merge($usedIds, $regionNews->pluck('id')->toArray());

        $latestNews = NewsArticle::published()
            ->with('category')
            ->whereNotIn('id', $usedIds)
            ->orderByRaw($editorialOrder)
            ->take((int) data_get($moduleMap, 'latest_news.settings.content_limit', 8))
            ->get();

        $breakingNews = NewsArticle::published()
            ->breaking()
            ->with('category')
            ->whereNotIn('id', $heroIds)
            ->latest('published_at')
            ->take(min(6, (int) data_get($moduleMap, 'breaking_bar.settings.content_limit', 6)))
            ->get();

        if ($breakingNews->isEmpty()) {
            $breakingNews = $heroSide->take(3);
        }

        $categories = Category::active()
            ->roots()
            ->orderBy('sort_order')
            ->whereHas('articles', fn ($query) => $query->published())
            ->withCount(['articles' => fn ($query) => $query->published()])
            ->take((int) data_get($moduleMap, 'category_shortcuts.settings.content_limit', 9))
            ->get();

        $ads = Advertisement::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('position');

        return compact(
            'heroMain',
            'heroSide',
            'localNews',
            'highlights',
            'mostRead',
            'regionNews',
            'latestNews',
            'breakingNews',
            'categories',
            'ads'
        );
    }

    public function buildSections(array $layoutState, array $data): array
    {
        return collect($layoutState['modules'] ?? [])
            ->filter(fn (array $module): bool => (bool) ($module['is_active'] ?? true))
            ->sortBy('sort_order')
            ->map(function (array $module) use ($data): array {
                return [
                    'key' => $module['key'],
                    'name' => $module['name'],
                    'settings' => $module['settings'] ?? [],
                    'has_content' => $this->moduleHasContent($module['key'], $data),
                ];
            })
            ->filter(fn (array $module): bool => $module['has_content'])
            ->values()
            ->all();
    }

    public function hasEditorialContent(array $sections): bool
    {
        return collect($sections)->contains(function (array $section): bool {
            return ! in_array($section['key'] ?? null, self::NON_EDITORIAL_MODULE_KEYS, true);
        });
    }

    public function shouldShowFallbackNotice(array $sections): bool
    {
        return ! $this->hasEditorialContent($sections);
    }

    private function moduleHasContent(string $key, array $data): bool
    {
        return match ($key) {
            'hero' => filled($data['heroMain'] ?? null),
            'local_news' => ($data['localNews'] ?? collect())->isNotEmpty(),
            'highlights' => ($data['highlights'] ?? collect())->isNotEmpty(),
            'most_read' => ($data['mostRead'] ?? collect())->isNotEmpty(),
            'region_news' => ($data['regionNews'] ?? collect())->isNotEmpty(),
            'latest_news' => ($data['latestNews'] ?? collect())->isNotEmpty(),
            'category_shortcuts' => ($data['categories'] ?? collect())->isNotEmpty(),
            'sidebar_widgets' => true,
            'ads' => $this->hasRenderableAds($data['ads'] ?? collect()) || $this->houseAdsEnabled(),
            'breaking_bar' => ($data['breakingNews'] ?? collect())->isNotEmpty(),
            default => true,
        };
    }

    private function hasRenderableAds(Collection $ads): bool
    {
        if ($ads->isEmpty()) {
            return false;
        }

        $adsenseClientId = Setting::get('integration', 'adsense_client_id')
            ?: config('services.adsense.client_id');

        return collect(AdvertisementPlacement::homeModulePositions())
            ->contains(function (string $position) use ($ads, $adsenseClientId): bool {
                return ($ads->get($position) ?? collect())
                    ->contains(fn (Advertisement $ad): bool => $ad->isRenderable($adsenseClientId));
            });
    }

    private function houseAdsEnabled(): bool
    {
        return filter_var(
            Setting::get('advertising', 'house_ads_enabled', '1'),
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        ) ?? true;
    }

    private function diversifyByCategory(Collection $pool, int $limit, int $maxPerCategory = 2): Collection
    {
        $result = collect();
        $categoryCount = [];

        foreach ($pool as $article) {
            $categoryId = $article->category_id;
            $count = $categoryCount[$categoryId] ?? 0;

            if ($count < $maxPerCategory) {
                $result->push($article);
                $categoryCount[$categoryId] = $count + 1;
            }

            if ($result->count() >= $limit) {
                break;
            }
        }

        if ($result->count() >= $limit) {
            return $result;
        }

        foreach ($pool as $article) {
            if ($result->pluck('id')->contains($article->id)) {
                continue;
            }

            $result->push($article);

            if ($result->count() >= $limit) {
                break;
            }
        }

        return $result;
    }
}
