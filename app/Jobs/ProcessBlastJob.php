<?php

namespace App\Jobs;

use App\Models\Blast;
use App\Models\Campaign;
use App\Models\SuppressionEntry;
use App\Services\Impl\MetaCloudApiService;
use App\Services\MetaRateLimiter;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBlastJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before the job is marked as failed.
     * Attempts 1-4 are for transient errors; attempt 5 writes to failed_jobs.
     */
    public int $tries = 5;

    /**
     * Exponential backoff in seconds between retries.
     * 30s → 2min → 4min → 10min → 30min
     */
    public array $backoff = [30, 120, 240, 600, 1800];

    /**
     * Meta error codes that indicate a permanent opt-out / invalid number.
     * These suppress the number and do NOT retry.
     */
    private const SUPPRESSION_CODES = [131026, 131047, 131048, 131049, 131051];

    /**
     * Meta error codes that indicate a rate-limit (429-equivalent).
     * These re-queue the job with backoff instead of failing.
     */
    private const RATE_LIMIT_CODES = [80007, 4, 130429, 131056];

    public function __construct(
        public readonly int $blastId,
        public readonly int $campaignId,
    ) {}

    public function handle(): void
    {
        $blast = Blast::with('campaign.device', 'campaign.wabaTemplate')->find($this->blastId);

        if (!$blast) {
            Log::warning("ProcessBlastJob: blast {$this->blastId} not found — skipping.");
            return;
        }

        // Already processed (success/suppressed) — idempotency guard
        if (in_array($blast->status, ['success', 'suppressed'])) {
            return;
        }

        $campaign = $blast->campaign;
        $device   = $campaign?->device;

        if (!$campaign || !$device) {
            $blast->update(['status' => 'failed']);
            return;
        }

        // Campaign paused — release back to queue for later
        if ($campaign->status === 'paused') {
            $this->release(300);
            return;
        }

        // Pre-flight: suppression check
        if (SuppressionEntry::isSuppressed($blast->user_id, $blast->receiver)) {
            $blast->update(['status' => 'suppressed']);
            $this->checkCampaignCompletion($campaign);
            return;
        }

        // Pre-flight: device connectivity
        if ($device->status !== 'Connected') {
            $campaign->update(['status' => 'paused']);
            Log::warning("ProcessBlastJob: campaign {$campaign->id} paused — device {$device->id} disconnected.");
            $this->release(300);
            return;
        }

        // Local MPS rate limiter — back off 1s if over tier limit
        $rateLimiter = new MetaRateLimiter();
        if (!$rateLimiter->acquire((string) $device->id, $device->messaging_tier)) {
            $this->release(1);
            return;
        }

        $service = new MetaCloudApiService($device);

        try {
            if ($campaign->wabaTemplate) {
                $result = $service->sendBlastTemplate($blast, [
                    'name'     => $campaign->wabaTemplate->name,
                    'language' => $campaign->wabaTemplate->language,
                ]);
            } else {
                $msg = is_array($blast->message) ? ($blast->message['text'] ?? '') : $blast->message;
                $fakeRequest = (object) ['message' => $msg];
                $result = $service->sendText($fakeRequest, $blast->receiver);
            }

            if ($result->status) {
                $blast->update([
                    'status'          => 'success',
                    'meta_message_id' => $result->message_id ?? null,
                ]);
            } else {
                $this->handleApiError($blast, $campaign, $result);
                return;
            }

        } catch (\Throwable $e) {
            Log::error("ProcessBlastJob: blast {$blast->id} exception: " . $e->getMessage());
            if ($this->attempts() >= $this->tries) {
                $blast->update(['status' => 'failed']);
            } else {
                $this->release($this->backoff[$this->attempts() - 1] ?? 1800);
            }
            return;
        }

        $this->checkCampaignCompletion($campaign);
    }

    private function handleApiError(Blast $blast, Campaign $campaign, object $result): void
    {
        $errorCode = (int) ($result->error_code ?? 0);

        // Permanent opt-out / invalid number — suppress and don't retry
        if (in_array($errorCode, self::SUPPRESSION_CODES)) {
            SuppressionEntry::suppress(
                $blast->user_id,
                $blast->receiver,
                'meta_block',
                "Auto-suppressed: Meta error {$errorCode}"
            );
            $blast->update(['status' => 'suppressed']);
            Log::info("ProcessBlastJob: {$blast->receiver} suppressed (error {$errorCode}).");
            $this->checkCampaignCompletion($campaign);
            return;
        }

        // Rate-limited — re-queue with exponential backoff
        if (in_array($errorCode, self::RATE_LIMIT_CODES) || str_contains((string) ($result->error ?? ''), '429')) {
            $delay = $this->backoff[$this->attempts() - 1] ?? 1800;
            Log::warning("ProcessBlastJob: rate-limited on blast {$blast->id}, re-queuing in {$delay}s.");
            $this->release($delay);
            return;
        }

        // Transient error — retry if attempts remain
        Log::error("ProcessBlastJob: blast {$blast->id} API error {$errorCode}: " . ($result->error ?? 'unknown'));
        if ($this->attempts() >= $this->tries) {
            $blast->update(['status' => 'failed']);
            $this->checkCampaignCompletion($campaign);
        } else {
            $this->release($this->backoff[$this->attempts() - 1] ?? 1800);
        }
    }

    private function checkCampaignCompletion(Campaign $campaign): void
    {
        $pending = $campaign->blasts()->where('status', 'pending')->count();

        if ($pending === 0) {
            $hasFailures = $campaign->blasts()->whereIn('status', ['failed'])->exists();
            $allFailed   = !$campaign->blasts()->where('status', 'success')->exists();

            $campaign->update([
                'status' => $allFailed ? 'failed' : 'completed',
            ]);

            Log::info("Campaign {$campaign->id} " . ($allFailed ? 'failed' : 'completed') . ".");
        }
    }

    /**
     * Called when the job permanently fails after all retries.
     */
    public function failed(\Throwable $exception): void
    {
        $blast = Blast::find($this->blastId);
        if ($blast && $blast->status === 'pending') {
            $blast->update(['status' => 'failed']);
        }

        Log::error("ProcessBlastJob permanently failed for blast {$this->blastId}: " . $exception->getMessage());
    }
}
