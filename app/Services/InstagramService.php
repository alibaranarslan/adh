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
        $this->graphVersion = config('services.instagram.graph_version', 'v18.0');
        $this->graphUrl = config('services.instagram.graph_url', 'https://graph.facebook.com');
    }

    protected function getAccessToken(): string
    {
        return Setting::get('integration', 'instagram_access_token')
            ?: config('services.instagram.access_token', '');
    }

    protected function getBusinessAccountId(): string
    {
        return Setting::get('integration', 'instagram_business_account_id')
            ?: config('services.instagram.business_account_id', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->accessToken) && !empty($this->businessAccountId);
    }

    public function configurationStatus(): array
    {
        $missing = [];

        if (empty($this->accessToken)) {
            $missing[] = 'Access token';
        }

        if (empty($this->businessAccountId)) {
            $missing[] = 'Business account ID';
        }

        return [
            'configured' => empty($missing),
            'missing' => $missing,
        ];
    }

    public function publishPhoto(string $imageUrl, string $caption): ?string
    {
        if (!$this->isConfigured()) {
            Log::info('Instagram yayini atlandi: token veya hesap kimligi eksik');
            return null;
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

            if (!$containerResponse->successful()) {
                Log::error('Instagram container olusturulamadi', [
                    'response' => $containerResponse->json(),
                ]);
                return null;
            }

            $containerId = $containerResponse->json('id');

            sleep(3);

            $publishResponse = Http::timeout(30)->post(
                "{$this->graphUrl}/{$this->graphVersion}/{$this->businessAccountId}/media_publish",
                [
                    'creation_id' => $containerId,
                    'access_token' => $this->accessToken,
                ]
            );

            if (!$publishResponse->successful()) {
                Log::error('Instagram yayini basarisiz', [
                    'container_id' => $containerId,
                    'response' => $publishResponse->json(),
                ]);
                return null;
            }

            $mediaId = $publishResponse->json('id');
            Log::info('Instagram paylasimi basarili', ['media_id' => $mediaId]);

            return $mediaId;
        } catch (\Exception $e) {
            Log::error('Instagram servisi hatasi', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function generateCaption(NewsArticle $article): string
    {
        $title = $article->getTranslation('title', 'tr');
        $summary = Str::limit($article->getTranslation('summary', 'tr'), 200);
        $url = url('/') . '/' . ltrim($article->slug, '/');
        $hashtags = $this->generateHashtags($article);

        return "{$title}\n\n{$summary}\n\n{$url}\n\n{$hashtags}";
    }

    public function generateNewsImage(NewsArticle $article): ?string
    {
        if ($article->hasMedia('featured_image')) {
            return $article->getFirstMediaUrl('featured_image');
        }

        if (!empty($article->featured_image)) {
            return Storage::disk('public')->url($article->featured_image);
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
}