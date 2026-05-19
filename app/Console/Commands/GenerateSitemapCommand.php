<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Tag;
use App\Services\IhaCategoryMapper;
use App\Support\LocalizedUrl;
use App\Support\SeoUrls;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'XML sitemap index, public sitemap files and Google News sitemap üret';

    public function handle(): int
    {
        $baseUrl = SeoUrls::canonicalBaseUrl();
        $generatedAt = now();

        $pageUrls = [
            ['loc' => $this->localizedUrl($baseUrl, '', 'tr'), 'priority' => '1.0', 'changefreq' => 'hourly'],
        ];
        $taxonomyUrls = [];
        $articleUrls = [];
        $newsUrls = [];

        NewsArticle::published()
            ->orderByDesc('published_at')
            ->select(['id', 'slug', 'title', 'published_at', 'updated_at'])
            ->chunk(500, function ($articles) use ($baseUrl, &$articleUrls, &$newsUrls) {
                foreach ($articles as $article) {
                    $title = trim((string) $article->getTranslation('title', 'tr', false));

                    if ($title === '') {
                        continue;
                    }

                    $articleUrls[] = [
                        'loc' => $this->localizedUrl($baseUrl, $article->slug, 'tr'),
                        'lastmod' => ($article->updated_at ?? $article->published_at)?->toAtomString(),
                        'priority' => '0.8',
                        'changefreq' => 'weekly',
                    ];

                    if ($article->published_at && $article->published_at->greaterThanOrEqualTo(now()->subDays(2))) {
                        $newsUrls[] = [
                            'loc' => $this->localizedUrl($baseUrl, $article->slug, 'tr'),
                            'publication_name' => 'Adıyaman Dijital Haber',
                            'publication_language' => 'tr',
                            'publication_date' => $article->published_at->toAtomString(),
                            'title' => $title,
                        ];
                    }
                }
            });

        Category::active()->select(['slug'])->get()->each(function ($category) use ($baseUrl, &$taxonomyUrls): void {
            $taxonomyUrls[] = [
                'loc' => $this->localizedUrl($baseUrl, "kategori/{$category->slug}", 'tr'),
                'priority' => '0.6',
                'changefreq' => 'daily',
            ];
        });

        foreach (IhaCategoryMapper::getActiveCities() as $slug => $cityName) {
            $taxonomyUrls[] = [
                'loc' => $this->localizedUrl($baseUrl, "il/{$slug}", 'tr'),
                'priority' => $slug === 'adiyaman' ? '0.7' : '0.5',
                'changefreq' => 'daily',
            ];
        }

        Tag::select(['slug'])->get()->each(function ($tag) use ($baseUrl, &$taxonomyUrls): void {
            $taxonomyUrls[] = [
                'loc' => $this->localizedUrl($baseUrl, "etiket/{$tag->slug}", 'tr'),
                'priority' => '0.4',
                'changefreq' => 'weekly',
            ];
        });

        Page::published()->select(['slug'])->get()->each(function ($page) use ($baseUrl, &$pageUrls): void {
            $pageUrls[] = [
                'loc' => $this->localizedUrl($baseUrl, 'sayfa/' . $page->slug, 'tr'),
                'priority' => '0.5',
                'changefreq' => 'monthly',
            ];
        });

        file_put_contents(public_path('sitemap-pages.xml'), $this->buildUrlsetXml($pageUrls));
        file_put_contents(public_path('sitemap-categories.xml'), $this->buildUrlsetXml($taxonomyUrls));
        file_put_contents(public_path('sitemap-articles.xml'), $this->buildUrlsetXml($articleUrls));
        file_put_contents(public_path('sitemap-news.xml'), $this->buildNewsXml($newsUrls));
        file_put_contents(public_path('sitemap.xml'), $this->buildSitemapIndexXml([
            ['loc' => SeoUrls::absolute('/sitemap-pages.xml'), 'lastmod' => $generatedAt],
            ['loc' => SeoUrls::absolute('/sitemap-categories.xml'), 'lastmod' => $generatedAt],
            ['loc' => SeoUrls::absolute('/sitemap-articles.xml'), 'lastmod' => $generatedAt],
            ['loc' => SeoUrls::absolute('/sitemap-news.xml'), 'lastmod' => $generatedAt],
        ]));

        $this->info('Sitemap index oluşturuldu: '
            . count($pageUrls) . ' sayfa, '
            . count($taxonomyUrls) . ' kategori/şehir/etiket, '
            . count($articleUrls) . ' haber, '
            . count($newsUrls) . ' news URL.');

        return self::SUCCESS;
    }

    private function buildUrlsetXml(array $urls): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($url['loc']) . '</loc>';
            if (! empty($url['lastmod'])) {
                $lines[] = '    <lastmod>' . htmlspecialchars($url['lastmod']) . '</lastmod>';
            }
            if (! empty($url['changefreq'])) {
                $lines[] = '    <changefreq>' . htmlspecialchars($url['changefreq']) . '</changefreq>';
            }
            if (! empty($url['priority'])) {
                $lines[] = '    <priority>' . htmlspecialchars($url['priority']) . '</priority>';
            }
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    private function buildNewsXml(array $urls): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($url['loc']) . '</loc>';
            $lines[] = '    <news:news>';
            $lines[] = '      <news:publication>';
            $lines[] = '        <news:name>' . htmlspecialchars($url['publication_name']) . '</news:name>';
            $lines[] = '        <news:language>' . htmlspecialchars($url['publication_language']) . '</news:language>';
            $lines[] = '      </news:publication>';
            $lines[] = '      <news:publication_date>' . htmlspecialchars($url['publication_date']) . '</news:publication_date>';
            $lines[] = '      <news:title>' . htmlspecialchars($url['title']) . '</news:title>';
            $lines[] = '    </news:news>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    private function buildSitemapIndexXml(array $sitemaps): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($sitemaps as $sitemap) {
            $lastmod = $sitemap['lastmod'] instanceof Carbon
                ? $sitemap['lastmod']->toAtomString()
                : (string) $sitemap['lastmod'];

            $lines[] = '  <sitemap>';
            $lines[] = '    <loc>' . htmlspecialchars($sitemap['loc']) . '</loc>';
            $lines[] = '    <lastmod>' . htmlspecialchars($lastmod) . '</lastmod>';
            $lines[] = '  </sitemap>';
        }

        $lines[] = '</sitemapindex>';

        return implode("\n", $lines);
    }

    private function localizedUrl(string $baseUrl, string $path, string $locale): string
    {
        return $baseUrl . LocalizedUrl::path($path, $locale);
    }
}
