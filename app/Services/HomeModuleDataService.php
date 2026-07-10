<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Support\AdvertisementPlacement;
use Illuminate\Database\Eloquent\Builder;
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
        $freshNewsOrder = 'is_breaking DESC, published_at DESC, editorial_score DESC';
        $newsValueOrder = $this->newsValueOrder();
        $usedIds = [];

        $heroSideLimit = max(1, (int) data_get($moduleMap, 'hero.settings.content_limit', 5));
        $heroMain = $this->pickPinnedOrBest('hero', $usedIds, $newsValueOrder, true);
        $this->rememberUsed(collect([$heroMain])->filter(), $usedIds);

        $heroSide = $this->selectModuleArticles(
            'hero_side',
            $usedIds,
            $heroSideLimit,
            fn (Builder $query): Builder => $query,
            $newsValueOrder,
            true,
            2
        );
        $heroIds = $usedIds;

        $localNews = $this->selectModuleArticles(
            'local_news',
            $usedIds,
            (int) data_get($moduleMap, 'local_news.settings.content_limit', 6),
            fn (Builder $query): Builder => $query->where(function ($query) {
                $query->where('city_slug', 'adiyaman')
                    ->orWhere(function ($fallbackQuery) {
                        $fallbackQuery->whereNull('city_slug')
                            ->where('city_code', IhaCategoryMapper::LOCALITY_LOCAL);
                    });
            }),
            $newsValueOrder,
            false,
            2
        );

        $highlights = $this->selectModuleArticles(
            'highlights',
            $usedIds,
            (int) data_get($moduleMap, 'highlights.settings.content_limit', 4),
            fn (Builder $query): Builder => $query,
            $newsValueOrder,
            false,
            1
        );

        $mostReadLimit = (int) data_get($moduleMap, 'most_read.settings.content_limit', 5);
        $mostRead = $this->baseHomepageQuery()
            ->where('published_at', '>=', now()->subDays(7))
            ->orderByDesc('view_count')
            ->take($mostReadLimit)
            ->get();

        if ($mostRead->isEmpty()) {
            $mostRead = $this->baseHomepageQuery()
                ->orderByDesc('view_count')
                ->orderByRaw($editorialOrder)
                ->take($mostReadLimit)
                ->get();
        }

        if ($mostRead->isEmpty()) {
            $mostRead = $this->baseHomepageQuery()
                ->orderByRaw($editorialOrder)
                ->take($mostReadLimit)
                ->get();
        }

        $asayisNews = $this->selectModuleArticles(
            'asayis_news',
            $usedIds,
            (int) data_get($moduleMap, 'asayis_news.settings.content_limit', 6),
            fn (Builder $query): Builder => $this->whereCategorySlugIn($query, ['asayis']),
            $newsValueOrder,
            false,
            2
        );

        $regionNews = $this->selectModuleArticles(
            'region_news',
            $usedIds,
            (int) data_get($moduleMap, 'region_news.settings.content_limit', 6),
            fn (Builder $query): Builder => $query->where('city_code', IhaCategoryMapper::LOCALITY_REGION),
            $newsValueOrder,
            false,
            2
        );

        $politicsEconomyNews = $this->selectModuleArticles(
            'politics_economy',
            $usedIds,
            (int) data_get($moduleMap, 'politics_economy.settings.content_limit', 6),
            fn (Builder $query): Builder => $this->whereCategorySlugIn($query, ['siyaset', 'ekonomi']),
            $newsValueOrder,
            false,
            2
        );

        $lifeDigestNews = $this->selectModuleArticles(
            'life_digest',
            $usedIds,
            (int) data_get($moduleMap, 'life_digest.settings.content_limit', 6),
            fn (Builder $query): Builder => $this->whereCategorySlugIn($query, ['yasam', 'egitim', 'saglik', 'kultur-sanat', 'teknoloji']),
            $newsValueOrder,
            false,
            2
        );

        $latestNews = $this->selectModuleArticles(
            'latest_news',
            $usedIds,
            (int) data_get($moduleMap, 'latest_news.settings.content_limit', 8),
            fn (Builder $query): Builder => $query,
            $freshNewsOrder,
            false,
            2
        );

        $newsRiverLimit = (int) data_get($moduleMap, 'news_river.settings.content_limit', 16);
        $newsRiver = $this->selectModuleArticles(
            'news_river',
            $usedIds,
            $newsRiverLimit,
            fn (Builder $query): Builder => $query,
            $freshNewsOrder,
            false,
            3
        );

        $breakingLimit = min(6, (int) data_get($moduleMap, 'breaking_bar.settings.content_limit', 6));

        $breakingNews = $this->excludeUsed($this->baseHomepageQuery(), $heroIds)
            ->breaking()
            ->orderByRaw($newsValueOrder)
            ->take($breakingLimit)
            ->get();

        if ($breakingNews->count() < $breakingLimit) {
            $breakingNews = $breakingNews
                ->merge(
                    $this->excludeUsed($this->baseHomepageQuery(), array_merge($heroIds, $breakingNews->pluck('id')->toArray()))
                        ->orderByRaw($newsValueOrder)
                        ->take($breakingLimit - $breakingNews->count())
                        ->get()
                )
                ->values();
        }

        if ($breakingNews->count() < $breakingLimit) {
            $breakingNews = $breakingNews
                ->merge(
                    $this->excludeUsed($this->baseHomepageQuery(), array_merge($heroIds, $breakingNews->pluck('id')->toArray()))
                        ->orderByRaw($newsValueOrder)
                        ->take($breakingLimit - $breakingNews->count())
                        ->get()
                )
                ->values();
        }

        $categoryLimit = (int) data_get($moduleMap, 'category_shortcuts.settings.content_limit', 9);
        $categories = Category::active()
            ->roots()
            ->orderBy('sort_order')
            ->get()
            ->map(function (Category $category): Category {
                $category->setAttribute('articles_count', $category->publicArticlesCount());

                return $category;
            })
            ->filter(fn (Category $category): bool => (int) $category->articles_count > 0)
            ->take($categoryLimit)
            ->values();

        $ads = Advertisement::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('position');

        return compact(
            'heroMain',
            'heroSide',
            'localNews',
            'highlights',
            'asayisNews',
            'mostRead',
            'regionNews',
            'politicsEconomyNews',
            'lifeDigestNews',
            'latestNews',
            'newsRiver',
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
            'asayis_news' => ($data['asayisNews'] ?? collect())->isNotEmpty(),
            'most_read' => ($data['mostRead'] ?? collect())->isNotEmpty(),
            'region_news' => ($data['regionNews'] ?? collect())->isNotEmpty(),
            'politics_economy' => ($data['politicsEconomyNews'] ?? collect())->isNotEmpty(),
            'life_digest' => ($data['lifeDigestNews'] ?? collect())->isNotEmpty(),
            'latest_news' => ($data['latestNews'] ?? collect())->isNotEmpty(),
            'news_river' => ($data['newsRiver'] ?? collect())->isNotEmpty(),
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

    private function baseHomepageQuery(): Builder
    {
        return NewsArticle::published()
            ->homepageVisible()
            ->with('category');
    }

    private function pickPinnedOrBest(string $area, array $usedIds, string $order, bool $imageRequired = false): ?NewsArticle
    {
        $pinnedQuery = $this->excludeUsed($this->baseHomepageQuery()->pinnedFor($area), $usedIds);

        if ($imageRequired) {
            $pinnedQuery->withRealImage();
        }

        $pinned = $pinnedQuery
            ->orderByRaw($order)
            ->first();

        if ($pinned) {
            return $pinned;
        }

        $query = $this->excludeUsed($this->baseHomepageQuery(), $usedIds);

        if ($imageRequired) {
            $query->withRealImage();
        }

        return $query
            ->orderByRaw($order)
            ->first();
    }

    /**
     * @param array<int, int> $usedIds
     * @param callable(Builder): Builder $constraint
     */
    private function selectModuleArticles(
        string $area,
        array &$usedIds,
        int $limit,
        callable $constraint,
        string $order,
        bool $imageRequired = false,
        int $maxPerCategory = 2
    ): Collection {
        $limit = max(1, $limit);
        $pinnedQuery = $constraint($this->excludeUsed($this->baseHomepageQuery()->pinnedFor($area), $usedIds));

        if ($imageRequired) {
            $pinnedQuery->withRealImage();
        }

        $pinned = $pinnedQuery
            ->orderByRaw($order)
            ->take($limit)
            ->get();

        $moduleUsedIds = array_values(array_unique(array_merge($usedIds, $pinned->pluck('id')->toArray())));
        $poolQuery = $constraint($this->excludeUsed($this->baseHomepageQuery(), $moduleUsedIds));

        if ($imageRequired) {
            $poolQuery->withRealImage();
        }

        $pool = $poolQuery
            ->orderByRaw($order)
            ->take(max($limit * 4, 24))
            ->get();

        $selected = $pinned
            ->merge($this->diversifyByCategory($pool, $limit - $pinned->count(), $maxPerCategory))
            ->unique('id')
            ->take($limit)
            ->values();

        $this->rememberUsed($selected, $usedIds);

        return $selected;
    }

    /**
     * @param array<int, int> $usedIds
     */
    private function excludeUsed(Builder $query, array $usedIds): Builder
    {
        $usedIds = array_values(array_filter(array_unique($usedIds)));

        return empty($usedIds) ? $query : $query->whereNotIn('id', $usedIds);
    }

    /**
     * @param array<int, int> $usedIds
     */
    private function rememberUsed(Collection $articles, array &$usedIds): void
    {
        $usedIds = array_values(array_unique(array_merge($usedIds, $articles->pluck('id')->filter()->toArray())));
    }

    /**
     * @param array<int, string> $slugs
     */
    private function whereCategorySlugIn(Builder $query, array $slugs): Builder
    {
        return $query->whereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->whereIn('slug', $slugs));
    }

    private function diversifyByCategory(Collection $pool, int $limit, int $maxPerCategory = 2): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

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

    private function newsValueOrder(): string
    {
        return "(CASE
            WHEN featured_image IS NOT NULL
                AND featured_image != ''
                AND featured_image NOT LIKE '%placeholder%'
            THEN 120 ELSE 0
        END
        + CASE WHEN is_breaking = 1 THEN 20 ELSE 0 END
        + CASE WHEN is_featured = 1 THEN 16 ELSE 0 END
        + CASE
            WHEN city_code = 3 THEN 10
            WHEN city_code = 2 THEN 6
            ELSE 0
        END
        + CASE
            WHEN COALESCE(editorial_score, 0) > 100 THEN 100
            WHEN COALESCE(editorial_score, 0) < 0 THEN 0
            ELSE COALESCE(editorial_score, 0)
        END) DESC, published_at DESC";
    }
}
