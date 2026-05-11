<?php

namespace App\Filament\Pages;

use App\Models\AnalyticsPageView;
use App\Models\NewsArticle;
use App\Support\AdminPrivileges;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Performans';
    protected static ?string $title = 'Performans';
    protected static ?string $navigationGroup = 'Analiz';
    protected static ?int $navigationSort = 20;
    protected static string $view = 'filament.pages.analytics-studio';

    public string $period = '7days';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public static function canAccess(): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->dateFrom = match ($period) {
            'today' => now()->format('Y-m-d'),
            '7days' => now()->subDays(7)->format('Y-m-d'),
            '30days' => now()->subDays(30)->format('Y-m-d'),
            default => $this->dateFrom,
        };
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getViewData(): array
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        $current = $this->buildSnapshot($from, $to);
        $previousRange = $this->resolvePreviousRange($from, $to);
        $previous = $this->buildSnapshot($previousRange['from'], $previousRange['to']);

        $comparison = [
            'views' => $this->calculateDelta($current['totalViews'], $previous['totalViews']),
            'visitors' => $this->calculateDelta($current['uniqueVisitors'], $previous['uniqueVisitors']),
        ];

        return array_merge($current, [
            'previousPeriod' => $previous,
            'comparison' => $comparison,
            'periodLabel' => $from->format('d.m.Y') . ' - ' . $to->format('d.m.Y'),
            'previousPeriodLabel' => $previousRange['from']->format('d.m.Y') . ' - ' . $previousRange['to']->format('d.m.Y'),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->getViewData();

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['metric', 'value']);
            fputcsv($handle, ['total_views', $data['totalViews']]);
            fputcsv($handle, ['unique_visitors', $data['uniqueVisitors']]);
            fputcsv($handle, ['views_delta', $data['comparison']['views']['difference']]);
            fputcsv($handle, ['visitors_delta', $data['comparison']['visitors']['difference']]);
            fputcsv($handle, []);

            fputcsv($handle, ['device_distribution']);
            fputcsv($handle, ['device', 'count']);
            foreach ($data['deviceDistribution'] as $device => $count) {
                fputcsv($handle, [$device ?: 'unknown', $count]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['traffic_sources']);
            fputcsv($handle, ['source', 'count']);
            foreach ($data['trafficSources'] as $source => $count) {
                fputcsv($handle, [$source, $count]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['category_distribution']);
            fputcsv($handle, ['category', 'views']);
            foreach ($data['categoryDistribution'] as $category => $count) {
                fputcsv($handle, [$category, $count]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['top_articles']);
            fputcsv($handle, ['title', 'page_views']);
            foreach ($data['topArticles'] as $article) {
                fputcsv($handle, [
                    $article->getTranslation('title', 'tr'),
                    $article->page_views_count,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['daily_views']);
            fputcsv($handle, ['date', 'views']);
            foreach ($data['dailyViews'] as $date => $count) {
                fputcsv($handle, [$date, $count]);
            }

            fclose($handle);
        }, 'adh-analytics.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildSnapshot(Carbon $from, Carbon $to): array
    {
        $totalViews = AnalyticsPageView::whereBetween('viewed_at', [$from, $to])->count();

        $uniqueVisitors = AnalyticsPageView::whereBetween('viewed_at', [$from, $to])
            ->distinct('session_id')
            ->count('session_id');

        $deviceDistribution = AnalyticsPageView::whereBetween('viewed_at', [$from, $to])
            ->selectRaw('COALESCE(device_type, "unknown") as device_type, count(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type');

        $topArticles = NewsArticle::query()
            ->with('category')
            ->whereHas('pageViews', fn ($query) => $query->whereBetween('viewed_at', [$from, $to]))
            ->withCount(['pageViews' => fn ($query) => $query->whereBetween('viewed_at', [$from, $to])])
            ->orderByDesc('page_views_count')
            ->limit(10)
            ->get();

        $dailyViews = AnalyticsPageView::whereBetween('viewed_at', [$from, $to])
            ->selectRaw('DATE(viewed_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $trafficSources = AnalyticsPageView::whereBetween('viewed_at', [$from, $to])
            ->get(['referer'])
            ->groupBy(fn (AnalyticsPageView $view): string => $this->resolveTrafficSource($view->referer))
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $categoryDistribution = $topArticles
            ->groupBy(fn (NewsArticle $article) => $article->category?->getTranslation('name', 'tr') ?: 'Diger')
            ->map(fn ($group) => $group->sum('page_views_count'))
            ->sortDesc();

        return compact(
            'totalViews',
            'uniqueVisitors',
            'deviceDistribution',
            'topArticles',
            'dailyViews',
            'trafficSources',
            'categoryDistribution'
        );
    }

    private function resolvePreviousRange(Carbon $from, Carbon $to): array
    {
        $daySpan = max($from->copy()->startOfDay()->diffInDays($to->copy()->endOfDay()) + 1, 1);
        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($daySpan - 1)->startOfDay();

        return [
            'from' => $previousFrom,
            'to' => $previousTo,
        ];
    }

    private function calculateDelta(int|float $current, int|float $previous): array
    {
        $difference = $current - $previous;
        $percentage = $previous > 0 ? round(($difference / $previous) * 100, 1) : null;

        return [
            'difference' => $difference,
            'percentage' => $percentage,
            'direction' => $difference >= 0 ? 'up' : 'down',
        ];
    }

    private function resolveTrafficSource(?string $referer): string
    {
        if (blank($referer)) {
            return 'direct';
        }

        $host = strtolower((string) parse_url($referer, PHP_URL_HOST));

        if ($host === '') {
            return 'referral';
        }

        foreach (['google.', 'bing.', 'yandex.', 'duckduckgo.'] as $needle) {
            if (str_contains($host, $needle)) {
                return 'search';
            }
        }

        foreach (['facebook.', 'instagram.', 'twitter.', 'x.com', 't.co', 'linkedin.', 'youtube.', 'tiktok.'] as $needle) {
            if (str_contains($host, $needle)) {
                return 'social';
            }
        }

        return 'referral';
    }
}
