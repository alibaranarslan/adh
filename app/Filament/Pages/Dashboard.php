<?php

namespace App\Filament\Pages;

use App\Services\AdhControlCenterService;
use App\Support\ControlCenter\AdhControlCenterPresenter;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFilters;

class Dashboard extends BaseDashboard
{
    use HasFilters;

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

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    public function getViewData(): array
    {
        $snapshot = app(AdhControlCenterService::class)->snapshot(
            (array) ($this->filters ?? request()->query('filters', [])),
            auth()->user()
        );

        return app(AdhControlCenterPresenter::class)->present($snapshot);
    }
}
