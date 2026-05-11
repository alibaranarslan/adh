<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\NewsArticleResource;
use App\Models\NewsArticle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MostViewedWidget extends BaseWidget
{
    protected static ?string $heading = 'Son 7 Günde En Çok Okunan';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                NewsArticle::withCount(['pageViews' => fn ($q) => $q->where('viewed_at', '>=', now()->subDays(7))])
                    ->orderByDesc('page_views_count')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Başlık')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('title', 'tr'))
                    ->limit(60)
                    ->url(fn ($record) => NewsArticleResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('page_views_count')
                    ->label('Görüntülenme')
                    ->numeric()
                    ->sortable(),
            ]);
    }
}
