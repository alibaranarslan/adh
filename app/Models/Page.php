<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations;

    public const PROTECTED_STATIC_SLUGS = [
        'iletisim',
        'hakkimizda',
        'yayin-ilkeleri',
        'gizlilik-politikasi',
        'kvkk-aydinlatma',
        'cerez-politikasi',
    ];

    public array $translatable = ['title', 'content', 'meta_title', 'meta_description'];

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function pageViews()
    {
        return $this->morphMany(AnalyticsPageView::class, 'viewable');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function isProtectedStaticPage(): bool
    {
        return in_array($this->slug, self::PROTECTED_STATIC_SLUGS, true);
    }

    /**
     * @return array<int, string>
     */
    public static function protectedStaticSlugs(): array
    {
        return self::PROTECTED_STATIC_SLUGS;
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::bumpPublicContentVersion());
        static::deleted(fn () => static::bumpPublicContentVersion());
    }

    public static function bumpPublicContentVersion(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            Setting::set('system', 'content_version', (string) Str::uuid());
        } catch (\Throwable) {
            // Cache invalidation must not block editorial saves.
        }
    }
}
