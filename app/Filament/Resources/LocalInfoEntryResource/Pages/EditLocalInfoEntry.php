<?php

namespace App\Filament\Resources\LocalInfoEntryResource\Pages;

use App\Filament\Resources\LocalInfoEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocalInfoEntry extends EditRecord
{
    protected static string $resource = LocalInfoEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
