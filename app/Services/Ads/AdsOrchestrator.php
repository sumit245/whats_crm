<?php

namespace App\Services\Ads;

use App\Jobs\Ads\LaunchAdPlacementJob;
use App\Jobs\Ads\SyncAdMetricsJob;
use App\Models\AdCampaign;
use App\Models\AdPlacement;
use App\Services\Ads\Channels\EmailAdsService;
use App\Services\Ads\Channels\InstagramService;
use App\Services\Ads\Channels\LinkedInService;
use App\Services\Ads\Channels\MetaAdsService;
use App\Services\Ads\Channels\MetaCtwaService;
use App\Services\Ads\Channels\TelegramService;
use Illuminate\Support\Facades\Log;

class AdsOrchestrator
{
    public function launch(AdCampaign $campaign): void
    {
        foreach ($campaign->placements()->where('status', 'pending')->get() as $placement) {
            LaunchAdPlacementJob::dispatch($placement->id);
        }
    }

    public function pause(AdCampaign $campaign): void
    {
        foreach ($campaign->placements()->where('status', 'active')->get() as $placement) {
            try {
                $this->serviceFor($placement)->pause($placement);
                $placement->update(['status' => 'paused']);
            } catch (\Throwable $e) {
                Log::error("AdsOrchestrator::pause failed for placement {$placement->id}: " . $e->getMessage());
            }
        }
    }

    public function syncMetrics(AdCampaign $campaign): void
    {
        foreach ($campaign->placements()->whereIn('status', ['active', 'paused', 'completed'])->get() as $placement) {
            SyncAdMetricsJob::dispatch($placement->id);
        }
    }

    public function serviceFor(AdPlacement $placement): AdChannelInterface
    {
        $type = $placement->channel->type ?? 'meta';

        return match($type) {
            'meta'      => new MetaCtwaService(),
            'facebook'  => new MetaAdsService(),
            'instagram' => new InstagramService(),
            'telegram'  => new TelegramService(),
            'email'     => new EmailAdsService(),
            'linkedin'  => new LinkedInService(),
            default     => throw new \RuntimeException("Unknown ad channel type: {$type}"),
        };
    }
}
