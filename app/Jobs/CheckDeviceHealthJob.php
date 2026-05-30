<?php

namespace App\Jobs;

use App\Mail\QualityRatingAlert;
use App\Models\Device;
use App\Services\Impl\MetaCloudApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckDeviceHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $deviceId) {}

    public function handle(): void
    {
        $device = Device::with('user')->find($this->deviceId);

        if (!$device || !$device->phone_number_id || !$device->access_token) {
            return;
        }

        $service  = new MetaCloudApiService();
        $response = $service->connectDevice($device);

        if (!$response->status) {
            Log::warning("CheckDeviceHealthJob: failed to reach Meta for device {$device->id}: " . ($response->error ?? 'unknown'));
            return;
        }

        $data       = $response->data ?? [];
        $oldRating  = $device->quality_rating ?? 'GREEN';
        $newRating  = strtoupper($data['quality_rating'] ?? $oldRating);
        $newTier    = $data['messaging_limit_tier'] ?? $device->messaging_tier;

        $ratingRank = ['GREEN' => 3, 'YELLOW' => 2, 'RED' => 1, 'UNKNOWN' => 0];
        $oldRank    = $ratingRank[$oldRating] ?? 3;
        $newRank    = $ratingRank[$newRating] ?? 3;

        $device->update([
            'quality_rating' => $newRating,
            'messaging_tier' => $newTier,
            'meta_profile'   => array_merge($device->meta_profile ?? [], [
                'display_phone_number' => $data['display_phone_number'] ?? null,
                'verified_name'        => $data['verified_name'] ?? null,
            ]),
        ]);

        // Alert when quality degrades (GREEN→YELLOW or GREEN→RED or YELLOW→RED)
        if ($newRank < $oldRank && $device->user?->email) {
            Log::warning("Device {$device->id} quality degraded: {$oldRating} → {$newRating}. Sending alert.");
            Mail::to($device->user->email)->send(new QualityRatingAlert($device, $oldRating, $newRating));
        }

        Log::info("CheckDeviceHealthJob: device {$device->id} quality={$newRating} tier={$newTier}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("CheckDeviceHealthJob failed for device {$this->deviceId}: " . $exception->getMessage());
    }
}
