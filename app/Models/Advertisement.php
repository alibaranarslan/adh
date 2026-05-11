<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Advertisement extends Model
{
    public const TYPE_BANNER = 'banner';
    public const TYPE_ADSENSE = 'adsense';

    protected $fillable = [
        'name',
        'position',
        'type',
        'image_path',
        'desktop_image_path',
        'mobile_image_path',
        'link_url',
        'adsense_slot',
        'start_date',
        'end_date',
        'is_active',
        'sort_order',
        'click_count',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'click_count' => 'integer',
            'view_count' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    public function isRenderable(?string $adsenseClientId = null): bool
    {
        return $this->renderStatus($adsenseClientId) === 'ready';
    }

    public function renderStatus(?string $adsenseClientId = null): string
    {
        if (! $this->is_active) {
            return 'passive';
        }

        if ($this->start_date && $this->start_date->isFuture()) {
            return 'scheduled';
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return 'expired';
        }

        return match ($this->type) {
            self::TYPE_BANNER => $this->desktopImageUrl() === null ? 'missing_banner_image' : 'ready',
            self::TYPE_ADSENSE => match (true) {
                blank($this->adsense_slot) => 'missing_adsense_slot',
                blank($adsenseClientId) => 'missing_adsense_client',
                default => 'ready',
            },
            default => 'invalid_type',
        };
    }

    public function imageUrl(): ?string
    {
        return $this->desktopImageUrl();
    }

    public function desktopImageUrl(): ?string
    {
        return $this->assetUrl($this->desktop_image_path ?: $this->image_path);
    }

    public function mobileImageUrl(): ?string
    {
        return $this->assetUrl($this->mobile_image_path ?: null);
    }

    private function assetUrl(?string $value): ?string
    {
        $path = trim((string) $value);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }
}
