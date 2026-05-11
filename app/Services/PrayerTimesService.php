<?php

namespace App\Services;

use App\Support\ExternalApiHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PrayerTimesService
{
    private const CACHE_KEY = 'adiyaman_prayer_times';
    private const LEGACY_CACHE_KEY = 'prayer_times';
    private const META_CACHE_KEY = 'adiyaman_prayer_times_meta';
    private const CACHE_TTL = 3600;

    public static function get(): array
    {
        $legacy = Cache::get(self::LEGACY_CACHE_KEY);
        if (empty(Cache::get(self::CACHE_KEY)) && ! empty($legacy)) {
            Cache::put(self::CACHE_KEY, $legacy, self::CACHE_TTL);
        }

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
            Cache::put(self::LEGACY_CACHE_KEY, $data, self::CACHE_TTL);
            Cache::put(self::META_CACHE_KEY, [
                'source' => 'aladhan',
                'fetched_at' => now()->toIso8601String(),
                'stale' => false,
            ], self::CACHE_TTL);

            return $data;
        }

        $previous = Cache::get(self::CACHE_KEY, $data);
        Cache::put(self::META_CACHE_KEY, [
            'source' => 'aladhan',
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
        $fallback = [];

        try {
            $today = now()->format('d-m-Y');
            $response = ExternalApiHttp::json(config('services.prayer_times.verify_ssl', true))->get('https://api.aladhan.com/v1/timingsByCity/' . $today, [
                'city' => 'Adiyaman',
                'country' => 'Turkey',
                'method' => 13,
            ]);

            if (! $response->successful()) {
                $response = ExternalApiHttp::json(config('services.prayer_times.verify_ssl', true))->get('https://api.aladhan.com/v1/timings/' . $today, [
                    'latitude' => config('services.prayer_times.latitude', 37.76),
                    'longitude' => config('services.prayer_times.longitude', 38.28),
                    'method' => config('services.prayer_times.method', 13),
                ]);
            }

            if (! $response->successful()) {
                return $fallback;
            }

            $timings = $response->json('data.timings');
            if (! $timings) {
                return $fallback;
            }

            return [
                'imsak' => self::formatTime($timings['Imsak'] ?? ''),
                'gunes' => self::formatTime($timings['Sunrise'] ?? ''),
                'ogle' => self::formatTime($timings['Dhuhr'] ?? ''),
                'ikindi' => self::formatTime($timings['Asr'] ?? ''),
                'aksam' => self::formatTime($timings['Maghrib'] ?? ''),
                'yatsi' => self::formatTime($timings['Isha'] ?? ''),
            ];
        } catch (\Throwable $e) {
            Log::warning('Prayer times API hatası', ['message' => $e->getMessage()]);
            return $fallback;
        }
    }

    private static function isValid(mixed $data): bool
    {
        return is_array($data) && ! empty(array_filter($data));
    }

    private static function formatTime(string $time): string
    {
        $parts = explode(' ', $time);
        return $parts[0] ?? $time;
    }
}