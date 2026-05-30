<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\MessageDeliveryEvent;
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

        // Per-campaign breakdown
        $campaigns = Campaign::where('user_id', $userId)
            ->with(['device'])
            ->withCount([
                'blasts',
                'blasts as blasts_success' => fn ($q) => $q->where('status', 'success'),
                'blasts as blasts_failed'  => fn ($q) => $q->where('status', 'failed'),
            ])
            ->latest()
            ->paginate(10);

        return view('theme::pages.analytics.index', compact(
            'totalCampaigns', 'totalSent', 'deliveryRate', 'readRate',
            'timeSeries', 'campaigns'
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

        return response()->json([
            'campaign' => $campaign->name,
            'labels'   => ['Sent', 'Delivered', 'Read', 'Failed'],
            'data'     => [
                $breakdown['sent']      ?? 0,
                $breakdown['delivered'] ?? 0,
                $breakdown['read']      ?? 0,
                $breakdown['failed']    ?? 0,
            ],
        ]);
    }
}
