<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdChannel;
use App\Models\AdCreative;
use App\Models\AdPlacement;
use App\Models\Tag;
use App\Services\Ads\AdsOrchestrator;
use Illuminate\Http\Request;

class AdCampaignsController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = AdCampaign::where('user_id', $request->user()->id)
            ->withCount('placements')
            ->with(['placements.channel'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('theme::pages.ads.campaigns.index', compact('campaigns'));
    }

    public function create(Request $request)
    {
        $channels  = AdChannel::where('user_id', $request->user()->id)->where('status', 'active')->get();
        $segments  = Tag::where('user_id', $request->user()->id)->get();
        $creatives = AdCreative::where('user_id', $request->user()->id)->where('status', 'ready')->get();

        return view('theme::pages.ads.campaigns.create', compact('channels', 'segments', 'creatives'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'objective'   => 'required|in:awareness,traffic,engagement,leads,ctwa,sales',
            'channel_ids' => 'required|array|min:1',
            'channel_ids.*' => 'exists:ad_channels,id',
        ]);

        $user = $request->user();

        $campaign = AdCampaign::create([
            'user_id'           => $user->id,
            'name'              => $request->name,
            'objective'         => $request->objective,
            'status'            => 'draft',
            'budget_total'      => $request->budget_total ?: null,
            'budget_daily'      => $request->budget_daily ?: null,
            'currency'          => $request->currency ?? 'USD',
            'bid_strategy'      => $request->bid_strategy ?? 'lowest_cost',
            'start_at'          => $request->start_at ?: null,
            'end_at'            => $request->end_at ?: null,
            'target_segment_id' => $request->target_segment_id ?: null,
            'audience_settings' => [
                'age_min'   => $request->age_min ?? 18,
                'age_max'   => $request->age_max ?? 65,
                'genders'   => $request->genders ?? [],
                'locations' => $request->locations ?? [],
                'interests' => $request->interests ?? [],
            ],
        ]);

        // Create inline creative if no existing one selected
        $creativeId = $request->creative_id ?: null;
        if (!$creativeId && $request->filled('creative_name')) {
            $mediaPaths = [];
            if ($request->hasFile('creative_media')) {
                foreach ($request->file('creative_media') as $file) {
                    $mediaPaths[] = $file->store('ads/media', 'public');
                }
            }
            $carouselCards = [];
            if ($request->filled('carousel_cards')) {
                $carouselCards = json_decode($request->carousel_cards, true) ?? [];
            }
            $creative = AdCreative::create([
                'user_id'        => $user->id,
                'ad_campaign_id' => $campaign->id,
                'name'           => $request->creative_name,
                'format'         => $request->format ?? 'text',
                'headline'       => $request->creative_headline,
                'body'           => $request->creative_body,
                'cta_text'       => $request->creative_cta_text,
                'cta_url'        => $request->creative_cta_url,
                'media_paths'    => $mediaPaths ?: null,
                'carousel_cards' => $carouselCards ?: null,
                'status'         => 'ready',
            ]);
            $creativeId = $creative->id;
        }

        // Create a placement for each selected channel
        $channels = AdChannel::whereIn('id', $request->channel_ids)
            ->where('user_id', $user->id)
            ->get();

        foreach ($channels as $channel) {
            AdPlacement::create([
                'ad_campaign_id' => $campaign->id,
                'ad_channel_id'  => $channel->id,
                'ad_creative_id' => $creativeId,
                'placement_type' => $request->input("placement_type_{$channel->id}", 'feed'),
                'status'         => 'pending',
                'budget_override' => $request->input("budget_{$channel->id}") ?: null,
            ]);
        }

        // If user clicked "Save & Launch", kick off placement jobs immediately
        if ($request->input('action') === 'launch' && $campaign->placements()->exists()) {
            (new AdsOrchestrator())->launch($campaign);
            $campaign->update(['status' => 'active']);
            return redirect()->route('ads.campaigns.show', $campaign)
                ->with('alert', ['type' => 'success', 'msg' => __('Campaign launched! Placements are being submitted to each channel.')]);
        }

        return redirect()->route('ads.campaigns.show', $campaign)
            ->with('alert', ['type' => 'success', 'msg' => __('Campaign saved as draft. Assign a creative and launch when ready.')]);
    }

    public function show(Request $request, AdCampaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);

        $campaign->load([
            'placements.channel',
            'placements.creative',
            'placements.metrics' => fn($q) => $q->orderBy('date')->take(30),
            'creatives',
        ]);

        // 30-day chart data for this campaign
        $chartDates = collect();
        $metricsMap = \App\Models\AdMetric::whereHas('placement', fn($q) => $q->where('ad_campaign_id', $campaign->id))
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('date, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(spend) as spend')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $chartDates->push([
                'date'        => $d,
                'impressions' => (int)   ($metricsMap[$d]->impressions ?? 0),
                'clicks'      => (int)   ($metricsMap[$d]->clicks ?? 0),
                'spend'       => (float) ($metricsMap[$d]->spend ?? 0),
            ]);
        }

        $availableChannels = AdChannel::where('user_id', $request->user()->id)->where('status', 'active')->get();
        $availableCreatives = AdCreative::where('user_id', $request->user()->id)->where('status', 'ready')->get();

        return view('theme::pages.ads.campaigns.show', compact(
            'campaign', 'chartDates', 'availableChannels', 'availableCreatives'
        ));
    }

    public function update(Request $request, AdCampaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);

        $campaign->update($request->only([
            'name', 'objective', 'budget_total', 'budget_daily',
            'currency', 'bid_strategy', 'start_at', 'end_at',
        ]));

        return redirect()->route('ads.campaigns.show', $campaign)
            ->with('alert', ['type' => 'success', 'msg' => __('Campaign updated.')]);
    }

    public function destroy(Request $request, AdCampaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);
        $campaign->delete();

        return redirect()->route('ads.campaigns.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Campaign deleted.')]);
    }

    public function launch(Request $request, AdCampaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);

        $pendingPlacements = $campaign->placements()->where('status', 'pending')->count();
        if ($pendingPlacements === 0) {
            return response()->json(['error' => true, 'msg' => __('No pending placements to launch.')]);
        }

        (new AdsOrchestrator())->launch($campaign);

        $campaign->update(['status' => 'active']);

        return response()->json(['error' => false, 'msg' => __('Campaign launch initiated. Placements are being submitted to each channel.')]);
    }

    public function pause(Request $request, AdCampaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);

        (new AdsOrchestrator())->pause($campaign);
        $campaign->update(['status' => 'paused']);

        return response()->json(['error' => false, 'msg' => __('Campaign paused on all channels.')]);
    }

    public function syncMetrics(Request $request, AdCampaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);

        (new AdsOrchestrator())->syncMetrics($campaign);

        return response()->json(['error' => false, 'msg' => __('Metrics sync queued.')]);
    }

    public function retryPlacement(Request $request, AdCampaign $campaign, AdPlacement $placement)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);
        abort_unless($placement->ad_campaign_id === $campaign->id, 404);

        $placement->update(['status' => 'pending']);
        \App\Jobs\Ads\LaunchAdPlacementJob::dispatch($placement->id);

        return response()->json(['error' => false, 'msg' => __('Placement queued for retry.')]);
    }

    public function addPlacement(Request $request, AdCampaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);

        $request->validate([
            'ad_channel_id'  => 'required|exists:ad_channels,id',
            'placement_type' => 'required|string',
            'ad_creative_id' => 'nullable|exists:ad_creatives,id',
        ]);

        // Ensure channel belongs to this user
        $channel = AdChannel::where('id', $request->ad_channel_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $placement = AdPlacement::create([
            'ad_campaign_id' => $campaign->id,
            'ad_channel_id'  => $channel->id,
            'ad_creative_id' => $request->ad_creative_id ?: $campaign->placements()->whereNotNull('ad_creative_id')->value('ad_creative_id'),
            'placement_type' => $request->placement_type,
            'status'         => 'pending',
            'budget_override' => $request->budget_override ?: null,
        ]);

        if ($campaign->status === 'active') {
            \App\Jobs\Ads\LaunchAdPlacementJob::dispatch($placement->id);
        }

        return response()->json(['error' => false, 'msg' => __('Placement added.'), 'placement_id' => $placement->id]);
    }

    public function assignCreative(Request $request, AdCampaign $campaign, AdPlacement $placement)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);
        abort_unless($placement->ad_campaign_id === $campaign->id, 404);

        $request->validate(['ad_creative_id' => 'required|exists:ad_creatives,id']);

        $creative = AdCreative::where('id', $request->ad_creative_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $placement->update(['ad_creative_id' => $creative->id]);

        return response()->json(['error' => false, 'msg' => __('Creative assigned.')]);
    }
}
