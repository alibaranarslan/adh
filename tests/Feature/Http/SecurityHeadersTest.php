<?php

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_web_responses_include_security_headers(): void
    {
        Route::middleware('web')->prefix('test-probes')->get('/security-header-probe', fn () => response('ok'));

        $response = $this->get('/test-probes/security-header-probe');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_https_responses_include_hsts(): void
    {
        Route::middleware('web')->prefix('test-probes')->get('/security-header-secure', fn () => response('ok'));

        config(['app.url' => 'https://adh.test']);

        $response = $this->withServerVariables(['HTTPS' => 'on'])->get('/test-probes/security-header-secure');

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    }
}
