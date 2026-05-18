<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class NewsPresenter
{
    public static function present(mixed $article, string $imageConversion = 'medium'): array
    {
        $locale = app()->getLocale();
        $publishedAt = self::resolveDate(data_get($article, 'published_at'));
        $updatedAt = self::resolveDate(data_get($article, 'updated_at'));
        $readTime = max(1, (int) data_get($article, 'read_time', 1));

        $hasImage = self::hasArticleImage($article);

        return [
            'url' => filled(data_get($article, 'slug'))
                ? LocalizedUrl::route('news.show', ['slug' => data_get($article, 'slug')], $locale)
                : '#',
            'title' => (string) data_get($article, 'title', ''),
            'summary' => trim((string) data_get($article, 'summary', '')),
            'image_url' => self::imageUrl($article, $imageConversion),
            'has_image' => $hasImage,
            'uses_placeholder_image' => ! $hasImage,
            'category_name' => data_get($article, 'category.name') ?: data_get($article, 'category'),
            'published_at' => $publishedAt,
            'published_iso' => $publishedAt?->toIso8601String(),
            'published_label' => $publishedAt?->locale($locale)->isoFormat('D MMMM YYYY, HH:mm'),
            'published_short_label' => $publishedAt?->locale($locale)->isoFormat('D MMM, HH:mm'),
            'updated_at' => $updatedAt,
            'updated_iso' => $updatedAt?->toIso8601String(),
            'updated_label' => self::updatedLabel($updatedAt, $publishedAt),
            'freshness_label' => self::freshnessLabel($publishedAt),
            'source_label' => self::sourceLabel((string) data_get($article, 'source', '')),
            'author_name' => self::authorName($article),
            'read_time' => $readTime,
            'read_time_label' => __(':count dk okuma', ['count' => $readTime]),
            'views_label' => self::viewsLabel((int) data_get($article, 'view_count', 0)),
        ];
    }

    private static function imageUrl(mixed $article, string $imageConversion): string
    {
        $image = null;

        if (is_object($article) && method_exists($article, 'getFirstMediaUrl')) {
            $image = $article->getFirstMediaUrl('featured_image', $imageConversion) ?: data_get($article, 'featured_image');
        }

        $image = $image
            ?: data_get($article, 'featured_image')
            ?: data_get($article, 'image');

        return self::isPlaceholderImage($image)
            ? asset('images/news/placeholder-news.jpg')
            : ($image ?: asset('images/news/placeholder-news.jpg'));
    }

    private static function hasArticleImage(mixed $article): bool
    {
        if (is_object($article) && method_exists($article, 'getFirstMediaUrl')) {
            $mediaUrl = $article->getFirstMediaUrl('featured_image');

            if (filled($mediaUrl) && ! self::isPlaceholderImage($mediaUrl)) {
                return true;
            }
        }

        $featuredImage = data_get($article, 'featured_image');
        $image = data_get($article, 'image');

        return (filled($featuredImage) && ! self::isPlaceholderImage($featuredImage))
            || (filled($image) && ! self::isPlaceholderImage($image));
    }

    public static function isPlaceholderImage(mixed $image): bool
    {
        if (blank($image)) {
            return false;
        }

        $normalized = str_replace('\\', '/', mb_strtolower((string) $image));

        return str_contains($normalized, 'placeholder-news.jpg')
            || str_contains($normalized, 'images/news/placeholder');
    }

    private static function resolveDate(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value);
    }

    private static function freshnessLabel(?CarbonInterface $publishedAt): ?string
    {
        if (! $publishedAt) {
            return null;
        }

        if ($publishedAt->greaterThanOrEqualTo(now()->subHour())) {
            return __('Son Dakika');
        }

        if ($publishedAt->greaterThanOrEqualTo(now()->subHours(6))) {
            return __('Sıcak Gelişme');
        }

        if ($publishedAt->isToday()) {
            return __('Bugün');
        }

        return null;
    }

    private static function sourceLabel(string $source): ?string
    {
        return match (strtolower(trim($source))) {
            'iha' => __('İHA'),
            'manuel', 'manual' => __('Editör Girişi'),
            '' => null,
            default => mb_strtoupper($source),
        };
    }

    private static function viewsLabel(int $viewCount): ?string
    {
        if ($viewCount <= 0) {
            return null;
        }

        return __(':count görüntüleme', ['count' => number_format($viewCount, 0, ',', '.')]);
    }

    private static function updatedLabel(?CarbonInterface $updatedAt, ?CarbonInterface $publishedAt): ?string
    {
        if (! $updatedAt) {
            return null;
        }

        if ($publishedAt && $updatedAt->lessThanOrEqualTo($publishedAt->copy()->addMinute())) {
            return null;
        }

        return __('Güncellendi: :date', [
            'date' => $updatedAt->locale(app()->getLocale())->isoFormat('D MMMM YYYY, HH:mm'),
        ]);
    }

    private static function authorName(mixed $article): ?string
    {
        $authorName = trim((string) data_get($article, 'author.name', ''));

        return $authorName !== '' ? $authorName : null;
    }
}
