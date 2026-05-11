<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\NewsArticleResource;
use App\Models\NewsArticle;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestNewsWidget extends BaseWidget
{
    protected static ?string $heading = 'Son Eklenen Haberler';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(NewsArticle::with('category')->latest()->limit(5))
            ->columns([
                TextColumn::make('title')
                    ->label('Başlık')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('title', 'tr'))
                    ->limit(60)
                    ->url(fn ($record) => NewsArticleResource::getUrl('edit', ['record' => $record])),

                BadgeColumn::make('category.name')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($record) => $record->category?->getTranslation('name', 'tr') ?? '-')
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->since(),
            ]);
    }
}
