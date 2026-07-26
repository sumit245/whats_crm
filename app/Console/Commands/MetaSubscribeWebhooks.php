<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\Impl\MetaCloudApiService;
use Illuminate\Console\Command;

class MetaSubscribeWebhooks extends Command
{
    protected $signature   = 'meta:subscribe-webhooks {--device= : Subscribe only a specific device ID}';
    protected $description = 'Subscribe connected WhatsApp Business Accounts to this app so inbound messages + delivery statuses reach the webhook';

    public function handle(): int
    {
        $query = Device::where('status', 'Connected')->whereNotNull('waba_id');

        if ($deviceId = $this->option('device')) {
            $query->where('id', $deviceId);
        }

        $devices = $query->get();

        if ($devices->isEmpty()) {
            $this->warn('No connected devices with a WABA ID found.');
            return self::SUCCESS;
        }

        foreach ($devices as $device) {
            $result = (new MetaCloudApiService($device))->subscribeToWaba();
            if ($result->status) {
                $this->info("✓ Device #{$device->id} ({$device->body}) — WABA {$device->waba_id} subscribed.");
            } else {
                $this->error("✗ Device #{$device->id} ({$device->body}) — {$result->error}");
            }
        }

        return self::SUCCESS;
    }
}
