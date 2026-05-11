<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Models\Setting;
use Illuminate\Console\Command;

class ArchiveOldNewsCommand extends Command
{
    protected $signature = 'news:archive
                            {--dry-run : Show what would be archived without making changes}';

    protected $description = 'Archive old news articles based on settings without deleting archived articles';

    public function handle(): int
    {
        $activeDays = (int) Setting::get('general', 'archive_active_days', 90);
        $isDryRun   = $this->option('dry-run');
        $archivedTotalBefore = NewsArticle::where('status', 'archived')->count();

        // Move published articles older than threshold to archived
        $archiveQuery = NewsArticle::where('status', 'published')
            ->where('published_at', '<', now()->subDays($activeDays));

        $archiveCount = $archiveQuery->count();

        if ($archiveCount > 0) {
            $this->info("Found {$archiveCount} article(s) to archive (older than {$activeDays} days).");
            if (! $isDryRun) {
                $archiveQuery->update([
                    'status' => 'archived',
                    'archived_at' => now(),
                ]);
                $this->info("Archived {$archiveCount} article(s).");
            }
        } else {
            $this->line('No articles to archive.');
        }

        $archivedTotal = $isDryRun
            ? $archivedTotalBefore
            : NewsArticle::where('status', 'archived')->count();

        $this->line("Archived articles total: {$archivedTotal} (permanent deletion disabled per K24).");

        if ($isDryRun) {
            $this->comment('Dry run: no changes made.');
        }

        return self::SUCCESS;
    }
}
