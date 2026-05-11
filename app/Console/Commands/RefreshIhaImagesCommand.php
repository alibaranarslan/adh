<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Services\IhaApiService;
use App\Services\IhaImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RefreshIhaImagesCommand extends Command
{
    protected $signature = 'iha:refresh-images
        {--hours=24 : Son kac saatin haberlerini kontrol et}
        {--limit= : Islenecek en fazla haber sayisi}';

    protected $description = 'Son saatlerdeki IHA haberlerinin eksik gorsellerini tek feed gecisi ile yenile';

    public function handle(IhaApiService $apiService, IhaImageService $imageService): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;

        $articlesQuery = NewsArticle::fromIha()
            ->where(function ($query) {
                $query->whereNull('featured_image')->orWhere('featured_image', '');
            })
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderByDesc('published_at');

        if ($limit !== null) {
            $articlesQuery->limit($limit);
        }

        $articles = $articlesQuery->get();

        if ($articles->isEmpty()) {
            $this->info('Eksik gorselli IHA haberi yok.');

            return self::SUCCESS;
        }

        $feedItems = collect($apiService->fetchNews(null, 0, true))
            ->filter(fn (array $item): bool => ! empty($item['iha_id']))
            ->keyBy('iha_id');

        $refreshed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            $matched = $this->matchFeedItem($feedItems, $article->iha_id);

            if ($matched === null || empty($matched['image_url'])) {
                $skipped++;

                continue;
            }

            try {
                $imagePath = $imageService->downloadImage($matched['image_url'], $article->slug);

                if (! $imagePath) {
                    $failed++;

                    continue;
                }

                $article->update(['featured_image' => '/storage/' . ltrim($imagePath, '/')]);
                $refreshed++;
            } catch (\Throwable $e) {
                Log::warning('IHA gorsel yenileme hatasi', [
                    'article_id' => $article->id,
                    'iha_id' => $article->iha_id,
                    'message' => $e->getMessage(),
                ]);

                $failed++;
            }
        }

        Log::info('IHA gorsel yenileme tamamlandi', [
            'candidates' => $articles->count(),
            'refreshed' => $refreshed,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        $this->info(sprintf(
            'IHA gorsel yenileme tamamlandi. Aday: %d | Yenilenen: %d | Atlanan: %d | Basarisiz: %d',
            $articles->count(),
            $refreshed,
            $skipped,
            $failed,
        ));

        return self::SUCCESS;
    }

    private function matchFeedItem(Collection $feedItems, ?string $ihaId): ?array
    {
        if (blank($ihaId)) {
            return null;
        }

        $matched = $feedItems->get($ihaId);

        return is_array($matched) ? $matched : null;
    }
}
