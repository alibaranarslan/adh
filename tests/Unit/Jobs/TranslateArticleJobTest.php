<?php

namespace Tests\Unit\Jobs;

use App\Jobs\TranslateArticleJob;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TranslateArticleJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_translate_article_job_includes_meta_fields_and_force_flag(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Genel'],
            'slug' => 'genel',
            'is_active' => true,
        ]);

        $article = NewsArticle::create([
            'title' => ['tr' => 'Örnek Başlık'],
            'slug' => 'ornek-baslik',
            'summary' => ['tr' => 'Örnek özet'],
            'content' => ['tr' => 'Örnek içerik'],
            'meta_title' => ['tr' => 'Örnek Başlık'],
            'meta_description' => ['tr' => 'Örnek özet'],
            'source' => 'iha',
            'source_url' => 'https://www.iha.com.tr',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $service = $this->mock(TranslationService::class, function (MockInterface $mock) use ($article): void {
            $mock->shouldReceive('translateModel')
                ->once()
                ->withArgs(function ($model, array $fields, array $targetLangs, bool $force) use ($article): bool {
                    return $model->is($article)
                        && $fields === ['title', 'summary', 'content', 'meta_title', 'meta_description']
                        && $targetLangs === ['en', 'ku']
                        && $force === true;
                });
        });

        (new TranslateArticleJob($article->id, true))->handle($service);
    }
}
