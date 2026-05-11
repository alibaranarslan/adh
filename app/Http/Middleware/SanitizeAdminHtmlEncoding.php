<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeAdminHtmlEncoding
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! str_contains((string) $response->headers->get('content-type'), 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return $response;
        }

        $response->setContent(str_replace($this->replacements(), $this->replacementsTo(), $content));

        return $response;
    }

    /**
     * @return array<int, string>
     */
    private function replacements(): array
    {
        return [
            'Ä±',
            'Ä°',
            'ÄŸ',
            'Äž',
            'Ã¼',
            'Ãœ',
            'Ã¶',
            'Ã–',
            'Ã§',
            'Ã‡',
            'ÅŸ',
            'Åž',
            'â€™',
            'â€“',
            'â€”',
            'â€¦',
            'â‚º',
            'Â ',
            'Â',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function replacementsTo(): array
    {
        return [
            'ı',
            'İ',
            'ğ',
            'Ğ',
            'ü',
            'Ü',
            'ö',
            'Ö',
            'ç',
            'Ç',
            'ş',
            'Ş',
            '’',
            '–',
            '—',
            '…',
            '₺',
            ' ',
            '',
        ];
    }
}
