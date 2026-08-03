<?php

namespace App\Services\Ads\Channels;

use App\Models\AdMetric;
use App\Models\AdPlacement;
use App\Models\Contact;
use App\Models\TrackedLink;
use App\Services\Ads\AdChannelInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email broadcast campaigns via per-user SMTP/API credentials.
 * Uses existing TrackedLink model for CTR tracking.
 */
class EmailAdsService implements AdChannelInterface
{
    public function launch(AdPlacement $placement): object
    {
        $creds = $placement->channel->credentials ?? [];
        if (empty($creds['host']) && empty($creds['api_key'])) {
            return (object) ['status' => false, 'external_id' => null, 'error' => 'Missing email credentials'];
        }

        try {
            $campaign = $placement->campaign;
            $creative = $placement->creative;

            // Build recipient list from segment or all contacts
            $userId    = $campaign->user_id;
            $contacts  = $this->resolveContacts($campaign);

            if ($contacts->isEmpty()) {
                return (object) ['status' => false, 'external_id' => null, 'error' => 'No contacts in target audience'];
            }

            // Wrap CTA URL with tracking
            $trackedUrl = null;
            if ($creative?->cta_url) {
                $tracked    = TrackedLink::findOrMake($userId, null, $creative->cta_url);
                $trackedUrl = url('/t/' . $tracked->slug);
            }

            // Configure mailer dynamically with user's credentials
            $mailerConfig = $this->buildMailerConfig($creds);
            config(['mail.mailers.user_smtp' => $mailerConfig]);
            config(['mail.from.address' => $creds['from_address'] ?? config('mail.from.address')]);
            config(['mail.from.name'    => $creds['from_name']    ?? config('mail.from.name')]);

            $sent = 0;
            $failed = 0;

            foreach ($contacts as $contact) {
                try {
                    Mail::mailer('user_smtp')->send(
                        [],
                        ['creative' => $creative, 'trackedUrl' => $trackedUrl, 'contact' => $contact],
                        function ($msg) use ($contact, $creative, $campaign) {
                            $msg->to($contact->email ?? $contact->number)
                                ->subject($creative?->headline ?? $campaign->name)
                                ->html($this->buildEmailHtml($creative));
                        }
                    );
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('EmailAdsService: send failed for contact', ['error' => $e->getMessage()]);
                    $failed++;
                }
            }

            $externalId = 'email_' . now()->timestamp;
            $placement->update(['external_ad_id' => $externalId, 'status' => 'active']);

            // Log initial metric
            AdMetric::updateOrCreate(
                ['ad_placement_id' => $placement->id, 'date' => now()->toDateString()],
                ['impressions' => $sent, 'reach' => $sent, 'clicks' => 0, 'spend' => 0,
                 'channel_raw' => compact('sent', 'failed')]
            );

            return (object) ['status' => true, 'external_id' => $externalId, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('EmailAdsService::launch failed', ['error' => $e->getMessage()]);
            return (object) ['status' => false, 'external_id' => null, 'error' => $e->getMessage()];
        }
    }

    public function pause(AdPlacement $placement): object
    {
        $placement->update(['status' => 'paused']);
        return (object) ['status' => true, 'error' => null];
    }

    public function syncMetrics(AdPlacement $placement): void
    {
        // Pull click counts from TrackedLink / LinkClick for emails that used tracked URLs
        try {
            // Find tracked links associated with this campaign
            $campaignId = $placement->ad_campaign_id;
            $links = TrackedLink::where('campaign_id', $campaignId)->withCount('clicks')->get();
            $totalClicks = $links->sum('clicks_count');

            if ($totalClicks > 0) {
                $existing = AdMetric::where('ad_placement_id', $placement->id)->first();
                if ($existing) {
                    $existing->update(['clicks' => $totalClicks]);
                }
            }

            $placement->update(['last_synced_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('EmailAdsService::syncMetrics failed', ['error' => $e->getMessage()]);
        }
    }

    public function verify(array $credentials): bool
    {
        // SMTP verify: attempt connection. For simplicity, just check required fields are present.
        if (!empty($credentials['api_key'])) return true;
        return !empty($credentials['host']) && !empty($credentials['username']);
    }

    private function resolveContacts($campaign)
    {
        if ($campaign->target_segment_id) {
            return Contact::where('user_id', $campaign->user_id)
                ->whereHas('tags', fn($q) => $q->where('tags.id', $campaign->target_segment_id))
                ->whereNotNull('email')
                ->get();
        }
        return Contact::where('user_id', $campaign->user_id)->whereNotNull('email')->limit(10000)->get();
    }

    private function buildMailerConfig(array $creds): array
    {
        return [
            'transport' => 'smtp',
            'host'      => $creds['host']     ?? 'smtp.mailgun.org',
            'port'      => $creds['port']     ?? 587,
            'username'  => $creds['username'] ?? '',
            'password'  => $creds['password'] ?? '',
            'encryption' => 'tls',
        ];
    }

    private function buildEmailHtml($creative): string
    {
        $headline = $creative?->headline ? "<h2>{$creative->headline}</h2>" : '';
        $body     = nl2br(htmlspecialchars($creative?->body ?? ''));
        $cta      = $creative?->cta_url
            ? "<p><a href='{$creative->cta_url}' style='display:inline-block;padding:10px 20px;background:#25d366;color:#fff;text-decoration:none;border-radius:4px'>{$creative?->cta_text}</a></p>"
            : '';

        return "<!DOCTYPE html><html><body style='font-family:sans-serif;max-width:600px;margin:auto;padding:20px'>
            {$headline}<div>{$body}</div>{$cta}
        </body></html>";
    }
}
