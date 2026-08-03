<x-layout-dashboard title="{{ isset($flow) ? __('Edit Flow') . ': ' . $flow->name : __('New Flow') }}">

{{-- Drawflow CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/drawflow/dist/drawflow.min.css">

<style>
/* ── Editor shell ─────────────────────────────────────────────── */
.flow-editor-wrap    { max-width:1600px; margin:0 auto; padding:0 16px 16px; }

/* Top bar — two rows so it never wraps */
.flow-topbar         { display:flex; flex-direction:column; gap:10px; padding:14px 16px 12px; background:#fff; border-bottom:1px solid #e2e8f0; }
.flow-topbar-row1    { display:flex; align-items:center; gap:10px; }
.flow-topbar-row2    { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.flow-back-btn       { width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e2e8f0; border-radius:7px; color:#64748b; text-decoration:none; transition:background 150ms, border-color 150ms; flex-shrink:0; }
.flow-back-btn:hover { background:#f8fafc; border-color:#94a3b8; color:#334155; }
.flow-name-input     { font-size:0.9rem; font-weight:700; color:#0f172a; border:1px solid transparent; background:transparent; border-radius:6px; padding:4px 8px; transition:border-color 150ms, background 150ms; flex:1; min-width:140px; max-width:280px; }
.flow-name-input:hover { border-color:#e2e8f0; background:#f8fafc; }
.flow-name-input:focus { border-color:#6366f1; background:#fff; outline:none; }
.topbar-label        { font-size:0.7rem; font-weight:600; color:#94a3b8; letter-spacing:.05em; text-transform:uppercase; white-space:nowrap; }
.topbar-select       { font-size:0.8rem; border:1px solid #e2e8f0; border-radius:7px; padding:5px 8px; color:#334155; background:#fff; min-width:120px; transition:border-color 150ms; }
.topbar-select:focus { border-color:#6366f1; outline:none; }
.topbar-input        { font-size:0.8rem; border:1px solid #e2e8f0; border-radius:7px; padding:5px 8px; color:#334155; background:#fff; min-width:100px; transition:border-color 150ms; }
.topbar-input:focus  { border-color:#6366f1; outline:none; }
.topbar-divider      { width:1px; height:22px; background:#e2e8f0; flex-shrink:0; }
.btn-save            { background:#0f172a; color:#fff; border:none; border-radius:7px; padding:7px 16px; font-size:0.8rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:background 150ms, transform 120ms; }
.btn-save:hover      { background:#1e293b; transform:translateY(-1px); }
.btn-save:active     { transform:scale(.98); }
.btn-save.saved      { background:#16a34a; }
.btn-toggle-status   { border:1px solid; border-radius:7px; padding:6px 13px; font-size:0.8rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:background 150ms; }
.btn-toggle-status.is-active { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.btn-toggle-status.is-draft  { background:#f8fafc; border-color:#e2e8f0; color:#64748b; }

/* Editor layout */
.flow-editor-shell   { display:flex; height:calc(100vh - 175px); min-height:460px; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
.flow-sidebar        { width:210px; flex-shrink:0; background:#fff; border-right:1px solid #e2e8f0; overflow-y:auto; display:flex; flex-direction:column; }
.flow-canvas         { flex:1; position:relative; overflow:hidden; }
.flow-config-panel   { width:290px; flex-shrink:0; background:#fff; border-left:1px solid #e2e8f0; overflow-y:auto; display:flex; flex-direction:column; padding:0; transform:translateX(100%); transition:transform 220ms cubic-bezier(.4,0,.2,1); position:absolute; right:0; top:0; height:100%; z-index:5; box-shadow:-4px 0 16px rgba(15,23,42,.06); }
.flow-config-panel.visible { transform:translateX(0); }

/* Canvas dot grid */
.flow-canvas-bg      { width:100%; height:100%; background-color:#f8fafc;
    background-image: radial-gradient(circle, #c7d2e0 1px, transparent 1px);
    background-size: 24px 24px; }
#drawflow            { position:absolute; inset:0; }

/* Node palette */
.palette-group       { padding:10px 10px 4px; }
.palette-group-label { font-size:0.68rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:#94a3b8; padding:0 4px; margin-bottom:4px; display:flex; align-items:center; gap:5px; }
.palette-group-label::after { content:''; flex:1; height:1px; background:#f1f5f9; }
.node-palette-item   { display:flex; align-items:center; gap:8px; padding:7px 8px; cursor:grab; border-radius:7px; font-size:0.8rem; font-weight:500; color:#334155; transition:background 130ms; user-select:none; }
.node-palette-item:hover    { background:#f1f5f9; }
.node-palette-item:active   { cursor:grabbing; }
.node-palette-item .ni      { width:26px; height:26px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }

/* Node icon colors */
.nc-trigger  { background:#dcfce7; color:#16a34a; }
.nc-action   { background:#dbeafe; color:#1d4ed8; }
.nc-logic    { background:#fef9c3; color:#a16207; }
.nc-control  { background:#fce7f3; color:#be185d; }

/* Drawflow node overrides */
.drawflow .drawflow-node          { min-width:190px; border-radius:10px; box-shadow:0 2px 10px rgba(15,23,42,.1); border:2px solid transparent; padding:0 !important; }
.drawflow .drawflow-node.selected { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.drawflow .drawflow-node.drawflow_content_node { padding:0 !important; }

.dflow-node-inner    { padding:10px 12px 10px 14px; border-radius:8px; }
.dflow-node-header   { display:flex; align-items:center; gap:6px; margin-bottom:5px; }
.dflow-node-title    { font-size:0.78rem; font-weight:700; color:#0f172a; letter-spacing:-.01em; }
.dflow-node-body     { font-size:0.75rem; color:#64748b; line-height:1.45; }
.dflow-node-body strong { color:#0f172a; font-weight:600; }

/* Node type left-border accent */
.df-trigger  { background:#f0fdf4 !important; border-left:3px solid #16a34a !important; }
.df-action   { background:#eff6ff !important; border-left:3px solid #3b82f6 !important; }
.df-logic    { background:#fefce8 !important; border-left:3px solid #eab308 !important; }
.df-control  { background:#fdf4ff !important; border-left:3px solid #a855f7 !important; }
.df-handoff  { background:#fff1f2 !important; border-left:3px solid #f43f5e !important; }

/* Condition output labels */
.df-output-labels { display:flex; justify-content:space-between; font-size:0.68rem; font-weight:600; color:#94a3b8; margin-top:6px; padding:0 2px; }
.df-output-labels .yes { color:#16a34a; }
.df-output-labels .no  { color:#ef4444; }

/* Canvas toolbar */
.flow-toolbar        { position:absolute; top:12px; left:12px; z-index:10; display:flex; gap:5px; }
.flow-toolbar-btn    { width:32px; height:32px; background:#fff; border:1px solid #e2e8f0; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; color:#475569; transition:background 140ms, border-color 140ms; font-size:0; }
.flow-toolbar-btn i  { font-size:16px; }
.flow-toolbar-btn:hover { background:#f8fafc; border-color:#94a3b8; }
.flow-help-hint      { position:absolute; bottom:12px; left:12px; font-size:0.7rem; color:#94a3b8; background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:4px 8px; pointer-events:none; }

/* Config panel */
.config-panel-header { padding:14px 16px 10px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; gap:8px; flex-shrink:0; }
.config-panel-title  { font-size:0.85rem; font-weight:700; color:#0f172a; margin:0; }
.config-close-btn    { background:none; border:none; color:#94a3b8; cursor:pointer; padding:2px; line-height:1; transition:color 150ms; }
.config-close-btn:hover { color:#334155; }
.config-panel-body   { padding:14px 16px; overflow-y:auto; flex:1; }
.config-label        { font-size:0.75rem; font-weight:600; color:#475569; margin-bottom:4px; display:block; }
.config-panel-body .form-control,
.config-panel-body .form-select { font-size:0.82rem; border-color:#e2e8f0; }
.config-panel-body .form-control:focus,
.config-panel-body .form-select:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }

/* Fallback settings panel */
.flow-settings-bar   { position:absolute; bottom:12px; right:12px; z-index:10; }
.flow-settings-btn   { background:#fff; border:1px solid #e2e8f0; border-radius:7px; padding:5px 10px; font-size:0.75rem; color:#64748b; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:background 140ms; }
.flow-settings-btn:hover { background:#f8fafc; color:#334155; }
.flow-settings-panel { position:absolute; bottom:36px; right:0; width:280px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px; box-shadow:0 8px 24px rgba(15,23,42,.1); display:none; }
.flow-settings-panel.open { display:block; }
</style>

<div class="flow-editor-wrap">

    {{-- Two-row top bar --}}
    <div class="flow-topbar">
        <div class="flow-topbar-row1">
            <a href="{{ route('flows.index') }}" class="flow-back-btn" title="{{ __('Back to flows') }}">
                <i class="bi bi-arrow-left" style="font-size:16px"></i>
            </a>
            <input type="text" id="flow-name" class="flow-name-input"
                   placeholder="{{ __('Untitled flow') }}"
                   value="{{ $flow->name ?? '' }}">
            <div class="topbar-divider d-none d-md-block"></div>
            <span class="topbar-label d-none d-md-inline">{{ __('Device') }}</span>
            <select id="flow-device" class="topbar-select">
                <option value="">{{ __('Select device') }}</option>
                @foreach($devices as $d)
                    <option value="{{ $d->id }}" {{ (isset($flow) && $flow->device_id == $d->id) ? 'selected' : '' }}>
                        {{ $d->meta_profile['verified_name'] ?? $d->body }}
                    </option>
                @endforeach
            </select>
            <div class="ms-auto d-flex gap-2 align-items-center">
                @isset($flow)
                <button id="btn-toggle" class="btn-toggle-status {{ $flow->status === 'active' ? 'is-active' : 'is-draft' }}" data-status="{{ $flow->status }}">
                    <i class="bi {{ $flow->status === 'active' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
                    {{ $flow->status === 'active' ? __('Active') : __('Draft') }}
                </button>
                @endisset
                <button id="btn-save" class="btn-save">
                    <i class="bi bi-floppy-fill"></i> {{ __('Save') }}
                </button>
            </div>
        </div>
        <div class="flow-topbar-row2">
            <span class="topbar-label">{{ __('Trigger') }}</span>
            <select id="flow-trigger-type" class="topbar-select" style="min-width:140px">
                <option value="keyword"  {{ (isset($flow) && $flow->trigger_type === 'keyword')  ? 'selected' : '' }}>{{ __('Keyword match') }}</option>
                <option value="all"      {{ (isset($flow) && $flow->trigger_type === 'all')      ? 'selected' : '' }}>{{ __('All messages') }}</option>
                <option value="referral" {{ (isset($flow) && $flow->trigger_type === 'referral') ? 'selected' : '' }}>{{ __('Ad click (referral)') }}</option>
                <option value="api"      {{ (isset($flow) && $flow->trigger_type === 'api')      ? 'selected' : '' }}>{{ __('API webhook') }}</option>
            </select>
            <input type="text" id="flow-trigger-value" class="topbar-input"
                   placeholder="{{ __('keyword...') }}"
                   value="{{ $flow->trigger_value ?? '' }}" style="min-width:130px">
            <select id="flow-trigger-match" class="topbar-select" style="min-width:120px">
                <option value="contains"    {{ (isset($flow) && $flow->trigger_match === 'contains')    ? 'selected' : '' }}>{{ __('Contains') }}</option>
                <option value="exact"       {{ (isset($flow) && $flow->trigger_match === 'exact')       ? 'selected' : '' }}>{{ __('Exact match') }}</option>
                <option value="starts_with" {{ (isset($flow) && $flow->trigger_match === 'starts_with') ? 'selected' : '' }}>{{ __('Starts with') }}</option>
            </select>
            <div class="topbar-divider"></div>
            <span class="topbar-label">{{ __('Fallback') }}</span>
            <input type="text" id="flow-fallback-msg" class="topbar-input" style="min-width:200px;flex:1;max-width:340px"
                   placeholder="{{ __('Message when bot cannot understand...') }}"
                   value="{{ $flow->fallback_message ?? '' }}">
            <span class="topbar-label" style="color:#94a3b8;font-size:0.68rem">
                <i class="bi bi-info-circle" style="font-size:11px;vertical-align:-1px"></i>
                {{ __('Sent after 2 failed matches. Escalates to human after 3.') }}
            </span>
        </div>
    </div>

    {{-- Editor shell --}}
    <div class="flow-editor-shell">

        {{-- Node Palette --}}
        <div class="flow-sidebar">
            <div class="palette-group">
                <div class="palette-group-label">{{ __('Triggers') }}</div>
                <div class="node-palette-item" draggable="true" data-node="trigger_keyword">
                    <div class="ni nc-trigger"><i class="bi bi-lightning-fill"></i></div> {{ __('Keyword') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="trigger_all">
                    <div class="ni nc-trigger"><i class="bi bi-infinity"></i></div> {{ __('All messages') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="trigger_referral">
                    <div class="ni nc-trigger"><i class="bi bi-cursor-fill"></i></div> {{ __('Ad click') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="trigger_api">
                    <div class="ni nc-trigger"><i class="bi bi-link-45deg"></i></div> {{ __('API webhook') }}
                </div>
            </div>

            <div class="palette-group">
                <div class="palette-group-label">{{ __('Actions') }}</div>
                <div class="node-palette-item" draggable="true" data-node="send_text">
                    <div class="ni nc-action"><i class="bi bi-chat-fill"></i></div> {{ __('Send text') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="send_image">
                    <div class="ni nc-action"><i class="bi bi-image-fill"></i></div> {{ __('Send image') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="send_buttons">
                    <div class="ni nc-action"><i class="bi bi-hand-index-fill"></i></div> {{ __('Send buttons') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="send_template">
                    <div class="ni nc-action"><i class="bi bi-file-text-fill"></i></div> {{ __('Send template') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="ask_input">
                    <div class="ni nc-action"><i class="bi bi-keyboard-fill"></i></div> {{ __('Ask for input') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="api_call">
                    <div class="ni nc-action"><i class="bi bi-globe2"></i></div> {{ __('API call') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="add_tag">
                    <div class="ni nc-action"><i class="bi bi-tag-fill"></i></div> {{ __('Add tag') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="remove_tag">
                    <div class="ni nc-action"><i class="bi bi-tag"></i></div> {{ __('Remove tag') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="send_catalog">
                    <div class="ni nc-action"><i class="bi bi-bag-fill"></i></div> {{ __('Send catalog') }}
                </div>
            </div>

            <div class="palette-group">
                <div class="palette-group-label">{{ __('Logic') }}</div>
                <div class="node-palette-item" draggable="true" data-node="condition">
                    <div class="ni nc-logic"><i class="bi bi-signpost-split-fill"></i></div> {{ __('Condition') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="random_split">
                    <div class="ni nc-logic"><i class="bi bi-shuffle"></i></div> {{ __('Random split') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="delay">
                    <div class="ni nc-logic"><i class="bi bi-clock-fill"></i></div> {{ __('Delay') }}
                </div>
            </div>

            <div class="palette-group">
                <div class="palette-group-label">{{ __('Control') }}</div>
                <div class="node-palette-item" draggable="true" data-node="human_handoff">
                    <div class="ni nc-control"><i class="bi bi-headset"></i></div> {{ __('Human handoff') }}
                </div>
                <div class="node-palette-item" draggable="true" data-node="end_flow">
                    <div class="ni nc-control"><i class="bi bi-stop-circle-fill"></i></div> {{ __('End flow') }}
                </div>
            </div>

            <div class="mt-auto p-2 border-top">
                <div style="font-size:0.68rem;color:#94a3b8;padding:4px 6px;line-height:1.5">
                    <strong style="color:#64748b;display:block;margin-bottom:3px">{{ __('Tips') }}</strong>
                    {{ __('Drag nodes onto canvas') }}<br>
                    {{ __('Click node to configure') }}<br>
                    <kbd style="font-size:0.65rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:3px;padding:1px 4px">Del</kbd> {{ __('delete selected') }} ·
                    <kbd style="font-size:0.65rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:3px;padding:1px 4px">Ctrl+S</kbd> {{ __('save') }}
                </div>
            </div>
        </div>

        {{-- Canvas --}}
        <div class="flow-canvas" id="canvas-wrap">
            <div class="flow-canvas-bg"></div>
            <div class="flow-toolbar">
                <button class="flow-toolbar-btn" id="btn-zoom-in" title="{{ __('Zoom in') }}"><i class="bi bi-zoom-in"></i></button>
                <button class="flow-toolbar-btn" id="btn-zoom-out" title="{{ __('Zoom out') }}"><i class="bi bi-zoom-out"></i></button>
                <button class="flow-toolbar-btn" id="btn-zoom-reset" title="{{ __('Fit to screen') }}"><i class="bi bi-arrows-fullscreen"></i></button>
                <button class="flow-toolbar-btn" id="btn-clear" title="{{ __('Clear canvas') }}" style="color:#ef4444"><i class="bi bi-trash-fill"></i></button>
            </div>
            <div id="drawflow"></div>
            <div class="flow-help-hint">
                <i class="bi bi-mouse" style="font-size:11px;vertical-align:-1px"></i>
                {{ __('Scroll to zoom · Drag to pan') }}
            </div>
        </div>

        {{-- Config Panel (slides in from right) --}}
        <div class="flow-config-panel" id="config-panel">
            <div class="config-panel-header">
                <span class="config-panel-title" id="config-panel-title">{{ __('Node settings') }}</span>
                <button class="config-close-btn" id="config-panel-close" title="{{ __('Close') }}">
                    <i class="bi bi-x-lg" style="font-size:16px"></i>
                </button>
            </div>
            <div class="config-panel-body" id="config-panel-body">
                <p class="text-muted small">{{ __('Click a node on the canvas to configure it.') }}</p>
            </div>
        </div>
    </div>
</div>{{-- end flow-editor-wrap --}}

<script src="https://cdn.jsdelivr.net/npm/drawflow/dist/drawflow.min.js"></script>
<script>
(function () {
'use strict';

const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
const FLOW_ID    = {{ isset($flow) ? $flow->id : 'null' }};
const STORE_URL  = "{{ route('flows.store') }}";
const UPDATE_URL = "{{ isset($flow) ? route('flows.update', $flow->id) : '' }}";
const TOGGLE_URL = "{{ isset($flow) ? route('flows.toggle', $flow->id) : '' }}";
const TEMPLATES  = @json($templates);

// ── Node definitions ─────────────────────────────────────────────
const NODE_DEFS = {
    trigger_keyword: {
        label: '{{ __('Keyword trigger') }}', cat: 'trigger', icon: 'bi-lightning-fill',
        inputs: 0, outputs: 1,
        defaultData: { keyword: '', match_type: 'contains' },
        preview: d => `<strong>${d.keyword || 'keyword...'}</strong>`,
    },
    trigger_all: {
        label: 'All Messages', cat: 'trigger', icon: 'bi-infinity',
        inputs: 0, outputs: 1,
        defaultData: {},
        preview: () => 'Catches every message',
    },
    trigger_referral: {
        label: 'Ad Click', cat: 'trigger', icon: 'bi-cursor-fill',
        inputs: 0, outputs: 1,
        defaultData: { ref_id: '' },
        preview: d => `Ref: <strong>${d.ref_id || '—'}</strong>`,
    },
    trigger_api: {
        label: 'API Webhook', cat: 'trigger', icon: 'bi-link-45deg',
        inputs: 0, outputs: 1,
        defaultData: {},
        preview: () => 'Triggered via API endpoint',
    },
    send_text: {
        label: 'Send Text', cat: 'action', icon: 'bi-chat-fill',
        inputs: 1, outputs: 1,
        defaultData: { message: '' },
        preview: d => d.message ? `"${d.message.slice(0,40)}"` : '<em>empty message</em>',
    },
    send_image: {
        label: 'Send Image', cat: 'action', icon: 'bi-image-fill',
        inputs: 1, outputs: 1,
        defaultData: { url: '', caption: '' },
        preview: d => d.url ? `<strong>Image</strong>: ${d.caption || ''}` : '<em>no URL</em>',
    },
    send_buttons: {
        label: 'Send Buttons', cat: 'action', icon: 'bi-hand-index-fill',
        inputs: 1, outputs: 1,
        defaultData: { message: '', buttons: '' },
        preview: d => d.message ? `"${d.message.slice(0,30)}"` : '<em>empty</em>',
    },
    send_template: {
        label: 'Send Template', cat: 'action', icon: 'bi-file-text-fill',
        inputs: 1, outputs: 1,
        defaultData: { template_id: '' },
        preview: d => {
            const t = TEMPLATES.find(t => t.id == d.template_id);
            return t ? `<strong>${t.name}</strong>` : '<em>no template</em>';
        },
    },
    ask_input: {
        label: 'Ask for Input', cat: 'action', icon: 'bi-keyboard-fill',
        inputs: 1, outputs: 1,
        defaultData: { question: '', variable: 'user_input', validation: 'none' },
        preview: d => d.question ? `"${d.question.slice(0,35)}"` : '<em>empty question</em>',
    },
    api_call: {
        label: 'API Call', cat: 'action', icon: 'bi-globe2',
        inputs: 1, outputs: 2,
        defaultData: { method: 'POST', url: '', body: '', save_as: 'api_response' },
        preview: d => `<strong>${d.method||'POST'}</strong> ${d.url ? d.url.slice(0,28) : '<em>no URL</em>'}`,
        outputLabels: ['Success', 'Error'],
    },
    add_tag: {
        label: 'Add Tag', cat: 'action', icon: 'bi-tag-fill',
        inputs: 1, outputs: 1,
        defaultData: { tag: '' },
        preview: d => d.tag ? `+<strong>${d.tag}</strong>` : '<em>no tag set</em>',
    },
    remove_tag: {
        label: 'Remove Tag', cat: 'action', icon: 'bi-tag',
        inputs: 1, outputs: 1,
        defaultData: { tag: '' },
        preview: d => d.tag ? `−<strong>${d.tag}</strong>` : '<em>no tag set</em>',
    },
    send_catalog: {
        label: 'Send Catalog', cat: 'action', icon: 'bi-bag-fill',
        inputs: 1, outputs: 1,
        defaultData: { catalog_id: '', body: '', footer: '' },
        preview: d => d.catalog_id ? `Catalog: <strong>${d.catalog_id}</strong>` : '<em>no catalog ID</em>',
    },
    condition: {
        label: 'Condition (IF)', cat: 'logic', icon: 'bi-signpost-split-fill',
        inputs: 1, outputs: 2,   // output_1 = YES, output_2 = NO
        defaultData: { variable: '', operator: 'contains', value: '' },
        preview: d => `<strong>${d.variable||'?'}</strong> ${d.operator} "${d.value||''}"`,
        outputLabels: ['Yes', 'No'],
    },
    random_split: {
        label: 'Random Split', cat: 'logic', icon: 'bi-shuffle',
        inputs: 1, outputs: 2,
        defaultData: { weight_a: 50 },
        preview: d => `A: <strong>${d.weight_a||50}%</strong> · B: <strong>${100-(d.weight_a||50)}%</strong>`,
        outputLabels: ['Branch A', 'Branch B'],
    },
    delay: {
        label: 'Delay', cat: 'logic', icon: 'bi-clock-fill',
        inputs: 1, outputs: 1,
        defaultData: { seconds: 2 },
        preview: d => `Wait <strong>${d.seconds || 2}s</strong>`,
    },
    human_handoff: {
        label: 'Human Handoff', cat: 'control', icon: 'bi-headset',
        inputs: 1, outputs: 0,
        defaultData: { message: 'Connecting you with a human agent...' },
        preview: d => d.message ? `"${d.message.slice(0,35)}"` : '',
    },
    end_flow: {
        label: 'End Flow', cat: 'control', icon: 'bi-stop-circle-fill',
        inputs: 1, outputs: 0,
        defaultData: {},
        preview: () => 'Conversation complete',
    },
};

const CAT_CLASS = { trigger: 'df-trigger', action: 'df-action', logic: 'df-logic', control: 'df-control' };

// ── Drawflow init ─────────────────────────────────────────────────
const canvasEl = document.getElementById('drawflow');
const editor   = new Drawflow(canvasEl);
editor.reroute = true;
editor.start();

// Load existing flow
@isset($flow)
@if($flow->flow_json)
try {
    editor.import(@json($flow->flow_json));
} catch(e) { console.warn('Flow import error', e); }
@endif
@endisset

// ── Drag & drop from palette ──────────────────────────────────────
let dragNodeType = null;

document.querySelectorAll('.node-palette-item').forEach(function (item) {
    item.addEventListener('dragstart', function (e) {
        dragNodeType = this.dataset.node;
    });
});

canvasEl.addEventListener('dragover', function (e) { e.preventDefault(); });
canvasEl.addEventListener('drop', function (e) {
    e.preventDefault();
    if (!dragNodeType) return;

    const pos = editor.drawflow.drawflow.Home.data;
    const nodeType = dragNodeType;
    dragNodeType = null;

    addNode(nodeType, e.clientX, e.clientY);
});

function addNode(type, clientX, clientY) {
    const def  = NODE_DEFS[type];
    if (!def) return;

    const canvasRect = document.getElementById('canvas-wrap').getBoundingClientRect();
    const posX = (clientX - canvasRect.left - editor.canvas_x) / editor.zoom;
    const posY = (clientY - canvasRect.top  - editor.canvas_y) / editor.zoom;

    const inputs  = def.inputs  > 0 ? Object.fromEntries(Array.from({length: def.inputs},  (_,i) => [`input_${i+1}`,  {connections:[]}])) : {};
    const outputs = def.outputs > 0 ? Object.fromEntries(Array.from({length: def.outputs}, (_,i) => [`output_${i+1}`, {connections:[]}])) : {};

    const html = buildNodeHtml(type, def, def.defaultData);

    editor.addNode(type, def.inputs, def.outputs, posX, posY, type, def.defaultData, html);
}

function buildNodeHtml(type, def, data) {
    const catClass  = CAT_CLASS[def.cat] || 'df-action';
    const iconClass = catClass.replace('df-', 'nc-');
    const preview   = def.preview ? def.preview(data) : '';

    let outputLabels = '';
    if (def.outputLabels) {
        outputLabels = `<div class="df-output-labels">
            <span class="yes">✓ ${def.outputLabels[0]}</span>
            <span class="no">✗ ${def.outputLabels[1]}</span>
        </div>`;
    }

    return `<div class="dflow-node-inner ${catClass}">
        <div class="dflow-node-header">
            <div class="ni ${iconClass}"><i class="bi ${def.icon}"></i></div>
            <span class="dflow-node-title">${def.label}</span>
        </div>
        <div class="dflow-node-body node-preview">${preview}</div>
        ${outputLabels}
    </div>`;
}

// Re-render node preview after data change
function refreshNodePreview(nodeId, data) {
    const node = editor.getNodeFromId(nodeId);
    if (!node) return;
    const def   = NODE_DEFS[node.name];
    if (!def || !def.preview) return;

    const el = canvasEl.querySelector(`#node-${nodeId} .node-preview`);
    if (el) el.innerHTML = def.preview(data);
}

// ── Node selection → config panel ────────────────────────────────
let selectedNodeId = null;

editor.on('nodeSelected', function (nodeId) {
    selectedNodeId = nodeId;
    openConfigPanel(nodeId);
});

editor.on('nodeUnselected', function () {
    document.getElementById('config-panel').classList.remove('visible');
    selectedNodeId = null;
});

editor.on('nodeMoved', function () {});

function openConfigPanel(nodeId) {
    const node   = editor.getNodeFromId(nodeId);
    if (!node) return;
    const def    = NODE_DEFS[node.name];
    if (!def) return;
    const data   = node.data || {};
    const panel  = document.getElementById('config-panel');
    const title  = document.getElementById('config-panel-title');
    const body   = document.getElementById('config-panel-body');

    title.textContent = def.label;
    body.innerHTML    = buildConfigForm(node.name, def, data, nodeId);
    panel.classList.add('visible');

    // Live update on change
    body.querySelectorAll('input,select,textarea').forEach(function (el) {
        el.addEventListener('input', function () {
            const form    = body;
            const newData = collectFormData(form, node.name);
            editor.updateNodeDataFromId(nodeId, newData);
            refreshNodePreview(nodeId, newData);
            // update Branch B display for random_split
            const weightB = body.querySelector('#weight-b-display');
            if (weightB) weightB.textContent = (100 - (parseInt(newData.weight_a) || 50)) + '%';
        });
    });

    // Delete button
    const delBtn = body.querySelector('.btn-delete-node');
    if (delBtn) {
        delBtn.addEventListener('click', function () {
            if (confirm('{{ __('Delete this node?') }}')) {
                editor.removeNodeId('node-' + nodeId);
                document.getElementById('config-panel').classList.remove('visible');
            }
        });
    }
}

function collectFormData(form, nodeType) {
    const data = {};
    form.querySelectorAll('[data-field]').forEach(function (el) {
        data[el.dataset.field] = el.value;
    });
    return data;
}

function buildConfigForm(type, def, data, nodeId) {
    const field = (name, label, type, value, extra='') =>
        `<div class="mb-3">
            <label class="config-label">${label}</label>
            <input type="${type}" class="form-control form-control-sm" data-field="${name}" value="${escHtml(String(value ?? ''))}" ${extra}>
        </div>`;

    const textarea = (name, label, value) =>
        `<div class="mb-3">
            <label class="config-label">${label}</label>
            <textarea class="form-control form-control-sm" data-field="${name}" rows="3">${escHtml(String(value ?? ''))}</textarea>
        </div>`;

    const select = (name, label, opts, selected) =>
        `<div class="mb-3">
            <label class="config-label">${label}</label>
            <select class="form-select form-select-sm" data-field="${name}">
                ${opts.map(([v,l]) => `<option value="${v}" ${v == selected ? 'selected' : ''}>${l}</option>`).join('')}
            </select>
        </div>`;

    let html = '';

    switch (type) {
        case 'trigger_keyword':
            html = field('keyword', 'Keyword', 'text', data.keyword) +
                   select('match_type', 'Match Type', [['contains','Contains'],['exact','Exact'],['starts_with','Starts With']], data.match_type || 'contains');
            break;
        case 'trigger_referral':
            html = field('ref_id', 'Referral Ref ID', 'text', data.ref_id);
            break;
        case 'trigger_all':
            html = `<p class="text-muted small">This trigger fires on every inbound message that doesn't match another flow.</p>`;
            break;
        case 'send_text':
            html = textarea('message', 'Message (use {variable} for stored values)', data.message);
            break;
        case 'send_image':
            html = field('url', 'Image URL', 'url', data.url) +
                   field('caption', 'Caption (optional)', 'text', data.caption);
            break;
        case 'send_buttons':
            html = textarea('message', 'Message', data.message) +
                   textarea('buttons', 'Button Labels (one per line, max 3)', data.buttons) +
                   `<small class="text-muted">Use {variable} in message text.</small>`;
            break;
        case 'send_template':
            html = `<div class="mb-3">
                <label class="config-label">Template</label>
                <select class="form-select form-select-sm" data-field="template_id">
                    <option value="">{{ __('Select template') }}</option>
                    ${TEMPLATES.map(t => `<option value="${t.id}" ${t.id == (data.template_id||'') ? 'selected' : ''}>${t.name} (${t.language})</option>`).join('')}
                </select>
            </div>`;
            break;
        case 'ask_input':
            html = textarea('question', 'Question to ask', data.question) +
                   field('variable', 'Save answer as variable', 'text', data.variable || 'user_input') +
                   select('validation', 'Validate answer format', [
                       ['none','No validation'],
                       ['email','Email address'],
                       ['phone','Phone number'],
                       ['number','Numeric only'],
                   ], data.validation || 'none') +
                   `<small class="text-muted">If validation fails the question is repeated.</small>`;
            break;
        case 'trigger_api':
            html = `<p class="text-muted small mb-2">This flow is triggered by calling your API endpoint:</p>
                    <code class="d-block p-2 rounded mb-2" style="background:#f1f5f9;font-size:0.75rem;word-break:break-all">POST /api/en/flow/trigger</code>
                    <p class="text-muted small">Include <code>device_id</code> and <code>phone_number</code> in the request body. Optional: <code>variables</code> object to pre-load session variables.</p>`;
            break;
        case 'api_call':
            html = select('method', 'HTTP Method', [['POST','POST'],['GET','GET'],['PUT','PUT'],['PATCH','PATCH']], data.method || 'POST') +
                   field('url', 'Request URL', 'url', data.url, 'placeholder="https://api.example.com/webhook"') +
                   textarea('body', 'Request body (JSON) — use {variable} for session values', data.body) +
                   field('save_as', 'Save response as variable', 'text', data.save_as || 'api_response') +
                   `<small class="text-muted">Output 1 (left) = HTTP 2xx success, Output 2 (right) = any error.</small>`;
            break;
        case 'add_tag':
            html = field('tag', 'Tag name to add', 'text', data.tag, 'placeholder="e.g. VIP, Lead, Interested"') +
                   `<small class="text-muted">Creates the tag if it doesn't exist yet. Contact is associated with this tag.</small>`;
            break;
        case 'remove_tag':
            html = field('tag', 'Tag name to remove', 'text', data.tag, 'placeholder="e.g. Lead"') +
                   `<small class="text-muted">Removes only this specific tag from the contact. Other tags are unaffected.</small>`;
            break;
        case 'send_catalog':
            html = field('catalog_id', 'Meta Catalog ID', 'text', data.catalog_id, 'placeholder="Your Facebook Catalog ID"') +
                   textarea('body', 'Message body (optional)', data.body) +
                   field('footer', 'Footer text (optional)', 'text', data.footer) +
                   `<small class="text-muted">Requires an approved Meta Commerce Catalog linked to your WABA.</small>`;
            break;
        case 'random_split':
            html = field('weight_a', 'Branch A probability (%)', 'number', data.weight_a ?? 50, 'min="1" max="99"') +
                   `<p class="text-muted small mt-1">Branch B probability: <strong id="weight-b-display">${100-(data.weight_a??50)}%</strong>.<br>Output 1 (left) = Branch A, Output 2 (right) = Branch B.</p>`;
            break;
        case 'condition':
            html = field('variable', 'Variable name (or leave empty to check last reply)', 'text', data.variable) +
                   select('operator', 'Operator', [
                       ['contains','Contains'], ['equals','Equals'], ['starts_with','Starts With'],
                       ['not_equals','Does not equal'], ['not_contains','Does not contain'],
                   ], data.operator || 'contains') +
                   field('value', 'Value to compare', 'text', data.value) +
                   `<small class="text-muted">Output 1 (left) = match, Output 2 (right) = no match.</small>`;
            break;
        case 'delay':
            html = field('seconds', 'Delay in seconds (max 5)', 'number', data.seconds || 2, 'min="1" max="5"');
            break;
        case 'human_handoff':
            html = textarea('message', 'Handoff message (optional)', data.message);
            break;
        case 'end_flow':
            html = `<p class="text-muted small">Flow ends here. The session is marked as completed.</p>`;
            break;
    }

    html += `<hr><button class="btn btn-outline-danger btn-sm w-100 btn-delete-node">
        <i class="bi bi-trash me-1"></i> Delete Node
    </button>`;

    return html;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Toolbar ───────────────────────────────────────────────────────
document.getElementById('btn-zoom-in').addEventListener('click', function () { editor.zoom_in(); });
document.getElementById('btn-zoom-out').addEventListener('click', function () { editor.zoom_out(); });
document.getElementById('btn-zoom-reset').addEventListener('click', function () { editor.zoom_reset(); });
document.getElementById('btn-clear').addEventListener('click', function () {
    if (confirm('{{ __('Clear the entire canvas?') }}')) editor.clearModuleSelected();
});

// Config panel close button
document.getElementById('config-panel-close')?.addEventListener('click', function () {
    document.getElementById('config-panel').classList.remove('visible');
    selectedNodeId = null;
});

// Ctrl+S shortcut
document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('btn-save').click();
    }
});

// ── Save ─────────────────────────────────────────────────────────
document.getElementById('btn-save').addEventListener('click', function () {
    const name    = document.getElementById('flow-name').value.trim();
    const device  = document.getElementById('flow-device').value;

    if (!name)   { alert('{{ __('Enter a flow name.') }}'); return; }
    if (!device) { alert('{{ __('Select a device.') }}'); return; }

    const flowJson  = JSON.stringify(editor.export());
    const payload   = {
        name:             name,
        device_id:        device,
        trigger_type:     document.getElementById('flow-trigger-type').value,
        trigger_value:    document.getElementById('flow-trigger-value').value,
        trigger_match:    document.getElementById('flow-trigger-match').value,
        fallback_message: document.getElementById('flow-fallback-msg').value,
        flow_json:        flowJson,
    };

    const url    = FLOW_ID ? UPDATE_URL : STORE_URL;
    const method = FLOW_ID ? 'PUT' : 'POST';

    fetch(url, {
        method,
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => {
        // Remove any previous validation banner
        const oldBanner = document.getElementById('flow-validation-banner');
        if (oldBanner) oldBanner.remove();

        if (d.error) {
            // Phase E: Show structured validation errors in a dismissable banner
            const errors   = (d.errors   || []).map(e => `<li>${e}</li>`).join('');
            const warnings = (d.warnings || []).map(w => `<li class="text-warning">${w}</li>`).join('');
            const banner   = document.createElement('div');
            banner.id      = 'flow-validation-banner';
            banner.className = 'alert alert-danger alert-dismissible mx-3 mt-2';
            banner.innerHTML = `<button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>`
                + `<strong>{{ __('Flow cannot be saved:') }}</strong><ul class="mb-0 mt-1">${errors}${warnings}</ul>`;
            document.querySelector('.drawflow-wrapper, #drawflow')?.before(banner);
            if (errors) toastr.error('{{ __('Fix flow errors before saving.') }}');
        } else {
            // Show save feedback
            const btn = document.getElementById('btn-save');
            btn.classList.add('saved');
            btn.innerHTML = '<i class="bi bi-check-lg"></i> {{ __('Saved') }}';
            if (typeof toastr !== 'undefined') toastr.success('{{ __('Flow saved.') }}');
            setTimeout(() => {
                btn.classList.remove('saved');
                btn.innerHTML = '<i class="bi bi-floppy-fill"></i> {{ __('Save') }}';
            }, 2500);
            // Show warnings non-blocking
            if (d.warnings && d.warnings.length) {
                toastr.warning(d.warnings.join(' | '), '{{ __('Flow Warnings') }}');
            }
            if (!FLOW_ID && d.redirect) window.location.href = d.redirect;
        }
    });
});

// ── Toggle active/draft ────────────────────────────────────────────
const toggleBtn = document.getElementById('btn-toggle');
if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
        const self = this;
        self.disabled = true;
        fetch(TOGGLE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            if (!d.error) {
                const isNowActive = d.status === 'active';
                self.className   = 'btn-toggle-status ' + (isNowActive ? 'is-active' : 'is-draft');
                self.dataset.status = d.status;
                self.innerHTML   = `<i class="bi ${isNowActive ? 'bi-pause-circle-fill' : 'bi-play-circle-fill'}"></i> ${isNowActive ? '{{ __('Active') }}' : '{{ __('Draft') }}'}`;
                if (typeof toastr !== 'undefined') toastr.success(d.message);
            } else {
                alert(d.message);
            }
        }).finally(() => { self.disabled = false; });
    });
}

// ── Auto-save on connection change ────────────────────────────────
editor.on('connectionCreated', function () {});
editor.on('connectionRemoved', function () {});

})();
</script>

</x-layout-dashboard>
