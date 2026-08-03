<?php

namespace App\Http\Controllers;

use App\Models\ChatbotFlow;
use App\Models\Device;
use App\Models\WabaTemplate;
use App\Models\WebhookLog;
use App\Models\WebhookSource;
use App\Models\WebhookTrigger;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    // ── Sources ───────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sources = WebhookSource::where('user_id', $request->user()->id)
            ->withCount('triggers')
            ->latest()
            ->get();

        $devices = $request->user()->devices()->where('status', 'Connected')->get();

        return view('theme::pages.webhooks.index', compact('sources', 'devices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'platform'    => 'required|in:shopify,woocommerce,razorpay,generic',
            'phone_field' => 'required|string|max:200',
            'name_field'  => 'nullable|string|max:200',
            'secret'      => 'nullable|string|max:200',
        ]);

        $source = WebhookSource::create([
            ...$data,
            'user_id' => $request->user()->id,
            'token'   => WebhookSource::generateToken(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Webhook source created.'),
            'source'  => [
                'id'   => $source->id,
                'name' => $source->name,
                'url'  => $source->inboundUrl(),
            ],
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        WebhookSource::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => __('Webhook source deleted.')]);
    }

    public function toggleActive(Request $request, int $id)
    {
        $source = WebhookSource::where('user_id', $request->user()->id)->findOrFail($id);
        $source->update(['active' => !$source->active]);
        return response()->json(['success' => true, 'active' => $source->active]);
    }

    // ── Source detail + logs ─────────────────────────────────────────────────

    public function show(Request $request, int $id)
    {
        $source   = WebhookSource::where('user_id', $request->user()->id)
            ->with('triggers.device')
            ->findOrFail($id);

        $logs = WebhookLog::where('webhook_source_id', $source->id)
            ->with('trigger')
            ->latest('created_at')
            ->take(50)
            ->get();

        $devices   = $request->user()->devices()->where('status', 'Connected')->get();
        $templates = WabaTemplate::where('user_id', $request->user()->id)
            ->where('status', 'APPROVED')
            ->orderBy('name')
            ->get(['id', 'name', 'language']);
        $flows = ChatbotFlow::where('user_id', $request->user()->id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('theme::pages.webhooks.show', compact('source', 'logs', 'devices', 'templates', 'flows'));
    }

    // ── Triggers ─────────────────────────────────────────────────────────────

    public function storeTrigger(Request $request, int $sourceId)
    {
        $source = WebhookSource::where('user_id', $request->user()->id)->findOrFail($sourceId);

        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'match_field'       => 'nullable|string|max:200',
            'match_value'       => 'nullable|string|max:200',
            'action_type'       => 'required|in:send_template,start_flow',
            'device_id'         => 'required|exists:devices,id',
            'template_name'     => 'required_if:action_type,send_template|nullable|string|max:512',
            'template_language' => 'nullable|string|max:10',
            'variable_map'      => 'nullable|string', // JSON string from form
            'flow_id'           => 'required_if:action_type,start_flow|nullable|exists:chatbot_flows,id',
        ]);

        $varMap = null;
        if (!empty($data['variable_map'])) {
            $varMap = json_decode($data['variable_map'], true) ?: null;
        }

        $trigger = $source->triggers()->create([
            'name'              => $data['name'],
            'match_field'       => $data['match_field'] ?: null,
            'match_value'       => $data['match_value'] ?: null,
            'action_type'       => $data['action_type'],
            'device_id'         => $data['device_id'],
            'template_name'     => $data['template_name'] ?? null,
            'template_language' => $data['template_language'] ?? 'en',
            'variable_map'      => $varMap,
            'flow_id'           => $data['flow_id'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => __('Trigger created.'), 'trigger_id' => $trigger->id]);
    }

    public function destroyTrigger(Request $request, int $sourceId, int $triggerId)
    {
        $source = WebhookSource::where('user_id', $request->user()->id)->findOrFail($sourceId);
        WebhookTrigger::where('webhook_source_id', $source->id)->findOrFail($triggerId)->delete();
        return response()->json(['success' => true, 'message' => __('Trigger deleted.')]);
    }

    public function toggleTrigger(Request $request, int $sourceId, int $triggerId)
    {
        $source  = WebhookSource::where('user_id', $request->user()->id)->findOrFail($sourceId);
        $trigger = WebhookTrigger::where('webhook_source_id', $source->id)->findOrFail($triggerId);
        $trigger->update(['active' => !$trigger->active]);
        return response()->json(['success' => true, 'active' => $trigger->active]);
    }
}
