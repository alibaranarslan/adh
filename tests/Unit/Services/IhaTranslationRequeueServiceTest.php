<?php

namespace Tests\Unit\Services;

use App\Jobs\TranslateArticleJob;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Services\IhaTranslationRequeueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IhaTranslationRequeueServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requeues_missing_iha_translations_in_chunks_and_marks_duplicates(): void
    {
        Cache::flush();
        Queue::fake();

        $this->ihaArticle('ceviri-1');
        $this->ihaArticle('ceviri-2');
        $this->ihaArticle('ceviri-tamam', [
            'title' => ['tr' => 'Tamam', 'en' => 'Complete', 'ku' => 'Complete'],
            'summary' => ['tr' => 'Ozet', 'en' => 'Summary', 'ku' => 'Summary'],
            'content' => ['tr' => 'Icerik', 'en' => 'Body', 'ku' => 'Body'],
            'meta_title' => ['tr' => 'Meta', 'en' => 'Meta', 'ku' => 'Meta'],
            'meta_description' => ['tr' => 'Aciklama', 'en' => 'Description', 'ku' => 'Description'],
        ]);

        $result = app(IhaTranslationRequeueService::class)->requeueMissingTranslations(chunkSize: 1);

        $this->assertSame(2, $result['backlog']);
        $this->assertSame(2, $result['queued']);
        $this->assertSame(0, $result['skipped_duplicates']);
        Queue::assertPushed(TranslateArticleJob::class, 2);

        Queue::fake();

        $result = app(IhaTranslationRequeueService::class)->requeueMissingTranslations(chunkSize: 1);

        $this->assertSame(2, $result['backlog']);
        $this->assertSame(0, $result['queued']);
        $this->assertSame(2, $result['skipped_duplicates']);
        Queue::assertNothingPushed();
    }

    public function test_it_skips_article_when_translation_job_is_already_in_database_queue(): void
    {
        Queue::fake();
        Cache::flush();

        $article = $this->ihaArticle('ceviri-queued');

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => TranslateArticleJob::class,
                'data' => [
                    'command' => 'TranslateArticleJob articleId";i:'.$article->id.';',
                ],
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $result = app(IhaTranslationRequeueService::class)->requeueMissingTranslations();

        $this->assertSame(1, $result['backlog']);
        $this->assertSame(0, $result['queued']);
        $this->assertSame(1, $result['skipped_duplicates']);
        Queue::assertNothingPushed();
    }

    private function ihaArticle(string $slug, array $overrides = []): NewsArticle
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'gundem'],
            ['name' => ['tr' => 'Gundem'], 'is_active' => true],
        );

        return NewsArticle::query()->create(array_merge([
            'title' => ['tr' => 'Ceviri Test'],
            'slug' => $slug,
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Icerik'],
            'meta_title' => ['tr' => 'Meta'],
            'meta_description' => ['tr' => 'Aciklama'],
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }
}
