<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_source_id', 'webhook_trigger_id', 'headers', 'payload',
        'status', 'resolved_phone', 'error', 'processed_at',
    ];

    protected $casts = [
        'headers'      => 'array',
        'payload'      => 'array',
        'processed_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function source()
    {
        return $this->belongsTo(WebhookSource::class, 'webhook_source_id');
    }

    public function trigger()
    {
        return $this->belongsTo(WebhookTrigger::class, 'webhook_trigger_id');
    }
}
