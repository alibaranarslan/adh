<?php

return [

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'release' => env('SENTRY_RELEASE'),

    'environment' => env('APP_ENV', 'production'),

    'sample_rate' => env('SENTRY_SAMPLE_RATE', 1.0),

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    'before_send' => [App\Support\SentryFilters::class, 'beforeSend'],

    'tags' => [
        'app' => 'adh',
    ],

];
