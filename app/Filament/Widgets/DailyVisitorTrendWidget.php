<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsPageView;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DailyVisitorTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Son 30 Gün — Günlük Ziyaretçi Trendi';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $days   = collect();
        $counts = collect();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days->push($date->format('d.m'));
            $counts->push(
                AnalyticsPageView::whereDate('viewed_at', $date)->count()
            );
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Sayfa Görüntüleme',
                    'data'            => $counts->toArray(),
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $days->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
