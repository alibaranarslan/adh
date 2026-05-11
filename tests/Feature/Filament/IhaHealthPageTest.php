<?php

namespace Tests\Feature\Filament;

use App\Models\Category;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IhaHealthPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_admin_can_view_iha_health_page_and_see_backlog(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subMinutes(6),
            'completed_at' => now()->subMinutes(5),
            'articles_fetched' => 12,
            'articles_created' => 5,
            'articles_updated' => 2,
            'articles_skipped' => 5,
            'images_downloaded' => 4,
        ]);

        IhaSyncLog::query()->create([
            'status' => 'failed',
            'started_at' => now()->subMinutes(3),
            'completed_at' => now()->subMinutes(2),
            'articles_fetched' => 3,
            'articles_created' => 0,
            'articles_updated' => 0,
            'articles_skipped' => 0,
            'images_downloaded' => 0,
            'error_message' => 'Bağlantı hatası password=secret-token token=abc https://feed.example.test?username=ali&password=secret',
        ]);

        $category = Category::query()->create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        NewsArticle::query()->create([
            'title' => ['tr' => 'İHA Sağlık Testi'],
            'slug' => 'iha-saglik-testi',
            'summary' => ['tr' => 'Eksik çeviri özeti'],
            'content' => ['tr' => 'Eksik çeviri içeriği'],
            'meta_title' => ['tr' => 'Meta başlık'],
            'meta_description' => ['tr' => 'Meta açıklama'],
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/iha-health')
            ->assertOk()
            ->assertSee('Efektif senkron aralığı')
            ->assertSee('Eksik çeviri')
            ->assertSee('Son hata özeti');

        $this->actingAs($admin)
            ->get('/admin/iha-health')
            ->assertSee('Hatalı')
            ->assertSee('Yeniden deneme notu')
            ->assertSee('[redacted]')
            ->assertDontSee('secret-token')
            ->assertDontSee('username=ali');
    }
}
