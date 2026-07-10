<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\NewsArticle;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index()
    {
        $editorialOrder = 'editorial_score DESC, published_at DESC';
        $freshNewsOrder = 'is_breaking DESC, published_at DESC, editorial_score DESC';
        $newsValueOrder = "(CASE
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

        $heroMain = NewsArticle::published()
            ->with('category')
            ->orderByRaw($newsValueOrder)
            ->first();

        $heroSidePool = NewsArticle::published()
            ->with('category')
            ->whereNot('id', $heroMain?->id ?? 0)
            ->orderByRaw($newsValueOrder)
            ->take(30)
            ->get();

        $heroSide = $this->diversifyByCategory($heroSidePool, 5, 2);

        $heroIds = collect([$heroMain?->id])
            ->merge($heroSide->pluck('id'))
            ->filter()
            ->values()
            ->toArray();

        $localNews = NewsArticle::published()
            ->with('category')
            ->where('city_code', 3)
            ->orderByRaw($freshNewsOrder)
            ->take(6)
            ->get();

        $highlightsPool = NewsArticle::published()
            ->with('category')
            ->whereNotIn('id', $heroIds)
            ->orderByRaw($editorialOrder)
            ->take(20)
            ->get();

        $highlights = $this->diversifyByCategory($highlightsPool, 4, 1);
        $usedIds = array_merge($heroIds, $highlights->pluck('id')->toArray());

        $regionNews = NewsArticle::published()
            ->with('category')
            ->where('city_code', 2)
            ->whereNotIn('id', $usedIds)
            ->orderByRaw($freshNewsOrder)
            ->take(6)
            ->get();

        $usedIds = array_merge($usedIds, $regionNews->pluck('id')->toArray());

        $latestNews = NewsArticle::published()
            ->with('category')
            ->whereNotIn('id', $usedIds)
            ->orderByRaw($freshNewsOrder)
            ->take(12)
            ->get();

        $breakingNews = NewsArticle::published()
            ->breaking()
            ->with('category')
            ->whereNotIn('id', $heroIds)
            ->latest('published_at')
            ->take(6)
            ->get();

        if ($breakingNews->isEmpty()) {
            $breakingNews = $heroSide->take(3);
        }

        $categories = Category::active()
            ->roots()
            ->orderBy('sort_order')
            ->whereHas('articles', fn ($q) => $q->published())
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->get();

        $mostRead = NewsArticle::published()
            ->with('category')
            ->where('published_at', '>=', now()->subDays(7))
            ->orderByDesc('view_count')
            ->take(5)
            ->get();

        $ads = Advertisement::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('position');

        return view('home.index', compact(
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
        ))->with([
            'metaTitle'       => null,
            'metaDescription' => __('Adıyaman ve çevresinden en güncel haberler.'),
            'ogImage'         => $heroMain?->featured_image,
        ]);
    }

    private function diversifyByCategory(Collection $pool, int $limit, int $maxPerCategory = 2): Collection
    {
        $result = collect();
        $categoryCount = [];

        foreach ($pool as $article) {
            $catId = $article->category_id;
            $count = $categoryCount[$catId] ?? 0;

            if ($count < $maxPerCategory) {
                $result->push($article);
                $categoryCount[$catId] = $count + 1;
            }

            if ($result->count() >= $limit) {
                break;
            }
        }

        if ($result->count() < $limit) {
            foreach ($pool as $article) {
                if ($result->pluck('id')->contains($article->id)) {
                    continue;
                }
                $result->push($article);
                if ($result->count() >= $limit) {
                    break;
                }
            }
        }

        return $result;
    }
}
