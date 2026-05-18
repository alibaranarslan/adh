<?php

namespace Tests\Feature\Public;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\LocalInfoEntry;
use App\Models\NewsArticle;
use App\Models\Setting;
use Database\Seeders\CustomerContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_category_and_news_pages_render_with_shared_breaking_news(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $breaking = NewsArticle::create([
            'title' => ['tr' => 'Son Dakika Haber'],
            'slug' => 'son-dakika-haber',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-breaking.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'is_breaking' => true,
            'published_at' => now()->subMinutes(5),
            'editorial_score' => 95,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Normal Haber'],
            'slug' => 'normal-haber',
            'summary' => ['tr' => 'Ikinci ozet'],
            'content' => ['tr' => 'Ikinci detayli haber icerigi'],
            'featured_image' => '/images/test-normal.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(1),
            'editorial_score' => 85,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Son Dakika Haber')
            ->assertSee('/images/test-breaking.jpg')
            ->assertDontSee('Haber akisi gecici olarak eksik.');

        $this->get(route('news.category', ['slug' => $category->slug]))
            ->assertOk()
            ->assertSee('Son Dakika Haber');

        $this->get(route('news.show', ['slug' => $breaking->slug]))
            ->assertOk()
            ->assertSee('Son Dakika Haber');
    }

    public function test_city_page_and_robots_txt_rewrite_existing_sitemap_url(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Yerel'],
            'slug' => 'yerel',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Adiyaman Merkez Haberi'],
            'slug' => 'adiyaman-merkez-haberi',
            'summary' => ['tr' => 'Yerel ozet'],
            'content' => ['tr' => 'Yerel detayli haber icerigi'],
            'featured_image' => '/images/test-city.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 70,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        Setting::create([
            'group' => 'seo',
            'key' => 'robots_txt',
            'value' => "User-agent: *\nAllow: /\nSitemap: https://adiyamandijitalhaber.com.tr/sitemap.xml",
        ]);

        $this->get(route('city.show', ['slug' => 'adiyaman']))
            ->assertOk()
            ->assertSee('Adiyaman');

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: ' . url('/sitemap.xml'), false);
    }

    public function test_robots_txt_appends_sitemap_when_stored_content_has_no_sitemap_line(): void
    {
        Setting::create([
            'group' => 'seo',
            'key' => 'robots_txt',
            'value' => "User-agent: *\nAllow: /\nDisallow: /admin",
        ]);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: ' . url('/sitemap.xml'), false);
    }

    public function test_robots_txt_fallback_includes_sitemap_when_no_stored_value_exists(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: *')
            ->assertSee('Allow: /')
            ->assertSee('Sitemap: ' . url('/sitemap.xml'), false);
    }

    public function test_static_public_robots_file_is_not_present_to_bypass_laravel_route(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function test_static_and_contact_pages_render_clean_meta_titles(): void
    {
        $this->seed(CustomerContentSeeder::class);

        $this->get('/gizlilik-politikasi')
            ->assertOk()
            ->assertSee('<title>Gizlilik Politikası | Adıyaman Dijital Haber</title>', false)
            ->assertDontSee('Gizlilik Politikası | Adıyaman Dijital Haber | Adıyaman Dijital Haber', false);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('<title>İletişim | Adıyaman Dijital Haber</title>', false);
    }

    public function test_health_endpoint_reports_ok_with_testing_database(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'app' => 'adh',
                'database' => 'ok',
            ]);
    }

    public function test_home_shows_local_widget_fallback_messages_when_api_data_is_unavailable(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Test Haber'],
            'slug' => 'test-haber',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(json_decode('"Ad\u0131yaman hava durumu verisi \u015fu anda g\u00fcncellenemiyor."'))
            ->assertSee(json_decode('"N\u00f6bet\u00e7i eczane verisi \u015fu anda g\u00fcncellenemiyor."'))
            ->assertSee(json_decode('"Namaz vakti verisi \u015fu anda g\u00fcncellenemiyor."'));
    }

    public function test_home_shows_current_local_announcements_created_from_admin(): void
    {
        Cache::forget('local_announcements');

        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Duyuru ile ana sayfa'],
            'slug' => 'duyuru-ile-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        LocalInfoEntry::create([
            'type' => 'road_status',
            'title' => 'Adiyaman-Golbasi yolunda kontrollu gecis',
            'content' => 'Bakim calismasi nedeniyle trafik tek seritten veriliyor.',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Yerel Duyurular')
            ->assertSee('Adiyaman-Golbasi yolunda kontrollu gecis')
            ->assertSee('Bakim calismasi nedeniyle trafik tek seritten veriliyor.');
    }

    public function test_home_shows_public_fallback_notice_when_editorial_modules_are_empty(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Haber akisi gecici olarak eksik.')
            ->assertSee('Bilgi Panosu')
            ->assertDontSee('Adiyaman Gundemi');
    }

    public function test_home_renders_current_active_home_and_global_ads_with_storage_urls(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Reklam ile ana sayfa'],
            'slug' => 'reklam-ile-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        foreach ([
            ['Header Reklami', 'header', 'advertisements/header.jpg'],
            ['Ana Sayfa Ust Reklami', 'home-top', 'advertisements/home-top.jpg'],
            ['Ana Sayfa Haber Arasi Reklam', 'home-feed', 'advertisements/home-feed.jpg'],
            ['Ana Sayfa Alt Reklam', 'home-lower', 'advertisements/home-lower.jpg'],
            ['Haberler Arasi Reklam', 'between-news', 'advertisements/between.jpg'],
            ['Sidebar Ust Reklami', 'sidebar-top', 'advertisements/sidebar-top.jpg'],
            ['Sidebar Alt Reklami', 'sidebar-bottom', 'storage/advertisements/sidebar-bottom.jpg'],
            ['Footer Reklami', 'footer', 'advertisements/footer.jpg'],
        ] as [$name, $position, $imagePath]) {
            Advertisement::create([
                'name' => $name,
                'position' => $position,
                'type' => 'banner',
                'desktop_image_path' => $imagePath,
                'link_url' => 'https://example.com',
                'is_active' => true,
                'start_date' => now()->subDay(),
                'end_date' => now()->addDay(),
                'sort_order' => 1,
                'view_count' => 0,
                'click_count' => 0,
            ]);
        }

        $activeAd = Advertisement::create([
            'name' => 'Aktif Placeholder Reklami',
            'position' => 'sidebar-top',
            'type' => 'banner',
            'image_path' => null,
            'link_url' => null,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'sort_order' => 1,
            'view_count' => 0,
            'click_count' => 0,
        ]);

        Advertisement::create([
            'name' => 'Gelecek Reklam',
            'position' => 'sidebar-top',
            'type' => 'banner',
            'is_active' => true,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'sort_order' => 0,
            'view_count' => 0,
            'click_count' => 0,
        ]);

        $renderedAd = Advertisement::query()->where('name', 'Header Reklami')->firstOrFail();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Header Reklami')
            ->assertSee('Ana Sayfa Ust Reklami')
            ->assertSee('Ana Sayfa Haber Arasi Reklam')
            ->assertSee('Ana Sayfa Alt Reklam')
            ->assertSee('Haberler Arasi Reklam')
            ->assertSee('Sidebar Ust Reklami')
            ->assertSee('Sidebar Alt Reklami')
            ->assertSee('Footer Reklami')
            ->assertSee('/storage/advertisements/header.jpg', false)
            ->assertSee('/storage/advertisements/home-top.jpg', false)
            ->assertSee('/storage/advertisements/home-feed.jpg', false)
            ->assertSee('/storage/advertisements/home-lower.jpg', false)
            ->assertSee('/storage/advertisements/between.jpg', false)
            ->assertSee('/storage/advertisements/sidebar-top.jpg', false)
            ->assertSee('/storage/advertisements/sidebar-bottom.jpg', false)
            ->assertSee('/storage/advertisements/footer.jpg', false)
            ->assertSee('data-impression-url="' . route('ad.impression', ['ad' => $renderedAd->id]) . '"', false)
            ->assertSee('IntersectionObserver', false)
            ->assertDontSee('x-intersect', false)
            ->assertDontSee('Gelecek Reklam')
            ->assertDontSee('src=""', false)
            ->assertDontSee('src="/"', false);

        $this->postJson(route('ad.impression', ['ad' => $activeAd->id]))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->postJson(route('ad.click', ['ad' => $activeAd->id]))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $activeAd->refresh();

        $this->assertSame(1, $activeAd->view_count);
        $this->assertSame(1, $activeAd->click_count);
    }

    public function test_active_banner_without_media_is_hidden_from_public_page(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Placeholder reklamli ana sayfa'],
            'slug' => 'placeholder-reklamli-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        Advertisement::create([
            'name' => 'Gorselsiz Aktif Reklam',
            'position' => 'sidebar-top',
            'type' => 'banner',
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Gorselsiz Aktif Reklam')
            ->assertDontSee(json_decode('"Bu reklam alan\u0131 i\u00e7in g\u00f6rsel hen\u00fcz tan\u0131mlanmad\u0131."'))
            ->assertDontSee('src=""', false)
            ->assertDontSee('src="/"', false);
    }

    public function test_empty_home_ad_slots_render_professional_house_ads_when_enabled(): void
    {
        Setting::set('general', 'contact_phone', '0416 000 00 00');
        Setting::set('advertising', 'house_ads_enabled', '1');

        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Reklam dolgu ana sayfa'],
            'slug' => 'reklam-dolgu-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Bu alanda markanızı öne çıkarın')
            ->assertSee('Ana Sayfa Üst Sponsor')
            ->assertSee('Ana Sayfa Haber Arası')
            ->assertSee('Ana Sayfa Alt Sponsor')
            ->assertSee('İletişim: 0416 000 00 00')
            ->assertSee('tel:0416000000', false)
            ->assertDontSee('data-impression-url="', false);
    }

    public function test_empty_home_ad_slots_are_hidden_when_house_ads_are_disabled(): void
    {
        Setting::set('advertising', 'house_ads_enabled', '0');

        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Reklam dolgu kapali ana sayfa'],
            'slug' => 'reklam-dolgu-kapali-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Buraya reklam verebilirsiniz')
            ->assertDontSee('Bu alanda markanızı öne çıkarın')
            ->assertDontSee('Ana Sayfa Üst Sponsor');
    }

    public function test_manual_banner_renders_mobile_source_when_mobile_image_is_available(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Responsive reklamli ana sayfa'],
            'slug' => 'responsive-reklamli-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        Advertisement::create([
            'name' => 'Responsive Header Reklami',
            'position' => 'header',
            'type' => Advertisement::TYPE_BANNER,
            'desktop_image_path' => 'advertisements/header-desktop.jpg',
            'mobile_image_path' => 'advertisements/header-mobile.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Responsive Header Reklami')
            ->assertSee('<picture', false)
            ->assertSee('media="(max-width: 767px)"', false)
            ->assertSee('/storage/advertisements/header-mobile.jpg', false)
            ->assertSee('/storage/advertisements/header-desktop.jpg', false)
            ->assertSee('--adh-ad-max-height: 180px', false)
            ->assertSee('--adh-ad-mobile-max-height: 150px', false)
            ->assertSee('--adh-ad-aspect-ratio: 8 / 1', false)
            ->assertSee('--adh-ad-mobile-aspect-ratio: 3.3 / 1', false)
            ->assertSee('aspect-[var(--adh-ad-mobile-aspect-ratio)] md:aspect-[var(--adh-ad-aspect-ratio)]', false)
            ->assertSee('max-h-[var(--adh-ad-mobile-max-height)] md:max-h-[var(--adh-ad-max-height)]', false);
    }

    public function test_sidebar_banner_renders_sidebar_specific_height_guardrails(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Sidebar reklamli ana sayfa'],
            'slug' => 'sidebar-reklamli-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        Advertisement::create([
            'name' => 'Sidebar Reklami',
            'position' => 'sidebar-top',
            'type' => Advertisement::TYPE_BANNER,
            'desktop_image_path' => 'advertisements/sidebar.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sidebar Reklami')
            ->assertSee('--adh-ad-max-height: 360px', false)
            ->assertSee('--adh-ad-mobile-max-height: 260px', false)
            ->assertSee('--adh-ad-aspect-ratio: 1.25 / 1', false)
            ->assertSee('--adh-ad-mobile-aspect-ratio: 2.8 / 1', false);
    }

    public function test_adsense_ad_requires_client_and_slot_before_rendering(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Adsense reklamli ana sayfa'],
            'slug' => 'adsense-reklamli-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        Advertisement::create([
            'name' => 'Eksik Client Adsense',
            'position' => 'header',
            'type' => 'adsense',
            'adsense_slot' => '1234567890',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-ad-slot="1234567890"', false);

        Setting::create([
            'group' => 'integration',
            'key' => 'adsense_client_id',
            'value' => 'ca-pub-test-client',
        ]);
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-test-client', false)
            ->assertSee('data-ad-client="ca-pub-test-client"', false)
            ->assertSee('data-ad-slot="1234567890"', false);
    }

    public function test_news_detail_renders_article_top_and_bottom_ads_without_sidebar_dependency(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Reklamli haber detayi'],
            'slug' => 'reklamli-haber-detayi',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        Advertisement::create([
            'name' => 'Haber Ustu Reklami',
            'position' => 'article-top',
            'type' => 'banner',
            'desktop_image_path' => 'advertisements/article-top.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Advertisement::create([
            'name' => 'Haber Alti Reklami',
            'position' => 'article-bottom',
            'type' => 'banner',
            'desktop_image_path' => 'advertisements/article-bottom.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('news.show', ['slug' => $article->slug]))
            ->assertOk()
            ->assertSee('Haber Ustu Reklami')
            ->assertSee('Haber Alti Reklami')
            ->assertSee('/storage/advertisements/article-top.jpg', false)
            ->assertSee('/storage/advertisements/article-bottom.jpg', false)
            ->assertDontSee('Sponsorlu Alanlar');
    }

    public function test_sparse_home_modules_do_not_render_empty_side_panels_or_unrendered_ads(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Tek haberli ana sayfa'],
            'slug' => 'tek-haberli-ana-sayfa',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-single.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        Advertisement::create([
            'name' => 'Article Detail Only Reklami',
            'position' => 'article-top',
            'type' => 'banner',
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tek haberli ana sayfa')
            ->assertDontSee('Hızlı Gündem Akışı')
            ->assertSee('Reklam Verilebilir Alanlar')
            ->assertSee('Bu alanda markanızı öne çıkarın')
            ->assertDontSee('Article Detail Only Reklami');
    }

    public function test_dark_mode_uses_dark_page_background_instead_of_light_layout_background(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--adh-page-bg: var(--adh-surface-bg);', false)
            ->assertSee('.dark', false)
            ->assertSee('--adh-page-bg: #1A1A2E;', false)
            ->assertSee('background-color: var(--adh-page-bg);', false)
            ->assertDontSee('background-color: var(--adh-surface-bg);', false);
    }

    public function test_cookie_consent_banner_is_compact_and_scrollable_on_mobile(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('adh_site_cookie_consent', false)
            ->assertSee('inset-x-2 bottom-2', false)
            ->assertSee('md:max-w-sm', false)
            ->assertSee('grid grid-cols-3', false)
            ->assertDontSee('max-h-[34dvh]', false)
            ->assertDontSee('sticky -top-3', false);
    }

    public function test_public_news_surfaces_do_not_render_stock_placeholder_as_article_image(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Gorselsiz kamu haberi'],
            'slug' => 'gorselsiz-kamu-haberi',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/news/placeholder-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_slug' => 'adiyaman',
            'city_code' => 3,
        ]);

        $this->get(route('news.category', ['slug' => $category->slug]))
            ->assertOk()
            ->assertSee('Gorselsiz kamu haberi')
            ->assertDontSee('placeholder-news.jpg', false);

        $this->get(route('news.show', ['slug' => $article->slug]))
            ->assertOk()
            ->assertSee('Gorselsiz kamu haberi')
            ->assertDontSee('placeholder-news.jpg', false);
    }
}
