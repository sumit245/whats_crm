<?php

namespace App\Services;

use App\Models\ChatSetting;
use App\Models\Conversation;
use App\Models\Device;
use App\Services\Impl\MetaCloudApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OffHoursService
{
    public function handle(Conversation $conversation, Device $device): void
    {
        $setting = ChatSetting::forUser($conversation->user_id);

        if (!$setting->off_hours_enabled) return;
        if (empty($setting->off_hours_message)) return;
        if ($setting->isWithinWorkingHours()) return;

        // Throttle: send at most once every 4 hours per contact
        $cacheKey = "off_hours_sent:{$conversation->user_id}:{$conversation->contact_number}";
        if (Cache::has($cacheKey)) return;

        $service = new MetaCloudApiService($device);
        $req     = (object) ['message' => $setting->off_hours_message];

        $result  = $service->sendText($req, $conversation->contact_number);

        if ($result->status) {
            Cache::put($cacheKey, true, now()->addHours(4));
            Log::info("OffHoursService: sent reply to {$conversation->contact_number}");
        }
    }
}
