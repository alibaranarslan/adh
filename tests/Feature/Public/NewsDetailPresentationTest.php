<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsDetailPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_news_detail_shows_editorial_signals_and_share_links(): void
    {
        $author = User::factory()->create([
            'name' => 'Ayse Editor',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Manuel Haber'],
            'slug' => 'manuel-haber',
            'summary' => ['tr' => 'Manuel haber ozet'],
            'content' => ['tr' => 'Manuel haber detay icerigi'],
            'featured_image' => '/images/test-manual.jpg',
            'source' => 'manuel',
            'author_id' => $author->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(5),
            'editorial_score' => 90,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $response = $this->get(route('news.show', ['slug' => $article->slug]));

        $response
            ->assertOk()
            ->assertSee('Anasayfa')
            ->assertSee('Gundem')
            ->assertSee('Manuel haber detay icerigi')
            ->assertSee(json_decode('"Edit\u00f6r Giri\u015fi"'))
            ->assertSee('Yazar')
            ->assertSee('Ayse Editor')
            ->assertSee(json_decode('"G\u00fcncellendi"'))
            ->assertSee('twitter.com/intent/tweet', false)
            ->assertSee('facebook.com/sharer/sharer.php', false)
            ->assertSee('wa.me/?text=', false);
    }

    public function test_detail_page_falls_back_to_summary_when_body_is_empty(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Ozetten Okunan Haber'],
            'slug' => 'ozetten-okunan-haber',
            'summary' => ['tr' => 'Govde bos olsa da okunabilir ozet metni gosterilmeli.'],
            'content' => ['tr' => ''],
            'featured_image' => '/images/test-summary-fallback.jpg',
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
            'editorial_score' => 70,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $this->get(route('news.show', ['slug' => $article->slug]))
            ->assertOk()
            ->assertSee(json_decode('"Haber \u00d6zeti"'))
            ->assertSee('Govde bos olsa da okunabilir ozet metni gosterilmeli.')
            ->assertDontSee(json_decode('"Bu haber i\u00e7in ayr\u0131nt\u0131l\u0131 i\u00e7erik hen\u00fcz payla\u015f\u0131lmad\u0131."'));
    }

    public function test_iha_detail_escapes_untrusted_html_body(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Guvenli IHA Govdesi'],
            'slug' => 'guvenli-iha-govdesi',
            'summary' => ['tr' => 'IHA govdesi sanitize edilmeli.'],
            'content' => ['tr' => '<p>Guvenli govde metni.</p><img src=x onerror="alert(1)"><script>alert("x")</script><a href="javascript:alert(1)">zararli link</a>'],
            'featured_image' => '/images/test-iha-html.jpg',
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
            'editorial_score' => 70,
        ]);

        $this->get(route('news.show', ['slug' => $article->slug]))
            ->assertOk()
            ->assertSee('Guvenli govde metni.')
            ->assertSee('zararli link')
            ->assertDontSee('alert("x")', false)
            ->assertDontSee('<img src=x', false)
            ->assertDontSee('onerror=', false)
            ->assertDontSee('javascript:', false)
            ->assertDontSee('<p>Guvenli govde metni.</p>', false);
    }

    public function test_detail_body_card_has_dark_mode_contrast_guard(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Mobil Karanlik Mod Haberi'],
            'slug' => 'mobil-karanlik-mod-haberi',
            'summary' => ['tr' => 'Karanlik mod govde kontrasti korunmali.'],
            'content' => ['tr' => 'Ana haber metni mobil karanlik modda okunabilir kalmali.'],
            'featured_image' => '/images/test-dark-detail.jpg',
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
            'editorial_score' => 70,
        ]);

        $this->get(route('news.show', ['slug' => $article->slug]))
            ->assertOk()
            ->assertSee('adh-article-body-card', false)
            ->assertSee('.dark .adh-article-body-card', false)
            ->assertSee('--tw-prose-body: #e5e7eb;', false)
            ->assertSee('dark:bg-adh-blue', false);
    }

    public function test_prefixed_english_detail_route_renders_and_keeps_locale_links(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem', 'en' => 'Agenda'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tag = Tag::create([
            'name' => ['tr' => 'Yerel Gundem', 'en' => 'Local Agenda'],
            'slug' => 'yerel-gundem',
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Locale Koruyan Haber', 'en' => 'Locale Preserving Story'],
            'slug' => 'locale-koruyan-haber',
            'summary' => ['tr' => 'Detay sayfa locale linklerini korumali.', 'en' => 'Detail page should preserve locale links.'],
            'content' => ['tr' => 'Govde mevcut.', 'en' => 'Body is present.'],
            'featured_image' => '/images/test-locale-links.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(15),
            'editorial_score' => 75,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $article->tags()->attach($tag->id);

        $this->get('/en/' . $article->slug)
            ->assertOk()
            ->assertSee('href="' . url('/en') . '"', false)
            ->assertSee('href="' . url('/en/kategori/' . $category->slug) . '"', false)
            ->assertSee('href="' . url('/en/etiket/' . $tag->slug) . '"', false);

        $this->get('/en/kategori/' . $category->slug)
            ->assertOk()
            ->assertSee('Agenda');

        $this->get('/en/etiket/' . $tag->slug)
            ->assertOk()
            ->assertSee('Local Agenda');
    }

    public function test_prefixed_kurdish_detail_route_falls_back_without_404(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Kurtce Prefix Testi'],
            'slug' => 'kurtce-prefix-testi',
            'summary' => ['tr' => 'Kurtce route fallback ile 404 olmamali.'],
            'content' => ['tr' => ''],
            'featured_image' => '/images/test-kurdish-prefix.jpg',
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
            'editorial_score' => 65,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $this->get('/ku/' . $article->slug)
            ->assertOk()
            ->assertSee('Kurtce route fallback ile 404 olmamali.')
            ->assertDontSee(json_decode('"Bu haber i\u00e7in ayr\u0131nt\u0131l\u0131 i\u00e7erik hen\u00fcz payla\u015f\u0131lmad\u0131."'));
    }
}
