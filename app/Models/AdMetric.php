<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdMetric extends Model
{
    protected $fillable = [
        'ad_placement_id', 'date',
        'impressions', 'reach', 'clicks', 'spend', 'conversions',
        'ctr', 'cpm', 'cpc', 'channel_raw',
    ];

    protected $casts = [
        'date'        => 'date',
        'channel_raw' => 'array',
        'spend'       => 'decimal:4',
        'ctr'         => 'decimal:4',
        'cpm'         => 'decimal:4',
        'cpc'         => 'decimal:4',
    ];

    public function placement()
    {
        return $this->belongsTo(AdPlacement::class, 'ad_placement_id');
    }

    // Recompute derived fields before save
    protected static function booted(): void
    {
        static::saving(function (self $m) {
            if ($m->impressions > 0) {
                $m->ctr = round($m->clicks / $m->impressions * 100, 4);
                $m->cpm = round($m->spend / $m->impressions * 1000, 4);
            }
            if ($m->clicks > 0) {
                $m->cpc = round($m->spend / $m->clicks, 4);
            }
        });
    }
}
