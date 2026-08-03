<?php

namespace App\Services\Ads\Channels;

use App\Models\AdMetric;
use App\Models\AdPlacement;
use App\Services\Ads\AdChannelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta Click-to-WhatsApp (CTWA) Ads via Meta Marketing API.
 * Campaign objective = MESSAGES, CTA = WHATSAPP_MESSAGE.
 * Docs: https://developers.facebook.com/docs/whatsapp/business-management-api/ads
 */
class MetaCtwaService implements AdChannelInterface
{
    private const GRAPH_VERSION = 'v20.0';
    private const BASE_URL      = 'https://graph.facebook.com';

    public function launch(AdPlacement $placement): object
    {
        $creds = $placement->channel->credentials ?? [];
        $token = $creds['access_token'] ?? null;
        $adAccountId = $creds['ad_account_id'] ?? null;

        if (!$token || !$adAccountId) {
            return (object) ['status' => false, 'external_id' => null, 'error' => 'Missing Meta credentials'];
        }

        try {
            $campaign = $placement->campaign;
            $creative = $placement->creative;

            // Step 1: Create campaign
            $campaignRes = $this->post("/act_{$adAccountId}/campaigns", $token, [
                'name'              => $campaign->name,
                'objective'         => 'MESSAGES',
                'status'            => 'ACTIVE',
                'special_ad_categories' => [],
            ]);

            if (!$campaignRes->status) {
                return (object) ['status' => false, 'external_id' => null, 'error' => $campaignRes->error];
            }

            $platformCampaignId = $campaignRes->data['id'];

            // Step 2: Create ad set
            $adsetRes = $this->post("/act_{$adAccountId}/adsets", $token, [
                'name'                => $campaign->name . ' - Ad Set',
                'campaign_id'         => $platformCampaignId,
                'daily_budget'        => $placement->budget_override
                    ? (int) ($placement->budget_override * 100)
                    : (int) (($campaign->budget_daily ?? 10) * 100),
                'billing_event'       => 'IMPRESSIONS',
                'optimization_goal'   => 'CONVERSATIONS',
                'bid_strategy'        => strtoupper($campaign->bid_strategy),
                'targeting'           => $this->buildTargeting($campaign->audience_settings ?? []),
                'status'              => 'ACTIVE',
                'start_time'          => $campaign->start_at?->toIso8601String() ?? now()->toIso8601String(),
            ]);

            if (!$adsetRes->status) {
                return (object) ['status' => false, 'external_id' => null, 'error' => $adsetRes->error];
            }

            $platformAdsetId = $adsetRes->data['id'];

            // Step 3: Create ad creative
            $adCreativeRes = $this->post("/act_{$adAccountId}/adcreatives", $token, [
                'name'          => ($creative?->name ?? $campaign->name) . ' Creative',
                'object_story_spec' => [
                    'page_id'   => $creds['page_id'] ?? '',
                    'link_data' => [
                        'message'     => $creative?->body ?? $campaign->name,
                        'name'        => $creative?->headline ?? $campaign->name,
                        'call_to_action' => [
                            'type'  => 'WHATSAPP_MESSAGE',
                            'value' => ['app_destination' => 'WHATSAPP'],
                        ],
                    ],
                ],
            ]);

            if (!$adCreativeRes->status) {
                return (object) ['status' => false, 'external_id' => null, 'error' => $adCreativeRes->error];
            }

            // Step 4: Create ad
            $adRes = $this->post("/act_{$adAccountId}/ads", $token, [
                'name'        => $campaign->name . ' - Ad',
                'adset_id'    => $platformAdsetId,
                'creative'    => ['creative_id' => $adCreativeRes->data['id']],
                'status'      => 'ACTIVE',
            ]);

            if (!$adRes->status) {
                return (object) ['status' => false, 'external_id' => null, 'error' => $adRes->error];
            }

            $placement->update([
                'platform_campaign_id' => $platformCampaignId,
                'platform_adset_id'    => $platformAdsetId,
                'external_ad_id'       => $adRes->data['id'],
                'status'               => 'active',
            ]);

            return (object) ['status' => true, 'external_id' => $adRes->data['id'], 'error' => null];

        } catch (\Throwable $e) {
            Log::error('MetaCtwaService::launch failed', ['error' => $e->getMessage(), 'placement' => $placement->id]);
            return (object) ['status' => false, 'external_id' => null, 'error' => $e->getMessage()];
        }
    }

    public function pause(AdPlacement $placement): object
    {
        $creds = $placement->channel->credentials ?? [];
        $token = $creds['access_token'] ?? null;
        if (!$token || !$placement->external_ad_id) {
            return (object) ['status' => false, 'error' => 'Missing credentials or ad ID'];
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
            $res = Http::withToken($token)
                ->get(self::BASE_URL . '/' . self::GRAPH_VERSION . "/{$placement->external_ad_id}/insights", [
                    'fields' => 'impressions,reach,clicks,spend,conversions,date_start,date_stop',
                    'time_increment' => 1,
                    'date_preset'    => 'last_30d',
                ]);

            if (!$res->successful()) return;

            foreach ($res->json('data', []) as $row) {
                $impressions = (int) ($row['impressions'] ?? 0);
                $clicks      = (int) ($row['clicks'] ?? 0);
                $spend       = (float) ($row['spend'] ?? 0);

                AdMetric::updateOrCreate(
                    ['ad_placement_id' => $placement->id, 'date' => $row['date_start']],
                    [
                        'impressions'  => $impressions,
                        'reach'        => (int) ($row['reach'] ?? 0),
                        'clicks'       => $clicks,
                        'spend'        => $spend,
                        'conversions'  => (int) ($row['conversions'][0]['value'] ?? 0),
                        'channel_raw'  => $row,
                    ]
                );
            }

            // Update metrics_cache with totals
            $totals = $placement->metrics()->selectRaw('
                SUM(impressions) as impressions, SUM(clicks) as clicks,
                SUM(spend) as spend, SUM(conversions) as conversions,
                AVG(ctr) as ctr, AVG(cpm) as cpm, AVG(cpc) as cpc
            ')->first();

            $placement->update([
                'metrics_cache'  => $totals->toArray(),
                'last_synced_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('MetaCtwaService::syncMetrics failed', ['error' => $e->getMessage()]);
        }
    }

    public function verify(array $credentials): bool
    {
        $token = $credentials['access_token'] ?? null;
        if (!$token) return false;

        try {
            $res = Http::withToken($token)->get(self::BASE_URL . '/' . self::GRAPH_VERSION . '/me');
            return $res->successful() && isset($res->json()['id']);
        } catch (\Throwable) {
            return false;
        }
    }

    private function post(string $path, string $token, array $payload): object
    {
        try {
            $res = Http::withToken($token)
                ->post(self::BASE_URL . '/' . self::GRAPH_VERSION . $path, $payload);

            if ($res->successful()) {
                return (object) ['status' => true, 'data' => $res->json(), 'error' => null];
            }

            $error = $res->json('error.message') ?? 'Meta API error';
            Log::error("MetaCtwaService POST {$path} failed", ['body' => $res->body()]);
            return (object) ['status' => false, 'data' => null, 'error' => $error];

        } catch (\Throwable $e) {
            return (object) ['status' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    private function buildTargeting(array $settings): array
    {
        $targeting = [
            'age_min' => $settings['age_min'] ?? 18,
            'age_max' => $settings['age_max'] ?? 65,
        ];

        if (!empty($settings['genders'])) {
            $targeting['genders'] = $settings['genders'];
        }

        if (!empty($settings['locations'])) {
            $targeting['geo_locations'] = ['countries' => $settings['locations']];
        }

        if (!empty($settings['interests'])) {
            $targeting['flexible_spec'] = [['interests' => array_map(
                fn($id) => ['id' => $id],
                $settings['interests']
            )]];
        }

        return $targeting;
    }
}
