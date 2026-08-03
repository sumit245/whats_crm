<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAbTest extends Model
{
    protected $table = 'ad_ab_tests';

    protected $fillable = [
        'user_id', 'ad_campaign_id', 'name', 'winner_metric',
        'decide_after_hours', 'launched_at', 'decided_at',
        'winner_creative_id', 'status',
    ];

    protected $casts = [
        'launched_at' => 'datetime',
        'decided_at'  => 'datetime',
    ];

    public function campaign()   { return $this->belongsTo(AdCampaign::class, 'ad_campaign_id'); }
    public function variants()   { return $this->hasMany(AdAbVariant::class, 'ad_ab_test_id')->orderBy('label'); }
    public function winner()     { return $this->belongsTo(AdCreative::class, 'winner_creative_id'); }

    public function statusColor(): string
    {
        return match($this->status) {
            'running'   => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }

    public function winnerMetricLabel(): string
    {
        return match($this->winner_metric) {
            'ctr'         => 'CTR (Click-through Rate)',
            'conversions' => 'Conversions',
            'clicks'      => 'Total Clicks',
            default       => ucfirst($this->winner_metric),
        };
    }

    /** Aggregate metrics for a given variant across all its placements */
    public function metricsForVariant(AdAbVariant $variant): array
    {
        $placements = AdPlacement::where('ad_campaign_id', $this->ad_campaign_id)
            ->where('ad_ab_variant_id', $variant->id)
            ->with('metrics')
            ->get();

        $impressions = $placements->sum(fn($p) => $p->metrics->sum('impressions'));
        $clicks      = $placements->sum(fn($p) => $p->metrics->sum('clicks'));
        $spend       = $placements->sum(fn($p) => $p->metrics->sum('spend'));
        $conversions = $placements->sum(fn($p) => $p->metrics->sum('conversions'));
        $ctr         = $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0;

        return compact('impressions', 'clicks', 'spend', 'conversions', 'ctr');
    }
}
