<?php

namespace Tests\Feature\Automation;

use App\Jobs\PublishToInstagramJob;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Models\SocialPublication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InstagramAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_iha_news_is_recorded_and_queued_for_instagram(): void
    {
        Queue::fake();
        $this->enableInstagram();

        $article = $this->createArticle('iha', 'published');

        Queue::assertPushed(PublishToInstagramJob::class);
        $this->assertDatabaseHas('social_publications', [
            'news_article_id' => $article->id,
            'platform' => 'instagram',
            'status' => SocialPublication::STATUS_PENDING,
        ]);
    }

    public function test_published_manual_news_is_recorded_and_queued_for_instagram(): void
    {
        Queue::fake();
        $this->enableInstagram();

        $article = $this->createArticle('manuel', 'published');

        Queue::assertPushed(PublishToInstagramJob::class);
        $this->assertDatabaseHas('social_publications', [
            'news_article_id' => $article->id,
            'platform' => 'instagram',
        ]);
    }

    public function test_draft_news_is_not_recorded_for_instagram(): void
    {
        Queue::fake();
        $this->enableInstagram();

        $article = $this->createArticle('iha', 'draft');

        Queue::assertNotPushed(PublishToInstagramJob::class);
        $this->assertDatabaseMissing('social_publications', [
            'news_article_id' => $article->id,
            'platform' => 'instagram',
        ]);
    }

    public function test_existing_publication_prevents_duplicate_queueing(): void
    {
        Queue::fake();
        $this->enableInstagram();

        $article = $this->createArticle('iha', 'published');
        $article->update(['status' => 'draft']);
        $article->update(['status' => 'published']);

        Queue::assertPushed(PublishToInstagramJob::class, 1);
        $this->assertSame(1, SocialPublication::query()->where('news_article_id', $article->id)->count());
    }

    private function createArticle(string $source, string $status): NewsArticle
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        return NewsArticle::query()->create([
            'iha_id' => $source === 'iha' ? 'IHA-' . uniqid() : null,
            'title' => ['tr' => 'Yeni Yayin'],
            'slug' => 'yeni-yayin-' . uniqid(),
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Icerik'],
            'source' => $source,
            'category_id' => $category->id,
            'status' => $status,
            'published_at' => now(),
        ]);
    }

    private function enableInstagram(): void
    {
        Setting::set('integration', 'instagram_enabled', true);
        Setting::set('integration', 'instagram_access_token', 'token-123');
        Setting::set('integration', 'instagram_business_account_id', 'acct-456');
    }
}
