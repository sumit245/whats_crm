<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentInvitation extends Model
{
    protected $fillable = ['agent_id', 'token', 'expires_at', 'accepted_at'];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }
}
