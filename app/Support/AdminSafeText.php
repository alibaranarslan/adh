<?php

namespace App\Support;

use Illuminate\Support\Str;

class AdminSafeText
{
    public static function redact(?string $message): string
    {
        $safe = trim((string) $message);

        if ($safe === '') {
            return '';
        }

        $safe = preg_replace('/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $safe) ?? $safe;
        $safe = preg_replace(
            '/([?&](?:password|pass|pwd|sifre|şifre|token|access_token|api_key|apikey|key|secret|username|user)=)[^&\s]+/iu',
            '$1[redacted]',
            $safe,
        ) ?? $safe;
        $safe = preg_replace(
            '/\b(password|pass|pwd|sifre|şifre|token|access_token|api_key|apikey|secret|authorization|iha_password|iha_username|iha_user_code)\b\s*[:=]\s*([^\s,;]+)/iu',
            '$1=[redacted]',
            $safe,
        ) ?? $safe;

        return trim($safe);
    }

    public static function limit(?string $message, int $limit = 140): string
    {
        return Str::limit(self::redact($message), $limit);
    }
}
