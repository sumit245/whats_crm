<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdChannel;
use App\Models\AdMetric;
use App\Models\AdPlacement;
use Illuminate\Http\Request;

class AdAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $dateFrom   = $request->date_from   ?? now()->subDays(29)->toDateString();
        $dateTo     = $request->date_to     ?? now()->toDateString();
        $channelType = $request->channel_type;
        $campaignId  = $request->campaign_id;

        $metricsQuery = AdMetric::whereHas('placement.campaign', fn($q) => $q->where('user_id', $user->id))
            ->whereBetween('date', [$dateFrom, $dateTo]);

        if ($channelType) {
            $metricsQuery->whereHas('placement.channel', fn($q) => $q->where('type', $channelType));
        }
        if ($campaignId) {
            $metricsQuery->whereHas('placement', fn($q) => $q->where('ad_campaign_id', $campaignId));
        }

        $totals = (clone $metricsQuery)->selectRaw('
            SUM(impressions) as impressions,
            SUM(reach)       as reach,
            SUM(clicks)      as clicks,
            SUM(spend)       as spend,
            SUM(conversions) as conversions
        ')->first();

        $avgCtr = ($totals->impressions > 0)
            ? round($totals->clicks / $totals->impressions * 100, 2)
            : 0;
        $avgCpm = ($totals->impressions > 0)
            ? round($totals->spend / $totals->impressions * 1000, 2)
            : 0;
        $avgCpc = ($totals->clicks > 0)
            ? round($totals->spend / $totals->clicks, 2)
            : 0;
        $roas = ($totals->spend > 0 && $totals->conversions > 0)
            ? round($totals->conversions / $totals->spend, 2)
            : 0;

        // Daily time-series chart
        $chartRows = (clone $metricsQuery)
            ->selectRaw('date, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(spend) as spend')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartDates = collect();
        $current = \Carbon\Carbon::parse($dateFrom);
        $end     = \Carbon\Carbon::parse($dateTo);
        while ($current->lte($end)) {
            $d = $current->toDateString();
            $chartDates->push([
                'date'        => $d,
                'impressions' => (int)   ($chartRows[$d]->impressions ?? 0),
                'clicks'      => (int)   ($chartRows[$d]->clicks ?? 0),
                'spend'       => (float) ($chartRows[$d]->spend ?? 0),
            ]);
            $current->addDay();
        }

        // Per-channel-type breakdown (for stacked breakdown chart)
        $channelBreakdown = AdMetric::whereHas('placement.campaign', fn($q) => $q->where('user_id', $user->id))
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($campaignId, fn($q) => $q->whereHas('placement', fn($p) => $p->where('ad_campaign_id', $campaignId)))
            ->join('ad_placements', 'ad_metrics.ad_placement_id', '=', 'ad_placements.id')
            ->join('ad_channels',   'ad_placements.ad_channel_id', '=', 'ad_channels.id')
            ->selectRaw('ad_channels.type, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(spend) as spend')
            ->groupBy('ad_channels.type')
            ->get();

        // Per-placement breakdown
        $placements = AdPlacement::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
            ->with(['channel', 'campaign'])
            ->withSum(['metrics as total_impressions' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'impressions')
            ->withSum(['metrics as total_clicks'      => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'clicks')
            ->withSum(['metrics as total_spend'       => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'spend')
            ->withSum(['metrics as total_conversions' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'conversions')
            ->when($channelType, fn($q) => $q->whereHas('channel', fn($c) => $c->where('type', $channelType)))
            ->when($campaignId,  fn($q) => $q->where('ad_campaign_id', $campaignId))
            ->orderByDesc('total_spend')
            ->paginate(25)
            ->withQueryString();

        $campaigns = AdCampaign::where('user_id', $user->id)->orderBy('name')->get(['id', 'name']);

        return view('theme::pages.ads.analytics', compact(
            'totals', 'roas', 'avgCtr', 'avgCpm', 'avgCpc',
            'chartDates', 'channelBreakdown',
            'placements', 'dateFrom', 'dateTo', 'channelType', 'campaignId', 'campaigns'
        ));
    }

    public function placement(Request $request, AdPlacement $placement)
    {
        abort_unless($placement->campaign->user_id === $request->user()->id, 403);

        $metrics = $placement->metrics()
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->orderBy('date')
            ->get(['date', 'impressions', 'clicks', 'spend', 'ctr', 'cpm', 'cpc']);

        return response()->json([
            'placement' => [
                'id'             => $placement->id,
                'channel'        => $placement->channel?->typeLabel(),
                'channel_icon'   => $placement->channel?->typeIcon(),
                'placement_type' => $placement->placement_type,
                'status'         => $placement->status,
                'campaign'       => $placement->campaign?->name,
                'external_id'    => $placement->external_ad_id,
            ],
            'metrics' => $metrics,
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();

        $dateFrom    = $request->date_from   ?? now()->subDays(29)->toDateString();
        $dateTo      = $request->date_to     ?? now()->toDateString();
        $channelType = $request->channel_type;
        $campaignId  = $request->campaign_id;

        $rows = AdPlacement::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
            ->with(['channel', 'campaign'])
            ->withSum(['metrics as total_impressions' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'impressions')
            ->withSum(['metrics as total_clicks'      => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'clicks')
            ->withSum(['metrics as total_spend'       => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'spend')
            ->withSum(['metrics as total_conversions' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'conversions')
            ->when($channelType, fn($q) => $q->whereHas('channel', fn($c) => $c->where('type', $channelType)))
            ->when($campaignId,  fn($q) => $q->where('ad_campaign_id', $campaignId))
            ->orderByDesc('total_spend')
            ->get();

        $headers = ['Campaign', 'Channel', 'Placement Type', 'Status', 'Impressions', 'Clicks', 'CTR %', 'Spend', 'CPM', 'CPC', 'Conversions', 'External ID'];

        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $pl) {
            $impr   = (int) ($pl->total_impressions ?? 0);
            $clicks = (int) ($pl->total_clicks ?? 0);
            $spend  = (float) ($pl->total_spend ?? 0);
            $convs  = (int) ($pl->total_conversions ?? 0);
            $ctr    = $impr  > 0 ? round($clicks / $impr * 100, 2) : 0;
            $cpm    = $impr  > 0 ? round($spend / $impr * 1000, 4) : 0;
            $cpc    = $clicks > 0 ? round($spend / $clicks, 4) : 0;

            $csv .= implode(',', [
                '"' . str_replace('"', '""', $pl->campaign?->name ?? '') . '"',
                $pl->channel?->typeLabel() ?? '—',
                ucfirst(str_replace('_', ' ', $pl->placement_type)),
                $pl->status,
                $impr, $clicks, $ctr,
                number_format($spend, 2),
                $cpm, $cpc, $convs,
                '"' . ($pl->external_ad_id ?? '') . '"',
            ]) . "\n";
        }

        $filename = 'ads-analytics-' . $dateFrom . '-to-' . $dateTo . '.csv';
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function syncAll(Request $request)
    {
        \App\Jobs\Ads\SyncAdMetricsJob::dispatch();
        return response()->json(['error' => false, 'msg' => __('Global metrics sync queued.')]);
    }
}
