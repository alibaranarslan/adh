<?php

namespace App\Jobs;

use App\Models\NewsArticle;
use App\Services\TranslationService;
use App\Support\TranslationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranslateArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 2;
    public int $backoff = 60;

    public function __construct(
        private int $articleId,
        private bool $force = false,
    ) {
        $this->onQueue('default');
    }

    public function handle(TranslationService $translationService): void
    {
        if (! TranslationSettings::ready()) {
            $this->delete();

            return;
        }

        $article = NewsArticle::find($this->articleId);

        if (!$article) {
            return;
        }

        $translationService->translateModel(
            $article,
            ['title', 'summary', 'content', 'meta_title', 'meta_description'],
            force: $this->force,
        );
    }
}
