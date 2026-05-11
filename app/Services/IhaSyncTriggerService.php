<?php

namespace App\Services;

use App\Models\IhaSyncLog;
use App\Support\AdminSafeText;
use Illuminate\Support\Facades\Artisan;

class IhaSyncTriggerService
{
    public function triggerQueued(): array
    {
        $previousLogId = IhaSyncLog::query()->max('id');

        $exitCode = Artisan::call('iha:sync');
        $output = trim(Artisan::output());

        $latestLog = IhaSyncLog::query()->latest('id')->first();
        $createdNewLog = $latestLog !== null && $latestLog->id !== $previousLogId;

        if (! $createdNewLog) {
            return [
                'status' => 'skipped',
                'title' => 'IHA senkronu zaten calisiyor',
                'body' => 'Yeni bir manuel senkron baslatilmadi. Mevcut calisan is tamamlandiktan sonra kayitlari yeniden kontrol edin.',
                'log_id' => $latestLog?->id,
                'exit_code' => $exitCode,
                'output' => $output,
            ];
        }

        return [
            'status' => $latestLog->status,
            'title' => match ($latestLog->status) {
                'running' => 'IHA senkronu kuyruga alindi',
                'success' => 'IHA senkronu tamamlandi',
                'partial' => 'IHA senkronu kismi tamamlandi',
                'failed' => 'IHA senkronu basarisiz oldu',
                default => 'IHA senkronu tetiklendi',
            },
            'body' => $this->buildBody($latestLog, $output),
            'log_id' => $latestLog->id,
            'exit_code' => $exitCode,
            'output' => $output,
        ];
    }

    private function buildBody(IhaSyncLog $log, string $output): string
    {
        $summary = sprintf(
            'Log #%d | Durum: %s | Cekilen: %d | Yeni: %d | Guncellenen: %d | Atlanan: %d',
            $log->id,
            $log->status,
            $log->articles_fetched,
            $log->articles_created,
            $log->articles_updated,
            $log->articles_skipped,
        );

        if (filled($log->error_message)) {
            return $summary . ' | Hata: ' . AdminSafeText::redact($log->error_message);
        }

        if ($output !== '') {
            return $summary . ' | Komut: ' . $output;
        }

        return $summary;
    }
}
