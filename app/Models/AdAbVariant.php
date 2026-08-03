<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAbVariant extends Model
{
    protected $table = 'ad_ab_variants';

    protected $fillable = ['ad_ab_test_id', 'label', 'ad_creative_id', 'budget_split'];

    public function test()     { return $this->belongsTo(AdAbTest::class, 'ad_ab_test_id'); }
    public function creative() { return $this->belongsTo(AdCreative::class, 'ad_creative_id'); }

    public function placements()
    {
        return AdPlacement::where('ad_ab_variant_id', $this->id);
    }
}
