<?php

namespace Tests\Feature\Commands;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsArchiveCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_command_moves_old_published_news_without_purging_archived_content(): void
    {
        $category = Category::create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Setting::set('general', 'archive_active_days', 90);

        $oldPublished = NewsArticle::create([
            'title' => ['tr' => 'Eski Yayin Haberi'],
            'slug' => 'eski-yayin-haberi',
            'summary' => ['tr' => 'Ozet bilgi'],
            'content' => ['tr' => 'Detayli haber icerigi'],
            'featured_image' => '/images/test-old-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(91),
            'editorial_score' => 40,
        ]);

        $alreadyArchived = NewsArticle::create([
            'title' => ['tr' => 'Mevcut Arsiv Haberi'],
            'slug' => 'mevcut-arsiv-haberi',
            'summary' => ['tr' => 'Arsiv ozet'],
            'content' => ['tr' => 'Arsiv detay'],
            'featured_image' => '/images/test-archived-news.jpg',
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'archived',
            'published_at' => now()->subDays(180),
            'archived_at' => now()->subDays(90),
            'editorial_score' => 10,
        ]);

        $this->artisan('news:archive --dry-run')
            ->expectsOutput('Found 1 article(s) to archive (older than 90 days).')
            ->expectsOutput('Archived articles total: 1 (permanent deletion disabled per K24).')
            ->expectsOutput('Dry run: no changes made.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('news_articles', [
            'id' => $oldPublished->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('news_articles', [
            'id' => $alreadyArchived->id,
            'status' => 'archived',
        ]);

        $this->artisan('news:archive')
            ->expectsOutput('Found 1 article(s) to archive (older than 90 days).')
            ->expectsOutput('Archived 1 article(s).')
            ->expectsOutput('Archived articles total: 2 (permanent deletion disabled per K24).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('news_articles', [
            'id' => $oldPublished->id,
            'status' => 'archived',
        ]);

        $this->assertDatabaseHas('news_articles', [
            'id' => $alreadyArchived->id,
            'status' => 'archived',
        ]);

        $this->assertDatabaseCount('news_articles', 2);
        $this->assertNotNull($oldPublished->fresh()->archived_at);
    }
}
