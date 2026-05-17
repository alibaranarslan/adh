<?php

namespace Tests\Unit\Jobs;

use App\Jobs\TranslateArticleJob;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TranslateArticleJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_translate_article_job_includes_meta_fields_and_force_flag(): void
    {
        Setting::set('integration', 'google_translate_api_key', 'google-key');

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

    public function test_translate_article_job_deletes_itself_when_google_api_key_is_missing(): void
    {
        config(['services.google_translate.api_key' => null]);

        $job = new TranslateArticleJob(123, true);

        $service = $this->mock(TranslationService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('translateModel');
        });

        $job->withFakeQueueInteractions();
        $job->handle($service);
        $job->assertDeleted();
    }
}
