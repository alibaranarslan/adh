<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\InstagramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstagramServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_caption_includes_title_summary_link_and_hashtags(): void
    {
        $article = $this->articleWithCategoryAndTag();

        $caption = app(InstagramService::class)->generateCaption($article);

        $this->assertStringContainsString('Instagram Test Haberi', $caption);
        $this->assertStringContainsString('Bu ozet Instagram caption akisi icin kullanilir.', $caption);
        $this->assertStringContainsString(url('/') . '/instagram-test-haberi', $caption);
        $this->assertStringContainsString('#adiyaman', $caption);
        $this->assertStringContainsString('#gundem', $caption);
        $this->assertLessThanOrEqual(2200, mb_strlen($caption));
    }

    public function test_configuration_status_lists_missing_fields(): void
    {
        Setting::query()->delete();

        $status = app(InstagramService::class)->configurationStatus();

        $this->assertFalse($status['configured']);
        $this->assertContains('Instagram automation enabled', $status['missing']);
        $this->assertContains('Access token', $status['missing']);
        $this->assertContains('Business account ID', $status['missing']);
    }

    public function test_configuration_status_is_ready_when_enabled_and_credentials_exist(): void
    {
        Setting::set('integration', 'instagram_enabled', true);
        Setting::set('integration', 'instagram_access_token', 'token-123');
        Setting::set('integration', 'instagram_business_account_id', 'acct-456');

        $status = app(InstagramService::class)->configurationStatus();

        $this->assertTrue($status['configured']);
        $this->assertTrue($status['enabled']);
        $this->assertSame([], $status['missing']);
    }

    public function test_short_title_is_limited_for_creative_overlay(): void
    {
        $article = $this->articleWithCategoryAndTag([
            'title' => ['tr' => str_repeat('Cok Uzun Haber Basligi ', 10)],
        ]);

        $this->assertLessThanOrEqual(72, mb_strlen(app(InstagramService::class)->generateShortTitle($article)));
    }

    public function test_generate_creative_image_creates_square_jpeg_and_absolute_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('news/source.jpg', $this->jpegBytes());

        $article = $this->articleWithCategoryAndTag([
            'featured_image' => 'news/source.jpg',
        ]);

        $creative = app(InstagramService::class)->generateCreativeImage($article);

        $this->assertNotNull($creative);
        Storage::disk('public')->assertExists($creative['path']);
        $this->assertStringStartsWith(url('/storage/instagram/creatives'), $creative['url']);

        [$width, $height] = getimagesize(Storage::disk('public')->path($creative['path']));
        $this->assertSame(1080, $width);
        $this->assertSame(1080, $height);
    }

    private function articleWithCategoryAndTag(array $overrides = []): NewsArticle
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

        $article = NewsArticle::query()->create(array_merge([
            'title' => ['tr' => 'Instagram Test Haberi'],
            'slug' => 'instagram-test-haberi',
            'summary' => ['tr' => 'Bu ozet Instagram caption akisi icin kullanilir.'],
            'content' => ['tr' => 'Detayli haber metni.'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));

        $article->tags()->attach($tag->id);

        return $article->load(['category', 'tags']);
    }

    private function jpegBytes(): string
    {
        $image = imagecreatetruecolor(1200, 800);
        imagefilledrectangle($image, 0, 0, 1200, 800, imagecolorallocate($image, 20, 80, 140));
        ob_start();
        imagejpeg($image, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return (string) $bytes;
    }
}
