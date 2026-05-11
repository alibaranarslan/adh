<?php

namespace App\Services;

use App\Jobs\TranslateArticleJob;
use App\Models\NewsArticle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IhaTranslationRequeueService
{
    public function countBacklog(): int
    {
        $count = 0;

        NewsArticle::query()
            ->fromIha()
            ->select(['id', 'title', 'summary', 'content', 'meta_title', 'meta_description'])
            ->orderBy('id')
            ->chunkById(100, function ($articles) use (&$count): void {
                foreach ($articles as $article) {
                    if ($this->articleNeedsTranslation($article)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function countQueuedJobs(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')
            ->where('payload', 'like', '%TranslateArticleJob%')
            ->count();
    }

    /**
     * @return array{backlog:int, queued:int, skipped_duplicates:int, chunk_size:int}
     */
    public function requeueMissingTranslations(int $chunkSize = 100): array
    {
        $queuedArticleIds = $this->queuedTranslationArticleIds();
        $backlog = 0;
        $queued = 0;
        $skippedDuplicates = 0;
        $chunkSize = max(1, $chunkSize);

        NewsArticle::query()
            ->fromIha()
            ->select(['id', 'title', 'summary', 'content', 'meta_title', 'meta_description'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($articles) use (&$backlog, &$queued, &$skippedDuplicates, $queuedArticleIds): void {
                foreach ($articles as $article) {
                    if (! $this->articleNeedsTranslation($article)) {
                        continue;
                    }

                    $backlog++;

                    if (isset($queuedArticleIds[$article->id]) || Cache::has($this->dedupeCacheKey($article->id))) {
                        $skippedDuplicates++;

                        continue;
                    }

                    TranslateArticleJob::dispatch($article->id, true);
                    Cache::put($this->dedupeCacheKey($article->id), true, now()->addHours(6));
                    $queued++;
                }
            });

        return [
            'backlog' => $backlog,
            'queued' => $queued,
            'skipped_duplicates' => $skippedDuplicates,
            'chunk_size' => $chunkSize,
        ];
    }

    /**
     * @return array<int, true>
     */
    public function queuedTranslationArticleIds(): array
    {
        if (! Schema::hasTable('jobs')) {
            return [];
        }

        $ids = [];

        DB::table('jobs')
            ->where('payload', 'like', '%TranslateArticleJob%')
            ->orderBy('id')
            ->pluck('payload')
            ->each(function (string $payload) use (&$ids): void {
                $decoded = json_decode($payload, true);
                $command = (string) data_get($decoded, 'data.command', $payload);

                if (preg_match_all('/articleId";i:(\d+)/', $command, $matches) !== false) {
                    foreach ($matches[1] ?? [] as $id) {
                        $ids[(int) $id] = true;
                    }
                }
            });

        return $ids;
    }

    public function articleNeedsTranslation(NewsArticle $article): bool
    {
        foreach (['title', 'summary', 'content', 'meta_title', 'meta_description'] as $field) {
            $turkishValue = trim((string) ($article->getTranslation($field, 'tr', false) ?? ''));

            if ($turkishValue === '') {
                continue;
            }

            foreach (['en', 'ku'] as $locale) {
                $translatedValue = trim((string) ($article->getTranslation($field, $locale, false) ?? ''));

                if ($translatedValue === '') {
                    return true;
                }
            }
        }

        return false;
    }

    private function dedupeCacheKey(int $articleId): string
    {
        return "iha.translation.requeue.article.{$articleId}";
    }
}
