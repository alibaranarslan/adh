<?php

namespace App\Filament\Pages;

use App\Support\AdminPrivileges;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class CacheManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationLabel = 'Önbellek';
    protected static ?string $title = 'Önbellek Yönetimi';
    protected static ?string $navigationGroup = 'Operasyon';
    protected static ?int $navigationSort = 30;
    protected static string $view = 'filament.pages.cache-management';

    public static function canAccess(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public function clearConfig(): void
    {
        $this->runCommand('config:clear', 'Yapılandırma önbelleği temizlendi');
    }

    public function clearView(): void
    {
        $this->runCommand('view:clear', 'Görünüm önbelleği temizlendi');
    }

    public function clearRoute(): void
    {
        $this->runCommand('route:clear', 'Rota önbelleği temizlendi');
    }

    public function clearAll(): void
    {
        $commands = ['cache:clear', 'config:clear', 'view:clear', 'route:clear'];
        $errors = [];

        foreach ($commands as $command) {
            $exitCode = Artisan::call($command);
            if ($exitCode !== 0) {
                $errors[] = $command . ': ' . trim(Artisan::output());
            }
        }

        if ($errors !== []) {
            Notification::make()
                ->danger()
                ->title('Tüm önbellek temizlenemedi')
                ->body(implode(' | ', $errors))
                ->send();

            return;
        }

        Notification::make()->success()->title('Tüm önbellek temizlendi')->send();
    }

    public function optimizeApp(): void
    {
        $this->runCommand('optimize', 'Uygulama optimize edildi');
    }

    private function runCommand(string $command, string $successTitle): void
    {
        $exitCode = Artisan::call($command);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            Notification::make()
                ->danger()
                ->title('İşlem tamamlanamadı')
                ->body($output !== '' ? $output : $command . ' komutu sıfır dışı çıkış kodu ile döndü.')
                ->send();

            return;
        }

        Notification::make()->success()->title($successTitle)->send();
    }
}
