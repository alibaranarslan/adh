<?php

namespace App\Services;

use App\Models\NewsArticle;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InstagramService
{
    private string $accessToken;
    private string $businessAccountId;
    private string $graphVersion;
    private string $graphUrl;

    public function __construct()
    {
        $this->accessToken = $this->getAccessToken();
        $this->businessAccountId = $this->getBusinessAccountId();
        $this->graphVersion = config('services.instagram.graph_version', 'v24.0');
        $this->graphUrl = config('services.instagram.graph_url', 'https://graph.facebook.com');
    }

    protected function getAccessToken(): string
    {
        return (string) (Setting::get('integration', 'instagram_access_token')
            ?: config('services.instagram.access_token', ''));
    }

    protected function getBusinessAccountId(): string
    {
        return (string) (Setting::get('integration', 'instagram_business_account_id')
            ?: config('services.instagram.business_account_id', ''));
    }

    public function automationEnabled(): bool
    {
        $value = Setting::get('integration', 'instagram_enabled', config('services.instagram.enabled', false));

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->accessToken) && ! empty($this->businessAccountId);
    }

    public function isReady(): bool
    {
        return $this->automationEnabled() && $this->isConfigured();
    }

    public function configurationStatus(): array
    {
        $missing = [];

        if (! $this->automationEnabled()) {
            $missing[] = 'Instagram automation enabled';
        }

        if (empty($this->accessToken)) {
            $missing[] = 'Access token';
        }

        if (empty($this->businessAccountId)) {
            $missing[] = 'Business account ID';
        }

        return [
            'configured' => empty($missing),
            'enabled' => $this->automationEnabled(),
            'missing' => $missing,
        ];
    }

    public function publishPhoto(string $imageUrl, string $caption): ?string
    {
        $result = $this->publishImage($imageUrl, $caption);

        return $result['ok'] ? ($result['media_id'] ?? null) : null;
    }

    public function publishImage(string $imageUrl, string $caption): array
    {
        if (! $this->isReady()) {
            Log::info('Instagram yayini atlandi: otomasyon kapali veya kimlik bilgisi eksik');

            return [
                'ok' => false,
                'skipped' => true,
                'error' => 'Instagram otomasyonu kapali veya kimlik bilgisi eksik.',
            ];
        }

        try {
            $containerResponse = Http::timeout(30)->post(
                "{$this->graphUrl}/{$this->graphVersion}/{$this->businessAccountId}/media",
                [
                    'image_url' => $imageUrl,
                    'caption' => $caption,
                    'access_token' => $this->accessToken,
                ]
            );

            if (! $containerResponse->successful()) {
                $error = $this->responseError($containerResponse->json(), 'Instagram container olusturulamadi');
                Log::error('Instagram container olusturulamadi', [
                    'response' => $containerResponse->json(),
                ]);

                return [
                    'ok' => false,
                    'skipped' => false,
                    'error' => $error,
                ];
            }

            $containerId = $containerResponse->json('id');

            if (! app()->runningUnitTests()) {
                sleep(3);
            }

            $publishResponse = Http::timeout(30)->post(
                "{$this->graphUrl}/{$this->graphVersion}/{$this->businessAccountId}/media_publish",
                [
                    'creation_id' => $containerId,
                    'access_token' => $this->accessToken,
                ]
            );

            if (! $publishResponse->successful()) {
                $error = $this->responseError($publishResponse->json(), 'Instagram yayini basarisiz');
                Log::error('Instagram yayini basarisiz', [
                    'container_id' => $containerId,
                    'response' => $publishResponse->json(),
                ]);

                return [
                    'ok' => false,
                    'skipped' => false,
                    'container_id' => $containerId,
                    'error' => $error,
                ];
            }

            $mediaId = $publishResponse->json('id');
            Log::info('Instagram paylasimi basarili', ['media_id' => $mediaId]);

            return [
                'ok' => true,
                'skipped' => false,
                'container_id' => $containerId,
                'media_id' => $mediaId,
            ];
        } catch (\Throwable $e) {
            Log::error('Instagram servisi hatasi', ['message' => $e->getMessage()]);

            return [
                'ok' => false,
                'skipped' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function generateCaption(NewsArticle $article): string
    {
        $title = $this->translation($article, 'title');
        $summary = Str::limit($this->translation($article, 'summary'), 240);
        $url = url('/') . '/' . ltrim($article->slug, '/');
        $hashtags = $this->generateHashtags($article);
        $caption = trim("{$title}\n\n{$summary}\n\nHaberi okumak icin:\n{$url}\n\n{$hashtags}");

        return Str::limit($caption, 2200, '');
    }

    public function generateShortTitle(NewsArticle $article): string
    {
        return Str::limit($this->translation($article, 'title'), 72, '');
    }

    public function generateNewsImage(NewsArticle $article): ?string
    {
        if ($article->hasMedia('featured_image')) {
            return $this->absoluteUrl($article->getFirstMediaUrl('featured_image'));
        }

        if (! empty($article->featured_image)) {
            return $this->absoluteUrl($article->featured_image);
        }

        return null;
    }

    public function generateCreativeImage(NewsArticle $article): ?array
    {
        $source = $this->loadSourceImage($article);

        if (! $source) {
            return null;
        }

        $canvas = $this->coverCrop($source, 1080, 1080);
        imagedestroy($source);

        $this->drawOverlay($canvas, $article);

        $path = 'instagram/creatives/' . now()->format('Y/m') . '/' . $article->slug . '-' . Str::random(6) . '.jpg';

        ob_start();
        imagejpeg($canvas, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($canvas);

        if ($bytes === false) {
            return null;
        }

        Storage::disk('public')->put($path, $bytes);

        return [
            'path' => $path,
            'url' => $this->absoluteUrl(Storage::disk('public')->url($path)),
        ];
    }

    private function loadSourceImage(NewsArticle $article): mixed
    {
        $bytes = null;

        if ($article->hasMedia('featured_image')) {
            $path = $article->getFirstMediaPath('featured_image');
            $bytes = is_file($path) ? file_get_contents($path) : null;
        } elseif (! empty($article->featured_image)) {
            $bytes = $this->readImageBytes((string) $article->featured_image);
        }

        if (! $bytes) {
            return null;
        }

        $image = @imagecreatefromstring($bytes);

        return $image ?: null;
    }

    private function readImageBytes(string $pathOrUrl): ?string
    {
        if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
            $response = Http::timeout(20)->get($pathOrUrl);

            return $response->successful() ? $response->body() : null;
        }

        $normalized = ltrim($pathOrUrl, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            $publicPath = public_path($normalized);
            if (is_file($publicPath)) {
                return file_get_contents($publicPath) ?: null;
            }

            $storageRelative = Str::after($normalized, 'storage/');
            $storagePath = Storage::disk('public')->path($storageRelative);

            return is_file($storagePath) ? (file_get_contents($storagePath) ?: null) : null;
        }

        $storagePath = Storage::disk('public')->path($normalized);
        if (is_file($storagePath)) {
            return file_get_contents($storagePath) ?: null;
        }

        $publicPath = public_path($normalized);

        return is_file($publicPath) ? (file_get_contents($publicPath) ?: null) : null;
    }

    private function coverCrop(mixed $source, int $targetWidth, int $targetHeight): mixed
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $scaledWidth = (int) ceil($sourceWidth * $scale);
        $scaledHeight = (int) ceil($sourceHeight * $scale);

        $scaled = imagecreatetruecolor($scaledWidth, $scaledHeight);
        imagecopyresampled($scaled, $source, 0, 0, 0, 0, $scaledWidth, $scaledHeight, $sourceWidth, $sourceHeight);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopy(
            $canvas,
            $scaled,
            0,
            0,
            (int) max(0, ($scaledWidth - $targetWidth) / 2),
            (int) max(0, ($scaledHeight - $targetHeight) / 2),
            $targetWidth,
            $targetHeight
        );
        imagedestroy($scaled);

        return $canvas;
    }

    private function drawOverlay(mixed $image, NewsArticle $article): void
    {
        imagealphablending($image, true);

        for ($y = 560; $y < 1080; $y++) {
            $progress = ($y - 560) / 520;
            $alpha = (int) (105 - ($progress * 75));
            $color = imagecolorallocatealpha($image, 0, 0, 0, max(20, min(105, $alpha)));
            imageline($image, 0, $y, 1080, $y, $color);
        }

        $red = imagecolorallocate($image, 211, 38, 38);
        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 230, 230, 230);
        imagefilledrectangle($image, 0, 930, 1080, 1080, imagecolorallocatealpha($image, 0, 0, 0, 18));
        imagefilledrectangle($image, 72, 928, 250, 936, $red);

        $font = $this->fontPath();
        $title = $this->generateShortTitle($article);
        $brand = 'ADIYAMAN DIJITAL HABER';

        if ($font) {
            $this->drawWrappedText($image, $title, $font, 50, 72, 690, 936, $white, 2);
            imagettftext($image, 23, 0, 72, 1000, $muted, $font, $brand);
        } else {
            imagestring($image, 5, 72, 720, $title, $white);
            imagestring($image, 4, 72, 980, $brand, $muted);
        }
    }

    private function drawWrappedText(mixed $image, string $text, string $font, int $size, int $x, int $y, int $maxWidth, int $color, int $maxLines): void
    {
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = ($box[2] ?? 0) - ($box[0] ?? 0);

            if ($line !== '' && $width > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }

            if (count($lines) >= $maxLines) {
                break;
            }
        }

        if ($line !== '' && count($lines) < $maxLines) {
            $lines[] = $line;
        }

        foreach (array_slice($lines, 0, $maxLines) as $index => $lineText) {
            imagettftext($image, $size, 0, $x, $y + ($index * 64), $color, $font, $lineText);
        }
    }

    private function fontPath(): ?string
    {
        $candidates = [
            public_path('fonts/DejaVuSans-Bold.ttf'),
            'C:\\Windows\\Fonts\\arialbd.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function generateHashtags(NewsArticle $article): string
    {
        $tags = ['#adiyaman', '#adiyamandijital', '#haber'];

        if ($article->relationLoaded('category') ? $article->category : $article->category()->exists()) {
            $categoryName = optional($article->category)->getTranslation('name', 'tr');
            $categorySlug = Str::slug((string) $categoryName, '');
            if ($categorySlug !== '') {
                $tags[] = '#' . $categorySlug;
            }
        }

        $articleTags = $article->relationLoaded('tags') ? $article->tags : $article->tags()->get();
        foreach ($articleTags as $tag) {
            $tagName = $tag->getTranslation('name', 'tr') ?? $tag->name ?? '';
            $slug = Str::slug((string) $tagName, '');
            if ($slug !== '') {
                $tags[] = '#' . $slug;
            }
        }

        return implode(' ', array_unique(array_slice($tags, 0, 10)));
    }

    private function translation(NewsArticle $article, string $field): string
    {
        return trim((string) $article->getTranslation($field, 'tr', false));
    }

    private function absoluteUrl(string $pathOrUrl): string
    {
        if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
            return app()->environment('production') ? str_replace('http://', 'https://', $pathOrUrl) : $pathOrUrl;
        }

        $path = '/' . ltrim($pathOrUrl, '/');

        return app()->environment('production') ? secure_url($path) : url($path);
    }

    private function responseError(?array $response, string $fallback): string
    {
        $message = data_get($response, 'error.message');

        return is_string($message) && $message !== '' ? $message : $fallback;
    }
}
