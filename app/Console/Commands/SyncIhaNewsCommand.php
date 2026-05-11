<?php

namespace App\Console\Commands;

use App\Jobs\SyncIhaNewsJob;
use App\Models\IhaSyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class SyncIhaNewsCommand extends Command
{
    protected $signature = 'iha:sync
        {--city= : Belirli bir sehir kodu ile senkronize et}
        {--force : Calisan sync kontrolunu atla}
        {--inline : Isi kuyruksuz calistir}
        {--limit= : Islenecek en fazla haber sayisi}';

    protected $description = "IHA RSS API'den haberleri cekip veritabanina kaydet";

    public function handle(): int
    {
        $cityCode = $this->option('city') ? (int) $this->option('city') : null;
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;

        IhaSyncLog::query()
            ->where('status', 'running')
            ->where('started_at', '<', now()->subHours(2))
            ->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Zaman asimi: onceki IHA sync 2 saati asti ve yarim kalmis gorunuyor. Aktif runtime baglami, hedef DB ve son sync loglarini kontrol edin.',
            ]);

        if (! $this->option('force')) {
            $running = IhaSyncLog::query()
                ->where('status', 'running')
                ->exists();

            if ($running) {
                $this->warn('IHA sync zaten calisiyor. Atlaniyor.');

                return self::SUCCESS;
            }
        }

        $syncLog = IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);

        if ($this->option('inline')) {
            SyncIhaNewsJob::dispatchSync($cityCode, $syncLog->id, $limit);
            $syncLog->refresh();

            $this->info(sprintf(
                'IHA sync tamamlandi. Log ID: %d | Durum: %s | Cekilen: %d | Yeni: %d | Guncellenen: %d | Atlanan: %d',
                $syncLog->id,
                $syncLog->status,
                $syncLog->articles_fetched,
                $syncLog->articles_created,
                $syncLog->articles_updated,
                $syncLog->articles_skipped,
            ));

            if ($syncLog->error_message) {
                $this->warn($syncLog->error_message);
            }

            return $syncLog->status === 'failed'
                ? self::FAILURE
                : self::SUCCESS;
        }

        try {
            Bus::dispatch((new SyncIhaNewsJob($cityCode, $syncLog->id, $limit))->onQueue('default'));
        } catch (Throwable $exception) {
            $syncLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'IHA sync queue dispatch basarisiz: ' . $exception->getMessage(),
            ]);

            $this->error($syncLog->error_message);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'IHA sync kuyruga alindi. Log ID: %d | Connection: %s | Queue: default | Durum: %s',
            $syncLog->id,
            config('queue.default', 'default'),
            $syncLog->status,
        ));

        return self::SUCCESS;
    }
}
