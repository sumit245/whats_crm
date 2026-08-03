<?php

namespace App\Services\Ads;

use App\Models\AdPlacement;

interface AdChannelInterface
{
    /**
     * Submit the placement to the channel's ad platform.
     * Returns ['status' => bool, 'external_id' => string|null, 'error' => string|null]
     */
    public function launch(AdPlacement $placement): object;

    /**
     * Pause an active placement on the channel platform.
     */
    public function pause(AdPlacement $placement): object;

    /**
     * Pull latest metrics for the placement from the platform API.
     * Should upsert AdMetric rows and update placement.metrics_cache.
     */
    public function syncMetrics(AdPlacement $placement): void;

    /**
     * Verify that the channel credentials are still valid.
     */
    public function verify(array $credentials): bool;
}
