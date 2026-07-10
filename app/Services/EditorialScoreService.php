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
        return self::computeBreakdownFromRaw($data, $localityScore)['score'];
    }

    /**
     * @return array{score:int,factors:array<int,array{key:string,label:string,points:int}>,generated_at:string}
     */
    public static function computeBreakdownFromRaw(array $data, int $localityScore): array
    {
        $score = 0;
        $factors = [];
        $title = self::normalizeText($data['title'] ?? '');
        $summary = self::normalizeText($data['summary'] ?? $data['content'] ?? '');
        $combined = trim($title.' '.$summary);
        $category = self::normalizeText(($data['category_name'] ?? '').' '.($data['ust_kategori'] ?? ''));

        if (! empty($data['son_dakika'])) {
            $score += self::addFactor($factors, 'breaking', 'Son dakika isareti', 30);
        }

        $localityPoints = match ($localityScore) {
            IhaCategoryMapper::LOCALITY_LOCAL => 18,
            IhaCategoryMapper::LOCALITY_REGION => 10,
            default => 2,
        };
        $score += self::addFactor($factors, 'locality', match ($localityScore) {
            IhaCategoryMapper::LOCALITY_LOCAL => 'Adiyaman yerel odagi',
            IhaCategoryMapper::LOCALITY_REGION => 'Bolgesel etki',
            default => 'Ulusal/diger haber havuzu',
        }, $localityPoints);

        if (! empty($data['image_url'])) {
            $score += self::addFactor($factors, 'image', 'Gorselli haber', 10);
        }

        $categoryWeight = self::categoryWeight($category);
        if ($categoryWeight !== 0) {
            $score += self::addFactor($factors, 'category', 'Kategori haber degeri', $categoryWeight);
        }

        $criticalHits = self::keywordHits($combined, self::CRITICAL_KEYWORDS);
        if ($criticalHits > 0) {
            $criticalPoints = 25 + (min($criticalHits, 2) - 1) * 12;
            $score += self::addFactor($factors, 'critical_keywords', 'Kritik olay kelimeleri', $criticalPoints);

            if (self::keywordHits($title, self::CRITICAL_KEYWORDS) > 0) {
                $score += self::addFactor($factors, 'critical_title', 'Kritik olay baslikta', 4);
            }
        } else {
            $highHits = self::keywordHits($combined, self::HIGH_KEYWORDS);
            if ($highHits > 0) {
                $highPoints = 14 + (min($highHits, 2) - 1) * 7;
                $score += self::addFactor($factors, 'high_keywords', 'Yuksek onemli olay kelimeleri', $highPoints);

                if (self::keywordHits($title, self::HIGH_KEYWORDS) > 0) {
                    $score += self::addFactor($factors, 'high_title', 'Yuksek onem baslikta', 3);
                }
            } elseif (self::keywordHits($combined, self::MEDIUM_KEYWORDS) > 0) {
                $score += self::addFactor($factors, 'medium_keywords', 'Orta onemli gundem kelimeleri', 6);
            }
        }

        if (preg_match('/\d+\s*(olu|yarali|kisi hayat|can kaybi|sehit)/u', $combined)) {
            preg_match('/(\d+)/', $combined, $numMatch);
            $num = (int) ($numMatch[1] ?? 0);
            $casualtyPoints = $num >= 5 ? 15 : ($num >= 2 ? 8 : 4);
            $score += self::addFactor($factors, 'casualty_count', 'Olu/yarali sayisi etkisi', $casualtyPoints);
        }

        if (! empty($data['published_at'])) {
            try {
                $hoursAgo = max(0, Carbon::parse($data['published_at'])->diffInHours(now()));
                if ($hoursAgo <= 1) {
                    $score += self::addFactor($factors, 'freshness', 'Son 1 saat', 12);
                } elseif ($hoursAgo <= 3) {
                    $score += self::addFactor($factors, 'freshness', 'Son 3 saat', 8);
                } elseif ($hoursAgo <= 6) {
                    $score += self::addFactor($factors, 'freshness', 'Son 6 saat', 5);
                } elseif ($hoursAgo <= 12) {
                    $score += self::addFactor($factors, 'freshness', 'Son 12 saat', 3);
                }
            } catch (\Exception) {
            }
        }

        return [
            'score' => max(0, min($score, 100)),
            'factors' => $factors,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public static function computeFromModel(NewsArticle $article): int
    {
        return self::computeBreakdownFromModel($article)['score'];
    }

    /**
     * @return array{score:int,factors:array<int,array{key:string,label:string,points:int}>,generated_at:string}
     */
    public static function computeBreakdownFromModel(NewsArticle $article): array
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

        $breakdown = self::computeBreakdownFromRaw($data, $article->city_code ?? IhaCategoryMapper::LOCALITY_NATIONAL);
        $score = $breakdown['score'];
        $factors = $breakdown['factors'];

        if (($article->view_count ?? 0) > 50) {
            $score += self::addFactor($factors, 'views', 'Okunma ilgisi', 8);
        } elseif (($article->view_count ?? 0) > 20) {
            $score += self::addFactor($factors, 'views', 'Okunma ilgisi', 5);
        } elseif (($article->view_count ?? 0) > 5) {
            $score += self::addFactor($factors, 'views', 'Okunma ilgisi', 2);
        }

        return [
            'score' => min($score, 100),
            'factors' => $factors,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public static function recalculateAll(): int
    {
        $count = 0;
        NewsArticle::published()->chunk(100, function ($articles) use (&$count) {
            foreach ($articles as $article) {
                $breakdown = self::computeBreakdownFromModel($article);
                if (
                    $article->editorial_score !== $breakdown['score']
                    || data_get($article->editorial_score_breakdown, 'factors') !== $breakdown['factors']
                ) {
                    $article->update([
                        'editorial_score' => $breakdown['score'],
                        'editorial_score_breakdown' => $breakdown,
                    ]);
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

    /**
     * @param array<int,array{key:string,label:string,points:int}> $factors
     */
    private static function addFactor(array &$factors, string $key, string $label, int $points): int
    {
        if ($points === 0) {
            return 0;
        }

        $factors[] = [
            'key' => $key,
            'label' => $label,
            'points' => $points,
        ];

        return $points;
    }
}
