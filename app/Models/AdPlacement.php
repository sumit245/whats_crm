<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPlacement extends Model
{
    protected $fillable = [
        'ad_campaign_id', 'ad_channel_id', 'ad_creative_id',
        'placement_type', 'status',
        'external_ad_id', 'platform_campaign_id', 'platform_adset_id',
        'budget_override', 'metrics_cache', 'last_synced_at',
    ];

    protected $casts = [
        'metrics_cache'  => 'array',
        'last_synced_at' => 'datetime',
        'budget_override' => 'decimal:2',
    ];

    public function campaign()
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function channel()
    {
        return $this->belongsTo(AdChannel::class, 'ad_channel_id');
    }

    public function creative()
    {
        return $this->belongsTo(AdCreative::class, 'ad_creative_id');
    }

    public function metrics()
    {
        return $this->hasMany(AdMetric::class);
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'active'    => 'success',
            'paused'    => 'warning',
            'pending'   => 'secondary',
            'completed' => 'info',
            'failed'    => 'danger',
            'in_review' => 'primary',
            default     => 'secondary',
        };
    }

    // Totals from metrics_cache for quick display
    public function cachedImpressions(): int
    {
        return $this->metrics_cache['impressions'] ?? 0;
    }

    public function cachedClicks(): int
    {
        return $this->metrics_cache['clicks'] ?? 0;
    }

    public function cachedSpend(): float
    {
        return $this->metrics_cache['spend'] ?? 0;
    }

    public function cachedCtr(): float
    {
        return $this->metrics_cache['ctr'] ?? 0;
    }
}
