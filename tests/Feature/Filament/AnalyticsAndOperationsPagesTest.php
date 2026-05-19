<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Analytics;
use App\Models\AnalyticsPageView;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsAndOperationsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_admin_can_view_analytics_and_operations_pages(): void
    {
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

        $article = NewsArticle::query()->create([
            'title' => ['tr' => 'Analitik test haberi'],
            'slug' => 'analitik-test-haberi',
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'İçerik'],
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        AnalyticsPageView::query()->create([
            'viewable_type' => NewsArticle::class,
            'viewable_id' => $article->id,
            'session_id' => 'sess-1',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'referer' => 'https://www.google.com/search?q=adh',
            'device_type' => 'mobile',
            'country' => 'TR',
            'viewed_at' => now()->subDay(),
        ]);

        AnalyticsPageView::query()->create([
            'viewable_type' => NewsArticle::class,
            'viewable_id' => $article->id,
            'session_id' => 'sess-2',
            'ip_address' => '127.0.0.2',
            'user_agent' => 'TestAgentDesktop',
            'referer' => 'https://facebook.com/story.php',
            'device_type' => 'desktop',
            'country' => 'TR',
            'viewed_at' => now()->subHours(10),
        ]);

        $this->actingAs($admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee('Trafik ve karar destek görünümü')
            ->assertSee('Yerel analytics_page_views')
            ->assertSee('En çok okunan haberler')
            ->assertSee('Trafik kaynakları')
            ->assertSee('Cihaz kırılımı');

        $this->actingAs($admin)
            ->get('/admin/cache-management')
            ->assertOk()
            ->assertSee('Tam temizlik');

        $this->actingAs($admin)
            ->get('/admin/backup-manager')
            ->assertOk()
            ->assertSee('Yedekleme ve geri dönüş hazırlığı')
            ->assertSee('Runbook özeti');
    }

    public function test_analytics_export_returns_csv_download(): void
    {
        $page = new Analytics();
        $page->mount();

        $response = $page->exportCsv();

        $this->assertStringContainsString('adh-performans-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }

    public function test_editor_cannot_access_user_management(): void
    {
        $editor = User::factory()->create([
            'is_active' => true,
        ]);

        $this->attachRole($editor, 'editor');

        $this->actingAs($editor)
            ->get('/admin/users')
            ->assertForbidden();
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
