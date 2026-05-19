<?php

namespace App\Support;

use App\Models\NewsArticle;
use App\Models\Setting;
use Illuminate\Support\Str;

class SeoHealth
{
    public function snapshot(): array
    {
        $sitemapPath = public_path('sitemap.xml');
        $newsSitemapPath = public_path('sitemap-news.xml');
        $robots = (string) Setting::get('seo', 'robots_txt', '');
        $expectedSitemap = SeoUrls::absolute('/sitemap.xml');
        $effectiveRobots = $this->effectiveRobots($robots, $expectedSitemap);
        $recentArticles = NewsArticle::published()
            ->with('media')
            ->latest('published_at')
            ->take(20)
            ->get();

        $missingMeta = $recentArticles->filter(function (NewsArticle $article): bool {
            return blank($article->getTranslation('meta_description', 'tr', false))
                && blank($article->getTranslation('summary', 'tr', false));
        })->count();

        $missingImage = $recentArticles->filter(function (NewsArticle $article): bool {
            return ! NewsPresenter::present($article)['has_image'];
        })->count();

        return [
            'base_url' => SeoUrls::canonicalBaseUrl(),
            'https_ok' => str_starts_with(SeoUrls::canonicalBaseUrl(), 'https://'),
            'robots_points_to_sitemap' => Str::contains($effectiveRobots, 'Sitemap: ' . $expectedSitemap),
            'expected_sitemap' => $expectedSitemap,
            'sitemap_exists' => is_file($sitemapPath),
            'sitemap_updated_at' => is_file($sitemapPath) ? date('d.m.Y H:i', (int) filemtime($sitemapPath)) : null,
            'news_sitemap_exists' => is_file($newsSitemapPath),
            'news_sitemap_url_count' => $this->countUrls($newsSitemapPath),
            'recent_articles_checked' => $recentArticles->count(),
            'recent_missing_meta' => $missingMeta,
            'recent_missing_image' => $missingImage,
        ];
    }

    public function cards(): array
    {
        $snapshot = $this->snapshot();

        return [
            [
                'label' => 'Canonical HTTPS',
                'value' => $snapshot['https_ok'] ? 'Hazır' : 'Risk',
                'meta' => $snapshot['base_url'],
                'tone' => $snapshot['https_ok'] ? 'success' : 'danger',
            ],
            [
                'label' => 'Sitemap',
                'value' => $snapshot['sitemap_exists'] ? 'Var' : 'Eksik',
                'meta' => $snapshot['sitemap_updated_at'] ? 'Son üretim ' . $snapshot['sitemap_updated_at'] : 'Üretim bekleniyor',
                'tone' => $snapshot['sitemap_exists'] ? 'success' : 'warning',
            ],
            [
                'label' => 'News sitemap',
                'value' => number_format($snapshot['news_sitemap_url_count']),
                'meta' => 'Son 48 saat haber URL sayısı',
                'tone' => $snapshot['news_sitemap_exists'] ? 'success' : 'warning',
            ],
            [
                'label' => 'Son 20 haber',
                'value' => $snapshot['recent_missing_meta'] . ' meta / ' . $snapshot['recent_missing_image'] . ' görsel',
                'meta' => $snapshot['recent_articles_checked'] . ' haber kontrol edildi',
                'tone' => ($snapshot['recent_missing_meta'] + $snapshot['recent_missing_image']) > 0 ? 'warning' : 'success',
            ],
        ];
    }

    private function countUrls(string $path): int
    {
        if (! is_file($path)) {
            return 0;
        }

        return substr_count((string) file_get_contents($path), '<url>');
    }

    private function effectiveRobots(string $stored, string $sitemap): string
    {
        if ($stored === '') {
            return "User-agent: *\nAllow: /\nSitemap: {$sitemap}";
        }

        $content = preg_replace('#Sitemap:\s*https?://[^\s]+#i', 'Sitemap: ' . $sitemap, $stored);

        if (! preg_match('/^Sitemap:/im', $content)) {
            $content = rtrim($content) . "\nSitemap: {$sitemap}";
        }

        return $content;
    }
}
