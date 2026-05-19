<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\BackupManager;
use App\Filament\Pages\CacheManagement;
use App\Filament\Resources\IhaSyncLogResource\Pages\ListIhaSyncLogs;
use App\Filament\Resources\NewsletterSubscriptionResource\Pages\ListNewsletterSubscriptions;
use App\Filament\Resources\SocialPublicationResource\Pages\ListSocialPublications;
use App\Jobs\PublishToInstagramJob;
use App\Models\AnalyticsPageView;
use App\Models\Category;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use App\Models\NewsletterSubscription;
use App\Models\SocialPublication;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AdminOperationsPagesOperationalClarityTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_pages_render_clean_operational_labels_without_mojibake(): void
    {
        $admin = $this->superAdmin();
        $article = $this->article();

        config([
            'services.iha.user_code' => 'ENV-CODE',
            'services.iha.username' => 'ENV-USER',
            'services.iha.password' => 'ENV-PASS',
        ]);

        NewsletterSubscription::query()->create([
            'email' => 'okur@example.test',
            'name' => 'Okur Test',
            'is_active' => true,
            'confirmed_at' => now(),
        ]);

        AnalyticsPageView::query()->create([
            'viewable_type' => NewsArticle::class,
            'viewable_id' => $article->id,
            'session_id' => 'ops-session',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'referer' => 'https://www.google.com/search?q=adh',
            'device_type' => 'mobile',
            'country' => 'TR',
            'viewed_at' => now()->subHour(),
        ]);

        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(12),
            'completed_at' => now()->subMinutes(11),
            'articles_fetched' => 8,
            'articles_created' => 2,
            'articles_updated' => 1,
            'articles_skipped' => 5,
            'images_downloaded' => 3,
        ]);

        SocialPublication::query()->updateOrCreate(
            [
                'news_article_id' => $article->id,
                'platform' => SocialPublication::PLATFORM_INSTAGRAM,
            ],
            [
                'status' => SocialPublication::STATUS_FAILED,
                'attempts' => 2,
                'error_message' => 'access_token=secret-token',
            ],
        );

        foreach ([
            '/admin/newsletter-subscriptions' => ['Bülten Aboneleri', 'Aktiflik', 'Onay Durumu'],
            '/admin/analytics' => ['Trafik ve karar destek görünümü', 'Yerel analytics_page_views', 'GA/GSC değildir'],
            '/admin/cache-management' => ['Önbellek Yönetimi', 'Cache store', 'Public page cache'],
            '/admin/backup-manager' => ['Yedekleme ve geri dönüş hazırlığı', 'Yalnız DB yedeği', 'Restore yok'],
            '/admin/iha-health' => ['İHA Sağlık Merkezi', 'Son koşu durumu', 'Config/env fallback hazır'],
            '/admin/iha-sync-logs' => ['İHA Senkron Kayıtları', 'Operasyon Riski', 'Bayat çalışan kayıtlar'],
            '/admin/social-publications' => ['Instagram Paylaşımları', 'Creative Durumu', 'Tekrar Dene'],
        ] as $path => $labels) {
            $response = $this->actingAs($admin)->get($path)->assertOk();

            foreach ($labels as $label) {
                $response->assertSee($label);
            }

            $this->assertDoesNotMatchRegularExpression('/(?:Ã|Ä|Å|â€|Â)/u', $response->getContent() ?: '', "{$path} renders mojibake.");
        }
    }

    public function test_newsletter_filters_and_bulk_status_actions_are_operational(): void
    {
        $this->actingAs($this->superAdmin());

        $activeConfirmed = NewsletterSubscription::query()->create([
            'email' => 'aktif@example.test',
            'name' => 'Aktif Okur',
            'is_active' => true,
            'confirmed_at' => now(),
        ]);

        $passiveUnconfirmed = NewsletterSubscription::query()->create([
            'email' => 'pasif@example.test',
            'name' => 'Pasif Okur',
            'is_active' => false,
            'confirmed_at' => null,
        ]);

        Livewire::test(ListNewsletterSubscriptions::class)
            ->filterTable('is_active', true)
            ->filterTable('confirmation_state', 'confirmed')
            ->assertCanSeeTableRecords([$activeConfirmed])
            ->assertCanNotSeeTableRecords([$passiveUnconfirmed]);

        Livewire::test(ListNewsletterSubscriptions::class)
            ->callTableBulkAction('deactivate', [$activeConfirmed]);

        $this->assertFalse($activeConfirmed->refresh()->is_active);

        Livewire::test(ListNewsletterSubscriptions::class)
            ->callTableBulkAction('activate', [$passiveUnconfirmed]);

        $this->assertTrue($passiveUnconfirmed->refresh()->is_active);
        $this->assertSame("'=cmd|calc", \App\Filament\Resources\NewsletterSubscriptionResource::escapeCsvCell('=cmd|calc'));
    }

    public function test_iha_sync_log_filters_and_errors_are_redacted(): void
    {
        $this->actingAs($this->superAdmin());

        $staleRunning = IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now()->subHours(3),
        ]);

        $failed = IhaSyncLog::query()->create([
            'status' => 'failed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
            'error_message' => 'password=secret-token&username=operator',
        ]);

        $success = IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(4),
        ]);

        Livewire::test(ListIhaSyncLogs::class)
            ->filterTable('risk_scope', 'stale_running')
            ->assertCanSeeTableRecords([$staleRunning])
            ->assertCanNotSeeTableRecords([$failed, $success]);

        $this->get('/admin/iha-sync-logs')
            ->assertOk()
            ->assertSee('[redacted]')
            ->assertDontSee('secret-token')
            ->assertDontSee('operator');
    }

    public function test_instagram_filters_retry_visibility_and_error_redaction(): void
    {
        Queue::fake();
        $this->actingAs($this->superAdmin());
        $article = $this->article();

        $failed = SocialPublication::query()->updateOrCreate(
            [
                'news_article_id' => $article->id,
                'platform' => SocialPublication::PLATFORM_INSTAGRAM,
            ],
            [
                'status' => SocialPublication::STATUS_FAILED,
                'attempts' => 3,
                'error_message' => 'token=instagram-secret',
            ],
        );

        $publishedArticle = $this->article('published-instagram');
        $published = SocialPublication::query()->updateOrCreate(
            [
                'news_article_id' => $publishedArticle->id,
                'platform' => SocialPublication::PLATFORM_INSTAGRAM,
            ],
            [
                'status' => SocialPublication::STATUS_PUBLISHED,
                'creative_image_url' => 'https://cdn.example.test/creative.jpg',
                'attempts' => 1,
                'published_at' => now(),
            ],
        );

        Livewire::test(ListSocialPublications::class)
            ->filterTable('status', SocialPublication::STATUS_FAILED)
            ->filterTable('creative_state', 'missing')
            ->filterTable('attempts', ['min_attempts' => 2])
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$published])
            ->assertTableActionVisible('retry', $failed)
            ->assertTableActionHidden('retry', $published);

        $this->get('/admin/social-publications')
            ->assertOk()
            ->assertSee('[redacted]')
            ->assertDontSee('instagram-secret');

        Livewire::test(ListSocialPublications::class)
            ->callTableAction('retry', $failed);

        $this->assertSame(SocialPublication::STATUS_PENDING, $failed->refresh()->status);
        Queue::assertPushed(PublishToInstagramJob::class);
    }

    public function test_cache_and_backup_actions_report_command_results_without_real_external_calls(): void
    {
        $this->actingAs($this->superAdmin());

        Artisan::shouldReceive('call')->once()->with('config:clear')->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('');

        Livewire::test(CacheManagement::class)->call('clearConfig');

        $kernel = Mockery::mock(ConsoleKernel::class);
        $kernel->shouldReceive('all')->andReturn(['backup:run' => true]);
        $this->app->instance(ConsoleKernel::class, $kernel);

        Artisan::shouldReceive('call')->once()->with('backup:run --only-db')->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('');

        app(BackupManager::class)->createBackup();

        $status = app(BackupManager::class)->getBackupStatus();
        $this->assertTrue($status['command_available']);
        $this->assertSame('Hazır', $status['readiness_label']);
    }

    private function superAdmin(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function article(string $slug = 'operasyon-test-haberi'): NewsArticle
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem-' . $slug,
            'is_active' => true,
        ]);

        return NewsArticle::query()->create([
            'title' => ['tr' => 'Operasyon Test Haberi'],
            'slug' => $slug,
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'İçerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
