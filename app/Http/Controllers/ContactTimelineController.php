<?php

namespace App\Http\Controllers;

use App\Models\ChatNote;
use App\Models\Contact;
use App\Models\ContactAttribute;
use App\Models\Conversation;
use App\Models\LinkClick;
use App\Models\Segment;
use App\Models\SuppressionEntry;
use App\Models\TrackedLink;
use App\Services\SegmentEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContactTimelineController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $q      = trim($request->input('q', ''));

        $phonebooks = $request->user()->phonebooks()->withCount('contacts')->orderBy('name')->get();

        $contacts = Contact::where('user_id', $userId)
            ->with('tags:id,name')
            ->when($q, fn ($query) => $query
                ->where('name',   'like', "%{$q}%")
                ->orWhere('number',  'like', "%{$q}%")
                ->orWhere('company', 'like', "%{$q}%")
                ->orWhere('email',   'like', "%{$q}%"))
            ->when($request->filled('tag_id'), fn ($query) => $query
                ->whereHas('tags', fn ($t) => $t->where('tags.id', $request->tag_id)))
            ->when($request->filled('status'), fn ($query) => $query
                ->where('status', $request->status))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        // Batch-fetch latest conversation per contact number (no N+1)
        $numbers = $contacts->pluck('number')->all();
        $convMap = Conversation::where('user_id', $userId)
            ->whereIn('contact_number', $numbers)
            ->select('id', 'contact_number', 'conversation_status')
            ->orderByDesc('last_message_at')
            ->get()
            ->unique('contact_number')          // keep only latest per number
            ->keyBy('contact_number');

        return view('theme::pages.contacts.directory', compact('contacts', 'q', 'convMap', 'phonebooks'));
    }

    public function show(Request $request, string $number)
    {
        $userId = $request->user()->id;

        // ── Identity ────────────────────────────────────────────────────────
        $contact     = Contact::where('user_id', $userId)->where('number', $number)->with('tags:id,name')->first();
        $phonebooks  = $contact?->tags ?? collect();
        $contactName = $contact?->name ?? $number;
        $isSuppressed = SuppressionEntry::isSuppressed($userId, $number);
        $attributes   = ContactAttribute::allFor($userId, $number);

        // ── Segment memberships (check each segment, skip if too many) ──────
        $segments = $this->resolveSegments($userId, $number);

        // ── Campaign blast history ──────────────────────────────────────────
        $blasts = DB::table('blasts')
            ->join('campaigns', 'campaigns.id', '=', 'blasts.campaign_id')
            ->where('blasts.user_id', $userId)
            ->where('blasts.receiver', $number)
            ->select(
                'blasts.id',
                'blasts.status',
                'blasts.created_at',
                'campaigns.id as campaign_id',
                'campaigns.name as campaign_name',
                DB::raw('NULL as template_name')
            )
            ->orderByDesc('blasts.created_at')
            ->get();

        // Best delivery status per blast from delivery events
        $blastIds    = $blasts->pluck('id');
        $deliveryMap = DB::table('message_delivery_events')
            ->whereIn('blast_id', $blastIds)
            ->selectRaw('blast_id, MAX(CASE status WHEN "read" THEN 3 WHEN "delivered" THEN 2 WHEN "sent" THEN 1 ELSE 0 END) as dlr_rank')
            ->groupBy('blast_id')
            ->pluck('dlr_rank', 'blast_id');

        $rankToStatus = [3 => 'read', 2 => 'delivered', 1 => 'sent', 0 => 'pending'];

        // ── Link click history ──────────────────────────────────────────────
        $clicks = LinkClick::where('contact_number', $number)
            ->with(['trackedLink' => fn ($q) => $q->select('id', 'slug', 'original_url', 'campaign_id')
                ->with('campaign:id,name')])
            ->orderByDesc('clicked_at')
            ->get();

        // ── Conversations ───────────────────────────────────────────────────
        $conversations = Conversation::where('user_id', $userId)
            ->where('contact_number', $number)
            ->with(['assignedAgent:id,name', 'labels:id,name,color'])
            ->orderByDesc('last_message_at')
            ->get();

        $convIds = $conversations->pluck('id');

        // ── Chat notes ──────────────────────────────────────────────────────
        $notes = ChatNote::whereIn('conversation_id', $convIds)
            ->with('conversation:id,contact_number')
            ->orderByDesc('created_at')
            ->get();

        // ── Unified timeline ────────────────────────────────────────────────
        $timeline = $this->buildTimeline($blasts, $deliveryMap, $rankToStatus, $clicks, $conversations, $notes);

        // ── Summary stats ───────────────────────────────────────────────────
        $stats = [
            'campaigns_sent'    => $blasts->count(),
            'read_count'        => $blasts->filter(fn ($b) => ($rankToStatus[$deliveryMap[$b->id] ?? 0] ?? 'pending') === 'read')->count(),
            'total_clicks'      => $clicks->count(),
            'conversations'     => $conversations->count(),
        ];

        return view('theme::pages.contacts.timeline', compact(
            'number', 'contact', 'contactName', 'isSuppressed',
            'phonebooks', 'attributes', 'segments',
            'blasts', 'deliveryMap', 'rankToStatus',
            'clicks', 'conversations', 'notes',
            'timeline', 'stats'
        ));
    }

    private function buildTimeline(
        $blasts, $deliveryMap, $rankToStatus,
        $clicks, $conversations, $notes
    ): Collection {
        $items = collect();

        foreach ($blasts as $b) {
            $status = $rankToStatus[$deliveryMap[$b->id] ?? 0] ?? 'pending';
            $items->push([
                'type'      => 'blast',
                'date'      => $b->created_at,
                'title'     => $b->campaign_name,
                'subtitle'  => ucfirst($status),
                'status'    => $status,
                'meta'      => ['campaign_id' => $b->campaign_id],
            ]);
        }

        foreach ($clicks as $c) {
            $url = $c->trackedLink?->original_url ?? '—';
            $short = strlen($url) > 50 ? substr($url, 0, 47) . '…' : $url;
            $items->push([
                'type'      => 'click',
                'date'      => $c->clicked_at->toDateTimeString(),
                'title'     => $c->trackedLink?->campaign?->name ?? __('Unknown campaign'),
                'subtitle'  => $short,
                'status'    => 'clicked',
                'meta'      => ['url' => $url],
            ]);
        }

        foreach ($conversations as $conv) {
            $items->push([
                'type'      => 'conversation',
                'date'      => $conv->created_at,
                'title'     => __('Conversation started'),
                'subtitle'  => $conv->last_message ?? '—',
                'status'    => $conv->conversation_status,
                'meta'      => ['conversation_id' => $conv->id, 'agent' => $conv->assignedAgent?->name],
            ]);
        }

        foreach ($notes as $n) {
            $items->push([
                'type'      => 'note',
                'date'      => $n->created_at,
                'title'     => $n->title ?? __('Note'),
                'subtitle'  => $n->note,
                'status'    => 'note',
                'meta'      => ['author' => $n->author, 'conversation_id' => $n->conversation_id],
            ]);
        }

        return $items->sortByDesc('date')->values();
    }

    private function resolveSegments(int $userId, string $number): Collection
    {
        $segments = Segment::where('user_id', $userId)->limit(50)->get();
        if ($segments->isEmpty()) return collect();

        $engine = new SegmentEngine();
        $matched = [];

        foreach ($segments as $segment) {
            try {
                $inSegment = $engine->buildQuery($segment)
                    ->where('contacts.number', $number)
                    ->exists();

                if ($inSegment) {
                    $matched[] = $segment;
                }
            } catch (\Throwable) {
                // Skip broken segment rules
            }
        }

        return collect($matched);
    }
}
