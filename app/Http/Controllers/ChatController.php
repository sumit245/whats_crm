<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatNote;
use App\Models\ChatbotSession;
use App\Models\ContactAttribute;
use App\Models\Conversation;
use App\Models\Device;
use App\Models\MessageHistory;
use App\Services\ChatRouter;
use App\Services\FlowEngine;
use App\Services\Impl\MetaCloudApiService;
use App\Services\SocketPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $devices = $request->user()->devices()->where('status', 'Connected')->get();

        $deviceId     = $request->device_id ?? session('selectedDevice.device_id');
        $agentFilter  = $request->agent_id;
        $teamFilter   = $request->team_id;
        $statusFilter = $request->conv_status ?? 'open';

        // Supervisor mode: show all agents' conversations when an agent/team filter is active
        // or the user explicitly requests global view
        $isSupervisor = $request->boolean('global') ||
            Agent::where('user_id', $request->user()->id)
                ->whereIn('role', ['supervisor', 'admin'])
                ->exists();

        $conversations = $request->user()->conversations()
            ->when($deviceId,     fn ($q) => $q->where('device_id', $deviceId))
            ->when($agentFilter,  fn ($q) => $q->where('assigned_agent_id', $agentFilter))
            ->when($teamFilter,   fn ($q) => $q->whereHas('assignedAgent', fn ($aq) => $aq->where('team_id', $teamFilter)))
            ->when($statusFilter, fn ($q) => $q->where('conversation_status', $statusFilter))
            ->when($request->sla_only, fn ($q) => $q->where('sla_breached', true))
            ->when($request->unassigned_only, fn ($q) => $q->whereNull('assigned_agent_id'))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('contact_name', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_number', 'like', '%' . $request->search . '%');
            }))
            ->with(['device', 'assignedAgent'])
            ->orderByDesc('sla_breached')
            ->orderByDesc('last_message_at')
            ->paginate(30);

        $agents = Agent::where('user_id', $request->user()->id)->with('team')->get();
        $teams  = \App\Models\Team::where('user_id', $request->user()->id)->get();

        return view('theme::pages.chat.index', compact(
            'conversations', 'devices', 'deviceId', 'agents', 'teams',
            'statusFilter', 'isSupervisor'
        ));
    }

    public function show(Request $request, $id)
    {
        $conversation = $request->user()->conversations()
            ->with(['device', 'assignedAgent.team', 'notes'])
            ->findOrFail($id);

        // Mark as read
        $conversation->update(['unread_count' => 0]);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        // Merge messages and notes into a single chronological timeline
        $timeline = $messages->map(fn ($m) => (object)[
                'type'       => 'message',
                'item'       => $m,
                'created_at' => $m->created_at,
            ])
            ->concat(
                $conversation->notes->map(fn ($n) => (object)[
                    'type'       => 'note',
                    'item'       => $n,
                    'created_at' => $n->created_at,
                ])
            )
            ->sortBy('created_at')
            ->values();

        $devices      = $request->user()->devices()->where('status', 'Connected')->get();
        $deviceId     = $request->device_id ?? session('selectedDevice.device_id');
        $statusFilter = $request->conv_status ?? $conversation->conversation_status;
        $agentFilter  = $request->agent_id;
        $teamFilter   = $request->team_id;

        $isSupervisor = $request->boolean('global') ||
            Agent::where('user_id', $request->user()->id)
                ->whereIn('role', ['supervisor', 'admin'])
                ->exists();

        // Sidebar conversation list — apply same filters as index() so supervisor controls work
        $conversations = $request->user()->conversations()
            ->when($deviceId,              fn ($q) => $q->where('device_id', $deviceId))
            ->when($agentFilter,           fn ($q) => $q->where('assigned_agent_id', $agentFilter))
            ->when($teamFilter,            fn ($q) => $q->whereHas('assignedAgent', fn ($aq) => $aq->where('team_id', $teamFilter)))
            ->when($statusFilter,          fn ($q) => $q->where('conversation_status', $statusFilter))
            ->when($request->sla_only,     fn ($q) => $q->where('sla_breached', true))
            ->when($request->unassigned_only, fn ($q) => $q->whereNull('assigned_agent_id'))
            ->with(['device', 'assignedAgent'])
            ->orderByDesc('sla_breached')
            ->orderByDesc('last_message_at')
            ->paginate(30);

        // Approved templates for this device (for first-contact outreach)
        $approvedTemplates = \App\Models\WabaTemplate::where('user_id', $request->user()->id)
            ->where('device_id', $conversation->device_id)
            ->where('status', 'APPROVED')
            ->get(['id', 'name', 'category', 'language', 'components']);

        $agents = Agent::where('user_id', $request->user()->id)->with('team')->get();
        $teams  = \App\Models\Team::where('user_id', $request->user()->id)->get();

        // CRM right panel: custom attributes and phonebook tags
        $contactAttributes = ContactAttribute::allFor(
            $request->user()->id,
            $conversation->contact_number
        );
        $contactTags = \App\Models\Contact::where('user_id', $request->user()->id)
            ->where('number', $conversation->contact_number)
            ->with('tag')
            ->get()
            ->pluck('tag.name')
            ->filter()
            ->unique()
            ->values();

        return view('theme::pages.chat.index', compact(
            'conversation', 'messages', 'timeline', 'conversations', 'devices',
            'deviceId', 'approvedTemplates', 'agents', 'teams',
            'statusFilter', 'isSupervisor', 'contactAttributes', 'contactTags'
        ));
    }

    public function messages(Request $request, $id)
    {
        $conversation = $request->user()->conversations()->findOrFail($id);

        $query = $conversation->messages()->orderBy('created_at');

        if ($request->after_id) {
            $query->where('id', '>', (int) $request->after_id);
        }

        $messages = $query->get()->map(fn ($m) => $this->formatMessage($m));

        // Mark as read on poll
        if ($messages->where('direction', 'inbound')->isNotEmpty()) {
            $conversation->update(['unread_count' => 0]);
        }

        return response()->json([
            'messages' => $messages,
            'unread'   => $conversation->fresh()->unread_count,
        ]);
    }

    public function send(Request $request, $id)
    {
        $request->validate(['message' => 'required|string|max:4096']);

        $conversation = $request->user()->conversations()->with('device')->findOrFail($id);
        $device       = $conversation->device;

        if ($device->status !== 'Connected') {
            return response()->json(['error' => true, 'message' => __('Device is not connected.')], 422);
        }

        $service    = new MetaCloudApiService($device);
        $fakeReq    = (object) ['message' => $request->message, 'text' => $request->message];

        Log::debug('Chat send attempt', [
            'conv_id' => $conversation->id,
            'to'      => $conversation->contact_number,
            'device'  => $device->id,
        ]);

        $result = $service->sendText($fakeReq, $conversation->contact_number);

        Log::debug('Chat send result', [
            'status'     => $result->status,
            'message_id' => $result->message_id ?? null,
            'error'      => $result->error ?? null,
        ]);

        $status = $result->status ? 'sent' : 'failed';

        $chatMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'text',
            'body'            => $request->message,
            'meta_message_id' => $result->message_id ?? null,
            'status'          => $status,
        ]);

        // Also record in message_histories so it appears in the history page
        MessageHistory::create([
            'user_id'   => $request->user()->id,
            'device_id' => $device->id,
            'number'    => $conversation->contact_number,
            'type'      => 'text',
            'message'   => $request->message,
            'payload'   => json_encode(['text' => ['body' => $request->message]]),
            'status'    => $result->status ? 'success' : 'failed',
            'send_by'   => 'web',
        ]);

        // Increment device message_sent counter
        $device->increment('message_sent');

        $updates = ['last_message' => $request->message, 'last_message_at' => now()];
        // Record first agent response time for SLA tracking
        if (!$conversation->first_response_at && $conversation->assigned_at) {
            $updates['first_response_at'] = now();
        }
        $conversation->update($updates);

        // Push outbound message to chat UI via Socket.io
        SocketPushService::pushToConversation($conversation->id, 'new_message', [
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'text',
            'body'            => $request->message,
            'created_at'      => now()->toISOString(),
        ]);

        if (!$result->status) {
            $reason = $result->error ?? __('Failed to send.');
            // Common Meta error: can't send free-form text outside 24h window
            if (str_contains((string)$reason, '131047') || str_contains((string)$reason, 'outside the allowed window')) {
                $reason = __('Cannot send free-form message — contact must message you first, or use an approved template.');
            }
            return response()->json(['error' => true, 'message' => $reason], 422);
        }

        return response()->json(['error' => false, 'message' => $this->formatMessage($chatMessage)]);
    }

    public function sendTemplate(Request $request, $id)
    {
        $request->validate(['template_id' => 'required|exists:waba_templates,id']);

        $conversation = $request->user()->conversations()->with('device')->findOrFail($id);
        $device       = $conversation->device;
        $template     = \App\Models\WabaTemplate::where('user_id', $request->user()->id)
            ->where('status', 'APPROVED')
            ->findOrFail($request->template_id);

        if ($device->status !== 'Connected') {
            return response()->json(['error' => true, 'message' => __('Device is not connected.')], 422);
        }

        $service = new MetaCloudApiService($device);

        // Build variable components from request
        $bodyVars = [];
        foreach ($request->vars ?? [] as $k => $v) {
            if ($v !== '') $bodyVars[] = ['type' => 'text', 'text' => (string) $v];
        }
        $components = $bodyVars ? [['type' => 'body', 'parameters' => $bodyVars]] : [];

        $result = $service->sendBlastTemplate(
            new \App\Models\Blast([
                'receiver'           => $conversation->contact_number,
                'template_variables' => array_values($request->vars ?? []),
            ]),
            ['name' => $template->name, 'language' => $template->language]
        );

        if (!$result->status) {
            return response()->json(['error' => true, 'message' => $result->error ?? 'Template send failed'], 422);
        }

        $preview = "[Template: {$template->name}]";
        $chatMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'template',
            'body'            => $preview,
            'meta_message_id' => $result->message_id ?? null,
            'status'          => 'sent',
        ]);

        MessageHistory::create([
            'user_id'   => $request->user()->id,
            'device_id' => $device->id,
            'number'    => $conversation->contact_number,
            'type'      => 'template',
            'message'   => $preview,
            'payload'   => json_encode(['template' => $template->name]),
            'status'    => 'success',
            'send_by'   => 'web',
        ]);

        $conversation->update(['last_message' => $preview, 'last_message_at' => now()]);
        $device->increment('message_sent');

        return response()->json(['error' => false, 'message' => $this->formatMessage($chatMessage)]);
    }

    public function start(Request $request)
    {
        $request->validate([
            'contact_number' => 'required|string',
            'device_id'      => 'required|exists:devices,id',
        ]);

        $device = $request->user()->devices()->findOrFail($request->device_id);

        $number = preg_replace('/[^0-9]/', '', $request->contact_number);

        $conversation = Conversation::firstOrCreate(
            [
                'user_id'        => $request->user()->id,
                'device_id'      => $device->id,
                'contact_number' => $number,
            ],
            [
                'contact_name'    => $request->contact_name ?: null,
                'last_message_at' => now(),
            ]
        );

        return redirect()->route('chat.show', $conversation->id);
    }

    /**
     * Resolve a human-assigned chat and reactivate the bot for future messages.
     */
    public function resolveBot(Request $request, $id)
    {
        $conversation = $request->user()->conversations()->findOrFail($id);

        FlowEngine::reactivateBot($conversation->id);

        return response()->json(['error' => false, 'message' => __('Chat resolved. Bot reactivated for future messages.')]);
    }

    /**
     * Return active bot session status for a conversation (for UI polling).
     */
    public function botStatus(Request $request, $id)
    {
        $conversation = $request->user()->conversations()->findOrFail($id);
        $session      = ChatbotSession::activeFor($conversation->id);

        return response()->json([
            'has_session'  => (bool) $session,
            'state'        => $session?->state,
            'flow_name'    => $session?->flow?->name,
            'flow_id'      => $session?->flow_id,
        ]);
    }

    // ── Feature 3: Typing indicator ────────────────────────────────────────

    public function typing(Request $request, $id)
    {
        $conversation = $request->user()->conversations()->findOrFail($id);
        $agentName    = $request->user()->name ?? 'Agent';

        SocketPushService::pushToConversation($conversation->id, 'agent_typing', [
            'conversation_id' => $conversation->id,
            'agent_name'      => $agentName,
            'typing'          => (bool) $request->input('typing', true),
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Feature 3: Notes CRUD ──────────────────────────────────────────────

    public function storeNote(Request $request, $id)
    {
        $request->validate(['note' => 'required|string|max:2000']);
        $conversation = $request->user()->conversations()->findOrFail($id);

        // Try to find the agent record matching the current user (by email or name)
        $agent = Agent::where('user_id', $request->user()->id)
            ->where(fn ($q) => $q->where('email', $request->user()->email)
                ->orWhere('name', $request->user()->name))
            ->first();

        $note = ChatNote::create([
            'conversation_id' => $conversation->id,
            'agent_id'        => $agent?->id,
            'agent_name'      => $agent?->name ?? $request->user()->name,
            'note'            => $request->note,
            'is_internal'     => (bool) $request->input('is_internal', true),
        ]);

        // Push whisper to other agents viewing this conversation
        SocketPushService::pushToConversation($conversation->id, 'new_note', [
            'id'          => $note->id,
            'author'      => $note->author,
            'note'        => $note->note,
            'is_internal' => $note->is_internal,
            'time'        => $note->created_at->format('H:i'),
        ]);

        return response()->json(['ok' => true, 'note' => [
            'id'          => $note->id,
            'author'      => $note->author,
            'note'        => $note->note,
            'is_internal' => $note->is_internal,
            'time'        => $note->created_at->format('H:i d M'),
        ]]);
    }

    // ── Feature 3: Contact attributes ─────────────────────────────────────

    public function saveAttribute(Request $request, $id)
    {
        $request->validate([
            'key'   => 'required|string|max:64',
            'value' => 'nullable|string|max:255',
        ]);
        $conversation = $request->user()->conversations()->findOrFail($id);

        ContactAttribute::setFor(
            $request->user()->id,
            $conversation->contact_number,
            $request->key,
            $request->value
        );

        return response()->json(['ok' => true]);
    }

    // ── Feature 3: Agent assignment ────────────────────────────────────────

    public function assign(Request $request, $id)
    {
        $request->validate(['agent_id' => 'required|exists:agents,id']);
        $conversation = $request->user()->conversations()->findOrFail($id);
        $agent        = Agent::where('user_id', $request->user()->id)->findOrFail($request->agent_id);

        (new ChatRouter())->manualAssign($conversation, $agent);

        return response()->json(['ok' => true, 'agent_name' => $agent->name]);
    }

    public function unassign(Request $request, $id)
    {
        $conversation = $request->user()->conversations()->findOrFail($id);

        if ($conversation->assigned_agent_id) {
            $old = Agent::find($conversation->assigned_agent_id);
            $old?->decrement('active_chat_count');
        }

        $conversation->update(['assigned_agent_id' => null, 'assignment_source' => null]);

        SocketPushService::pushToInbox($conversation->user_id, 'inbox_update', [
            'conversation_id'    => $conversation->id,
            'event'              => 'unassigned',
            'assigned_agent_id'  => null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function resolve(Request $request, $id)
    {
        $conversation = $request->user()->conversations()->findOrFail($id);

        if ($conversation->assigned_agent_id) {
            $agent = Agent::find($conversation->assigned_agent_id);
            $agent?->decrement('active_chat_count');
            $agent?->syncActiveCount();
        }

        $conversation->update([
            'conversation_status' => 'resolved',
            'resolved_at'         => now(),
        ]);

        SocketPushService::pushToConversation($conversation->id, 'conversation_updated', [
            'conversation_id' => $conversation->id,
            'event'           => 'resolved',
        ]);

        // Push to inbox room so the sidebar updates status without page refresh
        SocketPushService::pushToInbox($conversation->user_id, 'inbox_update', [
            'conversation_id'     => $conversation->id,
            'event'               => 'resolved',
            'conversation_status' => 'resolved',
        ]);

        // CSAT: send rating template if one is configured for this account
        $this->sendCsatTemplate($conversation);

        return response()->json(['ok' => true]);
    }

    private function sendCsatTemplate(\App\Models\Conversation $conversation): void
    {
        try {
            // Look for an approved template whose name starts with 'csat' or 'rating'
            $csatTemplate = \App\Models\WabaTemplate::where('user_id', $conversation->user_id)
                ->where('device_id', $conversation->device_id)
                ->where('status', 'APPROVED')
                ->where(function ($q) {
                    $q->where('name', 'like', 'csat%')
                      ->orWhere('name', 'like', 'rating%')
                      ->orWhere('name', 'like', 'feedback%');
                })
                ->first();

            if (!$csatTemplate || !$conversation->device) {
                return;
            }

            $service = new MetaCloudApiService($conversation->device);
            $result  = $service->sendBlastTemplate(
                new \App\Models\Blast(['receiver' => $conversation->contact_number, 'template_variables' => []]),
                ['name' => $csatTemplate->name, 'language' => $csatTemplate->language]
            );

            if ($result->status) {
                ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'direction'       => 'outbound',
                    'type'            => 'template',
                    'body'            => "[CSAT: {$csatTemplate->name}]",
                    'meta_message_id' => $result->message_id ?? null,
                    'status'          => 'sent',
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('CSAT template send skipped: ' . $e->getMessage());
        }
    }

    private function formatMessage(ChatMessage $m): array
    {
        return [
            'id'        => $m->id,
            'direction' => $m->direction,
            'type'      => $m->type,
            'body'      => $m->body,
            'media_url' => $m->media_url,
            'status'    => $m->status,
            'time'      => $m->created_at->format('H:i'),
            'date'      => $m->created_at->toDateString(),
        ];
    }
}
