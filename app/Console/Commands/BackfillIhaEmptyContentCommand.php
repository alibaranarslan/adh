<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Services\IhaPublicArticleResolverService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillIhaEmptyContentCommand extends Command
{
    protected $signature = 'iha:backfill-empty-content
        {--limit=5 : Islenecek en fazla bos govdeli IHA kaydi}
        {--dry-run : Yalniz eslesmeleri raporla, DB yazma}';

    protected $description = 'Bos govdeli mevcut IHA kayitlarini public IHA article page uzerinden update-only sekilde geri doldurur';

    public function handle(IhaPublicArticleResolverService $resolver): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $allIha = NewsArticle::query()
            ->where('source', 'iha')
            ->get();

        $beforeWithContent = $allIha->filter(fn (NewsArticle $article) => $this->hasContent($article))->count();
        $beforeWithoutContent = $allIha->count() - $beforeWithContent;

        $candidates = $allIha
            ->filter(fn (NewsArticle $article) => ! $this->hasContent($article))
            ->sortByDesc(fn (NewsArticle $article) => optional($article->published_at)?->timestamp ?? 0)
            ->take($limit)
            ->values();

        $updated = [];
        $matched = 0;

        foreach ($candidates as $article) {
            $resolved = $resolver->resolve($article);

            if (! $resolved) {
                $this->line(sprintf('ESLESME YOK: %s', $article->slug));
                continue;
            }

            $matched++;

            if ($dryRun) {
                $this->info(sprintf('DRY RUN ESLESME: %s -> %s', $article->slug, $resolved['url']));
                continue;
            }

            $content = trim((string) ($resolved['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $article->setTranslation('content', 'tr', $content);

            $descriptionSource = trim((string) ($resolved['description'] ?? ''));
            $metaDescription = Str::limit($descriptionSource !== '' ? $descriptionSource : strip_tags($content), 155, '...');
            if ($metaDescription !== '') {
                $article->setTranslation('meta_description', 'tr', $metaDescription);
            }

            $article->save();

            $updated[] = [
                'slug' => $article->slug,
                'url' => $resolved['url'],
                'content_len' => mb_strlen($content),
            ];

            $this->info(sprintf('GUNCELLENDI: %s (%d karakter)', $article->slug, mb_strlen($content)));
        }

        $afterIha = NewsArticle::query()
            ->where('source', 'iha')
            ->get();

        $afterWithContent = $afterIha->filter(fn (NewsArticle $article) => $this->hasContent($article))->count();
        $afterWithoutContent = $afterIha->count() - $afterWithContent;

        $this->newLine();
        $this->line(sprintf('Aday: %d | Eslesme: %d | Guncellenen: %d', $candidates->count(), $matched, count($updated)));
        $this->line(sprintf(
            'Once with/without: %d/%d | Sonra with/without: %d/%d',
            $beforeWithContent,
            $beforeWithoutContent,
            $afterWithContent,
            $afterWithoutContent
        ));

        return self::SUCCESS;
    }

    private function hasContent(NewsArticle $article): bool
    {
        return trim((string) ($article->getTranslation('content', 'tr', false) ?? '')) !== '';
    }
}
