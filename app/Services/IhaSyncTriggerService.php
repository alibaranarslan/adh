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
                'title' => 'İHA senkronu zaten çalışıyor',
                'body' => $this->buildSkippedBody($latestLog, $output),
                'log_id' => $latestLog?->id,
                'exit_code' => $exitCode,
                'output' => $output,
            ];
        }

        return [
            'status' => $latestLog->status,
            'title' => match ($latestLog->status) {
                'running' => 'İHA senkronu kuyruğa alındı',
                'success' => 'İHA senkronu tamamlandı',
                'partial' => 'İHA senkronu kısmi tamamlandı',
                'failed' => 'İHA senkronu başarısız oldu',
                default => 'İHA senkronu tetiklendi',
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
            'Log #%d | Durum: %s | Çekilen: %d | Yeni: %d | Güncellenen: %d | Atlanan: %d',
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

    private function buildSkippedBody(?IhaSyncLog $latestLog, string $output): string
    {
        $logPart = $latestLog !== null
            ? sprintf('Mevcut çalışan log #%d hâlâ running durumunda.', $latestLog->id)
            : 'Mevcut çalışan sync tespit edildi.';

        $queuePart = 'Yeni manuel senkron başlatılmadı. Queue worker çalışıyorsa mevcut iş tamamlandıktan sonra kayıtlar güncellenir.';

        if ($output !== '') {
            return $logPart . ' ' . $queuePart . ' Komut çıktısı: ' . $output;
        }

        return $logPart . ' ' . $queuePart;
    }
}
