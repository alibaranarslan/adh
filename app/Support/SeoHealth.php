<?php

namespace App\Support;

use App\Models\NewsArticle;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SeoHealth
{
    public function snapshot(): array
    {
        $sitemapPath = public_path('sitemap.xml');
        $newsSitemapPath = public_path('sitemap-news.xml');
        $robots = (string) Setting::get('seo', 'robots_txt', '');
        $expectedSitemap = SeoUrls::absolute('/sitemap.xml');
        $effectiveRobots = RobotsTxt::render($robots);
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
            'expected_news_sitemap' => SeoUrls::absolute('/sitemap-news.xml'),
            'expected_rss' => SeoUrls::absolute('/rss.xml'),
            'sitemap_exists' => is_file($sitemapPath),
            'sitemap_updated_at' => is_file($sitemapPath) ? date('d.m.Y H:i', (int) filemtime($sitemapPath)) : null,
            'news_sitemap_exists' => is_file($newsSitemapPath),
            'news_sitemap_url_count' => $this->countUrls($newsSitemapPath),
            'llms_txt_available' => Route::has('llms'),
            'rss_available' => Route::has('feeds.rss'),
            'oai_searchbot_allowed' => RobotsTxt::botAllowed($effectiveRobots, 'OAI-SearchBot'),
            'chatgpt_user_allowed' => RobotsTxt::botAllowed($effectiveRobots, 'ChatGPT-User'),
            'llms_recent_articles_available' => $recentArticles->isNotEmpty(),
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
                'label' => 'AI crawl',
                'value' => ($snapshot['oai_searchbot_allowed'] && $snapshot['chatgpt_user_allowed']) ? 'Hazır' : 'Risk',
                'meta' => 'OAI-SearchBot / ChatGPT-User robots signal',
                'tone' => ($snapshot['oai_searchbot_allowed'] && $snapshot['chatgpt_user_allowed']) ? 'success' : 'danger',
            ],
            [
                'label' => 'Feed / llms.txt',
                'value' => ($snapshot['rss_available'] && $snapshot['llms_txt_available']) ? 'Hazır' : 'Risk',
                'meta' => 'RSS and machine-readable source map',
                'tone' => ($snapshot['rss_available'] && $snapshot['llms_txt_available']) ? 'success' : 'warning',
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

}
