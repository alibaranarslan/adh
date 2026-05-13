<?php

namespace Tests\Feature\Commands;

use App\Models\Category;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MonitorIhaForwardIngestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reports_forward_ingest_metrics_for_recent_iha_window(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
        ]);

        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(10),
            'articles_fetched' => 5,
            'articles_created' => 4,
            'articles_updated' => 1,
            'articles_skipped' => 0,
            'error_message' => 'QUALITY_RISK affected=1 empty_content=0 body_not_deeper_than_summary=1 short_body=1 examples=ikinci-haber',
            'created_at' => now()->subMinutes(20),
        ]);

        NewsArticle::query()->create([
            'iha_id' => 'iha-1',
            'title' => ['tr' => 'Birinci haber'],
            'slug' => 'birinci-haber',
            'summary' => ['tr' => 'Kisa ozet'],
            'content' => ['tr' => 'Bu biraz daha uzun bir govdedir.'],
            'meta_title' => ['tr' => 'Birinci haber'],
            'meta_description' => ['tr' => 'Kisa ozet'],
            'source' => 'iha',
            'source_url' => 'https://www.iha.com.tr',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(9),
            'created_at' => now()->subMinutes(9),
            'updated_at' => now()->subMinutes(9),
        ]);

        NewsArticle::query()->create([
            'iha_id' => 'iha-2',
            'title' => ['tr' => 'Ikinci haber'],
            'slug' => 'ikinci-haber',
            'summary' => ['tr' => 'Bu biraz daha uzun bir ozet metni'],
            'content' => ['tr' => ''],
            'meta_title' => ['tr' => 'Ikinci haber'],
            'meta_description' => ['tr' => 'Bu biraz daha uzun bir ozet metni'],
            'source' => 'iha',
            'source_url' => 'https://example.com/article',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(8),
            'created_at' => now()->subMinutes(8),
            'updated_at' => now()->subMinutes(8),
        ]);

        NewsArticle::query()->create([
            'iha_id' => 'manual-1',
            'title' => ['tr' => 'Manual haber'],
            'slug' => 'manual-haber',
            'summary' => ['tr' => 'Manual ozet'],
            'content' => ['tr' => 'Manual govde'],
            'meta_title' => ['tr' => 'Manual haber'],
            'meta_description' => ['tr' => 'Manual ozet'],
            'source' => 'manuel',
            'source_url' => 'https://example.com/manual',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(7),
            'created_at' => now()->subMinutes(7),
            'updated_at' => now()->subMinutes(7),
        ]);

        $exitCode = Artisan::call('iha:monitor-forward', ['--limit' => 2]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('IHA_FORWARD_MONITOR health=critical', $output);
        $this->assertStringContainsString('sync_status=success', $output);
        $this->assertStringContainsString('quality_risk=yes quality_affected=1', $output);
        $this->assertStringContainsString('fetched=5 created=4 updated=1 skipped=0', $output);
        $this->assertStringContainsString('window=2 empty_content=1 weak_body=0 short_body=1', $output);
        $this->assertStringContainsString('body_depth_ratio=0.50', $output);
        $this->assertStringContainsString('generic_source_url_ratio=0.50', $output);
        $this->assertStringContainsString('Recent IHA Window (last 2 by created_at)', $output);
        $this->assertStringContainsString('quality_note: QUALITY_RISK affected=1 empty_content=0 body_not_deeper_than_summary=1 short_body=1 examples=ikinci-haber', $output);
    }

    public function test_command_uses_configured_short_body_threshold(): void
    {
        config(['services.iha.min_body_length' => 100]);

        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
        ]);

        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(10),
            'articles_fetched' => 1,
            'articles_created' => 1,
            'articles_updated' => 0,
            'articles_skipped' => 0,
            'created_at' => now()->subMinutes(20),
        ]);

        NewsArticle::query()->create([
            'iha_id' => 'iha-threshold',
            'title' => ['tr' => 'Esik haber'],
            'slug' => 'esik-haber',
            'summary' => ['tr' => 'Kisa'],
            'content' => ['tr' => str_repeat('G', 120)],
            'meta_title' => ['tr' => 'Esik haber'],
            'meta_description' => ['tr' => 'Kisa'],
            'source' => 'iha',
            'source_url' => 'https://example.com/article',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(8),
            'created_at' => now()->subMinutes(8),
            'updated_at' => now()->subMinutes(8),
        ]);

        $exitCode = Artisan::call('iha:monitor-forward', ['--limit' => 1]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('health=healthy', $output);
        $this->assertStringContainsString('short_body=0', $output);
    }

    public function test_running_sync_older_than_short_grace_period_reports_warn(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
        ]);

        IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(45),
            'created_at' => now()->subMinutes(45),
        ]);

        NewsArticle::query()->create([
            'iha_id' => 'iha-running-warn',
            'title' => ['tr' => 'Running warn haber'],
            'slug' => 'running-warn-haber',
            'summary' => ['tr' => 'Kisa'],
            'content' => ['tr' => str_repeat('Govde ', 80)],
            'meta_title' => ['tr' => 'Running warn haber'],
            'meta_description' => ['tr' => 'Kisa'],
            'source' => 'iha',
            'source_url' => 'https://example.com/article',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(8),
            'created_at' => now()->subMinutes(8),
            'updated_at' => now()->subMinutes(8),
        ]);

        $exitCode = Artisan::call('iha:monitor-forward', ['--limit' => 1]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('health=warn sync_status=running running_age_minutes=45', $output);
    }

    public function test_stale_running_sync_reports_critical(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
        ]);

        IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(130),
            'created_at' => now()->subMinutes(130),
        ]);

        NewsArticle::query()->create([
            'iha_id' => 'iha-running-critical',
            'title' => ['tr' => 'Running critical haber'],
            'slug' => 'running-critical-haber',
            'summary' => ['tr' => 'Kisa'],
            'content' => ['tr' => str_repeat('Govde ', 80)],
            'meta_title' => ['tr' => 'Running critical haber'],
            'meta_description' => ['tr' => 'Kisa'],
            'source' => 'iha',
            'source_url' => 'https://example.com/article',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(8),
            'created_at' => now()->subMinutes(8),
            'updated_at' => now()->subMinutes(8),
        ]);

        $exitCode = Artisan::call('iha:monitor-forward', ['--limit' => 1]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('health=critical sync_status=running running_age_minutes=130', $output);
    }
}
