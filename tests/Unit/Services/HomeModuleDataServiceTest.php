<?php

namespace Tests\Unit\Services;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Services\HomeModuleDataService;
use App\Services\IhaCategoryMapper;
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

    public function test_breaking_news_fallback_prioritizes_visual_news_value(): void
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

        $layoutState = [
            'modules' => [
                ['key' => 'hero', 'settings' => ['content_limit' => 1]],
                ['key' => 'breaking_bar', 'settings' => ['content_limit' => 4]],
            ],
        ];
        $payload = app(HomeModuleDataService::class)->collect($layoutState);

        $this->assertGreaterThanOrEqual(4, $payload['breakingNews']->count());
        $this->assertSame(
            ['gorselli-son-haber-2', 'gorselli-son-haber-3', 'gorselli-son-haber-4', 'gorselsiz-eski-haber-1'],
            $payload['breakingNews']->take(4)->pluck('slug')->all(),
        );
    }

    public function test_hero_requires_real_image_even_when_text_only_story_has_higher_score(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Gorselsiz cok kritik haber'],
            'slug' => 'gorselsiz-cok-kritik-haber',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Detay'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 100,
            'homepage_pin_area' => 'hero',
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Gorselli dengeli manset'],
            'slug' => 'gorselli-dengeli-manset',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Detay'],
            'featured_image' => '/images/visual.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(5),
            'editorial_score' => 50,
        ]);

        $payload = app(HomeModuleDataService::class)->collect([
            'modules' => [
                ['key' => 'hero', 'settings' => ['content_limit' => 3]],
            ],
        ]);

        $this->assertSame('gorselli-dengeli-manset', $payload['heroMain']->slug);
    }

    public function test_homepage_editorial_blocks_do_not_repeat_articles(): void
    {
        $categories = collect([
            'gundem',
            'asayis',
            'ekonomi',
            'yasam',
        ])->mapWithKeys(fn (string $slug): array => [$slug => Category::create([
            'name' => ['tr' => $slug],
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 1,
        ])]);

        for ($i = 1; $i <= 28; $i++) {
            $slug = match (true) {
                $i % 4 === 0 => 'asayis',
                $i % 4 === 1 => 'gundem',
                $i % 4 === 2 => 'ekonomi',
                default => 'yasam',
            };

            NewsArticle::create([
                'title' => ['tr' => 'Haber '.$i],
                'slug' => 'haber-'.$i,
                'summary' => ['tr' => 'Ozet '.$i],
                'content' => ['tr' => 'Detay '.$i],
                'featured_image' => '/images/news-'.$i.'.jpg',
                'source' => 'manuel',
                'category_id' => $categories[$slug]->id,
                'status' => 'published',
                'published_at' => now()->subMinutes($i),
                'editorial_score' => 100 - $i,
                'city_code' => $i <= 8 ? IhaCategoryMapper::LOCALITY_LOCAL : IhaCategoryMapper::LOCALITY_REGION,
                'city_slug' => $i <= 8 ? 'adiyaman' : null,
            ]);
        }

        $payload = app(HomeModuleDataService::class)->collect([
            'modules' => [
                ['key' => 'hero', 'settings' => ['content_limit' => 4]],
                ['key' => 'local_news', 'settings' => ['content_limit' => 4]],
                ['key' => 'asayis_news', 'settings' => ['content_limit' => 4]],
                ['key' => 'region_news', 'settings' => ['content_limit' => 4]],
                ['key' => 'politics_economy', 'settings' => ['content_limit' => 4]],
                ['key' => 'life_digest', 'settings' => ['content_limit' => 4]],
                ['key' => 'latest_news', 'settings' => ['content_limit' => 4]],
            ],
        ]);

        $ids = collect([$payload['heroMain']?->id])
            ->merge($payload['heroSide']->pluck('id'))
            ->merge($payload['localNews']->pluck('id'))
            ->merge($payload['asayisNews']->pluck('id'))
            ->merge($payload['regionNews']->pluck('id'))
            ->merge($payload['politicsEconomyNews']->pluck('id'))
            ->merge($payload['lifeDigestNews']->pluck('id'))
            ->merge($payload['latestNews']->pluck('id'))
            ->filter()
            ->values();

        $this->assertSame($ids->count(), $ids->unique()->count());
    }

    public function test_active_homepage_exclusion_removes_article_from_editorial_blocks(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Dislanan haber'],
            'slug' => 'dislanan-haber',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Detay'],
            'featured_image' => '/images/excluded.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 100,
            'homepage_exclude_until' => now()->addHour(),
        ]);

        NewsArticle::create([
            'title' => ['tr' => 'Gorunen haber'],
            'slug' => 'gorunen-haber',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Detay'],
            'featured_image' => '/images/visible.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'editorial_score' => 50,
        ]);

        $payload = app(HomeModuleDataService::class)->collect([
            'modules' => [
                ['key' => 'hero', 'settings' => ['content_limit' => 2]],
                ['key' => 'latest_news', 'settings' => ['content_limit' => 4]],
            ],
        ]);

        $slugs = collect([$payload['heroMain']?->slug])
            ->merge($payload['latestNews']->pluck('slug'))
            ->filter()
            ->all();

        $this->assertNotContains('dislanan-haber', $slugs);
        $this->assertContains('gorunen-haber', $slugs);
    }
}
