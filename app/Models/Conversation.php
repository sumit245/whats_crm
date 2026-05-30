<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'contact_number', 'contact_name',
        'last_message', 'last_message_at', 'unread_count', 'fallback_count',
        'assigned_agent_id', 'assignment_source', 'assigned_at',
        'first_response_at', 'resolved_at', 'sla_breached', 'conversation_status',
    ];

    protected $casts = [
        'last_message_at'   => 'datetime',
        'assigned_at'       => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at'       => 'datetime',
        'unread_count'      => 'integer',
        'sla_breached'      => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function chatbotSessions()
    {
        return $this->hasMany(\App\Models\ChatbotSession::class);
    }

    public function activeBotSession()
    {
        return $this->hasOne(\App\Models\ChatbotSession::class)
            ->whereIn('state', ['bot_active', 'awaiting_input'])
            ->latest();
    }

    public function assignedAgent()
    {
        return $this->belongsTo(\App\Models\Agent::class, 'assigned_agent_id');
    }

    public function notes()
    {
        return $this->hasMany(\App\Models\ChatNote::class)->orderBy('created_at');
    }

    public function getSlaMinutesElapsedAttribute(): ?int
    {
        if (!$this->assigned_at || $this->first_response_at || $this->resolved_at) {
            return null;
        }
        return (int) $this->assigned_at->diffInMinutes(now());
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->contact_name ?: $this->contact_number;
    }

    public function getAvatarLetterAttribute(): string
    {
        $name = $this->contact_name ?: $this->contact_number;
        return strtoupper(mb_substr($name, 0, 1));
    }
}
