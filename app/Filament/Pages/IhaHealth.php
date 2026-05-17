<?php

namespace App\Filament\Pages;

use App\Filament\Resources\IhaSyncLogResource;
use App\Models\IhaSyncLog;
use App\Models\Setting;
use App\Services\IhaSyncTriggerService;
use App\Services\IhaTranslationRequeueService;
use App\Support\AdminPrivileges;
use App\Support\AdminSafeText;
use App\Support\TranslationSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class IhaHealth extends Page
{
    private const EFFECTIVE_SYNC_INTERVAL_MINUTES = 15;

    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationGroup = 'Operasyon';
    protected static ?string $navigationLabel = 'İHA Sağlığı';
    protected static ?string $title = 'İHA Sağlık Merkezi';
    protected static ?int $navigationSort = 31;
    protected static string $view = 'filament.pages.iha-health';

    public static function canAccess(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manual_sync')
                ->label('Senkronu Başlat')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (): void {
                    $result = app(IhaSyncTriggerService::class)->triggerQueued();
                    $this->flushHealthCache();

                    $notification = Notification::make()
                        ->title($result['title'])
                        ->body($result['body']);

                    match ($result['status']) {
                        'success' => $notification->success(),
                        'partial', 'skipped' => $notification->warning(),
                        'failed' => $notification->danger(),
                        default => $notification->info(),
                    };

                    $notification->send();
                }),
            Action::make('start_translation_flow')
                ->label('Çeviri Sürecini Başlat')
                ->icon('heroicon-o-language')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    if (! TranslationSettings::ready()) {
                        Notification::make()
                            ->danger()
                            ->title('Google Translation API key eksik')
                            ->body('Önce Entegrasyon Ayarları ekranından Google Translation API key kaydedilmelidir. Key olmadan çeviri kuyruğu işlenmez.')
                            ->send();

                        return;
                    }

                    $result = app(IhaTranslationRequeueService::class)->requeueMissingTranslations();
                    $queued = $result['queued'];
                    $skippedDuplicates = $result['skipped_duplicates'];

                    $this->flushHealthCache();

                    Notification::make()
                        ->title($queued > 0 ? 'Çeviri süreci başlatıldı' : 'Çeviri kuyruğu güncel')
                        ->body($queued > 0
                            ? "{$queued} İHA haberi için çeviri işi kuyruğa gönderildi. {$skippedDuplicates} tekrar kayıt atlandı. Sunucu queue worker çalışıyorsa işlem arka planda tamamlanır."
                            : 'Şu anda yeniden kuyruğa alınması gereken bir İHA haberi görünmüyor.')
                        ->color($queued > 0 ? 'success' : 'gray')
                        ->send();
                }),
            Action::make('sync_logs')
                ->label('Senkron Kayıtları')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(IhaSyncLogResource::getUrl())
                ->color('gray'),
            Action::make('integration_settings')
                ->label('Entegrasyon Ayarları')
                ->icon('heroicon-o-adjustments-horizontal')
                ->url(IntegrationSettings::getUrl(panel: 'admin'))
                ->color('gray'),
        ];
    }

    public function getViewData(): array
    {
        $lastSuccessfulSync = IhaSyncLog::query()
            ->where('status', 'success')
            ->latest('completed_at')
            ->first();

        $latestSync = IhaSyncLog::query()->latest('started_at')->first();

        $lastFailedSync = IhaSyncLog::query()
            ->where('status', 'failed')
            ->latest('completed_at')
            ->first();

        $freshnessLagMinutes = $lastSuccessfulSync?->completed_at?->diffInMinutes(now());
        $translationBacklog = Cache::remember(
            'iha.health.translation_backlog',
            now()->addMinutes(5),
            fn (): int => $this->countTranslationBacklog()
        );
        $queuedTranslationJobs = $this->countQueuedTranslationJobs();

        $rollingWindow = IhaSyncLog::query()
            ->where('started_at', '>=', now()->subDay())
            ->get();

        return [
            'stats' => [
                'effective_interval' => self::EFFECTIVE_SYNC_INTERVAL_MINUTES . ' dakika',
                'schedule_note' => 'Operasyonel kural sabittir. İHA senkronu cron üzerinden her 15 dakikada bir çalışır.',
                'last_successful_sync' => $lastSuccessfulSync,
                'latest_sync' => $latestSync,
                'freshness_lag_minutes' => $freshnessLagMinutes,
                'freshness_state' => match (true) {
                    $freshnessLagMinutes === null => 'unknown',
                    $freshnessLagMinutes <= self::EFFECTIVE_SYNC_INTERVAL_MINUTES * 2 => 'healthy',
                    $freshnessLagMinutes <= self::EFFECTIVE_SYNC_INTERVAL_MINUTES * 4 => 'warning',
                    default => 'critical',
                },
                'summary' => [
                    'fetched' => (int) $rollingWindow->sum('articles_fetched'),
                    'created' => (int) $rollingWindow->sum('articles_created'),
                    'updated' => (int) $rollingWindow->sum('articles_updated'),
                    'skipped' => (int) $rollingWindow->sum('articles_skipped'),
                    'failed' => (int) $rollingWindow->where('status', 'failed')->count(),
                    'images' => (int) $rollingWindow->sum('images_downloaded'),
                ],
                'translation_backlog' => $translationBacklog,
                'queued_translation_jobs' => $queuedTranslationJobs,
                'iha_credentials_ready' => $this->ihaCredentialsReady(),
                'translation_credentials_ready' => TranslationSettings::ready(),
                'last_error' => AdminSafeText::redact($lastFailedSync?->error_message),
                'last_error_at' => $lastFailedSync?->completed_at,
                'retry_note' => $this->buildRetryNote(
                    translationBacklog: $translationBacklog,
                    queuedTranslationJobs: $queuedTranslationJobs,
                    lastFailedSync: $lastFailedSync,
                ),
            ],
            'recentLogs' => IhaSyncLog::query()
                ->latest('started_at')
                ->limit(6)
                ->get(),
        ];
    }

    private function countTranslationBacklog(): int
    {
        return app(IhaTranslationRequeueService::class)->countBacklog();
    }

    private function countQueuedTranslationJobs(): int
    {
        return app(IhaTranslationRequeueService::class)->countQueuedJobs();
    }

    private function buildRetryNote(int $translationBacklog, int $queuedTranslationJobs, ?IhaSyncLog $lastFailedSync): string
    {
        if (! TranslationSettings::ready()) {
            return 'Google Translation API key eksik. Key Entegrasyon Ayarları ekranından kaydedildiğinde eksik çeviriler otomatik kuyruğa alınır.';
        }

        if ($translationBacklog > 0) {
            return "Eksik çeviri birikimi var ({$translationBacklog}). \"Çeviri Sürecini Başlat\" aksiyonu panelden kullanılabilir.";
        }

        if ($queuedTranslationJobs > 0) {
            return "Çeviri kuyruğunda bekleyen {$queuedTranslationJobs} iş var. Tekrar kuyruğa alma yerine önce queue worker akışı kontrol edilmelidir.";
        }

        if ($lastFailedSync !== null) {
            return 'Son başarısız senkron kaydı görünüyor. Hata özeti okunup ardından manuel senkron ile kontrollü yeniden deneme yapılabilir.';
        }

        return 'Şu anda açık bir yeniden deneme ihtiyacı görünmüyor.';
    }

    private function ihaCredentialsReady(): bool
    {
        return filled($this->integrationSettingOrConfig('iha_user_code', 'services.iha.user_code'))
            && filled($this->integrationSettingOrConfig('iha_username', 'services.iha.username'))
            && filled($this->integrationSettingOrConfig('iha_password', 'services.iha.password'));
    }

    private function integrationSettingOrConfig(string $settingKey, string $configKey): string
    {
        $value = Setting::get('integration', $settingKey);

        return (string) (filled($value) ? $value : config($configKey, ''));
    }

    private function flushHealthCache(): void
    {
        Cache::forget('iha.health.translation_backlog');
    }
}
