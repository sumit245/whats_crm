<x-layout-dashboard title="{{ __('Chat') }}">

<style>
/* ── WhatsApp-style design tokens (dnd-pages.css isn't loaded on this page) ── */
.wachat-wrapper {
    --dnd-border:         #e9edef;
    --dnd-border-strong:  #d1d7db;
    --dnd-text:           #111b21;
    --dnd-text-secondary: #3b4a54;
    --dnd-text-muted:     #667781;
    --dnd-bg:             #f5f1ec;
    --dnd-surface:        #ffffff;
    --dnd-header:         #f0f2f5;   /* WhatsApp panel-header / input-bar gray */
    --dnd-brand:          #008069;   /* WhatsApp green */
    --dnd-brand-muted:    #d9fdd3;   /* outbound bubble green */
    --dnd-brand-subtle:   #f0f2f5;
    --dnd-accent-danger:  #dc3545;
    --dnd-accent-warning: #f59e0b;
    --dnd-accent-link:    #53bdeb;   /* read-tick blue */
    --dnd-radius:         6px;
    --dnd-radius-md:      7px;
    --dnd-radius-pill:    999px;
    --dnd-shadow-sm:      0 1px 1px rgba(11,20,26,.13);

    display:flex; overflow:hidden;
    height:calc(100vh - 108px);
    height:calc(100dvh - 108px);
    background:var(--dnd-bg);
    border:1px solid var(--dnd-border);
    border-radius:8px;
}
/* WhatsApp has no page footer — reclaim the space for a full-height chat */
.footer { display:none; }

.wachat-sidebar        { width:33%; max-width:420px; min-width:300px; flex-shrink:0; border-right:1px solid var(--dnd-border); display:flex; flex-direction:column; background:var(--dnd-surface); }
.wachat-main           { flex:1 1 0; min-width:0; display:flex; flex-direction:column; background:var(--dnd-bg); }
.wachat-crm            { width:300px; min-width:260px; flex-shrink:0; border-left:1px solid var(--dnd-border); display:flex; flex-direction:column; font-size:13px; overflow-y:auto; background:var(--dnd-surface); }
.wachat-sidebar-header  { padding:10px 12px; border-bottom:1px solid var(--dnd-border); min-height:60px; box-sizing:border-box; background:var(--dnd-header); }
.wachat-conv-list       { flex:1; overflow-y:auto; background:var(--dnd-surface); }
.wachat-conv-item       { display:flex; align-items:center; padding:9px 12px; cursor:pointer; border-bottom:1px solid var(--dnd-border); transition:background .15s; }
.wachat-conv-item:hover { background:#f5f6f6; }
.wachat-conv-item.active{ background:#f0f2f5; }
.wachat-conv-avatar     { width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:16px; flex-shrink:0; color:#fff; background:var(--dnd-brand); }
.wachat-conv-meta       { flex:1; min-width:0; padding-left:9px; }
.wachat-conv-name       { font-weight:600; font-size:13px; color:var(--dnd-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wachat-conv-preview    { font-size:11px; color:var(--dnd-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wachat-conv-time       { font-size:11px; color:var(--dnd-text-muted); white-space:nowrap; margin-left:6px; }
.unread-badge, .sla-badge { border-radius:50%; font-size:10px; min-width:16px; height:16px; display:flex; align-items:center; justify-content:center; padding:0 3px; margin-top:3px; color:#fff; }
.sla-badge              { background:var(--dnd-accent-danger); }
.wachat-sidebar-footer  { padding:10px; border-top:1px solid var(--dnd-border); background:var(--dnd-surface); }
.wachat-main-header     { padding:8px 14px; border-bottom:1px solid var(--dnd-border); display:flex; align-items:center; min-height:60px; box-sizing:border-box; color:var(--dnd-text); gap:10px; background:var(--dnd-header); }
.wachat-messages-area   { flex:1; overflow-y:auto; padding:14px; display:flex; flex-direction:column; gap:5px; }
.wachat-empty           { flex:1; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; color:var(--dnd-text-muted); background:var(--dnd-bg); }
.wachat-input-area      { padding:8px 12px; display:flex; align-items:flex-end; gap:8px; background:var(--dnd-header); }
.wachat-input-area .btn { height:44px; width:44px; padding:0; flex-shrink:0; display:flex; align-items:center; justify-content:center; }
#chatTextarea           { resize:none; overflow:hidden; border-radius:var(--dnd-radius-pill); padding:11px 14px; font-size:14px; flex:1; min-height:44px; max-height:120px; color:var(--dnd-text); background:var(--dnd-surface); border:1px solid var(--dnd-border-strong); box-sizing:border-box; }
.bubble-wrap            { display:flex; }
.bubble-wrap.inbound    { justify-content:flex-start; }
.bubble-wrap.outbound   { justify-content:flex-end; }
.bubble                 { max-width:65%; padding:8px 12px; border-radius:var(--dnd-radius-md); font-size:14px; line-height:1.45; word-break:break-word; color:var(--dnd-text); box-shadow:var(--dnd-shadow-sm); }
.bubble.inbound         { border-top-left-radius:0; background:var(--dnd-surface); }
.bubble.outbound        { border-top-right-radius:0; background:var(--dnd-brand-muted); }
.bubble.internal-note   { background:rgba(245,158,11,.15); border:1px dashed var(--dnd-accent-warning); max-width:80%; }
.bubble-time            { font-size:11px; color:var(--dnd-text-muted); text-align:right; margin-top:3px; display:flex; align-items:center; justify-content:flex-end; gap:3px; }
.bubble-media           { max-width:100%; border-radius:var(--dnd-radius); margin-bottom:4px; }
.status-tick.read       { color:var(--dnd-accent-link); }
.date-divider           { text-align:center; font-size:12px; color:var(--dnd-text-muted); margin:8px 0; }
.date-divider span      { background:var(--dnd-brand-muted); color:var(--dnd-brand); padding:3px 10px; border-radius:var(--dnd-radius-pill); }
.crm-section            { padding:12px; border-bottom:1px solid var(--dnd-border); }
.wachat-crm > .crm-section:first-child { min-height:60px; box-sizing:border-box; background:var(--dnd-header); }
.crm-section h6         { font-size:12px; font-weight:700; text-transform:uppercase; color:var(--dnd-text-muted); margin-bottom:8px; letter-spacing:.5px; }
.attr-key               { font-size:12px; color:var(--dnd-text-muted); width:90px; flex-shrink:0; }
.attr-val               { font-size:12px; font-weight:600; flex:1; cursor:pointer; padding:2px 4px; border-radius:var(--dnd-radius); color:var(--dnd-text); }
.attr-val:hover         { background:var(--dnd-brand-subtle); }
.note-item              { background:rgba(245,158,11,.12); border-left:3px solid var(--dnd-accent-warning); padding:6px 8px; border-radius:var(--dnd-radius); margin-bottom:6px; font-size:12px; }
.note-item.not-internal { background:var(--dnd-brand-muted); border-left-color:var(--dnd-brand); }
.conv-status-tabs       { display:flex; gap:4px; padding:6px 10px; border-bottom:1px solid var(--dnd-border); }
.conv-status-tabs .tab  { font-size:11px; padding:3px 10px; border-radius:var(--dnd-radius-pill); cursor:pointer; border:1px solid var(--dnd-border); white-space:nowrap; color:var(--dnd-text-secondary); transition:background .15s,color .15s,border-color .15s; }
.conv-status-tabs .tab:hover      { background:var(--dnd-brand-subtle); }
.conv-status-tabs .tab.active     { background:var(--dnd-brand); color:#fff; border-color:var(--dnd-brand); font-weight:600; }
.conv-status-tabs #slaFilterTab.active { background:var(--dnd-accent-danger); color:#fff !important; border-color:var(--dnd-accent-danger) !important; }
.btn-xs { padding:2px 7px; font-size:11px; }
.internal-mode-bar { background:rgba(245,158,11,.12); border-bottom:1px solid var(--dnd-accent-warning); padding:6px 14px; font-size:12px; color:var(--dnd-text); display:flex; align-items:center; gap:8px; }
/* ── Attachment bottom-sheet ── */
.attach-sheet        { min-width:230px; border-radius:12px !important; padding:4px 0 !important; max-height:min(480px,70vh); overflow-y:auto; }
.attach-group-label  { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--dnd-text-muted); padding:6px 16px 2px; pointer-events:none; }
.attach-item         { display:flex !important; align-items:center; gap:10px; padding:8px 16px !important; }
.attach-item:hover,.attach-item:focus { background:var(--dnd-brand-subtle) !important; }
.ai-icon             { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.ai-teal             { background:rgba(18,140,126,.15); color:#128c7e; }
.ai-blue             { background:rgba(37,99,235,.13); color:#2563eb; }
.ai-amber            { background:rgba(245,158,11,.15); color:#d97706; }
.ai-purple           { background:rgba(124,58,237,.13); color:#7c3aed; }
.ai-text             { flex:1; font-size:13px; }
.ks-badge            { font-size:10px; font-weight:700; background:var(--dnd-border); color:var(--dnd-text-muted); border-radius:4px; padding:1px 5px; font-family:monospace; }
/* ── Event modal sections ── */
.ev-section       { background:var(--dnd-surface); border:1px solid var(--dnd-border); border-radius:8px; padding:10px 12px; }
.ev-section-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--dnd-text-muted); margin-bottom:8px; display:block; }
/* ── Conversation labels ── */
.conv-label-dot      { width:8px; height:8px; border-radius:50%; display:inline-block; flex-shrink:0; }
.label-filter-bar    { display:flex; gap:4px; flex-wrap:wrap; padding:5px 10px; border-bottom:1px solid var(--dnd-border); background:var(--dnd-bg); }
.label-filter-chip   { font-size:10px; padding:2px 7px; border-radius:var(--dnd-radius-pill); cursor:pointer; border:1.5px solid var(--lc,#6c757d); color:var(--lc,#6c757d); white-space:nowrap; transition:background .12s,color .12s; }
.label-filter-chip.active, .label-filter-chip:hover { background:var(--lc,#6c757d); color:#fff; }
.crm-label-chip      { font-size:12px; padding:2px 8px; border-radius:var(--dnd-radius-pill); color:#fff; display:inline-flex; align-items:center; gap:4px; }
.crm-label-chip .rm  { opacity:.75; cursor:pointer; line-height:1; font-size:13px; }
.crm-label-chip .rm:hover { opacity:1; }
.label-picker-item   { display:flex; align-items:center; gap:8px; padding:5px 12px; cursor:pointer; font-size:13px; }
.label-picker-item:hover { background:var(--dnd-brand-subtle); }
/* ── Quick reply picker ── */
#qrPicker .qr-item         { padding:8px 14px; cursor:pointer; border-bottom:1px solid var(--dnd-border); }
#qrPicker .qr-item:last-child { border-bottom:none; }
#qrPicker .qr-item:hover,
#qrPicker .qr-item.active  { background:var(--dnd-brand-subtle); }
#qrPicker .qr-shortcut     { font-size:11px; color:var(--dnd-brand); font-weight:700; }
#qrPicker .qr-body         { font-size:12px; color:var(--dnd-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
/* ── Note cards ── */
.note-card               { border:1px solid var(--dnd-border); border-radius:8px; padding:8px 10px; margin-bottom:8px; transition:background .12s; }
.note-card:hover         { background:var(--dnd-brand-subtle); }
.note-card-visible       { border-left:3px solid var(--dnd-brand); }
.note-card-header        { display:flex; align-items:center; gap:6px; }
.note-type-icon          { font-size:13px; opacity:.45; flex-shrink:0; }
.note-card-title         { flex:1; font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.note-card-actions       { display:flex; gap:2px; opacity:0; transition:opacity .15s; }
.note-card:hover .note-card-actions { opacity:1; }
.note-card-meta          { font-size:11px; color:var(--dnd-text-muted); margin-top:3px; }
.btn-note-xs             { padding:1px 5px; font-size:11px; border:none; background:transparent; border-radius:4px; color:var(--dnd-text-secondary); }
.btn-note-xs:hover       { background:var(--dnd-border); }
/* ── Quill inside fly-in ── */
#noteEditorBody .ql-editor          { min-height:260px; font-size:14px; line-height:1.65; padding:14px 16px; color:var(--dnd-text); }
#noteEditorBody .ql-editor table    { border-collapse:collapse; width:100%; margin:8px 0; }
#noteEditorBody .ql-editor td,
#noteEditorBody .ql-editor th       { border:1px solid var(--dnd-border); padding:5px 8px; min-width:60px; }
#noteEditorBody .ql-editor th       { background:var(--dnd-bg); font-weight:600; }
#noteFlyIn .ql-toolbar.ql-snow      { border:none !important; border-bottom:1px solid var(--dnd-border) !important; }
#noteFlyIn .ql-container.ql-snow    { border:none !important; height:100%; }
#noteFlyIn .ql-snow .ql-picker-options { background:var(--dnd-surface); }

/* ── Responsive ─────────────────────────────────────────────── */
.wachat-back    { display:none; }              /* mobile-only "back to list" button */
.crm-close-btn  { display:none; }              /* mobile-only close button inside CRM panel */
.crm-backdrop   { position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1040; opacity:0; visibility:hidden; transition:opacity .2s ease; }
.crm-backdrop.show { opacity:1; visibility:visible; }

/* On medium screens the CRM panel would starve the chat thread, so make it a slide-in overlay */
@media (max-width: 1100px) {
    .wachat-crm {
        position:fixed; top:60px; right:0; bottom:0; height:calc(100vh - 60px);
        width:min(340px, 88vw); z-index:1050;
        background:var(--dnd-surface, #fff); box-shadow:-4px 0 24px rgba(0,0,0,.18);
        transform:translateX(100%); transition:transform .25s ease;
        display:flex !important;
    }
    .wachat-crm.crm-open { transform:none; }
    .crm-close-btn { display:inline-flex; }
}

/* On phones show a single pane at a time: conversation list OR the open thread */
@media (max-width: 767px) {
    /* Edge-to-edge, full-screen native feel (no page gutter, no card border) */
    .page-content { padding:0 !important; }
    .wachat-wrapper {
        border:0; border-radius:0;
        height:calc(100vh - 60px);
        height:calc(100dvh - 60px);
    }
    .wachat-sidebar { width:100%; min-width:0; max-width:none; border-right:none; }
    .wachat-main    { min-width:0; }
    .wachat-wrapper.has-active .wachat-sidebar    { display:none; }
    .wachat-wrapper:not(.has-active) .wachat-main { display:none; }
    .wachat-back    { display:inline-flex; }
    .wachat-main-header { padding:8px 10px; min-height:56px; }
    .wachat-sidebar-header, .wachat-crm > .crm-section:first-child { min-height:auto; }
    .bubble { max-width:85%; }
}
</style>
{{-- Quill 2 rich text editor --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">

<div>
    <div class="wachat-wrapper {{ isset($conversation) ? 'has-active' : '' }}">

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
            @php $slaActive = (bool) request()->sla_only; @endphp
            <div class="conv-status-tabs">
                <span class="tab {{ (!$slaActive && ($statusFilter === '' || $statusFilter === null)) ? 'active' : '' }}"
                      onclick="filterByStatus('')">{{ __('All') }}</span>
                @foreach(['open'=>__('Open'),'pending'=>__('Pending'),'resolved'=>__('Resolved')] as $st => $label)
                <span class="tab {{ (!$slaActive && $statusFilter === $st) ? 'active' : '' }}"
                      onclick="filterByStatus('{{ $st }}')">{{ $label }}</span>
                @endforeach
                <span class="tab {{ $slaActive ? 'active' : '' }}" id="slaFilterTab"
                      onclick="filterBySla()" style="{{ $slaActive ? '' : 'color:#dc3545;border-color:#dc3545;' }}">
                    🔴 SLA
                </span>
            </div>

            @if(isset($userLabels) && $userLabels->isNotEmpty())
            <div class="label-filter-bar">
                <span class="label-filter-chip {{ !request('label_id') ? 'active' : '' }}"
                      style="--lc:#6c757d"
                      onclick="filterByLabel(0)">{{ __('All') }}</span>
                @foreach($userLabels as $lbl)
                <span class="label-filter-chip {{ request('label_id') == $lbl->id ? 'active' : '' }}"
                      style="--lc:{{ $lbl->color }}"
                      onclick="filterByLabel({{ $lbl->id }})">{{ $lbl->name }}</span>
                @endforeach
            </div>
            @endif

            <div class="wachat-conv-list" id="convList">
                @forelse ($conversations as $conv)
                    <div class="wachat-conv-item {{ (isset($conversation) && $conversation->id === $conv->id) ? 'active' : '' }}"
                         data-conv-id="{{ $conv->id }}"
                         onclick="window.location='{{ route('chat.show', $conv->id) }}?conv_status={{ $conv->conversation_status }}{{ request('label_id') ? '&label_id='.request('label_id') : '' }}'">
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
                            @if($conv->labels->isNotEmpty())
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                @foreach($conv->labels as $lbl)
                                <span class="conv-label-dot" style="background:{{ $lbl->color }}" title="{{ $lbl->name }}"></span>
                                @endforeach
                            </div>
                            @endif
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
                    <a class="wachat-back btn btn-sm btn-outline-secondary align-items-center flex-shrink-0"
                       href="{{ route('chat.index') }}?conv_status={{ $statusFilter ?? 'open' }}"
                       title="{{ __('Back to conversations') }}">
                        <i class="bi bi-arrow-left"></i>
                    </a>
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
                            <span id="agentTypingLabel" style="display:none;font-style:italic;color:var(--dnd-accent-link)">{{ __('typing...') }}</span>
                            <span id="agentTypingMeta">
                                @if($conversation->display_name !== $conversation->contact_number)
                                    {{ $conversation->contact_number }}
                                    &nbsp;·&nbsp;
                                @endif
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
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 ms-auto flex-shrink-0">
                        {{-- Assign button --}}
                        @if($agents->count() > 0)
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-check"></i> <span class="d-none d-md-inline">{{ __('Assign') }}</span>
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
                            <i class="bi bi-person-fill-exclamation"></i> <span class="d-none d-md-inline">{{ __('Take Over') }}</span>
                        </button>
                        @endif

                        {{-- Resolve --}}
                        @if($conversation->conversation_status !== 'resolved')
                        <button class="btn btn-sm btn-outline-success" onclick="resolveConversation()"
                            title="{{ __('Resolve & close conversation') }}">
                            <i class="bi bi-check2-circle"></i> <span class="d-none d-md-inline">{{ __('Resolve') }}</span>
                        </button>
                        @endif

                        {{-- Internal note toggle --}}
                        <button class="btn btn-sm btn-outline-warning" id="internalModeBtn"
                            onclick="toggleInternalMode()" title="{{ __('Toggle internal note mode') }}">
                            <i class="bi bi-lock"></i> <span class="d-none d-md-inline">{{ __('Note') }}</span>
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

                {{-- Quick-reply picker (shown above input when / is typed) --}}
                <div id="qrPicker" style="display:none;position:relative">
                    <div style="position:absolute;bottom:0;left:12px;right:12px;background:var(--dnd-surface);border:1px solid var(--dnd-border);border-radius:10px 10px 0 0;box-shadow:0 -4px 16px rgba(0,0,0,.08);max-height:220px;overflow-y:auto;z-index:200" id="qrList"></div>
                </div>

                {{-- Input --}}
                <div class="wachat-input-area">

                    {{-- ── Attachment bottom-sheet ── --}}
                    <div class="dropdown dropup" id="attachMenu">
                        <button id="attachBtn" class="btn btn-outline-secondary" data-bs-toggle="dropdown"
                                aria-expanded="false" title="{{ __('Attach') }}">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <ul class="dropdown-menu attach-sheet p-0 shadow-lg">

                            {{-- ── Outreach group ── --}}
                            <li><span class="attach-group-label">{{ __('Outreach') }}</span></li>
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   @if(isset($approvedTemplates) && $approvedTemplates->count() > 0)
                                       data-bs-toggle="modal" data-bs-target="#templateModal"
                                   @else
                                       onclick="toastr.warning('{{ __('No approved templates. Create one first.') }}');return false;"
                                   @endif>
                                    <span class="ai-icon ai-teal"><i class="bi bi-layout-text-sidebar-reverse"></i></span>
                                    <span class="ai-text">{{ __('Template') }}</span>
                                    <span class="ks-badge">T</span>
                                </a>
                            </li>
                            <li>
                                <a class="attach-item dropdown-item" href="{{ route('quick-replies.index') }}" target="_blank"
                                   id="qrMenuBtn" onclick="openQrPicker(event)">
                                    <span class="ai-icon ai-teal"><i class="bi bi-lightning-fill"></i></span>
                                    <span class="ai-text">{{ __('Quick Replies') }}</span>
                                    <span class="ks-badge">/</span>
                                </a>
                            </li>

                            {{-- ── Media group ── --}}
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><span class="attach-group-label">{{ __('Media') }}</span></li>
                            {{-- hidden file input for Photos & Videos --}}
                            <input type="file" id="photosVideosInput" accept="image/*,video/*" style="display:none">
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   onclick="document.getElementById('photosVideosInput').click();return false;">
                                    <span class="ai-icon ai-blue"><i class="bi bi-image-fill"></i></span>
                                    <span class="ai-text">{{ __('Photos & Videos') }}</span>
                                    <span class="ks-badge">@</span>
                                </a>
                            </li>
                            {{-- hidden file input for Document --}}
                            <input type="file" id="documentInput" style="display:none">
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   onclick="document.getElementById('documentInput').click();return false;">
                                    <span class="ai-icon ai-blue"><i class="bi bi-file-earmark-fill"></i></span>
                                    <span class="ai-text">{{ __('Document') }}</span>
                                </a>
                            </li>
                            {{-- hidden file input for Audio --}}
                            <input type="file" id="audioInput" accept="audio/mpeg,audio/mp3,audio/3gpp,audio/ogg,audio/aac,audio/wav,audio/amr,audio/*" style="display:none">
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   onclick="document.getElementById('audioInput').click();return false;">
                                    <span class="ai-icon ai-blue"><i class="bi bi-music-note-beamed"></i></span>
                                    <span class="ai-text">{{ __('Audio') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   onclick="openCamera();return false;">
                                    <span class="ai-icon ai-blue"><i class="bi bi-camera-fill"></i></span>
                                    <span class="ai-text">{{ __('Camera') }}</span>
                                </a>
                            </li>

                            {{-- ── Business group ── --}}
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><span class="attach-group-label">{{ __('Business') }}</span></li>
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   data-bs-toggle="modal" data-bs-target="#pollModal">
                                    <span class="ai-icon ai-amber"><i class="bi bi-bar-chart-fill"></i></span>
                                    <span class="ai-text">{{ __('Poll') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   data-bs-toggle="modal" data-bs-target="#contactModal">
                                    <span class="ai-icon ai-amber"><i class="bi bi-person-vcard-fill"></i></span>
                                    <span class="ai-text">{{ __('Contact') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   data-bs-toggle="modal" data-bs-target="#catalogueModal">
                                    <span class="ai-icon ai-amber"><i class="bi bi-shop"></i></span>
                                    <span class="ai-text">{{ __('Catalogue') }}</span>
                                </a>
                            </li>

                            {{-- ── More group ── --}}
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><span class="attach-group-label">{{ __('More') }}</span></li>
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   data-bs-toggle="modal" data-bs-target="#eventModal">
                                    <span class="ai-icon ai-purple"><i class="bi bi-calendar-event-fill"></i></span>
                                    <span class="ai-text">{{ __('Event') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="attach-item dropdown-item" href="#"
                                   data-bs-toggle="modal" data-bs-target="#meetingModal">
                                    <span class="ai-icon ai-purple"><i class="bi bi-camera-video-fill"></i></span>
                                    <span class="ai-text">{{ __('Meeting Link') }}</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                    {{-- ── end attachment ── --}}

                    <textarea id="chatTextarea" class="form-control" rows="1"
                        placeholder="{{ __('Type a message… (Enter sends · Shift+Enter newline · / quick reply · @ attach)') }}"></textarea>
                    <button id="sendBtn" class="btn btn-primary" style="border-radius:50%">
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

            {{-- Mobile-only close button (panel is an overlay on small screens) --}}
            <button type="button" class="crm-close-btn btn btn-sm btn-outline-secondary align-items-center"
                    style="position:absolute;top:8px;right:8px;z-index:2;" onclick="toggleCrm()"
                    title="{{ __('Close') }}">
                <i class="bi bi-x-lg"></i>
            </button>

            {{-- Contact Info --}}
            <div class="crm-section">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">{{ __('Contact') }}</h6>
                    <a href="{{ route('contact.timeline', $conversation->contact_number) }}"
                       class="btn btn-xs btn-outline-secondary" target="_blank"
                       title="{{ __('360° Profile') }}" style="font-size:11px;padding:2px 7px">
                        <i class="bi bi-person-lines-fill me-1"></i>360°
                    </a>
                </div>
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

            {{-- Conversation Labels --}}
            <div class="crm-section" id="labelSection">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">{{ __('Labels') }}</h6>
                    <div class="dropdown">
                        <button class="btn btn-xs btn-outline-secondary" data-bs-toggle="dropdown"
                                aria-expanded="false" title="{{ __('Add label') }}">
                            <i class="bi bi-tag me-1"></i>+
                        </button>
                        <div class="dropdown-menu shadow" style="min-width:210px;max-height:300px;overflow-y:auto"
                             onclick="event.stopPropagation()">
                            <div class="px-2 pt-2 pb-1">
                                <input type="text" id="labelSearch" class="form-control form-control-sm"
                                       placeholder="{{ __('Search or create...') }}" autocomplete="off">
                            </div>
                            <div id="labelPickerList">
                                @foreach($userLabels as $lbl)
                                <div class="label-picker-item"
                                     data-label-id="{{ $lbl->id }}"
                                     data-label-name="{{ $lbl->name }}"
                                     data-label-color="{{ $lbl->color }}"
                                     onclick="toggleLabel({{ $lbl->id }}, '{{ addslashes($lbl->name) }}', '{{ $lbl->color }}')">
                                    <span style="width:12px;height:12px;border-radius:50%;background:{{ $lbl->color }};flex-shrink:0;display:inline-block"></span>
                                    <span class="flex-1">{{ $lbl->name }}</span>
                                    <i class="bi bi-check2 ms-auto label-pick-check" id="lbl-check-{{ $lbl->id }}"
                                       style="display:none;color:{{ $lbl->color }}"></i>
                                </div>
                                @endforeach
                                @if($userLabels->isEmpty())
                                <div class="px-3 py-2 text-muted small" id="noLabelsHint">{{ __('No labels yet') }}</div>
                                @endif
                            </div>
                            <div class="border-top mt-1 pt-1 px-2 pb-2" id="createLabelArea" style="display:none">
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <input type="color" id="newLabelColor" value="#6f42c1"
                                           style="width:32px;height:28px;border:1px solid var(--dnd-border);border-radius:4px;cursor:pointer;padding:2px;flex-shrink:0">
                                    <button class="btn btn-xs btn-primary flex-1" onclick="createLabel()">
                                        {{ __('Create') }} "<span id="newLabelNamePreview"></span>"
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="conversationLabels" class="d-flex flex-wrap gap-1">
                    @foreach($conversation->labels as $lbl)
                    <span class="crm-label-chip" id="conv-lbl-{{ $lbl->id }}" style="background:{{ $lbl->color }}">
                        {{ $lbl->name }}
                        <span class="rm" onclick="toggleLabel({{ $lbl->id }}, '{{ addslashes($lbl->name) }}', '{{ $lbl->color }}')" title="{{ __('Remove') }}">×</span>
                    </span>
                    @endforeach
                    @if($conversation->labels->isEmpty())
                    <span class="text-muted small" id="noLabelsMsg">{{ __('No labels') }}</span>
                    @endif
                </div>
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

            {{-- Notes --}}
            <div class="crm-section">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">{{ __('Notes') }}</h6>
                    <button class="btn btn-sm btn-warning" onclick="openNoteEditor()">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('New Note') }}
                    </button>
                </div>
                <div id="notesContainer">
                    @forelse($conversation->notes->sortByDesc('created_at') as $note)
                        @include('theme::pages.chat._note-card', ['note' => $note])
                    @empty
                        <div class="text-muted small" id="noNotesMsg">{{ __('No notes yet.') }}</div>
                    @endforelse
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

                    {{-- Contact search from phonebook --}}
                    <div class="mb-2" style="position:relative">
                        <label class="form-label fw-semibold small">{{ __('Search Phonebook') }}</label>
                        <input type="text" id="newChatSearch" class="form-control form-control-sm"
                               placeholder="{{ __('Type name or number…') }}" autocomplete="off">
                        <div id="newChatSuggestions"
                             style="display:none;position:absolute;left:0;right:0;top:100%;background:var(--dnd-surface,#fff);
                                    border:1px solid var(--dnd-border,#ddd);border-radius:0 0 8px 8px;
                                    box-shadow:0 4px 12px rgba(0,0,0,.1);z-index:9999;max-height:180px;overflow-y:auto">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" id="newChatNumber" class="form-control form-control-sm"
                            placeholder="e.g. 60123456789" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">{{ __('Name') }} <small class="text-muted">({{ __('optional') }})</small></label>
                        <input type="text" name="contact_name" id="newChatName" class="form-control form-control-sm">
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

<script>
(function () {
    const CONTACT_SEARCH_URL = '{{ route("contacts.search") }}';
    let ncTimer = null;

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput   = document.getElementById('newChatSearch');
        const suggestions   = document.getElementById('newChatSuggestions');
        const numberInput   = document.getElementById('newChatNumber');
        const nameInput     = document.getElementById('newChatName');
        if (!searchInput) return;

        searchInput.addEventListener('input', function () {
            clearTimeout(ncTimer);
            const q = this.value.trim();
            if (q.length < 1) { suggestions.style.display = 'none'; return; }
            ncTimer = setTimeout(() => {
                fetch(CONTACT_SEARCH_URL + '?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    suggestions.innerHTML = '';
                    if (!data.length) {
                        suggestions.style.display = 'none'; return;
                    }
                    data.forEach(c => {
                        const item = document.createElement('div');
                        item.style.cssText = 'padding:7px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--dnd-border,#eee)';
                        item.innerHTML = `<div class="fw-semibold">${c.name || c.number}</div>`
                            + `<div style="font-size:11px;color:#888">${c.number}`
                            + (c.phonebook ? ` &nbsp;·&nbsp; <span style="color:var(--dnd-brand,#128c7e)">${c.phonebook}</span>` : '')
                            + `</div>`;
                        item.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            numberInput.value = c.number;
                            nameInput.value   = c.name || '';
                            searchInput.value = c.name || c.number;
                            suggestions.style.display = 'none';
                        });
                        item.addEventListener('mouseover',  () => item.style.background = 'var(--dnd-brand-subtle,#e8f5f3)');
                        item.addEventListener('mouseout',   () => item.style.background = '');
                        suggestions.appendChild(item);
                    });
                    suggestions.style.display = 'block';
                });
            }, 220);
        });

        searchInput.addEventListener('blur', () => {
            setTimeout(() => { suggestions.style.display = 'none'; }, 150);
        });

        // Clear search field when modal closes
        document.getElementById('newChatModal')?.addEventListener('hidden.bs.modal', function () {
            searchInput.value = '';
            suggestions.style.display = 'none';
        });
    });
})();
</script>

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

{{-- ── Shared Media Modal (Photos/Videos, Document, Audio, Camera) ── --}}
@if (isset($conversation))
<div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="mediaModalLabel">{{ __('Send Media') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('File') }}</label>
                    <input type="file" id="mediaFileInput" class="form-control form-control-sm">
                </div>
                <img id="mediaPreviewImg" src="" style="display:none;max-width:100%;border-radius:8px;margin-bottom:8px">
                <div>
                    <label class="form-label fw-semibold small">{{ __('Caption') }} <small class="text-muted">({{ __('optional') }})</small></label>
                    <input type="text" id="mediaCaption" class="form-control form-control-sm" placeholder="{{ __('Add a caption…') }}">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-primary" id="mediaSendBtn" onclick="uploadAndSendMedia()">
                    <i class="bi bi-send me-1"></i>{{ __('Send') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Camera Capture Modal ── --}}
<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content overflow-hidden" style="border-radius:16px">
            <div class="modal-header py-2 border-0 d-flex align-items-center gap-2" style="background:#000;color:#fff">
                <h6 class="modal-title mb-0 small">{{ __('Camera') }}</h6>
                <select id="cameraSelect" class="form-select form-select-sm ms-1"
                    style="display:none;max-width:180px;background:#333;color:#fff;border-color:#555;flex:1"></select>
                <button type="button" class="btn-close btn-close-white ms-auto p-0" onclick="closeCamera()"></button>
            </div>

            <div class="modal-body p-0 position-relative" style="background:#000;min-height:300px">
                {{-- Live preview --}}
                <div id="camLiveView">
                    <video id="camVideo" autoplay muted playsinline
                        style="width:100%;display:block;max-height:380px;object-fit:cover"></video>
                    <div id="camRecTimer"
                        style="display:none;position:absolute;top:10px;left:50%;transform:translateX(-50%);
                               background:rgba(220,53,69,.9);color:#fff;padding:3px 14px;
                               border-radius:20px;font-size:13px;font-weight:700;letter-spacing:.5px">
                        ⏺ 00:00
                    </div>
                </div>
                {{-- Post-capture preview --}}
                <div id="camPreviewView" style="display:none">
                    <img id="camPhotoPreview" style="width:100%;display:none;max-height:380px;object-fit:contain">
                    <video id="camVideoPreview" controls
                        style="width:100%;display:none;max-height:380px;background:#000"></video>
                </div>
            </div>

            <div class="modal-footer border-0 justify-content-center gap-3 py-3" style="background:#111">
                {{-- Live controls --}}
                <div id="camLiveControls" class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-light" id="camModePhotoBtn"
                        onclick="setCamMode('photo')" style="border-radius:20px;min-width:72px">
                        📷 {{ __('Photo') }}
                    </button>
                    <button id="camShootBtn" onclick="camShoot()"
                        class="btn rounded-circle d-flex align-items-center justify-content-center"
                        style="width:58px;height:58px;background:#fff;border:4px solid #aaa;font-size:20px;flex-shrink:0">
                        ⬤
                    </button>
                    <button class="btn btn-sm btn-outline-light" id="camModeVideoBtn"
                        onclick="setCamMode('video')" style="border-radius:20px;min-width:72px">
                        🎬 {{ __('Video') }}
                    </button>
                </div>
                {{-- Post-capture controls --}}
                <div id="camCaptureControls" style="display:none" class="d-flex gap-3">
                    <button class="btn btn-sm btn-outline-light" onclick="camRetake()">
                        ↩ {{ __('Retake') }}
                    </button>
                    <button class="btn btn-sm btn-success" id="camSendBtn" onclick="camSend()">
                        <i class="bi bi-send me-1"></i>{{ __('Send') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Poll Modal ── --}}
<div class="modal fade" id="pollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-bar-chart-fill me-2"></i>{{ __('Send Poll') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Question') }}</label>
                    <input type="text" id="pollQuestion" class="form-control form-control-sm" placeholder="{{ __('What would you like to ask?') }}" maxlength="255">
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold small">{{ __('Options') }} <small class="text-muted">({{ __('2–10') }})</small></label>
                    <div id="pollOptions">
                        <input type="text" class="form-control form-control-sm mb-1 poll-opt" placeholder="{{ __('Option 1') }}" maxlength="100">
                        <input type="text" class="form-control form-control-sm mb-1 poll-opt" placeholder="{{ __('Option 2') }}" maxlength="100">
                    </div>
                    <button class="btn btn-xs btn-outline-secondary mt-1" onclick="addPollOption()">
                        <i class="bi bi-plus-sm"></i> {{ __('Add option') }}
                    </button>
                </div>
                <div class="mt-2 d-flex align-items-center gap-2">
                    <input type="checkbox" class="form-check-input" id="pollMulti">
                    <label class="form-check-label small" for="pollMulti">{{ __('Allow multiple answers') }}</label>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-primary" onclick="submitPoll()">
                    <i class="bi bi-send me-1"></i>{{ __('Send Poll') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Contact Modal ── --}}
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-person-vcard-fill me-2"></i>{{ __('Send Contact') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Full Name') }}</label>
                    <input type="text" id="contactName" class="form-control form-control-sm" placeholder="{{ __('e.g. John Smith') }}">
                </div>
                <div>
                    <label class="form-label fw-semibold small">{{ __('Phone Number') }}</label>
                    <input type="text" id="contactPhone" class="form-control form-control-sm" placeholder="{{ __('e.g. 60123456789') }}">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-primary" onclick="submitContact()">
                    <i class="bi bi-send me-1"></i>{{ __('Send Contact') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Catalogue Modal ── --}}
<div class="modal fade" id="catalogueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-shop me-2"></i>{{ __('Send Catalogue') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Select Catalogue') }}</label>
                    <select id="catalogSelect" class="form-select form-select-sm" onchange="loadCatalogueProducts(this.value)">
                        <option value="">{{ __('Loading…') }}</option>
                    </select>
                </div>
                <div class="mb-3" id="productSelectWrap" style="display:none">
                    <label class="form-label fw-semibold small">{{ __('Specific Product') }} <small class="text-muted">({{ __('optional') }})</small></label>
                    <select id="productSelect" class="form-select form-select-sm">
                        <option value="">{{ __('Entire catalogue (no specific product)') }}</option>
                    </select>
                </div>
                <div id="catalogManualWrap" style="display:none" class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Or enter Catalog ID manually') }}</label>
                    <input type="text" id="catalogIdManual" class="form-control form-control-sm" placeholder="{{ __('Catalog ID') }}">
                </div>
                <div>
                    <label class="form-label fw-semibold small">{{ __('Message Body') }} <small class="text-muted">({{ __('optional') }})</small></label>
                    <input type="text" id="catalogBody" class="form-control form-control-sm" placeholder="{{ __('Browse our catalog') }}">
                </div>
            </div>
            <div class="modal-footer py-2 justify-content-between">
                <button class="btn btn-xs btn-link text-muted p-0" onclick="toggleManualCatalog()">{{ __('Enter ID manually') }}</button>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button class="btn btn-sm btn-primary" onclick="submitCatalog()">
                        <i class="bi bi-send me-1"></i>{{ __('Send') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Event Modal ── --}}
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-calendar-event-fill me-2" style="color:#7c3aed"></i>{{ __('Create Event') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body d-flex flex-column gap-2" style="max-height:72vh;overflow-y:auto">

                {{-- Event Details --}}
                <div class="ev-section">
                    <span class="ev-section-label">{{ __('Event Details') }}</span>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1">{{ __('Event Name') }} <span class="text-danger">*</span></label>
                        <input type="text" id="evName" class="form-control form-control-sm"
                            placeholder="{{ __('e.g. Product Launch') }}" maxlength="120">
                    </div>
                    <div>
                        <label class="form-label fw-semibold small mb-1">
                            {{ __('Description') }} <span class="text-muted fw-normal">({{ __('optional') }})</span>
                        </label>
                        <textarea id="evDesc" class="form-control form-control-sm" rows="2"
                            placeholder="{{ __('What is this event about?') }}"></textarea>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="ev-section">
                    <span class="ev-section-label">{{ __('Schedule') }}</span>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                            <input type="date" id="evStartDate" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">{{ __('Start Time') }}</label>
                            <input type="time" id="evStartTime" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">{{ __('End Date') }}</label>
                            <input type="date" id="evEndDate" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">{{ __('End Time') }}</label>
                            <input type="time" id="evEndTime" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                {{-- Location --}}
                <div class="ev-section">
                    <span class="ev-section-label">
                        {{ __('Location') }} <span class="text-muted fw-normal" style="font-size:9px;text-transform:none;letter-spacing:0">({{ __('optional') }})</span>
                    </span>
                    <input type="text" id="evLocation" class="form-control form-control-sm"
                        placeholder="{{ __('e.g. Conference Hall A, Zoom, Google Meet…') }}">
                </div>

                {{-- WhatsApp Call --}}
                <div class="ev-section">
                    <div class="d-flex align-items-center justify-content-between mb-0">
                        <span class="ev-section-label mb-0">{{ __('WhatsApp Call') }}</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="evCallToggle"
                                role="switch" onchange="toggleEvCall(this)">
                        </div>
                    </div>
                    <div id="evCallOptions" style="display:none;padding-top:10px;border-top:1px solid var(--dnd-border);margin-top:8px">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">{{ __('Call Type') }}</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="evCallType"
                                        id="evCallAudio" value="audio" checked>
                                    <label class="form-check-label small" for="evCallAudio">📞 {{ __('Audio Call') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="evCallType"
                                        id="evCallVideo" value="video">
                                    <label class="form-check-label small" for="evCallVideo">🎥 {{ __('Video Call') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="evCallApproval">
                            <label class="form-check-label small" for="evCallApproval">
                                {{ __('Require approval to join') }}
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Custom URL --}}
                <div class="ev-section">
                    <span class="ev-section-label">
                        {{ __('Custom URL') }} <span class="text-muted fw-normal" style="font-size:9px;text-transform:none;letter-spacing:0">({{ __('optional') }})</span>
                    </span>
                    <input type="url" id="evUrl" class="form-control form-control-sm" placeholder="https://…">
                </div>

            </div>

            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-primary" onclick="submitEvent()">
                    <i class="bi bi-send me-1"></i>{{ __('Send Event') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Meeting Link Modal ── --}}
<div class="modal fade" id="meetingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-camera-video-fill me-2"></i>{{ __('Send Meeting Link') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Meeting URL') }}</label>
                    <input type="url" id="meetingUrl" class="form-control form-control-sm" placeholder="https://meet.google.com/…">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Button Label') }} <small class="text-muted">({{ __('optional') }})</small></label>
                    <input type="text" id="meetingLabel" class="form-control form-control-sm" placeholder="{{ __('Join Meeting') }}" maxlength="20">
                </div>
                <div>
                    <label class="form-label fw-semibold small">{{ __('Message Body') }} <small class="text-muted">({{ __('optional') }})</small></label>
                    <input type="text" id="meetingBody" class="form-control form-control-sm" placeholder="{{ __('e.g. Join our weekly sync call') }}">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-primary" onclick="submitMeetingLink()">
                    <i class="bi bi-send me-1"></i>{{ __('Send') }}
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
const CSRF           = '{{ csrf_token() }}';
const UPLOAD_URL     = '{{ route('chat.upload.media', $conversation->id) }}';
const SEND_MEDIA_URL = '{{ route('chat.send.media', $conversation->id) }}';
const SEND_POLL_URL  = '{{ route('chat.send.poll', $conversation->id) }}';
const SEND_CONTACT_URL = '{{ route('chat.send.contact', $conversation->id) }}';
const SEND_CATALOG_URL = '{{ route('chat.send.catalog', $conversation->id) }}';
const SEND_MEETING_URL = '{{ route('chat.send.meeting', $conversation->id) }}';
const QUICK_REPLIES  = @json($quickReplies ?? []);
let lastId           = {{ $messages->last()?->id ?? 0 }};
let pollTimer       = null;
let typingTimer     = null;
let isInternalMode  = false;

// ── Auto-scroll ────────────────────────────────────────────────
function scrollBottom() {
    const area = document.getElementById('messagesArea');
    if (area) area.scrollTop = area.scrollHeight;
}
scrollBottom();

// ── WhatsApp text formatter ────────────────────────────────────
function waFmt(text) {
    if (!text) return '';
    // Escape HTML first
    text = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    // Bold *text*
    text = text.replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');
    // Italic _text_
    text = text.replace(/_([^_\n]+)_/g, '<em>$1</em>');
    // Strikethrough ~text~
    text = text.replace(/~([^~\n]+)~/g, '<del>$1</del>');
    // Monospace `text`
    text = text.replace(/`([^`\n]+)`/g, '<code style="font-family:monospace;font-size:.9em;background:rgba(0,0,0,.07);border-radius:3px;padding:0 3px">$1</code>');
    // Newlines
    text = text.replace(/\n/g, '<br>');
    return text;
}

// ── Build bubble HTML ──────────────────────────────────────────
function buildBubble(msg) {
    const isOut  = msg.direction === 'outbound';
    const tick   = isOut ? tickHtml(msg.status) : '';
    const type   = msg.type || 'text';
    const body   = msg.body || '';
    const url    = msg.media_url || '';
    const hidden = ['[Image]','[Video]','[Audio]','[Document]','[Sticker]','[Location]','[Contact]'];

    let mediaHtml = '';
    if (url) {
        if (type === 'image' || type === 'sticker') {
            mediaHtml = `<img src="${url}" class="bubble-media" onerror="this.style.display='none'">`;
        } else if (type === 'video') {
            mediaHtml = `<video src="${url}" controls class="bubble-media" style="border-radius:6px;max-width:100%"></video>`;
        } else if (type === 'audio') {
            mediaHtml = `<audio src="${url}" controls style="width:220px;max-width:100%"></audio>`;
        } else if (type === 'document') {
            mediaHtml = `<a href="${url}" target="_blank" class="d-flex align-items-center gap-2 text-decoration-none mb-1 p-2" style="background:rgba(0,0,0,.05);border-radius:8px;">
                <i class="bi bi-file-earmark-text" style="font-size:22px;flex-shrink:0"></i>
                <div><div style="font-size:12px;font-weight:600">Document</div><div style="font-size:10px;opacity:.6">Tap to open</div></div>
                <i class="bi bi-download ms-auto" style="font-size:13px;opacity:.5"></i></a>`;
        }
    }

    let bodyHtml = '';
    const typeLabels = {
        template: ['bi-layout-text-sidebar-reverse', 'Template'],
        poll:     ['bi-bar-chart-line', 'Poll'],
        meeting_link: ['bi-camera-video', 'Meeting Link'],
        catalog:  ['bi-shop', 'Catalogue'],
    };
    if (typeLabels[type]) {
        const [icon, label] = typeLabels[type];
        bodyHtml = `<div class="d-flex align-items-center gap-1 mb-1" style="opacity:.55;font-size:11px">
            <i class="bi ${icon}"></i><span style="font-weight:600;text-transform:uppercase;letter-spacing:.4px">${label}</span></div>`;
        if (body && !hidden.includes(body)) bodyHtml += `<div>${waFmt(body)}</div>`;
    } else if (type === 'contact') {
        bodyHtml = `<div class="d-flex align-items-center gap-2" style="background:rgba(0,0,0,.05);border-radius:8px;padding:8px 10px">
            <i class="bi bi-person-circle" style="font-size:28px;flex-shrink:0;opacity:.5"></i>
            <div style="font-weight:600;font-size:14px">${waFmt(body) || '<span style="opacity:.5;font-size:12px">Contact</span>'}</div></div>`;
    } else if (type === 'location') {
        bodyHtml = `<div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-geo-alt-fill" style="font-size:18px;color:#dc3545"></i><span style="font-size:12px;font-weight:600">Location</span></div>`;
        if (body && !hidden.includes(body)) bodyHtml += `<div style="font-size:12px;opacity:.7">${waFmt(body)}</div>`;
    } else if (!url || (body && !hidden.includes(body))) {
        if (body && !hidden.includes(body)) bodyHtml = `<div>${waFmt(body)}</div>`;
        else if (!url) bodyHtml = `<em class="text-muted small">${body}</em>`;
    }

    // Caption under media (not for document which shows filename)
    if (url && body && !hidden.includes(body) && type !== 'document' && !typeLabels[type]) {
        bodyHtml += `<div class="mt-1" style="font-size:13px">${waFmt(body)}</div>`;
    }

    return `<div class="bubble-wrap ${msg.direction}" data-id="${msg.id}">
        <div class="bubble ${msg.direction}">
            ${mediaHtml}${bodyHtml}
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

// Replace the delivery tick on a single outbound bubble (by ChatMessage id)
function updateTick(id, status) {
    const time = document.querySelector(`.bubble-wrap[data-id="${id}"] .bubble-time`);
    if (!time) return;
    const tick = time.querySelector('.status-tick');
    if (tick) tick.outerHTML = tickHtml(status);
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
                if (msg.direction === 'outbound') updateTick(msg.id, msg.status);
            });
        }
        if (data.recent_statuses) {
            data.recent_statuses.forEach(function(s) {
                updateTick(s.id, s.status);
            });
        }
    });
}

// ── Socket.io real-time ────────────────────────────────────────
// socket.io is loaded with `defer`, so it isn't available while this inline
// script is parsed. Wait for DOMContentLoaded (fires after deferred scripts
// have executed) so `io` is defined before we connect.
document.addEventListener('DOMContentLoaded', function () {
    @php
        // SOCKET_PUBLIC_URL lets the socket server live on its own (sub)domain
        // — needed when it's deployed separately from the app, e.g. behind a
        // hosting panel's Node.js App manager, which doesn't expose custom
        // ports on the main domain. Falls back to same-host:PORT_NODE for
        // setups (like local dev) where one process serves both.
        $socketPublicUrl = env('SOCKET_PUBLIC_URL');
        if (!$socketPublicUrl) {
            $parsedUrl = parse_url(env('APP_URL', 'http://localhost'));
            $socketHost = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? 'localhost');
            $socketPublicUrl = $socketHost . ':' . env('PORT_NODE', 3100);
        }
    @endphp
    var socketUrl = '{{ $socketPublicUrl }}';
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

        // Delivery tick updates (sent → delivered → read / failed) in real time
        socket.on('message_status', function (data) {
            if (data && data.id) updateTick(data.id, data.status);
        });

        // Typing indicator from other agents — shown inline in the header subtitle
        socket.on('agent_typing', function (data) {
            const label = document.getElementById('agentTypingLabel');
            const meta  = document.getElementById('agentTypingMeta');
            if (!label || !meta) return;
            clearTimeout(window._typingClearTimer);
            if (data.typing) {
                label.textContent = data.agent_name + ' {{ __("is typing...") }}';
                label.style.display = '';
                meta.style.display  = 'none';
                window._typingClearTimer = setTimeout(function () {
                    label.style.display = 'none';
                    meta.style.display  = '';
                }, 4000);
            } else {
                label.style.display = 'none';
                meta.style.display  = '';
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
});

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
// Legacy saveNote kept for socket new_note event from other agents
function appendNote(note) {
    if (typeof prependNoteCard === 'function') {
        prependNoteCard(note);
    }
}

function appendNoteToMessages(note) {
    const title   = note.title || '';
    const snippet = (note.note || '').replace(/<[^>]+>/g, '').substring(0, 80);
    const display = title ? `<strong>${title}</strong><br><span style="opacity:.7;font-size:12px">${snippet}</span>` : snippet;
    const html = `<div class="bubble-wrap outbound">
        <div class="bubble internal-note">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-lock-fill text-warning" style="font-size:11px"></i>
                <small class="fw-semibold text-warning-emphasis">{{ __('Internal Note') }} — ${note.author}</small>
            </div>
            <div style="font-size:13px">${display}</div>
            <div class="bubble-time">${note.time}</div>
        </div>
    </div>`;
    $('#messagesArea').append(html);
    scrollBottom();
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
    if (!panel) return;
    // On narrow screens the panel is a slide-in overlay; on desktop it's toggled in-flow.
    if (window.matchMedia('(max-width: 1100px)').matches) {
        const opening = !panel.classList.contains('crm-open');
        panel.classList.toggle('crm-open', opening);
        crmBackdrop(opening);
    } else {
        panel.style.display = (panel.style.display === 'none') ? '' : 'none';
    }
}

function crmBackdrop(show) {
    let bd = document.getElementById('crmBackdrop');
    if (show) {
        if (!bd) {
            bd = document.createElement('div');
            bd.id = 'crmBackdrop';
            bd.className = 'crm-backdrop';
            bd.addEventListener('click', () => toggleCrm());
            document.body.appendChild(bd);
        }
        requestAnimationFrame(() => bd.classList.add('show'));
    } else if (bd) {
        bd.classList.remove('show');
    }
}

// ── Sidebar filters ────────────────────────────────────────────
function filterByStatus(status) {
    const url = new URL(window.location.href);
    if (status === '') url.searchParams.delete('conv_status');
    else url.searchParams.set('conv_status', status);
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

function filterByLabel(labelId) {
    const url = new URL(window.location.href);
    if (labelId) {
        url.searchParams.set('label_id', labelId);
    } else {
        url.searchParams.delete('label_id');
        url.searchParams.delete('conv_status');
        url.searchParams.delete('sla_only');
    }
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

// ── Direct file-upload helper (no modal) ───────────────────────
function uploadFileAndSend(file, mediaType) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', CSRF);
    toastr.info('{{ __("Uploading…") }}');
    fetch(UPLOAD_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.url) throw new Error(data.message ?? '{{ __("Upload failed") }}');
            return fetch(SEND_MEDIA_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ url: data.url, media_type: mediaType, caption: '' })
            }).then(r => r.json());
        })
        .then(res => {
            if (res.error) throw new Error(res.message ?? '{{ __("Send failed") }}');
            if (res.message) { $('#messagesArea').append(buildBubble(res.message)); lastId = Math.max(lastId, res.message.id); scrollBottom(); }
            toastr.success('{{ __("Sent!") }}');
        })
        .catch(err => toastr.error(err.message));
}

document.getElementById('photosVideosInput')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const mediaType = file.type.startsWith('video/') ? 'video' : 'image';
    uploadFileAndSend(file, mediaType);
    this.value = '';
});

document.getElementById('documentInput')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    uploadFileAndSend(file, 'document');
    this.value = '';
});

document.getElementById('audioInput')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    uploadFileAndSend(file, 'audio');
    this.value = '';
});

// ── Camera capture ─────────────────────────────────────────────
let _camStream = null, _camMode = 'photo', _camRecorder = null;
let _camChunks = [], _camBlob = null, _camTimerInterval = null, _camSeconds = 0;

async function openCamera() {
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
        toastr.warning(
            '{{ __("Camera requires a secure connection (HTTPS). To enable it locally, open Chrome and go to chrome://flags/#unsafely-treat-insecure-origin-as-secure, add your site URL, and relaunch.") }}',
            '{{ __("HTTPS required") }}',
            { timeOut: 0, extendedTimeOut: 0, closeButton: true }
        );
        return;
    }
    try {
        // Trigger permission prompt first, then release
        const probe = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        probe.getTracks().forEach(t => t.stop());

        // Enumerate cameras
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cams    = devices.filter(d => d.kind === 'videoinput');
        const sel     = document.getElementById('cameraSelect');
        sel.innerHTML = '';
        cams.forEach((d, i) => {
            const opt = document.createElement('option');
            opt.value = d.deviceId;
            opt.textContent = d.label || `{{ __('Camera') }} ${i + 1}`;
            sel.appendChild(opt);
        });
        sel.style.display = cams.length > 1 ? '' : 'none';

        // Reset UI to live view
        _camBlob = null;
        document.getElementById('camLiveView').style.display      = '';
        document.getElementById('camPreviewView').style.display   = 'none';
        document.getElementById('camLiveControls').style.display  = '';
        document.getElementById('camCaptureControls').style.display = 'none';
        document.getElementById('camRecTimer').style.display      = 'none';
        setCamMode('photo');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('cameraModal')).show();
        await _startCamStream(sel.value || null);
    } catch (e) {
        if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
            toastr.error('{{ __("Camera permission denied. Please allow access in your browser settings.") }}');
        } else if (e.name === 'NotFoundError') {
            toastr.error('{{ __("No camera found on this device.") }}');
        } else {
            toastr.error(e.message || '{{ __("Could not access camera.") }}');
        }
    }
}

document.getElementById('cameraSelect')?.addEventListener('change', function () {
    _startCamStream(this.value || null);
});

// Stop stream when modal is fully hidden (Escape / programmatic close)
document.getElementById('cameraModal')?.addEventListener('hidden.bs.modal', closeCamera);

async function _startCamStream(deviceId) {
    if (_camStream) _camStream.getTracks().forEach(t => t.stop());
    const constraints = {
        video: deviceId ? { deviceId: { exact: deviceId }, width: { ideal: 1280 }, height: { ideal: 720 } } : { width: { ideal: 1280 } },
        audio: false
    };
    _camStream = await navigator.mediaDevices.getUserMedia(constraints);
    document.getElementById('camVideo').srcObject = _camStream;
}

function setCamMode(mode) {
    _camMode = mode;
    const pBtn = document.getElementById('camModePhotoBtn');
    const vBtn = document.getElementById('camModeVideoBtn');
    const sBtn = document.getElementById('camShootBtn');
    pBtn.className = 'btn btn-sm ' + (mode === 'photo' ? 'btn-light' : 'btn-outline-light');
    pBtn.style.cssText = 'border-radius:20px;min-width:72px';
    vBtn.className = 'btn btn-sm ' + (mode === 'video' ? 'btn-light' : 'btn-outline-light');
    vBtn.style.cssText = 'border-radius:20px;min-width:72px';
    sBtn.style.background = mode === 'video' ? '#dc3545' : '#fff';
    sBtn.style.borderColor = mode === 'video' ? '#dc3545' : '#aaa';
    sBtn.textContent = mode === 'video' ? '⏺' : '⬤';
}

function camShoot() {
    if (_camMode === 'photo') {
        _capturePhoto();
    } else {
        _camRecorder && _camRecorder.state === 'recording' ? _stopVideoRec() : _startVideoRec();
    }
}

function _capturePhoto() {
    const v = document.getElementById('camVideo');
    const c = document.createElement('canvas');
    c.width  = v.videoWidth;
    c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    c.toBlob(blob => {
        _camBlob = blob;
        document.getElementById('camPhotoPreview').src = URL.createObjectURL(blob);
        document.getElementById('camPhotoPreview').style.display  = '';
        document.getElementById('camVideoPreview').style.display  = 'none';
        document.getElementById('camLiveView').style.display      = 'none';
        document.getElementById('camPreviewView').style.display   = '';
        document.getElementById('camLiveControls').style.display  = 'none';
        document.getElementById('camCaptureControls').style.display = '';
        if (_camStream) _camStream.getTracks().forEach(t => t.stop());
    }, 'image/jpeg', 0.92);
}

function _startVideoRec() {
    _camChunks = []; _camSeconds = 0;

    // Restart stream with audio for recording
    const deviceId = document.getElementById('cameraSelect').value || null;
    const constraints = {
        video: deviceId ? { deviceId: { exact: deviceId } } : true,
        audio: true
    };
    navigator.mediaDevices.getUserMedia(constraints).then(stream => {
        if (_camStream) _camStream.getTracks().forEach(t => t.stop());
        _camStream = stream;
        document.getElementById('camVideo').srcObject = stream;

        // Pick best supported MIME (prefer mp4 for Meta compatibility)
        const mime = ['video/mp4', 'video/webm;codecs=vp9,opus', 'video/webm'].find(m => MediaRecorder.isTypeSupported(m)) || '';
        _camRecorder = new MediaRecorder(stream, mime ? { mimeType: mime } : {});
        _camRecorder.ondataavailable = e => { if (e.data.size > 0) _camChunks.push(e.data); };
        _camRecorder.onstop = _onVideoRecStop;
        _camRecorder.start(200);

        const timer = document.getElementById('camRecTimer');
        timer.style.display = '';
        document.getElementById('camShootBtn').textContent  = '⏹';
        document.getElementById('camShootBtn').style.background = '#dc3545';

        _camTimerInterval = setInterval(() => {
            _camSeconds++;
            const m = String(Math.floor(_camSeconds / 60)).padStart(2, '0');
            const s = String(_camSeconds % 60).padStart(2, '0');
            timer.textContent = `⏺ ${m}:${s}`;
            if (_camSeconds >= 60) _stopVideoRec();
        }, 1000);
    }).catch(() => toastr.error('{{ __("Could not start video recording.") }}'));
}

function _stopVideoRec() {
    clearInterval(_camTimerInterval);
    document.getElementById('camRecTimer').style.display = 'none';
    if (_camRecorder && _camRecorder.state === 'recording') _camRecorder.stop();
}

function _onVideoRecStop() {
    const mime = _camRecorder.mimeType || 'video/webm';
    _camBlob = new Blob(_camChunks, { type: mime });
    const vp = document.getElementById('camVideoPreview');
    vp.src = URL.createObjectURL(_camBlob);
    vp.style.display = '';
    document.getElementById('camPhotoPreview').style.display    = 'none';
    document.getElementById('camLiveView').style.display        = 'none';
    document.getElementById('camPreviewView').style.display     = '';
    document.getElementById('camLiveControls').style.display    = 'none';
    document.getElementById('camCaptureControls').style.display = '';
}

function camRetake() {
    _camBlob = null;
    document.getElementById('camLiveView').style.display        = '';
    document.getElementById('camPreviewView').style.display     = 'none';
    document.getElementById('camLiveControls').style.display    = '';
    document.getElementById('camCaptureControls').style.display = 'none';
    document.getElementById('camRecTimer').style.display        = 'none';
    const deviceId = document.getElementById('cameraSelect').value || null;
    _startCamStream(deviceId);
}

function camSend() {
    if (!_camBlob) return;
    const isVideo = _camMode === 'video';
    const ext  = isVideo ? (_camBlob.type.includes('mp4') ? 'mp4' : 'webm') : 'jpg';
    const file = new File([_camBlob], `camera-${Date.now()}.${ext}`, { type: _camBlob.type });
    uploadFileAndSend(file, isVideo ? 'video' : 'image');
    closeCamera();
}

function closeCamera() {
    if (_camStream)   { _camStream.getTracks().forEach(t => t.stop()); _camStream = null; }
    if (_camRecorder && _camRecorder.state === 'recording') _camRecorder.stop();
    clearInterval(_camTimerInterval);
    // Detach srcObject so browser releases camera LED
    const v = document.getElementById('camVideo');
    if (v) { v.srcObject = null; }
    const modal = bootstrap.Modal.getInstance(document.getElementById('cameraModal'));
    if (modal) modal.hide();
}

// ── Attachment bottom-sheet helpers ────────────────────────────
let _mediaType = 'image';

function openMediaModal(type, title, accept, capture) {
    _mediaType = type;
    document.getElementById('mediaModalLabel').textContent = title;
    const input = document.getElementById('mediaFileInput');
    input.accept = accept;
    if (capture) input.setAttribute('capture', 'environment');
    else input.removeAttribute('capture');
    document.getElementById('mediaCaption').value = '';
    document.getElementById('mediaPreviewImg').style.display = 'none';
    document.getElementById('mediaPreviewImg').src = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('mediaModal')).show();
}

document.getElementById('mediaFileInput')?.addEventListener('change', function () {
    const file = this.files[0];
    const img  = document.getElementById('mediaPreviewImg');
    if (file && file.type.startsWith('image/')) {
        img.src = URL.createObjectURL(file);
        img.style.display = '';
    } else {
        img.style.display = 'none';
    }
});

function uploadAndSendMedia() {
    const fileInput = document.getElementById('mediaFileInput');
    const caption   = document.getElementById('mediaCaption').value.trim();
    if (!fileInput.files.length) { toastr.warning('{{ __("Please select a file.") }}'); return; }
    const btn = document.getElementById('mediaSendBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const fd = new FormData();
    fd.append('file', fileInput.files[0]);
    fd.append('_token', CSRF);

    fetch(UPLOAD_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.url) throw new Error(data.message ?? '{{ __("Upload failed") }}');
            return fetch(SEND_MEDIA_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ url: data.url, media_type: _mediaType, caption: caption })
            }).then(r => r.json());
        })
        .then(res => {
            if (res.error) throw new Error(res.message ?? '{{ __("Send failed") }}');
            if (res.message) { $('#messagesArea').append(buildBubble(res.message)); lastId = Math.max(lastId, res.message.id); scrollBottom(); }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mediaModal')).hide();
            toastr.success('{{ __("Media sent!") }}');
        })
        .catch(err => toastr.error(err.message))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send me-1"></i>{{ __("Send") }}'; });
}

// ── Poll ────────────────────────────────────────────────────────
function addPollOption() {
    const opts = document.querySelectorAll('.poll-opt');
    if (opts.length >= 10) { toastr.warning('{{ __("Maximum 10 options.") }}'); return; }
    const n = opts.length + 1;
    const input = document.createElement('input');
    input.type = 'text'; input.className = 'form-control form-control-sm mb-1 poll-opt';
    input.placeholder = `{{ __("Option") }} ${n}`; input.maxLength = 100;
    document.getElementById('pollOptions').appendChild(input);
}

function submitPoll() {
    const question = document.getElementById('pollQuestion').value.trim();
    const opts = [...document.querySelectorAll('.poll-opt')].map(i => i.value.trim()).filter(Boolean);
    if (!question) { toastr.warning('{{ __("Please enter a question.") }}'); return; }
    if (opts.length < 2) { toastr.warning('{{ __("Please add at least 2 options.") }}'); return; }
    const multi = document.getElementById('pollMulti').checked;
    $.ajax({
        method: 'POST', url: SEND_POLL_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data: { name: question, options: opts, countable: multi ? opts.length : 1 },
        success: function(res) {
            if (res.message) { $('#messagesArea').append(buildBubble(res.message)); lastId = Math.max(lastId, res.message.id); scrollBottom(); }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('pollModal')).hide();
            toastr.success('{{ __("Poll sent!") }}');
        },
        error: function(err) { toastr.error(err.responseJSON?.message ?? '{{ __("Send failed") }}'); }
    });
}

// ── Contact ─────────────────────────────────────────────────────
function submitContact() {
    const name  = document.getElementById('contactName').value.trim();
    const phone = document.getElementById('contactPhone').value.trim();
    if (!name || !phone) { toastr.warning('{{ __("Name and phone are required.") }}'); return; }
    $.ajax({
        method: 'POST', url: SEND_CONTACT_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data: { name, phone },
        success: function(res) {
            if (res.message) { $('#messagesArea').append(buildBubble(res.message)); lastId = Math.max(lastId, res.message.id); scrollBottom(); }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('contactModal')).hide();
            toastr.success('{{ __("Contact sent!") }}');
        },
        error: function(err) { toastr.error(err.responseJSON?.message ?? '{{ __("Send failed") }}'); }
    });
}

// ── Catalogue ───────────────────────────────────────────────────
let _cataloguesLoaded = false;

document.getElementById('catalogueModal').addEventListener('show.bs.modal', function() {
    if (_cataloguesLoaded) return;
    fetch('/{{ app()->getLocale() }}/catalogue/sync', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ device_id: null })
    }); // fire-and-forget pre-sync (silent)

    fetch('/{{ app()->getLocale() }}/catalogue?json=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.ok ? r.json() : null)
    .then(data => {
        const sel = document.getElementById('catalogSelect');
        if (!data || !data.length) {
            sel.innerHTML = '<option value="">{{ __("No catalogues — go to Catalogue page to sync") }}</option>';
            return;
        }
        sel.innerHTML = '<option value="">{{ __("Select a catalogue…") }}</option>' +
            data.map(c => `<option value="${c.id}" data-meta-id="${c.meta_catalog_id}">${c.name}</option>`).join('');
        _cataloguesLoaded = true;
    })
    .catch(() => {
        document.getElementById('catalogSelect').innerHTML = '<option value="">{{ __("Failed to load — enter ID manually") }}</option>';
        document.getElementById('catalogManualWrap').style.display = '';
    });
});

function loadCatalogueProducts(catId) {
    document.getElementById('productSelectWrap').style.display = catId ? '' : 'none';
    if (!catId) return;
    fetch(`/{{ app()->getLocale() }}/catalogue/${catId}/products-json`)
        .then(r => r.json())
        .then(products => {
            const sel = document.getElementById('productSelect');
            sel.innerHTML = '<option value="">{{ __("Entire catalogue (no specific product)") }}</option>' +
                products.map(p => `<option value="${p.retailer_id}">${p.name} — ${p.currency} ${p.price}</option>`).join('');
        });
}

function toggleManualCatalog() {
    const w = document.getElementById('catalogManualWrap');
    w.style.display = w.style.display === 'none' ? '' : 'none';
}

function submitCatalog() {
    const manual    = document.getElementById('catalogIdManual').value.trim();
    const selOpt    = document.getElementById('catalogSelect');
    const product   = document.getElementById('productSelect')?.value?.trim() || '';
    const body      = document.getElementById('catalogBody').value.trim();

    let catalogId = manual;
    if (!catalogId && selOpt.value) {
        // Use product retailer_id if selected, else the meta catalog id
        catalogId = product || selOpt.selectedOptions[0]?.dataset?.metaId || '';
    }

    if (!catalogId) { toastr.warning('{{ __("Please select a catalogue or enter a Catalog ID.") }}'); return; }

    $.ajax({
        method: 'POST', url: SEND_CATALOG_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data: { catalog_id: catalogId, body },
        success: function(res) {
            if (res.message) { $('#messagesArea').append(buildBubble(res.message)); lastId = Math.max(lastId, res.message.id); scrollBottom(); }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('catalogueModal')).hide();
            toastr.success('{{ __("Catalogue sent!") }}');
        },
        error: function(err) { toastr.error(err.responseJSON?.message ?? '{{ __("Send failed") }}'); }
    });
}

// ── Event ───────────────────────────────────────────────────────
function toggleEvCall(el) {
    document.getElementById('evCallOptions').style.display = el.checked ? '' : 'none';
}

function submitEvent() {
    const name      = document.getElementById('evName').value.trim();
    const desc      = document.getElementById('evDesc').value.trim();
    const startDate = document.getElementById('evStartDate').value;
    const startTime = document.getElementById('evStartTime').value;
    const endDate   = document.getElementById('evEndDate').value;
    const endTime   = document.getElementById('evEndTime').value;
    const location  = document.getElementById('evLocation').value.trim();
    const hasCall   = document.getElementById('evCallToggle').checked;
    const callType  = document.querySelector('input[name="evCallType"]:checked')?.value ?? 'audio';
    const approval  = document.getElementById('evCallApproval').checked;
    const url       = document.getElementById('evUrl').value.trim();

    if (!name)      { toastr.warning('{{ __("Event name is required.") }}'); return; }
    if (!startDate) { toastr.warning('{{ __("Start date is required.") }}'); return; }

    const fmtDateTime = (date, time) => {
        if (!date) return null;
        const d = new Date(date + (time ? 'T' + time : 'T00:00'));
        const opts = { day: '2-digit', month: 'short', year: 'numeric' };
        if (time) { opts.hour = '2-digit'; opts.minute = '2-digit'; }
        return d.toLocaleString('en-GB', opts);
    };

    let msg = `📅 *${name}*`;
    if (desc) msg += `\n\n${desc}`;
    msg += `\n\n🗓 *{{ __("Start") }}:* ${fmtDateTime(startDate, startTime)}`;
    if (endDate) msg += `\n🏁 *{{ __("End") }}:* ${fmtDateTime(endDate, endTime)}`;
    if (location) msg += `\n📍 ${location}`;
    if (hasCall) {
        const icon = callType === 'video' ? '🎥' : '📞';
        const label = callType === 'video' ? '{{ __("WhatsApp Video Call") }}' : '{{ __("WhatsApp Audio Call") }}';
        msg += `\n${icon} ${label}`;
        if (approval) msg += ` _({{ __("Approval Required") }})_`;
    }
    if (url) msg += `\n🔗 ${url}`;

    $.ajax({
        method: 'POST', url: SEND_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data: { message: msg },
        success: function(res) {
            if (res.message) { $('#messagesArea').append(buildBubble(res.message)); lastId = Math.max(lastId, res.message.id); scrollBottom(); }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('eventModal')).hide();
            toastr.success('{{ __("Event sent!") }}');
        },
        error: function(err) { toastr.error(err.responseJSON?.message ?? '{{ __("Send failed") }}'); }
    });
}

// ── Meeting link ────────────────────────────────────────────────
function submitMeetingLink() {
    const url   = document.getElementById('meetingUrl').value.trim();
    const label = document.getElementById('meetingLabel').value.trim() || '{{ __("Join Meeting") }}';
    const body  = document.getElementById('meetingBody').value.trim();
    if (!url) { toastr.warning('{{ __("Please enter a URL.") }}'); return; }
    $.ajax({
        method: 'POST', url: SEND_MEETING_URL,
        headers: { 'X-CSRF-TOKEN': CSRF },
        data: { url, display_text: label, body },
        success: function(res) {
            if (res.message) { $('#messagesArea').append(buildBubble(res.message)); lastId = Math.max(lastId, res.message.id); scrollBottom(); }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('meetingModal')).hide();
            toastr.success('{{ __("Meeting link sent!") }}');
        },
        error: function(err) { toastr.error(err.responseJSON?.message ?? '{{ __("Send failed") }}'); }
    });
}

// ── Quick reply picker (/  shortcut) ────────────────────────────
let _qrActive = -1;

function openQrPicker(e) {
    e.preventDefault();
    bootstrap.Dropdown.getOrCreateInstance(document.getElementById('attachBtn')).hide();
    showQrPicker('');
    document.getElementById('chatTextarea').focus();
}

function showQrPicker(query) {
    if (!QUICK_REPLIES.length) return;
    const q = query.toLowerCase();
    const matches = q
        ? QUICK_REPLIES.filter(r => r.shortcut.toLowerCase().includes(q) || r.title.toLowerCase().includes(q))
        : QUICK_REPLIES;
    if (!matches.length) { hideQrPicker(); return; }
    _qrActive = -1;
    const list = document.getElementById('qrList');
    list.innerHTML = matches.map((r, i) =>
        `<div class="qr-item" data-idx="${i}" data-body="${r.body.replace(/"/g,'&quot;')}" onclick="selectQr(this)">
            <div class="qr-shortcut">/${r.shortcut}</div>
            <div class="qr-body">${r.title} — ${r.body}</div>
        </div>`
    ).join('');
    document.getElementById('qrPicker').style.display = '';
}

function hideQrPicker() {
    document.getElementById('qrPicker').style.display = 'none';
    _qrActive = -1;
}

function selectQr(el) {
    const body = el.getAttribute('data-body');
    const ta = document.getElementById('chatTextarea');
    ta.value = body;
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
    hideQrPicker();
    ta.focus();
}

// Extend existing input/keydown handlers with / and @ shortcuts
$('#chatTextarea').on('input.shortcuts', function() {
    const val = this.value;
    if (val === '@') {
        this.value = '';
        bootstrap.Dropdown.getOrCreateInstance(document.getElementById('attachBtn')).show();
        return;
    }
    if (val.startsWith('/')) {
        showQrPicker(val.slice(1));
    } else {
        hideQrPicker();
    }
});

$('#chatTextarea').on('keydown.qr', function(e) {
    const picker = document.getElementById('qrPicker');
    if (picker.style.display === 'none') return;
    const items = picker.querySelectorAll('.qr-item');
    if (!items.length) return;
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        items[_qrActive]?.classList.remove('active');
        _qrActive = Math.min(_qrActive + 1, items.length - 1);
        items[_qrActive]?.classList.add('active');
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        items[_qrActive]?.classList.remove('active');
        _qrActive = Math.max(_qrActive - 1, 0);
        items[_qrActive]?.classList.add('active');
    } else if (e.key === 'Enter' && _qrActive >= 0) {
        e.preventDefault();
        selectQr(items[_qrActive]);
    } else if (e.key === 'Escape') {
        hideQrPicker();
    }
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

{{-- Sidebar filter functions must be available on BOTH index and show pages --}}
<script>
if (typeof filterByStatus === 'undefined') {
    function filterByStatus(status) {
        const url = new URL(window.location.href);
        if (status === '') url.searchParams.delete('conv_status');
        else url.searchParams.set('conv_status', status);
        url.searchParams.delete('sla_only');
        window.location.href = url.toString();
    }
    function filterBySla() {
        const url = new URL(window.location.href);
        url.searchParams.set('sla_only', '1');
        url.searchParams.delete('conv_status');
        url.searchParams.delete('unassigned_only');
        window.location.href = url.toString();
    }
    function filterUnassigned() {
        const url = new URL(window.location.href);
        url.searchParams.set('unassigned_only', '1');
        url.searchParams.delete('sla_only');
        window.location.href = url.toString();
    }

}
</script>

@if(isset($conversation))
<script>
(function () {
    const CONV_ID = {{ $conversation->id }};
    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content;
    const ATTACH_TPL = '{{ rtrim(route("chat.labels.attach", ["id"=>"__CID__","labelId"=>"__LID__"]), "/") }}';
    const DETACH_TPL = '{{ rtrim(route("chat.labels.detach", ["id"=>"__CID__","labelId"=>"__LID__"]), "/") }}';

    const attachedIds = new Set([{{ $conversation->labels->pluck('id')->join(',') }}]);

    // Mark already-attached checkmarks
    document.addEventListener('DOMContentLoaded', function () {
        attachedIds.forEach(id => {
            const el = document.getElementById('lbl-check-' + id);
            if (el) el.style.display = '';
        });

        // Label search / create-label area
        const searchInput = document.getElementById('labelSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                let anyVisible = false;
                document.querySelectorAll('#labelPickerList .label-picker-item').forEach(item => {
                    const match = !q || item.dataset.labelName.toLowerCase().includes(q);
                    item.style.display = match ? '' : 'none';
                    if (match) anyVisible = true;
                });
                const createArea = document.getElementById('createLabelArea');
                const preview    = document.getElementById('newLabelNamePreview');
                const hint       = document.getElementById('noLabelsHint');
                if (hint) hint.style.display = q ? 'none' : '';
                if (q) {
                    let exactMatch = false;
                    document.querySelectorAll('#labelPickerList .label-picker-item').forEach(item => {
                        if (item.dataset.labelName.toLowerCase() === q) exactMatch = true;
                    });
                    createArea.style.display = exactMatch ? 'none' : '';
                    if (preview) preview.textContent = this.value.trim();
                } else {
                    createArea.style.display = 'none';
                }
            });
        }
    });

    window.toggleLabel = function (labelId, labelName, labelColor) {
        const isAttached = attachedIds.has(labelId);
        const url = (isAttached ? DETACH_TPL : ATTACH_TPL)
            .replace('__CID__', CONV_ID).replace('__LID__', labelId);

        fetch(url, {
            method: isAttached ? 'DELETE' : 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        }).then(r => r.json()).then(data => {
            if (data.error) return;
            const check     = document.getElementById('lbl-check-' + labelId);
            const existing  = document.getElementById('conv-lbl-' + labelId);
            const container = document.getElementById('conversationLabels');
            const noMsg     = document.getElementById('noLabelsMsg');

            if (isAttached) {
                attachedIds.delete(labelId);
                if (existing) existing.remove();
                if (check) check.style.display = 'none';
                if (attachedIds.size === 0 && !document.getElementById('noLabelsMsg')) {
                    const span = document.createElement('span');
                    span.className = 'text-muted small';
                    span.id = 'noLabelsMsg';
                    span.textContent = '{{ __("No labels") }}';
                    container.appendChild(span);
                }
            } else {
                attachedIds.add(labelId);
                if (check) check.style.display = '';
                if (noMsg) noMsg.remove();
                if (!existing) {
                    const chip = document.createElement('span');
                    chip.className = 'crm-label-chip';
                    chip.id = 'conv-lbl-' + labelId;
                    chip.style.background = labelColor;
                    chip.innerHTML = labelName +
                        ' <span class="rm" title="{{ __("Remove") }}" onclick="toggleLabel(' +
                        labelId + ',\'' + labelName.replace(/'/g,"\\'") + '\',\'' + labelColor + '\')">×</span>';
                    container.appendChild(chip);
                }
            }
        });
    };

    window.createLabel = function () {
        const name  = document.getElementById('labelSearch')?.value.trim();
        const color = document.getElementById('newLabelColor')?.value || '#6c757d';
        if (!name) return;

        fetch('{{ route("chat.labels.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name, color }),
        }).then(r => r.json()).then(data => {
            if (data.error) { alert(data.message); return; }
            const lbl  = data.label;
            const list = document.getElementById('labelPickerList');
            const hint = document.getElementById('noLabelsHint');
            if (hint) hint.remove();

            const div = document.createElement('div');
            div.className = 'label-picker-item';
            div.dataset.labelId    = lbl.id;
            div.dataset.labelName  = lbl.name;
            div.dataset.labelColor = lbl.color;
            div.setAttribute('onclick', 'toggleLabel(' + lbl.id + ',\'' + lbl.name.replace(/'/g,"\\'") + '\',\'' + lbl.color + '\')');
            div.innerHTML = '<span style="width:12px;height:12px;border-radius:50%;background:' + lbl.color + ';flex-shrink:0;display:inline-block"></span>'
                + ' <span class="flex-1">' + lbl.name + '</span>'
                + ' <i class="bi bi-check2 ms-auto label-pick-check" id="lbl-check-' + lbl.id + '" style="display:none;color:' + lbl.color + '"></i>';
            list.appendChild(div);

            document.getElementById('labelSearch').value = '';
            document.getElementById('createLabelArea').style.display = 'none';

            // Also add to sidebar label filter bar dynamically
            const filterBar = document.querySelector('.label-filter-bar');
            if (filterBar) {
                const chip = document.createElement('span');
                chip.className = 'label-filter-chip';
                chip.style.setProperty('--lc', lbl.color);
                chip.textContent = lbl.name;
                chip.setAttribute('onclick', 'filterByLabel(' + lbl.id + ')');
                filterBar.appendChild(chip);
            }

            // Auto-attach the new label to current conversation
            toggleLabel(lbl.id, lbl.name, lbl.color);
        });
    };
}());
</script>
@endif

{{-- ── Note fly-in panel + drawing canvas ─────────────────────────────────── --}}

{{-- Backdrop --}}
<div id="noteFlyBackdrop" onclick="closeNoteEditor()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.38);z-index:1040;backdrop-filter:blur(2px)"></div>

{{-- Fly-in panel --}}
<div id="noteFlyIn" style="position:fixed;top:0;right:0;width:min(640px,100vw);height:100vh;
     background:var(--dnd-surface);border-left:1px solid var(--dnd-border);
     display:flex;flex-direction:column;z-index:1050;
     transform:translateX(100%);transition:transform .25s cubic-bezier(.4,0,.2,1);
     box-shadow:-8px 0 32px rgba(0,0,0,.12)">

    {{-- Header bar --}}
    <div style="display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--dnd-border);flex-shrink:0">
        <input id="noteTitleInput" type="text" placeholder="{{ __('Note title (optional)') }}"
               style="flex:1;border:none;background:transparent;font-size:15px;font-weight:600;outline:none;color:var(--dnd-text)">
        <label class="form-check form-switch mb-0 small d-flex align-items-center gap-1" style="white-space:nowrap">
            <input type="checkbox" class="form-check-input" id="noteInternalToggle" checked>
            <span class="form-check-label" style="color:var(--dnd-text-muted)">{{ __('Internal') }}</span>
        </label>
        <button onclick="exportNotePdf()" class="btn btn-sm btn-outline-secondary" title="{{ __('Export as PDF') }}">
            <i class="bi bi-file-earmark-pdf"></i>
        </button>
        <button onclick="closeNoteEditor()" class="btn btn-sm btn-outline-secondary" title="{{ __('Close') }}">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    {{-- Quill static toolbar --}}
    <div id="noteEditorToolbar" style="flex-shrink:0">
        <span class="ql-formats">
            <select class="ql-header">
                <option value="1">H1</option>
                <option value="2">H2</option>
                <option value="3">H3</option>
                <option selected></option>
            </select>
        </span>
        <span class="ql-formats">
            <button class="ql-bold"></button>
            <button class="ql-italic"></button>
            <button class="ql-underline"></button>
            <button class="ql-strike"></button>
        </span>
        <span class="ql-formats">
            <select class="ql-color"></select>
            <select class="ql-background"></select>
        </span>
        <span class="ql-formats">
            <button class="ql-list" value="ordered"></button>
            <button class="ql-list" value="bullet"></button>
        </span>
        <span class="ql-formats">
            <button class="ql-blockquote"></button>
            <button class="ql-code-block"></button>
        </span>
        <span class="ql-formats">
            <button class="ql-link"></button>
        </span>
        <span class="ql-formats">
            <button id="qlTableBtn" title="{{ __('Insert table') }}" style="width:auto;padding:0 6px">
                <i class="bi bi-table" style="font-size:14px"></i>
            </button>
        </span>
        <span class="ql-formats">
            <button class="ql-clean"></button>
        </span>
    </div>

    {{-- Media action bar --}}
    <div style="display:flex;gap:6px;padding:6px 14px;border-bottom:1px solid var(--dnd-border);flex-shrink:0;background:var(--dnd-bg)">
        <button class="btn btn-sm btn-outline-secondary" onclick="openDrawCanvas()">
            <i class="bi bi-brush me-1"></i>{{ __('Draw') }}
        </button>
        <button class="btn btn-sm btn-outline-secondary" id="voiceRecBtn" onclick="toggleVoiceRec()">
            <i class="bi bi-mic me-1"></i>{{ __('Voice') }}
        </button>
        <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer">
            <i class="bi bi-image me-1"></i>{{ __('Image') }}
            <input type="file" id="noteImageInput" accept="image/*" style="display:none" onchange="insertNoteImage(this)">
        </label>
    </div>

    {{-- Quill editor body --}}
    <div id="noteEditorBody" style="flex:1;overflow-y:auto;"></div>

    {{-- Footer --}}
    <div style="display:flex;justify-content:flex-end;gap:8px;padding:12px 16px;border-top:1px solid var(--dnd-border);flex-shrink:0">
        <button class="btn btn-sm btn-outline-secondary" onclick="closeNoteEditor()">{{ __('Cancel') }}</button>
        <button class="btn btn-sm btn-warning" onclick="saveCurrentNote()">
            <i class="bi bi-floppy me-1"></i>{{ __('Save Note') }}
        </button>
    </div>
</div>

{{-- Drawing canvas overlay --}}
<div id="drawPanel" style="display:none;position:fixed;inset:0;background:var(--dnd-surface);z-index:1060;flex-direction:column">
    <div style="display:flex;gap:8px;align-items:center;padding:10px 14px;border-bottom:1px solid var(--dnd-border);flex-shrink:0;flex-wrap:wrap">
        <input type="color" id="drawColor" value="#1a1a1a" title="{{ __('Color') }}"
               style="width:32px;height:32px;border:none;cursor:pointer;border-radius:6px;padding:0">
        <input type="range" id="drawSize" min="1" max="30" value="3" style="width:90px" title="{{ __('Brush size') }}">
        <button onclick="setDrawMode('pen')"    id="drawPenBtn"    class="btn btn-sm btn-warning">{{ __('Pen') }}</button>
        <button onclick="setDrawMode('eraser')" id="drawEraserBtn" class="btn btn-sm btn-outline-secondary">{{ __('Eraser') }}</button>
        <button onclick="clearCanvas()" class="btn btn-sm btn-outline-secondary">{{ __('Clear') }}</button>
        <div class="ms-auto d-flex gap-2">
            <button onclick="closeDrawCanvas()" class="btn btn-sm btn-outline-secondary">{{ __('Cancel') }}</button>
            <button onclick="confirmDrawing()" class="btn btn-sm btn-warning">
                <i class="bi bi-check-lg me-1"></i>{{ __('Insert Drawing') }}
            </button>
        </div>
    </div>
    <canvas id="drawCanvas" style="flex:1;cursor:crosshair;touch-action:none;background:#fff;display:block"></canvas>
</div>

{{-- Embedded note data for JS editing --}}
@if(isset($conversation))
<script>
const NOTE_UPLOAD_URL = '{{ route('chat.notes.upload', $conversation->id) }}';
const NOTE_BASE_URL   = '{{ rtrim(url(app()->getLocale() . '/chat/' . $conversation->id . '/notes'), '/') }}';
// Serialise all loaded notes so fly-in can populate without extra AJAX
@php
$_loadedNotes = $conversation->notes->map(fn($n) => [
    'id'          => $n->id,
    'title'       => $n->title,
    'note_type'   => $n->note_type ?? 'text',
    'note'        => $n->note,
    'is_internal' => $n->is_internal,
    'author'      => $n->author,
    'time'        => $n->created_at->format('H:i d M'),
])->values();
@endphp
const LOADED_NOTES = @json($_loadedNotes);
</script>
@endif

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
// ── Note fly-in globals ──────────────────────────────────────────────────
let quillEditor   = null;
let editingNoteId = null;
let voiceRecorder = null;
let voiceChunks   = [];
let drawMode      = 'pen';
let isDrawing     = false;
let drawCtx       = null;
let drawLastX     = 0, drawLastY  = 0;

// ── Quill lazy-init ──────────────────────────────────────────────────────
function initQuill() {
    if (quillEditor) return;
    quillEditor = new Quill('#noteEditorBody', {
        theme: 'snow',
        modules: {
            toolbar: { container: '#noteEditorToolbar' },
        },
        placeholder: '{{ __("Write your note here…") }}',
    });
    // Table insert button (uses Quill 2 getModule API)
    document.getElementById('qlTableBtn').addEventListener('click', function() {
        try {
            const tableModule = quillEditor.getModule('table');
            if (tableModule) {
                tableModule.insertTable(3, 3);
            } else {
                // Fallback: insert a raw HTML table via clipboard
                const tableHtml = '<table><thead><tr><th>Header 1</th><th>Header 2</th><th>Header 3</th></tr></thead><tbody><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table>';
                const range = quillEditor.getSelection(true);
                quillEditor.clipboard.dangerouslyPasteHTML(range.index, tableHtml);
            }
        } catch(e) {
            const tableHtml = '<table><thead><tr><th>Col 1</th><th>Col 2</th><th>Col 3</th></tr></thead><tbody><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table>';
            const range = quillEditor.getSelection(true);
            quillEditor.clipboard.dangerouslyPasteHTML(range.index, tableHtml);
        }
    });
}

// ── Open / Close fly-in ──────────────────────────────────────────────────
function openNoteEditor(noteId) {
    initQuill();
    editingNoteId = noteId || null;

    if (noteId) {
        // Load existing note data from LOADED_NOTES cache
        const noteData = (typeof LOADED_NOTES !== 'undefined')
            ? LOADED_NOTES.find(n => n.id === noteId) : null;
        if (noteData) {
            document.getElementById('noteTitleInput').value = noteData.title || '';
            document.getElementById('noteInternalToggle').checked = !!noteData.is_internal;
            quillEditor.root.innerHTML = noteData.note || '';
        }
    } else {
        document.getElementById('noteTitleInput').value = '';
        document.getElementById('noteInternalToggle').checked = true;
        quillEditor.setText('');
    }

    document.getElementById('noteFlyBackdrop').style.display = 'block';
    document.getElementById('noteFlyIn').style.transform = 'translateX(0)';
    setTimeout(() => quillEditor.focus(), 300);
}

function closeNoteEditor() {
    document.getElementById('noteFlyIn').style.transform = 'translateX(100%)';
    setTimeout(() => {
        document.getElementById('noteFlyBackdrop').style.display = 'none';
    }, 260);
    stopVoiceRec();
}

// ── Save note (POST new / PUT existing) ─────────────────────────────────
function detectNoteType() {
    const html = quillEditor.root.innerHTML;
    if (html.includes('<audio')) return 'voice';
    return 'text';
}

function saveCurrentNote() {
    if (!quillEditor) return;
    const html        = quillEditor.root.innerHTML;
    const plainText   = quillEditor.getText().trim();
    if (!plainText && html === '<p><br></p>') {
        toastr.warning('{{ __("Note is empty.") }}');
        return;
    }

    const method = editingNoteId ? 'PUT' : 'POST';
    const url    = editingNoteId
        ? (typeof NOTE_BASE_URL !== 'undefined' ? NOTE_BASE_URL + '/' + editingNoteId : null)
        : (typeof NOTE_URL !== 'undefined' ? NOTE_URL : null);

    if (!url) { toastr.error('{{ __("Cannot save: no conversation loaded.") }}'); return; }

    $.ajax({
        method,
        url,
        headers: { 'X-CSRF-TOKEN': (typeof CSRF !== 'undefined' ? CSRF : document.querySelector('meta[name=csrf-token]')?.content) },
        data: {
            title:       document.getElementById('noteTitleInput').value.trim(),
            note:        html,
            note_type:   detectNoteType(),
            is_internal: document.getElementById('noteInternalToggle').checked ? 1 : 0,
        },
        success(res) {
            if (res.ok) {
                if (editingNoteId) {
                    updateNoteCard(res.note);
                    // Update cache
                    if (typeof LOADED_NOTES !== 'undefined') {
                        const idx = LOADED_NOTES.findIndex(n => n.id === editingNoteId);
                        if (idx >= 0) LOADED_NOTES[idx] = res.note;
                    }
                } else {
                    prependNoteCard(res.note);
                    if (typeof LOADED_NOTES !== 'undefined') LOADED_NOTES.unshift(res.note);
                    // Also show in messages area timeline
                    if (typeof appendNoteToMessages === 'function') appendNoteToMessages(res.note);
                }
                closeNoteEditor();
                toastr.success('{{ __("Note saved.") }}');
            }
        },
        error() { toastr.error('{{ __("Failed to save note.") }}'); }
    });
}

// ── Delete note ──────────────────────────────────────────────────────────
function deleteNote(noteId) {
    if (!confirm('{{ __("Delete this note? This cannot be undone.") }}')) return;
    const url = typeof NOTE_BASE_URL !== 'undefined' ? NOTE_BASE_URL + '/' + noteId : null;
    if (!url) return;

    $.ajax({
        method: 'DELETE',
        url,
        headers: { 'X-CSRF-TOKEN': (typeof CSRF !== 'undefined' ? CSRF : document.querySelector('meta[name=csrf-token]')?.content) },
        success(res) {
            if (res.ok) {
                const card = document.querySelector(`.note-card[data-id="${noteId}"]`);
                if (card) card.remove();
                if (typeof LOADED_NOTES !== 'undefined') {
                    const idx = LOADED_NOTES.findIndex(n => n.id === noteId);
                    if (idx >= 0) LOADED_NOTES.splice(idx, 1);
                }
                if (!document.querySelector('#notesContainer .note-card')) {
                    document.getElementById('notesContainer').innerHTML =
                        '<div class="text-muted small" id="noNotesMsg">{{ __("No notes yet.") }}</div>';
                }
                toastr.success('{{ __("Note deleted.") }}');
            }
        },
        error() { toastr.error('{{ __("Failed to delete note.") }}'); }
    });
}

// ── Note card DOM helpers ────────────────────────────────────────────────
function noteCardHtml(note) {
    const iconMap = { voice: 'bi-mic-fill', drawing: 'bi-brush-fill' };
    const icon    = iconMap[note.note_type] || 'bi-journal-text';
    const excerpt = (note.title || (note.note || '').replace(/<[^>]+>/g, '').substring(0, 45)) || '(empty)';
    const isInternal = note.is_internal;
    const visibleBadge = !isInternal ? '<span class="badge bg-info-subtle text-info ms-1">{{ __("Visible") }}</span>' : '';
    return `<div class="note-card ${isInternal ? '' : 'note-card-visible'}" data-id="${note.id}"
                 data-title="${(note.title||'').replace(/"/g,'&quot;')}"
                 data-type="${note.note_type||'text'}"
                 data-internal="${isInternal ? 1 : 0}">
        <div class="note-card-header">
            <i class="bi ${icon} note-type-icon"></i>
            <span class="note-card-title">${excerpt}</span>
            <div class="note-card-actions">
                <button class="btn-note-xs" onclick="openNoteEditor(${note.id})" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                <button class="btn-note-xs text-danger" onclick="deleteNote(${note.id})" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="note-card-meta">${note.author} · ${note.time} ${visibleBadge}</div>
    </div>`;
}

function prependNoteCard(note) {
    const noMsg = document.getElementById('noNotesMsg');
    if (noMsg) noMsg.remove();
    const container = document.getElementById('notesContainer');
    if (container) container.insertAdjacentHTML('afterbegin', noteCardHtml(note));
}

function updateNoteCard(note) {
    const card = document.querySelector(`.note-card[data-id="${note.id}"]`);
    if (card) {
        const newCard = document.createElement('div');
        newCard.innerHTML = noteCardHtml(note);
        card.replaceWith(newCard.firstElementChild);
    }
}

// ── PDF export ───────────────────────────────────────────────────────────
function exportNotePdf() {
    if (!editingNoteId) {
        toastr.warning('{{ __("Save the note first, then export.") }}');
        return;
    }
    const url = typeof NOTE_BASE_URL !== 'undefined' ? NOTE_BASE_URL + '/' + editingNoteId + '/print' : null;
    if (url) window.open(url, '_blank');
}

// ── Image insert ─────────────────────────────────────────────────────────
function insertNoteImage(input) {
    if (!input.files || !input.files[0]) return;
    const uploadUrl = typeof NOTE_UPLOAD_URL !== 'undefined' ? NOTE_UPLOAD_URL : null;
    if (!uploadUrl) { toastr.error('{{ __("No upload URL — open a conversation first.") }}'); return; }

    const fd = new FormData();
    fd.append('file', input.files[0]);
    fd.append('_token', typeof CSRF !== 'undefined' ? CSRF : '');

    toastr.info('{{ __("Uploading…") }}');
    $.ajax({
        method: 'POST', url: uploadUrl,
        data: fd, processData: false, contentType: false,
        success(res) {
            if (res.url && quillEditor) {
                const range = quillEditor.getSelection(true);
                quillEditor.insertEmbed(range ? range.index : 0, 'image', res.url, 'user');
            }
        },
        error() { toastr.error('{{ __("Image upload failed.") }}'); }
    });
    input.value = '';
}

// ── Voice recording ──────────────────────────────────────────────────────
function toggleVoiceRec() {
    if (voiceRecorder && voiceRecorder.state === 'recording') {
        voiceRecorder.stop();
        return;
    }
    if (!navigator.mediaDevices?.getUserMedia) {
        toastr.error('{{ __("Microphone not supported in this browser.") }}'); return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        voiceChunks = [];
        voiceRecorder = new MediaRecorder(stream);
        voiceRecorder.ondataavailable = e => { if (e.data.size) voiceChunks.push(e.data); };
        voiceRecorder.onstop = () => {
            stream.getTracks().forEach(t => t.stop());
            const blob = new Blob(voiceChunks, { type: 'audio/webm' });
            uploadVoiceBlob(blob);
            const btn = document.getElementById('voiceRecBtn');
            btn.innerHTML = '<i class="bi bi-mic me-1"></i>{{ __("Voice") }}';
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-outline-secondary');
        };
        voiceRecorder.start();
        const btn = document.getElementById('voiceRecBtn');
        btn.innerHTML = '<i class="bi bi-stop-circle me-1"></i>{{ __("Stop") }}';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-danger');
    }).catch(e => {
        if (e.name === 'NotAllowedError') toastr.error('{{ __("Microphone permission denied.") }}');
        else toastr.error(e.message || '{{ __("Could not access microphone.") }}');
    });
}

function stopVoiceRec() {
    if (voiceRecorder && voiceRecorder.state === 'recording') voiceRecorder.stop();
}

function uploadVoiceBlob(blob) {
    const uploadUrl = typeof NOTE_UPLOAD_URL !== 'undefined' ? NOTE_UPLOAD_URL : null;
    if (!uploadUrl) return;
    const fd = new FormData();
    fd.append('file', blob, 'voice-memo-' + Date.now() + '.webm');
    fd.append('_token', typeof CSRF !== 'undefined' ? CSRF : '');
    toastr.info('{{ __("Saving voice memo…") }}');
    $.ajax({
        method: 'POST', url: uploadUrl,
        data: fd, processData: false, contentType: false,
        success(res) {
            if (res.url && quillEditor) {
                const range = quillEditor.getSelection(true);
                const audioHtml = `<p><audio controls src="${res.url}" style="max-width:100%"></audio></p><p><br></p>`;
                quillEditor.clipboard.dangerouslyPasteHTML(range ? range.index : quillEditor.getLength(), audioHtml);
            }
        },
        error() { toastr.error('{{ __("Voice upload failed.") }}'); }
    });
}

// ── Drawing canvas ───────────────────────────────────────────────────────
function openDrawCanvas() {
    const panel  = document.getElementById('drawPanel');
    const canvas = document.getElementById('drawCanvas');
    panel.style.display = 'flex';
    // Size canvas to the available draw area
    requestAnimationFrame(() => {
        canvas.width  = canvas.offsetWidth  || window.innerWidth;
        canvas.height = canvas.offsetHeight || window.innerHeight - 60;
        drawCtx = canvas.getContext('2d');
        drawCtx.lineCap    = 'round';
        drawCtx.lineJoin   = 'round';
        drawCtx.strokeStyle = document.getElementById('drawColor').value;
        drawCtx.lineWidth   = parseInt(document.getElementById('drawSize').value);
    });
    setDrawMode('pen');
}

function closeDrawCanvas() {
    document.getElementById('drawPanel').style.display = 'none';
}

function setDrawMode(mode) {
    drawMode = mode;
    document.getElementById('drawPenBtn').className    = 'btn btn-sm ' + (mode === 'pen'    ? 'btn-warning' : 'btn-outline-secondary');
    document.getElementById('drawEraserBtn').className = 'btn btn-sm ' + (mode === 'eraser' ? 'btn-warning' : 'btn-outline-secondary');
    document.getElementById('drawCanvas').style.cursor = mode === 'eraser' ? 'cell' : 'crosshair';
}

function clearCanvas() {
    if (drawCtx) drawCtx.clearRect(0, 0, drawCtx.canvas.width, drawCtx.canvas.height);
}

function confirmDrawing() {
    const canvas = document.getElementById('drawCanvas');
    canvas.toBlob(blob => {
        const uploadUrl = typeof NOTE_UPLOAD_URL !== 'undefined' ? NOTE_UPLOAD_URL : null;
        if (!uploadUrl) { closeDrawCanvas(); return; }
        const fd = new FormData();
        fd.append('file', blob, 'drawing-' + Date.now() + '.png');
        fd.append('_token', typeof CSRF !== 'undefined' ? CSRF : '');
        $.ajax({
            method: 'POST', url: uploadUrl,
            data: fd, processData: false, contentType: false,
            success(res) {
                if (res.url && quillEditor) {
                    const range = quillEditor.getSelection(true);
                    quillEditor.insertEmbed(range ? range.index : 0, 'image', res.url, 'user');
                }
                closeDrawCanvas();
            },
            error() { toastr.error('{{ __("Drawing upload failed.") }}'); closeDrawCanvas(); }
        });
    }, 'image/png');
}

// Canvas drawing event handlers
(function() {
    function getPos(e, canvas) {
        const rect = canvas.getBoundingClientRect();
        const src  = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }
    function startDraw(e) {
        isDrawing = true;
        const canvas = document.getElementById('drawCanvas');
        if (!drawCtx) return;
        const p = getPos(e, canvas);
        drawLastX = p.x; drawLastY = p.y;
        drawCtx.beginPath();
        drawCtx.moveTo(p.x, p.y);
        e.preventDefault();
    }
    function doDraw(e) {
        if (!isDrawing || !drawCtx) return;
        const canvas = document.getElementById('drawCanvas');
        const p = getPos(e, canvas);
        drawCtx.strokeStyle = drawMode === 'eraser' ? '#ffffff' : document.getElementById('drawColor').value;
        drawCtx.lineWidth   = parseInt(document.getElementById('drawSize').value) * (drawMode === 'eraser' ? 4 : 1);
        drawCtx.lineTo(p.x, p.y);
        drawCtx.stroke();
        drawLastX = p.x; drawLastY = p.y;
        e.preventDefault();
    }
    function endDraw(e) {
        isDrawing = false;
        if (drawCtx) { drawCtx.beginPath(); }
        e.preventDefault();
    }
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('drawCanvas');
        if (!canvas) return;
        canvas.addEventListener('mousedown',  startDraw);
        canvas.addEventListener('mousemove',  doDraw);
        canvas.addEventListener('mouseup',    endDraw);
        canvas.addEventListener('mouseleave', endDraw);
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove',  doDraw,    { passive: false });
        canvas.addEventListener('touchend',   endDraw,   { passive: false });
    });
})();

// ── Socket listeners for notes from other agents ─────────────────────────
if (typeof socket !== 'undefined') {
    socket.on('updated_note', function(note) { updateNoteCard(note); });
    socket.on('deleted_note', function(data) {
        const card = document.querySelector(`.note-card[data-id="${data.id}"]`);
        if (card) card.remove();
    });
}
</script>

</x-layout-dashboard>
