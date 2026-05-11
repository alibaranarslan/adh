<?php

namespace Tests\Unit\Support;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Support\NewsPresenter;
use Tests\TestCase;

class NewsPresenterTest extends TestCase
{
    public function test_it_builds_consistent_news_view_data(): void
    {
        $category = new Category([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
        ]);

        $article = new NewsArticle([
            'title' => ['tr' => 'Test Haber'],
            'slug' => 'test-haber',
            'summary' => ['tr' => 'Özet bilgi'],
            'content' => ['tr' => 'Detaylı içerik burada yer alıyor.'],
            'source' => 'iha',
            'view_count' => 1234,
            'published_at' => now()->subMinutes(20),
        ]);

        $article->setRelation('category', $category);

        $story = NewsPresenter::present($article);

        $this->assertStringContainsString('test-haber', $story['url']);
        $this->assertSame('Gündem', $story['category_name']);
        $this->assertSame('İHA', $story['source_label']);
        $this->assertSame('1.234 görüntüleme', $story['views_label']);
        $this->assertSame('1 dk okuma', $story['read_time_label']);
        $this->assertNotEmpty($story['freshness_label']);
        $this->assertStringContainsString('placeholder-news.jpg', $story['image_url']);
    }
}
