<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blast extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'sender', 'campaign_id', 'receiver',
        'message', 'type', 'status',
        'meta_message_id', 'template_variables',
    ];

    protected $casts = [
        'template_variables' => 'json',
        'message'            => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function deliveryEvents()
    {
        return $this->hasMany(MessageDeliveryEvent::class);
    }
}
