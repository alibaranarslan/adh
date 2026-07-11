<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsPageView;
use App\Models\IhaSyncLog;
use App\Models\NewsArticle;
use App\Support\AdminSafeText;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $todayArticles = NewsArticle::whereDate('created_at', today())->count();
        $weekArticles = NewsArticle::whereBetween('published_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', 'published')
            ->count();
        $totalArticles = NewsArticle::count();
        $todayViews = AnalyticsPageView::whereDate('viewed_at', today())->count();
        $lastSync = IhaSyncLog::query()
            ->where('status', 'success')
            ->latest('completed_at')
            ->first()
            ?? IhaSyncLog::latest('started_at')->first();

        return [
            Stat::make('Bugünkü Haberler', $todayArticles)
                ->description('Bugün eklenen haber sayısı')
                ->icon('heroicon-o-newspaper')
                ->color('primary'),

            Stat::make('Bu Hafta Yayınlanan', $weekArticles)
                ->description(now()->startOfWeek()->format('d.m') . ' - ' . now()->endOfWeek()->format('d.m'))
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('Toplam Haber', number_format($totalArticles))
                ->description('Sistemdeki toplam haber')
                ->icon('heroicon-o-archive-box')
                ->color('info'),

            Stat::make('Bugünkü Görüntülenme', number_format($todayViews))
                ->description('Bugün sayfa görüntüleme')
                ->icon('heroicon-o-eye')
                ->color('warning'),

            Stat::make(
                'İHA Son Sync',
                $lastSync
                    ? (($lastSync->status === 'success' && $lastSync->completed_at)
                        ? $lastSync->completed_at->diffForHumans()
                        : $lastSync->started_at->diffForHumans())
                    : 'Hiç'
            )
                ->description($lastSync ? ($lastSync->status.($lastSync->error_message ? ': '.AdminSafeText::limit($lastSync->error_message, 40) : '')) : 'Bilinmiyor')
                ->icon('heroicon-o-arrow-path')
                ->color($lastSync?->status === 'success' ? 'success' : 'danger'),
        ];
    }
}
