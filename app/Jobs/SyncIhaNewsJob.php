<?php

namespace App\Jobs;

use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use App\Services\EditorialScoreService;
use App\Services\IhaApiService;
use App\Services\IhaCategoryMapper;
use App\Services\IhaImageService;
use App\Services\IhaSyncException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncIhaNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUALITY_RISK_PREFIX = 'QUALITY_RISK';

    public int $timeout = 900;
    public int $tries = 3;
    public int $backoff = 120;

    private ?int $cityCode;
    private int $syncLogId;
    private ?int $limit;

    public function __construct(?int $cityCode = null, int $syncLogId = 0, ?int $limit = null)
    {
        $this->cityCode = $cityCode;
        $this->syncLogId = $syncLogId;
        $this->limit = $limit !== null ? max(0, $limit) : null;
    }

    public function handle(
        IhaApiService $apiService,
        IhaCategoryMapper $categoryMapper,
        IhaImageService $imageService
    ): void {
        $syncLog = IhaSyncLog::find($this->syncLogId);

        if ($syncLog !== null && $syncLog->status !== 'running') {
            Log::info('IHA sync job atlandi; log artik aktif degil', [
                'sync_log_id' => $this->syncLogId,
                'status' => $syncLog->status,
            ]);

            return;
        }

        $stats = [
            'articles_fetched' => 0,
            'articles_created' => 0,
            'articles_updated' => 0,
            'articles_skipped' => 0,
            'images_downloaded' => 0,
        ];
        $qualityStats = [
            'affected_articles' => 0,
            'empty_content_articles' => 0,
            'body_not_deeper_than_summary_articles' => 0,
            'short_body_articles' => 0,
            'examples' => [],
        ];
        $processingFailures = 0;
        $processingErrorMessage = null;

        try {
            $articles = $apiService->fetchNews($this->cityCode, 0, true);

            if ($this->limit !== null) {
                $articles = array_slice($articles, 0, $this->limit);
            }

            $stats['articles_fetched'] = count($articles);

            if ($stats['articles_fetched'] === 0) {
                $message = 'IHA akisindan haber alinamadi. Baglanti, kimlik bilgileri ve uzak servis durumunu kontrol edin.';

                if ($syncLog) {
                    $syncLog->update(array_merge($stats, [
                        'status' => 'partial',
                        'completed_at' => now(),
                        'error_message' => $message,
                    ]));
                }

                Log::warning('IHA sync kismi tamamlandi', array_merge($stats, [
                    'city_code' => $this->cityCode,
                    'message' => $message,
                ]));

                return;
            }

            $requestDelay = (int) config('services.iha.request_delay', 1);

            foreach ($articles as $index => $articleData) {
                if ($index > 0 && $requestDelay > 0) {
                    sleep($requestDelay);
                }

                try {
                    $result = $this->processArticle($articleData, $categoryMapper, $imageService);
                    $this->recordQualityRisk($qualityStats, $result['quality'] ?? null, $result['article'] ?? null);

                    if (($result['result'] ?? 'skipped') === 'created') {
                        $stats['articles_created']++;

                        if (! empty($articleData['image_url'])) {
                            $stats['images_downloaded']++;
                        }
                    } elseif (($result['result'] ?? 'skipped') === 'updated') {
                        $stats['articles_updated']++;
                    } else {
                        $stats['articles_skipped']++;
                    }
                } catch (Throwable $e) {
                    Log::warning('IHA haber isleme hatasi', [
                        'iha_id' => $articleData['iha_id'] ?? null,
                        'title' => $articleData['title'] ?? '',
                        'message' => $e->getMessage(),
                    ]);

                    $processingFailures++;
                    $processingErrorMessage ??= $e->getMessage();
                    $stats['articles_skipped']++;
                }
            }

            if ($syncLog) {
                $status = 'success';
                $errorMessage = $this->buildQualityRiskNote($qualityStats);

                if ($processingFailures > 0) {
                    $status = ($processingFailures === $stats['articles_fetched']
                        && $stats['articles_created'] === 0
                        && $stats['articles_updated'] === 0)
                        ? 'failed'
                        : 'partial';

                    $errorMessage = sprintf(
                        'IHA sync isleme hatalari: %d/%d makale basarisiz. Ilk hata: %s',
                        $processingFailures,
                        $stats['articles_fetched'],
                        $processingErrorMessage ?? 'UNVERIFIED'
                    );

                    $qualityNote = $this->buildQualityRiskNote($qualityStats);
                    if ($qualityNote !== null) {
                        $errorMessage .= "\n" . $qualityNote;
                    }
                }

                $syncLog->update(array_merge($stats, [
                    'status' => $status,
                    'completed_at' => now(),
                    'error_message' => $errorMessage,
                ]));
            }

            Log::info('IHA sync tamamlandi', $stats);

            if ($qualityStats['affected_articles'] > 0) {
                Log::warning('IHA sync govde kalite riski algilandi', [
                    'city_code' => $this->cityCode,
                    'affected_articles' => $qualityStats['affected_articles'],
                    'empty_content_articles' => $qualityStats['empty_content_articles'],
                    'body_not_deeper_than_summary_articles' => $qualityStats['body_not_deeper_than_summary_articles'],
                    'short_body_articles' => $qualityStats['short_body_articles'],
                    'examples' => $qualityStats['examples'],
                ]);
            }
        } catch (IhaSyncException|Throwable $e) {
            Log::error('IHA sync job hatasi', [
                'message' => $e->getMessage(),
                'city_code' => $this->cityCode,
            ]);

            if ($syncLog) {
                $syncLog->update(array_merge($stats, [
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]));
            }
        }
    }

    private function processArticle(
        array $articleData,
        IhaCategoryMapper $categoryMapper,
        IhaImageService $imageService
    ): array {
        $ihaId = $articleData['iha_id'] ?? null;

        if (empty($ihaId)) {
            return ['result' => 'skipped', 'quality' => null, 'article' => null];
        }

        $title = $this->normalizeArticleText($articleData['title'] ?? '');
        $summary = $this->normalizeArticleText($articleData['summary'] ?? '');
        $content = $this->normalizeArticleText($articleData['content'] ?? $summary);
        $metaDescription = $this->buildMetaDescription($summary, $content);
        $quality = $this->assessBodyQuality($summary, $content);
        $articleMeta = [
            'iha_id' => $ihaId,
            'slug' => $articleData['slug'] ?? null,
            'title' => $title,
        ];

        if ($title === '') {
            return ['result' => 'skipped', 'quality' => $quality, 'article' => $articleMeta];
        }

        $existing = NewsArticle::where('iha_id', $ihaId)->first();

        if ($existing) {
            $articleMeta['slug'] = $existing->slug;
            $shouldRetranslate = false;

            $this->syncTurkishTranslation($existing, 'title', $title, $shouldRetranslate);
            $this->syncTurkishTranslation($existing, 'summary', $summary, $shouldRetranslate);
            $this->syncTurkishTranslation($existing, 'content', $content, $shouldRetranslate);
            $this->syncTurkishTranslation($existing, 'meta_title', $title, $shouldRetranslate);
            $this->syncTurkishTranslation($existing, 'meta_description', $metaDescription, $shouldRetranslate);

            if ($this->hasBodyQualityRisk($quality) && $existing->status === 'published') {
                $existing->status = 'draft';
            }

            if (! empty($articleData['image_url']) && empty($existing->featured_image)) {
                $downloaded = $imageService->downloadImage($articleData['image_url'], $existing->slug);

                if ($downloaded) {
                    $existing->featured_image = '/storage/' . ltrim($downloaded, '/');
                }
            }

            if (! $existing->isDirty()) {
                return ['result' => 'skipped', 'quality' => $quality, 'article' => $articleMeta];
            }

            $existing->save();

            if ($shouldRetranslate && $existing->status === 'published') {
                $this->dispatchTranslation($existing->id, true);
            }

            return ['result' => 'updated', 'quality' => $quality, 'article' => $articleMeta];
        }

        $categoryId = $categoryMapper->mapFromArticle($articleData);
        $localityScore = $categoryMapper->localityScore($articleData);
        $citySlug = $categoryMapper->detectCitySlug($articleData);
        $editorialScore = EditorialScoreService::computeFromRaw($articleData, $localityScore);
        $slug = $this->generateUniqueSlug(Str::slug($title, '-'));

        $featuredImage = null;

        if (! empty($articleData['image_url'])) {
            $featuredImage = $imageService->downloadImage($articleData['image_url'], $slug);
        }

        if ($featuredImage) {
            $featuredImage = '/storage/' . ltrim($featuredImage, '/');
        }

        $article = NewsArticle::create([
            'iha_id' => $ihaId,
            'title' => ['tr' => $title],
            'slug' => $slug,
            'summary' => ['tr' => $summary],
            'content' => ['tr' => $content],
            'meta_title' => ['tr' => $title],
            'meta_description' => ['tr' => $metaDescription],
            'featured_image' => $featuredImage,
            'source' => 'iha',
            'source_url' => $articleData['source_url'] ?: 'https://www.iha.com.tr',
            'category_id' => $categoryId,
            'city_code' => $localityScore,
            'city_slug' => $citySlug,
            'editorial_score' => $editorialScore,
            'status' => $this->hasBodyQualityRisk($quality) ? 'draft' : 'published',
            'is_breaking' => $articleData['son_dakika'] ?? false,
            'published_at' => $articleData['published_at'] ?? now(),
        ]);

        if ($article->status === 'published') {
            $this->dispatchTranslation($article->id);
        }
        $articleMeta['slug'] = $article->slug;

        return ['result' => 'created', 'quality' => $quality, 'article' => $articleMeta];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('IHA sync job tamamen basarisiz', [
            'message' => $exception->getMessage(),
            'syncLogId' => $this->syncLogId,
        ]);

        $syncLog = IhaSyncLog::find($this->syncLogId);

        if ($syncLog && $syncLog->status === 'running') {
            $syncLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Job tum denemelerden sonra basarisiz oldu: ' . $exception->getMessage(),
            ]);
        }
    }

    private function generateUniqueSlug(string $slug): string
    {
        $original = $slug;
        $counter = 1;

        while (NewsArticle::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    private function dispatchTranslation(int $articleId, bool $force = false): void
    {
        TranslateArticleJob::dispatch($articleId, $force)->delay(now()->addSeconds(5));
    }

    private function syncTurkishTranslation(NewsArticle $article, string $field, string $value, bool &$changed): void
    {
        if ($value === '') {
            return;
        }

        $current = trim((string) ($article->getTranslation($field, 'tr', false) ?? ''));

        if ($current === $value) {
            return;
        }

        $article->setTranslation($field, 'tr', $value);
        $changed = true;
    }

    private function normalizeArticleText(?string $value): string
    {
        return trim((string) $value);
    }

    private function buildMetaDescription(string $summary, string $content): string
    {
        $source = $summary !== '' ? $summary : strip_tags($content);

        return trim((string) Str::limit($source, 155, '...'));
    }

    private function assessBodyQuality(string $summary, string $content): array
    {
        $summaryLen = mb_strlen(strip_tags($summary));
        $contentLen = mb_strlen(strip_tags($content));
        $minBodyLength = $this->minimumBodyLength();

        return [
            'summary_len' => $summaryLen,
            'content_len' => $contentLen,
            'empty_content' => $contentLen === 0,
            'body_not_deeper_than_summary' => $contentLen > 0 && $contentLen <= $summaryLen,
            'short_body' => $contentLen > 0 && $contentLen < $minBodyLength,
            'min_body_length' => $minBodyLength,
        ];
    }

    private function recordQualityRisk(array &$qualityStats, ?array $quality, ?array $articleMeta): void
    {
        if ($quality === null) {
            return;
        }

        if (! $this->hasBodyQualityRisk($quality)) {
            return;
        }

        $qualityStats['affected_articles']++;
        $qualityStats['empty_content_articles'] += (int) ($quality['empty_content'] ?? false);
        $qualityStats['body_not_deeper_than_summary_articles'] += (int) ($quality['body_not_deeper_than_summary'] ?? false);
        $qualityStats['short_body_articles'] += (int) ($quality['short_body'] ?? false);

        if (count($qualityStats['examples']) < 3) {
            $qualityStats['examples'][] = $articleMeta['slug']
                ?? $articleMeta['iha_id']
                ?? $articleMeta['title']
                ?? 'UNVERIFIED';
        }
    }

    private function buildQualityRiskNote(array $qualityStats): ?string
    {
        if (($qualityStats['affected_articles'] ?? 0) === 0) {
            return null;
        }

        $parts = [
            self::QUALITY_RISK_PREFIX,
            'affected=' . ($qualityStats['affected_articles'] ?? 0),
            'empty_content=' . ($qualityStats['empty_content_articles'] ?? 0),
            'body_not_deeper_than_summary=' . ($qualityStats['body_not_deeper_than_summary_articles'] ?? 0),
            'short_body=' . ($qualityStats['short_body_articles'] ?? 0),
            'min_body_length=' . $this->minimumBodyLength(),
        ];

        if (! empty($qualityStats['examples'])) {
            $parts[] = 'examples=' . implode(',', $qualityStats['examples']);
        }

        return implode(' ', $parts);
    }

    private function minimumBodyLength(): int
    {
        return max(1, (int) config('services.iha.min_body_length', 280));
    }

    private function hasBodyQualityRisk(array $quality): bool
    {
        return ($quality['empty_content'] ?? false)
            || ($quality['body_not_deeper_than_summary'] ?? false)
            || ($quality['short_body'] ?? false);
    }
}
