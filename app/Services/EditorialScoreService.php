<?php

namespace App\Services;

use App\Models\NewsArticle;
use Illuminate\Support\Carbon;

class EditorialScoreService
{
    private const CRITICAL_KEYWORDS = [
        'deprem', 'sel', 'yangın', 'patlama', 'ölü', 'ölüm', 'öldü',
        'savaş', 'terör', 'tsunami', 'çığ',
    ];

    private const HIGH_KEYWORDS = [
        'yaralı', 'kaza', 'saldırı', 'operasyon', 'gözaltı', 'tutuklama',
        'vali', 'bakan', 'cumhurbaşkan', 'başbakan', 'milletvekili',
        'fırtına', 'heyelan', 'ambulans', 'hastane', 'acil',
    ];

    private const MEDIUM_KEYWORDS = [
        'belediye başkan', 'meclis', 'seçim', 'ihale', 'proje',
        'açılış', 'ödül', 'şampiyon', 'rekor', 'festival',
        'kurtarıl', 'arama', 'kayıp',
    ];

    public static function computeFromRaw(array $data, int $localityScore): int
    {
        $score = 0;
        $title = mb_strtolower($data['title'] ?? '');
        $summary = mb_strtolower($data['summary'] ?? $data['content'] ?? '');

        if (!empty($data['son_dakika'])) {
            $score += 35;
        }

        if ($localityScore === 3) {
            $score += 15;
        } elseif ($localityScore === 2) {
            $score += 8;
        }

        if (!empty($data['image_url'])) {
            $score += 10;
        }

        $criticalHits = 0;
        foreach (self::CRITICAL_KEYWORDS as $kw) {
            if (mb_strpos($title, $kw) !== false) {
                $score += ($criticalHits === 0) ? 20 : 10;
                $criticalHits++;
                if ($criticalHits >= 2) break;
            }
        }

        if ($criticalHits === 0) {
            $highHits = 0;
            foreach (self::HIGH_KEYWORDS as $kw) {
                if (mb_strpos($title, $kw) !== false) {
                    $score += ($highHits === 0) ? 12 : 6;
                    $highHits++;
                    if ($highHits >= 2) break;
                }
            }

            if ($highHits === 0) {
                foreach (self::MEDIUM_KEYWORDS as $kw) {
                    if (mb_strpos($title, $kw) !== false) {
                        $score += 5;
                        break;
                    }
                }
            }
        }

        if (preg_match('/\d+\s*(ölü|yaralı|kişi hayat|can kaybı)/u', $title, $m)) {
            preg_match('/(\d+)/', $title, $numMatch);
            $num = (int) ($numMatch[1] ?? 0);
            if ($num >= 5) {
                $score += 15;
            } elseif ($num >= 2) {
                $score += 8;
            } else {
                $score += 4;
            }
        }

        if (!empty($data['published_at'])) {
            try {
                $pub = Carbon::parse($data['published_at']);
                $hoursAgo = max(0, $pub->diffInHours(now()));
                if ($hoursAgo <= 1) {
                    $score += 10;
                } elseif ($hoursAgo <= 3) {
                    $score += 7;
                } elseif ($hoursAgo <= 6) {
                    $score += 4;
                } elseif ($hoursAgo <= 12) {
                    $score += 2;
                }
            } catch (\Exception) {
            }
        }

        return min($score, 100);
    }

    public static function computeFromModel(NewsArticle $article): int
    {
        $data = [
            'title'        => $article->getTranslation('title', 'tr', false) ?: $article->title,
            'summary'      => $article->getTranslation('summary', 'tr', false) ?: ($article->summary ?? ''),
            'son_dakika'   => $article->is_breaking,
            'image_url'    => $article->featured_image,
            'published_at' => $article->published_at?->toDateTimeString(),
        ];

        $localityScore = $article->city_code ?? 1;
        $score = self::computeFromRaw($data, $localityScore);

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
}
