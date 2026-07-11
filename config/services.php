<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_translate' => [
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
        'target_language_map' => [
            'en' => env('GOOGLE_TRANSLATE_TARGET_EN', 'en'),
            'ku' => env('GOOGLE_TRANSLATE_TARGET_KU', 'ku'),
        ],
    ],

    // ── IHA Haber Ajansı ────────────────────────────────────────────────────
    'iha' => [
        'base_url'        => env('IHA_API_URL', env('IHA_BASE_URL', 'https://abonerss.iha.com.tr/xml/standartrss')),
        'user_code'       => env('IHA_USER_CODE'),
        'username'        => env('IHA_USERNAME'),
        'password'        => env('IHA_PASSWORD'),
        'sync_interval'   => env('IHA_SYNC_INTERVAL', 15),
        'stale_running_minutes' => env('IHA_STALE_RUNNING_MINUTES', 20),
        'request_delay'   => env('IHA_REQUEST_DELAY', 1),
        'verify_ssl'      => env('IHA_VERIFY_SSL', true),
        'retry_attempts'  => env('IHA_RETRY_ATTEMPTS', 3),
        'retry_delay'     => env('IHA_RETRY_DELAY', 60),
        'image_disk'      => env('IHA_IMAGE_DISK', 'public'),
        'image_path'      => env('IHA_IMAGE_PATH', 'news-images'),
        'min_body_length' => env('IHA_MIN_BODY_LENGTH', 280),
    ],

    // ── Hava Durumu (Open-Meteo — API key gerektirmez) ──────────────────────
    'weather' => [
        'provider'      => env('WEATHER_PROVIDER', 'open-meteo'),
        'api_key'       => env('OPENWEATHER_API_KEY', ''),
        'city'          => env('WEATHER_CITY', 'Adiyaman'),
        'latitude'      => env('WEATHER_LAT', '37.76'),
        'longitude'     => env('WEATHER_LON', '38.28'),
        'cache_minutes' => env('WEATHER_CACHE_MINUTES', 30),
    ],

    // ── Nöbetçi Eczane (NosyAPI) ─────────────────────────────────────────────
    'pharmacy' => [
        'api_url'     => env('PHARMACY_API_URL', 'https://api.nosyapi.com/apiv2/pharmacy'),
        'api_key'     => env('PHARMACY_API_KEY', ''),
        'city'        => env('PHARMACY_CITY', 'adiyaman'),
        'cache_hours' => env('PHARMACY_CACHE_HOURS', 24),
    ],

    // ── Namaz Vakitleri (Aladhan — API key gerektirmez) ──────────────────────
    'prayer_times' => [
        'provider'    => env('PRAYER_PROVIDER', 'aladhan'),
        'latitude'    => env('PRAYER_LAT', '37.76'),
        'longitude'   => env('PRAYER_LON', '38.28'),
        'method'      => env('PRAYER_METHOD', 13), // 13 = Diyanet method
        'cache_hours' => env('PRAYER_CACHE_HOURS', 24),
    ],

    // ── Instagram Graph API ───────────────────────────────────────────────────
    'instagram' => [
        'access_token'        => env('INSTAGRAM_ACCESS_TOKEN', ''),
        'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID', ''),
        'facebook_page_id'    => env('FACEBOOK_PAGE_ID', ''),
        'enabled'             => env('INSTAGRAM_ENABLED', false),
        'graph_version'       => env('INSTAGRAM_GRAPH_VERSION', 'v24.0'),
        'graph_url'           => 'https://graph.facebook.com',
    ],

    // ── Google AdSense ───────────────────────────────────────────────────────
    'adsense' => [
        'client_id' => env('ADSENSE_CLIENT_ID', ''),
        'enabled'   => env('ADSENSE_ENABLED', false),
    ],

    'google_analytics' => [
        'measurement_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

];
