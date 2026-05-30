<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'body', 'webhook', 'status', 'message_sent',
        'phone_number_id', 'waba_id', 'access_token',
        'quality_rating', 'messaging_tier', 'meta_profile',
    ];

    protected $casts = [
        'meta_profile' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function autoreplies()
    {
        return $this->hasMany(Autoreply::class, 'device', 'body');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function wabaTemplates()
    {
        return $this->hasMany(WabaTemplate::class);
    }

    public function messageDeliveryEvents()
    {
        return $this->hasMany(MessageDeliveryEvent::class);
    }

    public function getVerifiedNameAttribute(): string
    {
        return $this->meta_profile['verified_name'] ?? $this->body ?? '';
    }

    public function getQualityColorAttribute(): string
    {
        return match ($this->quality_rating) {
            'GREEN'  => 'success',
            'YELLOW' => 'warning',
            'RED'    => 'danger',
            default  => 'secondary',
        };
    }
}
