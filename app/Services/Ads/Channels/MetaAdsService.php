<?php

namespace App\Services\Ads\Channels;

use App\Models\AdMetric;
use App\Models\AdPlacement;
use App\Services\Ads\AdChannelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Facebook Feed / Stories / Reels Sponsored Ads via Meta Marketing API.
 * Objective varies by campaign (REACH, TRAFFIC, ENGAGEMENT, LEAD_GENERATION).
 */
class MetaAdsService implements AdChannelInterface
{
    private const GRAPH_VERSION = 'v20.0';
    private const BASE_URL      = 'https://graph.facebook.com';

    private const OBJECTIVE_MAP = [
        'awareness'  => 'BRAND_AWARENESS',
        'traffic'    => 'LINK_CLICKS',
        'engagement' => 'POST_ENGAGEMENT',
        'leads'      => 'LEAD_GENERATION',
        'sales'      => 'CONVERSIONS',
        'ctwa'       => 'MESSAGES',
    ];

    public function launch(AdPlacement $placement): object
    {
        $creds = $placement->channel->credentials ?? [];
        $token = $creds['access_token'] ?? null;
        $adAccountId = $creds['ad_account_id'] ?? null;

        if (!$token || !$adAccountId) {
            return (object) ['status' => false, 'external_id' => null, 'error' => 'Missing Facebook credentials'];
        }

        try {
            $campaign  = $placement->campaign;
            $creative  = $placement->creative;
            $objective = self::OBJECTIVE_MAP[$campaign->objective] ?? 'LINK_CLICKS';

            // Campaign
            $campaignRes = $this->post("/act_{$adAccountId}/campaigns", $token, [
                'name'                  => $campaign->name,
                'objective'             => $objective,
                'status'                => 'ACTIVE',
                'special_ad_categories' => [],
            ]);
            if (!$campaignRes->status) return $campaignRes;

            $platformCampaignId = $campaignRes->data['id'];

            // Ad Set with placement-specific targeting
            $placements = match($placement->placement_type) {
                'stories' => ['publisher_platforms' => ['facebook'], 'facebook_positions' => ['story']],
                'reels'   => ['publisher_platforms' => ['facebook'], 'facebook_positions' => ['reels']],
                default   => ['publisher_platforms' => ['facebook'], 'facebook_positions' => ['feed']],
            };

            $adsetRes = $this->post("/act_{$adAccountId}/adsets", $token, [
                'name'              => $campaign->name . ' - Ad Set',
                'campaign_id'       => $platformCampaignId,
                'daily_budget'      => (int) (($placement->budget_override ?? $campaign->budget_daily ?? 10) * 100),
                'billing_event'     => 'IMPRESSIONS',
                'optimization_goal' => $objective === 'LINK_CLICKS' ? 'LINK_CLICKS' : 'REACH',
                'targeting'         => array_merge(
                    $placements,
                    $this->buildTargeting($campaign->audience_settings ?? [])
                ),
                'status'            => 'ACTIVE',
            ]);
            if (!$adsetRes->status) return $adsetRes;

            $platformAdsetId = $adsetRes->data['id'];

            // Ad Creative
            $creativePayload = [
                'name'              => ($creative?->name ?? $campaign->name) . ' Creative',
                'object_story_spec' => [
                    'page_id'   => $creds['page_id'] ?? '',
                    'link_data' => [
                        'message' => $creative?->body ?? '',
                        'name'    => $creative?->headline ?? $campaign->name,
                        'link'    => $creative?->cta_url ?? 'https://example.com',
                        'call_to_action' => [
                            'type'  => 'LEARN_MORE',
                            'value' => ['link' => $creative?->cta_url ?? 'https://example.com'],
                        ],
                    ],
                ],
            ];

            if ($creative?->firstMediaUrl()) {
                $creativePayload['object_story_spec']['link_data']['picture'] = $creative->firstMediaUrl();
            }

            if ($creative?->format === 'carousel' && !empty($creative->carousel_cards)) {
                unset($creativePayload['object_story_spec']['link_data']);
                $creativePayload['object_story_spec']['link_data'] = [
                    'child_attachments' => array_map(fn($card) => [
                        'name'        => $card['headline'] ?? '',
                        'description' => $card['description'] ?? '',
                        'link'        => $card['cta_url'] ?? '',
                        'picture'     => $card['image'] ?? '',
                    ], $creative->carousel_cards),
                    'call_to_action' => ['type' => 'LEARN_MORE'],
                ];
            }

            $adCreativeRes = $this->post("/act_{$adAccountId}/adcreatives", $token, $creativePayload);
            if (!$adCreativeRes->status) return $adCreativeRes;

            // Ad
            $adRes = $this->post("/act_{$adAccountId}/ads", $token, [
                'name'     => $campaign->name . ' - Ad',
                'adset_id' => $platformAdsetId,
                'creative' => ['creative_id' => $adCreativeRes->data['id']],
                'status'   => 'ACTIVE',
            ]);
            if (!$adRes->status) return $adRes;

            $placement->update([
                'platform_campaign_id' => $platformCampaignId,
                'platform_adset_id'    => $platformAdsetId,
                'external_ad_id'       => $adRes->data['id'],
                'status'               => 'active',
            ]);

            return (object) ['status' => true, 'external_id' => $adRes->data['id'], 'error' => null];

        } catch (\Throwable $e) {
            Log::error('MetaAdsService::launch failed', ['error' => $e->getMessage()]);
            return (object) ['status' => false, 'external_id' => null, 'error' => $e->getMessage()];
        }
    }

    public function pause(AdPlacement $placement): object
    {
        $creds = $placement->channel->credentials ?? [];
        $token = $creds['access_token'] ?? null;
        if (!$token || !$placement->external_ad_id) {
            return (object) ['status' => false, 'error' => 'Missing credentials'];
        }

        $res = $this->post("/{$placement->external_ad_id}", $token, ['status' => 'PAUSED']);
        return (object) ['status' => $res->status, 'error' => $res->error ?? null];
    }

    public function syncMetrics(AdPlacement $placement): void
    {
        $creds = $placement->channel->credentials ?? [];
        $token = $creds['access_token'] ?? null;
        if (!$token || !$placement->external_ad_id) return;

        try {
            $res = Http::withToken($token)->get(
                self::BASE_URL . '/' . self::GRAPH_VERSION . "/{$placement->external_ad_id}/insights",
                ['fields' => 'impressions,reach,clicks,spend,date_start', 'time_increment' => 1, 'date_preset' => 'last_30d']
            );

            if (!$res->successful()) return;

            foreach ($res->json('data', []) as $row) {
                AdMetric::updateOrCreate(
                    ['ad_placement_id' => $placement->id, 'date' => $row['date_start']],
                    [
                        'impressions' => (int) ($row['impressions'] ?? 0),
                        'reach'       => (int) ($row['reach'] ?? 0),
                        'clicks'      => (int) ($row['clicks'] ?? 0),
                        'spend'       => (float) ($row['spend'] ?? 0),
                        'channel_raw' => $row,
                    ]
                );
            }

            $totals = $placement->metrics()->selectRaw('SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(spend) as spend')->first();
            $placement->update(['metrics_cache' => $totals->toArray(), 'last_synced_at' => now()]);

        } catch (\Throwable $e) {
            Log::error('MetaAdsService::syncMetrics failed', ['error' => $e->getMessage()]);
        }
    }

    public function verify(array $credentials): bool
    {
        try {
            $res = Http::withToken($credentials['access_token'] ?? '')->get(self::BASE_URL . '/' . self::GRAPH_VERSION . '/me');
            return $res->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function post(string $path, string $token, array $payload): object
    {
        try {
            $res = Http::withToken($token)->post(self::BASE_URL . '/' . self::GRAPH_VERSION . $path, $payload);
            if ($res->successful()) return (object) ['status' => true, 'data' => $res->json(), 'error' => null];
            return (object) ['status' => false, 'data' => null, 'error' => $res->json('error.message') ?? 'API error'];
        } catch (\Throwable $e) {
            return (object) ['status' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    private function buildTargeting(array $settings): array
    {
        $t = ['age_min' => $settings['age_min'] ?? 18, 'age_max' => $settings['age_max'] ?? 65];
        if (!empty($settings['genders']))   $t['genders'] = $settings['genders'];
        if (!empty($settings['locations'])) $t['geo_locations'] = ['countries' => $settings['locations']];
        return $t;
    }
}
