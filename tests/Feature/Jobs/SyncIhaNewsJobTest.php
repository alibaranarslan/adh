<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncIhaNewsJob;
use App\Jobs\TranslateArticleJob;
use App\Models\Category;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use App\Services\IhaApiService;
use App\Services\IhaCategoryMapper;
use App\Services\IhaImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncIhaNewsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_iha_news_job_creates_meta_translations_source_and_dispatches_translation_job(): void
    {
        Bus::fake();
        config(['services.iha.min_body_length' => 1]);

        $category = Category::create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([[
                    'iha_id' => 'IHA-100',
                    'title' => 'İHA Test Başlığı',
                    'summary' => 'İHA test özeti',
                    'content' => 'İHA test içeriği',
                    'image_url' => null,
                    'source_url' => 'https://www.iha.com.tr/test',
                    'published_at' => now(),
                    'son_dakika' => false,
                ]]);
        });

        $categoryMapper = $this->mock(IhaCategoryMapper::class, function (MockInterface $mock) use ($category): void {
            $mock->shouldReceive('mapFromArticle')->once()->andReturn($category->id);
            $mock->shouldReceive('localityScore')->once()->andReturn(2);
            $mock->shouldReceive('detectCitySlug')->once()->andReturn('adiyaman');
        });

        $imageService = $this->mock(IhaImageService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('downloadImage')->never();
        });

        (new SyncIhaNewsJob())->handle($apiService, $categoryMapper, $imageService);

        $article = NewsArticle::query()->where('iha_id', 'IHA-100')->firstOrFail();

        $this->assertSame('İHA Test Başlığı', $article->getTranslation('meta_title', 'tr'));
        $this->assertSame('İHA test özeti', $article->getTranslation('meta_description', 'tr'));

        Bus::assertDispatched(TranslateArticleJob::class);
    }

    public function test_sync_iha_news_job_updates_existing_iha_article_without_creating_duplicate(): void
    {
        Bus::fake();
        config(['services.iha.min_body_length' => 1]);

        $category = Category::create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $existing = NewsArticle::query()->create([
            'iha_id' => 'IHA-200',
            'title' => ['tr' => 'Eski Başlık'],
            'slug' => 'iha-200-eski-baslik',
            'summary' => ['tr' => 'Eski özet'],
            'content' => ['tr' => 'Eski içerik'],
            'meta_title' => ['tr' => 'Eski Başlık'],
            'meta_description' => ['tr' => 'Eski meta'],
            'source' => 'iha',
            'source_url' => 'https://www.iha.com.tr/eski',
            'category_id' => $category->id,
            'city_code' => 3,
            'city_slug' => 'adiyaman',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([[
                    'iha_id' => 'IHA-200',
                    'title' => 'Güncel Başlık',
                    'summary' => 'Güncel özet',
                    'content' => 'Güncel içerik',
                    'image_url' => null,
                    'source_url' => 'https://www.iha.com.tr/guncel',
                    'published_at' => now(),
                    'son_dakika' => false,
                ]]);
        });

        $categoryMapper = $this->mock(IhaCategoryMapper::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('mapFromArticle');
            $mock->shouldReceive('localityScore')->once()->andReturn(IhaCategoryMapper::LOCALITY_REGION);
            $mock->shouldReceive('detectCitySlug')->once()->andReturn('malatya');
        });

        $imageService = $this->mock(IhaImageService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('downloadImage')->never();
        });

        (new SyncIhaNewsJob())->handle($apiService, $categoryMapper, $imageService);

        $this->assertSame(1, NewsArticle::query()->where('iha_id', 'IHA-200')->count());

        $existing->refresh();

        $this->assertSame($existing->id, NewsArticle::query()->where('iha_id', 'IHA-200')->value('id'));
        $this->assertSame('Güncel Başlık', $existing->getTranslation('title', 'tr'));
        $this->assertSame('Güncel özet', $existing->getTranslation('summary', 'tr'));

        $this->assertSame(IhaCategoryMapper::LOCALITY_REGION, $existing->city_code);
        $this->assertSame('malatya', $existing->city_slug);
        $this->assertIsInt($existing->editorial_score);

        Bus::assertDispatched(TranslateArticleJob::class, fn (TranslateArticleJob $job) => true);
    }

    public function test_sync_iha_news_job_skips_when_log_is_no_longer_running(): void
    {
        $syncLog = IhaSyncLog::query()->create([
            'status' => 'failed',
            'started_at' => now()->subHours(2),
            'completed_at' => now(),
            'error_message' => 'stale',
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('fetchNews');
        });

        $categoryMapper = app(IhaCategoryMapper::class);
        $imageService = app(IhaImageService::class);

        (new SyncIhaNewsJob(null, $syncLog->id))->handle($apiService, $categoryMapper, $imageService);

        $this->assertDatabaseHas('iha_sync_logs', [
            'id' => $syncLog->id,
            'status' => 'failed',
            'error_message' => 'stale',
        ]);
    }
}
