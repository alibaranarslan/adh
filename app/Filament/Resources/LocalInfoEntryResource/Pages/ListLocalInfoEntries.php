<?php

namespace App\Filament\Resources\LocalInfoEntryResource\Pages;

use App\Filament\Resources\LocalInfoEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLocalInfoEntries extends ListRecords
{
    protected static string $resource = LocalInfoEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Yeni Bilgi'),
        ];
    }
}
