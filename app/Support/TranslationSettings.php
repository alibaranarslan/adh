<?php

namespace App\Support;

use App\Models\Setting;

class TranslationSettings
{
    public static function apiKey(): string
    {
        $settingValue = Setting::get('integration', 'google_translate_api_key');

        return (string) (filled($settingValue)
            ? $settingValue
            : config('services.google_translate.api_key', ''));
    }

    public static function ready(): bool
    {
        return filled(self::apiKey());
    }
}
