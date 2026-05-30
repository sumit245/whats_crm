<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'direction', 'type', 'body',
        'media_url', 'meta_message_id', 'status', 'payload',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }
}
