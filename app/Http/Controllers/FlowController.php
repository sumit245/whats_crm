<?php

namespace App\Http\Controllers;

use App\Models\ChatbotFlow;
use App\Models\Conversation;
use App\Models\WabaTemplate;
use App\Services\FlowEngine;
use App\Services\FlowValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlowController extends Controller
{
    public function index(Request $request)
    {
        $flows = ChatbotFlow::where('user_id', $request->user()->id)
            ->with('device')
            ->withCount('sessions')
            ->latest()
            ->paginate(20);

        return view('theme::pages.flows.index', compact('flows'));
    }

    public function create(Request $request)
    {
        $devices   = $request->user()->devices()->where('status', 'Connected')->get();
        $templates = WabaTemplate::where('user_id', $request->user()->id)
            ->where('status', 'APPROVED')
            ->get(['id', 'name', 'language']);

        return view('theme::pages.flows.editor', compact('devices', 'templates'));
    }

    public function edit(Request $request, $id)
    {
        $flow      = ChatbotFlow::where('user_id', $request->user()->id)->findOrFail($id);
        $devices   = $request->user()->devices()->where('status', 'Connected')->get();
        $templates = WabaTemplate::where('user_id', $request->user()->id)
            ->where('status', 'APPROVED')
            ->get(['id', 'name', 'language']);

        return view('theme::pages.flows.editor', compact('flow', 'devices', 'templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'device_id' => 'required|exists:devices,id',
        ]);

        try {
            $flowJson = $request->flow_json ? json_decode($request->flow_json, true) : null;

            // Phase E: Validate flow structure before saving
            $validation = $flowJson ? (new FlowValidator())->validate($flowJson) : ['valid' => true, 'errors' => [], 'warnings' => []];
            if (!$validation['valid']) {
                return response()->json([
                    'error'    => true,
                    'message'  => __('Flow validation failed.'),
                    'errors'   => $validation['errors'],
                    'warnings' => $validation['warnings'],
                ], 422);
            }

            $flow = ChatbotFlow::create([
                'user_id'          => $request->user()->id,
                'device_id'        => $request->device_id,
                'name'             => $request->name,
                'description'      => $request->description,
                'status'           => 'draft',
                'trigger_type'     => $request->trigger_type ?? 'keyword',
                'trigger_value'    => $request->trigger_value,
                'trigger_match'    => $request->trigger_match ?? 'contains',
                'flow_json'        => $flowJson,
                'fallback_message' => $request->fallback_message,
            ]);

            return response()->json([
                'error'    => false,
                'id'       => $flow->id,
                'message'  => __('Flow saved.'),
                'warnings' => $validation['warnings'],
                'redirect' => route('flows.edit', $flow->id),
            ]);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $flow     = ChatbotFlow::where('user_id', $request->user()->id)->findOrFail($id);
            $flowJson = $request->flow_json ? json_decode($request->flow_json, true) : $flow->flow_json;

            // Phase E: Validate flow structure before updating
            if ($request->filled('flow_json')) {
                $validation = (new FlowValidator())->validate($flowJson);
                if (!$validation['valid']) {
                    return response()->json([
                        'error'    => true,
                        'message'  => __('Flow validation failed.'),
                        'errors'   => $validation['errors'],
                        'warnings' => $validation['warnings'],
                    ], 422);
                }
            } else {
                $validation = ['warnings' => []];
            }

            $flow->update([
                'name'             => $request->name ?? $flow->name,
                'description'      => $request->description ?? $flow->description,
                'device_id'        => $request->device_id ?? $flow->device_id,
                'trigger_type'     => $request->trigger_type ?? $flow->trigger_type,
                'trigger_value'    => $request->trigger_value ?? $flow->trigger_value,
                'trigger_match'    => $request->trigger_match ?? $flow->trigger_match,
                'flow_json'        => $flowJson,
                'fallback_message' => $request->fallback_message ?? $flow->fallback_message,
            ]);

            return response()->json([
                'error'    => false,
                'message'  => __('Flow updated.'),
                'warnings' => $validation['warnings'] ?? [],
            ]);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Phase G: API trigger — programmatically trigger a flow for a contact.
     * POST /api/{locale}/flow/trigger
     */
    public function apiTrigger(Request $request)
    {
        $request->validate([
            'device_id'    => 'required|exists:devices,id',
            'phone_number' => 'required|string',
        ]);

        try {
            $device = $request->user()->devices()->findOrFail($request->device_id);

            if ($device->status !== 'Connected') {
                return response()->json(['error' => true, 'message' => __('Device is not connected.')], 422);
            }

            $conversation = Conversation::firstOrCreate(
                [
                    'user_id'        => $request->user()->id,
                    'device_id'      => $device->id,
                    'contact_number' => $request->phone_number,
                ],
                ['contact_name' => $request->name ?? null]
            );

            // Inject any provided variables into a transient "payload" for the engine
            $variables = $request->variables ?? [];

            $engine  = new FlowEngine();
            $handled = $engine->handleInbound(
                $conversation->load('device'),
                '__api_trigger__',
                ['api_trigger' => true, 'variables' => $variables]
            );

            if (!$handled) {
                return response()->json(['error' => true, 'message' => __('No active api-triggered flow found for this device.')], 404);
            }

            return response()->json(['error' => false, 'message' => __('Flow triggered successfully.')]);

        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $flow       = ChatbotFlow::where('user_id', $request->user()->id)->findOrFail($id);
            $newStatus  = $flow->status === 'active' ? 'draft' : 'active';
            $flow->update(['status' => $newStatus]);

            return response()->json([
                'error'  => false,
                'status' => $newStatus,
                'message' => $newStatus === 'active' ? __('Flow activated.') : __('Flow deactivated.'),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            ChatbotFlow::where('user_id', $request->user()->id)->findOrFail($id)->delete();
            return response()->json(['error' => false, 'message' => __('Flow deleted.')]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function duplicate(Request $request, $id)
    {
        try {
            $original = ChatbotFlow::where('user_id', $request->user()->id)->findOrFail($id);
            $copy = $original->replicate();
            $copy->name   = $original->name . ' (Copy)';
            $copy->status = 'draft';
            $copy->save();

            return response()->json([
                'error'    => false,
                'message'  => __('Flow duplicated.'),
                'redirect' => route('flows.edit', $copy->id),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
