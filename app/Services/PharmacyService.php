<?php

namespace App\Services;

use App\Support\ExternalApiHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PharmacyService
{
    private const CACHE_KEY = 'pharmacy.today';
    private const META_CACHE_KEY = 'pharmacy.today.meta';

    private string $apiUrl;
    private string $apiKey;
    private string $city;
    private int $cacheTtl;

    public function __construct()
    {
        $this->apiUrl = config('services.pharmacy.api_url', 'https://api.nosyapi.com/apiv2/pharmacy');
        $this->apiKey = config('services.pharmacy.api_key', '');
        $this->city = config('services.pharmacy.city', 'adiyaman');
        $this->cacheTtl = (int) config('services.pharmacy.cache_ttl', 1440);
    }

    public function getTodayPharmacies(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($this->isValid($cached)) {
            return $cached;
        }

        return $this->refreshCache();
    }

    public function refreshCache(): array
    {
        $fetched = $this->fetchFromSources();

        if ($this->isValid($fetched['data'])) {
            Cache::put(self::CACHE_KEY, $fetched['data'], $this->cacheTtl * 60);
            Cache::put(self::META_CACHE_KEY, [
                'source' => $fetched['source'],
                'fetched_at' => now()->toIso8601String(),
                'stale' => false,
            ], $this->cacheTtl * 60);

            return $fetched['data'];
        }

        $previous = Cache::get(self::CACHE_KEY, []);

        Cache::put(self::META_CACHE_KEY, [
            'source' => $fetched['source'] ?? 'none',
            'fetched_at' => now()->toIso8601String(),
            'stale' => $this->isValid($previous),
        ], $this->cacheTtl * 60);

        return $this->isValid($previous) ? $previous : [];
    }

    public function meta(): array
    {
        return Cache::get(self::META_CACHE_KEY, []);
    }

    private function fetchFromSources(): array
    {
        if (! empty($this->apiKey)) {
            $nosy = $this->fetchFromNosyApi();

            if ($this->isValid($nosy)) {
                return ['data' => $nosy, 'source' => 'nosyapi'];
            }
        }

        $eczaneIo = $this->fetchFromEczaneIo();
        if ($this->isValid($eczaneIo)) {
            return ['data' => $eczaneIo, 'source' => 'eczane.io'];
        }

        return ['data' => [], 'source' => 'none'];
    }

    private function fetchFromNosyApi(): array
    {
        try {
            $response = ExternalApiHttp::json(config('services.pharmacy.verify_ssl', true))
                ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
                ->get($this->apiUrl, ['ilce' => $this->city]);

            if (! $response->successful()) {
                Log::warning('Nöbetçi eczane API hatası', ['status' => $response->status()]);
                return [];
            }

            $data = $response->json();
            $pharmacies = $data['data'] ?? $data['result'] ?? $data ?? [];

            return $this->normalize((array) $pharmacies);
        } catch (\Throwable $e) {
            Log::error('Nöbetçi eczane servisi hatası', ['message' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchFromEczaneIo(): array
    {
        $paths = [
            'https://www.eczane.io/' . $this->city,
            'https://www.eczane.io/' . $this->city . '/' . now()->format('d-m-Y'),
            'https://www.eczane.io/' . $this->city . '/' . Str::slug(now()->locale('tr')->translatedFormat('j F Y'), '-'),
        ];

        foreach ($paths as $url) {
            try {
                $response = ExternalApiHttp::html(config('services.pharmacy.verify_ssl', true))->get($url);

                if (! $response->successful()) {
                    continue;
                }

                $parsed = $this->parseEczaneIoHtml((string) $response->body());
                if ($this->isValid($parsed)) {
                    return $parsed;
                }
            } catch (\Throwable $e) {
                Log::warning('Eczane.io kazıma hatası', [
                    'message' => $e->getMessage(),
                    'url' => $url,
                ]);
            }
        }

        return [];
    }

    private function parseEczaneIoHtml(string $html): array
    {
        $cardMatches = [];
        preg_match_all(
            '/<h3[^>]*>([^<]+)<\/h3>\s*<p[^>]*>([^<]+)<\/p>.*?<p class="text-xs leading-relaxed">([^<]+)<\/p>.*?(?:href="tel:([^"]+)")?/si',
            $html,
            $cardMatches,
            PREG_SET_ORDER
        );

        if (! empty($cardMatches)) {
            return $this->normalize(array_map(function (array $match) {
                return [
                    'name' => $this->normalizeText($match[1] ?? ''),
                    'district' => $this->normalizeText($match[2] ?? ''),
                    'address' => $this->normalizeText($match[3] ?? ''),
                    'phone' => $this->normalizePhone($match[4] ?? ''),
                    'lat' => null,
                    'lng' => null,
                ];
            }, $cardMatches));
        }

        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\r/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;

        $lines = array_values(array_filter(array_map(
            static fn (string $line) => trim($line),
            preg_split('/\n+/u', $text) ?: []
        )));

        $pharmacies = [];
        $lineCount = count($lines);

        for ($i = 0; $i < $lineCount; $i++) {
            $line = $this->normalizeText($lines[$i]);

            if (! preg_match('/ECZANESI$/iu', Str::upper($line))) {
                continue;
            }

            $addressParts = [];
            $phone = '';

            for ($j = $i + 1; $j < min($i + 8, $lineCount); $j++) {
                $candidate = $this->normalizeText($lines[$j]);

                if ($candidate === '' || preg_match('/^\d+$/', $candidate)) {
                    continue;
                }

                if (preg_match('/ECZANESI$/iu', Str::upper($candidate))) {
                    break;
                }

                if (preg_match('/\b(Ara|Yol Tarifi|Haritada Aç|Bugün|Yarın|Yakınındaki|Nöbetçi Eczane)\b/ui', $candidate)) {
                    continue;
                }

                if (preg_match('/0\s*\(?\d{3}\)?\s*\d{3}\s*\d{2}\s*\d{2}/u', $candidate, $matches)) {
                    $phone = $this->normalizePhone($matches[0]);
                    continue;
                }

                $addressParts[] = $candidate;
            }

            $pharmacies[] = [
                'name' => $line,
                'address' => implode(' ', array_slice($addressParts, 0, 3)),
                'phone' => $phone,
                'lat' => null,
                'lng' => null,
            ];
        }

        return $this->normalize($pharmacies);
    }

    private function normalize(array $pharmacies): array
    {
        return collect($pharmacies)
            ->map(function (array $pharmacy) {
                return [
                    'name' => trim((string) ($pharmacy['name'] ?? $pharmacy['adi'] ?? '')),
                    'address' => trim((string) ($pharmacy['address'] ?? $pharmacy['adres'] ?? '')),
                    'phone' => trim((string) ($pharmacy['phone'] ?? $pharmacy['telefon'] ?? '')),
                    'lat' => $pharmacy['lat'] ?? $pharmacy['latitude'] ?? null,
                    'lng' => $pharmacy['lng'] ?? $pharmacy['longitude'] ?? null,
                ];
            })
            ->filter(fn (array $pharmacy) => $pharmacy['name'] !== '' && $pharmacy['address'] !== '')
            ->unique('name')
            ->values()
            ->all();
    }

    private function normalizeText(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        return str_replace(['Â'], [''], $value);
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '90')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    private function isValid(mixed $pharmacies): bool
    {
        return is_array($pharmacies) && count($pharmacies) > 0;
    }
}