<?php

namespace App\Services;

use App\Support\ExternalApiHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const CACHE_KEY = 'adiyaman_weather';
    private const META_CACHE_KEY = 'adiyaman_weather_meta';
    private const CACHE_TTL = 1800;
    private const LAT = 37.76;
    private const LON = 38.28;

    public static function get(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (self::isValid($cached)) {
            return $cached;
        }

        return self::refresh();
    }

    public static function refresh(): array
    {
        $data = self::fetchFromApi();

        if (self::isValid($data)) {
            Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
            Cache::put(self::META_CACHE_KEY, [
                'source' => 'open-meteo',
                'fetched_at' => now()->toIso8601String(),
                'stale' => false,
            ], self::CACHE_TTL);

            return $data;
        }

        $previous = Cache::get(self::CACHE_KEY, $data);

        Cache::put(self::META_CACHE_KEY, [
            'source' => 'open-meteo',
            'fetched_at' => now()->toIso8601String(),
            'stale' => self::isValid($previous),
        ], self::CACHE_TTL);

        return self::isValid($previous) ? $previous : $data;
    }

    public static function meta(): array
    {
        return Cache::get(self::META_CACHE_KEY, []);
    }

    private static function fetchFromApi(): array
    {
        $fallback = [
            'temp' => '--',
            'description' => '',
            'icon' => '🌤️',
            'feels_like' => '--',
            'humidity' => '--',
            'wind' => '--',
        ];

        try {
            $response = ExternalApiHttp::json(config('services.weather.verify_ssl', true))->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => self::LAT,
                'longitude' => self::LON,
                'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m,weather_code',
                'timezone' => 'Europe/Istanbul',
            ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $current = $response->json('current');
            if (! $current) {
                return $fallback;
            }

            $code = (int) ($current['weather_code'] ?? 0);

            return [
                'temp' => round($current['temperature_2m'] ?? 0),
                'description' => self::weatherDescription($code),
                'icon' => self::weatherIcon($code),
                'feels_like' => round($current['apparent_temperature'] ?? 0),
                'humidity' => $current['relative_humidity_2m'] ?? '--',
                'wind' => round($current['wind_speed_10m'] ?? 0, 1),
            ];
        } catch (\Throwable $e) {
            Log::warning('Weather API hatası', ['message' => $e->getMessage()]);
            return $fallback;
        }
    }

    private static function isValid(mixed $data): bool
    {
        return is_array($data) && ($data['temp'] ?? '--') !== '--';
    }

    private static function weatherDescription(int $code): string
    {
        return match (true) {
            $code === 0 => 'Açık',
            in_array($code, [1, 2, 3], true) => 'Parçalı Bulutlu',
            in_array($code, [45, 48], true) => 'Sisli',
            in_array($code, [51, 53, 55], true) => 'Çisenti',
            in_array($code, [61, 63, 65], true) => 'Yağmurlu',
            in_array($code, [66, 67], true) => 'Dondurucu Yağmur',
            in_array($code, [71, 73, 75, 77], true) => 'Karlı',
            in_array($code, [80, 81, 82], true) => 'Sağanak Yağış',
            in_array($code, [85, 86], true) => 'Kar Yağışlı',
            in_array($code, [95, 96, 99], true) => 'Gök Gürültülü Fırtına',
            default => 'Değişken',
        };
    }

    private static function weatherIcon(int $code): string
    {
        return match (true) {
            $code === 0 => '☀️',
            in_array($code, [1, 2], true) => '⛅',
            $code === 3 => '☁️',
            in_array($code, [45, 48], true) => '🌫️',
            in_array($code, [51, 53, 55, 56, 57], true) => '🌦️',
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => '🌧️',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => '🌨️',
            in_array($code, [95, 96, 99], true) => '⛈️',
            default => '🌤️',
        };
    }
}