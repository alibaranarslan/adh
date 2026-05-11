<?php

namespace Tests\Feature\Filament;

use App\Models\Category;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAndNewsResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_admin_sees_dashboard_status_cards_and_quick_actions(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(12),
            'completed_at' => now()->subMinutes(10),
            'articles_fetched' => 8,
            'articles_created' => 3,
            'articles_updated' => 2,
            'articles_skipped' => 3,
            'images_downloaded' => 2,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Haber Masası')
            ->assertSee('Şimdi Dikkat Gerekenler')
            ->assertSee('Yayın Kuyruğu')
            ->assertSee('İHA ve Çeviri Akışı')
            ->assertSee('Yönetim Panelini Tanı');
    }

    public function test_bootstrap_admin_can_open_news_resource_pages_and_see_editorial_column(): void
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
            'sort_order' => 1,
        ]);

        $article = NewsArticle::query()->create([
            'title' => ['tr' => 'Admin Resource Haberi'],
            'slug' => 'admin-resource-haberi',
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'İçerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'draft',
            'editorial_score' => 77,
        ]);

        $this->actingAs($admin)
            ->get('/admin/news-articles')
            ->assertOk()
            ->assertSee('Editoryal Puan')
            ->assertSee('Admin Resource Haberi');

        $this->actingAs($admin)
            ->get('/admin/news-articles/create')
            ->assertOk()
            ->assertSee('Haber Formu')
            ->assertSee('Temel Bilgiler');

        $this->actingAs($admin)
            ->get('/admin/news-articles/' . $article->id . '/edit')
            ->assertOk()
            ->assertSee('Admin Resource Haberi')
            ->assertSee('SEO');
    }
}
