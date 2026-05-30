<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Token-bucket rate limiter for Meta Cloud API sends.
 * Uses Laravel's RateLimiter (backed by cache driver) to enforce MPS limits per device.
 *
 * Meta tier MPS limits:
 *   TIER_1K  → 80 MPS
 *   TIER_10K → 400 MPS
 *   TIER_100K → 1000 MPS
 */
class MetaRateLimiter
{
    private const TIER_LIMITS = [
        'TIER_1K'   => 80,
        'TIER_10K'  => 400,
        'TIER_100K' => 1000,
        'default'   => 80,
    ];

    /**
     * Attempt to acquire a send slot for a device.
     * Returns true if allowed, false if rate-limited (caller should release/delay job).
     */
    public function acquire(string $deviceId, ?string $tier = null): bool
    {
        $mps = self::TIER_LIMITS[$tier ?? 'default'] ?? 80;
        $key = "meta_mps:{$deviceId}";

        return RateLimiter::attempt(
            key:      $key,
            maxAttempts: $mps,
            callback: fn () => true,
            decaySeconds: 1,
        );
    }

    /**
     * How many seconds until the rate limit resets for this device.
     */
    public function availableIn(string $deviceId): int
    {
        return RateLimiter::availableIn("meta_mps:{$deviceId}");
    }
}
