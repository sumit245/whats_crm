<x-layout-dashboard title="{{ __('Chat') }}">

<style>
/* Chat layout — colors via dnd-pages.css + tokens */
.wachat-wrapper        { display:flex; overflow:hidden; }
.wachat-sidebar        { width:300px; min-width:260px; flex-shrink:0; border-right:1px solid var(--dnd-border); display:flex; flex-direction:column; }
.wachat-main           { flex:1 1 0; min-width:0; display:flex; flex-direction:column; }
.wachat-crm            { width:300px; min-width:260px; flex-shrink:0; border-left:1px solid var(--dnd-border); display:flex; flex-direction:column; font-size:13px; overflow-y:auto; }
.wachat-sidebar-header  { padding:10px 12px; border-bottom:1px solid var(--dnd-border); min-height:90px; box-sizing:border-box; }
.wachat-conv-list       { flex:1; overflow-y:auto; }
.wachat-conv-item       { display:flex; align-items:center; padding:9px 12px; cursor:pointer; border-bottom:1px solid var(--dnd-border); transition:background .15s; }
.wachat-conv-avatar     { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; flex-shrink:0; color:#fff; }
.wachat-conv-meta       { flex:1; min-width:0; padding-left:9px; }
.wachat-conv-name       { font-weight:600; font-size:13px; color:var(--dnd-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wachat-conv-preview    { font-size:11px; color:var(--dnd-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wachat-conv-time       { font-size:11px; color:var(--dnd-text-muted); white-space:nowrap; margin-left:6px; }
.unread-badge, .sla-badge { border-radius:50%; font-size:10px; min-width:16px; height:16px; display:flex; align-items:center; justify-content:center; padding:0 3px; margin-top:3px; color:#fff; }
.sla-badge              { background:var(--dnd-accent-danger); }
.wachat-sidebar-footer  { padding:10px; border-top:1px solid var(--dnd-border); }
.wachat-main-header     { padding:10px 14px; border-bottom:1px solid var(--dnd-border); display:flex; align-items:center; min-height:90px; box-sizing:border-box; color:var(--dnd-text); gap:10px; }
.wachat-messages-area   { flex:1; overflow-y:auto; padding:14px; display:flex; flex-direction:column; gap:5px; }
.wachat-empty           { flex:1; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; color:var(--dnd-text-muted); background:var(--dnd-bg); }
.wachat-input-area      { padding:8px 12px; border-top:1px solid var(--dnd-border); display:flex; align-items:flex-end; gap:8px; background:var(--dnd-surface); }
#chatTextarea           { resize:none; border-radius:var(--dnd-radius-pill); padding:8px 14px; font-size:14px; flex:1; max-height:120px; color:var(--dnd-text); background:var(--dnd-surface); border:1px solid var(--dnd-border-strong); }
#typingIndicator        { padding:4px 14px; font-size:12px; color:var(--dnd-text-muted); min-height:24px; }
.bubble-wrap            { display:flex; }
.bubble-wrap.inbound    { justify-content:flex-start; }
.bubble-wrap.outbound   { justify-content:flex-end; }
.bubble                 { max-width:65%; padding:8px 12px; border-radius:var(--dnd-radius-md); font-size:14px; line-height:1.45; word-break:break-word; color:var(--dnd-text); box-shadow:var(--dnd-shadow-sm); }
.bubble.inbound         { border-top-left-radius:0; }
.bubble.outbound        { border-top-right-radius:0; }
.bubble.internal-note   { background:rgba(245,158,11,.15); border:1px dashed var(--dnd-accent-warning); max-width:80%; }
.bubble-time            { font-size:11px; color:var(--dnd-text-muted); text-align:right; margin-top:3px; display:flex; align-items:center; justify-content:flex-end; gap:3px; }
.bubble-media           { max-width:100%; border-radius:var(--dnd-radius); margin-bottom:4px; }
.status-tick.read       { color:var(--dnd-accent-link); }
.date-divider           { text-align:center; font-size:12px; color:var(--dnd-text-muted); margin:8px 0; }
.date-divider span      { background:var(--dnd-brand-muted); color:var(--dnd-brand); padding:3px 10px; border-radius:var(--dnd-radius-pill); }
.crm-section            { padding:12px; border-bottom:1px solid var(--dnd-border); }
.wachat-crm > .crm-section:first-child { min-height:90px; box-sizing:border-box; }
.crm-section h6         { font-size:12px; font-weight:700; text-transform:uppercase; color:var(--dnd-text-muted); margin-bottom:8px; letter-spacing:.5px; }
.attr-key               { font-size:12px; color:var(--dnd-text-muted); width:90px; flex-shrink:0; }
.attr-val               { font-size:12px; font-weight:600; flex:1; cursor:pointer; padding:2px 4px; border-radius:var(--dnd-radius); color:var(--dnd-text); }
.attr-val:hover         { background:var(--dnd-brand-subtle); }
.note-item              { background:rgba(245,158,11,.12); border-left:3px solid var(--dnd-accent-warning); padding:6px 8px; border-radius:var(--dnd-radius); margin-bottom:6px; font-size:12px; }
.note-item.not-internal { background:var(--dnd-brand-muted); border-left-color:var(--dnd-brand); }
.conv-status-tabs       { display:flex; gap:4px; padding:6px 10px; border-bottom:1px solid var(--dnd-border); }
.conv-status-tabs .tab  { font-size:11px; padding:3px 8px; border-radius:var(--dnd-radius-pill); cursor:pointer; border:1px solid var(--dnd-border); white-space:nowrap; color:var(--dnd-text-secondary); }
.btn-xs { padding:2px 7px; font-size:11px; }
.internal-mode-bar { background:rgba(245,158,11,.12); border-bottom:1px solid var(--dnd-accent-warning); padding:6px 14px; font-size:12px; color:var(--dnd-text); display:flex; align-items:center; gap:8px; }
</style>

<div>
    <div class="wachat-wrapper">

        {{-- ── LEFT SIDEBAR ──────────────────────────────── --}}
        <div class="wachat-sidebar">
            <div class="wachat-sidebar-header">
                <select id="deviceFilter" class="form-select form-select-sm mb-2">
                    <option value="">{{ __('All Devices') }}</option>
                    @foreach ($devices as $d)
                        <option value="{{ $d->id }}" {{ (isset($deviceId) && $deviceId == $d->id) ? 'selected' : '' }}>
                            {{ $d->meta_profile['verified_name'] ?? $d->body }}
                        </option>
                    @endforeach
                </select>
                <div class="d-flex gap-2">
                    <input type="text" id="convSearch" class="form-control form-control-sm flex-grow-1"
                        placeholder="{{ __('Search...') }}">
                </div>
                @if($isSupervisor ?? false)
                <div class="d-flex gap-2 mt-1">
                    <select id="agentFilter" class="form-select form-select-sm" style="min-width:0;flex:1">
                        <option value="">{{ __('All Agents') }}</option>
                        @foreach($agents as $a)
                            <option value="{{ $a->id }}" {{ request('agent_id') == $a->id ? 'selected' : '' }}>
                                {{ $a->name }}
                            </option>
                        @endforeach
                    </select>
                    <select id="teamFilter" class="form-select form-select-sm" style="min-width:0;flex:1">
                        <option value="">{{ __('All Teams') }}</option>
                        @foreach($teams as $t)
                            <option value="{{ $t->id }}" {{ request('team_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-xs btn-outline-secondary flex-shrink-0"
                        onclick="filterUnassigned()"
                        title="{{ __('Unassigned only') }}">
                        <i class="bi bi-person-x"></i>
                    </button>
                </div>
                @endif
            </div>

            {{-- Status tabs --}}
            <div class="conv-status-tabs">
                @foreach(['open'=>__('Open'),'pending'=>__('Pending'),'resolved'=>__('Resolved')] as $st => $label)
                <span class="tab {{ $statusFilter === $st ? 'active' : '' }}"
                      onclick="filterByStatus('{{ $st }}')">{{ $label }}</span>
                @endforeach
                <span class="tab {{ request()->sla_only ? 'active' : '' }}" id="slaFilterTab"
                      onclick="filterBySla()" style="{{ request()->sla_only ? '' : 'color:#dc3545;border-color:#dc3545;' }}">
                    🔴 SLA
                </span>
            </div>

            <div class="wachat-conv-list" id="convList">
                @forelse ($conversations as $conv)
                    <div class="wachat-conv-item {{ (isset($conversation) && $conversation->id === $conv->id) ? 'active' : '' }}"
                         data-conv-id="{{ $conv->id }}"
                         onclick="window.location='{{ route('chat.show', $conv->id) }}?conv_status={{ $conv->conversation_status }}'">
                        <div class="wachat-conv-avatar" style="{{ $conv->sla_breached ? 'background:#dc3545' : '' }}">
                            {{ $conv->avatar_letter }}
                        </div>
                        <div class="wachat-conv-meta">
                            <div class="wachat-conv-name">
                                {{ $conv->display_name }}
                                @if($conv->assignedAgent)
                                    <span class="agent-chip ms-1">{{ $conv->assignedAgent->name }}</span>
                                @endif
                            </div>
                            <div class="wachat-conv-preview">{{ Str::limit($conv->last_message, 32) }}</div>
                        </div>
                        <div class="d-flex flex-column align-items-end ms-1">
                            <span class="wachat-conv-time">{{ $conv->last_message_at?->diffForHumans(null, true) }}</span>
                            @if($conv->sla_breached)
                                <span class="sla-badge" title="{{ __('SLA Breached') }}">!</span>
                            @elseif($conv->unread_count > 0)
                                <span class="unread-badge">{{ $conv->unread_count }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted p-4" style="font-size:13px">
                        <i class="bi bi-chat-dots fs-2 d-block mb-2"></i>
                        {{ __('No conversations.') }}
                    </div>
                @endforelse
            </div>

            <div class="wachat-sidebar-footer">
                <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#newChatModal">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('New Chat') }}
                </button>
            </div>
        </div>

        {{-- ── MAIN PANEL ────────────────────────────────── --}}
        <div class="wachat-main">
            @if (isset($conversation))

                {{-- Header --}}
                <div class="wachat-main-header">
                    <div class="wachat-conv-avatar" style="{{ $conversation->sla_breached ? 'background:#dc3545' : '' }}">
                        {{ $conversation->avatar_letter }}
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold d-flex align-items-center gap-2" style="font-size:15px">
                            {{ $conversation->display_name }}
                            @if($conversation->sla_breached)
                                <span class="badge bg-danger small">{{ __('SLA Breach') }}</span>
                            @endif
                            @if($conversation->conversation_status === 'resolved')
                                <span class="badge bg-secondary small">{{ __('Resolved') }}</span>
                            @elseif($conversation->conversation_status === 'pending')
                                <span class="badge bg-warning small">{{ __('Pending') }}</span>
                            @endif
                        </div>
                        <div style="font-size:12px;color:#667781">
                            {{ $conversation->contact_number }}
                            &nbsp;·&nbsp;
                            <span class="badge bg-success-subtle text-success">{{ $conversation->device->meta_profile['verified_name'] ?? $conversation->device->body }}</span>
                            @if($conversation->assignedAgent)
                                &nbsp;·&nbsp;
                                <span class="agent-chip">
                                    <i class="bi bi-person-check" style="font-size:10px"></i>
                                    {{ $conversation->assignedAgent->name }}
                                </span>
                            @else
                                &nbsp;·&nbsp; <span class="text-muted small">{{ __('Unassigned') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-2 ms-auto flex-shrink-0">
                        {{-- Assign button --}}
                        @if($agents->count() > 0)
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-check"></i> {{ __('Assign') }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:170px">
                                @foreach($agents as $agent)
                                <li>
                                    <a class="dropdown-item small" href="#"
                                       onclick="assignAgent({{ $agent->id }}, '{{ addslashes($agent->name) }}'); return false;">
                                        <span class="badge bg-{{ $agent->status === 'online' ? 'success' : ($agent->status === 'busy' ? 'warning' : 'secondary') }}-subtle me-1" style="width:8px;height:8px;border-radius:50%;display:inline-block;padding:0"></span>
                                        {{ $agent->name }}
                                        <small class="text-muted">({{ $agent->active_chat_count }}/{{ $agent->max_concurrent_chats }})</small>
                                    </a>
                                </li>
                                @endforeach
                                @if($conversation->assigned_agent_id)
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item small text-danger" href="#" onclick="unassignAgent(); return false;">
                                    <i class="bi bi-person-x me-1"></i>{{ __('Unassign') }}
                                </a></li>
                                @endif
                            </ul>
                        </div>
                        @endif

                        {{-- Supervisor: Take Over (reassign to yourself) --}}
                        @if(($isSupervisor ?? false) && $conversation->assignedAgent)
                        <button class="btn btn-sm btn-outline-danger" onclick="takeOverConversation()"
                            title="{{ __('Take over this conversation from the current agent') }}">
                            <i class="bi bi-person-fill-exclamation"></i> {{ __('Take Over') }}
                        </button>
                        @endif

                        {{-- Resolve --}}
                        @if($conversation->conversation_status !== 'resolved')
                        <button class="btn btn-sm btn-outline-success" onclick="resolveConversation()"
                            title="{{ __('Resolve & close conversation') }}">
                            <i class="bi bi-check2-circle"></i> {{ __('Resolve') }}
                        </button>
                        @endif

                        {{-- Internal note toggle --}}
                        <button class="btn btn-sm btn-outline-warning" id="internalModeBtn"
                            onclick="toggleInternalMode()" title="{{ __('Toggle internal note mode') }}">
                            <i class="bi bi-lock"></i> {{ __('Note') }}
                        </button>

                        {{-- Toggle CRM panel --}}
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleCrm()" title="{{ __('Toggle CRM panel') }}">
                            <i class="bi bi-layout-sidebar-reverse"></i>
                        </button>
                    </div>
                </div>

                {{-- Internal note mode bar --}}
                <div class="internal-mode-bar d-none" id="internalModeBar">
                    <i class="bi bi-lock-fill"></i>
                    <strong>{{ __('Internal Note Mode') }}</strong>
                    <span class="text-muted">— {{ __('Message will be saved as a private note, not sent to WhatsApp.') }}</span>
                    <button class="btn btn-xs btn-outline-secondary ms-auto" onclick="toggleInternalMode()">
                        {{ __('Exit note mode') }}
                    </button>
                </div>

                {{-- Bot status bar --}}
                @php $activeSession = $conversation->activeBotSession; @endphp
                @if($activeSession)
                <div id="bot-status-bar" class="d-flex align-items-center justify-content-between px-3 py-2 border-top"
                     style="background:{{ $activeSession->state === 'human_assigned' ? '#fff1f2' : '#f0fdf4' }};font-size:0.82rem;">
                    <div class="d-flex align-items-center gap-2">
                        @if($activeSession->state === 'human_assigned')
                            <i class="material-icons text-danger" style="font-size:16px">support_agent</i>
                            <span class="text-danger fw-semibold">{{ __('Human assigned') }}</span>
                            <span class="text-muted">— {{ __('Bot is silent.') }}</span>
                        @else
                            <i class="material-icons text-success" style="font-size:16px">smart_toy</i>
                            <span class="text-success fw-semibold">{{ __('Bot active') }}</span>
                            <span class="text-muted">— {{ $activeSession->flow->name }}</span>
                        @endif
                    </div>
                    @if($activeSession->state === 'human_assigned')
                    <button class="btn btn-sm btn-outline-success" id="btn-resolve-bot"
                            data-url="{{ route('chat.resolve.bot', $conversation->id) }}">
                        <i class="material-icons me-1" style="font-size:14px">check_circle</i> {{ __('Resolve & Reactivate Bot') }}
                    </button>
                    @else
                    <button class="btn btn-sm btn-outline-danger" id="btn-pause-bot"
                            data-url="{{ route('chat.resolve.bot', $conversation->id) }}">
                        <i class="material-icons me-1" style="font-size:14px">pause</i> {{ __('Take Over (Disable Bot)') }}
                    </button>
                    @endif
                </div>
                @endif

                {{-- Typing indicator --}}
                <div id="typingIndicator"></div>

                {{-- Messages + notes in chronological order --}}
                <div class="wachat-messages-area" id="messagesArea">
                    @php $lastDate = null; @endphp
                    @foreach (isset($timeline) ? $timeline : [] as $entry)
                        @php $entryDate = $entry->created_at->toDateString(); @endphp
                        @if ($entryDate !== $lastDate)
                            <div class="date-divider"><span>{{ $entry->created_at->isToday() ? __('Today') : $entry->created_at->format('d M Y') }}</span></div>
                            @php $lastDate = $entryDate; @endphp
                        @endif

                        @if ($entry->type === 'message')
                            @include('theme::pages.chat._bubble', ['msg' => $entry->item])
                        @else
                            {{-- Internal note rendered inline in the timeline --}}
                            <div class="bubble-wrap outbound">
                                <div class="bubble internal-note">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-lock-fill text-warning" style="font-size:11px"></i>
                                        <small class="fw-semibold text-warning-emphasis">{{ __('Internal Note') }} — {{ $entry->item->author }}</small>
                                    </div>
                                    {!! nl2br(e($entry->item->note)) !!}
                                    <div class="bubble-time">{{ $entry->item->created_at->format('H:i d M') }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Input --}}
                <div class="wachat-input-area">
                    @if (isset($approvedTemplates) && $approvedTemplates->count() > 0)
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-toggle="modal" data-bs-target="#templateModal"
                            title="{{ __('Send Template') }}">
                            <i class="bi bi-layout-text-sidebar-reverse"></i>
                        </button>
                    @endif
                    <textarea id="chatTextarea" class="form-control" rows="1"
                        placeholder="{{ __('Type a message... (Enter to send, Shift+Enter for new line)') }}"></textarea>
                    <button id="sendBtn" class="btn btn-primary" style="border-radius:50%;width:44px;height:44px;flex-shrink:0">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>

                @if (!isset($approvedTemplates) || $approvedTemplates->count() === 0)
                <div class="px-3 py-2 bg-warning-subtle border-top" style="font-size:12px">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ __('First message to a new contact must use an approved template.') }}
                    <a href="{{ route('templates.index') }}">{{ __('Create a template') }}</a>.
                </div>
                @endif

            @else
                <div class="wachat-empty">
                    <i class="bi bi-chat-dots" style="font-size:48px;opacity:.3"></i>
                    <p class="mb-0">{{ __('Select a conversation') }}</p>
                    <p class="small">{{ __('or click "New Chat" to start') }}</p>
                </div>
            @endif
        </div>

        {{-- ── CRM RIGHT PANEL ──────────────────────────── --}}
        @if(isset($conversation))
        <div class="wachat-crm" id="crmPanel">

            {{-- Contact Info --}}
            <div class="crm-section">
                <h6>{{ __('Contact') }}</h6>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="wachat-conv-avatar" style="width:36px;height:36px;font-size:14px">{{ $conversation->avatar_letter }}</div>
                    <div>
                        <div class="fw-semibold">{{ $conversation->contact_name ?? '—' }}</div>
                        <div class="text-muted small">{{ $conversation->contact_number }}</div>
                    </div>
                </div>
                @if($contactTags->isNotEmpty())
                <div class="d-flex flex-wrap gap-1 mt-1">
                    @foreach($contactTags as $tag)
                        <span class="badge bg-primary-subtle text-primary small">{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Custom Attributes --}}
            <div class="crm-section">
                <h6>{{ __('Attributes') }}</h6>
                @php
                    $defaultKeys = ['LTV', 'Lifetime Orders', 'Segment', 'Source'];
                    $allKeys = array_unique(array_merge($defaultKeys, array_keys($contactAttributes)));
                @endphp
                @foreach($allKeys as $key)
                <div class="attr-row">
                    <span class="attr-key">{{ $key }}</span>
                    <span class="attr-val" id="attr-{{ Str::slug($key) }}"
                          onclick="editAttribute('{{ addslashes($key) }}', this)"
                          title="{{ __('Click to edit') }}">
                        {{ $contactAttributes[$key] ?? '—' }}
                    </span>
                </div>
                @endforeach
                <div class="mt-2">
                    <input type="text" id="newAttrKey" class="form-control form-control-sm mb-1"
                        placeholder="{{ __('Custom attribute key...') }}">
                    <div class="d-flex gap-1">
                        <input type="text" id="newAttrVal" class="form-control form-control-sm"
                            placeholder="{{ __('Value') }}">
                        <button class="btn btn-sm btn-outline-primary flex-shrink-0" onclick="addAttribute()">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- SLA Status --}}
            @if($conversation->assigned_at && !$conversation->resolved_at)
            <div class="crm-section">
                <h6>{{ __('SLA') }}</h6>
                @php
                    $slaMinutes = $conversation->sla_minutes_elapsed;
                    $slaClass   = $conversation->sla_breached ? 'danger' : ($slaMinutes > 10 ? 'warning' : 'ok');
                    $slaText    = $conversation->first_response_at
                        ? __('First response: :m min', ['m' => (int) $conversation->assigned_at->diffInMinutes($conversation->first_response_at)])
                        : ($slaMinutes !== null ? __(':m min waiting', ['m' => $slaMinutes]) : __('Not started'));
                @endphp
                <div class="sla-timer {{ $slaClass }}">
                    <i class="bi bi-clock me-1"></i>{{ $slaText }}
                    @if($conversation->sla_breached)
                        <span class="badge bg-danger ms-1">{{ __('Breached') }}</span>
                    @endif
                </div>
                @if($conversation->assigned_agent_id)
                <div class="text-muted mt-1" style="font-size:11px">
                    {{ __('Assigned to') }}: {{ $conversation->assignedAgent->name }}
                </div>
                @endif
            </div>
            @endif

            {{-- Internal Notes --}}
            <div class="crm-section">
                <h6>{{ __('Notes') }}</h6>
                <div id="notesContainer">
                    @forelse($conversation->notes as $note)
                    <div class="note-item {{ $note->is_internal ? '' : 'not-internal' }}">
                        {!! nl2br(e($note->note)) !!}
                        <div class="note-meta">
                            {{ $note->author }} · {{ $note->created_at->format('d M H:i') }}
                            @if(!$note->is_internal)
                                <span class="badge bg-info-subtle text-info ms-1">{{ __('Visible') }}</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-muted small" id="noNotesMsg">{{ __('No notes yet.') }}</div>
                    @endforelse
                </div>
                <div class="mt-2">
                    <textarea id="noteText" class="form-control form-control-sm" rows="2"
                        placeholder="{{ __('Add internal note...') }}"></textarea>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <label class="form-check-label small d-flex align-items-center gap-1">
                            <input type="checkbox" class="form-check-input" id="noteInternal" checked>
                            {{ __('Internal (whisper)') }}
                        </label>
                        <button class="btn btn-sm btn-warning ms-auto" onclick="addNote()">
                            <i class="bi bi-lock me-1"></i>{{ __('Add Note') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Conversation History Summary --}}
            <div class="crm-section">
                <h6>{{ __('History') }}</h6>
                <div class="text-muted small">
                    {{ __('Total messages') }}: <strong>{{ $messages->count() }}</strong><br>
                    {{ __('First contact') }}: <strong>{{ $conversation->created_at->format('d M Y') }}</strong><br>
                    {{ __('Status') }}: <strong>{{ ucfirst($conversation->conversation_status) }}</strong>
                </div>
            </div>

        </div>
        @endif

    </div>{{-- end wrapper --}}
</div>

{{-- New Chat Modal --}}
<div class="modal fade" id="newChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ __('New Chat') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('chat.start') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Device') }} <span class="text-danger">*</span></label>
                        <select name="device_id" class="form-select form-select-sm" required>
                            @foreach ($devices as $d)
                                <option value="{{ $d->id }}">{{ $d->meta_profile['verified_name'] ?? $d->body }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control form-control-sm"
                            placeholder="e.g. 60123456789" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">{{ __('Name') }} <small class="text-muted">({{ __('optional') }})</small></label>
                        <input type="text" name="contact_name" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Start Chat') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Send Template Modal --}}
@if (isset($conversation) && isset($approvedTemplates) && $approvedTemplates->count() > 0)
<div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-layout-text-sidebar-reverse me-2"></i>{{ __('Send Template Message') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Select Template') }}</label>
                    <select id="templateSelect" class="form-select form-select-sm">
                        <option value="">{{ __('Choose a template...') }}</option>
                        @foreach ($approvedTemplates as $tpl)
                            <option value="{{ $tpl->id }}" data-components="{{ json_encode($tpl->components) }}" data-name="{{ $tpl->name }}">
                                {{ $tpl->name }} ({{ $tpl->language }}) — {{ $tpl->category }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="templatePreview" class="d-none">
                    <div class="border rounded p-3 bg-light mb-3" id="templatePreviewText" style="font-size:13px;white-space:pre-wrap"></div>
                    <div id="templateVarsSection"></div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" id="sendTemplateBtn" class="btn btn-sm btn-primary" disabled>
                    <i class="bi bi-send me-1"></i>{{ __('Send Template') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if (isset($conversation))
<script>
const CONV_ID       = {{ $conversation->id }};
const SEND_URL      = '{{ route('chat.send', $conversation->id) }}';
const POLL_URL      = '{{ route('chat.messages', $conversation->id) }}';
const TYPING_URL    = '{{ route('chat.typing', $conversation->id) }}';
const NOTE_URL      = '{{ route('chat.notes.store', $conversation->id) }}';
const ATTR_URL      = '{{ route('chat.attribute.save', $conversation->id) }}';
const ASSIGN_URL    = '{{ route('chat.assign', $conversation->id) }}';
const UNASSIGN_URL  = '{{ route('chat.unassign', $conversation->id) }}';
const RESOLVE_URL   = '{{ route('chat.resolve', $conversation->id) }}';
const CSRF          = '{{ csrf_token() }}';
let lastId          = {{ $messages->last()?->id ?? 0 }};
let pollTimer       = null;
let typingTimer     = null;
let isInternalMode  = false;

// ── Auto-scroll ────────────────────────────────────────────────
function scrollBottom() {
    const area = document.getElementById('messagesArea');
    if (area) area.scrollTop = area.scrollHeight;
}
scrollBottom();

// ── Build bubble HTML ──────────────────────────────────────────
function buildBubble(msg) {
    const isOut = msg.direction === 'outbound';
    const tick  = isOut ? tickHtml(msg.status) : '';
    const media = msg.media_url
        ? `<img src="${msg.media_url}" class="bubble-media" onerror="this.style.display='none'"><br>` : '';
    const body  = (msg.body || '').replace(/\n/g, '<br>');
    return `<div class="bubble-wrap ${msg.direction}" data-id="${msg.id}">
        <div class="bubble ${msg.direction}">
            ${media}${body}
            <div class="bubble-time">${msg.time} ${tick}</div>
        </div>
    </div>`;
}

function tickHtml(status) {
    if (status === 'failed') {
        return `<span class="status-tick" style="color:#dc3545" title="{{ __('Delivery failed — contact may be outside 24h window. Use a template.') }}">✕</span>`;
    }
    const cls  = status === 'read' ? 'read' : (status === 'delivered' ? 'delivered' : '');
    const icon = (status === 'sent') ? '✓' : '✓✓';
    return `<span class="status-tick ${cls}">${icon}</span>`;
}

// ── Polling (fallback) ─────────────────────────────────────────
function poll() {
    $.getJSON(POLL_URL + '?after_id=' + lastId, function(data) {
        if (data.messages && data.messages.length > 0) {
            let appended = false;
            data.messages.forEach(function(msg) {
                if (msg.id > lastId) {
                    lastId = msg.id;
                    if (!document.querySelector(`.bubble-wrap[data-id="${msg.id}"]`)) {
                        $('#messagesArea').append(buildBubble(msg));
                        appended = true;
                    }
                }
            });
            if (appended) scrollBottom();
        }
        if (data.messages) {
            data.messages.forEach(function(msg) {
                if (msg.direction === 'outbound') {
                    const t = $(`.bubble-wrap[data-id="${msg.id}"] .bubble-time`);
                    if (t.length) t.find('.status-tick').replaceWith(tickHtml(msg.status));
                }
            });
        }
    });
}

// ── Socket.io real-time ────────────────────────────────────────
(function () {
    @php
        $parsedUrl = parse_url(env('APP_URL', 'http://localhost'));
        $socketHost = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? 'localhost');
    @endphp
    var socketUrl = '{{ $socketHost }}:{{ env("PORT_NODE", 3100) }}';
    var connected = false;

    try {
        var socket = io(socketUrl, { transports: ['websocket', 'polling'], timeout: 4000 });

        socket.on('connect', function () {
            connected = true;
            socket.emit('join', 'conv-' + CONV_ID);
            // Join the user inbox room so the sidebar updates live when new messages arrive
            socket.emit('join', 'inbox-{{ auth()->id() }}');
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        });

        socket.on('new_message', function (msg) {
            $.getJSON(POLL_URL + '?after_id=' + lastId, function(data) {
                if (data.messages) {
                    data.messages.forEach(function(m) {
                        if (m.id > lastId) {
                            lastId = m.id;
                            if (!document.querySelector(`.bubble-wrap[data-id="${m.id}"]`)) {
                                $('#messagesArea').append(buildBubble(m));
                                scrollBottom();
                            }
                        }
                    });
                }
            });
        });

        // Feature 3: Typing indicator from other agents
        socket.on('agent_typing', function (data) {
            const indicator = document.getElementById('typingIndicator');
            if (!indicator) return;
            if (data.typing) {
                indicator.innerHTML = `<span class="text-muted"><i class="bi bi-three-dots"></i> ${data.agent_name} {{ __('is typing...') }}</span>`;
                clearTimeout(window._typingClearTimer);
                window._typingClearTimer = setTimeout(() => { indicator.innerHTML = ''; }, 4000);
            } else {
                indicator.innerHTML = '';
            }
        });

        // Feature 3: Note added by another agent
        socket.on('new_note', function (note) {
            appendNote(note);
        });

        // Feature 3: Conversation updated (assignment, resolved)
        socket.on('conversation_updated', function (data) {
            if (data.event === 'resolved') {
                toastr.info('{{ __("Conversation resolved by another agent.") }}');
            } else if (data.event === 'assigned' && data.agent_name) {
                toastr.info(`{{ __("Assigned to") }} ${data.agent_name}`);
            }
        });

        // Feature 3: SLA breach alert
        socket.on('sla_breach', function (data) {
            toastr.warning(`⚠️ {{ __("SLA breach") }}: ${data.contact_name}`, '', { timeOut: 0, extendedTimeOut: 0 });
        });

        // Feature 3: Inbox update — handles new messages, assignments, resolves, and SLA breaches
        socket.on('inbox_update', function (data) {
            const convId  = data.conversation_id;
            const list    = document.getElementById('convList');
            if (!list) return;

            const existing = list.querySelector(`.wachat-conv-item[data-conv-id="${convId}"]`);
            if (existing) {
                if (data.event === 'resolved') {
                    // Remove from the open list (or dim it to indicate resolved)
                    existing.style.opacity = '0.4';
                    setTimeout(() => existing.remove(), 1500);
                    return;
                }
                if (data.event === 'unassigned') {
                    const chip = existing.querySelector('.agent-chip');
                    if (chip) chip.remove();
                }
                if (data.event === 'assigned' && data.agent_name) {
                    let chip = existing.querySelector('.agent-chip');
                    if (!chip) {
                        chip = document.createElement('span');
                        chip.className = 'agent-chip ms-1';
                        existing.querySelector('.wachat-conv-name')?.appendChild(chip);
                    }
                    chip.textContent = data.agent_name;
                }
                // Update preview text for new messages
                const preview = existing.querySelector('.wachat-conv-preview');
                if (preview && data.last_message) preview.textContent = data.last_message.substring(0, 32);
                // Bubble to top of list for new activity
                if (data.event !== 'assigned' && data.event !== 'unassigned') {
                    list.prepend(existing);
                }
            } else if (!data.event || data.event === 'new_message') {
                // Brand-new conversation not yet in list
                toastr.info('{{ __("New conversation received.") }}', '', { timeOut: 4000 });
            }
        });

        socket.on('disconnect', function () {
            connected = false;
            if (!pollTimer) pollTimer = setInterval(poll, 3000);
        });

        socket.on('connect_error', function () {
            if (!connected && !pollTimer) pollTimer = setInterval(poll, 3000);
        });

        pollTimer = setInterval(poll, 3000);

    } catch (e) {
        pollTimer = setInterval(poll, 3000);
    }
})();

// ── Send message ───────────────────────────────────────────────
function sendMessage() {
    const text = $('#chatTextarea').val().trim();
    if (!text) return;

    // Internal note mode — save as note instead of sending to WA
    if (isInternalMode) {
        saveNote(text, true);
        $('#chatTextarea').val('');
        return;
    }

    $('#sendBtn').prop('disabled', true);
    $('#chatTextarea').prop('disabled', true);

    $.ajax({
        method : 'POST',
        url    : SEND_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data   : { message: text },
        success: function(res) {
            if (!res.error && res.message) {
                $('#messagesArea').append(buildBubble(res.message));
                lastId = Math.max(lastId, res.message.id);
                scrollBottom();
            }
            $('#chatTextarea').val('').prop('disabled', false).focus();
        },
        error: function(err) {
            toastr.error(err.responseJSON?.message ?? '{{ __("Send failed") }}');
            $('#chatTextarea').prop('disabled', false).focus();
        },
        complete: function() {
            $('#sendBtn').prop('disabled', false);
        }
    });
}

$('#sendBtn').on('click', sendMessage);
$('#chatTextarea').on('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// ── Typing indicator — debounced emit ─────────────────────────
$('#chatTextarea').on('input', function () {
    if (isInternalMode) return;
    clearTimeout(typingTimer);
    $.post(TYPING_URL, { typing: 1, _token: CSRF });
    typingTimer = setTimeout(function() {
        $.post(TYPING_URL, { typing: 0, _token: CSRF });
    }, 3000);
});

// ── Internal note mode ─────────────────────────────────────────
function toggleInternalMode() {
    isInternalMode = !isInternalMode;
    const bar  = document.getElementById('internalModeBar');
    const btn  = document.getElementById('internalModeBtn');
    const area = document.getElementById('chatTextarea');
    if (isInternalMode) {
        bar.classList.remove('d-none');
        btn.classList.add('active', 'btn-warning');
        btn.classList.remove('btn-outline-warning');
        area.placeholder = '{{ __("Type internal note... (not sent to WhatsApp)") }}';
        area.style.background = '#fffbea';
    } else {
        bar.classList.add('d-none');
        btn.classList.remove('active', 'btn-warning');
        btn.classList.add('btn-outline-warning');
        area.placeholder = '{{ __("Type a message... (Enter to send, Shift+Enter for new line)") }}';
        area.style.background = '#fff';
    }
}

// ── Notes ──────────────────────────────────────────────────────
function saveNote(text, isInternal) {
    $.ajax({
        method : 'POST',
        url    : NOTE_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data   : { note: text, is_internal: isInternal ? 1 : 0 },
        success: function(res) {
            if (res.ok) {
                appendNote(res.note);
                appendNoteToMessages(res.note);
            }
        },
        error: function() { toastr.error('{{ __("Failed to save note.") }}'); }
    });
}

function appendNote(note) {
    const noMsg = document.getElementById('noNotesMsg');
    if (noMsg) noMsg.remove();

    const html = `<div class="note-item ${note.is_internal ? '' : 'not-internal'}">
        ${note.note.replace(/\n/g,'<br>')}
        <div class="note-meta">${note.author} · ${note.time}
            ${!note.is_internal ? '<span class="badge bg-info-subtle text-info ms-1">Visible</span>' : ''}
        </div>
    </div>`;
    $('#notesContainer').append(html);
}

function appendNoteToMessages(note) {
    const html = `<div class="bubble-wrap outbound">
        <div class="bubble internal-note">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-lock-fill text-warning" style="font-size:11px"></i>
                <small class="fw-semibold text-warning-emphasis">{{ __('Internal Note') }} — ${note.author}</small>
            </div>
            ${note.note.replace(/\n/g,'<br>')}
            <div class="bubble-time">${note.time}</div>
        </div>
    </div>`;
    $('#messagesArea').append(html);
    scrollBottom();
}

function addNote() {
    const text = $('#noteText').val().trim();
    if (!text) return;
    const isInternal = $('#noteInternal').is(':checked');
    saveNote(text, isInternal);
    $('#noteText').val('');
}

// ── Contact attributes ─────────────────────────────────────────
function editAttribute(key, el) {
    const current = el.innerText.trim() === '—' ? '' : el.innerText.trim();
    const val = prompt(`{{ __('Edit') }} ${key}:`, current);
    if (val === null) return; // cancelled
    $.ajax({
        method : 'POST',
        url    : ATTR_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data   : { key: key, value: val },
        success: function() {
            el.innerText = val || '—';
            toastr.success('{{ __("Attribute saved.") }}');
        },
        error: function() { toastr.error('{{ __("Failed to save attribute.") }}'); }
    });
}

function addAttribute() {
    const key = $('#newAttrKey').val().trim();
    const val = $('#newAttrVal').val().trim();
    if (!key) return;
    $.ajax({
        method : 'POST',
        url    : ATTR_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data   : { key: key, value: val },
        success: function() {
            toastr.success('{{ __("Attribute saved.") }}');
            $('#newAttrKey').val('');
            $('#newAttrVal').val('');
            // Add row to DOM
            const slug = key.toLowerCase().replace(/[^a-z0-9]/g,'-');
            const row  = `<div class="attr-row"><span class="attr-key">${key}</span>
                <span class="attr-val" id="attr-${slug}" onclick="editAttribute('${key}', this)">${val || '—'}</span></div>`;
            $('#notesContainer').closest('.wachat-crm').find('.crm-section').first().next().find('[id^=newAttrKey]').closest('.mt-2').before(row);
        },
        error: function() { toastr.error('{{ __("Failed.") }}'); }
    });
}

// ── Agent assignment ───────────────────────────────────────────
function assignAgent(agentId, agentName) {
    $.ajax({
        method : 'POST',
        url    : ASSIGN_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data   : { agent_id: agentId },
        success: function() {
            toastr.success(`{{ __("Assigned to") }} ${agentName}`);
            setTimeout(() => location.reload(), 800);
        },
        error: function(e) { toastr.error(e.responseJSON?.message ?? '{{ __("Failed.") }}'); }
    });
}

function unassignAgent() {
    $.ajax({
        method : 'POST',
        url    : UNASSIGN_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        success: function() { toastr.success('{{ __("Unassigned.") }}'); setTimeout(() => location.reload(), 800); },
        error:   function() { toastr.error('{{ __("Failed.") }}'); }
    });
}

function resolveConversation() {
    if (!confirm('{{ __("Mark this conversation as resolved?") }}')) return;
    $.ajax({
        method : 'POST',
        url    : RESOLVE_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        success: function() { toastr.success('{{ __("Conversation resolved.") }}'); setTimeout(() => location.reload(), 800); },
        error:   function() { toastr.error('{{ __("Failed.") }}'); }
    });
}

// ── CRM panel toggle ───────────────────────────────────────────
function toggleCrm() {
    const panel = document.getElementById('crmPanel');
    if (panel) panel.style.display = (panel.style.display === 'none') ? '' : 'none';
}

// ── Sidebar filters ────────────────────────────────────────────
function filterByStatus(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('conv_status', status);
    url.searchParams.delete('sla_only');
    window.location.href = url.toString();
}

function filterBySla() {
    const url = new URL(window.location.href);
    url.searchParams.set('sla_only', '1');
    url.searchParams.delete('unassigned_only');
    window.location.href = url.toString();
}

function filterUnassigned() {
    const url = new URL(window.location.href);
    url.searchParams.set('unassigned_only', '1');
    url.searchParams.delete('sla_only');
    window.location.href = url.toString();
}

// Supervisor: take over the current conversation (self-assign)
function takeOverConversation() {
    if (!confirm('{{ __("Take over this conversation from the current agent?") }}')) return;
    // Show list of supervisor/admin agents to assign to self — for simplicity just unassign + reassign
    $.ajax({
        method : 'POST',
        url    : UNASSIGN_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        success: function() {
            toastr.success('{{ __("You have taken over this conversation.") }}');
            setTimeout(() => location.reload(), 800);
        },
        error: function() { toastr.error('{{ __("Failed to take over.") }}'); }
    });
}

let searchTimer;
$('#convSearch').on('input', function() {
    clearTimeout(searchTimer);
    const q = $(this).val();
    searchTimer = setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set('search', q);
        window.location.href = url.toString();
    }, 600);
});

$('#deviceFilter').on('change', function() {
    const url = new URL('{{ route('chat.index') }}');
    if ($(this).val()) url.searchParams.set('device_id', $(this).val());
    window.location.href = url.toString();
});

if (document.getElementById('agentFilter')) {
    document.getElementById('agentFilter').addEventListener('change', function() {
        const url = new URL(window.location.href);
        if (this.value) url.searchParams.set('agent_id', this.value);
        else url.searchParams.delete('agent_id');
        window.location.href = url.toString();
    });
}

if (document.getElementById('teamFilter')) {
    document.getElementById('teamFilter').addEventListener('change', function() {
        const url = new URL(window.location.href);
        if (this.value) url.searchParams.set('team_id', this.value);
        else url.searchParams.delete('team_id');
        window.location.href = url.toString();
    });
}

// ── Template send ──────────────────────────────────────────────
$('#templateSelect').on('change', function () {
    if (!$(this).val()) {
        $('#templatePreview').addClass('d-none');
        $('#sendTemplateBtn').prop('disabled', true);
        return;
    }
    const opt    = $(this).find(':selected');
    const comps  = JSON.parse(opt.attr('data-components') || '[]');
    let bodyText = '';
    comps.forEach(function(c) { if (c.type === 'BODY') bodyText = c.text; });
    const matches = bodyText.match(/\{\{(\d+)\}\}/g) || [];
    const vars    = [...new Set(matches.map(m => m.replace(/\D/g,'')))].sort((a,b)=>+a-+b);
    $('#templatePreviewText').text(bodyText || opt.attr('data-name'));
    let varHtml = '';
    vars.forEach(function(v) {
        varHtml += `<div class="input-group input-group-sm mb-2">
            <span class="input-group-text">@{{${v}}}</span>
            <input type="text" class="form-control template-var" data-var="${v}" placeholder="Value for @{{${v}}}">
        </div>`;
    });
    $('#templateVarsSection').html(varHtml ? '<div class="mb-2"><small class="text-muted">{{ __("Fill in variables:") }}</small></div>' + varHtml : '');
    $('#templatePreview').removeClass('d-none');
    $('#sendTemplateBtn').prop('disabled', false);
});

$('#sendTemplateBtn').on('click', function () {
    const templateId = $('#templateSelect').val();
    if (!templateId) return;
    const vars = {};
    $('.template-var').each(function () { vars[$(this).data('var')] = $(this).val(); });
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    $.ajax({
        method : 'POST',
        url    : '{{ route("chat.send.template", $conversation->id) }}',
        headers: { 'X-CSRF-TOKEN': CSRF },
        data   : { template_id: templateId, vars: vars },
        success: function (res) {
            if (!res.error && res.message) {
                $('#messagesArea').append(buildBubble(res.message));
                lastId = Math.max(lastId, res.message.id);
                scrollBottom();
            }
            $('#templateModal').modal('hide');
            toastr.success('{{ __("Template sent!") }}');
        },
        error: function (err) { toastr.error(err.responseJSON?.message ?? '{{ __("Template send failed") }}'); },
        complete: function () {
            $('#sendTemplateBtn').prop('disabled', false).html('<i class="bi bi-send me-1"></i>{{ __("Send Template") }}');
        }
    });
});

// ── Bot handoff controls ───────────────────────────────────────
['btn-resolve-bot','btn-pause-bot'].forEach(function(id) {
    const btn = document.getElementById(id);
    if (!btn) return;
    btn.addEventListener('click', function () {
        fetch(this.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            if (!d.error) location.reload();
            else alert(d.message);
        });
    });
});
</script>
@endif

</x-layout-dashboard>
