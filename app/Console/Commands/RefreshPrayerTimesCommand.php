<?php

namespace App\Console\Commands;

use App\Services\PrayerTimesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshPrayerTimesCommand extends Command
{
    protected $signature = 'prayer:refresh';
    protected $description = 'Namaz vakitlerini API\'den yenile ve önbelleği güncelle';

    public function handle(): int
    {
        $times = PrayerTimesService::refresh();
        Cache::put('prayer_times', $times, 3600);

        if (!empty($times)) {
            $this->info('Namaz vakitleri güncellendi: ' . implode(', ', $times));
        } else {
            $this->warn('Namaz vakitleri alınamadı.');
        }

        return self::SUCCESS;
    }
}
