<?php

namespace App\Jobs\Ads;

use App\Models\AdAbTest;
use App\Models\AdPlacement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DecideAdAbWinnerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(public int $testId) {}

    public function handle(): void
    {
        $test = AdAbTest::with('variants.creative')->find($this->testId);

        if (!$test || $test->status !== 'running') {
            return;
        }

        // Sync latest metrics for all placements in this campaign
        $placements = AdPlacement::where('ad_campaign_id', $test->ad_campaign_id)
            ->whereNotNull('ad_ab_variant_id')
            ->with(['channel', 'metrics'])
            ->get();

        if ($placements->isEmpty()) {
            Log::warning("DecideAdAbWinnerJob: no placements for test {$test->id}");
            return;
        }

        // Aggregate metric per variant
        $scores = [];
        foreach ($test->variants as $variant) {
            $varPlacements = $placements->where('ad_ab_variant_id', $variant->id);
            $impressions   = $varPlacements->sum(fn($p) => $p->metrics->sum('impressions'));
            $clicks        = $varPlacements->sum(fn($p) => $p->metrics->sum('clicks'));
            $conversions   = $varPlacements->sum(fn($p) => $p->metrics->sum('conversions'));

            $scores[$variant->id] = match($test->winner_metric) {
                'ctr'         => $impressions > 0 ? $clicks / $impressions : 0,
                'conversions' => (float) $conversions,
                'clicks'      => (float) $clicks,
                default       => 0,
            };
        }

        if (empty($scores)) {
            return;
        }

        // Winner = highest score
        arsort($scores);
        $winnerVariantId = array_key_first($scores);
        $winnerVariant   = $test->variants->firstWhere('id', $winnerVariantId);

        if (!$winnerVariant) {
            return;
        }

        // Pause loser placements
        $loserVariantIds = $test->variants->pluck('id')->filter(fn($id) => $id !== $winnerVariantId)->values();
        if ($loserVariantIds->isNotEmpty()) {
            AdPlacement::where('ad_campaign_id', $test->ad_campaign_id)
                ->whereIn('ad_ab_variant_id', $loserVariantIds)
                ->where('status', 'active')
                ->get()
                ->each(function ($p) {
                    try {
                        (new \App\Services\Ads\AdsOrchestrator())->pause(
                            \App\Models\AdCampaign::find($p->ad_campaign_id)
                        );
                    } catch (\Throwable) {}
                    $p->update(['status' => 'paused']);
                });
        }

        // Mark winner placements clearly
        AdPlacement::where('ad_campaign_id', $test->ad_campaign_id)
            ->where('ad_ab_variant_id', $winnerVariantId)
            ->where('status', 'active')
            ->update(['ad_creative_id' => $winnerVariant->ad_creative_id]);

        $test->update([
            'status'             => 'completed',
            'decided_at'         => now(),
            'winner_creative_id' => $winnerVariant->ad_creative_id,
        ]);

        Log::info("DecideAdAbWinnerJob: test {$test->id} completed. Winner: variant {$winnerVariant->label} (creative {$winnerVariant->ad_creative_id})", ['scores' => $scores]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("DecideAdAbWinnerJob failed for test {$this->testId}", ['error' => $e->getMessage()]);
    }
}
