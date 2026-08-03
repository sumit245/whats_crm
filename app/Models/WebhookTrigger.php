<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookTrigger extends Model
{
    protected $fillable = [
        'webhook_source_id', 'name', 'match_field', 'match_value',
        'action_type', 'device_id', 'template_name', 'template_language',
        'variable_map', 'flow_id', 'active',
    ];

    protected $casts = [
        'variable_map' => 'array',
        'active'       => 'boolean',
    ];

    public function source()
    {
        return $this->belongsTo(WebhookSource::class, 'webhook_source_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function flow()
    {
        return $this->belongsTo(ChatbotFlow::class, 'flow_id');
    }

    /** Check whether this trigger should fire for the given decoded payload. */
    public function matches(array $payload): bool
    {
        if (!$this->active) return false;
        if (!$this->match_field) return true; // no filter = match all

        $actual = data_get($payload, $this->match_field);
        return (string) $actual === (string) $this->match_value;
    }
}
