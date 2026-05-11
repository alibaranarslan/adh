<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\AdminPrivileges;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPrivilegesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_panel_and_publish_configuration(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $this->attachRole($user, 'super_admin');

        $this->assertTrue(AdminPrivileges::canAccessAdminPanel($user));
        $this->assertTrue(AdminPrivileges::canAccessConfiguration($user));
        $this->assertTrue(AdminPrivileges::canPublishConfiguration($user));
    }

    public function test_editor_can_access_panel_but_cannot_publish_system_configuration(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $this->attachRole($user, 'editor');

        $this->assertTrue(AdminPrivileges::canAccessAdminPanel($user));
        $this->assertTrue(AdminPrivileges::canAccessConfiguration($user));
        $this->assertFalse(AdminPrivileges::canPublishConfiguration($user));
        $this->assertFalse(AdminPrivileges::canManageSystemSettings($user));
    }

    public function test_active_user_without_admin_role_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->assertFalse(AdminPrivileges::canAccessAdminPanel($user));
    }

    private function attachRole(User $user, string $roleName): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $modelMorphKey = config('permission.column_names.model_morph_key', 'model_id');

        DB::table($rolesTable)->updateOrInsert(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['name' => $roleName, 'guard_name' => 'web']
        );

        $roleId = (int) DB::table($rolesTable)
            ->where('name', $roleName)
            ->value('id');

        DB::table($modelHasRolesTable)->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            $modelMorphKey => $user->getKey(),
        ]);
    }
}
