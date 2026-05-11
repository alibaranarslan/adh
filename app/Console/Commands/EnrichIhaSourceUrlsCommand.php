<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Services\IhaPublicArticleResolverService;
use Illuminate\Console\Command;

class EnrichIhaSourceUrlsCommand extends Command
{
    protected $signature = 'iha:enrich-source-urls
        {--limit=10 : Islenecek en fazla generic source_url sahibi IHA kaydi}
        {--slug=* : Belirli slug veya slug listesini hedefle}
        {--url=* : slug=https://... formatinda bilinen public URL ile exact dogrulama yap}
        {--dry-run : Eslesmeleri raporla, DB yazma}';

    protected $description = 'Generic IHA source_url alanlarini exact public article resolve ile update-only sekilde zenginlestirir';

    public function handle(IhaPublicArticleResolverService $resolver): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $slugs = collect($this->option('slug'))
            ->map(fn (string $slug) => trim($slug))
            ->filter()
            ->values();
        [$urlMap, $invalidUrlSlugs] = $this->parseUrlMap($this->option('url'));
        $invalidUrlSlugs = collect($invalidUrlSlugs);
        $targetSlugs = $slugs
            ->merge(array_keys($urlMap))
            ->merge($invalidUrlSlugs)
            ->unique()
            ->values();

        $candidates = NewsArticle::query()
            ->where('source', 'iha')
            ->where(function ($query) {
                $query->whereNull('source_url')
                    ->orWhere('source_url', '')
                    ->orWhere('source_url', 'https://www.iha.com.tr');
            })
            ->when($targetSlugs->isNotEmpty(), fn ($query) => $query->whereIn('slug', $targetSlugs->all()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $matched = 0;
        $updated = 0;
        $unresolved = 0;

        foreach ($candidates as $article) {
            if ($invalidUrlSlugs->contains($article->slug) && ! isset($urlMap[$article->slug])) {
                $unresolved++;
                $this->warn(sprintf('GECERSIZ IHA URL NEDENIYLE ATLANDI: %s', $article->slug));
                continue;
            }

            $resolved = isset($urlMap[$article->slug])
                ? $resolver->resolveFromUrl($article, $urlMap[$article->slug])
                : $resolver->resolve($article);
            $sourceUrl = trim((string) ($resolved['url'] ?? ''));

            if ($sourceUrl === '') {
                $unresolved++;
                $this->line(sprintf('ESLESME YOK: %s', $article->slug));
                continue;
            }

            $matched++;

            if ($dryRun) {
                $this->info(sprintf('DRY RUN ESLESME: %s -> %s', $article->slug, $sourceUrl));
                continue;
            }

            $article->forceFill(['source_url' => $sourceUrl])->save();
            $updated++;

            $this->info(sprintf('GUNCELLENDI: %s -> %s', $article->slug, $sourceUrl));
        }

        $this->newLine();
        $this->line(sprintf(
            'IHA_SOURCE_URL_ENRICHMENT scanned=%d matched=%d updated=%d unresolved=%d dry_run=%s',
            $candidates->count(),
            $matched,
            $updated,
            $unresolved,
            $dryRun ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }

    /**
     * @param list<string> $urlOptions
     *
     * @return array{0: array<string, string>, 1: list<string>}
     */
    private function parseUrlMap(array $urlOptions): array
    {
        $map = [];
        $invalidSlugs = [];

        foreach ($urlOptions as $option) {
            [$slug, $url] = array_pad(explode('=', trim((string) $option), 2), 2, '');
            $slug = trim($slug);
            $url = trim($url);

            if ($slug === '' || $url === '') {
                continue;
            }

            if (! $this->isAllowedIhaArticleUrl($url)) {
                $invalidSlugs[] = $slug;
                $this->warn(sprintf('GECERSIZ IHA URL: %s -> %s', $slug, $url));
                continue;
            }

            $map[$slug] = $url;
        }

        return [$map, $invalidSlugs];
    }

    private function isAllowedIhaArticleUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''));

        if ($scheme !== 'https' || $host !== 'www.iha.com.tr') {
            return false;
        }

        if ($path === '' || $path === '/') {
            return false;
        }

        return ! str_starts_with(strtolower($path), '/arama');
    }
}
