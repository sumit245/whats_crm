<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdChannel;
use App\Models\AdMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $channelCount  = AdChannel::where('user_id', $user->id)->where('status', 'active')->count();
        $campaignCount = AdCampaign::where('user_id', $user->id)->count();

        $activeCampaigns = AdCampaign::where('user_id', $user->id)
            ->where('status', 'active')
            ->withCount('placements')
            ->latest()
            ->take(5)
            ->get();

        // 30-day aggregated metrics across all placements for this user
        $metrics30 = AdMetric::whereHas('placement.campaign', fn($q) => $q->where('user_id', $user->id))
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('
                SUM(impressions) as total_impressions,
                SUM(clicks)      as total_clicks,
                SUM(spend)       as total_spend,
                SUM(conversions) as total_conversions
            ')
            ->first();

        // Daily chart data (last 30 days)
        $chartData = AdMetric::whereHas('placement.campaign', fn($q) => $q->where('user_id', $user->id))
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('date, SUM(spend) as spend, SUM(clicks) as clicks, SUM(impressions) as impressions')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Build complete 30-day series (fill missing days with zeros)
        $dates = collect();
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $dates->push([
                'date'        => $d,
                'spend'       => (float) ($chartData[$d]->spend ?? 0),
                'clicks'      => (int)   ($chartData[$d]->clicks ?? 0),
                'impressions' => (int)   ($chartData[$d]->impressions ?? 0),
            ]);
        }

        $recentCampaigns = AdCampaign::where('user_id', $user->id)
            ->with(['placements.channel'])
            ->latest()
            ->take(10)
            ->get();

        $connectedChannels = AdChannel::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        return view('theme::pages.ads.dashboard', compact(
            'channelCount', 'campaignCount', 'activeCampaigns',
            'metrics30', 'dates', 'recentCampaigns', 'connectedChannels'
        ));
    }
}
