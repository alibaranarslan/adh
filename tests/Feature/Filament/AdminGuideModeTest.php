<?php

namespace Tests\Feature\Filament;

use App\Models\AdminGuideProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminGuideModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_help_trigger_and_intro_card(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Yardım')
            ->assertSee('Yönetim Panelini Tanı')
            ->assertSee('data-tour-anchor="dashboard.attention"', false)
            ->assertSee('dashboard-overview');
    }

    public function test_guide_progress_is_persisted_for_visible_guide(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.guides.progress.store'), [
                'guide_key' => 'dashboard-overview',
                'status' => 'completed',
                'last_step_index' => 4,
                'meta' => ['path' => '/admin'],
            ])
            ->assertOk()
            ->assertJsonPath('progress.status', 'completed');

        $this->assertDatabaseHas('admin_guide_progress', [
            'user_id' => $admin->id,
            'guide_key' => 'dashboard-overview',
            'status' => 'completed',
            'last_step_index' => 4,
        ]);
    }

    public function test_editor_cannot_store_progress_for_super_admin_only_guide(): void
    {
        Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);

        $editor = User::query()->create([
            'name' => 'Editor',
            'email' => 'editor@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $editor->assignRole('editor');

        $this->actingAs($editor)
            ->postJson(route('admin.guides.progress.store'), [
                'guide_key' => 'user-management',
                'status' => 'in_progress',
                'last_step_index' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('admin_guide_progress', 0);
    }

    public function test_layout_and_health_pages_render_tour_anchors(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/layout-studio')
            ->assertOk()
            ->assertSee('data-tour-anchor="layout.hero"', false)
            ->assertSee('data-tour-anchor="layout.publish"', false);

        $this->actingAs($admin)
            ->get('/admin/iha-health')
            ->assertOk()
            ->assertSee('data-tour-anchor="iha.health.summary"', false)
            ->assertSee('data-tour-anchor="iha.health.logs"', false);
    }
}
