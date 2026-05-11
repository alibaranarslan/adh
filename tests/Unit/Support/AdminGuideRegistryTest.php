<?php

namespace Tests\Unit\Support;

use App\Support\AdminGuides\AdminGuideRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminGuideRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_admin_sees_super_admin_guides(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $catalog = app(AdminGuideRegistry::class)->catalogForUser($admin);
        $keys = collect($catalog)->pluck('guide_key')->all();

        $this->assertContains('dashboard-overview', $keys);
        $this->assertContains('integration-settings', $keys);
        $this->assertContains('user-management', $keys);
    }

    public function test_editor_catalog_hides_super_admin_only_guides(): void
    {
        Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);

        $editor = User::query()->create([
            'name' => 'Editor',
            'email' => 'editor@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $editor->assignRole('editor');

        $catalog = app(AdminGuideRegistry::class)->catalogForUser($editor);
        $keys = collect($catalog)->pluck('guide_key')->all();

        $this->assertContains('dashboard-overview', $keys);
        $this->assertContains('layout-studio', $keys);
        $this->assertNotContains('integration-settings', $keys);
        $this->assertNotContains('user-management', $keys);
        $this->assertNotContains('iha-health', $keys);
    }
}
