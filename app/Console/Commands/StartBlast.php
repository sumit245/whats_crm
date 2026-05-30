<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBlastJob;
use App\Models\Campaign;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class StartBlast extends Command
{
    protected $signature   = 'start:blast';
    protected $description = 'Dispatch queued blast jobs for pending campaigns using batch tracking';

    public function handle(): int
    {
        Campaign::where('schedule', '<=', now())
            ->whereIn('status', ['waiting', 'processing'])
            ->whereNull('job_batch_id')
            ->with(['device', 'wabaTemplate'])
            ->chunk(20, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    try {
                        $this->dispatchCampaign($campaign);
                    } catch (\Throwable $e) {
                        Log::error("StartBlast: campaign {$campaign->id} dispatch error: " . $e->getMessage());
                    }
                }
            });

        return 0;
    }

    private function dispatchCampaign(Campaign $campaign): void
    {
        if ($campaign->device->status !== 'Connected') {
            $campaign->update(['status' => 'paused']);
            $this->warn("Campaign {$campaign->id} paused — device disconnected.");
            return;
        }

        $pendingBlasts = $campaign->blasts()
            ->where('status', 'pending')
            ->get(['id', 'campaign_id']);

        if ($pendingBlasts->isEmpty()) {
            $campaign->update(['status' => 'completed']);
            $this->info("Campaign {$campaign->id} already completed (no pending blasts).");
            return;
        }

        $delay = max(1, (int) $campaign->delay);

        $jobs = $pendingBlasts->map(function ($blast, $index) use ($campaign, $delay) {
            return (new ProcessBlastJob($blast->id, $campaign->id))
                ->delay(now()->addSeconds($index * $delay))
                ->onQueue('broadcasts');
        })->all();

        $campaignId = $campaign->id;

        $batch = Bus::batch($jobs)
            ->then(function (Batch $batch) use ($campaignId) {
                $campaign = Campaign::find($campaignId);
                if ($campaign && $campaign->status !== 'paused') {
                    $allFailed = !$campaign->blasts()->where('status', 'success')->exists();
                    $campaign->update(['status' => $allFailed ? 'failed' : 'completed']);
                    Log::info("Campaign {$campaignId} " . ($allFailed ? 'failed' : 'completed') . " via batch.");
                }
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($campaignId) {
                Log::error("Campaign {$campaignId} batch error: " . $e->getMessage());
            })
            ->name("Campaign #{$campaignId}")
            ->allowFailures()
            ->dispatch();

        $campaign->update(['status' => 'processing', 'job_batch_id' => $batch->id]);

        $this->info("Campaign {$campaign->id}: dispatched {$pendingBlasts->count()} blast jobs in batch {$batch->id}.");
    }
}
