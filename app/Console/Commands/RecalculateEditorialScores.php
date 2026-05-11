<?php

namespace App\Console\Commands;

use App\Services\EditorialScoreService;
use Illuminate\Console\Command;

class RecalculateEditorialScores extends Command
{
    protected $signature = 'editorial:recalculate';
    protected $description = 'Tüm yayınlanan haberlerin editöryal puanlarını yeniden hesapla';

    public function handle(): int
    {
        $count = EditorialScoreService::recalculateAll();
        $this->info("$count haber puanı yeniden hesaplandı.");

        return self::SUCCESS;
    }
}
