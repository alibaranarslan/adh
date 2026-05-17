<?php

namespace App\Console\Commands;

use App\Services\IhaTranslationRequeueService;
use Illuminate\Console\Command;

class RequeueIhaTranslationsCommand extends Command
{
    protected $signature = 'iha:translations:requeue {--chunk=100 : Number of articles scanned per chunk}';

    protected $description = 'Eksik EN/KU IHA haber cevirilerini database queue uzerinden yeniden kuyruga al';

    public function handle(IhaTranslationRequeueService $service): int
    {
        $result = $service->requeueMissingTranslations((int) $this->option('chunk'));

        $this->components->info('Eksik IHA ceviri taramasi tamamlandi.');
        $this->line('backlog=' . $result['backlog']);
        $this->line('queued=' . $result['queued']);
        $this->line('skipped_duplicates=' . $result['skipped_duplicates']);
        $this->line('chunk_size=' . $result['chunk_size']);

        return self::SUCCESS;
    }
}
