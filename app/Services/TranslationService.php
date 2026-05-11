<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Google Cloud Translation API ile çeviri yapar.
     * Çeviri kimliği eksikse veya servis hata verirse null döner; public yüzey fallback ile devam eder.
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'tr'): ?string
    {
        if (empty(trim($text))) {
            return $text;
        }

        $cacheKey = 'translation_' . md5($text . $targetLang);

        return Cache::remember($cacheKey, 86400 * 30, function () use ($text, $targetLang, $sourceLang) {
            try {
                $apiKey = config('services.google_translate.api_key');

                if (empty($apiKey)) {
                    Log::warning('Google Translate API key not configured');
                    return null;
                }

                $response = Http::post('https://translation.googleapis.com/language/translate/v2', [
                    'q' => $text,
                    'target' => $targetLang,
                    'source' => $sourceLang,
                    'key' => $apiKey,
                    'format' => 'html',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data']['translations'][0]['translatedText'] ?? null;
                }

                Log::error('Translation API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Translation failed', [
                    'error' => $e->getMessage(),
                    'target' => $targetLang,
                ]);
                return null;
            }
        });
    }

    /**
     * Bir modelin translatable alanlarını çevir.
     * Zaten çevrilmiş alanlar atlanır (idempotent).
     */
    public function translateModel($model, array $fields, array $targetLangs = ['en', 'ku'], bool $force = false): void
    {
        foreach ($fields as $field) {
            $turkishValue = $model->getTranslation($field, 'tr', false);

            if (empty($turkishValue)) {
                continue;
            }

            foreach ($targetLangs as $lang) {
                $existing = $model->getTranslation($field, $lang, false);
                if (! $force && ! empty($existing)) {
                    continue;
                }

                $translated = $this->translate($turkishValue, $lang);
                if ($translated) {
                    $model->setTranslation($field, $lang, $translated);
                }
            }
        }

        $model->saveQuietly();
    }
}
