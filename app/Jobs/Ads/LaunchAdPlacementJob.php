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

class LaunchAdPlacementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public int $placementId) {}

    public function handle(AdsOrchestrator $orchestrator): void
    {
        $placement = AdPlacement::with(['campaign', 'channel', 'creative'])->find($this->placementId);

        if (!$placement) {
            Log::warning("LaunchAdPlacementJob: placement {$this->placementId} not found");
            return;
        }

        if ($placement->status !== 'pending') {
            return; // Already launched or failed
        }

        try {
            $service = $orchestrator->serviceFor($placement);
            $result  = $service->launch($placement);

            if (!$result->status) {
                $placement->update(['status' => 'failed']);
                Log::error("LaunchAdPlacementJob: launch failed for placement {$this->placementId}", [
                    'error' => $result->error,
                ]);
            }
        } catch (\Throwable $e) {
            $placement->update(['status' => 'failed']);
            Log::error("LaunchAdPlacementJob: exception for placement {$this->placementId}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        AdPlacement::where('id', $this->placementId)->update(['status' => 'failed']);
        Log::error("LaunchAdPlacementJob permanently failed for placement {$this->placementId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
