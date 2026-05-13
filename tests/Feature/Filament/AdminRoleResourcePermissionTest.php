<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\TagResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\LocalInfoEntry;
use App\Models\NewsArticle;
use App\Models\NewsletterSubscription;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminRoleResourcePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_writer_is_limited_to_newsroom_draft_surfaces(): void
    {
        $this->seed(RoleSeeder::class);
        $writer = $this->userWithRole('writer');
        $records = $this->contentRecords();

        foreach ([
            '/admin/news-articles',
            '/admin/news-articles/create',
            "/admin/news-articles/{$records['article']->getKey()}/edit",
            '/admin/categories',
            '/admin/tags',
            '/admin/tags/create',
        ] as $path) {
            $this->actingAs($writer)->get($path)->assertOk();
        }

        foreach ([
            '/admin/categories/create',
            "/admin/tags/{$records['tag']->getKey()}/edit",
            '/admin/pages',
            '/admin/pages/create',
            '/admin/advertisements',
            '/admin/local-info-entries',
            '/admin/newsletter-subscriptions',
            '/admin/iha-sync-logs',
            '/admin/iha-health',
            '/admin/general-settings',
            '/admin/users',
        ] as $path) {
            $this->actingAs($writer)->get($path)->assertForbidden();
        }
    }

    public function test_editor_can_operate_allowed_content_but_not_system_surfaces(): void
    {
        $this->seed(RoleSeeder::class);
        $editor = $this->userWithRole('editor');
        $records = $this->contentRecords();

        foreach ([
            '/admin/news-articles',
            '/admin/news-articles/create',
            "/admin/news-articles/{$records['article']->getKey()}/edit",
            '/admin/categories',
            '/admin/categories/create',
            "/admin/categories/{$records['category']->getKey()}/edit",
            '/admin/tags',
            '/admin/tags/create',
            "/admin/tags/{$records['tag']->getKey()}/edit",
            '/admin/pages',
            '/admin/advertisements',
            '/admin/local-info-entries',
            '/admin/local-info-entries/create',
            "/admin/local-info-entries/{$records['localInfo']->getKey()}/edit",
            '/admin/newsletter-subscriptions',
            '/admin/media-library',
            '/admin/analytics',
        ] as $path) {
            $this->actingAs($editor)->get($path)->assertOk();
        }

        foreach ([
            '/admin/pages/create',
            "/admin/pages/{$records['page']->getKey()}/edit",
            '/admin/advertisements/create',
            "/admin/advertisements/{$records['ad']->getKey()}/edit",
            '/admin/iha-sync-logs',
            '/admin/iha-health',
            '/admin/general-settings',
            '/admin/email-settings',
            '/admin/integration-settings',
            '/admin/users',
        ] as $path) {
            $this->actingAs($editor)->get($path)->assertForbidden();
        }
    }

    public function test_super_admin_retains_full_admin_surface_access(): void
    {
        $this->seed(RoleSeeder::class);
        $superAdmin = $this->userWithRole('super_admin');
        $records = $this->contentRecords();

        foreach ([
            '/admin/news-articles',
            '/admin/news-articles/create',
            "/admin/news-articles/{$records['article']->getKey()}/edit",
            '/admin/categories/create',
            "/admin/categories/{$records['category']->getKey()}/edit",
            '/admin/tags/create',
            "/admin/tags/{$records['tag']->getKey()}/edit",
            '/admin/pages/create',
            "/admin/pages/{$records['page']->getKey()}/edit",
            '/admin/advertisements/create',
            "/admin/advertisements/{$records['ad']->getKey()}/edit",
            '/admin/local-info-entries/create',
            "/admin/local-info-entries/{$records['localInfo']->getKey()}/edit",
            '/admin/newsletter-subscriptions',
            "/admin/newsletter-subscriptions/{$records['subscriber']->getKey()}/edit",
            '/admin/iha-sync-logs',
            '/admin/iha-health',
            '/admin/general-settings',
            '/admin/email-settings',
            '/admin/integration-settings',
            '/admin/users',
        ] as $path) {
            $this->actingAs($superAdmin)->get($path)->assertOk();
        }
    }

    public function test_reorder_and_tag_merge_permissions_follow_mutating_permissions(): void
    {
        $this->seed(RoleSeeder::class);

        $writer = $this->userWithRole('writer');
        $editor = $this->userWithRole('editor');

        $this->actingAs($writer);
        $this->assertFalse(CategoryResource::canReorder());
        $this->assertFalse(PageResource::canReorder());
        $this->assertFalse(TagResource::canMerge());

        $this->actingAs($editor);
        $this->assertTrue(CategoryResource::canReorder());
        $this->assertFalse(PageResource::canReorder());
        $this->assertFalse(TagResource::canMerge());
    }

    public function test_user_resource_blocks_self_and_last_super_admin_delete(): void
    {
        $this->seed(RoleSeeder::class);

        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($superAdmin);

        $this->assertFalse(UserResource::canDelete($superAdmin));
        $this->assertTrue(UserResource::isProtectedAccountMutation($superAdmin));

        $secondSuperAdmin = $this->userWithRole('super_admin');

        $this->assertFalse(UserResource::canDelete($superAdmin));
        $this->assertTrue(UserResource::canDelete($secondSuperAdmin));
    }

    public function test_user_bulk_delete_does_not_delete_protected_accounts_or_selected_peers(): void
    {
        $this->seed(RoleSeeder::class);

        $superAdmin = $this->userWithRole('super_admin');
        $secondSuperAdmin = $this->userWithRole('super_admin');
        $writer = $this->userWithRole('writer');

        $this->actingAs($superAdmin);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('delete', [$superAdmin, $secondSuperAdmin, $writer]);

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $secondSuperAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $writer->id]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function contentRecords(): array
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $tag = Tag::query()->create([
            'name' => ['tr' => 'Yerel'],
            'slug' => 'yerel',
        ]);

        $article = NewsArticle::query()->create([
            'title' => ['tr' => 'Yetki Test Haberi'],
            'slug' => 'yetki-test-haberi',
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'Haber gövdesi'],
            'category_id' => $category->id,
            'status' => 'draft',
            'source' => 'manuel',
        ]);

        $page = Page::query()->create([
            'title' => ['tr' => 'Yetki Test Sayfası'],
            'slug' => 'yetki-test-sayfasi',
            'content' => ['tr' => 'Sayfa içeriği'],
            'is_published' => true,
        ]);

        $ad = Advertisement::query()->create([
            'name' => 'Yetki Test Reklamı',
            'position' => 'sidebar-top',
            'type' => 'banner',
            'is_active' => true,
        ]);

        $localInfo = LocalInfoEntry::query()->create([
            'type' => 'other',
            'title' => 'Yetki Test Yerel Bilgi',
            'content' => 'Yerel bilgi içeriği',
            'is_active' => true,
        ]);

        $subscriber = NewsletterSubscription::query()->create([
            'email' => 'subscriber@example.test',
            'name' => 'Abone',
            'is_active' => true,
        ]);

        return compact('category', 'tag', 'article', 'page', 'ad', 'localInfo', 'subscriber');
    }
}
