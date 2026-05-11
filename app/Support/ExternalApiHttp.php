<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ExternalApiHttp
{
    public static function json(?bool $verifySsl = null): PendingRequest
    {
        return self::base($verifySsl)
            ->acceptJson();
    }

    public static function html(?bool $verifySsl = null): PendingRequest
    {
        return self::base($verifySsl)
            ->accept('text/html,application/xhtml+xml');
    }

    private static function base(?bool $verifySsl = null): PendingRequest
    {
        $options = [
            'proxy' => [
                'http' => null,
                'https' => null,
                'no' => ['*'],
            ],
            'curl' => [],
        ];

        if (defined('CURLOPT_PROXY')) {
            $options['curl'][CURLOPT_PROXY] = '';
        }

        if (defined('CURLOPT_NOPROXY')) {
            $options['curl'][CURLOPT_NOPROXY] = '*';
        }

        if ($verifySsl === false) {
            $options['verify'] = false;
        }

        return Http::withHeaders([
            'User-Agent' => 'ADH/1.0 (+https://adiyamandijitalhaber.com.tr)',
        ])->timeout(12)
            ->connectTimeout(5)
            ->retry(2, 500, null, false)
            ->withOptions($options);
    }
}
