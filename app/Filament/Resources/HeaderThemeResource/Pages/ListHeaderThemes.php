<?php

namespace App\Filament\Resources\HeaderThemeResource\Pages;

use App\Filament\Resources\HeaderThemeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHeaderThemes extends ListRecords
{
    protected static string $resource = HeaderThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Yeni Milli Gün Teması'),
        ];
    }
}
