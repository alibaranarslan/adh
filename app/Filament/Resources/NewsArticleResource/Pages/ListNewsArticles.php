<?php

namespace App\Filament\Resources\NewsArticleResource\Pages;

use App\Filament\Resources\NewsArticleResource;
use App\Models\NewsArticle;
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
                ->label('Editoryal Puanı Yeniden Hesapla')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => AdminPrivileges::canPublishConfiguration(auth()->user()))
                ->requiresConfirmation()
                ->modalHeading('Editoryal puanları yeniden hesapla')
                ->modalDescription('Yayınlanan haberlerin editoryal puanları güncel içerik ve vitrin sinyallerine göre yeniden hesaplanır.')
                ->modalSubmitActionLabel('Yeniden Hesapla')
                ->action(function (): void {
                    Artisan::call('editorial:recalculate');

                    Notification::make()
                        ->success()
                        ->title('Editoryal puanlar güncellendi')
                        ->body(trim(Artisan::output()) ?: 'Yayınlanan haberler yeniden hesaplandı.')
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tümü')
                ->badge(fn (): int => $this->countArticles())
                ->badgeColor('gray'),
            'published' => Tab::make('Yayında')
                ->badge(fn (): int => $this->countArticles(fn (Builder $query) => $query->where('status', 'published')))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')),
            'draft' => Tab::make('Taslak')
                ->badge(fn (): int => $this->countArticles(fn (Builder $query) => $query->where('status', 'draft')))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
            'iha' => Tab::make('İHA')
                ->badge(fn (): int => $this->countArticles(fn (Builder $query) => $query->where('source', 'iha')))
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('source', 'iha')),
            'manual' => Tab::make('Manuel')
                ->badge(fn (): int => $this->countArticles(fn (Builder $query) => $query->where('source', 'manuel')))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('source', 'manuel')),
            'breaking' => Tab::make('Son Dakika')
                ->badge(fn (): int => $this->countArticles(fn (Builder $query) => $query->where('is_breaking', true)))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_breaking', true)),
            'featured' => Tab::make('Manşet')
                ->badge(fn (): int => $this->countArticles(fn (Builder $query) => $query->where('is_featured', true)))
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_featured', true)),
            'archived' => Tab::make('Arşiv')
                ->badge(fn (): int => $this->countArticles(fn (Builder $query) => $query->where('status', 'archived')))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'archived')),
        ];
    }

    private function countArticles(?callable $scope = null): int
    {
        $query = NewsArticle::query();

        if ($scope !== null) {
            $scope($query);
        }

        return $query->count();
    }
}
