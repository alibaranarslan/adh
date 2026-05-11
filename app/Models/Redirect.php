<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'old_slug',
        'new_slug',
        'model_type',
        'model_id',
        'status_code',
    ];

    public static function findBySlug(string $slug, string $modelType): ?self
    {
        return static::query()
            ->where('old_slug', $slug)
            ->where('model_type', $modelType)
            ->first();
    }
}
