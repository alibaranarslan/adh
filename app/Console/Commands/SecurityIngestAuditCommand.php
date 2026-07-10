<?php

namespace App\Console\Commands;

use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SecurityIngestAuditCommand extends Command
{
    protected $signature = 'adh:security-ingest-audit
        {--base-url= : HTTP smoke icin kontrol edilecek base URL}
        {--freshness-minutes=60 : Son basarili IHA sync icin kabul edilen dakika siniri}
        {--skip-http : HTTP security header smoke kontrolunu atla}';

    protected $description = 'ADH guvenlik mimarisi ve IHA haber akisi surekliligi icin operasyon denetimi';

    public function handle(): int
    {
        $failed = false;
        $warned = false;

        $this->info('=== ADH Security + Ingest Audit ===');

        $this->check('App key configured', filled(config('app.key')), $failed);
        $this->check('Production debug is disabled', ! app()->environment('production') || ! config('app.debug'), $failed);
        $this->check('Production app URL uses HTTPS', ! app()->environment('production') || str_starts_with((string) config('app.url'), 'https://'), $failed);
        $this->check('Queue uses database driver', config('queue.default') === 'database', $failed);
        $this->check('Jobs table exists', Schema::hasTable(config('queue.connections.database.table', 'jobs')), $failed);
        $this->check('Session secure cookie follows HTTPS', ! str_starts_with((string) config('app.url'), 'https://') || (bool) config('session.secure'), $failed);

        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $this->warnIf('No failed queue jobs', $failedJobs === 0, $warned, "{$failedJobs} failed job kaydi var");

        if (! $this->option('skip-http')) {
            $this->httpChecks($failed);
        }

        $this->ingestChecks($failed, $warned);

        $this->newLine();
        if ($failed) {
            $this->error('SECURITY_INGEST_AUDIT status=fail');

            return self::FAILURE;
        }

        $this->info('SECURITY_INGEST_AUDIT status=' . ($warned ? 'warn' : 'pass'));

        return self::SUCCESS;
    }

    private function httpChecks(bool &$failed): void
    {
        $baseUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');

        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $this->warn("  [WARN] HTTP security header smoke skipped: base URL gecersiz ({$baseUrl})");

            return;
        }

        $response = Http::timeout(10)->get("{$baseUrl}/");

        $this->check("Homepage responds ({$baseUrl}/)", $response->successful(), $failed);
        $this->check('Security header: X-Content-Type-Options', $response->header('X-Content-Type-Options') === 'nosniff', $failed);
        $this->check('Security header: X-Frame-Options', $response->header('X-Frame-Options') === 'SAMEORIGIN', $failed);
        $this->check('Security header: Referrer-Policy', filled($response->header('Referrer-Policy')), $failed);
        $this->check('Security header: Permissions-Policy', filled($response->header('Permissions-Policy')), $failed);
        $this->check('Security header: CSP', filled($response->header('Content-Security-Policy')) || filled($response->header('Content-Security-Policy-Report-Only')), $failed);

        if (str_starts_with($baseUrl, 'https://')) {
            $this->check('Security header: HSTS', filled($response->header('Strict-Transport-Security')), $failed);
        }
    }

    private function ingestChecks(bool &$failed, bool &$warned): void
    {
        $freshnessLimit = max(1, (int) $this->option('freshness-minutes'));
        $latestSync = IhaSyncLog::query()->latest('id')->first();
        $latestSuccess = IhaSyncLog::query()->whereIn('status', ['success', 'partial'])->latest('completed_at')->first();
        $running = IhaSyncLog::query()->where('status', 'running')->latest('started_at')->first();

        $this->check('IHA sync log exists', $latestSync !== null, $failed);
        $this->check('Latest IHA sync is not failed', $latestSync === null || $latestSync->status !== 'failed', $failed);

        if ($running?->started_at) {
            $runningAge = (int) $running->started_at->diffInMinutes(now());
            $this->check('No stale running IHA sync', $runningAge < 120, $failed);
            $this->warnIf('No long-running IHA sync warning', $runningAge < 30, $warned, "running_age_minutes={$runningAge}");
        } else {
            $this->line('  [OK] No running IHA sync');
        }

        if ($latestSuccess?->completed_at) {
            $freshness = (int) $latestSuccess->completed_at->diffInMinutes(now());
            $this->check('IHA freshness within threshold', $freshness <= $freshnessLimit, $failed);
            $this->line("  [INFO] IHA freshness_minutes={$freshness}");
        } else {
            $this->check('IHA has completed success/partial sync', false, $failed);
        }

        $publishedCount = NewsArticle::query()->where('status', 'published')->count();
        $ihaWindow = NewsArticle::query()
            ->where('source', 'iha')
            ->latest('created_at')
            ->limit(20)
            ->get();

        $this->check('Published news exists', $publishedCount > 0, $failed);
        $this->check('Recent IHA article window exists', $ihaWindow->isNotEmpty(), $failed);

        $minBodyLength = max(1, (int) config('services.iha.min_body_length', 280));
        $empty = 0;
        $weak = 0;
        $short = 0;

        foreach ($ihaWindow as $article) {
            $summaryLen = mb_strlen(strip_tags((string) ($article->getTranslation('summary', 'tr', false) ?? '')));
            $contentLen = mb_strlen(strip_tags((string) ($article->getTranslation('content', 'tr', false) ?? '')));

            $empty += $contentLen === 0 ? 1 : 0;
            $weak += $contentLen > 0 && $contentLen <= $summaryLen ? 1 : 0;
            $short += $contentLen > 0 && $contentLen < $minBodyLength ? 1 : 0;
        }

        $this->check('No empty IHA body in recent window', $empty === 0, $failed);
        $this->check('No weak IHA body in recent window', $weak === 0, $failed);
        $this->warnIf('No short IHA body in recent window', $short === 0, $warned, "short_body_count={$short}");
        $this->line("  [INFO] published_news={$publishedCount} iha_window={$ihaWindow->count()} empty_content={$empty} weak_body={$weak} short_body={$short}");
    }

    private function check(string $name, bool $passed, bool &$failed): void
    {
        if ($passed) {
            $this->line("  [OK] {$name}");

            return;
        }

        $failed = true;
        $this->error("  [FAIL] {$name}");
    }

    private function warnIf(string $name, bool $passed, bool &$warned, string $detail): void
    {
        if ($passed) {
            $this->line("  [OK] {$name}");

            return;
        }

        $warned = true;
        $this->warn("  [WARN] {$name}: {$detail}");
    }
}
