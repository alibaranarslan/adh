<?php

namespace App\Models;

use App\Support\AdminPrivileges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class, 'author_id');
    }

    public function localInfoEntries(): HasMany
    {
        return $this->hasMany(LocalInfoEntry::class, 'created_by');
    }

    public function adminGuideProgress(): HasMany
    {
        return $this->hasMany(AdminGuideProgress::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return AdminPrivileges::canAccessAdminPanel($this);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
