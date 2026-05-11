<?php

namespace App\Observers;

use App\Jobs\PublishToInstagramJob;
use App\Jobs\TranslateArticleJob;
use App\Models\NewsArticle;
use App\Models\Redirect;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

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
        if ($article->status !== 'published') {
            return;
        }

        $token = Setting::get('integration', 'instagram_access_token')
            ?: config('services.instagram.access_token');
        $businessAccountId = Setting::get('integration', 'instagram_business_account_id')
            ?: config('services.instagram.business_account_id');

        if (empty($token) || empty($businessAccountId)) {
            Log::info('Instagram kuyruğu atlandı: eksik kimlik bilgisi', [
                'article_id' => $article->id,
                'has_token' => !empty($token),
                'has_business_account_id' => !empty($businessAccountId),
            ]);
            return;
        }

        PublishToInstagramJob::dispatch($article)->delay(now()->addMinutes(2));
    }

    private function dispatchTranslationIfNeeded(NewsArticle $article): void
    {
        if ($article->source === 'iha') {
            return;
        }

        if (
            $article->status === 'published'
            && !empty(config('services.google_translate.api_key'))
        ) {
            TranslateArticleJob::dispatch($article->id)->delay(now()->addSeconds(30));
        }
    }
}