<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\WabaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaHealthController extends Controller
{
    protected string $graphBase = 'https://graph.facebook.com/v20.0';

    public function index(Request $request)
    {
        $devices = $request->user()->devices()->where('status', 'Connected')->get();
        $healthData = [];

        foreach ($devices as $device) {
            $healthData[$device->id] = $this->fetchHealth($device);
        }

        return view('theme::pages.analytics.health', compact('devices', 'healthData'));
    }

    public function refresh(Request $request, $deviceId)
    {
        $device = $request->user()->devices()->findOrFail($deviceId);
        Cache::forget("meta_health_{$device->id}");
        $health = $this->fetchHealth($device);
        return response()->json(['error' => false, 'data' => $health]);
    }

    private function fetchHealth(Device $device): array
    {
        return Cache::remember("meta_health_{$device->id}", 300, function () use ($device) {
            $health = [
                'verified_name'       => $device->meta_profile['verified_name'] ?? $device->body,
                'display_phone'       => $device->meta_profile['display_phone_number'] ?? $device->body,
                'quality_rating'      => $device->quality_rating ?? 'UNKNOWN',
                'messaging_tier'      => $device->messaging_tier ?? 'UNKNOWN',
                'conversations_used'  => 0,
                'conversations_limit' => 1000,
                'template_stats'      => ['APPROVED' => 0, 'PENDING' => 0, 'REJECTED' => 0],
                'error'               => null,
            ];

            try {
                $response = Http::withToken($device->access_token)->get(
                    "{$this->graphBase}/{$device->phone_number_id}",
                    ['fields' => 'quality_rating,display_phone_number,verified_name,throughput,messaging_limit_tier,platform_type']
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $health['quality_rating'] = $data['quality_rating']['display_quality_rating'] ?? $health['quality_rating'];
                    $health['messaging_tier'] = $data['messaging_limit_tier'] ?? $health['messaging_tier'];
                    $health['verified_name']  = $data['verified_name'] ?? $health['verified_name'];
                    $health['display_phone']  = $data['display_phone_number'] ?? $health['display_phone'];

                    // Update device with fresh data
                    $device->update([
                        'quality_rating'  => $health['quality_rating'],
                        'messaging_tier'  => $health['messaging_tier'],
                        'meta_profile'    => array_merge($device->meta_profile ?? [], [
                            'verified_name'        => $health['verified_name'],
                            'display_phone_number' => $health['display_phone'],
                        ]),
                    ]);
                } else {
                    $health['error'] = $response->json('error.message', 'API call failed');
                }
            } catch (\Throwable $e) {
                Log::error("MetaHealth fetch failed for device {$device->id}: " . $e->getMessage());
                $health['error'] = $e->getMessage();
            }

            // Template counts from local DB
            $templateCounts = WabaTemplate::where('device_id', $device->id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $health['template_stats'] = [
                'APPROVED' => $templateCounts['APPROVED'] ?? 0,
                'PENDING'  => $templateCounts['PENDING']  ?? 0,
                'REJECTED' => $templateCounts['REJECTED'] ?? 0,
            ];

            // Tier → daily limit mapping
            $health['conversations_limit'] = match ($health['messaging_tier']) {
                'TIER_1K'          => 1000,
                'TIER_10K'         => 10000,
                'TIER_100K'        => 100000,
                'TIER_UNLIMITED'   => 999999,
                default            => 1000,
            };

            return $health;
        });
    }
}
