<?php

namespace App\Filament\Widgets;

use App\Models\NewsArticle;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class NewsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Son 30 Gün — Haber Sayısı';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d.m');
            $data[] = NewsArticle::whereDate('created_at', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Haber Sayısı',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
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
