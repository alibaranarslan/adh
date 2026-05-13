<?php

namespace App\Console\Commands;

use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MonitorIhaForwardIngestCommand extends Command
{
    private const QUALITY_RISK_PREFIX = 'QUALITY_RISK';
    private const RUNNING_WARN_MINUTES = 30;
    private const RUNNING_CRITICAL_MINUTES = 120;

    protected $signature = 'iha:monitor-forward
        {--limit=20 : Son kac yeni IHA kaydi uzerinden kalite penceresi hesaplanacak}';

    protected $description = 'IHA forward ingest sagligini read-only olarak raporla';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $latestSync = IhaSyncLog::query()->latest('id')->first();
        $recentArticles = NewsArticle::query()
            ->where('source', 'iha')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $metrics = $this->buildMetrics($latestSync, $recentArticles, $limit);
        $summary = sprintf(
            'IHA_FORWARD_MONITOR health=%s sync_status=%s running_age_minutes=%s quality_risk=%s quality_affected=%d freshness_minutes=%s fetched=%d created=%d updated=%d skipped=%d window=%d empty_content=%d weak_body=%d short_body=%d body_depth_ratio=%.2f generic_source_url_ratio=%.2f',
            $metrics['health'],
            $metrics['sync_status'],
            $metrics['running_age_minutes'] === null ? 'none' : $metrics['running_age_minutes'],
            $metrics['quality_risk'] ? 'yes' : 'no',
            $metrics['quality_risk_affected'],
            $metrics['freshness_minutes'] === null ? 'UNVERIFIED' : $metrics['freshness_minutes'],
            $metrics['fetched'],
            $metrics['created'],
            $metrics['updated'],
            $metrics['skipped'],
            $metrics['window_count'],
            $metrics['empty_content_count'],
            $metrics['weak_body_count'],
            $metrics['short_body_count'],
            $metrics['body_depth_ratio'],
            $metrics['generic_source_url_ratio'],
        );

        $this->line($summary);
        $this->newLine();
        $this->line('Last Sync');
        $this->line(sprintf('  status: %s', $metrics['sync_status']));
        $this->line(sprintf('  running_age_minutes: %s', $metrics['running_age_minutes'] === null ? 'none' : $metrics['running_age_minutes']));
        $this->line(sprintf('  quality_risk: %s', $metrics['quality_risk'] ? 'yes' : 'no'));
        $this->line(sprintf('  quality_affected: %d', $metrics['quality_risk_affected']));
        $this->line(sprintf('  freshness_minutes: %s', $metrics['freshness_minutes'] === null ? 'UNVERIFIED' : $metrics['freshness_minutes']));
        $this->line(sprintf('  fetched/created/updated/skipped: %d/%d/%d/%d', $metrics['fetched'], $metrics['created'], $metrics['updated'], $metrics['skipped']));

        if ($metrics['quality_risk_note'] !== null) {
            $this->line(sprintf('  quality_note: %s', $metrics['quality_risk_note']));
        }

        $this->newLine();
        $this->line(sprintf('Recent IHA Window (last %d by created_at)', $limit));
        $this->line(sprintf('  window_count: %d', $metrics['window_count']));
        $this->line(sprintf('  empty_content_count: %d', $metrics['empty_content_count']));
        $this->line(sprintf('  weak_body_count: %d', $metrics['weak_body_count']));
        $this->line(sprintf('  short_body_count: %d', $metrics['short_body_count']));
        $this->line(sprintf('  body_depth_ratio: %.2f', $metrics['body_depth_ratio']));
        $this->line(sprintf('  generic_source_url_ratio: %.2f', $metrics['generic_source_url_ratio']));

        if ($metrics['latest_articles']->isNotEmpty()) {
            $this->newLine();
            $this->line('Sample Articles');
            foreach ($metrics['latest_articles'] as $articleMetric) {
                $this->line(sprintf(
                    '  - %s | summary_len=%d | content_len=%d | empty=%s | weak=%s | short=%s | generic_source_url=%s',
                    $articleMetric['slug'],
                    $articleMetric['summary_len'],
                    $articleMetric['content_len'],
                    $articleMetric['empty_content'] ? 'yes' : 'no',
                    $articleMetric['weak_body'] ? 'yes' : 'no',
                    $articleMetric['short_body'] ? 'yes' : 'no',
                    $articleMetric['generic_source_url'] ? 'yes' : 'no',
                ));
            }
        }

        return in_array($metrics['health'], ['critical'], true)
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function buildMetrics(?IhaSyncLog $latestSync, Collection $recentArticles, int $limit): array
    {
        $articleMetrics = $recentArticles->map(function (NewsArticle $article): array {
            $minBodyLength = $this->minimumBodyLength();
            $summary = trim((string) ($article->getTranslation('summary', 'tr', false) ?? ''));
            $content = trim((string) ($article->getTranslation('content', 'tr', false) ?? ''));
            $summaryLen = mb_strlen(strip_tags($summary));
            $contentLen = mb_strlen(strip_tags($content));
            $sourceUrl = trim((string) ($article->source_url ?? ''));
            $emptyContent = $contentLen === 0;
            $weakBody = $contentLen > 0 && $contentLen <= $summaryLen;
            $shortBody = $contentLen > 0 && $contentLen < $minBodyLength;

            return [
                'slug' => $article->slug,
                'summary_len' => $summaryLen,
                'content_len' => $contentLen,
                'empty_content' => $emptyContent,
                'weak_body' => $weakBody,
                'short_body' => $shortBody,
                'body_deeper_than_summary' => $contentLen > $summaryLen,
                'generic_source_url' => $sourceUrl === 'https://www.iha.com.tr' || $sourceUrl === '',
            ];
        });

        $windowCount = $articleMetrics->count();
        $emptyContentCount = $articleMetrics->where('empty_content', true)->count();
        $weakBodyCount = $articleMetrics->where('weak_body', true)->count();
        $shortBodyCount = $articleMetrics->where('short_body', true)->count();
        $bodyDepthCount = $articleMetrics->where('body_deeper_than_summary', true)->count();
        $genericSourceUrlCount = $articleMetrics->where('generic_source_url', true)->count();

        $freshnessMinutes = null;
        if ($latestSync?->completed_at) {
            $freshnessMinutes = max(0, (int) $latestSync->completed_at->diffInMinutes(now()));
        } elseif ($latestSync?->started_at) {
            $freshnessMinutes = max(0, (int) $latestSync->started_at->diffInMinutes(now()));
        }

        $runningAgeMinutes = null;
        if ($latestSync?->status === 'running' && $latestSync->started_at) {
            $runningAgeMinutes = max(0, (int) $latestSync->started_at->diffInMinutes(now()));
        }

        $qualityRisk = $this->extractQualityRisk($latestSync?->error_message);
        $bodyDepthRatio = $windowCount > 0 ? $bodyDepthCount / $windowCount : 0.0;
        $genericSourceUrlRatio = $windowCount > 0 ? $genericSourceUrlCount / $windowCount : 0.0;

        return [
            'health' => $this->determineHealth($latestSync, $freshnessMinutes, $runningAgeMinutes, $windowCount, $emptyContentCount, $weakBodyCount, $shortBodyCount, $bodyDepthRatio, $qualityRisk),
            'sync_status' => $latestSync?->status ?? 'UNVERIFIED',
            'running_age_minutes' => $runningAgeMinutes,
            'quality_risk' => $qualityRisk !== null,
            'quality_risk_affected' => $qualityRisk['affected'] ?? 0,
            'quality_risk_note' => $qualityRisk['raw'] ?? null,
            'freshness_minutes' => $freshnessMinutes,
            'fetched' => $latestSync?->articles_fetched ?? 0,
            'created' => $latestSync?->articles_created ?? 0,
            'updated' => $latestSync?->articles_updated ?? 0,
            'skipped' => $latestSync?->articles_skipped ?? 0,
            'window_count' => $windowCount,
            'empty_content_count' => $emptyContentCount,
            'weak_body_count' => $weakBodyCount,
            'short_body_count' => $shortBodyCount,
            'body_depth_ratio' => $bodyDepthRatio,
            'generic_source_url_ratio' => $genericSourceUrlRatio,
            'latest_articles' => $articleMetrics->take(min($limit, 5))->values(),
        ];
    }

    private function determineHealth(
        ?IhaSyncLog $latestSync,
        ?int $freshnessMinutes,
        ?int $runningAgeMinutes,
        int $windowCount,
        int $emptyContentCount,
        int $weakBodyCount,
        int $shortBodyCount,
        float $bodyDepthRatio,
        ?array $qualityRisk
    ): string {
        if (! $latestSync) {
            return 'critical';
        }

        if ($latestSync->status === 'failed') {
            return 'critical';
        }

        if ($latestSync->status === 'running') {
            if ($runningAgeMinutes === null || $runningAgeMinutes >= self::RUNNING_CRITICAL_MINUTES) {
                return 'critical';
            }

            if ($runningAgeMinutes >= self::RUNNING_WARN_MINUTES) {
                return 'warn';
            }
        }

        if ($freshnessMinutes === null || $freshnessMinutes > 24 * 60) {
            return 'warn';
        }

        if ($windowCount === 0) {
            return 'warn';
        }

        if ($emptyContentCount >= 3 || $bodyDepthRatio < 0.70) {
            return 'critical';
        }

        if (($qualityRisk['affected'] ?? 0) >= 3) {
            return 'warn';
        }

        if ($emptyContentCount > 0 || $weakBodyCount > 0 || $shortBodyCount > 0 || $bodyDepthRatio < 0.90 || $latestSync->status === 'partial' || $qualityRisk !== null) {
            return 'warn';
        }

        return 'healthy';
    }

    private function extractQualityRisk(?string $errorMessage): ?array
    {
        if ($errorMessage === null || trim($errorMessage) === '') {
            return null;
        }

        foreach (preg_split('/\r\n|\r|\n/', $errorMessage) ?: [] as $line) {
            $line = trim($line);
            if (! str_starts_with($line, self::QUALITY_RISK_PREFIX)) {
                continue;
            }

            preg_match_all('/([a-z_]+)=([^\s]+)/', $line, $matches, PREG_SET_ORDER);
            $parsed = ['raw' => $line];
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $match[2];
                $parsed[$key] = ctype_digit($value) ? (int) $value : $value;
            }

            return $parsed;
        }

        return null;
    }

    private function minimumBodyLength(): int
    {
        return max(1, (int) config('services.iha.min_body_length', 280));
    }
}
