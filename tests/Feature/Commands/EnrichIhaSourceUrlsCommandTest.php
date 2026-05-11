<?php

namespace Tests\Feature\Commands;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Services\IhaPublicArticleResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EnrichIhaSourceUrlsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_only_generic_iha_source_url(): void
    {
        $category = $this->category();
        $article = NewsArticle::query()->create([
            'iha_id' => '20260509AWTEST01',
            'title' => ['tr' => 'Kaynak URL test haberi'],
            'slug' => 'kaynak-url-test-haberi',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Govde'],
            'meta_title' => ['tr' => 'Kaynak URL test haberi'],
            'meta_description' => ['tr' => 'Ozet'],
            'source' => 'iha',
            'source_url' => 'https://www.iha.com.tr',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
        ]);

        NewsArticle::query()->create([
            'iha_id' => '20260509AWTEST02',
            'title' => ['tr' => 'Zaten ozel URL haberi'],
            'slug' => 'zaten-ozel-url-haberi',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Govde'],
            'meta_title' => ['tr' => 'Zaten ozel URL haberi'],
            'meta_description' => ['tr' => 'Ozet'],
            'source' => 'iha',
            'source_url' => 'https://www.iha.com.tr/gundem-haberleri/zaten-ozel-url-haberi-123',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(9),
        ]);

        $this->mock(IhaPublicArticleResolverService::class, function ($mock) {
            $mock->shouldReceive('resolve')
                ->once()
                ->andReturnUsing(fn (NewsArticle $article) => [
                    'url' => 'https://www.iha.com.tr/gundem-haberleri/'.$article->slug.'-456',
                    'headline' => $article->getTranslation('title', 'tr'),
                    'content' => 'Govde',
                    'description' => 'Ozet',
                    'published_at' => $article->published_at?->toIso8601String(),
                ]);
        });

        $exitCode = Artisan::call('iha:enrich-source-urls', ['--limit' => 10]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('IHA_SOURCE_URL_ENRICHMENT scanned=1 matched=1 updated=1 unresolved=0 dry_run=no', $output);
        $this->assertSame(
            'https://www.iha.com.tr/gundem-haberleri/kaynak-url-test-haberi-456',
            $article->refresh()->source_url
        );
    }

    public function test_command_dry_run_does_not_update_source_url(): void
    {
        $category = $this->category();
        $article = NewsArticle::query()->create([
            'iha_id' => '20260509AWTEST03',
            'title' => ['tr' => 'Dry run URL test haberi'],
            'slug' => 'dry-run-url-test-haberi',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Govde'],
            'meta_title' => ['tr' => 'Dry run URL test haberi'],
            'meta_description' => ['tr' => 'Ozet'],
            'source' => 'iha',
            'source_url' => '',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
        ]);

        $this->mock(IhaPublicArticleResolverService::class, function ($mock) {
            $mock->shouldReceive('resolve')
                ->once()
                ->andReturn(['url' => 'https://www.iha.com.tr/gundem-haberleri/dry-run-url-test-haberi-789']);
        });

        $exitCode = Artisan::call('iha:enrich-source-urls', [
            '--limit' => 10,
            '--dry-run' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('IHA_SOURCE_URL_ENRICHMENT scanned=1 matched=1 updated=0 unresolved=0 dry_run=yes', $output);
        $this->assertSame('', $article->refresh()->source_url);
    }

    public function test_command_can_validate_a_known_public_url_for_a_slug(): void
    {
        $category = $this->category();
        $article = NewsArticle::query()->create([
            'iha_id' => '20260509AWTEST04',
            'title' => ['tr' => 'Bilinen URL test haberi'],
            'slug' => 'bilinen-url-test-haberi',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Govde'],
            'meta_title' => ['tr' => 'Bilinen URL test haberi'],
            'meta_description' => ['tr' => 'Ozet'],
            'source' => 'iha',
            'source_url' => 'https://www.iha.com.tr',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
        ]);

        $knownUrl = 'https://www.iha.com.tr/gundem-haberleri/bilinen-url-test-haberi-999';

        $this->mock(IhaPublicArticleResolverService::class, function ($mock) use ($knownUrl) {
            $mock->shouldReceive('resolveFromUrl')
                ->once()
                ->withArgs(fn (NewsArticle $article, string $url) => $article->slug === 'bilinen-url-test-haberi' && $url === $knownUrl)
                ->andReturn(['url' => $knownUrl]);
        });

        $exitCode = Artisan::call('iha:enrich-source-urls', [
            '--url' => ['bilinen-url-test-haberi='.$knownUrl],
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame($knownUrl, $article->refresh()->source_url);
    }

    public function test_command_rejects_manual_url_outside_iha_domain(): void
    {
        $category = $this->category();
        $article = NewsArticle::query()->create([
            'iha_id' => '20260509AWTEST05',
            'title' => ['tr' => 'Mirror URL test haberi'],
            'slug' => 'mirror-url-test-haberi',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Govde'],
            'meta_title' => ['tr' => 'Mirror URL test haberi'],
            'meta_description' => ['tr' => 'Ozet'],
            'source' => 'iha',
            'source_url' => 'https://www.iha.com.tr',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subMinutes(10),
        ]);

        $this->mock(IhaPublicArticleResolverService::class, function ($mock) {
            $mock->shouldNotReceive('resolveFromUrl');
            $mock->shouldNotReceive('resolve');
        });

        $exitCode = Artisan::call('iha:enrich-source-urls', [
            '--url' => ['mirror-url-test-haberi=https://example.com/mirror-url-test-haberi'],
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('GECERSIZ IHA URL: mirror-url-test-haberi -> https://example.com/mirror-url-test-haberi', $output);
        $this->assertStringContainsString('IHA_SOURCE_URL_ENRICHMENT scanned=1 matched=0 updated=0 unresolved=1 dry_run=no', $output);
        $this->assertSame('https://www.iha.com.tr', $article->refresh()->source_url);
    }

    private function category(): Category
    {
        return Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
        ]);
    }
}
