<?php

namespace Tests\Feature\Commands;

use App\Jobs\SyncIhaNewsJob;
use App\Models\IhaSyncLog;
use App\Services\IhaApiService;
use App\Services\IhaCategoryMapper;
use App\Services\IhaImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncIhaNewsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_plain_iha_sync_queues_job_and_leaves_log_running(): void
    {
        config()->set('queue.default', 'database');
        Queue::fake();

        $exitCode = Artisan::call('iha:sync');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('IHA sync kuyruga alindi', $output);

        $log = IhaSyncLog::query()->latest('id')->firstOrFail();

        $this->assertSame('running', $log->status);
        $this->assertNull($log->completed_at);
        Queue::assertPushedOn('default', SyncIhaNewsJob::class);
    }

    public function test_inline_iha_sync_runs_synchronously_and_completes_a_log(): void
    {
        config()->set('queue.default', 'database');

        $this->mock(IhaApiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchNews')
                ->once()
                ->with(null, 0, true)
                ->andReturn([]);
        });

        $this->mock(IhaCategoryMapper::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('mapFromArticle');
            $mock->shouldNotReceive('localityScore');
            $mock->shouldNotReceive('detectCitySlug');
        });

        $this->mock(IhaImageService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('downloadImage');
        });

        $exitCode = Artisan::call('iha:sync', ['--inline' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('IHA sync tamamlandi', $output);

        $log = IhaSyncLog::query()->latest('id')->firstOrFail();

        $this->assertSame('partial', $log->status);
        $this->assertNotNull($log->completed_at);
        $this->assertSame(0, IhaSyncLog::query()->where('status', 'running')->count());
    }

    public function test_command_marks_stale_running_log_with_runtime_guidance(): void
    {
        config()->set('queue.default', 'database');
        Queue::fake();

        IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(25),
            'created_at' => now()->subMinutes(25),
        ]);

        Artisan::call('iha:sync');

        $staleLog = IhaSyncLog::query()->oldest('id')->firstOrFail();

        $this->assertSame('failed', $staleLog->status);
        $this->assertStringContainsString('20 dakikayi asti', $staleLog->error_message);
        Queue::assertPushedOn('default', SyncIhaNewsJob::class);
    }

    public function test_command_skips_non_stale_running_log_within_stale_window(): void
    {
        config()->set('queue.default', 'database');
        Queue::fake();

        IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(10),
            'created_at' => now()->subMinutes(10),
        ]);

        $exitCode = Artisan::call('iha:sync');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('IHA sync zaten calisiyor. Atlaniyor.', $output);
        $this->assertSame(1, IhaSyncLog::query()->where('status', 'running')->count());
        Queue::assertNothingPushed();
    }
}
