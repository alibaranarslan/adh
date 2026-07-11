<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\IhaHealth;
use App\Models\AdminOperationAudit;
use App\Models\Category;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Models\User;
use App\Jobs\TranslateArticleJob;
use App\Support\AdminOperationAuditor;
use App\Support\AdminPrivileges;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOperationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_manager_can_run_operations_without_system_settings_access(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('client_manager');

        $this->assertTrue(AdminPrivileges::canAccessAdminPanel($manager));
        $this->assertTrue(AdminPrivileges::canAccessConfiguration($manager));
        $this->assertTrue(AdminPrivileges::canPublishConfiguration($manager));
        $this->assertTrue(AdminPrivileges::canManageOperations($manager));
        $this->assertFalse(AdminPrivileges::canManageSystemSettings($manager));

        $this->actingAs($manager)->get('/admin/iha-health')->assertOk();
        $this->actingAs($manager)->get('/admin/admin-operation-audits')->assertOk();
        $this->actingAs($manager)->get('/admin/cache-management')->assertForbidden();
        $this->actingAs($manager)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_operation_audit_records_sanitized_context(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'email' => 'adh-audit-admin@example.test',
        ]);

        $this->actingAs($admin);

        AdminOperationAuditor::record(
            'iha.manual_test_sync',
            null,
            [
                'status' => 'success',
                'iha_password' => 'plain-secret',
                'nested' => ['access_token' => 'real-token'],
            ],
            'simulated',
            'İHA test senkronu'
        );

        $audit = AdminOperationAudit::query()->firstOrFail();

        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame('iha.manual_test_sync', $audit->action);
        $this->assertSame('simulated', $audit->status);
        $this->assertSame('[redacted]', $audit->context['iha_password']);
        $this->assertSame('[redacted]', $audit->context['nested']['access_token']);
        $this->assertSame('success', $audit->context['status']);
    }

    public function test_iha_health_queued_sync_action_records_operation_audit(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('iha:sync')
            ->andReturnUsing(function (): int {
                IhaSyncLog::query()->create([
                    'status' => 'running',
                    'started_at' => now(),
                    'created_at' => now(),
                ]);

                return 0;
            });

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('IHA sync kuyruga alindi.');

        $this->actingAs($admin);

        Livewire::test(IhaHealth::class)
            ->callAction('queue_sync');

        $audit = AdminOperationAudit::query()->where('action', 'iha.queued_sync')->firstOrFail();

        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame('success', $audit->status);
        $this->assertSame('running', $audit->context['status']);
        $this->assertNotNull($audit->context['log_id']);
    }

    public function test_iha_health_manual_test_sync_action_records_operation_audit(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('iha:sync', [
                '--inline' => true,
                '--force' => true,
                '--limit' => 5,
            ])
            ->andReturnUsing(function (): int {
                IhaSyncLog::query()->create([
                    'status' => 'success',
                    'started_at' => now()->subSeconds(5),
                    'completed_at' => now(),
                    'articles_fetched' => 3,
                    'articles_created' => 2,
                    'articles_updated' => 1,
                    'articles_skipped' => 0,
                    'images_downloaded' => 2,
                    'created_at' => now(),
                ]);

                return 0;
            });

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('IHA test senkronu tamamlandi.');

        $this->actingAs($admin);

        Livewire::test(IhaHealth::class)
            ->callAction('manual_sync');

        $audit = AdminOperationAudit::query()->where('action', 'iha.manual_test_sync')->firstOrFail();

        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame('simulated', $audit->status);
        $this->assertSame('success', $audit->context['status']);
        $this->assertStringContainsString('Log #', $audit->context['body']);
        $this->assertStringContainsString('Yeni: 2', $audit->context['body']);
    }

    public function test_iha_health_translation_flow_is_blocked_without_google_api_key(): void
    {
        config(['services.google_translate.api_key' => null]);
        Queue::fake();

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(IhaHealth::class)
            ->callAction('start_translation_flow');

        $audit = AdminOperationAudit::query()->where('action', 'iha.translation_requeue_blocked')->firstOrFail();

        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame('blocked', $audit->status);
        $this->assertSame('missing_google_translation_api_key', $audit->context['reason']);

        Queue::assertNothingPushed();
    }

    public function test_iha_health_translation_flow_queues_missing_iha_translations_when_ready(): void
    {
        config(['services.google_translate.api_key' => null]);

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        NewsArticle::query()->create([
            'iha_id' => 'IHA-TRANSLATION-1',
            'title' => ['tr' => 'Adıyaman için önemli gelişme'],
            'slug' => 'adiyaman-icin-onemli-gelisme',
            'summary' => ['tr' => 'Adıyaman gündemindeki gelişme kamuoyu ile paylaşıldı.'],
            'content' => ['tr' => 'Adıyaman gündemindeki gelişmenin ayrıntıları haber metninde yer alıyor.'],
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Setting::set('integration', 'google_translate_api_key', 'test-google-key');
        Queue::fake();

        $this->actingAs($admin);

        Livewire::test(IhaHealth::class)
            ->callAction('start_translation_flow');

        $audit = AdminOperationAudit::query()->where('action', 'iha.translation_requeue')->firstOrFail();

        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame('success', $audit->status);
        $this->assertSame(1, $audit->context['queued']);
        $this->assertSame(0, $audit->context['skipped_duplicates']);

        Queue::assertPushed(TranslateArticleJob::class, 1);
    }
}
