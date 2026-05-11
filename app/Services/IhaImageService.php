<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IhaImageService
{
    private string $disk;
    private string $path;
    private bool $verifySsl;

    public function __construct()
    {
        $this->disk      = config('services.iha.image_disk', 'public');
        $this->path      = config('services.iha.image_path', 'news-images');
        $this->verifySsl = (bool) config('services.iha.verify_ssl', true);
    }

    public function downloadImage(string $url, string $articleSlug): ?string
    {
        if (empty($url)) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => $this->verifySsl])
                ->retry(2, 5000)
                ->get($url);

            if (!$response->successful()) {
                Log::warning('IHA görsel indirme başarısız', ['url' => $url, 'status' => $response->status()]);
                return null;
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';
            $extension   = $this->getExtensionFromContentType($contentType);
            $filename    = $articleSlug . '-' . Str::random(6) . '.' . $extension;
            $storagePath = $this->path . '/' . date('Y/m') . '/' . $filename;

            Storage::disk($this->disk)->put($storagePath, $response->body());

            return $storagePath;
        } catch (\Exception $e) {
            Log::error('IHA görsel indirme hatası', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function optimizeImage(string $storagePath): void
    {
        // Spatie Media Library handles conversions via registerMediaCollections
        // This method is a hook for future standalone optimization if needed
        // WebP conversion and resizing is defined on the NewsArticle model media collections
    }

    public function getPlaceholderPath(): string
    {
        return 'images/news-placeholder.jpg';
    }

    private function getExtensionFromContentType(string $contentType): string
    {
        return match (true) {
            str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
            str_contains($contentType, 'png')  => 'png',
            str_contains($contentType, 'webp') => 'webp',
            str_contains($contentType, 'gif')  => 'gif',
            default                            => 'jpg',
        };
    }
}
