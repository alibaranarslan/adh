<?php

namespace Tests\Feature\Filament;

use App\Models\AdminOperationAudit;
use App\Models\User;
use App\Support\AdminOperationAuditor;
use App\Support\AdminPrivileges;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
