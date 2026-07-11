<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $clientManager = Role::firstOrCreate(['name' => 'client_manager', 'guard_name' => 'web']);
        $editor     = Role::firstOrCreate(['name' => 'editor',      'guard_name' => 'web']);
        $writer     = Role::firstOrCreate(['name' => 'writer',      'guard_name' => 'web']);

        // Permissions for news articles
        $permissions = [
            'view_news_article',
            'view_any_news_article',
            'create_news_article',
            'update_news_article',
            'delete_news_article',
            'delete_any_news_article',
            'force_delete_news_article',
            'force_delete_any_news_article',
            'restore_news_article',
            'restore_any_news_article',
            'publish_news_article',
            'view_category',
            'view_any_category',
            'create_category',
            'update_category',
            'delete_category',
            'view_tag',
            'view_any_tag',
            'create_tag',
            'update_tag',
            'delete_tag',
            'view_page',
            'view_any_page',
            'create_page',
            'update_page',
            'delete_page',
            'view_advertisement',
            'view_any_advertisement',
            'create_advertisement',
            'update_advertisement',
            'delete_advertisement',
            'view_local_info_entry',
            'view_any_local_info_entry',
            'create_local_info_entry',
            'update_local_info_entry',
            'delete_local_info_entry',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // super_admin gets all permissions
        $superAdmin->syncPermissions(Permission::all());

        $clientManager->syncPermissions(Permission::whereIn('name', [
            'view_news_article', 'view_any_news_article', 'create_news_article',
            'update_news_article', 'publish_news_article', 'restore_news_article',
            'view_category', 'view_any_category', 'create_category', 'update_category',
            'view_tag', 'view_any_tag', 'create_tag', 'update_tag',
            'view_page', 'view_any_page', 'create_page', 'update_page',
            'view_advertisement', 'view_any_advertisement', 'create_advertisement', 'update_advertisement',
            'view_local_info_entry', 'view_any_local_info_entry', 'create_local_info_entry', 'update_local_info_entry',
        ])->get());

        // editor: can view, create, update, publish — no delete/force delete
        $editor->syncPermissions(Permission::whereIn('name', [
            'view_news_article', 'view_any_news_article', 'create_news_article',
            'update_news_article', 'publish_news_article', 'restore_news_article',
            'view_category', 'view_any_category', 'create_category', 'update_category',
            'view_tag', 'view_any_tag', 'create_tag', 'update_tag',
            'view_page', 'view_any_page',
            'view_advertisement', 'view_any_advertisement',
            'view_local_info_entry', 'view_any_local_info_entry',
            'create_local_info_entry', 'update_local_info_entry',
        ])->get());

        // writer: can view and create drafts only
        $writer->syncPermissions(Permission::whereIn('name', [
            'view_news_article', 'view_any_news_article', 'create_news_article', 'update_news_article',
            'view_category', 'view_any_category',
            'view_tag', 'view_any_tag', 'create_tag',
        ])->get());
    }
}
