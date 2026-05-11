<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IhaSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'started_at',
        'completed_at',
        'status',
        'articles_fetched',
        'articles_created',
        'articles_updated',
        'articles_skipped',
        'images_downloaded',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'articles_fetched' => 'integer',
            'articles_created' => 'integer',
            'articles_updated' => 'integer',
            'articles_skipped' => 'integer',
            'images_downloaded' => 'integer',
        ];
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }
}
