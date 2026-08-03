<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAudience extends Model
{
    protected $fillable = [
        'user_id', 'ad_channel_id', 'name',
        'definition', 'external_audience_id', 'estimated_size',
    ];

    protected $casts = [
        'definition' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function channel()
    {
        return $this->belongsTo(AdChannel::class, 'ad_channel_id');
    }

    public function estimatedSizeLabel(): string
    {
        if (!$this->estimated_size) return '—';
        if ($this->estimated_size >= 1_000_000) return round($this->estimated_size / 1_000_000, 1) . 'M';
        if ($this->estimated_size >= 1_000)     return round($this->estimated_size / 1_000, 1) . 'K';
        return (string) $this->estimated_size;
    }
}
