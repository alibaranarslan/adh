<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class NewsArticle extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia, SoftDeletes;

    public array $translatable = ['title', 'summary', 'content', 'meta_title', 'meta_description'];

    protected $fillable = [
        'iha_id',
        'title',
        'slug',
        'summary',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
        'source',
        'source_url',
        'author_id',
        'category_id',
        'city_code',
        'city_slug',
        'editorial_score',
        'status',
        'is_breaking',
        'is_featured',
        'view_count',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_breaking' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'view_count' => 'integer',
            'city_code' => 'integer',
            'editorial_score' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(400)
                    ->height(300)
                    ->format('webp')
                    ->quality(80);
                $this->addMediaConversion('medium')
                    ->width(800)
                    ->height(600)
                    ->format('webp')
                    ->quality(80);
            });

        $this->addMediaCollection('gallery');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'news_article_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'news_article_tag');
    }

    public function images(): HasMany
    {
        return $this->hasMany(NewsImage::class)->orderBy('sort_order');
    }

    public function pageViews()
    {
        return $this->morphMany(AnalyticsPageView::class, 'viewable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopePubliclyAccessible($query)
    {
        return $query->whereIn('status', ['published', 'archived'])
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeBreaking($query)
    {
        return $query->where('is_breaking', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeFromIha($query)
    {
        return $query->where('source', 'iha');
    }

    public function scopeManual($query)
    {
        return $query->where('source', 'manuel');
    }

    public function isFromIha(): bool
    {
        return $this->source === 'iha';
    }

    public function getReadTimeAttribute(): int
    {
        $text = strip_tags($this->getTranslation('content', 'tr', false) ?: ($this->content ?? ''));
        $wordCount = str_word_count($text);
        return max(1, (int) ceil($wordCount / 200));
    }

    public function getMostReadScoreAttribute(): float
    {
        $views = $this->view_count ?? 0;
        $hoursAgo = $this->published_at ? max(1, $this->published_at->diffInHours(now())) : 999;
        $recencyMultiplier = 1 / log($hoursAgo + 2, 2);
        $editorialBonus = ($this->editorial_score ?? 0) * 0.1;

        return ($views * 2) + ($recencyMultiplier * 50) + $editorialBonus;
    }
}
