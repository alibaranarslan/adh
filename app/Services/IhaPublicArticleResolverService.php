<?php

namespace App\Services;

use App\Models\NewsArticle;
use App\Support\ExternalApiHttp;
use Carbon\Carbon;
use Illuminate\Support\Str;

class IhaPublicArticleResolverService
{
    public function resolve(NewsArticle $article): ?array
    {
        $title = trim((string) ($article->getTranslation('title', 'tr', false) ?: $article->title));

        if ($title === '' || ! $article->published_at) {
            return null;
        }

        foreach ($this->searchPublicUrls($title) as $url) {
            $candidate = $this->fetchPublicArticle($url);

            if (! $candidate || ! $this->matchesArticle($article, $candidate)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    public function resolveFromUrl(NewsArticle $article, string $url): ?array
    {
        $candidate = $this->fetchPublicArticle($url);

        if (! $candidate || ! $this->matchesArticle($article, $candidate)) {
            return null;
        }

        return $candidate;
    }

    /**
     * @return list<string>
     */
    public function searchPublicUrls(string $title): array
    {
        $query = sprintf('site:iha.com.tr "%s"', trim($title));
        $response = ExternalApiHttp::html()->get('https://html.duckduckgo.com/html/', [
            'q' => $query,
        ]);

        if (! $response->successful()) {
            return [];
        }

        preg_match_all('/uddg=([^&"]+)/', $response->body(), $matches);

        $urls = collect($matches[1] ?? [])
            ->map(static fn (string $encoded) => urldecode($encoded))
            ->filter(function (string $url) {
                if (! str_starts_with($url, 'https://www.iha.com.tr/')) {
                    return false;
                }

                return ! str_contains($url, '/arama');
            })
            ->unique()
            ->values()
            ->all();

        return array_values($urls);
    }

    public function fetchPublicArticle(string $url): ?array
    {
        $response = ExternalApiHttp::html()->get($url);

        if (! $response->successful()) {
            return null;
        }

        return $this->extractArticleDataFromHtml($response->body(), $url);
    }

    public function extractArticleDataFromHtml(string $html, string $url): ?array
    {
        preg_match_all('/<script type="application\/ld\+json">(?<json>.*?)<\/script>/is', $html, $matches);

        foreach ($matches['json'] ?? [] as $jsonBlock) {
            $sanitizedJson = $this->sanitizeJsonLd($jsonBlock);
            $decoded = json_decode($sanitizedJson, true);

            if ($decoded === null) {
                $fallbackCandidate = $this->extractArticleCandidateByRegex($sanitizedJson, $url);

                if ($fallbackCandidate !== null) {
                    return $fallbackCandidate;
                }

                continue;
            }

            foreach ($this->extractArticleCandidates($decoded) as $candidate) {
                $headline = $this->normalizeWhitespace((string) ($candidate['headline'] ?? ''));
                $body = $this->normalizeWhitespace((string) ($candidate['articleBody'] ?? ''));
                $publishedAt = trim((string) ($candidate['datePublished'] ?? ''));
                $description = $this->normalizeWhitespace((string) ($candidate['description'] ?? ''));

                if ($headline === '' || $body === '' || $publishedAt === '') {
                    continue;
                }

                return [
                    'url' => $url,
                    'headline' => $headline,
                    'content' => $body,
                    'description' => $description,
                    'published_at' => $publishedAt,
                ];
            }
        }

        return null;
    }

    private function matchesArticle(NewsArticle $article, array $candidate): bool
    {
        $expectedTitle = trim((string) ($article->getTranslation('title', 'tr', false) ?: $article->title));
        $resolvedTitle = trim((string) ($candidate['headline'] ?? ''));

        if ($this->normalizeComparisonText($expectedTitle) !== $this->normalizeComparisonText($resolvedTitle)) {
            return false;
        }

        try {
            $expectedPublishedAt = $article->published_at->copy()->timezone('Europe/Istanbul');
            $resolvedPublishedAt = Carbon::parse($candidate['published_at'])->timezone('Europe/Istanbul');
        } catch (\Throwable) {
            return false;
        }

        return $expectedPublishedAt->diffInSeconds($resolvedPublishedAt) <= 300;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractArticleCandidates(mixed $node): array
    {
        $candidates = [];

        if (is_array($node)) {
            if ($this->isArticleCandidate($node)) {
                $candidates[] = $node;
            }

            foreach ($node as $value) {
                foreach ($this->extractArticleCandidates($value) as $childCandidate) {
                    $candidates[] = $childCandidate;
                }
            }
        }

        return $candidates;
    }

    private function isArticleCandidate(array $candidate): bool
    {
        return array_key_exists('headline', $candidate)
            && array_key_exists('articleBody', $candidate)
            && array_key_exists('datePublished', $candidate);
    }

    private function normalizeWhitespace(string $value): string
    {
        $decoded = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $decoded));
    }

    private function normalizeComparisonText(string $value): string
    {
        $ascii = Str::ascii(Str::lower($this->normalizeWhitespace($value)));

        return preg_replace('/[^a-z0-9]+/', '', $ascii) ?? $ascii;
    }

    private function sanitizeJsonLd(string $jsonBlock): string
    {
        $decoded = html_entity_decode(trim($jsonBlock), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $decoded) ?? $decoded;
    }

    private function extractArticleCandidateByRegex(string $jsonBlock, string $url): ?array
    {
        $headline = $this->extractFieldBetween($jsonBlock, 'headline', 'alternativeHeadline')
            ?? $this->extractLooseField($jsonBlock, 'headline');
        $description = $this->extractFieldBetween($jsonBlock, 'description', 'articleBody')
            ?? $this->extractLooseField($jsonBlock, 'description');
        $body = $this->extractFieldBetween($jsonBlock, 'articleBody', 'image')
            ?? $this->extractFieldBetween($jsonBlock, 'articleBody', 'author')
            ?? $this->extractLooseField($jsonBlock, 'articleBody');
        $publishedAt = $this->extractLooseField($jsonBlock, 'datePublished');

        if ($headline === null || $body === null || $publishedAt === null) {
            return null;
        }

        $normalizedHeadline = $this->normalizeWhitespace($headline);
        $normalizedBody = $this->normalizeWhitespace($body);
        $normalizedPublishedAt = $this->normalizeWhitespace($publishedAt);
        $normalizedDescription = $this->normalizeWhitespace($description ?? '');

        if ($normalizedHeadline === '' || $normalizedBody === '' || $normalizedPublishedAt === '') {
            return null;
        }

        return [
            'url' => $url,
            'headline' => $normalizedHeadline,
            'content' => $normalizedBody,
            'description' => $normalizedDescription,
            'published_at' => $normalizedPublishedAt,
        ];
    }

    private function extractFieldBetween(string $jsonBlock, string $field, string $nextField): ?string
    {
        $pattern = sprintf('/"%s"\s*:\s*"(?<value>.*?)",\s*"%s"/su', preg_quote($field, '/'), preg_quote($nextField, '/'));

        if (! preg_match($pattern, $jsonBlock, $matches)) {
            return null;
        }

        return stripcslashes((string) ($matches['value'] ?? ''));
    }

    private function extractLooseField(string $jsonBlock, string $field): ?string
    {
        $pattern = sprintf('/"%s"\s*:\s*"(?<value>(?:[^"\\\\]|\\\\.)*)"/su', preg_quote($field, '/'));

        if (! preg_match($pattern, $jsonBlock, $matches)) {
            return null;
        }

        return stripcslashes((string) ($matches['value'] ?? ''));
    }
}
