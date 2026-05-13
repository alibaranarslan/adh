<?php

namespace App\Services;

use App\Jobs\PublishToInstagramJob;
use App\Models\NewsArticle;
use App\Models\SocialPublication;

class SocialPublicationService
{
    public function enqueueForArticle(NewsArticle $article): ?SocialPublication
    {
        if ($article->status !== 'published') {
            return null;
        }

        $publication = SocialPublication::query()->firstOrCreate(
            [
                'news_article_id' => $article->id,
                'platform' => SocialPublication::PLATFORM_INSTAGRAM,
            ],
            [
                'status' => SocialPublication::STATUS_PENDING,
            ]
        );

        if (! $publication->wasRecentlyCreated) {
            return $publication;
        }

        PublishToInstagramJob::dispatch($publication->id)->delay(now()->addMinutes(2));

        return $publication;
    }

    public function retry(SocialPublication $publication): SocialPublication
    {
        if ($publication->platform !== SocialPublication::PLATFORM_INSTAGRAM) {
            return $publication;
        }

        if ($publication->status === SocialPublication::STATUS_PUBLISHED) {
            return $publication;
        }

        $publication->forceFill([
            'status' => SocialPublication::STATUS_PENDING,
            'error_message' => null,
            'container_id' => null,
            'media_id' => null,
            'published_at' => null,
        ])->save();

        PublishToInstagramJob::dispatch($publication->id);

        return $publication;
    }
}
