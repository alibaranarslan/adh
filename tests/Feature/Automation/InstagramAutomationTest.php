<?php

namespace Tests\Feature\Automation;

use App\Jobs\PublishToInstagramJob;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InstagramAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_news_is_queued_for_instagram_when_credentials_are_complete(): void
    {
        Queue::fake();

        Setting::set('integration', 'instagram_access_token', 'token-123');
        Setting::set('integration', 'instagram_business_account_id', 'acct-456');

        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        NewsArticle::query()->create([
            'title' => ['tr' => 'Yeni Yayin'],
            'slug' => 'yeni-yayin',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Icerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Queue::assertPushed(PublishToInstagramJob::class);
    }

    public function test_published_news_is_not_queued_when_business_account_id_is_missing(): void
    {
        Queue::fake();

        Setting::set('integration', 'instagram_access_token', 'token-123');

        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        NewsArticle::query()->create([
            'title' => ['tr' => 'Eksik Kimlik'],
            'slug' => 'eksik-kimlik',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Icerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Queue::assertNotPushed(PublishToInstagramJob::class);
    }
}
