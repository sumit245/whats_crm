<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptInSetting extends Model
{
    protected $fillable = [
        'user_id',
        'opt_out_keywords',
        'opt_in_keyword',
        'opt_in_phonebook_id',
        'opt_in_reply',
        'opt_out_reply',
        'opt_in_enabled',
        'opt_out_enabled',
    ];

    protected $casts = [
        'opt_out_keywords'  => 'array',
        'opt_in_enabled'    => 'boolean',
        'opt_out_enabled'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function phonebook(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'opt_in_phonebook_id');
    }

    /**
     * Get or create settings for a user with sane defaults.
     */
    public static function forUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'opt_out_keywords'  => ['STOP', 'UNSUBSCRIBE', 'OPT OUT', 'OPTOUT', 'BERHENTI'],
                'opt_in_keyword'    => 'JOIN',
                'opt_in_phonebook_id' => null,
                'opt_in_reply'      => null,
                'opt_out_reply'     => null,
                'opt_in_enabled'    => true,
                'opt_out_enabled'   => true,
            ]
        );
    }

    /**
     * Check if the given text matches the opt-out keywords (case-insensitive, full word match).
     */
    public function matchesOptOut(string $text): bool
    {
        if (!$this->opt_out_enabled) return false;
        $normalized = strtoupper(trim($text));
        $keywords   = $this->opt_out_keywords ?? ['STOP', 'UNSUBSCRIBE', 'OPT OUT', 'OPTOUT'];
        foreach ($keywords as $kw) {
            if (strtoupper(trim($kw)) === $normalized) return true;
        }
        return false;
    }

    /**
     * Check if the given text matches the opt-in keyword (case-insensitive, full word match).
     */
    public function matchesOptIn(string $text): bool
    {
        if (!$this->opt_in_enabled) return false;
        if (!$this->opt_in_keyword) return false;
        return strtoupper(trim($text)) === strtoupper(trim($this->opt_in_keyword));
    }
}
