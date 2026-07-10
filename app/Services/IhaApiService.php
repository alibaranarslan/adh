<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\ExternalApiHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IhaApiService
{
    private const FEED_CACHE_TTL_SECONDS = 600;
    private const FEED_CACHE_VERSION_KEY = 'iha.feed_cache_version';

    private string $baseUrl;
    private string $userCode;
    private string $username;
    private string $password;
    private int $retryAttempts;
    private int $retryDelay;
    private bool $verifySsl;

    public function __construct()
    {
        $this->baseUrl = config('services.iha.base_url', 'https://abonerss.iha.com.tr/xml/standartrss');
        $this->userCode = $this->settingOrConfig('iha_user_code', 'services.iha.user_code');
        $this->username = $this->settingOrConfig('iha_username', 'services.iha.username');
        $this->password = $this->settingOrConfig('iha_password', 'services.iha.password');
        $this->retryAttempts = (int) config('services.iha.retry_attempts', 3);
        $this->retryDelay = (int) config('services.iha.retry_delay', 60);
        $this->verifySsl = (bool) config('services.iha.verify_ssl', true);
    }

    private function settingOrConfig(string $settingKey, string $configKey): string
    {
        $value = Setting::get('integration', $settingKey);

        return (string) (filled($value) ? $value : config($configKey, ''));
    }

    public function fetchNews(?int $cityCode = null, int $ustKategori = 0, bool $fresh = false): array
    {
        $cacheKey = $this->feedCacheKey($cityCode, $ustKategori);

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $articles = $this->requestFeed([
            'UstKategori' => $ustKategori,
            'Kategori' => 0,
            'Sehir' => $cityCode ?? 0,
            'wp' => 0,
            'tagp' => 0,
            'tip' => 1,
        ], [
            'operation' => 'fetch_news',
            'city' => $cityCode,
            'ust_kategori' => $ustKategori,
        ]);

        Cache::put($cacheKey, $articles, now()->addSeconds(self::FEED_CACHE_TTL_SECONDS));

        return $articles;
    }

    public function fetchNewsByCategory(int $categoryCode, bool $fresh = false): array
    {
        $cacheKey = $this->feedCacheKey(null, 0, $categoryCode);

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $articles = $this->requestFeed([
            'UstKategori' => 0,
            'Kategori' => $categoryCode,
            'Sehir' => 0,
            'wp' => 0,
            'tagp' => 0,
            'tip' => 1,
        ], [
            'operation' => 'fetch_category',
            'category' => $categoryCode,
        ]);

        Cache::put($cacheKey, $articles, now()->addSeconds(self::FEED_CACHE_TTL_SECONDS));

        return $articles;
    }

    public function parseXmlResponse(string $xml): array
    {
        if (trim($xml) === '') {
            throw new IhaSyncException('IHA API boş yanıt döndürdü.');
        }

        try {
            $previous = libxml_use_internal_errors(true);
            $parsed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            libxml_use_internal_errors($previous);

            if ($parsed === false) {
                if ($this->looksLikeCredentialRejection($xml)) {
                    Log::warning('IHA credential rejection', [
                        'xml_snippet' => $this->sanitizeMessage(substr($xml, 0, 500)),
                    ]);

                    throw new IhaSyncException('IHA kimlik bilgileri reddedildi. Entegrasyon ayarlarini ve IP yetkisini kontrol edin.');
                }

                Log::warning('IHA XML parse hatası', ['xml_snippet' => substr($xml, 0, 500)]);
                throw new IhaSyncException('IHA XML parse hatası.');
            }

            $articles = [];
            $items = $parsed->channel->item ?? $parsed->item ?? [];

            foreach ($items as $item) {
                $articles[] = $this->normalizeArticle($this->xmlItemToArray($item));
            }

            return $articles;
        } catch (IhaSyncException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('IHA XML işleme hatası', ['message' => $this->sanitizeMessage($e->getMessage())]);
            throw new IhaSyncException('IHA XML işleme hatası.', previous: $e);
        }
    }

    public static function bumpFeedCacheVersion(): void
    {
        Cache::forever(self::FEED_CACHE_VERSION_KEY, (string) now()->timestamp);
    }

    private function baseParams(): array
    {
        return [
            'UserCode' => $this->userCode,
            'UserName' => $this->username,
            'UserPassword' => $this->password,
        ];
    }

    private function requestFeed(array $params, array $context): array
    {
        $this->ensureCredentialsPresent();

        try {
            $response = ExternalApiHttp::html($this->verifySsl)
                ->timeout(30)
                ->retry($this->retryAttempts, $this->retryDelay * 1000)
                ->get($this->baseUrl, array_merge($this->baseParams(), $params));
        } catch (\Throwable $e) {
            $message = 'IHA API bağlantı hatası: ' . $this->sanitizeMessage($e->getMessage());
            Log::error('IHA API bağlantı hatası', array_merge($context, ['message' => $message]));
            throw new IhaSyncException($message, previous: $e);
        }

        if (! $response->successful()) {
            Log::error('IHA API başarısız yanıt', array_merge($context, [
                'status' => $response->status(),
                'body' => $this->sanitizeMessage(substr($response->body(), 0, 500)),
            ]));

            throw new IhaSyncException('IHA API başarısız yanıt döndürdü. HTTP ' . $response->status() . '.');
        }

        return $this->parseXmlResponse($response->body());
    }

    private function ensureCredentialsPresent(): void
    {
        if (blank($this->userCode) || blank($this->username) || blank($this->password)) {
            throw new IhaSyncException('İHA kimlik bilgileri eksik. Entegrasyon ayarlarını kontrol edin.');
        }
    }

    private function feedCacheKey(?int $cityCode = null, int $ustKategori = 0, ?int $categoryCode = null): string
    {
        $version = Cache::get(self::FEED_CACHE_VERSION_KEY, '1');

        if ($categoryCode !== null) {
            return "iha_news_category_{$categoryCode}_v{$version}";
        }

        return 'iha_news_feed' . ($cityCode ? "_{$cityCode}" : '') . "_{$ustKategori}_v{$version}";
    }

    private function sanitizeMessage(string $message): string
    {
        $patterns = [
            '/(UserCode\s*=\s*)[^,\]&\s]+/i',
            '/(UserName\s*=\s*)[^,\]&\s]+/i',
            '/(UserPassword\s*=\s*)[^,\]&\s]+/i',
            '/(UserCode=)[^&\\s]+/i',
            '/(UserName=)[^&\\s]+/i',
            '/(UserPassword=)[^&\\s]+/i',
        ];

        return preg_replace($patterns, '$1***', $message) ?? $message;
    }

    private function looksLikeCredentialRejection(string $body): bool
    {
        return str_contains($body, 'UserPassword=')
            || str_contains($body, 'UserName=')
            || str_contains($body, 'UserCode=');
    }

    private function xmlItemToArray(\SimpleXMLElement $item): array
    {
        $raw = [
            'title' => trim((string) ($item->title ?? '')),
            'description' => trim((string) ($item->description ?? '')),
            'content' => '',
            'link' => trim((string) ($item->link ?? '')),
            'pubDate' => trim((string) ($item->pubDate ?? '')),
            'guid' => trim((string) ($item->HaberKodu ?? $item->guid ?? '')),
            'image_url' => '',
            'category_name' => trim((string) ($item->Kategori ?? '')),
            'ust_kategori' => trim((string) ($item->UstKategori ?? '')),
            'city_name' => trim((string) ($item->Sehir ?? '')),
            'son_dakika' => strtolower(trim((string) ($item->SonDakika ?? ''))) === 'evet',
            'category_code' => 0,
            'city_code' => 0,
        ];

        if (isset($item->images) && isset($item->images->image)) {
            $firstImage = $item->images->image[0] ?? $item->images->image;
            $raw['image_url'] = trim((string) $firstImage);
        }

        $namespaces = $item->getNamespaces(true);
        foreach ($namespaces as $ns) {
            $nsItem = $item->children($ns);
            if (empty($raw['content']) && isset($nsItem->icerik)) {
                $raw['content'] = (string) $nsItem->icerik;
            }
            if (empty($raw['image_url']) && isset($nsItem->resim)) {
                $raw['image_url'] = (string) $nsItem->resim;
            }
        }

        if (empty($raw['image_url']) && isset($item->enclosure)) {
            $raw['image_url'] = (string) $item->enclosure->attributes()->url;
        }

        return $raw;
    }

    public function normalizeArticle(array $raw): array
    {
        $ihaId = $raw['guid'] ?? '';
        if (empty($ihaId)) {
            $ihaId = md5(($raw['title'] ?? '') . ($raw['pubDate'] ?? ''));
        }

        $publishedAt = null;
        if (! empty($raw['pubDate'])) {
            try {
                $publishedAt = \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', $raw['pubDate'])?->toDateTimeString()
                    ?? \Carbon\Carbon::parse($raw['pubDate'])->toDateTimeString();
            } catch (\Exception) {
                $publishedAt = now()->toDateTimeString();
            }
        }

        $content = trim((string) ($raw['content'] ?? ''));
        if ($content === '') {
            $content = trim((string) ($raw['description'] ?? ''));
        }
        $content = preg_replace('/<br\s*\/?>/i', "\n", $content) ?? $content;

        $rawSummary = strip_tags(trim($raw['description'] ?? ''));
        $rawSummary = preg_replace('/\s+/', ' ', $rawSummary);
        if (mb_strlen($rawSummary) > 200) {
            $truncated = mb_substr($rawSummary, 0, 200);
            $lastDot = mb_strrpos($truncated, '.');
            $rawSummary = ($lastDot !== false && $lastDot > 50) ? mb_substr($truncated, 0, $lastDot + 1) : $truncated;
        }

        return [
            'iha_id' => $ihaId,
            'title' => strip_tags(trim($raw['title'] ?? '')),
            'summary' => $rawSummary,
            'content' => trim($content),
            'source_url' => trim($raw['link'] ?? ''),
            'image_url' => trim($raw['image_url'] ?? ''),
            'category_name' => $raw['category_name'] ?? '',
            'ust_kategori' => $raw['ust_kategori'] ?? '',
            'city_name' => $raw['city_name'] ?? '',
            'son_dakika' => $raw['son_dakika'] ?? false,
            'category_code' => (int) ($raw['category_code'] ?? 0),
            'city_code' => (int) ($raw['city_code'] ?? 0),
            'published_at' => $publishedAt,
            'source' => 'iha',
        ];
    }
}
