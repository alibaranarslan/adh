<?php

namespace App\Filament\Resources\HeaderThemeResource\Pages;

use App\Filament\Resources\HeaderThemeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditHeaderTheme extends EditRecord
{
    use Translatable;

    protected static string $resource = HeaderThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
