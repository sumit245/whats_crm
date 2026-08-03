<?php

namespace App\Http\Controllers;

use App\Models\ChatbotFlow;
use App\Models\ChatbotSession;
use App\Models\FlowNodeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlowAnalyticsController extends Controller
{
    public function show(Request $request, int $flowId)
    {
        $userId = $request->user()->id;

        $flow = ChatbotFlow::where('user_id', $userId)->findOrFail($flowId);

        // ── Session volume over last 30 days ────────────────────────────────
        $sessionsByDay = ChatbotSession::where('flow_id', $flowId)
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        // Fill in zeros for missing days
        $days     = collect();
        $dayLabels = [];
        for ($i = 29; $i >= 0; $i--) {
            $date        = now()->subDays($i)->format('Y-m-d');
            $dayLabels[] = now()->subDays($i)->format('M d');
            $days->put($date, $sessionsByDay->get($date, 0));
        }

        // ── Overall session stats ───────────────────────────────────────────
        $totalSessions    = ChatbotSession::where('flow_id', $flowId)->count();
        $completedCount   = ChatbotSession::where('flow_id', $flowId)->where('state', 'completed')->count();
        $handoffCount     = ChatbotSession::where('flow_id', $flowId)->where('state', 'human_assigned')->count();
        $completionRate   = $totalSessions > 0 ? round(($completedCount / $totalSessions) * 100, 1) : 0;

        // ── Per-node visit counts (entered events only) ─────────────────────
        $nodeVisits = FlowNodeEvent::where('flow_id', $flowId)
            ->where('event_type', 'entered')
            ->selectRaw('node_id, node_type, COUNT(*) as visits, COUNT(DISTINCT session_id) as unique_sessions')
            ->groupBy('node_id', 'node_type')
            ->orderByDesc('visits')
            ->get();

        // Get node labels from flow JSON
        $flowNodes = $flow->getNodes();
        $nodeVisits = $nodeVisits->map(function ($row) use ($flowNodes) {
            $node = $flowNodes[$row->node_id] ?? null;
            $row->label = $node['data']['label'] ?? $node['data']['message'] ?? $node['name'] ?? $row->node_id;
            if (strlen($row->label) > 60) {
                $row->label = substr($row->label, 0, 57) . '…';
            }
            return $row;
        });

        // Drop-off: percentage of sessions that reached this node vs total sessions
        $nodeVisits = $nodeVisits->map(function ($row) use ($totalSessions) {
            $row->reach_rate = $totalSessions > 0
                ? round(($row->unique_sessions / $totalSessions) * 100, 1)
                : 0;
            return $row;
        });

        return view('theme::pages.flows.analytics', compact(
            'flow',
            'totalSessions',
            'completedCount',
            'handoffCount',
            'completionRate',
            'days',
            'dayLabels',
            'nodeVisits'
        ));
    }
}
