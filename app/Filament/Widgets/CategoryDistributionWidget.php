<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class CategoryDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Kategori Bazlı Haber Dağılımı';
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $categories = Category::withCount('articles')
            ->having('articles_count', '>', 0)
            ->orderByDesc('articles_count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Haber Sayısı',
                    'data' => $categories->pluck('articles_count')->toArray(),
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                        '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6b7280',
                    ],
                ],
            ],
            'labels' => $categories->map(fn ($c) => $c->getTranslation('name', 'tr'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
