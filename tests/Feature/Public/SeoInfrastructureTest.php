<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_uses_https_sitemap_index_url(): void
    {
        config(['app.url' => 'http://adiyamandijitalhaber.com.tr']);
        app()['env'] = 'production';

        $this->get('https://adiyamandijitalhaber.com.tr/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: https://adiyamandijitalhaber.com.tr/sitemap.xml', false);
    }

    public function test_sitemap_generate_creates_index_and_news_sitemap_for_recent_turkish_news(): void
    {
        config(['app.url' => 'http://adiyamandijitalhaber.com.tr']);
        app()['env'] = 'production';

        $category = $this->category();
        $recent = $this->article($category, [
            'title' => ['tr' => 'Adıyaman son dakika gelişmesi'],
            'slug' => 'adiyaman-son-dakika-gelismesi',
            'published_at' => now()->subHours(4),
        ]);
        $old = $this->article($category, [
            'title' => ['tr' => 'Eski Adıyaman haberi'],
            'slug' => 'eski-adiyaman-haberi',
            'published_at' => now()->subDays(4),
        ]);

        $this->artisan('sitemap:generate')->assertSuccessful();

        $this->assertStringContainsString('<sitemapindex', file_get_contents(public_path('sitemap.xml')));
        $this->assertStringContainsString('https://adiyamandijitalhaber.com.tr/sitemap-news.xml', file_get_contents(public_path('sitemap.xml')));

        $newsSitemap = file_get_contents(public_path('sitemap-news.xml'));
        $this->assertStringContainsString('<news:publication>', $newsSitemap);
        $this->assertStringContainsString('<news:language>tr</news:language>', $newsSitemap);
        $this->assertStringContainsString($recent->slug, $newsSitemap);
        $this->assertStringNotContainsString($old->slug, $newsSitemap);

        $articlesSitemap = file_get_contents(public_path('sitemap-articles.xml'));
        $this->assertStringContainsString($recent->slug, $articlesSitemap);
        $this->assertStringContainsString($old->slug, $articlesSitemap);
        $this->assertStringNotContainsString('/en/', $articlesSitemap);
        $this->assertStringNotContainsString('/ku/', $articlesSitemap);
    }

    public function test_news_detail_renders_seo_schema_without_placeholder_image(): void
    {
        config(['app.url' => 'https://adiyamandijitalhaber.com.tr']);

        $category = $this->category();
        $article = $this->article($category, [
            'title' => ['tr' => 'Görselsiz önemli haber'],
            'slug' => 'gorselsiz-onemli-haber',
            'summary' => ['tr' => 'Bu haberin açıklaması otomatik meta olarak kullanılabilir.'],
            'featured_image' => '/images/news/placeholder-news.jpg',
        ]);

        $response = $this->get(route('news.show', ['slug' => $article->slug]))
            ->assertOk()
            ->assertSee('"@type": "NewsArticle"', false)
            ->assertSee('"@type": "BreadcrumbList"', false)
            ->assertSee('"@type": "Organization"', false)
            ->assertSee('"@type": "WebSite"', false)
            ->assertSee('rel="canonical"', false)
            ->assertDontSee('placeholder-news.jpg', false);

        $this->assertStringNotContainsString('"image": null', $response->getContent());
    }

    private function category(): Category
    {
        return Category::create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function article(Category $category, array $overrides = []): NewsArticle
    {
        return NewsArticle::create(array_merge([
            'title' => ['tr' => 'Adıyaman haber başlığı'],
            'slug' => 'adiyaman-haber-basligi',
            'summary' => ['tr' => 'Adıyaman haber özeti'],
            'content' => ['tr' => 'Adıyaman haber gövdesi yeterli uzunlukta ana metin içerir.'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ], $overrides));
    }
}
