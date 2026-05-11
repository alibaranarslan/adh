<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_archived_news_is_listed_and_remains_publicly_accessible(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Arsiv Haber'],
            'slug' => 'arsiv-haber',
            'summary' => ['tr' => 'Arsiv ozet'],
            'content' => ['tr' => 'Arsiv icerigi'],
            'featured_image' => '/images/test-archive.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'archived',
            'published_at' => now()->subDays(120),
            'archived_at' => now()->subDays(30),
            'editorial_score' => 12,
        ]);

        $this->get(route('news.archive'))
            ->assertOk()
            ->assertSee('Arsiv Haber');

        $this->get(route('news.show', ['slug' => $article->slug]))
            ->assertOk()
            ->assertSee('Arsiv Haber')
            ->assertSee('Arşiv İçeriği');
    }

    public function test_search_includes_archived_articles(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Arsiv Dosyasi'],
            'slug' => 'arsiv-dosyasi',
            'summary' => ['tr' => 'Arsiv tarama ozet'],
            'content' => ['tr' => 'Arsiv tarama icerigi'],
            'featured_image' => '/images/test-archive-search.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'archived',
            'published_at' => now()->subDays(180),
            'archived_at' => now()->subDays(60),
            'editorial_score' => 5,
        ]);

        $this->get(route('search', ['q' => 'Arsiv']))
            ->assertOk()
            ->assertSee('Arsiv Dosyasi');
    }
}
