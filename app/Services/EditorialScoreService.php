<?php

namespace App\Services;

use App\Models\NewsArticle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EditorialScoreService
{
    private const CRITICAL_KEYWORDS = [
        'deprem', 'sel', 'yangin', 'patlama', 'olu', 'olum', 'oldur', 'sehit',
        'katliam', 'zehirlen', 'teror', 'savas', 'tsunami', 'cig',
    ];

    private const HIGH_KEYWORDS = [
        'yarali', 'kaza', 'saldiri', 'operasyon', 'gozalti', 'tutuklama',
        'vali', 'bakan', 'cumhurbaskan', 'basbakan', 'milletvekili',
        'firtina', 'heyelan', 'ambulans', 'hastane', 'acil', 'trafik',
    ];

    private const MEDIUM_KEYWORDS = [
        'belediye baskan', 'belediye', 'meclis', 'secim', 'ihale', 'proje',
        'acilis', 'odul', 'sampiyon', 'rekor', 'festival', 'kurtaril',
        'arama', 'kayip',
    ];

    private const CATEGORY_WEIGHTS = [
        'asayis' => 12,
        'gundem' => 10,
        'saglik' => 9,
        'siyaset' => 8,
        'ekonomi' => 7,
        'egitim' => 6,
        'spor' => 4,
        'yasam' => 3,
        'magazin' => -4,
    ];

    public static function computeFromRaw(array $data, int $localityScore): int
    {
        $score = 0;
        $title = self::normalizeText($data['title'] ?? '');
        $summary = self::normalizeText($data['summary'] ?? $data['content'] ?? '');
        $combined = trim($title.' '.$summary);
        $category = self::normalizeText(($data['category_name'] ?? '').' '.($data['ust_kategori'] ?? ''));

        if (! empty($data['son_dakika'])) {
            $score += 30;
        }

        $score += match ($localityScore) {
            IhaCategoryMapper::LOCALITY_LOCAL => 18,
            IhaCategoryMapper::LOCALITY_REGION => 10,
            default => 2,
        };

        if (! empty($data['image_url'])) {
            $score += 10;
        }

        $score += self::categoryWeight($category);

        $criticalHits = self::keywordHits($combined, self::CRITICAL_KEYWORDS);
        if ($criticalHits > 0) {
            $score += 25 + (min($criticalHits, 2) - 1) * 12;
            $score += self::keywordHits($title, self::CRITICAL_KEYWORDS) > 0 ? 4 : 0;
        } else {
            $highHits = self::keywordHits($combined, self::HIGH_KEYWORDS);
            if ($highHits > 0) {
                $score += 14 + (min($highHits, 2) - 1) * 7;
                $score += self::keywordHits($title, self::HIGH_KEYWORDS) > 0 ? 3 : 0;
            } elseif (self::keywordHits($combined, self::MEDIUM_KEYWORDS) > 0) {
                $score += 6;
            }
        }

        if (preg_match('/\d+\s*(olu|yarali|kisi hayat|can kaybi|sehit)/u', $combined)) {
            preg_match('/(\d+)/', $combined, $numMatch);
            $num = (int) ($numMatch[1] ?? 0);
            $score += $num >= 5 ? 15 : ($num >= 2 ? 8 : 4);
        }

        if (! empty($data['published_at'])) {
            try {
                $hoursAgo = max(0, Carbon::parse($data['published_at'])->diffInHours(now()));
                if ($hoursAgo <= 1) {
                    $score += 12;
                } elseif ($hoursAgo <= 3) {
                    $score += 8;
                } elseif ($hoursAgo <= 6) {
                    $score += 5;
                } elseif ($hoursAgo <= 12) {
                    $score += 3;
                }
            } catch (\Exception) {
            }
        }

        return max(0, min($score, 100));
    }

    public static function computeFromModel(NewsArticle $article): int
    {
        $data = [
            'title' => $article->getTranslation('title', 'tr', false) ?: $article->title,
            'summary' => $article->getTranslation('summary', 'tr', false) ?: ($article->summary ?? ''),
            'son_dakika' => $article->is_breaking,
            'image_url' => $article->featured_image,
            'published_at' => $article->published_at?->toDateTimeString(),
            'category_name' => $article->category?->slug
                ?? $article->category?->getTranslation('name', 'tr', false)
                ?? '',
        ];

        $score = self::computeFromRaw($data, $article->city_code ?? IhaCategoryMapper::LOCALITY_NATIONAL);

        if (($article->view_count ?? 0) > 50) {
            $score += 8;
        } elseif (($article->view_count ?? 0) > 20) {
            $score += 5;
        } elseif (($article->view_count ?? 0) > 5) {
            $score += 2;
        }

        return min($score, 100);
    }

    public static function recalculateAll(): int
    {
        $count = 0;
        NewsArticle::published()->chunk(100, function ($articles) use (&$count) {
            foreach ($articles as $article) {
                $newScore = self::computeFromModel($article);
                if ($article->editorial_score !== $newScore) {
                    $article->update(['editorial_score' => $newScore]);
                }
                $count++;
            }
        });

        return $count;
    }

    private static function normalizeText(?string $value): string
    {
        return Str::of((string) $value)->ascii()->lower()->toString();
    }

    /**
     * @param array<int, string> $keywords
     */
    private static function keywordHits(string $text, array $keywords): int
    {
        $hits = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $hits++;
            }
        }

        return $hits;
    }

    private static function categoryWeight(string $category): int
    {
        foreach (self::CATEGORY_WEIGHTS as $needle => $weight) {
            if (str_contains($category, $needle)) {
                return $weight;
            }
        }

        return 0;
    }
}
