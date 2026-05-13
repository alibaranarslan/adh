<?php

namespace Tests\Feature\Jobs;

use App\Jobs\PublishToInstagramJob;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Models\SocialPublication;
use App\Services\InstagramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstagramPublicationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_marks_publication_published_when_graph_api_succeeds(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('news/source.jpg', $this->jpegBytes());
        $this->enableInstagram();

        $article = $this->createArticle(['featured_image' => 'news/source.jpg']);
        $publication = SocialPublication::query()->firstOrCreate([
            'news_article_id' => $article->id,
            'platform' => SocialPublication::PLATFORM_INSTAGRAM,
        ]);

        Http::fakeSequence()
            ->push(['id' => 'container-123'], 200)
            ->push(['id' => 'media-456'], 200);

        (new PublishToInstagramJob($publication->id))->handle(app(InstagramService::class));

        $publication->refresh();
        $this->assertSame(SocialPublication::STATUS_PUBLISHED, $publication->status);
        $this->assertSame('container-123', $publication->container_id);
        $this->assertSame('media-456', $publication->media_id);
        $this->assertNotNull($publication->caption);
        $this->assertNotNull($publication->creative_image_path);
        $this->assertNotNull($publication->published_at);
        Storage::disk('public')->assertExists($publication->creative_image_path);
    }

    public function test_job_marks_publication_skipped_when_credentials_are_missing(): void
    {
        $article = $this->createArticle();
        $publication = SocialPublication::query()->firstOrCreate([
            'news_article_id' => $article->id,
            'platform' => SocialPublication::PLATFORM_INSTAGRAM,
        ]);

        (new PublishToInstagramJob($publication->id))->handle(app(InstagramService::class));

        $publication->refresh();
        $this->assertSame(SocialPublication::STATUS_SKIPPED, $publication->status);
        $this->assertStringContainsString('kimlik bilgisi eksik', $publication->error_message);
    }

    public function test_job_marks_publication_failed_when_container_creation_fails(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('news/source.jpg', $this->jpegBytes());
        $this->enableInstagram();

        $article = $this->createArticle(['featured_image' => 'news/source.jpg']);
        $publication = SocialPublication::query()->firstOrCreate([
            'news_article_id' => $article->id,
            'platform' => SocialPublication::PLATFORM_INSTAGRAM,
        ]);

        Http::fakeSequence()->push([
            'error' => ['message' => 'Bad image URL'],
        ], 400);

        (new PublishToInstagramJob($publication->id))->handle(app(InstagramService::class));

        $publication->refresh();
        $this->assertSame(SocialPublication::STATUS_FAILED, $publication->status);
        $this->assertSame('Bad image URL', $publication->error_message);
        $this->assertNull($publication->media_id);
    }

    public function test_job_marks_publication_skipped_when_article_has_no_image(): void
    {
        $this->enableInstagram();
        $article = $this->createArticle();
        $publication = SocialPublication::query()->firstOrCreate([
            'news_article_id' => $article->id,
            'platform' => SocialPublication::PLATFORM_INSTAGRAM,
        ]);

        (new PublishToInstagramJob($publication->id))->handle(app(InstagramService::class));

        $publication->refresh();
        $this->assertSame(SocialPublication::STATUS_SKIPPED, $publication->status);
        $this->assertStringContainsString('gorseli bulunamadi', $publication->error_message);
    }

    private function createArticle(array $overrides = []): NewsArticle
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        return NewsArticle::query()->create(array_merge([
            'title' => ['tr' => 'Instagram Haber Basligi'],
            'slug' => 'instagram-haber-basligi-' . uniqid(),
            'summary' => ['tr' => 'Instagram icin haber ozeti.'],
            'content' => ['tr' => 'Instagram icin detayli haber metni.'],
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    private function enableInstagram(): void
    {
        Setting::set('integration', 'instagram_enabled', true);
        Setting::set('integration', 'instagram_access_token', 'token-123');
        Setting::set('integration', 'instagram_business_account_id', 'acct-456');
    }

    private function jpegBytes(): string
    {
        $image = imagecreatetruecolor(1200, 800);
        imagefilledrectangle($image, 0, 0, 1200, 800, imagecolorallocate($image, 10, 70, 130));
        ob_start();
        imagejpeg($image, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return (string) $bytes;
    }
}
