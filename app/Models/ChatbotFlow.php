<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotFlow extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'name', 'description', 'status',
        'trigger_type', 'trigger_value', 'trigger_match',
        'flow_json', 'fallback_message',
    ];

    protected $casts = [
        'flow_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ChatbotSession::class, 'flow_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check whether an incoming message text matches this flow's trigger.
     */
    public function matchesTrigger(string $message, ?array $referral = null, bool $isApiTrigger = false): bool
    {
        return match ($this->trigger_type) {
            'all'      => true,
            'referral' => $referral && ($referral['ref'] ?? '') === $this->trigger_value,
            'keyword'  => $this->matchesKeyword($message),
            'api'      => $isApiTrigger, // Phase G: triggered programmatically
            default    => false,
        };
    }

    private function matchesKeyword(string $message): bool
    {
        $keyword = mb_strtolower(trim($this->trigger_value ?? ''));
        $message = mb_strtolower(trim($message));

        if (!$keyword) return false;

        // Exact/positional match first (most precise)
        $directMatch = match ($this->trigger_match) {
            'exact'       => $message === $keyword,
            'starts_with' => str_starts_with($message, $keyword),
            default       => str_contains($message, $keyword),
        };

        if ($directMatch) return true;

        // Phase F: Fuzzy fallback for typo tolerance on exact/contains triggers
        // Only apply fuzzy to keywords of 4+ characters to avoid false positives
        if (strlen($keyword) >= 4) {
            // similar_text: percentage similarity
            similar_text($message, $keyword, $pct);
            if ($pct >= 80) return true;

            // Levenshtein: allow up to 2 character edits for short keywords
            $distance = levenshtein($message, $keyword);
            if ($distance <= 2) return true;
        }

        return false;
    }

    /**
     * Return the parsed node map from the Drawflow JSON.
     * Returns: ['1' => [...node data...], '2' => [...], ...]
     */
    public function getNodes(): array
    {
        $json = $this->flow_json;
        if (!$json) return [];

        // Drawflow stores nodes under drawflow.Home.data
        return $json['drawflow']['Home']['data'] ?? [];
    }

    /**
     * Find the first trigger node (no inputs) in the flow.
     */
    public function findTriggerNodeId(): ?string
    {
        foreach ($this->getNodes() as $nodeId => $node) {
            if (empty($node['inputs'])) {
                return (string) $nodeId;
            }
        }
        return null;
    }
}
