<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DripStep extends Model
{
    protected $fillable = [
        'sequence_id', 'step_order',
        'delay_value', 'delay_unit',
        'message_type', 'message_body', 'template_id',
        'skip_if_replied',
    ];

    protected $casts = [
        'skip_if_replied' => 'boolean',
        'delay_value'     => 'integer',
        'step_order'      => 'integer',
    ];

    public function sequence()
    {
        return $this->belongsTo(DripSequence::class, 'sequence_id');
    }

    public function template()
    {
        return $this->belongsTo(WabaTemplate::class, 'template_id');
    }

    /** Human-readable delay string e.g. "2 days" */
    public function delayLabel(): string
    {
        if ($this->delay_value === 0) return 'Immediately';
        return "{$this->delay_value} {$this->delay_unit}";
    }
}
