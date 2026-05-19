<?php

namespace Tests\Unit\Services;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Services\HomeModuleDataService;
use App\Services\LayoutConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeModuleDataServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_most_read_falls_back_to_all_time_when_recent_window_is_empty(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        for ($i = 1; $i <= 18; $i++) {
            NewsArticle::create([
                'title' => ['tr' => 'Haber '.$i],
                'slug' => 'haber-'.$i,
                'summary' => ['tr' => 'Ozet '.$i],
                'content' => ['tr' => 'Detay '.$i],
                'featured_image' => '/images/test-'.$i.'.jpg',
                'source' => 'manuel',
                'category_id' => $category->id,
                'status' => 'published',
                'published_at' => now()->subDays(20),
                'editorial_score' => 100 - $i,
                'view_count' => 1000 - ($i * 10),
                'city_code' => $i <= 6 ? 3 : 2,
                'city_slug' => $i <= 6 ? 'adiyaman' : 'bolge',
            ]);
        }

        $layoutState = app(LayoutConfigService::class)->getDraftState();
        $payload = app(HomeModuleDataService::class)->collect($layoutState);

        $this->assertSame(5, $payload['mostRead']->count());
        $this->assertSame('haber-1', $payload['mostRead']->first()->slug);
    }

    public function test_ads_section_is_hidden_when_active_ads_are_not_rendered_positions(): void
    {
        Setting::set('advertising', 'house_ads_enabled', '0');

        Advertisement::query()->create([
            'name' => 'Footer Only',
            'position' => 'footer',
            'type' => Advertisement::TYPE_BANNER,
            'image_path' => 'advertisements/footer.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $layoutState = [
            'modules' => [
                [
                    'key' => 'ads',
                    'name' => 'Reklamlar',
                    'is_active' => true,
                    'sort_order' => 1,
                    'settings' => [],
                ],
            ],
        ];

        $service = app(HomeModuleDataService::class);
        $payload = $service->collect($layoutState);
        $sections = $service->buildSections($layoutState, $payload);

        $this->assertSame([], $sections);

        Advertisement::query()->create([
            'name' => 'Incomplete Sidebar Top',
            'position' => 'sidebar-top',
            'type' => Advertisement::TYPE_BANNER,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $payload = $service->collect($layoutState);
        $sections = $service->buildSections($layoutState, $payload);

        $this->assertSame([], $sections);

        Advertisement::query()->create([
            'name' => 'Sidebar Top',
            'position' => 'sidebar-top',
            'type' => Advertisement::TYPE_BANNER,
            'image_path' => 'advertisements/sidebar-top.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $payload = $service->collect($layoutState);
        $sections = $service->buildSections($layoutState, $payload);

        $this->assertSame('ads', $sections[0]['key'] ?? null);

        Advertisement::query()->where('position', 'sidebar-top')->delete();
        Advertisement::query()->create([
            'name' => 'Between News',
            'position' => 'between-news',
            'type' => Advertisement::TYPE_BANNER,
            'image_path' => 'advertisements/between-news.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $payload = $service->collect($layoutState);
        $sections = $service->buildSections($layoutState, $payload);

        $this->assertSame('ads', $sections[0]['key'] ?? null);
    }

    public function test_breaking_news_fallback_preserves_editorial_score_priority(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Asayis'],
            'slug' => 'asayis',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Hero Haber'],
            'slug' => 'hero-haber',
            'summary' => ['tr' => 'Hero ozet'],
            'content' => ['tr' => 'Hero detay'],
            'featured_image' => '/images/hero.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(20),
            'editorial_score' => 500,
        ]);

        for ($i = 1; $i <= 6; $i++) {
            NewsArticle::create([
                'title' => ['tr' => 'Gorselsiz Eski Haber '.$i],
                'slug' => 'gorselsiz-eski-haber-'.$i,
                'summary' => ['tr' => 'Ozet '.$i],
                'content' => ['tr' => 'Detay '.$i],
                'source' => 'manuel',
                'category_id' => $category->id,
                'status' => 'published',
                'published_at' => now()->subDays($i),
                'editorial_score' => 400 - $i,
            ]);
        }

        for ($i = 1; $i <= 4; $i++) {
            NewsArticle::create([
                'title' => ['tr' => 'Gorselli Son Haber '.$i],
                'slug' => 'gorselli-son-haber-'.$i,
                'summary' => ['tr' => 'Ozet '.$i],
                'content' => ['tr' => 'Detay '.$i],
                'featured_image' => '/images/latest-'.$i.'.jpg',
                'source' => 'manuel',
                'category_id' => $category->id,
                'status' => 'published',
                'published_at' => now()->subMinutes($i),
                'editorial_score' => 10,
            ]);
        }

        $layoutState = app(LayoutConfigService::class)->getDraftState();
        $payload = app(HomeModuleDataService::class)->collect($layoutState);

        $this->assertGreaterThanOrEqual(4, $payload['breakingNews']->count());
        $this->assertSame('gorselsiz-eski-haber-6', $payload['breakingNews']->first()->slug);
        $this->assertSame(
            ['gorselsiz-eski-haber-6', 'gorselli-son-haber-1', 'gorselli-son-haber-2', 'gorselli-son-haber-3'],
            $payload['breakingNews']->take(4)->pluck('slug')->all(),
        );
    }
}
