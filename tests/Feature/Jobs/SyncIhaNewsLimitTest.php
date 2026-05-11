<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncIhaNewsJob;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Services\IhaApiService;
use App\Services\IhaCategoryMapper;
use App\Services\IhaImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncIhaNewsLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_iha_news_job_respects_limit_when_processing_feed_items(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([
                    [
                        'iha_id' => 'IHA-LIMIT-1',
                        'title' => 'Limit Bir',
                        'summary' => 'Ozet 1',
                        'content' => 'Icerik 1',
                        'image_url' => null,
                        'source_url' => 'https://www.iha.com.tr/limit-1',
                        'published_at' => now(),
                        'son_dakika' => false,
                    ],
                    [
                        'iha_id' => 'IHA-LIMIT-2',
                        'title' => 'Limit Iki',
                        'summary' => 'Ozet 2',
                        'content' => 'Icerik 2',
                        'image_url' => null,
                        'source_url' => 'https://www.iha.com.tr/limit-2',
                        'published_at' => now(),
                        'son_dakika' => false,
                    ],
                ]);
        });

        $categoryMapper = $this->mock(IhaCategoryMapper::class, function (MockInterface $mock) use ($category): void {
            $mock->shouldReceive('mapFromArticle')->once()->andReturn($category->id);
            $mock->shouldReceive('localityScore')->once()->andReturn(3);
            $mock->shouldReceive('detectCitySlug')->once()->andReturn('adiyaman');
        });

        $imageService = $this->mock(IhaImageService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('downloadImage')->never();
        });

        (new SyncIhaNewsJob(null, 0, 1))->handle($apiService, $categoryMapper, $imageService);

        $this->assertSame(1, NewsArticle::query()->count());
        $this->assertDatabaseHas('news_articles', ['iha_id' => 'IHA-LIMIT-1']);
        $this->assertDatabaseMissing('news_articles', ['iha_id' => 'IHA-LIMIT-2']);
    }
}
