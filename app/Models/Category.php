<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'iha_category_code',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'iha_category_code' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class, 'category_id');
    }

    public function additionalArticles(): BelongsToMany
    {
        return $this->belongsToMany(NewsArticle::class, 'news_article_category');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function publicArticlesQuery(): Builder
    {
        return NewsArticle::published()
            ->where(function (Builder $query): void {
                $query
                    ->where('category_id', $this->getKey())
                    ->orWhereHas('categories', fn (Builder $query) => $query->whereKey($this->getKey()));
            });
    }

    public function publicArticlesCount(): int
    {
        return $this->publicArticlesQuery()->count();
    }
}
