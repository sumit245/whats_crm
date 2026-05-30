<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatNote extends Model
{
    protected $fillable = [
        'conversation_id', 'agent_id', 'agent_name', 'note', 'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function getAuthorAttribute(): string
    {
        return $this->agent_name ?? ($this->agent?->name ?? 'Unknown');
    }
}
