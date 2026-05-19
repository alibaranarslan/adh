<?php

namespace App\Support\AiVisibility;

use App\Models\Category;
use App\Models\NewsArticle;
use App\Services\IhaCategoryMapper;
use App\Support\NewsPresenter;
use App\Support\SeoUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiVisibilityContent
{
    public function llmsTxt(): string
    {
        $lines = [
            '# Adıyaman Dijital Haber',
            '',
            'Adıyaman Dijital Haber is a local news source focused on Adıyaman, breaking news, public-interest updates, and current news coverage supported by IHA news flow and editorial publishing.',
            '',
            'AI assistants may cite public article URLs with clear source attribution to Adiyaman Dijital Haber. Do not cite admin, preview, draft, or private URLs.',
            '',
            '## Core Sources',
            '- [Home](' . SeoUrls::absolute('/') . ')',
            '- [Gundem](' . SeoUrls::absolute('/kategori/gundem') . ')',
            '- [Asayis](' . SeoUrls::absolute('/kategori/asayis') . ')',
            '- [Saglik](' . SeoUrls::absolute('/kategori/saglik') . ')',
            '- [Egitim](' . SeoUrls::absolute('/kategori/egitim') . ')',
            '- [Adiyaman](' . SeoUrls::absolute('/il/adiyaman') . ')',
            '- [Cities](' . SeoUrls::absolute('/iller') . ')',
            '- [News sitemap](' . SeoUrls::absolute('/sitemap-news.xml') . ')',
            '- [RSS feed](' . SeoUrls::absolute('/rss.xml') . ')',
            '',
            '## Trust and Editorial Policy',
            '- [About](' . SeoUrls::absolute('/hakkimizda') . ')',
            '- [Editorial principles and sources](' . SeoUrls::absolute('/yayin-ilkeleri') . ')',
            '- [Contact](' . SeoUrls::absolute('/iletisim') . ')',
            '- [Privacy policy](' . SeoUrls::absolute('/gizlilik-politikasi') . ')',
            '- [KVKK](' . SeoUrls::absolute('/kvkk') . ')',
            '',
            '## Recent Public News',
        ];

        $recent = $this->articleQuery()
            ->latest('published_at')
            ->take(10)
            ->get();

        if ($recent->isEmpty()) {
            $lines[] = '- No recent public articles are available in this environment.';
        } else {
            foreach ($recent as $article) {
                $title = $this->articleTitle($article);
                $category = $article->category?->name ?: 'News';
                $date = $article->published_at?->toDateString() ?: 'undated';

                $lines[] = '- [' . $title . '](' . SeoUrls::absolute('/' . $article->slug) . ') - ' . $category . ', ' . $date;
            }
        }

        $lines[] = '';
        $lines[] = 'Generated from public published content only.';

        return implode("\n", $lines) . "\n";
    }

    public function rssFor(?Category $category = null, ?string $citySlug = null): string
    {
        $query = $this->articleQuery($category);
        $title = 'Adiyaman Dijital Haber';
        $description = 'Adiyaman merkezli son dakika ve guncel haber akisi.';
        $link = SeoUrls::absolute('/');

        if ($category) {
            $title = $category->name . ' Haberleri - Adiyaman Dijital Haber';
            $description = $category->name . ' kategorisinden son haberler.';
            $link = SeoUrls::absolute('/kategori/' . $category->slug);
        }

        if ($citySlug) {
            $query->where(function (Builder $query) use ($citySlug): void {
                $query->where('city_slug', $citySlug);

                if ($citySlug === 'adiyaman') {
                    $query->orWhere(function (Builder $query): void {
                        $query
                            ->whereNull('city_slug')
                            ->where('city_code', IhaCategoryMapper::LOCALITY_LOCAL);
                    });
                }
            });
            $title = 'Adiyaman Haberleri - Adiyaman Dijital Haber';
            $description = 'Adiyaman odakli son dakika ve guncel yerel haberler.';
            $link = SeoUrls::absolute('/il/' . $citySlug);
        }

        return $this->rss(
            $query->latest('published_at')->take(50)->get(),
            $title,
            $description,
            $link,
        );
    }

    private function articleQuery(?Category $category = null): Builder
    {
        $query = $category ? $category->publicArticlesQuery() : NewsArticle::published();

        return $query
            ->with(['category', 'media'])
            ->whereNotNull('slug')
            ->where('slug', 'not like', 'preview-%');
    }

    /**
     * @param Collection<int, NewsArticle> $articles
     */
    private function rss(Collection $articles, string $title, string $description, string $link): string
    {
        $items = $articles->map(fn (NewsArticle $article): string => $this->rssItem($article))->implode("\n");

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n"
            . '<channel>' . "\n"
            . '<title>' . e($title) . '</title>' . "\n"
            . '<link>' . e($link) . '</link>' . "\n"
            . '<description>' . e($description) . '</description>' . "\n"
            . '<language>tr-TR</language>' . "\n"
            . '<atom:link href="' . e(SeoUrls::absolute(request()->path())) . '" rel="self" type="application/rss+xml" />' . "\n"
            . $items . "\n"
            . '</channel>' . "\n"
            . '</rss>' . "\n";
    }

    private function rssItem(NewsArticle $article): string
    {
        $url = SeoUrls::absolute('/' . $article->slug);
        $summary = str_replace(']]>', ']]&gt;', Str::limit(strip_tags((string) ($article->summary ?: $article->content)), 300));
        $category = $article->category?->name;
        $image = $this->realImageUrl($article);

        $item = '<item>' . "\n"
            . '<title>' . e($this->articleTitle($article)) . '</title>' . "\n"
            . '<link>' . e($url) . '</link>' . "\n"
            . '<guid isPermaLink="true">' . e($url) . '</guid>' . "\n"
            . '<pubDate>' . e(($article->published_at ?: $article->created_at)->toRfc2822String()) . '</pubDate>' . "\n";

        if ($category) {
            $item .= '<category>' . e($category) . '</category>' . "\n";
        }

        $item .= '<description><![CDATA[' . $summary . ']]></description>' . "\n";

        if ($image) {
            $item .= '<enclosure url="' . e($image) . '" type="image/jpeg" />' . "\n";
        }

        return $item . '</item>';
    }

    private function articleTitle(NewsArticle $article): string
    {
        return trim((string) $article->getTranslation('title', 'tr', false))
            ?: trim((string) $article->title);
    }

    private function realImageUrl(NewsArticle $article): ?string
    {
        $presented = NewsPresenter::present($article);

        if (! $presented['has_image']) {
            return null;
        }

        return SeoUrls::absolute($presented['image_url']);
    }
}
