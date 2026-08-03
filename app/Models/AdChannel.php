<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AdChannel extends Model
{
    protected $fillable = [
        'user_id', 'type', 'name', 'status',
        'credentials', 'metadata', 'last_verified_at',
    ];

    protected $casts = [
        'metadata'        => 'array',
        'last_verified_at' => 'datetime',
    ];

    // Credentials stored encrypted
    public function setCredentialsAttribute(?array $value): void
    {
        $this->attributes['credentials'] = $value ? Crypt::encryptString(json_encode($value)) : null;
    }

    public function getCredentialsAttribute(?string $value): ?array
    {
        if (!$value) return null;
        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Throwable) {
            return null;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function placements()
    {
        return $this->hasMany(AdPlacement::class);
    }

    public function audiences()
    {
        return $this->hasMany(AdAudience::class);
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'meta'      => 'Meta (CTWA)',
            'facebook'  => 'Facebook',
            'instagram' => 'Instagram',
            'telegram'  => 'Telegram',
            'email'     => 'Email',
            'linkedin'  => 'LinkedIn',
            default     => ucfirst($this->type),
        };
    }

    public function typeIcon(): string
    {
        return match($this->type) {
            'meta'      => 'bi-whatsapp',
            'facebook'  => 'bi-facebook',
            'instagram' => 'bi-instagram',
            'telegram'  => 'bi-telegram',
            'email'     => 'bi-envelope-fill',
            'linkedin'  => 'bi-linkedin',
            default     => 'bi-broadcast',
        };
    }

    public function typeColor(): string
    {
        return match($this->type) {
            'meta'      => 'success',
            'facebook'  => 'primary',
            'instagram' => 'danger',
            'telegram'  => 'info',
            'email'     => 'warning',
            'linkedin'  => 'primary',
            default     => 'secondary',
        };
    }
}
