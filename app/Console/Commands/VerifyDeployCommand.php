<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class VerifyDeployCommand extends Command
{
    protected $signature = 'deploy:verify {--base-url= : Override the base URL used for HTTP checks} {--sentry : Send a test event to Sentry}';
    protected $description = 'Post-deploy smoke test: checks app boot, DB, logs, health endpoint';

    public function handle(): int
    {
        $failed = false;

        $this->info('=== ADH Deploy Verification ===');

        $this->check('App boots', fn () => true, $failed);

        $this->check('App URL configured', function () {
            return filled(config('app.url'));
        }, $failed);

        $this->check('Production debug guard', function () {
            if (! app()->environment('production')) {
                return true;
            }

            return ! config('app.debug');
        }, $failed);

        $this->check('Database connection', function () {
            DB::connection()->getPdo();
            return true;
        }, $failed);

        $this->check('Log directory writable', function () {
            return is_writable(storage_path('logs'));
        }, $failed);

        $this->check('Cache read/write', function () {
            cache()->put('deploy_verify_test', 'ok', 10);
            return cache()->get('deploy_verify_test') === 'ok';
        }, $failed);

        $this->check('Queue configuration', function () {
            $driver = config('queue.default');
            if (! filled($driver)) {
                return false;
            }

            if ($driver === 'database') {
                return Schema::hasTable(config('queue.connections.database.table', 'jobs'));
            }

            return true;
        }, $failed);

        $this->check('Session cookie security', function () {
            $appUrl = (string) config('app.url', '');

            if (! str_starts_with($appUrl, 'https://')) {
                return true;
            }

            return (bool) config('session.secure') && in_array(config('session.same_site'), ['lax', 'strict', 'none'], true);
        }, $failed);

        $appUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');
        $this->check("Health endpoint ({$appUrl}/health)", function () use ($appUrl) {
            $response = Http::timeout(5)->get("{$appUrl}/health");
            return $response->ok()
                && $response->json('status') === 'ok'
                && $response->json('database') === 'ok'
                && $response->json('cache') === 'ok';
        }, $failed);

        $this->check("Homepage responds ({$appUrl}/)", function () use ($appUrl) {
            return Http::timeout(10)->get("{$appUrl}/")->successful();
        }, $failed);

        $this->check("Robots responds ({$appUrl}/robots.txt)", function () use ($appUrl) {
            $response = Http::timeout(5)->get("{$appUrl}/robots.txt");

            return $response->ok()
                && str_contains($response->body(), 'Sitemap:');
        }, $failed);

        $this->check("Sitemap responds ({$appUrl}/sitemap.xml)", function () use ($appUrl) {
            $response = Http::timeout(10)->get("{$appUrl}/sitemap.xml");

            return $response->ok()
                && str_contains($response->header('Content-Type', ''), 'xml')
                && str_contains($response->body(), '<urlset');
        }, $failed);

        if ($this->option('sentry')) {
            $this->check('Sentry test event', function () {
                if (!config('sentry.dsn')) {
                    $this->warn('  SENTRY_LARAVEL_DSN not set — skipping');
                    return true;
                }
                \Sentry\captureMessage('deploy:verify test event from ADH');
                return true;
            }, $failed);
        }

        $this->newLine();
        if ($failed) {
            $this->error('Some checks FAILED. Review above.');
            return Command::FAILURE;
        }

        $this->info('All checks passed.');
        return Command::SUCCESS;
    }

    private function check(string $name, callable $test, bool &$failed): void
    {
        try {
            if ($test()) {
                $this->line("  [OK] {$name}");
            } else {
                $this->error("  [FAIL] {$name}");
                $failed = true;
            }
        } catch (\Throwable $e) {
            $this->error("  [FAIL] {$name}: {$e->getMessage()}");
            $failed = true;
        }
    }
}
