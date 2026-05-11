<?php

namespace App\Support\ControlCenter;

use App\Filament\Pages\IhaHealth;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdhControlCenterPresenter
{
    public function present(array $data): array
    {
        $data = $this->cleanRecursive($data);

        $freshnessLag = $data['health']['freshness_lag_minutes'] ?? null;
        $translationBacklog = (int) ($data['health']['translation_backlog'] ?? 0);
        $queuedTranslations = (int) ($data['health']['queued_translations'] ?? 0);
        $publishedTotal = $this->extractInteger(Arr::get($data, 'snapshot.0.value'));
        $draftTotal = $this->extractInteger(Arr::get($data, 'snapshot.1.value'));
        $todayViews = $this->extractInteger(Arr::get($data, 'snapshot.2.value'));
        $summary = (array) ($data['health']['summary'] ?? []);
        $queueRows = collect($data['publication_queue']['rows'] ?? [])->take(6)->values();
        $attentionItems = collect($data['attention'] ?? [])->take(4)->values();
        $topArticles = collect(Arr::get($data, 'traffic_pulse.top_articles', []))->take(4)->values();
        $risingArticles = collect(Arr::get($data, 'traffic_pulse.rising_articles', []))->take(3)->values();
        $topArticleMax = max(1, (int) $topArticles->max('views'));
        $risingArticleMax = max(1, (int) $risingArticles->max('views'));
        $activeModules = (int) ($data['homepage_status']['active_modules'] ?? 0);
        $failedRuns = (int) ($summary['failed_runs'] ?? 0);
        $ihaHealthUrl = $this->findQuickActionUrl($data, ['İHA', 'IHA']) ?? IhaHealth::getUrl(panel: 'admin');

        return [
            'hero' => [
                'title' => 'Haber Masası',
                'summary' => 'Yayın kararlarını, İHA akışını ve vitrin farkını tek bir editoryal çalışma alanında yönetin.',
                'window_label' => Arr::get($data, 'filters.window_label', 'Son 24 saat'),
                'source_label' => Arr::get($data, 'filters.source_label', 'Tüm içerik'),
                'last_refreshed_at' => $data['header']['last_refreshed_at'] ?? now(),
                'primary_action' => [
                    'label' => 'Yeni Haber',
                    'url' => Arr::get($data, 'header.primary_action.url', '#'),
                ],
                'secondary_action' => [
                    'label' => 'İHA Sağlığı',
                    'url' => $ihaHealthUrl,
                ],
                'guide_label' => 'Yönetim Panelini Tanı',
            ],
            'signals' => [
                [
                    'label' => 'İHA tazeliği',
                    'value' => $freshnessLag !== null ? $freshnessLag.' dk' : 'Kayıt yok',
                    'meta' => $freshnessLag !== null ? 'Son başarılı akış farkı' : 'Başarılı senkron bekleniyor',
                    'tone' => $freshnessLag === null ? 'warning' : ($freshnessLag > 45 ? 'danger' : 'success'),
                    'bars' => $this->miniBars([$freshnessLag === null ? 12 : max(12, 100 - min(100, $freshnessLag * 2)), 42, 60, 80]),
                ],
                [
                    'label' => 'Bekleyen çeviri',
                    'value' => number_format($translationBacklog),
                    'meta' => $queuedTranslations > 0 ? number_format($queuedTranslations).' iş kuyrukta' : 'Kuyruk dengeli',
                    'tone' => $translationBacklog > 0 ? 'warning' : 'success',
                    'bars' => $this->miniBars([$translationBacklog * 18, $queuedTranslations * 20, 44, 22]),
                ],
                [
                    'label' => 'Bugünkü görüntüleme',
                    'value' => number_format($todayViews),
                    'meta' => $topArticles->isNotEmpty() ? 'En çok ilgi gören içerik hazır' : 'Trafik bugün sakin',
                    'tone' => $todayViews > 0 ? 'neutral' : 'warning',
                    'bars' => $this->miniBars([$todayViews / 40, $todayViews / 28, $todayViews / 20, $todayViews / 18]),
                ],
                [
                    'label' => 'Yayında / taslak',
                    'value' => number_format($draftTotal).' taslak',
                    'meta' => number_format($publishedTotal).' haber yayında',
                    'tone' => $draftTotal > 0 ? 'warning' : 'neutral',
                    'bars' => $this->miniBars([$publishedTotal / 12, $draftTotal * 16, $activeModules * 16, 36]),
                ],
            ],
            'attention' => [
                'title' => 'Şimdi Dikkat Gerekenler',
                'summary' => 'Akışı tıkayan sinyaller ve hemen müdahale edilmesi gereken kayıtlar.',
                'items' => $attentionItems->map(fn (array $item): array => [
                    'title' => $item['title'] ?? 'Operasyon sinyali',
                    'meta' => $item['meta'] ?? 'Durum',
                    'body' => $item['body'] ?? 'Detay kaydı bu alanda görüntülenir.',
                    'action_label' => $item['action_label'] ?? 'Aç',
                    'url' => $item['url'] ?? '#',
                    'tone' => $item['tone'] ?? 'neutral',
                ])->all(),
            ],
            'primary_queue' => [
                'title' => 'Yayın Kuyruğu',
                'summary' => 'Bugün karar verilecek taslak, zamanlı yayın ve vitrin adayları.',
                'rows' => $queueRows->map(fn (array $row): array => [
                    'bucket' => $row['bucket'] ?? 'Kayıt',
                    'title' => $row['title'] ?? 'Başlıksız içerik',
                    'category' => $row['category'] ?? 'Kategorisiz',
                    'score' => $row['score'] ?? 0,
                    'status' => $row['status'] ?? 'draft',
                    'meta' => $row['meta'] ?? 'Editoryal karar bekleniyor',
                    'tone' => $row['tone'] ?? 'neutral',
                    'url' => $row['url'] ?? '#',
                ])->all(),
            ],
            'iha_flow' => [
                'title' => 'İHA ve Çeviri Akışı',
                'summary' => 'Senkronun tazeliğini, son özetini ve çeviri baskısını birlikte okuyun.',
                'cards' => [
                    [
                        'label' => 'Son başarılı senkron',
                        'value' => $this->formatRelative($data['health']['last_success']['completed_at'] ?? null),
                        'meta' => $freshnessLag !== null ? $freshnessLag.' dakikalık fark' : 'Kayıt bekleniyor',
                        'tone' => $freshnessLag === null ? 'warning' : ($freshnessLag > 45 ? 'danger' : 'success'),
                    ],
                    [
                        'label' => 'Senkron özeti',
                        'value' => '+'.$summary['created'].' / ~'.$summary['updated'].' / ='.$summary['skipped'],
                        'meta' => $failedRuns > 0 ? $failedRuns.' başarısız tur var' : 'Başarısız tur görünmüyor',
                        'tone' => $failedRuns > 0 ? 'warning' : 'neutral',
                    ],
                    [
                        'label' => 'Çeviri baskısı',
                        'value' => number_format($translationBacklog),
                        'meta' => $queuedTranslations > 0 ? number_format($queuedTranslations).' iş kuyrukta' : 'Kuyruk dengeli',
                        'tone' => $translationBacklog > 0 ? 'warning' : 'success',
                    ],
                ],
                'chart' => [
                    'title' => 'Son akış özeti',
                    'bars' => [
                        ['label' => 'Oluşturulan', 'value' => (int) ($summary['created'] ?? 0), 'tone' => 'success'],
                        ['label' => 'Güncellenen', 'value' => (int) ($summary['updated'] ?? 0), 'tone' => 'neutral'],
                        ['label' => 'Atlanan', 'value' => (int) ($summary['skipped'] ?? 0), 'tone' => 'warning'],
                    ],
                    'url' => $ihaHealthUrl,
                ],
            ],
            'homepage' => [
                'title' => 'Anasayfa ve Vitrin Durumu',
                'summary' => 'Canlı ile taslak arasındaki farkı ve son yayın izini kısa okuyun.',
                'state' => $data['homepage_status']['state'] ?? 'Kayıt yok',
                'tone' => $data['homepage_status']['tone'] ?? 'neutral',
                'draft_updated_at' => $this->formatDateTime($data['homepage_status']['draft_updated_at'] ?? null),
                'published_at' => $this->formatDateTime($data['homepage_status']['published_at'] ?? null),
                'rollback_at' => $this->formatDateTime($data['homepage_status']['archived_revision_at'] ?? null),
                'active_modules' => $activeModules,
                'url' => $data['homepage_status']['url'] ?? '#',
            ],
            'traffic' => [
                'title' => 'Trafik Nabzı',
                'summary' => 'Şu an ilgi çeken içerikleri ve yükselen haberleri ayrı görün.',
                'top_articles' => $topArticles->map(fn (array $article): array => [
                    'title' => $article['title'] ?? 'Başlıksız içerik',
                    'category' => $article['category'] ?? 'Kategorisiz',
                    'views' => (int) ($article['views'] ?? 0),
                    'percentage' => max(12, (int) round((((int) ($article['views'] ?? 0)) / $topArticleMax) * 100)),
                    'url' => $article['url'] ?? '#',
                ])->all(),
                'rising_articles' => $risingArticles->map(fn (array $article): array => [
                    'title' => $article['title'] ?? 'Başlıksız içerik',
                    'score' => (int) ($article['score'] ?? 0),
                    'views' => (int) ($article['views'] ?? 0),
                    'percentage' => max(12, (int) round((((int) ($article['views'] ?? 0)) / $risingArticleMax) * 100)),
                    'url' => $article['url'] ?? '#',
                ])->all(),
                'url' => $data['traffic_pulse']['analytics_url'] ?? '#',
            ],
            'quick_actions' => collect($data['quick_actions'] ?? [])->take(4)->map(fn (array $action): array => [
                'label' => $action['label'] ?? 'Aksiyon',
                'description' => $action['description'] ?? 'Detay sayfasını açın.',
                'url' => $action['url'] ?? '#',
                'tone' => $action['tone'] ?? 'neutral',
            ])->all(),
            'ops_health' => [
                'title' => 'Operasyon Sağlığı',
                'summary' => 'Yalnız yetkili kullanıcılar için kuyruk, cache ve sistem katmanı sinyalleri.',
                'cards' => collect($data['ops_health'] ?? [])->map(fn (array $card): array => [
                    'label' => $card['label'] ?? 'Sistem',
                    'value' => $card['value'] ?? '-',
                    'meta' => $card['meta'] ?? 'İzleme bilgisi',
                    'tone' => $card['tone'] ?? 'neutral',
                ])->all(),
            ],
            'is_ops' => (bool) ($data['is_ops'] ?? false),
        ];
    }

    private function extractInteger(mixed $value): int
    {
        return (int) preg_replace('/[^0-9]/', '', (string) $value);
    }

    private function formatRelative(mixed $date): string
    {
        if (! $date instanceof Carbon) {
            return 'Kayıt yok';
        }

        return $date->diffForHumans();
    }

    private function formatDateTime(mixed $date): string
    {
        if (! $date instanceof Carbon) {
            return 'Kayıt yok';
        }

        return $date->format('d.m.Y H:i');
    }

    private function miniBars(array $values): array
    {
        return collect($values)
            ->map(fn (mixed $value): int => max(16, min(100, (int) round((float) $value))))
            ->values()
            ->all();
    }

    private function findQuickActionUrl(array $data, array $needles): ?string
    {
        foreach (($data['quick_actions'] ?? []) as $action) {
            $haystack = mb_strtolower(trim(($action['label'] ?? '').' '.($action['description'] ?? '')));

            foreach ($needles as $needle) {
                if (str_contains($haystack, mb_strtolower($needle))) {
                    return $action['url'] ?? null;
                }
            }
        }

        return null;
    }

    private function cleanRecursive(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->cleanRecursive($item);
            }

            return $value;
        }

        if ($value instanceof Collection) {
            return $value->map(fn (mixed $item) => $this->cleanRecursive($item));
        }

        if (! is_string($value)) {
            return $value;
        }

        return str_replace([
            'Ä±', 'Ä°', 'ÄŸ', 'Äž', 'Ã¼', 'Ãœ', 'Ã¶', 'Ã–', 'Ã§', 'Ã‡', 'ÅŸ', 'Åž', 'â€™', 'â€“', 'â€”', 'â€¦', 'Â ', 'Â',
        ], [
            'ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç', 'ş', 'Ş', '’', '–', '—', '…', ' ', '',
        ], $value);
    }
}
