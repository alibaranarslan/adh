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
            ->assertSee('Yönetim Panelini Tanı')
            ->assertSee('Zaman aralığı')
            ->assertSee('İçerik kaynağı')
            ->assertSee('10 dk')
            ->assertSee('Karar bekleyen yayın yok')
            ->assertDontSee('filament-panels::');
    }

    public function test_dashboard_filters_are_get_based_and_show_selected_scope(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin?filters[window]=today&filters[source]=iha')
            ->assertOk()
            ->assertSee('Bugün')
            ->assertSee('Yalnız İHA')
            ->assertSee('name="filters[window]"', false)
            ->assertSee('name="filters[source]"', false);
    }

    public function test_dashboard_zero_today_traffic_does_not_claim_live_interest(): void
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

        NewsArticle::query()->create([
            'title' => ['tr' => 'Toplam okunma haberi'],
            'slug' => 'toplam-okunma-haberi',
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'İçerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'view_count' => 20,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Bugün trafik yok, toplam okunma gösteriliyor')
            ->assertSee('Toplam okunmaya göre')
            ->assertDontSee('En çok ilgi gören içerik hazır');
    }

    public function test_dashboard_routes_configuration_failures_to_integration_settings(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        IhaSyncLog::query()->create([
            'status' => 'failed',
            'started_at' => now()->subMinutes(15),
            'completed_at' => now()->subMinutes(14),
            'error_message' => 'İHA kimlik bilgileri eksik. Entegrasyon ayarlarını kontrol edin.',
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Son senkron hata verdi')
            ->assertSee('Entegrasyon Ayarları')
            ->assertSee('/admin/integration-settings', false);
    }

    public function test_dashboard_stale_iha_attention_uses_human_duration(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(76),
            'completed_at' => now()->subMinutes(75),
            'articles_fetched' => 8,
            'articles_created' => 3,
            'articles_updated' => 2,
            'articles_skipped' => 3,
            'images_downloaded' => 2,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Son başarılı senkron 1 saat 15 dakika önce tamamlandı.');
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
