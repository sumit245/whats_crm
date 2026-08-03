<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbTest extends Model
{
    protected $table = 'ab_tests';

    protected $fillable = [
        'user_id', 'name', 'phonebook_id', 'device_id',
        'holdout_percent', 'winner_metric', 'decide_after_hours',
        'decide_at', 'winner_variant_id', 'winner_campaign_id', 'status',
    ];

    protected $casts = [
        'decide_at' => 'datetime',
    ];

    public function variants()
    {
        return $this->hasMany(AbVariant::class)->orderBy('label');
    }

    public function phonebook()
    {
        return $this->belongsTo(Tag::class, 'phonebook_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function winnerVariant()
    {
        return $this->belongsTo(AbVariant::class, 'winner_variant_id');
    }

    public function winnerCampaign()
    {
        return $this->belongsTo(Campaign::class, 'winner_campaign_id');
    }

    public function holdoutContacts()
    {
        return $this->hasMany(AbHoldoutContact::class);
    }
}
