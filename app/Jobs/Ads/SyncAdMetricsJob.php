<?php

namespace App\Jobs\Ads;

use App\Models\AdPlacement;
use App\Services\Ads\AdsOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAdMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;

    public function __construct(public ?int $placementId = null) {}

    public function handle(AdsOrchestrator $orchestrator): void
    {
        if ($this->placementId) {
            // Single placement sync (dispatched from AdCampaignsController::syncMetrics)
            $placement = AdPlacement::with(['campaign', 'channel'])->find($this->placementId);
            if ($placement) {
                $this->syncOne($orchestrator, $placement);
            }
            return;
        }

        // Global daily sync for all active placements (from scheduler)
        AdPlacement::whereIn('status', ['active', 'paused'])
            ->with(['campaign', 'channel'])
            ->chunk(50, function ($placements) use ($orchestrator) {
                foreach ($placements as $placement) {
                    $this->syncOne($orchestrator, $placement);
                }
            });
    }

    private function syncOne(AdsOrchestrator $orchestrator, AdPlacement $placement): void
    {
        try {
            $orchestrator->serviceFor($placement)->syncMetrics($placement);
        } catch (\Throwable $e) {
            Log::error("SyncAdMetricsJob failed for placement {$placement->id}", ['error' => $e->getMessage()]);
        }
    }
}
