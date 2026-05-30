<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WabaTemplate extends Model
{
    protected $table = 'waba_templates';

    protected $fillable = [
        'user_id', 'device_id', 'name', 'meta_template_id',
        'category', 'language', 'status', 'components',
        'rejection_reason', 'meta_synced_at',
    ];

    protected $casts = [
        'components'     => 'json',
        'meta_synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'MARKETING'      => 'primary',
            'UTILITY'        => 'info',
            'AUTHENTICATION' => 'warning',
            default          => 'secondary',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'APPROVED' => 'success',
            'PENDING'  => 'warning',
            'REJECTED' => 'danger',
            'PAUSED'   => 'secondary',
            'DISABLED' => 'dark',
            default    => 'secondary',
        };
    }
}
