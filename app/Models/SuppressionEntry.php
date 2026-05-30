<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuppressionEntry extends Model
{
    protected $table = 'suppression_list';

    protected $fillable = ['user_id', 'number', 'reason', 'note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Add a number to the suppression list, ignoring duplicates.
     */
    public static function suppress(int $userId, string $number, string $reason = 'manual', ?string $note = null): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'number' => $number],
            ['reason' => $reason, 'note' => $note]
        );
    }

    /**
     * Check if a number is suppressed for a given user.
     */
    public static function isSuppressed(int $userId, string $number): bool
    {
        return static::where('user_id', $userId)->where('number', $number)->exists();
    }
}
