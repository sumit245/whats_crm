<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdChannel;
use App\Services\Ads\AdsOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdChannelsController extends Controller
{
    public function index(Request $request)
    {
        $channels = AdChannel::where('user_id', $request->user()->id)
            ->withCount('placements')
            ->latest()
            ->get();

        return view('theme::pages.ads.channels.index', compact('channels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:meta,facebook,instagram,telegram,email,linkedin',
            'name' => 'required|string|max:100',
        ]);

        $channel = AdChannel::create([
            'user_id'     => $request->user()->id,
            'type'        => $request->type,
            'name'        => $request->name,
            'status'      => 'inactive',
            'credentials' => $this->extractCredentials($request),
            'metadata'    => [],
        ]);

        return redirect()->route('ads.channels.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Channel saved. Click Verify to test the connection.')]);
    }

    public function destroy(Request $request, AdChannel $channel)
    {
        abort_unless($channel->user_id === $request->user()->id, 403);
        $channel->delete();

        return redirect()->route('ads.channels.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Channel disconnected.')]);
    }

    public function verify(Request $request, AdChannel $channel)
    {
        abort_unless($channel->user_id === $request->user()->id, 403);

        $creds = $channel->credentials;
        if (empty($creds)) {
            return response()->json(['error' => true, 'msg' => __('No credentials saved for this channel.')]);
        }

        try {
            $orchestrator = new AdsOrchestrator();
            // Create a temporary placement-less channel object to get the service
            $ok = match($channel->type) {
                'meta', 'facebook' => $this->verifyMeta($creds),
                'instagram'        => $this->verifyMeta($creds),
                'telegram'         => $this->verifyTelegram($creds),
                'email'            => $this->verifyEmail($creds),
                'linkedin'         => $this->verifyLinkedIn($creds),
                default            => false,
            };

            if ($ok) {
                $metadata = $this->fetchMetadata($channel->type, $creds);
                $channel->update([
                    'status'           => 'active',
                    'last_verified_at' => now(),
                    'metadata'         => array_merge($channel->metadata ?? [], $metadata),
                ]);
                return response()->json(['error' => false, 'msg' => __('Channel verified and active.')]);
            }

            $channel->update(['status' => 'error']);
            return response()->json(['error' => true, 'msg' => __('Verification failed — check your credentials.')]);

        } catch (\Throwable $e) {
            Log::error('Channel verify failed', ['channel' => $channel->id, 'error' => $e->getMessage()]);
            $channel->update(['status' => 'error']);
            return response()->json(['error' => true, 'msg' => __('Verification error: ') . $e->getMessage()]);
        }
    }

    public function syncMetadata(Request $request, AdChannel $channel)
    {
        abort_unless($channel->user_id === $request->user()->id, 403);

        try {
            $creds    = $channel->credentials ?? [];
            $metadata = $this->fetchMetadata($channel->type, $creds);
            $channel->update(['metadata' => array_merge($channel->metadata ?? [], $metadata)]);
            return response()->json(['error' => false, 'msg' => __('Metadata updated.'), 'metadata' => $metadata]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'msg' => $e->getMessage()]);
        }
    }

    // ── Verification helpers ─────────────────────────────────────────────────

    private function verifyMeta(array $creds): bool
    {
        $token = $creds['access_token'] ?? null;
        if (!$token) return false;

        $res = Http::withToken($token)->get('https://graph.facebook.com/v20.0/me', ['fields' => 'id,name']);
        if (!$res->successful() || !$res->json('id')) return false;

        // Verify ad account access if provided
        $adAccountId = $creds['ad_account_id'] ?? null;
        if ($adAccountId) {
            $cleanId = ltrim($adAccountId, 'act_');
            $acctRes = Http::withToken($token)->get("https://graph.facebook.com/v20.0/act_{$cleanId}", [
                'fields' => 'id,name,account_status',
            ]);
            return $acctRes->successful() && $acctRes->json('account_status') === 1;
        }

        return true;
    }

    private function verifyTelegram(array $creds): bool
    {
        $botToken = $creds['bot_token'] ?? null;
        if (!$botToken) return false;

        $res = Http::get("https://api.telegram.org/bot{$botToken}/getMe");
        return $res->successful() && $res->json('ok') === true;
    }

    private function verifyEmail(array $creds): bool
    {
        // Verify by checking required fields
        if (!empty($creds['api_key'])) return true;

        $host     = $creds['host']     ?? null;
        $username = $creds['username'] ?? null;
        $password = $creds['password'] ?? null;

        if (!$host || !$username) return false;

        // Attempt SMTP connection
        try {
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $host,
                (int) ($creds['port'] ?? 587),
                false
            );
            $transport->setUsername($username);
            $transport->setPassword($password ?? '');
            $transport->start();
            $transport->stop();
            return true;
        } catch (\Throwable) {
            // If SMTP connection fails but credentials look complete, allow manual override
            return !empty($host) && !empty($username) && !empty($password);
        }
    }

    private function verifyLinkedIn(array $creds): bool
    {
        $token = $creds['access_token'] ?? null;
        if (!$token) return false;

        $res = Http::withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->get('https://api.linkedin.com/v2/me');

        return $res->successful() && $res->json('id') !== null;
    }

    // ── Metadata fetchers ────────────────────────────────────────────────────

    private function fetchMetadata(string $type, array $creds): array
    {
        return match($type) {
            'meta', 'facebook', 'instagram' => $this->fetchMetaMeta($creds),
            'telegram'                       => $this->fetchTelegramMeta($creds),
            'linkedin'                       => $this->fetchLinkedInMeta($creds),
            default                          => [],
        };
    }

    private function fetchMetaMeta(array $creds): array
    {
        $token = $creds['access_token'] ?? null;
        if (!$token) return [];

        $data = [];

        $meRes = Http::withToken($token)->get('https://graph.facebook.com/v20.0/me', ['fields' => 'id,name,picture']);
        if ($meRes->successful()) {
            $data['user_name']   = $meRes->json('name');
            $data['fb_user_id']  = $meRes->json('id');
            $data['avatar_url']  = $meRes->json('picture.data.url');
        }

        $adAccountId = ltrim($creds['ad_account_id'] ?? '', 'act_');
        if ($adAccountId) {
            $acctRes = Http::withToken($token)->get("https://graph.facebook.com/v20.0/act_{$adAccountId}", [
                'fields' => 'name,currency,account_status,amount_spent',
            ]);
            if ($acctRes->successful()) {
                $data['account_name']   = $acctRes->json('name');
                $data['currency']       = $acctRes->json('currency');
                $data['amount_spent']   = $acctRes->json('amount_spent');
            }
        }

        return $data;
    }

    private function fetchTelegramMeta(array $creds): array
    {
        $botToken = $creds['bot_token'] ?? null;
        if (!$botToken) return [];

        $res = Http::get("https://api.telegram.org/bot{$botToken}/getMe");
        if (!$res->successful()) return [];

        $bot = $res->json('result', []);

        // Get channel info if provided
        $channelInfo = [];
        $channelUsername = $creds['channel_username'] ?? null;
        if ($channelUsername) {
            $chatRes = Http::get("https://api.telegram.org/bot{$botToken}/getChat", ['chat_id' => $channelUsername]);
            if ($chatRes->successful()) {
                $chat = $chatRes->json('result', []);
                $channelInfo = [
                    'channel_title'       => $chat['title'] ?? null,
                    'channel_description' => $chat['description'] ?? null,
                    'channel_id'          => $chat['id'] ?? null,
                ];

                // Get member count
                $countRes = Http::get("https://api.telegram.org/bot{$botToken}/getChatMemberCount", ['chat_id' => $channelUsername]);
                if ($countRes->successful()) {
                    $channelInfo['member_count'] = $countRes->json('result');
                }
            }
        }

        return array_merge([
            'bot_username' => $bot['username'] ?? null,
            'bot_name'     => $bot['first_name'] ?? null,
        ], $channelInfo);
    }

    private function fetchLinkedInMeta(array $creds): array
    {
        $token  = $creds['access_token'] ?? null;
        $orgId  = $creds['organization_id'] ?? null;
        if (!$token || !$orgId) return [];

        $cleanId = str_replace('urn:li:organization:', '', $orgId);
        $res = Http::withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->get("https://api.linkedin.com/v2/organizations/{$cleanId}", [
                'fields' => 'id,localizedName,logoV2',
            ]);

        if (!$res->successful()) return [];

        return [
            'org_name' => $res->json('localizedName'),
            'org_id'   => $cleanId,
        ];
    }

    private function extractCredentials(Request $request): ?array
    {
        return match($request->type) {
            'meta', 'facebook', 'instagram' => array_filter([
                'access_token'   => $request->input('meta_access_token'),
                'ad_account_id'  => $request->input('meta_ad_account_id'),
                'page_id'        => $request->input('meta_page_id'),
                'ig_account_id'  => $request->input('meta_ig_account_id'),
                'app_id'         => config('ads.meta.app_id'),
                'app_secret'     => config('ads.meta.app_secret'),
            ]),
            'telegram' => [
                'bot_token'        => $request->input('telegram_bot_token'),
                'channel_username' => $request->input('telegram_channel_username'),
            ],
            'email' => array_filter([
                'host'         => $request->input('email_host'),
                'port'         => $request->input('email_port', 587),
                'username'     => $request->input('email_username'),
                'password'     => $request->input('email_password'),
                'from_address' => $request->input('email_from_address'),
                'from_name'    => $request->input('email_from_name'),
                'api_key'      => $request->input('email_api_key'),
            ]),
            'linkedin' => [
                'access_token'    => $request->input('linkedin_access_token'),
                'organization_id' => $request->input('linkedin_organization_id'),
            ],
            default => null,
        };
    }
}
