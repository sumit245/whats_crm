<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'user_id', 'team_id', 'name', 'email', 'role',
        'status', 'max_concurrent_chats', 'active_chat_count', 'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'assigned_agent_id');
    }

    public function notes()
    {
        return $this->hasMany(ChatNote::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'online' && $this->active_chat_count < $this->max_concurrent_chats;
    }

    public function isSupervisor(): bool
    {
        return in_array($this->role, ['supervisor', 'admin']);
    }

    public function syncActiveCount(): void
    {
        $this->update([
            'active_chat_count' => $this->conversations()->where('conversation_status', 'open')->count(),
        ]);
    }
}
