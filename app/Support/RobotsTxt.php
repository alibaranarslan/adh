<?php

namespace App\Support;

use Illuminate\Support\Str;

class RobotsTxt
{
    /**
     * @return array<int, string>
     */
    public static function sitemapUrls(): array
    {
        return [
            SeoUrls::absolute('/sitemap.xml'),
            SeoUrls::absolute('/sitemap-news.xml'),
            SeoUrls::absolute('/rss.xml'),
        ];
    }

    public static function render(?string $stored): string
    {
        $content = trim((string) $stored);

        if ($content === '') {
            $content = "User-agent: *\nAllow: /";
        }

        $content = self::ensureBotAllowed($content, 'OAI-SearchBot');
        $content = self::ensureBotAllowed($content, 'ChatGPT-User');

        $content = preg_replace('#^Sitemap:\s*https?://[^\s]+#im', '', $content) ?? $content;
        $content = trim((string) preg_replace("/\n{3,}/", "\n\n", $content));

        foreach (self::sitemapUrls() as $url) {
            $content .= "\nSitemap: {$url}";
        }

        return trim($content) . "\n";
    }

    public static function botAllowed(string $robots, string $bot): bool
    {
        $pattern = '/User-agent:\s*' . preg_quote($bot, '/') . '\s*\RAllow:\s*\/(?:\R|$)/i';

        return preg_match($pattern, $robots) === 1;
    }

    private static function ensureBotAllowed(string $content, string $bot): string
    {
        if (Str::contains(Str::lower($content), 'user-agent: ' . Str::lower($bot))) {
            return $content;
        }

        return rtrim($content) . "\n\nUser-agent: {$bot}\nAllow: /";
    }
}
