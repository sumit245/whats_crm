<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBlastJob;
use App\Models\Blast;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageDeliveryEvent;
use App\Models\Segment;
use App\Models\Tag;
use App\Models\WabaTemplate;
use App\Services\SegmentEngine;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = $request->user()->campaigns()
            ->withCount([
                'blasts',
                'blasts as blasts_pending'    => fn ($q) => $q->where('status', 'pending'),
                'blasts as blasts_success'    => fn ($q) => $q->where('status', 'success'),
                'blasts as blasts_failed'     => fn ($q) => $q->where('status', 'failed'),
                'blasts as blasts_suppressed' => fn ($q) => $q->where('status', 'suppressed'),
            ])
            ->with(['device', 'wabaTemplate'])
            ->filter($request)
            ->latest()
            ->paginate(10);

        $devices = $request->user()->devices()->get();

        return view('theme::pages.campaign.index', compact('campaigns', 'devices'));
    }

    public function create(Request $request)
    {
        $phonebooks = $request->user()->phonebooks()
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->withCount('contacts')
            ->latest()
            ->get();

        $devices = $request->user()->devices()->where('status', 'Connected')->get();

        $approvedTemplates = WabaTemplate::where('user_id', $request->user()->id)
            ->approved()
            ->with('device')
            ->get();

        $segments = Segment::where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'name', 'contact_count']);

        return view('theme::pages.campaign.create', compact('phonebooks', 'devices', 'approvedTemplates', 'segments'));
    }

    /**
     * AJAX: return approved templates for a given device (used by campaign create JS).
     */
    public function getTemplatesForDevice(Request $request, $deviceId)
    {
        $templates = WabaTemplate::where('user_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->approved()
            ->get(['id', 'name', 'category', 'language', 'components']);

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        try {
            $device = $request->user()->devices()->findOrFail($request->device_id);

            if ($device->status !== 'Connected') {
                return response()->json(['error' => true, 'message' => __('Device is not connected.')]);
            }

            $template = null;
            if ($request->template_id) {
                $template = WabaTemplate::where('user_id', $request->user()->id)->findOrFail($request->template_id);
            }

            // ── Resolve contacts: phonebook or segment ─────────────────────
            $contacts = collect();

            if ($request->filled('segment_id')) {
                $segment  = Segment::where('user_id', $request->user()->id)->findOrFail($request->segment_id);
                $contacts = (new SegmentEngine())->resolve($segment);
            } else {
                $phonebook = $request->user()->phonebooks()->with('contacts')->findOrFail($request->phonebook_id);
                $contacts  = $phonebook->contacts;
            }

            if ($contacts->isEmpty()) {
                return response()->json(['error' => true, 'message' => __('No contacts found. Please add contacts or check your segment.')]);
            }

            // ── Build blast records ────────────────────────────────────────
            $blasts = [];
            foreach ($contacts as $contact) {
                if ($template) {
                    $vars = [];
                    $i = 1;
                    while ($request->has("var_source_{$i}")) {
                        $source = $request->input("var_source_{$i}");
                        $vars[$i] = match ($source) {
                            'name'   => $contact->name ?? '',
                            'number' => $contact->number ?? '',
                            default  => $request->input("var_static_{$i}", ''),
                        };
                        $i++;
                    }

                    $blasts[] = [
                        'user_id'            => $request->user()->id,
                        'sender'             => $device->body,
                        'status'             => 'pending',
                        'receiver'           => $contact->number,
                        'type'               => 'template',
                        'message'            => json_encode(['template_name' => $template->name, 'language' => $template->language]),
                        'template_variables' => json_encode($vars),
                        'campaign_id'        => 0,
                    ];
                } else {
                    $msg = str_replace('{name}', $contact->name ?? '', $request->message ?? '');
                    $blasts[] = [
                        'user_id'     => $request->user()->id,
                        'sender'      => $device->body,
                        'status'      => 'pending',
                        'receiver'    => $contact->number,
                        'type'        => $request->type ?? 'text',
                        'message'     => json_encode(['text' => $msg]),
                        'campaign_id' => 0,
                    ];
                }
            }

            $phonebookId = $request->filled('segment_id')
                ? null
                : ($request->phonebook_id ?? null);

            $campaign = $device->campaigns()->create([
                'user_id'      => $request->user()->id,
                'name'         => $request->campaign_name,
                'type'         => $template ? 'template' : ($request->type ?? 'text'),
                'category'     => $request->category ?? ($template?->category),
                'template_id'  => $template?->id,
                'delay'        => max(1, (int) $request->delay),
                'status'       => 'waiting',
                'message'      => $template
                    ? ['template_name' => $template->name, 'language' => $template->language]
                    : ['text' => $request->message ?? ''],
                'phonebook_id' => $phonebookId,
                'schedule'     => $request->tipe === 'schedule' ? $request->datetime : now(),
            ]);

            foreach ($blasts as &$blast) {
                $blast['campaign_id'] = $campaign->id;
            }
            $campaign->blasts()->createMany($blasts);

            return response()->json(['error' => false, 'message' => __('Campaign created successfully! It will begin processing shortly.')]);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['error' => true, 'message' => __('Failed to create campaign: ') . $th->getMessage()]);
        }
    }

    public function pause(Request $request, $id)
    {
        try {
            $request->user()->campaigns()->findOrFail($id)->update(['status' => 'paused']);
            session()->flash('alert', ['type' => 'success', 'msg' => __('Campaign paused')]);
        } catch (\Throwable $th) {
            session()->flash('alert', ['type' => 'danger', 'msg' => __('Something went wrong')]);
        }
        return json_encode(['error' => false, 'msg' => __('Campaign paused')]);
    }

    public function resume(Request $request, $id)
    {
        try {
            $campaign = $request->user()->campaigns()->findOrFail($id);
            $otherActive = $request->user()->campaigns()
                ->where('device_id', $campaign->device_id)
                ->whereIn('status', ['waiting', 'processing'])
                ->where('id', '!=', $id)
                ->count();

            if ($otherActive > 0) {
                session()->flash('alert', ['type' => 'danger', 'msg' => __('Another campaign is already running on this device.')]);
            } elseif ($campaign->device->status !== 'Connected') {
                session()->flash('alert', ['type' => 'danger', 'msg' => __('Device is not connected.')]);
            } else {
                $campaign->update(['status' => 'waiting']);
                session()->flash('alert', ['type' => 'success', 'msg' => __('Campaign resumed')]);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            session()->flash('alert', ['type' => 'danger', 'msg' => __('Something went wrong')]);
        }
        return json_encode(['error' => false, 'msg' => __('Campaign resumed')]);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $request->user()->campaigns()->findOrFail($id)->delete();
            session()->flash('alert', ['type' => 'success', 'msg' => __('Campaign deleted')]);
        } catch (\Throwable $th) {
            Log::error($th);
            session()->flash('alert', ['type' => 'danger', 'msg' => __('Something went wrong')]);
        }
        return json_encode(['error' => false, 'msg' => __('Campaign deleted')]);
    }

    public function destroyAll(Request $request)
    {
        try {
            $request->user()->campaigns()->delete();
            session()->flash('alert', ['type' => 'success', 'msg' => __('All campaigns deleted')]);
        } catch (\Throwable $th) {
            Log::error($th);
            session()->flash('alert', ['type' => 'danger', 'msg' => __('Something went wrong')]);
        }
        return response()->json(['error' => false, 'message' => __('All campaigns deleted')]);
    }

    public function show(Request $request, $id)
    {
        $campaign = $request->user()->campaigns()
            ->with(['device', 'wabaTemplate', 'phonebook'])
            ->findOrFail($id);

        // ── Delivery funnel (Phase D) ──────────────────────────────────────
        $blastIds = $campaign->blasts()->where('status', 'success')->pluck('id');
        $total    = $campaign->blasts()->count();

        // Best status per blast_id from delivery events
        $eventStatuses = MessageDeliveryEvent::whereIn('blast_id', $blastIds)
            ->selectRaw('blast_id, MAX(CASE status WHEN "read" THEN 3 WHEN "delivered" THEN 2 WHEN "sent" THEN 1 ELSE 0 END) as rank')
            ->groupBy('blast_id')
            ->pluck('rank', 'blast_id');

        $sent      = $blastIds->count();
        $delivered = $eventStatuses->filter(fn ($r) => $r >= 2)->count();
        $read      = $eventStatuses->filter(fn ($r) => $r >= 3)->count();
        $failed    = $campaign->blasts()->where('status', 'failed')->count();
        $suppressed = $campaign->blasts()->where('status', 'suppressed')->count();

        $funnel = compact('total', 'sent', 'delivered', 'read', 'failed', 'suppressed');

        return view('theme::pages.campaign.show', compact('campaign', 'funnel'));
    }

    /**
     * AJAX: Return batch progress percentage for a campaign.
     */
    public function progress(Request $request, $id)
    {
        $campaign = $request->user()->campaigns()->findOrFail($id);

        if (!$campaign->job_batch_id) {
            $total   = $campaign->blasts()->count();
            $pending = $campaign->blasts()->where('status', 'pending')->count();
            $done    = $total - $pending;
            $pct     = $total > 0 ? round(($done / $total) * 100) : 0;
            return response()->json(['progress' => $pct, 'status' => $campaign->status]);
        }

        $batch = Bus::findBatch($campaign->job_batch_id);

        if (!$batch) {
            return response()->json(['progress' => 100, 'status' => $campaign->status]);
        }

        return response()->json([
            'progress'       => $batch->progress(),
            'status'         => $campaign->status,
            'total'          => $batch->totalJobs,
            'pending'        => $batch->pendingJobs,
            'failed'         => $batch->failedJobs,
            'finished'       => $batch->finished(),
        ]);
    }

    /**
     * Phase D: Create a retarget campaign from delivery-status filtered recipients.
     */
    public function retarget(Request $request, $id)
    {
        try {
            $campaign = $request->user()->campaigns()->findOrFail($id);
            $filter   = $request->input('filter', 'not_delivered');

            $blastIds = $campaign->blasts()->where('status', 'success')->pluck('id');

            // Best delivery rank per blast from events
            $eventStatuses = MessageDeliveryEvent::whereIn('blast_id', $blastIds)
                ->selectRaw('blast_id, MAX(CASE status WHEN "read" THEN 3 WHEN "delivered" THEN 2 WHEN "sent" THEN 1 ELSE 0 END) as rank')
                ->groupBy('blast_id')
                ->pluck('rank', 'blast_id');

            $targetBlastIds = match ($filter) {
                // Sent but never reached delivered status (rank < 2) OR no event at all
                'not_delivered'      => $blastIds->filter(fn ($bid) => ($eventStatuses[$bid] ?? 0) < 2),
                // Delivered but not read
                'delivered_not_read' => $blastIds->filter(fn ($bid) => ($eventStatuses[$bid] ?? 0) === 2),
                // Read (highest status)
                'read'               => $blastIds->filter(fn ($bid) => ($eventStatuses[$bid] ?? 0) >= 3),
                default              => collect(),
            };

            if ($targetBlastIds->isEmpty()) {
                return response()->json(['error' => true, 'message' => __('No contacts match this filter.')]);
            }

            $receivers = Blast::whereIn('id', $targetBlastIds)->pluck('receiver');

            // Create a temporary phonebook for the retarget audience
            $tagName = 'Retarget: ' . $campaign->name . ' [' . $filter . '] ' . now()->format('m/d H:i');
            $tag = Tag::create(['user_id' => $request->user()->id, 'name' => $tagName]);

            $contactRows = $receivers->map(fn ($number) => [
                'user_id' => $request->user()->id,
                'tag_id'  => $tag->id,
                'number'  => $number,
                'name'    => $number,
            ]);
            Contact::insert($contactRows->toArray());

            return response()->json([
                'error'   => false,
                'message' => __('Retarget audience of :count contacts created.', ['count' => $receivers->count()]),
                'redirect' => route('campaign.create') . '?phonebook_id=' . $tag->id,
            ]);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['error' => true, 'message' => __('Failed to create retarget: ') . $th->getMessage()]);
        }
    }
}
