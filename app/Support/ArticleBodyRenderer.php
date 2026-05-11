<?php

namespace App\Support;

use App\Models\NewsArticle;

class ArticleBodyRenderer
{
    public static function render(NewsArticle $article, string $content): string
    {
        if ($article->isFromIha()) {
            return self::renderUntrustedText($content);
        }

        return strip_tags($content) === $content
            ? nl2br(e($content))
            : $content;
    }

    private static function renderUntrustedText(string $content): string
    {
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $content) ?? $content;
        $text = preg_replace('#<\s*br\s*/?>#i', "\n", $text) ?? $text;
        $text = preg_replace('#</\s*p\s*>#i', "\n\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return nl2br(e(trim($text)));
    }
}
