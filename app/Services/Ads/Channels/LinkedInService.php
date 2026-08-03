<?php

namespace App\Services\Ads\Channels;

use App\Models\AdMetric;
use App\Models\AdPlacement;
use App\Services\Ads\AdChannelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LinkedIn Marketing API v2 — UGC Posts for organic content and Sponsored Content for paid ads.
 * Docs: https://learn.microsoft.com/en-us/linkedin/marketing/
 */
class LinkedInService implements AdChannelInterface
{
    private const BASE_URL = 'https://api.linkedin.com/v2';
    private const ADS_URL  = 'https://api.linkedin.com/rest';

    public function launch(AdPlacement $placement): object
    {
        $creds  = $placement->channel->credentials ?? [];
        $token  = $creds['access_token'] ?? null;
        $orgId  = $creds['organization_id'] ?? null;

        if (!$token || !$orgId) {
            return (object) ['status' => false, 'external_id' => null, 'error' => 'Missing LinkedIn credentials'];
        }

        try {
            $creative = $placement->creative;
            $author   = "urn:li:organization:{$orgId}";

            // Build UGC Post
            $postPayload = [
                'author'          => $author,
                'lifecycleState'  => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => trim(($creative?->headline ? $creative->headline . "\n\n" : '') . ($creative?->body ?? '')),
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
            ];

            // Add article/link if CTA URL exists
            if ($creative?->cta_url) {
                $media = [
                    'status'      => 'READY',
                    'description' => ['text' => $creative->body ?? ''],
                    'originalUrl' => $creative->cta_url,
                    'title'       => ['text' => $creative->headline ?? $creative->cta_text ?? 'Read More'],
                ];
                $postPayload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $postPayload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [$media];
            }

            $res = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->post(self::BASE_URL . '/ugcPosts', $postPayload);

            if (!$res->successful()) {
                $error = $res->json('message') ?? $res->body();
                return (object) ['status' => false, 'external_id' => null, 'error' => $error];
            }

            $postId = $res->header('X-RestLi-Id') ?? $res->json('id') ?? 'li_' . now()->timestamp;
            $placement->update(['external_ad_id' => (string) $postId, 'status' => 'active']);

            // For sponsored placements, create a LinkedIn Ads campaign via Campaign Manager API
            if ($placement->placement_type === 'sponsored') {
                $this->launchSponsored($placement, $token, $orgId, $postId);
            }

            AdMetric::updateOrCreate(
                ['ad_placement_id' => $placement->id, 'date' => now()->toDateString()],
                ['impressions' => 0, 'reach' => 0, 'clicks' => 0, 'spend' => 0, 'channel_raw' => $res->json()]
            );

            return (object) ['status' => true, 'external_id' => (string) $postId, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('LinkedInService::launch failed', ['error' => $e->getMessage()]);
            return (object) ['status' => false, 'external_id' => null, 'error' => $e->getMessage()];
        }
    }

    public function pause(AdPlacement $placement): object
    {
        // Organic posts cannot be paused; sponsored content campaign can be paused via Ads API
        $placement->update(['status' => 'paused']);
        return (object) ['status' => true, 'error' => null];
    }

    public function syncMetrics(AdPlacement $placement): void
    {
        $creds = $placement->channel->credentials ?? [];
        $token = $creds['access_token'] ?? null;
        $orgId = $creds['organization_id'] ?? null;
        if (!$token || !$orgId || !$placement->external_ad_id) return;

        try {
            $orgUrn  = urlencode("urn:li:organization:{$orgId}");
            $postUrn = urlencode($placement->external_ad_id);

            $res = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->get(self::BASE_URL . "/organizationalEntityShareStatistics?q=organizationalEntity&organizationalEntity={$orgUrn}&shares=List({$postUrn})");

            if (!$res->successful()) return;

            $elements    = $res->json('elements.0.totalShareStatistics') ?? [];
            $impressions = (int) ($elements['impressionCount'] ?? 0);
            $clicks      = (int) ($elements['clickCount'] ?? 0);
            $engagement  = (int) ($elements['engagement'] ?? 0);

            AdMetric::updateOrCreate(
                ['ad_placement_id' => $placement->id, 'date' => now()->toDateString()],
                ['impressions' => $impressions, 'clicks' => $clicks, 'conversions' => $engagement, 'channel_raw' => $elements]
            );

            $placement->update(['last_synced_at' => now(), 'metrics_cache' => compact('impressions', 'clicks')]);

        } catch (\Throwable $e) {
            Log::error('LinkedInService::syncMetrics failed', ['error' => $e->getMessage()]);
        }
    }

    private function launchSponsored(AdPlacement $placement, string $token, string $orgId, string $shareUrn): void
    {
        try {
            $campaign   = $placement->campaign;
            $accountUrn = "urn:li:sponsoredAccount:{$orgId}";

            // Create Sponsored Campaign Group
            $groupRes = Http::withToken($token)
                ->withHeaders([
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'LinkedIn-Version'           => '202305',
                ])
                ->post(self::ADS_URL . '/adCampaignGroups', [
                    'account'         => $accountUrn,
                    'name'            => $campaign->name,
                    'status'          => 'ACTIVE',
                    'runSchedule'     => ['start' => now()->timestamp * 1000],
                    'totalBudget'     => $campaign->budget_total
                        ? ['currencyCode' => $campaign->currency, 'amount' => (string) $campaign->budget_total]
                        : null,
                ]);

            if (!$groupRes->successful()) {
                Log::warning('LinkedInService: campaign group creation failed', ['body' => $groupRes->body()]);
                return;
            }

            $groupUrn = $groupRes->header('X-RestLi-Id') ?? $groupRes->json('id');

            // Create Sponsored Campaign
            $campaignRes = Http::withToken($token)
                ->withHeaders([
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'LinkedIn-Version'           => '202305',
                ])
                ->post(self::ADS_URL . '/adCampaigns', [
                    'account'       => $accountUrn,
                    'campaignGroup' => $groupUrn,
                    'name'          => $campaign->name . ' - Sponsored',
                    'status'        => 'ACTIVE',
                    'type'          => 'SPONSORED_UPDATES',
                    'costType'      => 'CPM',
                    'dailyBudget'   => [
                        'currencyCode' => $campaign->currency,
                        'amount'       => (string) ($placement->budget_override ?? $campaign->budget_daily ?? '10'),
                    ],
                    'targeting' => [
                        'includedTargetingFacets' => [
                            'locations' => array_map(
                                fn($l) => "urn:li:country:{$l}",
                                $campaign->audience_settings['locations'] ?? []
                            ),
                        ],
                    ],
                ]);

            if (!$campaignRes->successful()) {
                Log::warning('LinkedInService: sponsored campaign creation failed', ['body' => $campaignRes->body()]);
                return;
            }

            $liCampaignUrn = $campaignRes->header('X-RestLi-Id') ?? $campaignRes->json('id');

            // Create Sponsored Creative (associate the UGC post share)
            Http::withToken($token)
                ->withHeaders([
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'LinkedIn-Version'           => '202305',
                ])
                ->post(self::ADS_URL . '/adCreatives', [
                    'campaign'  => $liCampaignUrn,
                    'reference' => $shareUrn,
                    'status'    => 'ACTIVE',
                    'type'      => 'SPONSORED_STATUS_UPDATE',
                ]);

            $placement->update(['platform_campaign_id' => $liCampaignUrn]);

        } catch (\Throwable $e) {
            Log::error('LinkedInService::launchSponsored failed', ['error' => $e->getMessage()]);
        }
    }

    public function verify(array $credentials): bool
    {
        $token = $credentials['access_token'] ?? null;
        if (!$token) return false;

        try {
            $res = Http::withToken($token)->get(self::BASE_URL . '/me');
            return $res->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
