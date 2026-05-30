<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageDeliveryEvent extends Model
{
    protected $table = 'message_delivery_events';

    protected $fillable = [
        'meta_message_id', 'blast_id', 'device_id',
        'status', 'error_code', 'error_title', 'event_timestamp',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
    ];

    public function blast()
    {
        return $this->belongsTo(Blast::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
