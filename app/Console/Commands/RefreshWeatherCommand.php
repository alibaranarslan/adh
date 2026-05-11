<?php

namespace App\Console\Commands;

use App\Services\WeatherService;
use Illuminate\Console\Command;

class RefreshWeatherCommand extends Command
{
    protected $signature = 'weather:refresh';
    protected $description = 'Adıyaman hava durumu verisini API\'den yenile';

    public function handle(): int
    {
        $data = WeatherService::refresh();

        if (($data['temp'] ?? '--') !== '--') {
            $this->info("Hava durumu güncellendi: {$data['temp']}°C, {$data['description']}");
        } else {
            $this->warn('Hava durumu verisi alınamadı.');
        }

        return self::SUCCESS;
    }
}
