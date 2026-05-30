<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotSession extends Model
{
    protected $fillable = [
        'conversation_id', 'flow_id', 'current_node_id',
        'state', 'variables', 'awaiting_variable',
        'fallback_count', 'last_executed_at', 'expires_at',
    ];

    protected $casts = [
        'variables'        => 'array',
        'last_executed_at' => 'datetime',
        'expires_at'       => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ChatbotFlow::class, 'flow_id');
    }

    public function isActive(): bool
    {
        return in_array($this->state, ['bot_active', 'awaiting_input']);
    }

    public function setVariable(string $key, mixed $value): void
    {
        $vars = $this->variables ?? [];
        $vars[$key] = $value;
        $this->update(['variables' => $vars]);
    }

    public function getVariable(string $key, mixed $default = null): mixed
    {
        return ($this->variables ?? [])[$key] ?? $default;
    }

    public function resolveVariables(string $text): string
    {
        foreach ($this->variables ?? [] as $key => $val) {
            $text = str_replace("{{$key}}", $val, $text);
        }
        return $text;
    }

    /**
     * Find the active (non-completed, non-expired) session for a conversation.
     */
    public static function activeFor(int $conversationId): ?self
    {
        return static::where('conversation_id', $conversationId)
            ->whereIn('state', ['bot_active', 'awaiting_input'])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('flow')
            ->latest()
            ->first();
    }
}
