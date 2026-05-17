<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizedNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_public_navigation_keeps_locale_prefix(): void
    {
        $this->seedPublicContent();

        $response = $this->get('/en');

        $response
            ->assertOk()
            ->assertSee('href="' . url('/en/arama') . '"', false)
            ->assertSee('href="' . url('/en/kategori/gundem') . '"', false)
            ->assertSee('href="' . url('/en/iletisim') . '"', false)
            ->assertSee('href="' . url('/en/locale-koruyan-haber') . '"', false)
            ->assertDontSee('href="' . url('/kategori/gundem') . '"', false)
            ->assertDontSee('href="' . url('/arama') . '"', false);
    }

    public function test_kurdish_public_navigation_keeps_locale_prefix(): void
    {
        $this->seedPublicContent();

        $response = $this->get('/ku');

        $response
            ->assertOk()
            ->assertSee('href="' . url('/ku/arama') . '"', false)
            ->assertSee('href="' . url('/ku/kategori/gundem') . '"', false)
            ->assertSee('href="' . url('/ku/iletisim') . '"', false)
            ->assertSee('href="' . url('/ku/locale-koruyan-haber') . '"', false)
            ->assertDontSee('href="' . url('/kategori/gundem') . '"', false)
            ->assertDontSee('href="' . url('/arama') . '"', false);
    }

    private function seedPublicContent(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem', 'en' => 'Agenda', 'ku' => 'Rojev'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::query()->create([
            'title' => [
                'tr' => 'Locale Koruyan Haber',
                'en' => 'Locale Preserving Story',
                'ku' => 'Nuceya Locale Dipareze',
            ],
            'slug' => 'locale-koruyan-haber',
            'summary' => [
                'tr' => 'Locale linkleri korunmali.',
                'en' => 'Locale links should be preserved.',
                'ku' => 'Giredanên locale divê werin parastin.',
            ],
            'content' => [
                'tr' => 'Ana haber metni vardir.',
                'en' => 'The main story body is present.',
                'ku' => 'Nivisa sereke ya nuceyê heye.',
            ],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
            'editorial_score' => 80,
        ]);
    }
}
