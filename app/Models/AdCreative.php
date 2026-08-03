<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdCreative extends Model
{
    protected $fillable = [
        'user_id', 'ad_campaign_id', 'name', 'format',
        'headline', 'body', 'cta_text', 'cta_url',
        'media_paths', 'carousel_cards', 'status',
    ];

    protected $casts = [
        'media_paths'    => 'array',
        'carousel_cards' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaign()
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function placements()
    {
        return $this->hasMany(AdPlacement::class);
    }

    public function formatIcon(): string
    {
        return match($this->format) {
            'text'     => 'bi-type',
            'image'    => 'bi-image',
            'video'    => 'bi-play-circle',
            'carousel' => 'bi-collection',
            'story'    => 'bi-phone',
            'reel'     => 'bi-film',
            default    => 'bi-file-earmark',
        };
    }

    public function firstMediaUrl(): ?string
    {
        return isset($this->media_paths[0]) ? asset('storage/' . $this->media_paths[0]) : null;
    }
}
