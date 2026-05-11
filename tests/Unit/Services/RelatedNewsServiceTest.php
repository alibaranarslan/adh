<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Services\RelatedNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatedNewsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_excludes_current_article_and_prioritizes_same_category(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $otherCategory = Category::create([
            'name' => ['tr' => 'Spor'],
            'slug' => 'spor',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Ana Haber'],
            'slug' => 'ana-haber',
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'İçerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'city_code' => 3,
            'status' => 'published',
            'published_at' => now()->subHour(),
            'editorial_score' => 90,
        ]);

        $sameCategory = NewsArticle::create([
            'title' => ['tr' => 'Kategori İçi'],
            'slug' => 'kategori-ici',
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'İçerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'city_code' => 3,
            'status' => 'published',
            'published_at' => now()->subMinutes(30),
            'editorial_score' => 95,
        ]);

        $fallback = NewsArticle::create([
            'title' => ['tr' => 'Yedek Haber'],
            'slug' => 'yedek-haber',
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'İçerik'],
            'source' => 'manuel',
            'category_id' => $otherCategory->id,
            'city_code' => 3,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
            'editorial_score' => 70,
        ]);

        $related = app(RelatedNewsService::class)->for($article, 2);

        $this->assertCount(2, $related);
        $this->assertFalse($related->pluck('id')->contains($article->id));
        $this->assertSame($sameCategory->id, $related->first()->id);
        $this->assertTrue($related->pluck('id')->contains($fallback->id));
    }
}
