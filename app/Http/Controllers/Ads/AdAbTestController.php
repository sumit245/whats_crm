<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Jobs\Ads\DecideAdAbWinnerJob;
use App\Jobs\Ads\LaunchAdPlacementJob;
use App\Models\AdAbTest;
use App\Models\AdAbVariant;
use App\Models\AdCampaign;
use App\Models\AdChannel;
use App\Models\AdCreative;
use App\Models\AdPlacement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdAbTestController extends Controller
{
    public function index(Request $request)
    {
        $tests = AdAbTest::where('user_id', $request->user()->id)
            ->with(['campaign', 'variants.creative', 'winner'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('theme::pages.ads.ab-tests.index', compact('tests'));
    }

    public function create(Request $request)
    {
        $campaigns = AdCampaign::where('user_id', $request->user()->id)
            ->whereIn('status', ['draft', 'active'])
            ->orderBy('name')
            ->get(['id', 'name', 'status']);

        $creatives = AdCreative::where('user_id', $request->user()->id)
            ->where('status', 'ready')
            ->orderBy('name')
            ->get(['id', 'name', 'format']);

        $channels = AdChannel::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->get();

        return view('theme::pages.ads.ab-tests.create', compact('campaigns', 'creatives', 'channels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:150',
            'ad_campaign_id'      => 'required|exists:ad_campaigns,id',
            'winner_metric'       => 'required|in:ctr,conversions,clicks',
            'decide_after_hours'  => 'required|integer|min:1|max:720',
            'variant_creatives'   => 'required|array|min:2|max:4',
            'variant_creatives.*' => 'required|exists:ad_creatives,id',
            'channel_ids'         => 'required|array|min:1',
            'channel_ids.*'       => 'exists:ad_channels,id',
        ]);

        $user     = $request->user();
        $campaign = AdCampaign::where('id', $request->ad_campaign_id)
            ->where('user_id', $user->id)->firstOrFail();

        $test = AdAbTest::create([
            'user_id'            => $user->id,
            'ad_campaign_id'     => $campaign->id,
            'name'               => $request->name,
            'winner_metric'      => $request->winner_metric,
            'decide_after_hours' => $request->decide_after_hours,
            'status'             => 'draft',
        ]);

        $labels = ['A', 'B', 'C', 'D'];
        $splits = $this->evenSplits(count($request->variant_creatives));

        foreach ($request->variant_creatives as $i => $creativeId) {
            $creative = AdCreative::where('id', $creativeId)->where('user_id', $user->id)->firstOrFail();
            AdAbVariant::create([
                'ad_ab_test_id' => $test->id,
                'label'         => $labels[$i],
                'ad_creative_id' => $creative->id,
                'budget_split'  => $splits[$i],
            ]);
        }

        return redirect()->route('ads.ab-tests.show', $test)
            ->with('alert', ['type' => 'success', 'msg' => __('A/B test created. Launch when ready.')]);
    }

    public function show(Request $request, AdAbTest $abTest)
    {
        abort_unless($abTest->user_id === $request->user()->id, 403);

        $abTest->load(['campaign', 'variants.creative', 'winner']);

        // Build variant metric summary
        $variantStats = [];
        foreach ($abTest->variants as $variant) {
            $variantStats[$variant->id] = $abTest->metricsForVariant($variant);
        }

        // Daily time-series per variant (last 14 days)
        $variantTimeSeries = [];
        foreach ($abTest->variants as $variant) {
            $placements = AdPlacement::where('ad_campaign_id', $abTest->ad_campaign_id)
                ->where('ad_ab_variant_id', $variant->id)
                ->pluck('id');

            $daily = \App\Models\AdMetric::whereIn('ad_placement_id', $placements)
                ->where('date', '>=', now()->subDays(13)->toDateString())
                ->selectRaw('date, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(spend) as spend')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $series = [];
            for ($i = 13; $i >= 0; $i--) {
                $d = now()->subDays($i)->toDateString();
                $series[] = [
                    'date'        => $d,
                    'clicks'      => (int) ($daily[$d]->clicks ?? 0),
                    'impressions' => (int) ($daily[$d]->impressions ?? 0),
                    'spend'       => (float) ($daily[$d]->spend ?? 0),
                ];
            }
            $variantTimeSeries[$variant->id] = $series;
        }

        return view('theme::pages.ads.ab-tests.show', compact('abTest', 'variantStats', 'variantTimeSeries'));
    }

    public function launch(Request $request, AdAbTest $abTest)
    {
        abort_unless($abTest->user_id === $request->user()->id, 403);

        if ($abTest->status !== 'draft') {
            return response()->json(['error' => true, 'msg' => __('Test is not in draft status.')]);
        }

        $request->validate([
            'channel_ids'   => 'required|array|min:1',
            'channel_ids.*' => 'exists:ad_channels,id',
            'placement_type' => 'required|string',
        ]);

        $channels = AdChannel::whereIn('id', $request->channel_ids)
            ->where('user_id', $request->user()->id)
            ->get();

        $abTest->load('variants');
        $campaign = $abTest->campaign;

        foreach ($abTest->variants as $variant) {
            $splitBudget = $campaign->budget_daily
                ? round($campaign->budget_daily * $variant->budget_split / 100, 2)
                : null;

            foreach ($channels as $channel) {
                $placement = AdPlacement::create([
                    'ad_campaign_id'  => $campaign->id,
                    'ad_channel_id'   => $channel->id,
                    'ad_creative_id'  => $variant->ad_creative_id,
                    'ad_ab_variant_id' => $variant->id,
                    'placement_type'  => $request->placement_type,
                    'status'          => 'pending',
                    'budget_override' => $splitBudget,
                ]);

                LaunchAdPlacementJob::dispatch($placement->id);
            }
        }

        $decidesAt = now()->addHours($abTest->decide_after_hours);
        DecideAdAbWinnerJob::dispatch($abTest->id)->delay($decidesAt);

        $abTest->update(['status' => 'running', 'launched_at' => now()]);
        if ($campaign->status === 'draft') {
            $campaign->update(['status' => 'active']);
        }

        return response()->json([
            'error' => false,
            'msg'   => __('A/B test launched. Winner will be decided in :hours hours.', ['hours' => $abTest->decide_after_hours]),
        ]);
    }

    public function decide(Request $request, AdAbTest $abTest)
    {
        abort_unless($abTest->user_id === $request->user()->id, 403);

        if ($abTest->status !== 'running') {
            return response()->json(['error' => true, 'msg' => __('Test is not running.')]);
        }

        DecideAdAbWinnerJob::dispatchSync($abTest->id);

        return response()->json(['error' => false, 'msg' => __('Winner decided. Page will reload.')]);
    }

    public function destroy(Request $request, AdAbTest $abTest)
    {
        abort_unless($abTest->user_id === $request->user()->id, 403);
        $abTest->update(['status' => 'cancelled']);

        return redirect()->route('ads.ab-tests.index')
            ->with('alert', ['type' => 'success', 'msg' => __('A/B test cancelled.')]);
    }

    private function evenSplits(int $count): array
    {
        $base = intdiv(100, $count);
        $remainder = 100 - $base * $count;
        $splits = array_fill(0, $count, $base);
        $splits[0] += $remainder;
        return $splits;
    }
}
