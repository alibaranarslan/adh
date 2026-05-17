<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Services\IhaCategoryMapper;

class CityController extends Controller
{
    public function show(string $localeOrSlug, ?string $slug = null)
    {
        $slug = $slug ?? $localeOrSlug;
        $allCities = IhaCategoryMapper::getActiveCities();

        abort_unless(in_array($slug, array_keys($allCities), true), 404);

        $cityName = $allCities[$slug];

        $articles = NewsArticle::published()
            ->with('category')
            ->where('city_slug', $slug)
            ->latest('published_at')
            ->paginate(16);

        return view('news.city', compact('cityName', 'slug', 'articles', 'allCities'))->with([
            'metaTitle' => $cityName . ' ' . __('Haberleri') . ' | ' . __('Adıyaman Dijital Haber'),
            'metaDescription' => $cityName . ' ' . __('ilinden en güncel haberler.'),
        ]);
    }

    public function index()
    {
        $allCities = IhaCategoryMapper::getActiveCities();

        $cityCounts = NewsArticle::published()
            ->whereNotNull('city_slug')
            ->selectRaw('city_slug, COUNT(*) as total')
            ->groupBy('city_slug')
            ->pluck('total', 'city_slug');

        return view('news.cities', compact('allCities', 'cityCounts'))->with([
            'metaTitle' => __('İller') . ' | ' . __('Adıyaman Dijital Haber'),
            'metaDescription' => __('Bölge illerinden en güncel haberler.'),
        ]);
    }
}
