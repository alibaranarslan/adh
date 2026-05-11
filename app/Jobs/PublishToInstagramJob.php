<?php

namespace App\Jobs;

use App\Models\NewsArticle;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishToInstagramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 300;

    public function __construct(private NewsArticle $article)
    {
        $this->onQueue('instagram');
    }

    public function handle(InstagramService $instagram): void
    {
        if (!$instagram->isConfigured()) {
            Log::info('Instagram atlandi: yapilandirilmamis', ['article_id' => $this->article->id]);
            return;
        }

        try {
            $imageUrl = $instagram->generateNewsImage($this->article);

            if (empty($imageUrl)) {
                Log::warning('Instagram paylasimi atlandi: gorsel bulunamadi', ['article_id' => $this->article->id]);
                return;
            }

            $caption = $instagram->generateCaption($this->article);
            $mediaId = $instagram->publishPhoto($imageUrl, $caption);

            if ($mediaId) {
                Log::info('Instagram paylasimi tamamlandi', [
                    'article_id' => $this->article->id,
                    'media_id' => $mediaId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Instagram job hatasi', [
                'article_id' => $this->article->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}