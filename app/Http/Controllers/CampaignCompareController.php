<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignCompareController extends Controller
{
    public function index(Request $request)
    {
        $userId    = $request->user()->id;
        $campaigns = Campaign::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'status', 'schedule', 'type', 'created_at']);

        // Pre-selected IDs from query string (max 5)
        $selected = array_slice(
            array_filter(explode(',', $request->query('ids', '')), 'is_numeric'),
            0, 5
        );

        return view('theme::pages.campaigns.compare', compact('campaigns', 'selected'));
    }

    public function compare(Request $request)
    {
        $userId = $request->user()->id;
        $ids    = $request->input('ids', []);

        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }
        $ids = array_unique(array_filter(array_map('intval', $ids)));

        if (count($ids) < 2 || count($ids) > 5) {
            return response()->json(['error' => 'Select 2–5 campaigns.'], 422);
        }

        // Verify ownership
        $campaigns = Campaign::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->with(['phonebook', 'wabaTemplate'])
            ->get()
            ->keyBy('id');

        if ($campaigns->count() !== count($ids)) {
            return response()->json(['error' => 'One or more campaigns not found.'], 404);
        }

        // ── Per-campaign delivery stats ──────────────────────────────────────
        $deliveryStats = DB::table('message_delivery_events as mde')
            ->join('blasts', 'blasts.id', '=', 'mde.blast_id')
            ->whereIn('blasts.campaign_id', $ids)
            ->selectRaw('
                blasts.campaign_id,
                COUNT(DISTINCT blasts.id) AS total,
                SUM(CASE WHEN mde.status IN ("delivered","read") THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN mde.status = "read" THEN 1 ELSE 0 END) AS read_count,
                SUM(CASE WHEN mde.status = "failed" THEN 1 ELSE 0 END) AS failed_count
            ')
            ->groupBy('blasts.campaign_id')
            ->get()
            ->keyBy('campaign_id');

        // ── Per-campaign click stats ─────────────────────────────────────────
        $clickStats = DB::table('link_clicks as lc')
            ->join('tracked_links as tl', 'tl.id', '=', 'lc.tracked_link_id')
            ->whereIn('tl.campaign_id', $ids)
            ->selectRaw('tl.campaign_id, COUNT(DISTINCT lc.contact_number) AS unique_clickers, COUNT(*) AS total_clicks')
            ->groupBy('tl.campaign_id')
            ->get()
            ->keyBy('campaign_id');

        // ── Blast audience size (from blasts table) ──────────────────────────
        $audienceSizes = DB::table('blasts')
            ->whereIn('campaign_id', $ids)
            ->selectRaw('campaign_id, COUNT(*) AS audience')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        // ── Build per-campaign result ────────────────────────────────────────
        $results = [];
        foreach ($ids as $cid) {
            $campaign = $campaigns->get($cid);
            if (!$campaign) continue;

            $ds        = $deliveryStats->get($cid);
            $cs        = $clickStats->get($cid);
            $audience  = $audienceSizes->get($cid)?->audience ?? 0;
            $total     = $ds?->total ?? 0;
            $delivered = $ds?->delivered ?? 0;
            $readCount = $ds?->read_count ?? 0;
            $clicks    = $cs?->unique_clickers ?? 0;

            $results[] = [
                'id'             => $cid,
                'name'           => $campaign->name,
                'status'         => $campaign->status,
                'type'           => $campaign->type,
                'phonebook'      => $campaign->phonebook?->name ?? '—',
                'template'       => $campaign->wabaTemplate?->name ?? null,
                'schedule'       => $campaign->schedule?->toDateTimeString(),
                'audience'       => $audience,
                'total'          => $total,
                'delivered'      => $delivered,
                'read_count'     => $readCount,
                'failed'         => $ds?->failed_count ?? 0,
                'clicks'         => $clicks,
                'delivery_rate'  => $audience > 0 ? round($delivered / $audience * 100, 1) : 0,
                'read_rate'      => $audience > 0 ? round($readCount  / $audience * 100, 1) : 0,
                'click_rate'     => $audience > 0 ? round($clicks     / $audience * 100, 1) : 0,
            ];
        }

        // ── Send-time heatmap across all selected campaigns ──────────────────
        // Uses campaign schedule time; falls back to created_at
        $heatmapRaw = DB::table('blasts as b')
            ->join('campaigns as c', 'c.id', '=', 'b.campaign_id')
            ->join('message_delivery_events as mde', 'mde.blast_id', '=', 'b.id')
            ->whereIn('c.id', $ids)
            ->selectRaw('
                DAYOFWEEK(COALESCE(c.schedule, c.created_at)) - 1 AS dow,
                HOUR(COALESCE(c.schedule, c.created_at))          AS hour,
                COUNT(*)                                           AS total,
                SUM(CASE WHEN mde.status = "read" THEN 1 ELSE 0 END) AS reads
            ')
            ->groupByRaw('dow, hour')
            ->get();

        $heatmap = [];
        foreach ($heatmapRaw as $row) {
            $heatmap[] = [
                'dow'      => (int) $row->dow,
                'hour'     => (int) $row->hour,
                'total'    => (int) $row->total,
                'reads'    => (int) $row->reads,
                'read_rate'=> $row->total > 0 ? round($row->reads / $row->total * 100, 1) : 0,
            ];
        }

        return response()->json([
            'campaigns' => $results,
            'heatmap'   => $heatmap,
        ]);
    }
}
