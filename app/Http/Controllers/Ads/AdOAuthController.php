<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdOAuthController extends Controller
{
    // ── Meta OAuth ────────────────────────────────────────────────────────────

    public function metaRedirect(Request $request)
    {
        $appId       = config('ads.meta.app_id');
        $redirectUri = config('ads.meta.redirect_uri') ?: url('/ads/oauth/meta/callback');
        $scopes      = config('ads.meta.scopes');

        if (!$appId) {
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('META_ADS_APP_ID is not configured in .env')]);
        }

        $state = Str::random(32);
        session(['ads_oauth_meta_state' => $state, 'ads_oauth_meta_user' => $request->user()->id]);

        $url = 'https://www.facebook.com/v20.0/dialog/oauth?' . http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => $redirectUri,
            'scope'         => $scopes,
            'response_type' => 'code',
            'state'         => $state,
        ]);

        return redirect($url);
    }

    public function metaCallback(Request $request)
    {
        // CSRF state check
        if ($request->state !== session('ads_oauth_meta_state')) {
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('OAuth state mismatch. Please try again.')]);
        }

        if ($request->has('error')) {
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('Meta authorisation denied: ') . $request->error_description]);
        }

        $appId       = config('ads.meta.app_id');
        $appSecret   = config('ads.meta.app_secret');
        $redirectUri = config('ads.meta.redirect_uri') ?: url('/ads/oauth/meta/callback');

        // Step 1: Exchange code for short-lived token
        $tokenRes = Http::get('https://graph.facebook.com/v20.0/oauth/access_token', [
            'client_id'     => $appId,
            'client_secret' => $appSecret,
            'redirect_uri'  => $redirectUri,
            'code'          => $request->code,
        ]);

        if (!$tokenRes->successful() || !$tokenRes->json('access_token')) {
            Log::error('Meta OAuth token exchange failed', ['body' => $tokenRes->body()]);
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('Failed to exchange Meta auth code for token.')]);
        }

        $shortToken = $tokenRes->json('access_token');

        // Step 2: Extend to long-lived token (60-day)
        $longRes = Http::get('https://graph.facebook.com/v20.0/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $appSecret,
            'fb_exchange_token' => $shortToken,
        ]);

        $accessToken = $longRes->json('access_token') ?? $shortToken;
        $expiresIn   = $longRes->json('expires_in') ?? null;

        // Step 3: Fetch ad accounts
        $adAccountsRes = Http::withToken($accessToken)
            ->get('https://graph.facebook.com/v20.0/me/adaccounts', [
                'fields' => 'id,name,account_status,currency',
                'limit'  => 50,
            ]);

        $adAccounts = $adAccountsRes->successful()
            ? collect($adAccountsRes->json('data', []))->where('account_status', 1)->values()->all()
            : [];

        // Step 4: Fetch pages
        $pagesRes = Http::withToken($accessToken)
            ->get('https://graph.facebook.com/v20.0/me/accounts', [
                'fields' => 'id,name,instagram_business_account',
                'limit'  => 50,
            ]);

        $pages = $pagesRes->successful() ? $pagesRes->json('data', []) : [];

        // Step 5: Fetch user info for display name
        $meRes = Http::withToken($accessToken)->get('https://graph.facebook.com/v20.0/me', ['fields' => 'id,name']);
        $userName = $meRes->json('name') ?? 'Meta Account';

        // Store token temporarily in session for the setup step
        session([
            'ads_oauth_meta_token'      => $accessToken,
            'ads_oauth_meta_expires_in' => $expiresIn,
            'ads_oauth_meta_user_name'  => $userName,
        ]);

        session()->forget('ads_oauth_meta_state');

        return view('theme::pages.ads.channels.setup-meta', compact('adAccounts', 'pages', 'userName'));
    }

    public function metaSetup(Request $request)
    {
        $request->validate([
            'channel_type'      => 'required|in:meta,facebook,instagram',
            'name'              => 'required|string|max:100',
            'meta_ad_account_id' => 'required|string',
        ]);

        $accessToken = session('ads_oauth_meta_token');
        if (!$accessToken) {
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('OAuth session expired. Please connect again.')]);
        }

        $user = $request->user();
        $type = $request->channel_type;

        // Detect IG account from selected page
        $igAccountId = $request->meta_ig_account_id;
        if (!$igAccountId && $request->meta_page_id) {
            $igRes = Http::withToken($accessToken)->get(
                'https://graph.facebook.com/v20.0/' . $request->meta_page_id,
                ['fields' => 'instagram_business_account']
            );
            $igAccountId = $igRes->json('instagram_business_account.id');
        }

        $credentials = [
            'access_token'    => $accessToken,
            'ad_account_id'   => $request->meta_ad_account_id,
            'page_id'         => $request->meta_page_id,
            'ig_account_id'   => $igAccountId,
            'app_id'          => config('ads.meta.app_id'),
            'app_secret'      => config('ads.meta.app_secret'),
            'token_expires_in' => session('ads_oauth_meta_expires_in'),
        ];

        $channel = AdChannel::create([
            'user_id'          => $user->id,
            'type'             => $type,
            'name'             => $request->name,
            'status'           => 'active',
            'credentials'      => $credentials,
            'metadata'         => ['user_name' => session('ads_oauth_meta_user_name')],
            'last_verified_at' => now(),
        ]);

        session()->forget(['ads_oauth_meta_token', 'ads_oauth_meta_expires_in', 'ads_oauth_meta_user_name']);

        return redirect()->route('ads.channels.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Meta channel connected successfully via OAuth.')]);
    }

    // ── LinkedIn OAuth ────────────────────────────────────────────────────────

    public function linkedinRedirect(Request $request)
    {
        $clientId    = config('ads.linkedin.client_id');
        $redirectUri = config('ads.linkedin.redirect_uri') ?: url('/ads/oauth/linkedin/callback');
        $scopes      = config('ads.linkedin.scopes');

        if (!$clientId) {
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('LINKEDIN_ADS_CLIENT_ID is not configured in .env')]);
        }

        $state = Str::random(32);
        session(['ads_oauth_linkedin_state' => $state, 'ads_oauth_linkedin_user' => $request->user()->id]);

        $url = 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'scope'         => $scopes,
            'state'         => $state,
        ]);

        return redirect($url);
    }

    public function linkedinCallback(Request $request)
    {
        if ($request->state !== session('ads_oauth_linkedin_state')) {
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('OAuth state mismatch. Please try again.')]);
        }

        if ($request->has('error')) {
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('LinkedIn authorisation denied: ') . $request->error_description]);
        }

        $clientId     = config('ads.linkedin.client_id');
        $clientSecret = config('ads.linkedin.client_secret');
        $redirectUri  = config('ads.linkedin.redirect_uri') ?: url('/ads/oauth/linkedin/callback');

        // Exchange code for access token
        $tokenRes = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type'    => 'authorization_code',
            'code'          => $request->code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (!$tokenRes->successful() || !$tokenRes->json('access_token')) {
            Log::error('LinkedIn OAuth token exchange failed', ['body' => $tokenRes->body()]);
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('Failed to exchange LinkedIn auth code for token.')]);
        }

        $accessToken = $tokenRes->json('access_token');
        $expiresIn   = $tokenRes->json('expires_in');

        // Fetch profile for display name
        $meRes = Http::withToken($accessToken)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->get('https://api.linkedin.com/v2/me', ['fields' => 'id,localizedFirstName,localizedLastName']);
        $displayName = trim(($meRes->json('localizedFirstName') ?? '') . ' ' . ($meRes->json('localizedLastName') ?? ''));

        // Fetch organizations (companies) this user can manage
        $orgsRes = Http::withToken($accessToken)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->get('https://api.linkedin.com/v2/organizationAcls', [
                'q'      => 'roleAssignee',
                'fields' => 'organization,role',
            ]);

        $orgIds = [];
        if ($orgsRes->successful()) {
            foreach ($orgsRes->json('elements', []) as $el) {
                $orgUrn = $el['organization'] ?? null;
                if ($orgUrn) {
                    $orgId = str_replace('urn:li:organization:', '', $orgUrn);
                    $orgIds[] = ['id' => $orgId, 'urn' => $orgUrn, 'role' => $el['role'] ?? 'MEMBER'];
                }
            }
        }

        // Enrich with org names
        $organizations = [];
        foreach ($orgIds as $org) {
            $orgRes = Http::withToken($accessToken)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->get('https://api.linkedin.com/v2/organizations/' . $org['id'], ['fields' => 'id,localizedName']);
            if ($orgRes->successful()) {
                $organizations[] = [
                    'id'   => $org['id'],
                    'urn'  => $org['urn'],
                    'name' => $orgRes->json('localizedName') ?? ('Organization ' . $org['id']),
                    'role' => $org['role'],
                ];
            }
        }

        session([
            'ads_oauth_linkedin_token'       => $accessToken,
            'ads_oauth_linkedin_expires_in'  => $expiresIn,
            'ads_oauth_linkedin_display_name' => $displayName,
        ]);
        session()->forget('ads_oauth_linkedin_state');

        return view('theme::pages.ads.channels.setup-linkedin', compact('organizations', 'displayName'));
    }

    public function linkedinSetup(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'organization_id' => 'required|string',
        ]);

        $accessToken = session('ads_oauth_linkedin_token');
        if (!$accessToken) {
            return redirect()->route('ads.channels.index')
                ->with('alert', ['type' => 'danger', 'msg' => __('OAuth session expired. Please connect again.')]);
        }

        $channel = AdChannel::create([
            'user_id'          => $request->user()->id,
            'type'             => 'linkedin',
            'name'             => $request->name,
            'status'           => 'active',
            'credentials'      => [
                'access_token'    => $accessToken,
                'organization_id' => $request->organization_id,
                'expires_in'      => session('ads_oauth_linkedin_expires_in'),
            ],
            'metadata'         => ['display_name' => session('ads_oauth_linkedin_display_name')],
            'last_verified_at' => now(),
        ]);

        session()->forget(['ads_oauth_linkedin_token', 'ads_oauth_linkedin_expires_in', 'ads_oauth_linkedin_display_name']);

        return redirect()->route('ads.channels.index')
            ->with('alert', ['type' => 'success', 'msg' => __('LinkedIn channel connected successfully via OAuth.')]);
    }
}
