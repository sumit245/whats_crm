<?php

namespace App\Services\Ads\Channels;

use App\Models\AdMetric;
use App\Models\AdPlacement;
use App\Services\Ads\AdChannelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Instagram organic posts, stories, and reels via Instagram Graph API (Content Publishing API).
 * Sponsored/boosted posts are handled by MetaAdsService with instagram placement targeting.
 * Docs: https://developers.facebook.com/docs/instagram-api/reference/ig-media
 */
class InstagramService implements AdChannelInterface
{
    private const GRAPH_VERSION = 'v20.0';
    private const BASE_URL      = 'https://graph.facebook.com';

    public function launch(AdPlacement $placement): object
    {
        $creds = $placement->channel->credentials ?? [];
        $token = $creds['access_token'] ?? null;
        $igAccountId = $creds['ig_account_id'] ?? null;

        if (!$token || !$igAccountId) {
            return (object) ['status' => false, 'external_id' => null, 'error' => 'Missing Instagram credentials'];
        }

        try {
            $creative = $placement->creative;

            // Step 1: Create media container
            $containerPayload = [
                'caption'     => trim(($creative?->headline ? $creative->headline . "\n\n" : '') . ($creative?->body ?? '')),
                'access_token' => $token,
            ];

            if ($placement->placement_type === 'stories') {
                $mediaUrl = $creative?->firstMediaUrl();
                if ($mediaUrl) {
                    $containerPayload['image_url'] = $mediaUrl;
                    $containerPayload['media_type'] = 'STORIES';
                }
            } elseif ($creative?->format === 'carousel' && !empty($creative->carousel_cards)) {
                // Carousel: create children first
                $childIds = [];
                foreach ($creative->carousel_cards as $card) {
                    $childRes = Http::withToken($token)->post(
                        self::BASE_URL . '/' . self::GRAPH_VERSION . "/{$igAccountId}/media",
                        ['image_url' => $card['image'] ?? '', 'is_carousel_item' => true, 'access_token' => $token]
                    );
                    if ($childRes->successful()) {
                        $childIds[] = $childRes->json('id');
                    }
                }
                $containerPayload['media_type']   = 'CAROUSEL';
                $containerPayload['children']      = implode(',', $childIds);
            } elseif ($creative?->format === 'video' || $placement->placement_type === 'reels') {
                $containerPayload['video_url']  = $creative?->firstMediaUrl() ?? '';
                $containerPayload['media_type'] = $placement->placement_type === 'reels' ? 'REELS' : 'VIDEO';
            } else {
                $containerPayload['image_url'] = $creative?->firstMediaUrl() ?? '';
            }

            $containerRes = Http::post(
                self::BASE_URL . '/' . self::GRAPH_VERSION . "/{$igAccountId}/media",
                $containerPayload
            );

            if (!$containerRes->successful()) {
                return (object) ['status' => false, 'external_id' => null, 'error' => $containerRes->json('error.message') ?? 'Container creation failed'];
            }

            $containerId = $containerRes->json('id');

            // Step 2: Publish
            $publishRes = Http::post(
                self::BASE_URL . '/' . self::GRAPH_VERSION . "/{$igAccountId}/media_publish",
                ['creation_id' => $containerId, 'access_token' => $token]
            );

            if (!$publishRes->successful()) {
                return (object) ['status' => false, 'external_id' => null, 'error' => $publishRes->json('error.message') ?? 'Publish failed'];
            }

            $mediaId = $publishRes->json('id');
            $placement->update(['external_ad_id' => $mediaId, 'status' => 'active']);

            return (object) ['status' => true, 'external_id' => $mediaId, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('InstagramService::launch failed', ['error' => $e->getMessage()]);
            return (object) ['status' => false, 'external_id' => null, 'error' => $e->getMessage()];
        }
    }

    public function pause(AdPlacement $placement): object
    {
        // Instagram organic posts cannot be "paused" — mark locally only
        $placement->update(['status' => 'paused']);
        return (object) ['status' => true, 'error' => null];
    }

    public function syncMetrics(AdPlacement $placement): void
    {
        $creds = $placement->channel->credentials ?? [];
        $token = $creds['access_token'] ?? null;
        if (!$token || !$placement->external_ad_id) return;

        try {
            $res = Http::withToken($token)->get(
                self::BASE_URL . '/' . self::GRAPH_VERSION . "/{$placement->external_ad_id}/insights",
                ['metric' => 'impressions,reach,total_interactions', 'period' => 'lifetime']
            );

            if (!$res->successful()) return;

            $data = collect($res->json('data', []))->keyBy('name');
            $impressions  = (int) ($data['impressions']?->values[0]['value'] ?? 0);
            $reach        = (int) ($data['reach']?->values[0]['value'] ?? 0);
            $interactions = (int) ($data['total_interactions']?->values[0]['value'] ?? 0);

            AdMetric::updateOrCreate(
                ['ad_placement_id' => $placement->id, 'date' => $placement->created_at->toDateString()],
                ['impressions' => $impressions, 'reach' => $reach, 'clicks' => $interactions, 'channel_raw' => $res->json()]
            );

            $placement->update([
                'metrics_cache'  => compact('impressions', 'reach', 'interactions'),
                'last_synced_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('InstagramService::syncMetrics failed', ['error' => $e->getMessage()]);
        }
    }

    public function verify(array $credentials): bool
    {
        try {
            $res = Http::withToken($credentials['access_token'] ?? '')->get(
                self::BASE_URL . '/' . self::GRAPH_VERSION . '/me'
            );
            return $res->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
