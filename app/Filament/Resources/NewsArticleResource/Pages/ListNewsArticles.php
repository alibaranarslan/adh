<?php

namespace App\Filament\Resources\NewsArticleResource\Pages;

use App\Filament\Resources\NewsArticleResource;
use App\Support\AdminPrivileges;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class ListNewsArticles extends ListRecords
{
    protected static string $resource = NewsArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Yeni Haber'),
            Actions\Action::make('recalculateEditorialScore')
                ->label('Editoryal Puani Yeniden Hesapla')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => AdminPrivileges::canPublishConfiguration(auth()->user()))
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('editorial:recalculate');

                    Notification::make()
                        ->success()
                        ->title('Editoryal puanlar guncellendi')
                        ->body(trim(Artisan::output()) ?: 'Yayinlanan haberler yeniden hesaplandi.')
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tümü'),
            'published' => Tab::make('Yayında')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')),
            'draft' => Tab::make('Taslak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
            'iha' => Tab::make('IHA')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('source', 'iha')),
            'breaking' => Tab::make('Son Dakika')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_breaking', true)),
        ];
    }
}
