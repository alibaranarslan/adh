<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminGuideProgress extends Model
{
    use HasFactory;

    protected $table = 'admin_guide_progress';

    protected $fillable = [
        'user_id',
        'guide_key',
        'status',
        'last_step_index',
        'completed_at',
        'dismissed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'last_step_index' => 'integer',
            'completed_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
