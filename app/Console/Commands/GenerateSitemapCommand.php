<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Tag;
use App\Support\LocalizedUrl;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'XML sitemap oluştur ve public dizinine kaydet';

    public function handle(): int
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $urls    = [];

        // Homepage
        foreach (LocalizedUrl::SUPPORTED_LOCALES as $locale) {
            $urls[] = ['loc' => $this->localizedUrl($baseUrl, '', $locale), 'priority' => '1.0', 'changefreq' => 'hourly'];
        }

        // Published news articles
        NewsArticle::published()
            ->orderByDesc('published_at')
            ->select(['id', 'slug', 'title', 'content', 'published_at', 'updated_at'])
            ->chunk(500, function ($articles) use ($baseUrl, &$urls) {
                foreach ($articles as $article) {
                    foreach (LocalizedUrl::SUPPORTED_LOCALES as $locale) {
                        if (! LocalizedUrl::articleHasLocaleContent($article, $locale)) {
                            continue;
                        }

                        $urls[] = [
                            'loc'        => $this->localizedUrl($baseUrl, $article->slug, $locale),
                            'lastmod'    => ($article->updated_at ?? $article->published_at)?->toAtomString(),
                            'priority'   => '0.8',
                            'changefreq' => 'weekly',
                        ];
                    }
                }
            });

        // Categories
        Category::active()->select(['slug'])->get()->each(function ($cat) use ($baseUrl, &$urls) {
            foreach (LocalizedUrl::SUPPORTED_LOCALES as $locale) {
                $urls[] = [
                    'loc'        => $this->localizedUrl($baseUrl, "kategori/{$cat->slug}", $locale),
                    'priority'   => '0.6',
                    'changefreq' => 'daily',
                ];
            }
        });

        // Tags
        Tag::select(['slug'])->get()->each(function ($tag) use ($baseUrl, &$urls) {
            foreach (LocalizedUrl::SUPPORTED_LOCALES as $locale) {
                $urls[] = [
                    'loc'        => $this->localizedUrl($baseUrl, "etiket/{$tag->slug}", $locale),
                    'priority'   => '0.4',
                    'changefreq' => 'weekly',
                ];
            }
        });

        // Published pages
        Page::published()->select(['slug'])->get()->each(function ($page) use ($baseUrl, &$urls) {
            foreach (LocalizedUrl::SUPPORTED_LOCALES as $locale) {
                $urls[] = [
                    'loc'        => $this->localizedUrl($baseUrl, 'sayfa/' . $page->slug, $locale),
                    'priority'   => '0.5',
                    'changefreq' => 'monthly',
                ];
            }
        });

        $xml = $this->buildXml($urls);
        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('Sitemap oluşturuldu: ' . count($urls) . ' URL.');

        return self::SUCCESS;
    }

    private function buildXml(array $urls): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($url['loc']) . '</loc>';
            if (!empty($url['lastmod']))   $lines[] = '    <lastmod>' . $url['lastmod'] . '</lastmod>';
            if (!empty($url['changefreq'])) $lines[] = '    <changefreq>' . $url['changefreq'] . '</changefreq>';
            if (!empty($url['priority']))  $lines[] = '    <priority>' . $url['priority'] . '</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    private function localizedUrl(string $baseUrl, string $path, string $locale): string
    {
        return $baseUrl . LocalizedUrl::path($path, $locale);
    }
}
