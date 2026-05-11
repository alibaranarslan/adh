<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\BackupManager;
use App\Models\IhaSyncLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsOperationsFinalSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_settings_and_operations_pages_without_mojibake(): void
    {
        $admin = $this->superAdmin();

        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
            'articles_fetched' => 5,
            'articles_created' => 2,
            'articles_updated' => 1,
            'articles_skipped' => 2,
        ]);

        foreach ([
            '/admin' => 'Haber Masası',
            '/admin/general-settings' => 'Genel Ayarlar',
            '/admin/seo-settings' => 'SEO Ayarları',
            '/admin/integration-settings' => 'Entegrasyon Ayarları',
            '/admin/email-settings' => 'E-posta Ayarları',
            '/admin/cache-management' => 'Önbellek Yönetimi',
            '/admin/iha-health' => 'İHA Sağlık Merkezi',
            '/admin/backup-manager' => 'Yedekleme',
        ] as $path => $expectedText) {
            $response = $this->actingAs($admin)->get($path)->assertOk();

            $response->assertSee($expectedText);
            $this->assertDoesNotMatchRegularExpression('/(?:Ã|Ä|Å|â€|Â)/u', $response->getContent() ?: '', "{$path} renders mojibake.");
        }
    }

    public function test_configured_roles_keep_settings_and_operations_pages_super_admin_only(): void
    {
        $editor = $this->userWithRole('editor');

        foreach ([
            '/admin/general-settings',
            '/admin/seo-settings',
            '/admin/integration-settings',
            '/admin/email-settings',
            '/admin/cache-management',
            '/admin/iha-health',
            '/admin/backup-manager',
        ] as $path) {
            $this->actingAs($editor)->get($path)->assertForbidden();
        }
    }

    public function test_backup_manager_reports_command_readiness_without_false_success(): void
    {
        $this->actingAs($this->superAdmin());

        $status = app(BackupManager::class)->getBackupStatus();

        $this->assertArrayHasKey('command_available', $status);
        $this->assertArrayHasKey('directories', $status);
        $this->assertArrayHasKey('latest_backup', $status);
        $this->assertIsBool($status['command_available']);
        $this->assertNotEmpty($status['directories']);
    }

    private function superAdmin(): User
    {
        return $this->userWithRole('super_admin');
    }

    private function userWithRole(string $role): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
