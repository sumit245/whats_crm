<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatNote;
use App\Models\ChatbotSession;
use App\Models\ContactAttribute;
use App\Models\Conversation;
use App\Models\ConversationLabel;
use App\Models\Device;
use App\Models\MessageHistory;
use App\Models\QuickReply;
use App\Services\ChatRouter;
use App\Services\FlowEngine;
use App\Services\Impl\MetaCloudApiService;
use App\Services\SocketPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // Returns the account-owner User and the logged-in agent (if any)
    private function resolveOwner(Request $request): array
    {
        $user = $request->user();
        if ($user->agent_id) {
            $agent = $user->agent()->with('user')->first();
            $owner = $agent->user;
            return [$owner, $agent];
        }
        return [$user, null];
    }

    public function index(Request $request)
    {
        [$owner, $loggedInAgent] = $this->resolveOwner($request);

        $devices = $owner->devices()->where('status', 'Connected')->get();

        $deviceId     = $request->device_id ?? session('selectedDevice.device_id');
        $agentFilter  = $request->agent_id;
        $teamFilter   = $request->team_id;
        $statusFilter = $request->conv_status ?? '';

        $isSupervisor = $request->boolean('global') ||
            ($loggedInAgent ? $loggedInAgent->isSupervisor() : true);

        $conversations = $owner->conversations()
            ->when($deviceId,     fn ($q) => $q->where('device_id', $deviceId))
            ->when($agentFilter,  fn ($q) => $q->where('assigned_agent_id', $agentFilter))
            ->when($teamFilter,   fn ($q) => $q->whereHas('assignedAgent', fn ($aq) => $aq->where('team_id', $teamFilter)))
            ->when($statusFilter, fn ($q) => $q->where('conversation_status', $statusFilter))
            ->when($request->sla_only, fn ($q) => $q->where('sla_breached', true))
            ->when($request->unassigned_only, fn ($q) => $q->whereNull('assigned_agent_id'))
            ->when($request->label_id, fn ($q) => $q->whereHas('labels', fn ($lq) => $lq->where('conversation_labels.id', $request->label_id)))
            // Non-supervisor agents only see their own assigned conversations
            ->when($loggedInAgent && !$loggedInAgent->isSupervisor(),
                fn ($q) => $q->where('assigned_agent_id', $loggedInAgent->id))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('contact_name', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_number', 'like', '%' . $request->search . '%');
            }))
            ->with(['device', 'assignedAgent', 'labels'])
            ->orderByDesc('sla_breached')
            ->orderByDesc('last_message_at')
            ->paginate(30);

        $agents     = Agent::where('user_id', $owner->id)->with('team')->get();
        $teams      = \App\Models\Team::where('user_id', $owner->id)->get();
        $userLabels = ConversationLabel::where('user_id', $owner->id)->orderBy('sort_order')->orderBy('name')->get();

        return view('theme::pages.chat.index', compact(
            'conversations', 'devices', 'deviceId', 'agents', 'teams',
            'statusFilter', 'isSupervisor', 'loggedInAgent', 'userLabels'
        ));
    }

    public function show(Request $request, $id)
    {
        [$owner, $loggedInAgent] = $this->resolveOwner($request);

        $conversation = $owner->conversations()
            ->with(['device', 'assignedAgent.team', 'notes.agent', 'labels'])
            ->findOrFail($id);

        // Non-supervisor agents can only open their own assigned conversations
        if ($loggedInAgent && !$loggedInAgent->isSupervisor()) {
            abort_if($conversation->assigned_agent_id !== $loggedInAgent->id, 403);
        }

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

        $devices      = $owner->devices()->where('status', 'Connected')->get();
        $deviceId     = $request->device_id ?? session('selectedDevice.device_id');
        $statusFilter = $request->conv_status ?? '';
        $agentFilter  = $request->agent_id;
        $teamFilter   = $request->team_id;

        $isSupervisor = $request->boolean('global') ||
            ($loggedInAgent ? $loggedInAgent->isSupervisor() : true);

        // Sidebar conversation list
        $conversations = $owner->conversations()
            ->when($deviceId,              fn ($q) => $q->where('device_id', $deviceId))
            ->when($agentFilter,           fn ($q) => $q->where('assigned_agent_id', $agentFilter))
            ->when($teamFilter,            fn ($q) => $q->whereHas('assignedAgent', fn ($aq) => $aq->where('team_id', $teamFilter)))
            ->when($statusFilter,          fn ($q) => $q->where('conversation_status', $statusFilter))
            ->when($request->sla_only,     fn ($q) => $q->where('sla_breached', true))
            ->when($request->unassigned_only, fn ($q) => $q->whereNull('assigned_agent_id'))
            ->when($request->label_id,     fn ($q) => $q->whereHas('labels', fn ($lq) => $lq->where('conversation_labels.id', $request->label_id)))
            ->when($loggedInAgent && !$loggedInAgent->isSupervisor(),
                fn ($q) => $q->where('assigned_agent_id', $loggedInAgent->id))
            ->with(['device', 'assignedAgent', 'labels'])
            ->orderByDesc('sla_breached')
            ->orderByDesc('last_message_at')
            ->paginate(30);

        // Approved templates for this device
        $approvedTemplates = \App\Models\WabaTemplate::where('user_id', $owner->id)
            ->where('device_id', $conversation->device_id)
            ->where('status', 'APPROVED')
            ->get(['id', 'name', 'category', 'language', 'components']);

        $agents = Agent::where('user_id', $owner->id)->with('team')->get();
        $teams  = \App\Models\Team::where('user_id', $owner->id)->get();

        // CRM right panel
        $contactAttributes = ContactAttribute::allFor(
            $owner->id,
            $conversation->contact_number
        );
        $contactTags = \App\Models\Contact::where('user_id', $owner->id)
            ->where('number', $conversation->contact_number)
            ->with('tags')
            ->first()
            ?->tags
            ->pluck('name')
            ->unique()
            ->values() ?? collect();

        $quickReplies = QuickReply::where('user_id', $owner->id)
            ->orderBy('shortcut')
            ->limit(50)
            ->get(['shortcut', 'title', 'body']);

        $userLabels = ConversationLabel::where('user_id', $owner->id)->orderBy('sort_order')->orderBy('name')->get();

        return view('theme::pages.chat.index', compact(
            'conversation', 'messages', 'timeline', 'conversations', 'devices',
            'deviceId', 'approvedTemplates', 'agents', 'teams',
            'statusFilter', 'isSupervisor', 'contactAttributes', 'contactTags',
            'quickReplies', 'loggedInAgent', 'userLabels'
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

        // Recent outbound statuses — lets the polling fallback refresh delivery
        // ticks (sent/delivered/read) on already-rendered messages, since the
        // socket push server isn't always available.
        $recentStatuses = $conversation->messages()
            ->where('direction', 'outbound')
            ->orderByDesc('id')
            ->take(20)
            ->get(['id', 'status'])
            ->map(fn ($m) => ['id' => $m->id, 'status' => $m->status]);

        return response()->json([
            'messages'        => $messages,
            'unread'          => $conversation->fresh()->unread_count,
            'recent_statuses' => $recentStatuses,
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

        $preview = $this->renderTemplateBody($template, $request->vars ?? []);
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

    // ── Attachment: file upload ────────────────────────────────────────────────

    public function uploadMedia(Request $request, $id)
    {
        $request->validate(['file' => 'required|file|max:65536']);
        $request->user()->conversations()->findOrFail($id); // auth check

        $path = $request->file('file')->store('chat-media', 'public');
        $url  = \Illuminate\Support\Facades\Storage::url($path);
        // Prepend APP_URL host if the url is relative
        if (!str_starts_with($url, 'http')) {
            $url = rtrim(config('app.url'), '/') . $url;
        }

        return response()->json(['url' => $url]);
    }

    // ── Attachment: send media (image / video / document / audio) ─────────────

    public function sendMedia(Request $request, $id)
    {
        $request->validate([
            'url'        => 'required|url',
            'media_type' => 'required|in:image,video,document,audio',
            'caption'    => 'nullable|string|max:1024',
        ]);

        $conversation = $request->user()->conversations()->with('device')->findOrFail($id);
        $device       = $conversation->device;

        if ($device->status !== 'Connected') {
            return response()->json(['error' => true, 'message' => __('Device is not connected.')], 422);
        }

        $service = new MetaCloudApiService($device);
        $fakeReq = (object) [
            'media_type' => $request->media_type,
            'url'        => $request->url,
            'caption'    => $request->caption ?? '',
        ];
        $result = $service->sendMedia($fakeReq, $conversation->contact_number);

        $status  = $result->status ? 'sent' : 'failed';
        $preview = '[' . ucfirst($request->media_type) . ($request->caption ? ': ' . $request->caption : '') . ']';

        $chatMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => $request->media_type,
            'body'            => $preview,
            'media_url'       => $request->url,
            'meta_message_id' => $result->message_id ?? null,
            'status'          => $status,
        ]);

        $conversation->update(['last_message' => $preview, 'last_message_at' => now()]);
        $device->increment('message_sent');

        SocketPushService::pushToConversation($conversation->id, 'new_message', [
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => $request->media_type,
            'body'            => $preview,
            'created_at'      => now()->toISOString(),
        ]);

        if (!$result->status) {
            return response()->json(['error' => true, 'message' => $result->error ?? __('Failed to send.')], 422);
        }

        return response()->json(['error' => false, 'message' => $this->formatMessage($chatMessage)]);
    }

    // ── Attachment: send poll ─────────────────────────────────────────────────

    public function sendPoll(Request $request, $id)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'options'   => 'required|array|min:2|max:10',
            'options.*' => 'required|string|max:100',
        ]);

        $conversation = $request->user()->conversations()->with('device')->findOrFail($id);
        $device       = $conversation->device;

        if ($device->status !== 'Connected') {
            return response()->json(['error' => true, 'message' => __('Device is not connected.')], 422);
        }

        $service = new MetaCloudApiService($device);
        $fakeReq = (object) [
            'name'      => $request->name,
            'option'    => $request->options,
            'countable' => (int) $request->input('countable', 1),
        ];
        $result = $service->sendPoll($fakeReq, $conversation->contact_number);

        $status  = $result->status ? 'sent' : 'failed';
        $preview = '📊 ' . $request->name;

        $chatMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'poll',
            'body'            => $preview,
            'meta_message_id' => $result->message_id ?? null,
            'status'          => $status,
        ]);

        $conversation->update(['last_message' => $preview, 'last_message_at' => now()]);
        $device->increment('message_sent');

        SocketPushService::pushToConversation($conversation->id, 'new_message', [
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'poll',
            'body'            => $preview,
            'created_at'      => now()->toISOString(),
        ]);

        if (!$result->status) {
            return response()->json(['error' => true, 'message' => $result->error ?? __('Failed to send.')], 422);
        }

        return response()->json(['error' => false, 'message' => $this->formatMessage($chatMessage)]);
    }

    // ── Attachment: send contact (vCard) ──────────────────────────────────────

    public function sendContact(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'required|string|max:30',
        ]);

        $conversation = $request->user()->conversations()->with('device')->findOrFail($id);
        $device       = $conversation->device;

        if ($device->status !== 'Connected') {
            return response()->json(['error' => true, 'message' => __('Device is not connected.')], 422);
        }

        $service = new MetaCloudApiService($device);
        $fakeReq = (object) ['name' => $request->name, 'phone' => $request->phone];
        $result  = $service->sendVcard($fakeReq, $conversation->contact_number);

        $status  = $result->status ? 'sent' : 'failed';
        $preview = '👤 ' . $request->name . ' (' . $request->phone . ')';

        $chatMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'contact',
            'body'            => $preview,
            'meta_message_id' => $result->message_id ?? null,
            'status'          => $status,
        ]);

        $conversation->update(['last_message' => $preview, 'last_message_at' => now()]);
        $device->increment('message_sent');

        SocketPushService::pushToConversation($conversation->id, 'new_message', [
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'contact',
            'body'            => $preview,
            'created_at'      => now()->toISOString(),
        ]);

        if (!$result->status) {
            return response()->json(['error' => true, 'message' => $result->error ?? __('Failed to send.')], 422);
        }

        return response()->json(['error' => false, 'message' => $this->formatMessage($chatMessage)]);
    }

    // ── Attachment: send catalogue ────────────────────────────────────────────

    public function sendCatalog(Request $request, $id)
    {
        $request->validate([
            'catalog_id' => 'required|string|max:100',
            'body'       => 'nullable|string|max:1024',
        ]);

        $conversation = $request->user()->conversations()->with('device')->findOrFail($id);
        $device       = $conversation->device;

        if ($device->status !== 'Connected') {
            return response()->json(['error' => true, 'message' => __('Device is not connected.')], 422);
        }

        $service = new MetaCloudApiService($device);
        $result  = $service->sendCatalog(
            $conversation->contact_number,
            $request->catalog_id,
            $request->body ?? ''
        );

        $status  = $result->status ? 'sent' : 'failed';
        $preview = '🛍 ' . __('Catalogue') . ($request->body ? ': ' . $request->body : '');

        $chatMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'catalog',
            'body'            => $preview,
            'meta_message_id' => $result->message_id ?? null,
            'status'          => $status,
        ]);

        $conversation->update(['last_message' => $preview, 'last_message_at' => now()]);
        $device->increment('message_sent');

        SocketPushService::pushToConversation($conversation->id, 'new_message', [
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'catalog',
            'body'            => $preview,
            'created_at'      => now()->toISOString(),
        ]);

        if (!$result->status) {
            return response()->json(['error' => true, 'message' => $result->error ?? __('Failed to send.')], 422);
        }

        return response()->json(['error' => false, 'message' => $this->formatMessage($chatMessage)]);
    }

    // ── Attachment: send meeting link (cta_url interactive) ───────────────────

    public function sendMeetingLink(Request $request, $id)
    {
        $request->validate([
            'url'          => 'required|url',
            'display_text' => 'nullable|string|max:20',
            'body'         => 'nullable|string|max:1024',
        ]);

        $conversation = $request->user()->conversations()->with('device')->findOrFail($id);
        $device       = $conversation->device;

        if ($device->status !== 'Connected') {
            return response()->json(['error' => true, 'message' => __('Device is not connected.')], 422);
        }

        $service     = new MetaCloudApiService($device);
        $displayText = $request->display_text ?: 'Join Meeting';
        $body        = $request->body ?: $request->url;
        $result      = $service->sendCtaUrl($conversation->contact_number, $request->url, $displayText, $body);

        $status  = $result->status ? 'sent' : 'failed';
        $preview = '🔗 ' . $displayText . ': ' . $request->url;

        $chatMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'meeting_link',
            'body'            => $preview,
            'meta_message_id' => $result->message_id ?? null,
            'status'          => $status,
        ]);

        $conversation->update(['last_message' => $preview, 'last_message_at' => now()]);
        $device->increment('message_sent');

        SocketPushService::pushToConversation($conversation->id, 'new_message', [
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'meeting_link',
            'body'            => $preview,
            'created_at'      => now()->toISOString(),
        ]);

        if (!$result->status) {
            return response()->json(['error' => true, 'message' => $result->error ?? __('Failed to send.')], 422);
        }

        return response()->json(['error' => false, 'message' => $this->formatMessage($chatMessage)]);
    }

    /**
     * Render the human-readable text of a WABA template (header + body + footer)
     * with body variables substituted, so the chat shows the actual message that
     * was sent rather than a "[Template: name]" placeholder.
     */
    private function renderTemplateBody(\App\Models\WabaTemplate $template, array $vars = []): string
    {
        // Body variables are positional: {{1}} -> first value, {{2}} -> second, ...
        $values = array_values($vars);
        $parts  = [];

        foreach ($template->components ?? [] as $comp) {
            $type = strtoupper($comp['type'] ?? '');
            $text = $comp['text'] ?? '';
            if ($text === '') {
                continue;
            }
            // Only TEXT headers carry displayable text; image/video/document headers have none.
            if ($type === 'HEADER' && strtoupper($comp['format'] ?? 'TEXT') !== 'TEXT') {
                continue;
            }
            if (in_array($type, ['HEADER', 'BODY', 'FOOTER'], true)) {
                $parts[] = $this->fillTemplateVars($text, $values);
            }
        }

        $rendered = trim(implode("\n\n", $parts));

        return $rendered !== '' ? $rendered : "[Template: {$template->name}]";
    }

    /**
     * Replace {{1}}, {{2}}, ... placeholders with the provided positional values.
     * Unmatched placeholders are left intact.
     */
    private function fillTemplateVars(string $text, array $values): string
    {
        return preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', function ($m) use ($values) {
            $idx = (int) $m[1] - 1;
            return $values[$idx] ?? $m[0];
        }, $text);
    }

    public function start(Request $request)
    {
        $request->validate([
            'contact_number' => 'required|string',
            'device_id'      => 'required|exists:devices,id',
        ]);

        $device = $request->user()->devices()->findOrFail($request->device_id);

        $number = Conversation::normalizeContactNumber($request->contact_number);

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
        $request->validate([
            'note'      => 'required|string|max:500000',
            'title'     => 'nullable|string|max:120',
            'note_type' => 'nullable|string|in:text,voice,drawing',
        ]);
        $conversation = $request->user()->conversations()->findOrFail($id);

        $agent = Agent::where('user_id', $request->user()->id)
            ->where(fn ($q) => $q->where('email', $request->user()->email)
                ->orWhere('name', $request->user()->name))
            ->first();

        $note = ChatNote::create([
            'conversation_id' => $conversation->id,
            'agent_id'        => $agent?->id,
            'agent_name'      => $agent?->name ?? $request->user()->name,
            'title'           => $request->input('title') ?: null,
            'note_type'       => $request->input('note_type', 'text'),
            'note'            => $request->note,
            'is_internal'     => (bool) $request->input('is_internal', true),
        ]);

        SocketPushService::pushToConversation($conversation->id, 'new_note', $this->formatNote($note));

        return response()->json(['ok' => true, 'note' => $this->formatNote($note)]);
    }

    public function updateNote(Request $request, $id, $noteId)
    {
        $request->validate([
            'note'      => 'required|string|max:500000',
            'title'     => 'nullable|string|max:120',
            'note_type' => 'nullable|string|in:text,voice,drawing',
        ]);
        $conversation = $request->user()->conversations()->findOrFail($id);
        $note         = ChatNote::where('conversation_id', $conversation->id)->findOrFail($noteId);

        $note->update([
            'title'       => $request->input('title') ?: null,
            'note_type'   => $request->input('note_type', $note->note_type),
            'note'        => $request->note,
            'is_internal' => (bool) $request->input('is_internal', $note->is_internal),
        ]);

        SocketPushService::pushToConversation($conversation->id, 'updated_note', $this->formatNote($note));

        return response()->json(['ok' => true, 'note' => $this->formatNote($note)]);
    }

    public function destroyNote(Request $request, $id, $noteId)
    {
        $conversation = $request->user()->conversations()->findOrFail($id);
        $note         = ChatNote::where('conversation_id', $conversation->id)->findOrFail($noteId);
        $note->delete();

        SocketPushService::pushToConversation($conversation->id, 'deleted_note', ['id' => $noteId]);

        return response()->json(['ok' => true]);
    }

    public function uploadNoteMedia(Request $request, $id)
    {
        $request->validate(['file' => 'required|file|max:20480']);
        $request->user()->conversations()->findOrFail($id);

        $file = $request->file('file');
        $path = $file->store("chat-notes/{$id}", 'public');

        return response()->json([
            'url'  => \Storage::disk('public')->url($path),
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
        ]);
    }

    public function printNote(Request $request, $id, $noteId)
    {
        $conversation = $request->user()->conversations()->findOrFail($id);
        $note         = ChatNote::where('conversation_id', $conversation->id)->findOrFail($noteId);

        return view('theme::pages.chat.note-print', compact('note', 'conversation'));
    }

    private function formatNote(\App\Models\ChatNote $note): array
    {
        return [
            'id'          => $note->id,
            'title'       => $note->title,
            'note_type'   => $note->note_type ?? 'text',
            'author'      => $note->author,
            'note'        => $note->note,
            'is_internal' => $note->is_internal,
            'time'        => $note->created_at->format('H:i d M'),
        ];
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

    // ── Labels ────────────────────────────────────────────────────────────────

    public function labelsIndex(Request $request)
    {
        [$owner] = $this->resolveOwner($request);
        $labels = ConversationLabel::where('user_id', $owner->id)
            ->orderBy('sort_order')->orderBy('name')->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['labels' => $labels]);
        }

        return view('theme::pages.labels.index', compact('labels'));
    }

    public function labelsStore(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:64',
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        [$owner] = $this->resolveOwner($request);

        $label = ConversationLabel::create([
            'user_id' => $owner->id,
            'name'    => $request->name,
            'color'   => $request->color,
        ]);

        return response()->json(['error' => false, 'label' => $label]);
    }

    public function labelsUpdate(Request $request, $labelId)
    {
        $request->validate([
            'name'  => 'required|string|max:64',
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        [$owner] = $this->resolveOwner($request);
        $label = ConversationLabel::where('user_id', $owner->id)->findOrFail($labelId);
        $label->update(['name' => $request->name, 'color' => $request->color]);

        return response()->json(['error' => false, 'label' => $label]);
    }

    public function labelsReorder(Request $request)
    {
        [$owner] = $this->resolveOwner($request);
        foreach ($request->order ?? [] as $i => $id) {
            ConversationLabel::where('user_id', $owner->id)->where('id', $id)
                ->update(['sort_order' => $i]);
        }
        return response()->json(['error' => false]);
    }

    public function labelsDestroy(Request $request, $labelId)
    {
        [$owner] = $this->resolveOwner($request);
        $label = ConversationLabel::where('user_id', $owner->id)->findOrFail($labelId);
        $label->delete();
        return response()->json(['error' => false]);
    }

    public function attachLabel(Request $request, $id, $labelId)
    {
        [$owner] = $this->resolveOwner($request);
        $conversation = $owner->conversations()->findOrFail($id);
        $label = ConversationLabel::where('user_id', $owner->id)->findOrFail($labelId);
        $conversation->labels()->syncWithoutDetaching([$label->id]);
        return response()->json(['error' => false]);
    }

    public function detachLabel(Request $request, $id, $labelId)
    {
        [$owner] = $this->resolveOwner($request);
        $conversation = $owner->conversations()->findOrFail($id);
        $conversation->labels()->detach($labelId);
        return response()->json(['error' => false]);
    }
}
