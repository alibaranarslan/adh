<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncIhaNewsJob;
use App\Models\Category;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use App\Services\IhaApiService;
use App\Services\IhaCategoryMapper;
use App\Services\IhaImageService;
use App\Services\IhaSyncException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class IhaSyncLogStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_marks_log_as_partial_when_feed_returns_no_articles(): void
    {
        $syncLog = IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([]);
        });

        $categoryMapper = $this->mock(IhaCategoryMapper::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('mapFromArticle');
            $mock->shouldNotReceive('localityScore');
            $mock->shouldNotReceive('detectCitySlug');
        });

        $imageService = $this->mock(IhaImageService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('downloadImage');
        });

        (new SyncIhaNewsJob(null, $syncLog->id))->handle($apiService, $categoryMapper, $imageService);

        $syncLog->refresh();

        $this->assertSame('partial', $syncLog->status);
        $this->assertSame(0, $syncLog->articles_fetched);
        $this->assertNotNull($syncLog->error_message);
    }

    public function test_sync_marks_log_as_failed_when_feed_request_throws(): void
    {
        $syncLog = IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andThrow(new IhaSyncException('IHA feed request failed'));
        });

        $categoryMapper = $this->mock(IhaCategoryMapper::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('mapFromArticle');
            $mock->shouldNotReceive('localityScore');
            $mock->shouldNotReceive('detectCitySlug');
        });

        $imageService = $this->mock(IhaImageService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('downloadImage');
        });

        (new SyncIhaNewsJob(null, $syncLog->id))->handle($apiService, $categoryMapper, $imageService);

        $syncLog->refresh();

        $this->assertSame('failed', $syncLog->status);
        $this->assertSame('IHA feed request failed', $syncLog->error_message);
    }

    public function test_sync_marks_log_as_failed_when_all_articles_fail_during_processing(): void
    {
        $syncLog = IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([[
                    'iha_id' => 'IHA-FK-1',
                    'title' => 'FK Test',
                    'summary' => 'Ozet',
                    'content' => 'Icerik',
                    'image_url' => null,
                    'source_url' => 'https://www.iha.com.tr/fk-test',
                    'published_at' => now(),
                    'son_dakika' => false,
                ]]);
        });

        $categoryMapper = $this->mock(IhaCategoryMapper::class, function (MockInterface $mock): void {
            $mock->shouldReceive('mapFromArticle')->once()->andReturn(999);
            $mock->shouldReceive('localityScore')->once()->andReturn(2);
            $mock->shouldReceive('detectCitySlug')->once()->andReturn('adiyaman');
        });

        $imageService = $this->mock(IhaImageService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('downloadImage');
        });

        (new SyncIhaNewsJob(null, $syncLog->id))->handle($apiService, $categoryMapper, $imageService);

        $syncLog->refresh();

        $this->assertSame('failed', $syncLog->status);
        $this->assertSame(1, $syncLog->articles_fetched);
        $this->assertSame(1, $syncLog->articles_skipped);
        $this->assertStringContainsString('Ilk hata:', (string) $syncLog->error_message);
    }

    public function test_sync_keeps_success_status_but_records_quality_risk_when_body_is_weak(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $syncLog = IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([[
                    'iha_id' => 'IHA-WEAK-1',
                    'title' => 'Zayif Govde Testi',
                    'summary' => 'Bu ozet govde ile ayni kalsin.',
                    'content' => 'Bu ozet govde ile ayni kalsin.',
                    'image_url' => null,
                    'source_url' => 'https://www.iha.com.tr/zayif-govde-testi',
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
            $mock->shouldNotReceive('downloadImage');
        });

        (new SyncIhaNewsJob(null, $syncLog->id))->handle($apiService, $categoryMapper, $imageService);

        $syncLog->refresh();

        $this->assertSame('success', $syncLog->status);
        $this->assertStringContainsString('QUALITY_RISK', (string) $syncLog->error_message);
        $this->assertStringContainsString('affected=1', (string) $syncLog->error_message);
        $this->assertStringContainsString('body_not_deeper_than_summary=1', (string) $syncLog->error_message);
        $this->assertStringContainsString('short_body=1', (string) $syncLog->error_message);
        $this->assertStringContainsString('min_body_length=280', (string) $syncLog->error_message);
        $this->assertSame('draft', NewsArticle::query()->where('iha_id', 'IHA-WEAK-1')->value('status'));
    }

    public function test_sync_records_quality_risk_when_content_is_empty(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $syncLog = IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([[
                    'iha_id' => 'IHA-EMPTY-1',
                    'title' => 'Bos Govde Testi',
                    'summary' => 'Ozet var ama govde yok.',
                    'content' => '',
                    'image_url' => null,
                    'source_url' => 'https://www.iha.com.tr/bos-govde-testi',
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
            $mock->shouldNotReceive('downloadImage');
        });

        (new SyncIhaNewsJob(null, $syncLog->id))->handle($apiService, $categoryMapper, $imageService);

        $syncLog->refresh();

        $this->assertSame('success', $syncLog->status);
        $this->assertStringContainsString('QUALITY_RISK', (string) $syncLog->error_message);
        $this->assertStringContainsString('affected=1', (string) $syncLog->error_message);
        $this->assertStringContainsString('empty_content=1', (string) $syncLog->error_message);
        $this->assertSame('draft', NewsArticle::query()->where('iha_id', 'IHA-EMPTY-1')->value('status'));
    }

    public function test_sync_leaves_quality_note_empty_when_body_is_healthy(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $syncLog = IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        $apiService = $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([[
                    'iha_id' => 'IHA-HEALTHY-1',
                    'title' => 'Saglikli Govde Testi',
                    'summary' => 'Kisa ozet.',
                    'content' => str_repeat('Uzun govde cumlesi. ', 20),
                    'image_url' => null,
                    'source_url' => 'https://www.iha.com.tr/saglikli-govde-testi',
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
            $mock->shouldNotReceive('downloadImage');
        });

        (new SyncIhaNewsJob(null, $syncLog->id))->handle($apiService, $categoryMapper, $imageService);

        $syncLog->refresh();

        $this->assertSame('success', $syncLog->status);
        $this->assertNull($syncLog->error_message);
    }
}
