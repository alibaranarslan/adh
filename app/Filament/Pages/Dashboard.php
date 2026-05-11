<?php

namespace App\Filament\Pages;

use App\Services\AdhControlCenterService;
use App\Support\ControlCenter\AdhControlCenterPresenter;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Haber Masası';

    protected static ?string $title = 'Haber Masası';

    protected static string $view = 'filament.pages.dashboard-newsroom';

    public static function getNavigationLabel(): string
    {
        return 'Haber Masası';
    }

    public function getTitle(): string
    {
        return 'Haber Masası';
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Görünüm')
                ->form([
                    Select::make('window')
                        ->label('Zaman aralığı')
                        ->options([
                            'today' => 'Bugün',
                            '24h' => 'Son 24 saat',
                            '7d' => 'Son 7 gün',
                        ])
                        ->default('24h')
                        ->native(false),
                    Select::make('source')
                        ->label('İçerik kaynağı')
                        ->options([
                            'all' => 'Tüm içerik',
                            'iha' => 'Yalnız İHA',
                            'manual' => 'Yalnız manuel',
                        ])
                        ->default('all')
                        ->native(false),
                ]),
        ];
    }

    public function getViewData(): array
    {
        $snapshot = app(AdhControlCenterService::class)->snapshot($this->filters ?? [], auth()->user());

        return app(AdhControlCenterPresenter::class)->present($snapshot);
    }
}
