<?php

namespace App\Jobs;

use App\Models\SocialPublication;
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
    public int $timeout = 180;
    public int $backoff = 300;

    public function __construct(private int $publicationId)
    {
        $this->onQueue('instagram');
    }

    public function handle(InstagramService $instagram): void
    {
        $publication = SocialPublication::query()
            ->with(['article.category', 'article.tags', 'article.media'])
            ->find($this->publicationId);

        if (! $publication || $publication->status === SocialPublication::STATUS_PUBLISHED) {
            return;
        }

        $publication->forceFill([
            'status' => SocialPublication::STATUS_PROCESSING,
            'attempts' => $publication->attempts + 1,
            'error_message' => null,
        ])->save();

        $article = $publication->article;

        if (! $article || $article->status !== 'published') {
            $this->markSkipped($publication, 'Haber yayinda degil veya bulunamadi.');

            return;
        }

        if (! $instagram->isReady()) {
            $this->markSkipped($publication, 'Instagram otomasyonu kapali veya kimlik bilgisi eksik.');

            return;
        }

        try {
            $creative = $instagram->generateCreativeImage($article);

            if (! $creative) {
                $this->markSkipped($publication, 'Instagram paylasimi icin haber gorseli bulunamadi.');

                return;
            }

            $caption = $instagram->generateCaption($article);

            $publication->forceFill([
                'caption' => $caption,
                'creative_image_path' => $creative['path'],
                'creative_image_url' => $creative['url'],
            ])->save();

            $result = $instagram->publishImage($creative['url'], $caption);

            if (! ($result['ok'] ?? false)) {
                $publication->forceFill([
                    'status' => ($result['skipped'] ?? false)
                        ? SocialPublication::STATUS_SKIPPED
                        : SocialPublication::STATUS_FAILED,
                    'container_id' => $result['container_id'] ?? null,
                    'error_message' => $result['error'] ?? 'Instagram paylasimi basarisiz.',
                ])->save();

                return;
            }

            $publication->forceFill([
                'status' => SocialPublication::STATUS_PUBLISHED,
                'container_id' => $result['container_id'] ?? null,
                'media_id' => $result['media_id'] ?? null,
                'error_message' => null,
                'published_at' => now(),
            ])->save();

            Log::info('Instagram paylasimi tamamlandi', [
                'article_id' => $article->id,
                'publication_id' => $publication->id,
                'media_id' => $publication->media_id,
            ]);
        } catch (\Throwable $e) {
            $publication->forceFill([
                'status' => SocialPublication::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ])->save();

            Log::error('Instagram job hatasi', [
                'publication_id' => $publication->id,
                'article_id' => $article?->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function markSkipped(SocialPublication $publication, string $reason): void
    {
        $publication->forceFill([
            'status' => SocialPublication::STATUS_SKIPPED,
            'error_message' => $reason,
        ])->save();
    }
}
