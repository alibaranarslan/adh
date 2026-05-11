<?php

namespace App\Filament\Resources\HeaderThemeResource\Pages;

use App\Filament\Resources\HeaderThemeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateHeaderTheme extends CreateRecord
{
    use Translatable;

    protected static string $resource = HeaderThemeResource::class;
}
