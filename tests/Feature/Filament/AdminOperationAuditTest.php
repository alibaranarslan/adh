<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\IhaHealth;
use App\Models\AdminOperationAudit;
use App\Models\IhaSyncLog;
use App\Models\User;
use App\Support\AdminOperationAuditor;
use App\Support\AdminPrivileges;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
}
