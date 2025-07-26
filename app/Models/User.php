<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isOAuthUser(): bool
    {
        return !is_null($this->provider) && $this->provider !== 'local';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar; // URL externa de OAuth
            }
            return asset('storage/' . $this->avatar); // Avatar local
        }
        return null;
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}
