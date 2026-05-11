<?php

namespace App\Filament\Resources\NewsArticleResource\Pages;

use App\Filament\Resources\NewsArticleResource;
use App\Support\AdminPrivileges;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditNewsArticle extends EditRecord
{
    use Translatable;

    protected static string $resource = NewsArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Sil')
                ->visible(fn (): bool => ! $this->record->isFromIha()),
            $this->getSaveFormAction()->formId('form'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->isFromIha()) {
            // IHA articles: mark fields as readonly via form disabling
        }
        return $data;
    }

    protected function beforeSave(): void
    {
        if (! $this->record->isFromIha()) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('İHA haberleri düzenlenemez')
            ->body('Kaynak disiplini gereği İHA haberleri manuel olarak değiştirilemez. Gerekirse ayrı bir manuel haber oluşturun.')
            ->send();

        $this->halt();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! AdminPrivileges::hasPermission(auth()->user(), 'publish_news_article')) {
            unset($data['status'], $data['is_breaking'], $data['is_featured'], $data['published_at']);
        }

        return $data;
    }
}
