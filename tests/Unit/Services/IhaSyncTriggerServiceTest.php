<?php

namespace Tests\Unit\Services;

use App\Models\IhaSyncLog;
use App\Services\IhaSyncTriggerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class IhaSyncTriggerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trigger_queued_returns_running_summary_from_new_log(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('iha:sync')
            ->andReturnUsing(function (): int {
                IhaSyncLog::query()->create([
                    'status' => 'running',
                    'started_at' => now()->subSeconds(5),
                ]);

                return 0;
            });

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('IHA sync kuyruga alindi.');

        $result = app(IhaSyncTriggerService::class)->triggerQueued();

        $this->assertSame('running', $result['status']);
        $this->assertSame('IHA senkronu kuyruga alindi', $result['title']);
        $this->assertStringContainsString('Log #', $result['body']);
        $this->assertStringContainsString('Durum: running', $result['body']);
    }

    public function test_trigger_queued_returns_skipped_when_no_new_log_is_created(): void
    {
        IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(2),
            'created_at' => now()->subMinutes(2),
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('iha:sync')
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('IHA sync zaten calisiyor. Atlaniyor.');

        $result = app(IhaSyncTriggerService::class)->triggerQueued();

        $this->assertSame('skipped', $result['status']);
        $this->assertStringContainsString('zaten calisiyor', $result['title']);
    }

    public function test_trigger_queued_surfaces_failed_log_status(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('iha:sync')
            ->andReturnUsing(function (): int {
                IhaSyncLog::query()->create([
                    'status' => 'failed',
                    'started_at' => now()->subSeconds(5),
                    'completed_at' => now(),
                    'articles_fetched' => 10,
                    'articles_created' => 0,
                    'articles_updated' => 0,
                    'articles_skipped' => 10,
                    'images_downloaded' => 0,
                    'error_message' => 'Kategori referanslari eksik. password=secret-token token=abc',
                ]);

                return 1;
            });

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('IHA sync basarisiz.');

        $result = app(IhaSyncTriggerService::class)->triggerQueued();

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('Kategori referanslari eksik.', $result['body']);
        $this->assertStringContainsString('password=[redacted]', $result['body']);
        $this->assertStringContainsString('token=[redacted]', $result['body']);
        $this->assertStringNotContainsString('secret-token', $result['body']);
        $this->assertSame(1, $result['exit_code']);
    }
}
