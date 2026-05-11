<?php

namespace App\Filament\Resources\NewsArticleResource\Pages;

use App\Filament\Resources\NewsArticleResource;
use App\Support\AdminPrivileges;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateNewsArticle extends CreateRecord
{
    use Translatable;

    protected static string $resource = NewsArticleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! AdminPrivileges::hasPermission(auth()->user(), 'publish_news_article')) {
            $data['status'] = 'draft';
            $data['is_breaking'] = false;
            $data['is_featured'] = false;
            unset($data['published_at']);
        }

        return $data;
    }
}
