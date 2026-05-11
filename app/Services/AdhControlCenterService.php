<?php

namespace App\Services;

use App\Filament\Pages\Analytics;
use App\Filament\Pages\BackupManager;
use App\Filament\Pages\CacheManagement;
use App\Filament\Pages\IhaHealth;
use App\Filament\Pages\LayoutStudio;
use App\Filament\Resources\NewsArticleResource;
use App\Models\AnalyticsPageView;
use App\Models\IhaSyncLog;
use App\Models\LayoutRevision;
use App\Models\NewsArticle;
use App\Models\User;
use App\Support\AdminPrivileges;
use App\Support\AdminSafeText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdhControlCenterService
{
    public function __construct(
        private readonly LayoutConfigService $layoutConfigService,
        private readonly InstagramService $instagramService,
    ) {
    }

    public function snapshot(array $filters, ?User $user): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $window = $this->resolveWindow($normalizedFilters['window']);
        $isEditor = AdminPrivileges::canAccessConfiguration($user);
        $isOps = AdminPrivileges::canManageSystemSettings($user);
        $thresholds = config('control_center.attention', []);

        $latestSync = IhaSyncLog::query()->latest('started_at')->first();
        $lastSuccessfulSync = IhaSyncLog::query()
            ->where('status', 'success')
            ->latest('completed_at')
            ->first();
        $lastFailedSync = IhaSyncLog::query()
            ->where('status', 'failed')
            ->latest('completed_at')
            ->first();

        $freshnessLagMinutes = $lastSuccessfulSync?->completed_at?->diffInMinutes(now());
        $failedJobsCount = $this->failedJobsCount();
        $translationBacklog = $this->countTranslationBacklog();
        $queuedTranslations = $this->countQueuedTranslationJobs();
        $criticalDrafts = $this->criticalDrafts($normalizedFilters['source']);

        $attention = collect();

        if ($freshnessLagMinutes === null || $freshnessLagMinutes > (int) ($thresholds['iha_stale_minutes'] ?? 45)) {
            $attention->push([
                'tone' => 'danger',
                'title' => 'İHA akışı geriden geliyor',
                'body' => $freshnessLagMinutes === null
                    ? 'Başarılı bir senkron kaydı görünmüyor. Akışı İHA Sağlığı ekranından kontrol edin.'
                    : "Son başarılı senkron {$freshnessLagMinutes} dakika önce tamamlandı.",
                'meta' => 'Entegrasyon',
                'url' => IhaHealth::getUrl(panel: 'admin'),
                'action_label' => 'İHA Sağlığı',
            ]);
        }

        if ($failedJobsCount > 0) {
            $attention->push([
                'tone' => 'danger',
                'title' => 'Kuyrukta başarısız iş var',
                'body' => "{$failedJobsCount} failed job kaydı müdahale bekliyor.",
                'meta' => 'Operasyon',
                'url' => $isOps ? CacheManagement::getUrl(panel: 'admin') : IhaHealth::getUrl(panel: 'admin'),
                'action_label' => $isOps ? 'Operasyon' : 'Sağlık',
            ]);
        }

        if ($translationBacklog >= (int) ($thresholds['translation_backlog_warning'] ?? 3)) {
            $attention->push([
                'tone' => 'warning',
                'title' => 'Çeviri backlog’u birikti',
                'body' => "{$translationBacklog} İHA haberi EN/KU çeviri tamamlanmasını bekliyor.",
                'meta' => 'Çeviri',
                'url' => IhaHealth::getUrl(panel: 'admin'),
                'action_label' => 'Çeviri Kuyruğu',
            ]);
        }

        if ($lastFailedSync !== null) {
            $attention->push([
                'tone' => 'warning',
                'title' => 'Son senkron hata verdi',
                'body' => AdminSafeText::limit($lastFailedSync->error_message ?? 'Hata detayı log kaydında görünüyor.', 140),
                'meta' => optional($lastFailedSync->completed_at)->diffForHumans() ?? 'Senkron',
                'url' => IhaHealth::getUrl(panel: 'admin'),
                'action_label' => 'Logu Aç',
            ]);
        }

        if ($criticalDrafts->isNotEmpty()) {
            $attention->push([
                'tone' => 'warning',
                'title' => 'Yayına hazır taslak var',
                'body' => $criticalDrafts->count().' yüksek skorlu taslak editör kararı bekliyor.',
                'meta' => 'Yayın kuyruğu',
                'url' => NewsArticleResource::getUrl(panel: 'admin'),
                'action_label' => 'Taslakları Aç',
            ]);
        }

        if ($isOps && ! $this->instagramService->configurationStatus()['configured']) {
            $attention->push([
                'tone' => 'neutral',
                'title' => 'Instagram otomasyonu kapalı',
                'body' => 'Token ve Business Account ID eksik olduğu için otomatik paylaşım devreye girmiyor.',
                'meta' => 'Opsiyonel entegrasyon',
                'url' => IhaHealth::getUrl(panel: 'admin'),
                'action_label' => 'Kontrol Et',
            ]);
        }

        $publicationQueue = $this->buildPublicationQueue($normalizedFilters['source']);
        $trafficPulse = $isEditor ? $this->buildTrafficPulse($window['from'], $window['to']) : null;
        $homepageStatus = $this->buildHomepageStatus();

        return [
            'header' => [
                'title' => 'Haber Masası',
                'summary' => 'Yayın akışını, İHA tazeliğini ve anasayfa durumunu tek yerden yönetin.',
                'window_label' => $window['label'],
                'source_label' => $normalizedFilters['source_label'],
                'last_refreshed_at' => now(),
                'primary_action' => [
                    'label' => 'Yeni Haber',
                    'url' => NewsArticleResource::getUrl('create', panel: 'admin'),
                ],
            ],
            'filters' => $normalizedFilters,
            'is_editor' => $isEditor,
            'is_ops' => $isOps,
            'snapshot' => [
                [
                    'label' => 'Yayındaki Haber',
                    'value' => number_format(NewsArticle::published()->count()),
                    'meta' => 'Public’te görünen toplam haber',
                    'tone' => 'neutral',
                ],
                [
                    'label' => 'Taslak Bekleyen',
                    'value' => number_format(NewsArticle::query()->where('status', 'draft')->count()),
                    'meta' => 'Editör kararı bekleyen haber',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Bugünkü Görüntüleme',
                    'value' => number_format(AnalyticsPageView::query()->whereDate('viewed_at', today())->count()),
                    'meta' => 'Bugün izlenen sayfa görüntüleme',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Son Başarılı Senkron',
                    'value' => $lastSuccessfulSync?->completed_at?->diffForHumans() ?? 'Kayıt yok',
                    'meta' => $lastSuccessfulSync ? 'İHA akışı aktif' : 'İlk başarılı kayıt bekleniyor',
                    'tone' => $lastSuccessfulSync ? 'success' : 'warning',
                ],
            ],
            'attention' => $attention
                ->take((int) ($thresholds['max_items'] ?? 5))
                ->values()
                ->all(),
            'publication_queue' => $publicationQueue,
            'health' => [
                'freshness_lag_minutes' => $freshnessLagMinutes,
                'queued_translations' => $queuedTranslations,
                'translation_backlog' => $translationBacklog,
                'summary' => $this->syncSummary($window['from'], $window['to']),
                'last_success' => $lastSuccessfulSync,
                'latest_sync' => $latestSync,
                'last_failure' => $lastFailedSync,
            ],
            'homepage_status' => $homepageStatus,
            'traffic_pulse' => $trafficPulse,
            'quick_actions' => $this->quickActions($isEditor, $isOps),
            'ops_health' => $isOps ? [
                [
                    'label' => 'Queue',
                    'value' => strtoupper((string) config('queue.default', 'sync')),
                    'meta' => 'Aktif işleyici sürücü',
                    'tone' => 'neutral',
                ],
                [
                    'label' => 'Failed Jobs',
                    'value' => number_format($failedJobsCount),
                    'meta' => 'Müdahale bekleyen kuyruk kaydı',
                    'tone' => $failedJobsCount > 0 ? 'danger' : 'success',
                ],
                [
                    'label' => 'Cache',
                    'value' => strtoupper((string) config('cache.default', 'file')),
                    'meta' => 'Aktif önbellek katmanı',
                    'tone' => 'neutral',
                ],
                [
                    'label' => 'Yedekleme',
                    'value' => array_key_exists('backup:run', app(\Illuminate\Contracts\Console\Kernel::class)->all()) ? 'Komut Hazır' : 'Hosting Katmanı',
                    'meta' => 'Yedek akışına bakış',
                    'tone' => 'neutral',
                ],
            ] : [],
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        $window = in_array(($filters['window'] ?? null), ['today', '24h', '7d'], true)
            ? $filters['window']
            : '24h';
        $source = in_array(($filters['source'] ?? null), ['all', 'iha', 'manual'], true)
            ? $filters['source']
            : 'all';

        return [
            'window' => $window,
            'window_label' => match ($window) {
                'today' => 'Bugün',
                '7d' => 'Son 7 gün',
                default => 'Son 24 saat',
            },
            'source' => $source,
            'source_label' => match ($source) {
                'iha' => 'Yalnız İHA',
                'manual' => 'Yalnız manuel',
                default => 'Tüm içerik',
            },
        ];
    }

    private function resolveWindow(string $window): array
    {
        return match ($window) {
            'today' => [
                'from' => now()->startOfDay(),
                'to' => now()->endOfDay(),
                'label' => 'Bugün',
            ],
            '7d' => [
                'from' => now()->subDays(7)->startOfDay(),
                'to' => now()->endOfDay(),
                'label' => 'Son 7 gün',
            ],
            default => [
                'from' => now()->subDay(),
                'to' => now(),
                'label' => 'Son 24 saat',
            ],
        };
    }

    private function articleScope(string $source)
    {
        return NewsArticle::query()->with('category')
            ->when($source === 'iha', fn ($query) => $query->fromIha())
            ->when($source === 'manual', fn ($query) => $query->manual());
    }

    private function criticalDrafts(string $source): Collection
    {
        return $this->articleScope($source)
            ->where('status', 'draft')
            ->orderByDesc('editorial_score')
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get()
            ->filter(fn (NewsArticle $article): bool => (int) $article->editorial_score >= (int) config('control_center.attention.critical_draft_score', 80))
            ->values();
    }

    private function buildPublicationQueue(string $source): array
    {
        $rows = collect();

        foreach ($this->criticalDrafts($source) as $article) {
            $rows->push($this->queueRow($article, 'Taslak', 'warning', 'Yüksek editoryal skor', 400));
        }

        $scheduled = $this->articleScope($source)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '>', now())
            ->orderBy('published_at')
            ->limit(3)
            ->get();

        foreach ($scheduled as $article) {
            $rows->push($this->queueRow($article, 'Zamanlı', 'neutral', optional($article->published_at)->format('d.m H:i').' için planlı', 350));
        }

        $featuredCandidates = $this->articleScope($source)
            ->published()
            ->where('is_featured', false)
            ->orderByDesc('editorial_score')
            ->orderByDesc('published_at')
            ->limit(4)
            ->get()
            ->filter(fn (NewsArticle $article): bool => (int) $article->editorial_score >= (int) config('control_center.attention.featured_candidate_score', 72));

        foreach ($featuredCandidates as $article) {
            $rows->push($this->queueRow($article, 'Vitrin adayı', 'success', 'Öne çıkarılmamış güçlü haber', 300));
        }

        $breakingCandidates = $this->articleScope($source)
            ->where('is_breaking', true)
            ->whereIn('status', ['draft', 'published'])
            ->orderByDesc('editorial_score')
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get();

        foreach ($breakingCandidates as $article) {
            $rows->push($this->queueRow($article, 'Son dakika', 'danger', 'Canlı akış önceliği', 500));
        }

        $uniqueRows = $rows
            ->sortByDesc('priority')
            ->unique('article_id')
            ->take((int) config('control_center.queue.max_rows', 8))
            ->values()
            ->map(function (array $row): array {
                unset($row['priority'], $row['article_id']);

                return $row;
            })
            ->all();

        return [
            'title' => 'Yayın Kuyruğu',
            'summary' => 'Taslak, zamanlı yayın ve vitrin adaylarını tek listede toplayın.',
            'rows' => $uniqueRows,
        ];
    }

    private function queueRow(NewsArticle $article, string $bucket, string $tone, string $meta, int $priority): array
    {
        return [
            'article_id' => $article->id,
            'priority' => $priority + (int) ($article->editorial_score ?? 0),
            'bucket' => $bucket,
            'tone' => $tone,
            'title' => $article->getTranslation('title', 'tr'),
            'category' => $article->category?->getTranslation('name', 'tr') ?? 'Kategorisiz',
            'score' => (int) ($article->editorial_score ?? 0),
            'status' => (string) $article->status,
            'meta' => $meta,
            'url' => NewsArticleResource::getUrl('edit', ['record' => $article], panel: 'admin'),
        ];
    }

    private function syncSummary($from, $to): array
    {
        $rows = IhaSyncLog::query()
            ->where('started_at', '>=', $from)
            ->where('started_at', '<=', $to)
            ->get();

        return [
            'created' => (int) $rows->sum('articles_created'),
            'updated' => (int) $rows->sum('articles_updated'),
            'skipped' => (int) $rows->sum('articles_skipped'),
            'failed_runs' => (int) $rows->where('status', 'failed')->count(),
        ];
    }

    private function buildHomepageStatus(): array
    {
        $draftRevision = $this->layoutConfigService->getDraftRevision();
        $publishedRevision = $this->layoutConfigService->getPublishedRevision();
        $draftState = $draftRevision->payload ?? [];
        $publishedState = $publishedRevision?->payload ?? [];
        $draftHash = md5(json_encode($draftState));
        $publishedHash = md5(json_encode($publishedState));
        $hasPendingChanges = $publishedRevision === null || $draftHash !== $publishedHash;
        $activeModules = collect(data_get($draftState, 'modules', []))
            ->where('is_active', true)
            ->count();
        $latestArchived = LayoutRevision::query()
            ->where('area', LayoutConfigService::AREA_HOME)
            ->where('status', LayoutRevision::STATUS_ARCHIVED)
            ->latest('updated_at')
            ->first();

        return [
            'state' => $hasPendingChanges ? 'Taslak farklı' : 'Canlı ile senkron',
            'tone' => $hasPendingChanges ? 'warning' : 'success',
            'published_at' => $publishedRevision?->published_at,
            'draft_updated_at' => $draftRevision?->updated_at,
            'active_modules' => $activeModules,
            'archived_revision_at' => $latestArchived?->updated_at,
            'url' => LayoutStudio::getUrl(panel: 'admin'),
        ];
    }

    private function buildTrafficPulse($from, $to): array
    {
        $topArticles = NewsArticle::query()
            ->published()
            ->with('category')
            ->withCount(['pageViews' => fn ($query) => $query->whereBetween('viewed_at', [$from, $to])])
            ->orderByDesc('page_views_count')
            ->limit((int) config('control_center.traffic.top_limit', 5))
            ->get()
            ->filter(fn (NewsArticle $article): bool => (int) $article->page_views_count > 0)
            ->values();

        if ($topArticles->isEmpty()) {
            $topArticles = NewsArticle::query()
                ->published()
                ->with('category')
                ->orderByDesc('view_count')
                ->limit((int) config('control_center.traffic.top_limit', 5))
                ->get();
        }

        $risingStories = NewsArticle::query()
            ->published()
            ->withCount(['pageViews' => fn ($query) => $query->where('viewed_at', '>=', now()->subHours(12))])
            ->orderByDesc('page_views_count')
            ->orderByDesc('editorial_score')
            ->limit((int) config('control_center.traffic.rising_limit', 4))
            ->get()
            ->filter(fn (NewsArticle $article): bool => (int) $article->page_views_count > 0)
            ->values();

        return [
            'title' => 'Trafik Nabzı',
            'summary' => 'İlgi toplayan içeriği ve hızla yükselen haberleri ayırt edin.',
            'top_articles' => $topArticles->map(function (NewsArticle $article): array {
                return [
                    'title' => $article->getTranslation('title', 'tr'),
                    'views' => (int) ($article->page_views_count ?? $article->view_count ?? 0),
                    'category' => $article->category?->getTranslation('name', 'tr') ?? 'Kategorisiz',
                    'url' => NewsArticleResource::getUrl('edit', ['record' => $article], panel: 'admin'),
                ];
            })->all(),
            'rising_articles' => $risingStories->map(function (NewsArticle $article): array {
                return [
                    'title' => $article->getTranslation('title', 'tr'),
                    'views' => (int) ($article->page_views_count ?? 0),
                    'score' => (int) ($article->editorial_score ?? 0),
                    'url' => NewsArticleResource::getUrl('edit', ['record' => $article], panel: 'admin'),
                ];
            })->all(),
            'analytics_url' => Analytics::getUrl(panel: 'admin'),
        ];
    }

    private function quickActions(bool $isEditor, bool $isOps): array
    {
        $actions = [
            [
                'label' => 'Yeni haber',
                'description' => 'Manuel haber akışına hemen başlayın.',
                'url' => NewsArticleResource::getUrl('create', panel: 'admin'),
                'tone' => 'warning',
            ],
            [
                'label' => 'Haber havuzu',
                'description' => 'Taslakları, yayındakileri ve arşivi birlikte yönetin.',
                'url' => NewsArticleResource::getUrl(panel: 'admin'),
                'tone' => 'neutral',
            ],
        ];

        if ($isEditor) {
            $actions[] = [
                'label' => 'Yerleşim Stüdyosu',
                'description' => 'Anasayfa vitrin akışını ve blok sırasını düzenleyin.',
                'url' => LayoutStudio::getUrl(panel: 'admin'),
                'tone' => 'success',
            ];
            $actions[] = [
                'label' => 'Performans',
                'description' => 'Trafik ve ilgi sinyallerini derin inceleyin.',
                'url' => Analytics::getUrl(panel: 'admin'),
                'tone' => 'neutral',
            ];
        }

        if ($isOps) {
            $actions[] = [
                'label' => 'İHA Sağlığı',
                'description' => 'Senkron, çeviri ve hata akışlarını kontrol edin.',
                'url' => IhaHealth::getUrl(panel: 'admin'),
                'tone' => 'danger',
            ];
            $actions[] = [
                'label' => 'Operasyon',
                'description' => 'Cache ve yedek akışlarını gözden geçirin.',
                'url' => CacheManagement::getUrl(panel: 'admin'),
                'secondary_url' => BackupManager::getUrl(panel: 'admin'),
                'secondary_label' => 'Yedekler',
                'tone' => 'neutral',
            ];
        }

        return $actions;
    }

    private function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private function countQueuedTranslationJobs(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')
            ->where('payload', 'like', '%TranslateArticleJob%')
            ->count();
    }

    private function countTranslationBacklog(): int
    {
        $count = 0;

        NewsArticle::query()
            ->fromIha()
            ->select(['id', 'title', 'summary', 'content', 'meta_title', 'meta_description'])
            ->orderBy('id')
            ->chunkById(100, function ($articles) use (&$count): void {
                foreach ($articles as $article) {
                    foreach (['title', 'summary', 'content', 'meta_title', 'meta_description'] as $field) {
                        $turkishValue = trim((string) ($article->getTranslation($field, 'tr', false) ?? ''));

                        if ($turkishValue === '') {
                            continue;
                        }

                        foreach (['en', 'ku'] as $locale) {
                            $translatedValue = trim((string) ($article->getTranslation($field, $locale, false) ?? ''));

                            if ($translatedValue === '') {
                                $count++;

                                continue 3;
                            }
                        }
                    }
                }
            });

        return $count;
    }
}
