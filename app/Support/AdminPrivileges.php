<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPrivileges
{
    private const ADMIN_PANEL_ROLES = ['super_admin', 'editor', 'writer'];

    private const CONFIGURATION_ROLES = ['super_admin', 'editor'];

    public static function canAccessConfiguration(?User $user): bool
    {
        if (! (bool) $user?->is_active) {
            return false;
        }

        if (! self::rolesAreConfigured()) {
            return self::isBootstrapAdmin($user);
        }

        return self::userHasAnyRole($user, self::CONFIGURATION_ROLES);
    }

    public static function canPublishConfiguration(?User $user): bool
    {
        if (! (bool) $user?->is_active) {
            return false;
        }

        if (! self::rolesAreConfigured()) {
            return self::isBootstrapAdmin($user);
        }

        return self::userHasRole($user, 'super_admin');
    }

    public static function canAccessAdminPanel(?User $user): bool
    {
        if (! (bool) $user?->is_active) {
            return false;
        }

        if (! self::rolesAreConfigured()) {
            return self::isBootstrapAdmin($user);
        }

        return self::userHasAnyRole($user, self::ADMIN_PANEL_ROLES);
    }

    public static function canManageSystemSettings(?User $user): bool
    {
        return self::canPublishConfiguration($user);
    }

    public static function hasPermission(?User $user, string $permission): bool
    {
        if (! (bool) $user?->is_active) {
            return false;
        }

        if (! self::rolesAreConfigured()) {
            return self::isBootstrapAdmin($user);
        }

        if (self::userHasRole($user, 'super_admin')) {
            return true;
        }

        return (bool) $user?->can($permission);
    }

    private static function userHasAnyRole(?User $user, array $roles): bool
    {
        if (! $user?->exists || $roles === []) {
            return false;
        }

        $rolesTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $modelMorphKey = config('permission.column_names.model_morph_key', 'model_id');

        return DB::table($modelHasRolesTable)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRolesTable}.role_id")
            ->where("{$modelHasRolesTable}.model_type", User::class)
            ->where("{$modelHasRolesTable}.{$modelMorphKey}", $user->getKey())
            ->whereIn("{$rolesTable}.name", $roles)
            ->exists();
    }

    private static function userHasRole(?User $user, string $role): bool
    {
        return self::userHasAnyRole($user, [$role]);
    }

    private static function rolesAreConfigured(): bool
    {
        try {
            if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
                return false;
            }

            return DB::table('roles')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function isBootstrapAdmin(?User $user): bool
    {
        if (! (bool) $user?->is_active) {
            return false;
        }

        return in_array($user?->email, ['admin@admin.com'], true);
    }
}
