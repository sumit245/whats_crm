<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DripEnrollment extends Model
{
    protected $fillable = [
        'sequence_id', 'user_id', 'contact_number', 'device_id',
        'current_step', 'status', 'next_run_at', 'completed_at', 'last_error',
    ];

    protected $casts = [
        'enrolled_at'  => 'datetime',
        'next_run_at'  => 'datetime',
        'completed_at' => 'datetime',
        'current_step' => 'integer',
    ];

    public function sequence()
    {
        return $this->belongsTo(DripSequence::class, 'sequence_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Check if contact has replied via any conversation since enrollment */
    public function contactHasReplied(): bool
    {
        return ChatMessage::whereHas('conversation', function ($q) {
                $q->where('contact_number', $this->contact_number)
                  ->where('user_id', $this->user_id);
            })
            ->where('direction', 'inbound')
            ->where('created_at', '>=', $this->enrolled_at)
            ->exists();
    }
}
