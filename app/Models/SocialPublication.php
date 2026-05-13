<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPublication extends Model
{
    public const PLATFORM_INSTAGRAM = 'instagram';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'news_article_id',
        'platform',
        'status',
        'caption',
        'creative_image_path',
        'creative_image_url',
        'container_id',
        'media_id',
        'error_message',
        'attempts',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'news_article_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, self::STATUS_SKIPPED], true);
    }
}
