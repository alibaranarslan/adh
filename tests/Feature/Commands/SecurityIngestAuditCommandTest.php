<?php

namespace Tests\Feature\Commands;

use App\Models\Category;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SecurityIngestAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_ingest_audit_passes_for_healthy_local_state(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'queue.default' => 'database',
            'services.iha.min_body_length' => 100,
        ]);

        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(2),
            'articles_fetched' => 2,
            'articles_created' => 2,
            'created_at' => now()->subMinutes(5),
        ]);

        foreach (['birinci-iha-haber', 'ikinci-iha-haber'] as $slug) {
            NewsArticle::query()->create([
                'iha_id' => $slug,
                'title' => ['tr' => str($slug)->replace('-', ' ')->title()->toString()],
                'slug' => $slug,
                'summary' => ['tr' => 'Kisa ozet'],
                'content' => ['tr' => str_repeat('Govde ', 80)],
                'source' => 'iha',
                'source_url' => 'https://www.iha.com.tr',
                'category_id' => $category->id,
                'status' => 'published',
                'published_at' => now()->subMinutes(2),
                'created_at' => now()->subMinutes(2),
            ]);
        }

        $exitCode = Artisan::call('adh:security-ingest-audit', ['--skip-http' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('SECURITY_INGEST_AUDIT status=pass', $output);
        $this->assertStringContainsString('IHA freshness within threshold', $output);
        $this->assertStringContainsString('No empty IHA body in recent window', $output);
    }

    public function test_security_ingest_audit_fails_for_missing_ingest_evidence(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'queue.default' => 'database',
        ]);

        $exitCode = Artisan::call('adh:security-ingest-audit', ['--skip-http' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('SECURITY_INGEST_AUDIT status=fail', $output);
        $this->assertStringContainsString('IHA sync log exists', $output);
        $this->assertStringContainsString('Published news exists', $output);
    }
}
