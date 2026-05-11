<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnalyticsPageView extends Model
{
    public $timestamps = false;

    protected $table = 'analytics_page_views';

    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'session_id',
        'ip_address',
        'user_agent',
        'referer',
        'device_type',
        'country',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
