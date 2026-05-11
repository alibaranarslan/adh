<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsPageView;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TrafficChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Son 30 Gün — Sayfa Görüntüleme';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d.m');
            $data[] = AnalyticsPageView::whereDate('viewed_at', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Görüntülenme',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
