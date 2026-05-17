<?php

namespace App\Services;

use App\Support\TranslationSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    public function translate(string $text, string $targetLang, string $sourceLang = 'tr'): ?string
    {
        if (trim($text) === '') {
            return $text;
        }

        $providerTargetLang = $this->providerTargetLanguage($targetLang);
        $cacheKey = 'translation_' . md5($text . $providerTargetLang . $sourceLang);

        if (($cached = Cache::get($cacheKey)) !== null) {
            return $cached;
        }

        try {
            $apiKey = TranslationSettings::apiKey();

            if (empty($apiKey)) {
                Log::warning('Google Translate API key not configured', [
                    'target' => $targetLang,
                    'provider_target' => $providerTargetLang,
                ]);

                return null;
            }

            $response = Http::post('https://translation.googleapis.com/language/translate/v2', [
                'q' => $text,
                'target' => $providerTargetLang,
                'source' => $sourceLang,
                'key' => $apiKey,
                'format' => 'html',
            ]);

            if ($response->successful()) {
                $translated = $response->json('data.translations.0.translatedText');

                if (is_string($translated) && trim($translated) !== '') {
                    Cache::put($cacheKey, $translated, now()->addDays(30));

                    return $translated;
                }
            }

            Log::error('Translation API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'target' => $targetLang,
                'provider_target' => $providerTargetLang,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Translation failed', [
                'error' => $e->getMessage(),
                'target' => $targetLang,
                'provider_target' => $providerTargetLang,
            ]);

            return null;
        }
    }

    /**
     * @return array{translated:int, failed:int, skipped:int}
     */
    public function translateModel($model, array $fields, array $targetLangs = ['en', 'ku'], bool $force = false): array
    {
        $stats = [
            'translated' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($fields as $field) {
            $turkishValue = $model->getTranslation($field, 'tr', false);

            if (blank($turkishValue)) {
                $stats['skipped']++;

                continue;
            }

            foreach ($targetLangs as $lang) {
                $existing = $model->getTranslation($field, $lang, false);

                if (! $force && filled($existing)) {
                    $stats['skipped']++;

                    continue;
                }

                $translated = $this->translate((string) $turkishValue, $lang);

                if (filled($translated)) {
                    $model->setTranslation($field, $lang, $translated);
                    $stats['translated']++;
                } else {
                    $stats['failed']++;
                }
            }
        }

        if ($model->isDirty()) {
            $model->saveQuietly();
        }

        return $stats;
    }

    private function providerTargetLanguage(string $targetLang): string
    {
        return (string) config("services.google_translate.target_language_map.{$targetLang}", $targetLang);
    }
}
