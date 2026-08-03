<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdCampaign extends Model
{
    protected $fillable = [
        'user_id', 'name', 'objective', 'status',
        'budget_total', 'budget_daily', 'currency', 'bid_strategy',
        'start_at', 'end_at', 'target_segment_id', 'audience_settings',
    ];

    protected $casts = [
        'audience_settings' => 'array',
        'start_at'          => 'datetime',
        'end_at'            => 'datetime',
        'budget_total'      => 'decimal:2',
        'budget_daily'      => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function placements()
    {
        return $this->hasMany(AdPlacement::class);
    }

    public function creatives()
    {
        return $this->hasMany(AdCreative::class);
    }

    public function segment()
    {
        return $this->belongsTo(Tag::class, 'target_segment_id');
    }

    public function objectiveLabel(): string
    {
        return match($this->objective) {
            'awareness'   => 'Brand Awareness',
            'traffic'     => 'Traffic',
            'engagement'  => 'Engagement',
            'leads'       => 'Lead Generation',
            'ctwa'        => 'Click-to-WhatsApp',
            'sales'       => 'Sales / Conversions',
            default       => ucfirst($this->objective),
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'active'    => 'success',
            'paused'    => 'warning',
            'draft'     => 'secondary',
            'completed' => 'info',
            'failed'    => 'danger',
            default     => 'secondary',
        };
    }

    // Aggregated spend from all placements' metrics
    public function totalSpend(): float
    {
        return $this->placements()
            ->join('ad_metrics', 'ad_placements.id', '=', 'ad_metrics.ad_placement_id')
            ->sum('ad_metrics.spend');
    }

    // Aggregated impressions
    public function totalImpressions(): int
    {
        return $this->placements()
            ->join('ad_metrics', 'ad_placements.id', '=', 'ad_metrics.ad_placement_id')
            ->sum('ad_metrics.impressions');
    }
}
