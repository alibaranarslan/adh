<?php

namespace App\Support;

use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdminImageUploads
{
    public const ACCEPTED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public const MAX_SIZE_KB = 5120;

    private const EXTENSIONS_BY_MIME_TYPE = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * @return array<int, string>
     */
    public static function acceptedMimeTypes(): array
    {
        return self::ACCEPTED_MIME_TYPES;
    }

    public static function maxSizeKb(): int
    {
        return self::MAX_SIZE_KB;
    }

    public static function storedFileName(TemporaryUploadedFile $file): string
    {
        return Str::random(40) . '.' . self::extensionForMimeType($file->getMimeType());
    }

    public static function extensionForMimeType(string $mimeType): string
    {
        $extension = self::EXTENSIONS_BY_MIME_TYPE[strtolower($mimeType)] ?? null;

        if ($extension === null) {
            throw new \InvalidArgumentException("Unsupported image MIME type [{$mimeType}].");
        }

        return $extension;
    }
}
