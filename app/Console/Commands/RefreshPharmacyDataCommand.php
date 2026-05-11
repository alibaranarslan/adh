<?php

namespace App\Console\Commands;

use App\Services\PharmacyService;
use Illuminate\Console\Command;

class RefreshPharmacyDataCommand extends Command
{
    protected $signature = 'pharmacy:refresh';
    protected $description = 'Nöbetçi eczane verilerini API\'den yenile ve önbelleği güncelle';

    public function handle(PharmacyService $pharmacyService): int
    {
        $pharmacies = $pharmacyService->refreshCache();

        $this->info('Nöbetçi eczane verileri güncellendi: ' . count($pharmacies) . ' eczane.');

        return self::SUCCESS;
    }
}
