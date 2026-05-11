<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Support\NewsPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTranslationFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_presenter_uses_turkish_fallback_when_foreign_translation_is_missing(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $article = NewsArticle::query()->create([
            'title' => ['tr' => 'İHA Fallback Başlığı'],
            'slug' => 'iha-fallback-basligi',
            'summary' => ['tr' => 'Sadece Türkçe özet mevcut.'],
            'content' => ['tr' => 'Sadece Türkçe içerik mevcut.'],
            'meta_title' => ['tr' => 'İHA Fallback Başlığı'],
            'meta_description' => ['tr' => 'Sadece Türkçe meta açıklama mevcut.'],
            'source' => 'iha',
            'source_url' => 'https://www.iha.com.tr/ornek',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
        ]);

        app()->setLocale('en');
        $englishStory = NewsPresenter::present($article);

        $this->assertSame('İHA Fallback Başlığı', $englishStory['title']);
        $this->assertSame('Sadece Türkçe özet mevcut.', $englishStory['summary']);

        app()->setLocale('ku');
        $kurdishStory = NewsPresenter::present($article);

        $this->assertSame('İHA Fallback Başlığı', $kurdishStory['title']);
        $this->assertSame('Sadece Türkçe özet mevcut.', $kurdishStory['summary']);
    }
}
