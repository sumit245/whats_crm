<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Conversation;
use App\Models\MessageDeliveryEvent;
use App\Models\TrackedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // Overall delivery stats from delivery events
        $stats = DB::table('message_delivery_events as mde')
            ->join('blasts', 'blasts.id', '=', 'mde.blast_id')
            ->join('campaigns', 'campaigns.id', '=', 'blasts.campaign_id')
            ->where('campaigns.user_id', $userId)
            ->selectRaw('
                COUNT(DISTINCT blasts.id) as total_messages,
                SUM(CASE WHEN mde.status = "delivered" OR mde.status = "read" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN mde.status = "read" THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN mde.status = "failed" THEN 1 ELSE 0 END) as failed_count
            ')
            ->first();

        $totalSent     = $stats->total_messages ?? 0;
        $delivered     = $stats->delivered ?? 0;
        $readCount     = $stats->read_count ?? 0;
        $deliveryRate  = $totalSent > 0 ? round($delivered / $totalSent * 100, 1) : 0;
        $readRate      = $totalSent > 0 ? round($readCount / $totalSent * 100, 1) : 0;

        // Click stats — unique contacts who clicked at least one link
        $clickStats = DB::table('link_clicks as lc')
            ->join('tracked_links as tl', 'tl.id', '=', 'lc.tracked_link_id')
            ->join('campaigns', 'campaigns.id', '=', 'tl.campaign_id')
            ->where('campaigns.user_id', $userId)
            ->selectRaw('COUNT(DISTINCT lc.contact_number) as unique_clickers, COUNT(*) as total_clicks')
            ->first();

        $totalClicks    = $clickStats->total_clicks ?? 0;
        $uniqueClickers = $clickStats->unique_clickers ?? 0;
        $clickRate      = $totalSent > 0 ? round($uniqueClickers / $totalSent * 100, 1) : 0;

        // Campaign total count
        $totalCampaigns = Campaign::where('user_id', $userId)->count();

        // Time-series: last 30 days sent/delivered/read
        $timeSeries = DB::table('message_delivery_events as mde')
            ->join('blasts', 'blasts.id', '=', 'mde.blast_id')
            ->join('campaigns', 'campaigns.id', '=', 'blasts.campaign_id')
            ->where('campaigns.user_id', $userId)
            ->where('mde.event_timestamp', '>=', now()->subDays(30))
            ->selectRaw('
                DATE(mde.event_timestamp) as date,
                SUM(CASE WHEN mde.status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN mde.status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN mde.status = "read" THEN 1 ELSE 0 END) as read_count
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Per-campaign breakdown with click counts
        $campaigns = Campaign::where('user_id', $userId)
            ->with(['device'])
            ->withCount([
                'blasts',
                'blasts as blasts_success' => fn ($q) => $q->where('status', 'success'),
                'blasts as blasts_failed'  => fn ($q) => $q->where('status', 'failed'),
            ])
            ->latest()
            ->paginate(10);

        // Attach click counts per campaign (batch query, not N+1)
        $campaignIds  = $campaigns->pluck('id');
        $clicksPerCampaign = DB::table('link_clicks as lc')
            ->join('tracked_links as tl', 'tl.id', '=', 'lc.tracked_link_id')
            ->whereIn('tl.campaign_id', $campaignIds)
            ->selectRaw('tl.campaign_id, COUNT(DISTINCT lc.contact_number) as unique_clickers, COUNT(*) as total_clicks')
            ->groupBy('tl.campaign_id')
            ->get()
            ->keyBy('campaign_id');

        $campaigns->each(function ($c) use ($clicksPerCampaign) {
            $c->click_data = $clicksPerCampaign->get($c->id);
        });

        // Agent WFM: FRT and AHT per agent (Feature 3 §5.3)
        $agentMetrics = DB::table('conversations')
            ->join('agents', 'agents.id', '=', 'conversations.assigned_agent_id')
            ->where('conversations.user_id', $userId)
            ->whereNotNull('conversations.assigned_agent_id')
            ->whereNotNull('conversations.resolved_at')
            ->selectRaw('
                agents.id as agent_id,
                agents.name as agent_name,
                COUNT(*) as total_resolved,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, conversations.assigned_at, conversations.first_response_at)), 1) as avg_frt_minutes,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, conversations.assigned_at, conversations.resolved_at)), 1) as avg_aht_minutes,
                SUM(CASE WHEN conversations.sla_breached = 1 THEN 1 ELSE 0 END) as sla_breaches
            ')
            ->groupBy('agents.id', 'agents.name')
            ->orderByDesc('total_resolved')
            ->get();

        return view('theme::pages.analytics.index', compact(
            'totalCampaigns', 'totalSent', 'deliveryRate', 'readRate',
            'totalClicks', 'uniqueClickers', 'clickRate',
            'timeSeries', 'campaigns', 'agentMetrics'
        ));
    }

    public function campaignDetail(Request $request, $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)->findOrFail($id);

        $breakdown = DB::table('message_delivery_events as mde')
            ->join('blasts', 'blasts.id', '=', 'mde.blast_id')
            ->where('blasts.campaign_id', $campaign->id)
            ->selectRaw('mde.status, COUNT(*) as count')
            ->groupBy('mde.status')
            ->pluck('count', 'status');

        $clickCount = DB::table('link_clicks as lc')
            ->join('tracked_links as tl', 'tl.id', '=', 'lc.tracked_link_id')
            ->where('tl.campaign_id', $campaign->id)
            ->count();

        $uniqueClickers = DB::table('link_clicks as lc')
            ->join('tracked_links as tl', 'tl.id', '=', 'lc.tracked_link_id')
            ->where('tl.campaign_id', $campaign->id)
            ->distinct('lc.contact_number')
            ->count('lc.contact_number');

        return response()->json([
            'campaign'        => $campaign->name,
            'labels'          => ['Sent', 'Delivered', 'Read', 'Failed', 'Clicked'],
            'data'            => [
                $breakdown['sent']      ?? 0,
                $breakdown['delivered'] ?? 0,
                $breakdown['read']      ?? 0,
                $breakdown['failed']    ?? 0,
                $uniqueClickers,
            ],
            'total_clicks'    => $clickCount,
            'unique_clickers' => $uniqueClickers,
            'has_links'       => $uniqueClickers > 0 || TrackedLink::where('campaign_id', $campaign->id)->exists(),
            'links_url'       => route('analytics.campaign.links', $campaign->id),
        ]);
    }

    public function campaignLinks(Request $request, $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)->findOrFail($id);

        $links = TrackedLink::where('campaign_id', $campaign->id)
            ->withCount('clicks')
            ->withMax('clicks', 'clicked_at')
            ->orderByDesc('clicks_count')
            ->get();

        return response()->json([
            'campaign' => $campaign->name,
            'links'    => $links->map(fn ($l) => [
                'original_url'  => $l->original_url,
                'slug'          => $l->slug,
                'total_clicks'  => $l->clicks_count,
                'last_click'    => $l->clicks_max_clicked_at,
            ]),
        ]);
    }
}
