<?php

namespace App\Filament\Pages;

use App\Support\AdminPrivileges;
use Filament\Pages\Page;

class LayoutManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Legacy Layout Manager';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $title = 'Legacy Layout Manager';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'layout-manager-legacy';

    protected static string $view = 'filament.pages.layout-manager-disabled';

    public static function canAccess(): bool
    {
        // Legacy stub is informational only; keep it hidden from navigation but reachable to admin-panel users.
        return AdminPrivileges::canAccessAdminPanel(auth()->user());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
