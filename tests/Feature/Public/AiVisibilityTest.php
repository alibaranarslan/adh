<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_exposes_ai_crawler_allow_and_machine_readable_sources(): void
    {
        config(['app.url' => 'http://adiyamandijitalhaber.com.tr']);
        app()['env'] = 'production';

        $this->get('https://adiyamandijitalhaber.com.tr/robots.txt')
            ->assertOk()
            ->assertSee("User-agent: *\nAllow: /", false)
            ->assertSee("User-agent: OAI-SearchBot\nAllow: /", false)
            ->assertSee("User-agent: ChatGPT-User\nAllow: /", false)
            ->assertSee('Sitemap: https://adiyamandijitalhaber.com.tr/sitemap.xml', false)
            ->assertSee('Sitemap: https://adiyamandijitalhaber.com.tr/sitemap-news.xml', false)
            ->assertSee('Sitemap: https://adiyamandijitalhaber.com.tr/rss.xml', false);
    }

    public function test_llms_txt_lists_public_sources_and_recent_published_articles_only(): void
    {
        config(['app.url' => 'https://adiyamandijitalhaber.com.tr']);

        $category = $this->category('Gundem', 'gundem');
        $published = $this->article($category, [
            'title' => ['tr' => 'Adiyaman icin onemli gelisme'],
            'slug' => 'adiyaman-icin-onemli-gelisme',
        ]);
        $draft = $this->article($category, [
            'title' => ['tr' => 'Taslak haber'],
            'slug' => 'taslak-haber',
            'status' => 'draft',
        ]);

        $response = $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('Adıyaman Dijital Haber', false)
            ->assertSee('https://adiyamandijitalhaber.com.tr/sitemap-news.xml', false)
            ->assertSee('https://adiyamandijitalhaber.com.tr/rss.xml', false)
            ->assertSee('https://adiyamandijitalhaber.com.tr/' . $published->slug, false)
            ->assertDontSee($draft->slug, false)
            ->assertDontSee('/admin', false)
            ->assertDontSee('/preview', false);

        $this->assertStringContainsString('AI assistants may cite public article URLs', $response->getContent());
    }

    public function test_rss_feeds_are_public_filterable_and_skip_placeholder_enclosures(): void
    {
        config(['app.url' => 'https://adiyamandijitalhaber.com.tr']);

        $gundem = $this->category('Gundem', 'gundem');
        $asayis = $this->category('Asayis', 'asayis');
        $realImage = $this->article($gundem, [
            'title' => ['tr' => 'Gorselli Adiyaman haberi'],
            'slug' => 'gorselli-adiyaman-haberi',
            'featured_image' => '/storage/news/real-news.jpg',
            'city_slug' => 'adiyaman',
        ]);
        $placeholder = $this->article($gundem, [
            'title' => ['tr' => 'Placeholder haber'],
            'slug' => 'placeholder-haber',
            'featured_image' => '/images/news/placeholder-news.jpg',
            'city_slug' => 'adiyaman',
        ]);
        $otherCity = $this->article($asayis, [
            'title' => ['tr' => 'Baska sehir haberi'],
            'slug' => 'baska-sehir-haberi',
            'featured_image' => '/storage/news/other-news.jpg',
            'city_slug' => 'malatya',
        ]);

        $this->get('/rss.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('<rss version="2.0"', false)
            ->assertSee($realImage->slug, false)
            ->assertSee('https://adiyamandijitalhaber.com.tr/storage/news/real-news.jpg', false)
            ->assertDontSee('placeholder-news.jpg', false);

        $this->get('/feed/news.xml')
            ->assertOk()
            ->assertSee($realImage->slug, false);

        $this->get('/feed/adiyaman.xml')
            ->assertOk()
            ->assertSee($realImage->slug, false)
            ->assertSee($placeholder->slug, false)
            ->assertDontSee($otherCity->slug, false);

        $this->get('/feed/kategori/gundem.xml')
            ->assertOk()
            ->assertSee($realImage->slug, false)
            ->assertDontSee($otherCity->slug, false);
    }

    public function test_public_pages_and_collections_render_ai_friendly_schema(): void
    {
        config(['app.url' => 'https://adiyamandijitalhaber.com.tr']);

        $category = $this->category('Gundem', 'gundem');
        $this->article($category, ['slug' => 'gundem-schema-haberi']);
        Page::create([
            'title' => ['tr' => 'Hakkimizda'],
            'slug' => 'hakkimizda',
            'content' => ['tr' => '<p>Kurumsal bilgi.</p>'],
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('"areaServed"', false)
            ->assertSee('"knowsAbout"', false)
            ->assertSee('Adıyaman haberleri', false);

        $this->get('/hakkimizda')
            ->assertOk()
            ->assertSee('"@type": "AboutPage"', false);

        $this->get('/iletisim')
            ->assertOk()
            ->assertSee('"@type": "ContactPage"', false);

        $this->get('/kategori/gundem')
            ->assertOk()
            ->assertSee('"@type": "CollectionPage"', false);
    }

    private function category(string $name, string $slug): Category
    {
        return Category::create([
            'name' => ['tr' => $name],
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function article(Category $category, array $overrides = []): NewsArticle
    {
        return NewsArticle::create(array_merge([
            'title' => ['tr' => 'Adiyaman haber basligi'],
            'slug' => 'adiyaman-haber-basligi-' . uniqid(),
            'summary' => ['tr' => 'Adiyaman haber ozeti'],
            'content' => ['tr' => 'Adiyaman haber govdesi yeterli uzunlukta ana metin icerir.'],
            'featured_image' => '/storage/news/test-news.jpg',
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
