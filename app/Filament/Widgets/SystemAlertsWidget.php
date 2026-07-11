<?php

namespace App\Filament\Widgets;

use App\Models\IhaSyncLog;
use App\Support\AdminSafeText;
use Filament\Widgets\Widget;

class SystemAlertsWidget extends Widget
{
    private const EXPECTED_SYNC_INTERVAL_MINUTES = 10;
    private const STALE_SUCCESS_MINUTES = 30;
    private const CRITICAL_SUCCESS_MINUTES = 60;
    private const STALE_RUNNING_MINUTES = 20;

    protected static ?int $sort = 7;
    protected static string $view = 'filament.widgets.system-alerts';

    public function getViewData(): array
    {
        $lastSync = IhaSyncLog::query()->latest('started_at')->first();
        $lastSuccessfulSync = IhaSyncLog::query()
            ->where('status', 'success')
            ->latest('completed_at')
            ->first();

        return [
            'lastSync' => $lastSync,
            'lastSuccessfulSync' => $lastSuccessfulSync,
            'alerts' => $this->buildAlerts($lastSync, $lastSuccessfulSync),
        ];
    }

    private function buildAlerts(?IhaSyncLog $lastSync, ?IhaSyncLog $lastSuccessfulSync): array
    {
        if (! $lastSync) {
            return [[
                'state' => 'no_log',
                'tone' => 'warning',
                'title' => 'İHA sync kanıtı yok',
                'message' => 'Henüz İHA sync logu oluşmadı. Cron, queue worker ve ilk evidence runbook kontrol edilmeli.',
            ]];
        }

        $alerts = [];

        if ($lastSync->status === 'running') {
            $runningAgeMinutes = $lastSync->started_at?->diffInMinutes(now()) ?? 0;
            $isStale = $runningAgeMinutes > self::STALE_RUNNING_MINUTES;

            $alerts[] = [
                'state' => $isStale ? 'running_stale' : 'running_young',
                'tone' => $isStale ? 'danger' : 'info',
                'title' => $isStale ? 'İHA sync running kaydı bayat' : 'İHA sync çalışıyor',
                'message' => $isStale
                    ? "Son running kaydı {$runningAgeMinutes} dakika önce başlamış. Queue worker veya yarım kalmış job kontrol edilmeli."
                    : "Son running kaydı {$runningAgeMinutes} dakika önce başladı. Worker tamamlanma logu bekleniyor.",
            ];
        }

        if ($lastSync->status === 'failed') {
            $alerts[] = [
                'state' => 'last_failed',
                'tone' => 'danger',
                'title' => 'Son İHA sync başarısız',
                'message' => $lastSync->error_message
                    ? 'Hata: '.AdminSafeText::redact($lastSync->error_message)
                    : 'Son sync failed durumunda. İHA health ve log detayları kontrol edilmeli.',
            ];
        }

        if (! $lastSuccessfulSync) {
            $alerts[] = [
                'state' => 'no_success',
                'tone' => 'warning',
                'title' => 'Başarılı İHA sync yok',
                'message' => 'Log var ancak henüz success kaydı yok. Worker, credential ve feed yanıtı kontrol edilmeli.',
            ];

            return $alerts;
        }

        $successLagMinutes = $lastSuccessfulSync->completed_at?->diffInMinutes(now());

        if ($successLagMinutes !== null && $successLagMinutes > self::CRITICAL_SUCCESS_MINUTES) {
            $alerts[] = [
                'state' => 'last_success_critical',
                'tone' => 'danger',
                'title' => 'İHA akışı kritik derecede bayat',
                'message' => "Son başarılı sync {$successLagMinutes} dakika önce tamamlandı. 60 dakikayı aşan gecikme canlı haber akışı riski üretir.",
            ];
        } elseif ($successLagMinutes !== null && $successLagMinutes > self::STALE_SUCCESS_MINUTES) {
            $alerts[] = [
                'state' => 'last_success_lag',
                'tone' => 'warning',
                'title' => 'Son başarılı İHA sync gecikmiş',
                'message' => "Son başarılı sync {$successLagMinutes} dakika önce tamamlandı. Beklenen aralık ".self::EXPECTED_SYNC_INTERVAL_MINUTES.' dakikadır.',
            ];
        }

        return $alerts;
    }
}
