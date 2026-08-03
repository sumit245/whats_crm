<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CampaignCalendarController extends Controller
{
    public function index(Request $request)
    {
        return view('theme::pages.campaigns.calendar');
    }

    /** JSON feed consumed by FullCalendar */
    public function events(Request $request)
    {
        $userId = $request->user()->id;
        $start  = $request->input('start') ? Carbon::parse($request->input('start')) : now()->startOfMonth();
        $end    = $request->input('end')   ? Carbon::parse($request->input('end'))   : now()->endOfMonth();

        $campaigns = Campaign::where('user_id', $userId)
            ->with('phonebook:id,name')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('schedule', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      // Show campaigns with no schedule date on their created_at
                      $q2->whereNull('schedule')->whereBetween('created_at', [$start, $end]);
                  });
            })
            ->get();

        // Detect conflicts: same phonebook, within 24 hours of another campaign
        $conflictIds = $this->detectConflicts($campaigns);

        $events = $campaigns->map(function ($c) use ($conflictIds) {
            $date      = $c->schedule ?? $c->created_at;
            $draggable = $c->status === 'waiting' && $c->schedule !== null;
            $conflict  = in_array($c->id, $conflictIds);

            $color = match ($c->status) {
                'waiting'    => $conflict ? '#f59e0b' : '#6366f1',
                'processing' => '#f97316',
                'completed'  => '#22c55e',
                'failed'     => '#ef4444',
                'paused'     => '#94a3b8',
                default      => '#6366f1',
            };

            return [
                'id'              => $c->id,
                'title'           => $c->name,
                'start'           => Carbon::parse($date)->toIso8601String(),
                'color'           => $color,
                'editable'        => $draggable,
                'extendedProps'   => [
                    'status'    => $c->status,
                    'phonebook' => $c->phonebook?->name ?? '—',
                    'type'      => $c->type,
                    'conflict'  => $conflict,
                    'scheduled' => $c->schedule !== null,
                ],
            ];
        });

        return response()->json($events->values());
    }

    /** PATCH — update schedule after drag-drop */
    public function reschedule(Request $request, int $id)
    {
        $userId   = $request->user()->id;
        $campaign = Campaign::where('user_id', $userId)
            ->where('status', 'waiting')
            ->findOrFail($id);

        $request->validate(['start' => 'required|date']);

        $campaign->update(['schedule' => Carbon::parse($request->input('start'))]);

        return response()->json(['ok' => true]);
    }

    private function detectConflicts($campaigns): array
    {
        $conflictIds = [];
        $byPhonebook = $campaigns->filter(fn ($c) => $c->phonebook_id)->groupBy('phonebook_id');

        foreach ($byPhonebook as $phonebookId => $group) {
            if ($group->count() < 2) continue;

            $sorted = $group->sortBy(fn ($c) => ($c->schedule ?? $c->created_at));
            $items  = $sorted->values();

            for ($i = 0; $i < $items->count() - 1; $i++) {
                $a = Carbon::parse($items[$i]->schedule   ?? $items[$i]->created_at);
                $b = Carbon::parse($items[$i+1]->schedule ?? $items[$i+1]->created_at);

                if (abs($a->diffInHours($b)) < 24) {
                    $conflictIds[] = $items[$i]->id;
                    $conflictIds[] = $items[$i+1]->id;
                }
            }
        }

        return array_unique($conflictIds);
    }
}
