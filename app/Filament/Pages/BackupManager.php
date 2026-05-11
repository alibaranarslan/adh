<?php

namespace App\Filament\Pages;

use App\Support\AdminPrivileges;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?string $navigationLabel = 'Yedekleme';

    protected static ?string $title = 'Yedekleme';

    protected static ?string $navigationGroup = 'Operasyon';

    protected static ?int $navigationSort = 31;

    protected static string $view = 'filament.pages.backup-studio';

    public static function canAccess(): bool
    {
        return AdminPrivileges::canPublishConfiguration(auth()->user());
    }

    public function getBackupFiles(): array
    {
        $files = [];

        foreach ($this->getBackupDirectories() as $backupPath) {
            try {
                if (Storage::disk('local')->exists($backupPath)) {
                    $allFiles = Storage::disk('local')->files($backupPath);

                    foreach ($allFiles as $file) {
                        $size = Storage::disk('local')->size($file);
                        $files[] = [
                            'name' => basename($file),
                            'path' => $file,
                            'directory' => $backupPath,
                            'size' => $this->humanFileSize($size),
                            'size_bytes' => $size,
                            'last_modified' => date('d.m.Y H:i', Storage::disk('local')->lastModified($file)),
                            'last_modified_ts' => Storage::disk('local')->lastModified($file),
                        ];
                    }
                }
            } catch (\Throwable) {
            }
        }

        usort($files, fn ($a, $b) => ($b['last_modified_ts'] ?? 0) <=> ($a['last_modified_ts'] ?? 0));

        return $files;
    }

    public function createBackup(): void
    {
        if (! $this->isBackupCommandAvailable()) {
            Notification::make()
                ->warning()
                ->title('Yedekleme yapılandırılmamış')
                ->body('backup:run komutu kayıtlı olmadığı için yeni yedek başlatılamıyor.')
                ->send();

            return;
        }

        try {
            $exitCode = Artisan::call('backup:run --only-db');
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                Notification::make()
                    ->danger()
                    ->title('Yedekleme başlatılamadı')
                    ->body($output !== '' ? $output : 'Komut sıfır dışı çıkış kodu ile döndü.')
                    ->send();

                return;
            }

            Notification::make()
                ->success()
                ->title('Yedekleme başlatıldı')
                ->body('Veritabanı yedeği komutu başarıyla tetiklendi.')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Yedekleme başlatılamadı')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function getBackupStatus(): array
    {
        $files = $this->getBackupFiles();
        $latest = $files[0] ?? null;

        return [
            'command_available' => $this->isBackupCommandAvailable(),
            'directories' => $this->getBackupDirectories(),
            'active_directory' => $latest['directory'] ?? $this->getBackupDirectories()[0],
            'files' => $files,
            'total_files' => count($files),
            'latest_backup' => $latest,
        ];
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    private function getBackupDirectories(): array
    {
        $appDirectory = 'backups/' . str(config('app.name', 'laravel'))->slug('_');

        return array_values(array_unique([
            $appDirectory,
            'backups/laravel',
        ]));
    }

    private function isBackupCommandAvailable(): bool
    {
        return array_key_exists('backup:run', app(ConsoleKernel::class)->all());
    }
}