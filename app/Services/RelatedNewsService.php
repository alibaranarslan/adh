<?php

namespace App\Services;

use App\Models\NewsArticle;
use Illuminate\Support\Collection;

class RelatedNewsService
{
    public function for(NewsArticle $article, int $limit = 4): Collection
    {
        $primary = NewsArticle::published()
            ->with('category')
            ->whereKeyNot($article->getKey())
            ->where('category_id', $article->category_id)
            ->orderByRaw(
                'CASE WHEN city_code = ? THEN 1 ELSE 0 END DESC, editorial_score DESC, published_at DESC',
                [$article->city_code]
            )
            ->take($limit)
            ->get();

        if ($primary->count() >= $limit) {
            return $primary;
        }

        $fallback = NewsArticle::published()
            ->with('category')
            ->whereKeyNot($article->getKey())
            ->whereNotIn('id', $primary->pluck('id'))
            ->orderByRaw(
                'CASE WHEN city_code = ? THEN 1 ELSE 0 END DESC, editorial_score DESC, published_at DESC',
                [$article->city_code]
            )
            ->take($limit - $primary->count())
            ->get();

        return $primary->concat($fallback)->take($limit)->values();
    }
}
