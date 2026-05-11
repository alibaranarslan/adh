<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\InstagramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_caption_includes_title_summary_link_and_hashtags(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tag = Tag::query()->create([
            'name' => ['tr' => 'Adiyaman'],
            'slug' => 'adiyaman',
        ]);

        $article = NewsArticle::query()->create([
            'title' => ['tr' => 'Instagram Test Haberi'],
            'slug' => 'instagram-test-haberi',
            'summary' => ['tr' => 'Bu ozet Instagram caption akisi icin kullanilir.'],
            'content' => ['tr' => 'Detayli haber metni.'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $article->tags()->attach($tag->id);
        $article->load(['category', 'tags']);

        $caption = app(InstagramService::class)->generateCaption($article);

        $this->assertStringContainsString('Instagram Test Haberi', $caption);
        $this->assertStringContainsString('Bu ozet Instagram caption akisi icin kullanilir.', $caption);
        $this->assertStringContainsString(url('/') . '/instagram-test-haberi', $caption);
        $this->assertStringContainsString('#adiyaman', $caption);
        $this->assertStringContainsString('#gundem', $caption);
    }

    public function test_configuration_status_lists_missing_fields(): void
    {
        Setting::query()->delete();

        $status = app(InstagramService::class)->configurationStatus();

        $this->assertFalse($status['configured']);
        $this->assertContains('Access token', $status['missing']);
        $this->assertContains('Business account ID', $status['missing']);
    }
}
