<x-layout-dashboard title="{{ __('Chatbot Flows') }}">

<style>
/* ── Flows list page ───────────────────────────────────────────── */
.flows-page            { max-width:1200px; margin:0 auto; padding:24px 20px 40px; }

/* Header */
.flows-header          { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:28px; gap:16px; }
.flows-header-left h4  { font-size:1.35rem; font-weight:700; color:#0f172a; letter-spacing:-.02em; margin:0 0 4px; }
.flows-header-left p   { font-size:0.82rem; color:#64748b; margin:0; }
.btn-new-flow          { background:#1e293b; color:#fff; border:none; border-radius:8px; padding:9px 18px; font-size:0.82rem; font-weight:600; display:inline-flex; align-items:center; gap:6px; text-decoration:none; transition:background 180ms, transform 120ms; white-space:nowrap; }
.btn-new-flow:hover    { background:#0f172a; color:#fff; transform:translateY(-1px); }
.btn-new-flow:active   { transform:translateY(0) scale(.98); }

/* Stats strip */
.flows-stats           { display:flex; gap:16px; margin-bottom:24px; }
.flow-stat             { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px 20px; flex:1; min-width:0; }
.flow-stat-value       { font-size:1.5rem; font-weight:700; color:#0f172a; letter-spacing:-.03em; font-variant-numeric:tabular-nums; }
.flow-stat-label       { font-size:0.75rem; color:#94a3b8; margin-top:1px; }

/* Grid */
.flows-grid            { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:16px; }

/* Flow card */
.flow-card             { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; display:flex; flex-direction:column; gap:14px; transition:border-color 180ms, box-shadow 180ms, transform 160ms; cursor:pointer; position:relative; }
.flow-card:hover       { border-color:#94a3b8; box-shadow:0 4px 16px rgba(15,23,42,.07); transform:translateY(-2px); }
.flow-card:active      { transform:translateY(0); }

/* Card top row */
.fc-top                { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.fc-status             { display:inline-flex; align-items:center; gap:5px; font-size:0.72rem; font-weight:600; letter-spacing:.04em; padding:3px 8px; border-radius:6px; white-space:nowrap; }
.fc-status.active      { background:#dcfce7; color:#15803d; }
.fc-status.draft       { background:#f1f5f9; color:#64748b; }
.fc-status .dot        { width:6px; height:6px; border-radius:50%; background:currentColor; display:inline-block; }

/* Three-dot menu */
.fc-menu-btn           { background:none; border:1px solid transparent; border-radius:6px; padding:4px 5px; color:#94a3b8; transition:background 150ms, border-color 150ms, color 150ms; cursor:pointer; line-height:1; }
.fc-menu-btn:hover     { background:#f8fafc; border-color:#e2e8f0; color:#334155; }

/* Card body */
.fc-name               { font-size:0.95rem; font-weight:700; color:#0f172a; letter-spacing:-.01em; margin:0; line-height:1.3; }
.fc-desc               { font-size:0.78rem; color:#64748b; margin:0; line-height:1.5; }

/* Trigger badge */
.fc-trigger            { display:inline-flex; align-items:center; gap:5px; font-size:0.75rem; font-weight:500; color:#334155; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:4px 9px; }
.fc-trigger i          { font-size:13px; color:#6366f1; }

/* Metrics row */
.fc-metrics            { display:flex; gap:14px; border-top:1px solid #f1f5f9; padding-top:12px; }
.fc-metric             { display:flex; flex-direction:column; gap:1px; }
.fc-metric-val         { font-size:0.9rem; font-weight:700; color:#0f172a; font-variant-numeric:tabular-nums; }
.fc-metric-lbl         { font-size:0.68rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }

/* Quick-action row */
.fc-actions            { display:flex; gap:8px; margin-top:auto; }
.fc-btn                { flex:1; border:1px solid #e2e8f0; background:#fff; border-radius:7px; padding:7px 10px; font-size:0.78rem; font-weight:500; color:#334155; cursor:pointer; transition:background 140ms, border-color 140ms, color 140ms; display:inline-flex; align-items:center; justify-content:center; gap:5px; text-decoration:none; }
.fc-btn:hover          { background:#f8fafc; border-color:#94a3b8; color:#0f172a; }
.fc-btn.primary        { background:#0f172a; border-color:#0f172a; color:#fff; }
.fc-btn.primary:hover  { background:#1e293b; border-color:#1e293b; }
.fc-btn.danger         { color:#ef4444; border-color:#fee2e2; }
.fc-btn.danger:hover   { background:#fef2f2; border-color:#fca5a5; }
.fc-btn i              { font-size:14px; }

/* Toggle button in card */
.fc-toggle             { display:inline-flex; align-items:center; gap:5px; font-size:0.78rem; font-weight:500; border-radius:7px; padding:7px 10px; border:1px solid; cursor:pointer; transition:background 140ms; }
.fc-toggle.is-active   { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.fc-toggle.is-draft    { background:#f8fafc; border-color:#e2e8f0; color:#64748b; }
.fc-toggle i           { font-size:13px; }

/* Empty state */
.flows-empty           { grid-column:1/-1; background:#fff; border:2px dashed #e2e8f0; border-radius:16px; padding:60px 24px; text-align:center; }
.flows-empty-icon      { width:56px; height:56px; background:#f1f5f9; border-radius:14px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px; }
.flows-empty-icon i    { font-size:28px; color:#94a3b8; }
.flows-empty h6        { font-size:1rem; font-weight:700; color:#0f172a; margin:0 0 8px; }
.flows-empty p         { font-size:0.82rem; color:#64748b; max-width:300px; margin:0 auto 20px; line-height:1.6; }
</style>

<div class="flows-page">

    {{-- Header --}}
    <div class="flows-header">
        <div class="flows-header-left">
            <h4><i class="material-icons" style="font-size:20px;vertical-align:-3px;margin-right:6px;color:#6366f1">account_tree</i>{{ __('Chatbot flows') }}</h4>
            <p>{{ __('Automate WhatsApp conversations with visual drag-and-drop flows') }}</p>
        </div>
        <a href="{{ route('flows.create') }}" class="btn-new-flow">
            <i class="material-icons" style="font-size:16px">add</i> {{ __('New flow') }}
        </a>
    </div>

    {{-- Stats strip --}}
    @if($flows->total() > 0)
    <div class="flows-stats">
        <div class="flow-stat">
            <div class="flow-stat-value">{{ $flows->total() }}</div>
            <div class="flow-stat-label">{{ __('Total flows') }}</div>
        </div>
        <div class="flow-stat">
            <div class="flow-stat-value">{{ $flows->where('status','active')->count() }}</div>
            <div class="flow-stat-label">{{ __('Active') }}</div>
        </div>
        <div class="flow-stat">
            <div class="flow-stat-value">{{ $flows->sum('sessions_count') }}</div>
            <div class="flow-stat-label">{{ __('Total sessions') }}</div>
        </div>
        <div class="flow-stat">
            <div class="flow-stat-value">{{ $flows->where('trigger_type','keyword')->count() }}</div>
            <div class="flow-stat-label">{{ __('Keyword triggers') }}</div>
        </div>
    </div>
    @endif

    {{-- Grid --}}
    <div class="flows-grid">
        @forelse ($flows as $flow)
        <div class="flow-card" onclick="window.location='{{ route('flows.edit', $flow->id) }}'">

            {{-- Top --}}
            <div class="fc-top">
                <span class="fc-status {{ $flow->status }}">
                    <span class="dot"></span>
                    {{ $flow->status === 'active' ? __('Active') : __('Draft') }}
                </span>
                <div class="dropdown" onclick="event.stopPropagation()">
                    <button class="fc-menu-btn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="material-icons" style="font-size:18px">more_horiz</i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:0.82rem;min-width:160px">
                        <li>
                            <a class="dropdown-item" href="{{ route('flows.edit', $flow->id) }}">
                                <i class="material-icons me-2" style="font-size:15px;vertical-align:-2px">edit</i>{{ __('Edit') }}
                            </a>
                        </li>
                        <li>
                            <button class="dropdown-item btn-duplicate-flow" data-id="{{ $flow->id }}">
                                <i class="material-icons me-2" style="font-size:15px;vertical-align:-2px">content_copy</i>{{ __('Duplicate') }}
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button class="dropdown-item text-danger btn-delete-flow" data-id="{{ $flow->id }}">
                                <i class="material-icons me-2" style="font-size:15px;vertical-align:-2px">delete_outline</i>{{ __('Delete') }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Name + desc --}}
            <div>
                <p class="fc-name">{{ $flow->name }}</p>
                @if($flow->description)
                    <p class="fc-desc mt-1">{{ Str::limit($flow->description, 72) }}</p>
                @endif
            </div>

            {{-- Trigger --}}
            <div>
                @php
                    $triggerIcon  = ['keyword'=>'flash_on','all'=>'all_inclusive','referral'=>'ads_click','api'=>'webhook'][$flow->trigger_type] ?? 'flash_on';
                    $triggerLabel = ['keyword'=>__('Keyword'),'all'=>__('All messages'),'referral'=>__('Ad click'),'api'=>__('API webhook')][$flow->trigger_type] ?? $flow->trigger_type;
                @endphp
                <span class="fc-trigger">
                    <i class="material-icons">{{ $triggerIcon }}</i>
                    {{ $triggerLabel }}
                    @if($flow->trigger_value)
                        <span style="color:#94a3b8">·</span>
                        <span style="font-family:monospace;font-size:0.72rem">{{ Str::limit($flow->trigger_value, 22) }}</span>
                    @endif
                </span>
            </div>

            {{-- Metrics --}}
            <div class="fc-metrics">
                <div class="fc-metric">
                    <span class="fc-metric-val">{{ $flow->sessions_count }}</span>
                    <span class="fc-metric-lbl">{{ __('Sessions') }}</span>
                </div>
                <div class="fc-metric">
                    <span class="fc-metric-val">{{ $flow->device->meta_profile['verified_name'] ?? Str::limit($flow->device->body ?? '—', 14) }}</span>
                    <span class="fc-metric-lbl">{{ __('Device') }}</span>
                </div>
                <div class="fc-metric">
                    <span class="fc-metric-val">{{ $flow->updated_at->diffForHumans(null, true) }}</span>
                    <span class="fc-metric-lbl">{{ __('Updated') }}</span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="fc-actions" onclick="event.stopPropagation()">
                <button class="fc-toggle {{ $flow->status === 'active' ? 'is-active' : 'is-draft' }} btn-toggle-flow"
                    data-id="{{ $flow->id }}" data-status="{{ $flow->status }}"
                    title="{{ $flow->status === 'active' ? __('Click to deactivate') : __('Click to activate') }}">
                    <i class="material-icons" style="font-size:14px">{{ $flow->status === 'active' ? 'pause_circle' : 'play_circle' }}</i>
                    {{ $flow->status === 'active' ? __('Pause') : __('Activate') }}
                </button>
                <a href="{{ route('flows.edit', $flow->id) }}" class="fc-btn primary">
                    <i class="material-icons">edit</i> {{ __('Edit') }}
                </a>
            </div>
        </div>
        @empty
        <div class="flows-empty">
            <div class="flows-empty-icon">
                <i class="material-icons">account_tree</i>
            </div>
            <h6>{{ __('No flows yet') }}</h6>
            <p>{{ __('Build your first automated conversation. Drag nodes, connect them, and go live in minutes.') }}</p>
            <a href="{{ route('flows.create') }}" class="btn-new-flow" style="display:inline-flex">
                <i class="material-icons" style="font-size:16px">add</i> {{ __('Create first flow') }}
            </a>
        </div>
        @endforelse
    </div>

    @if($flows->hasPages())
    <div class="mt-4">{{ $flows->links() }}</div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.btn-toggle-flow').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const self = this;
            self.disabled = true;
            fetch('/flows/' + id + '/toggle', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                if (!d.error) {
                    // Update button and status badge without full reload
                    const card   = self.closest('.flow-card');
                    const badge  = card.querySelector('.fc-status');
                    const isNowActive = d.status === 'active';
                    badge.className = 'fc-status ' + d.status;
                    badge.innerHTML = `<span class="dot"></span> ${isNowActive ? '{{ __('Active') }}' : '{{ __('Draft') }}'}`;
                    self.className  = 'fc-toggle ' + (isNowActive ? 'is-active' : 'is-draft') + ' btn-toggle-flow';
                    self.dataset.status = d.status;
                    self.innerHTML  = `<i class="material-icons" style="font-size:14px">${isNowActive ? 'pause_circle' : 'play_circle'}</i> ${isNowActive ? '{{ __('Pause') }}' : '{{ __('Activate') }}'}`;
                    if (typeof toastr !== 'undefined') toastr.success(d.message);
                } else {
                    alert(d.message);
                }
            }).finally(() => { self.disabled = false; });
        });
    });

    document.querySelectorAll('.btn-delete-flow').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('{{ __('Delete this flow? Active sessions will end.') }}')) return;
            const id   = this.dataset.id;
            const card = this.closest('.flow-card');
            fetch('/flows/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                if (!d.error) {
                    card.style.transition = 'opacity 250ms, transform 250ms';
                    card.style.opacity    = '0';
                    card.style.transform  = 'scale(.97)';
                    setTimeout(() => card.remove(), 260);
                } else {
                    alert(d.message);
                }
            });
        });
    });

    document.querySelectorAll('.btn-duplicate-flow').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetch('/flows/' + this.dataset.id + '/duplicate', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                if (!d.error) window.location.href = d.redirect;
                else alert(d.message);
            });
        });
    });
});
</script>

</x-layout-dashboard>
