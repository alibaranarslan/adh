<?php

namespace App\Filament\Widgets;

use App\Models\IhaSyncLog;
use App\Support\AdminSafeText;
use Filament\Widgets\Widget;

class SystemAlertsWidget extends Widget
{
    private const EXPECTED_SYNC_INTERVAL_MINUTES = 15;
    private const STALE_SUCCESS_MINUTES = 60;
    private const STALE_RUNNING_MINUTES = 120;

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
                'title' => 'IHA sync kaniti yok',
                'message' => 'Henuz IHA sync logu olusmadi. Cron, queue worker ve ilk evidence runbook kontrol edilmeli.',
            ]];
        }

        $alerts = [];

        if ($lastSync->status === 'running') {
            $runningAgeMinutes = $lastSync->started_at?->diffInMinutes(now()) ?? 0;

            $alerts[] = [
                'state' => $runningAgeMinutes > self::STALE_RUNNING_MINUTES ? 'running_stale' : 'running_young',
                'tone' => $runningAgeMinutes > self::STALE_RUNNING_MINUTES ? 'danger' : 'info',
                'title' => $runningAgeMinutes > self::STALE_RUNNING_MINUTES ? 'IHA sync running kaydi bayat' : 'IHA sync calisiyor',
                'message' => $runningAgeMinutes > self::STALE_RUNNING_MINUTES
                    ? "Son running kaydi {$runningAgeMinutes} dakika once baslamis. Queue worker veya yarim kalmis job kontrol edilmeli."
                    : "Son running kaydi {$runningAgeMinutes} dakika once basladi. Worker tamamlanma logu bekleniyor.",
            ];
        }

        if ($lastSync->status === 'failed') {
            $alerts[] = [
                'state' => 'last_failed',
                'tone' => 'danger',
                'title' => 'Son IHA sync basarisiz',
                'message' => $lastSync->error_message
                    ? 'Hata: '.AdminSafeText::redact($lastSync->error_message)
                    : 'Son sync failed durumunda. IHA health ve log detaylari kontrol edilmeli.',
            ];
        }

        if (! $lastSuccessfulSync) {
            $alerts[] = [
                'state' => 'no_success',
                'tone' => 'warning',
                'title' => 'Basarili IHA sync yok',
                'message' => 'Log var ancak henuz success kaydi yok. Worker, credential ve feed yaniti kontrol edilmeli.',
            ];

            return $alerts;
        }

        $successLagMinutes = $lastSuccessfulSync->completed_at?->diffInMinutes(now());

        if ($successLagMinutes !== null && $successLagMinutes > self::STALE_SUCCESS_MINUTES) {
            $alerts[] = [
                'state' => 'last_success_lag',
                'tone' => 'warning',
                'title' => 'Son basarili IHA sync gecikmis',
                'message' => "Son basarili sync {$successLagMinutes} dakika once tamamlandi. Beklenen aralik ".self::EXPECTED_SYNC_INTERVAL_MINUTES.' dakikadir.',
            ];
        }

        return $alerts;
    }
}
