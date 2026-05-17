<?php

namespace App\Observers;

use App\Jobs\TranslateArticleJob;
use App\Models\NewsArticle;
use App\Models\Redirect;
use App\Services\SocialPublicationService;
use App\Support\TranslationSettings;

class NewsArticleObserver
{
    public function updating(NewsArticle $article): void
    {
        if ($article->isDirty('slug')) {
            $oldSlug = $article->getOriginal('slug');
            $newSlug = $article->slug;

            if ($oldSlug && $oldSlug !== $newSlug) {
                Redirect::updateOrCreate(
                    ['old_slug' => $oldSlug, 'model_type' => 'news_article'],
                    ['new_slug' => $newSlug, 'model_id' => $article->id, 'status_code' => 301]
                );

                Redirect::query()
                    ->where('new_slug', $oldSlug)
                    ->where('model_type', 'news_article')
                    ->update(['new_slug' => $newSlug]);
            }
        }
    }

    public function created(NewsArticle $article): void
    {
        $this->dispatchInstagramJobIfNeeded($article);
        $this->dispatchTranslationIfNeeded($article);
    }

    public function updated(NewsArticle $article): void
    {
        if ($article->wasChanged('status') && $article->status === 'published') {
            $this->dispatchInstagramJobIfNeeded($article);
            $this->dispatchTranslationIfNeeded($article);
        }
    }

    private function dispatchInstagramJobIfNeeded(NewsArticle $article): void
    {
        app(SocialPublicationService::class)->enqueueForArticle($article);
    }

    private function dispatchTranslationIfNeeded(NewsArticle $article): void
    {
        if ($article->source === 'iha') {
            return;
        }

        if (
            $article->status === 'published'
            && TranslationSettings::ready()
        ) {
            TranslateArticleJob::dispatch($article->id)->delay(now()->addSeconds(30));
        }
    }
}
