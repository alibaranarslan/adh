<?php

namespace App\Support;

class LocalizedSettings
{
    public const LOCALES = [
        'tr' => 'Türkçe',
        'en' => 'English',
        'ku' => 'Kurdî',
    ];

    public static function localeLabels(): array
    {
        return self::LOCALES;
    }

    public static function blankText(): array
    {
        return array_fill_keys(array_keys(self::LOCALES), '');
    }

    public static function decodeText(mixed $value): array
    {
        if (is_array($value)) {
            return self::normalizeTextPayload($value);
        }

        $text = trim((string) $value);

        if ($text === '') {
            return self::blankText();
        }

        $decoded = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return self::normalizeTextPayload($decoded);
        }

        return self::normalizeTextPayload(['tr' => $text]);
    }

    public static function encodeText(mixed $value): string
    {
        return json_encode(self::normalizeTextPayload($value), JSON_UNESCAPED_UNICODE);
    }

    public static function resolveText(mixed $value, string $fallback = ''): string
    {
        $normalized = self::decodeText($value);
        $locale = app()->getLocale();

        foreach ([$locale, 'tr', 'en', 'ku'] as $candidate) {
            $resolved = trim((string) ($normalized[$candidate] ?? ''));

            if ($resolved !== '') {
                return $resolved;
            }
        }

        return $fallback;
    }

    public static function decodeRepeater(mixed $value, array $localizedKeys = []): array
    {
        $items = self::decodeArray($value, []);

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) use ($localizedKeys): array {
                foreach ($localizedKeys as $key) {
                    $item[$key] = self::decodeText($item[$key] ?? []);
                }

                return $item;
            })
            ->values()
            ->all();
    }

    public static function encodeRepeater(array $items, array $localizedKeys = []): string
    {
        $normalized = collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) use ($localizedKeys): array {
                foreach ($localizedKeys as $key) {
                    $item[$key] = self::normalizeTextPayload($item[$key] ?? []);
                }

                return $item;
            })
            ->values()
            ->all();

        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    public static function resolveRepeater(mixed $value, array $localizedKeys = []): array
    {
        return collect(self::decodeRepeater($value, $localizedKeys))
            ->map(function (array $item) use ($localizedKeys): array {
                foreach ($localizedKeys as $key) {
                    $item[$key] = self::resolveText($item[$key] ?? [], '');
                }

                return $item;
            })
            ->values()
            ->all();
    }

    private static function normalizeTextPayload(mixed $value): array
    {
        $normalized = self::blankText();

        if (is_array($value)) {
            foreach (array_keys(self::LOCALES) as $locale) {
                $normalized[$locale] = trim((string) ($value[$locale] ?? ''));
            }

            return $normalized;
        }

        $normalized['tr'] = trim((string) $value);

        return $normalized;
    }

    private static function decodeArray(mixed $value, array $fallback): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : $fallback;
    }
}
