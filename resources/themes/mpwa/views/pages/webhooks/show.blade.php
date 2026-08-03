<x-layout-dashboard title="{{ $source->name }}">
<x-page-header title="{{ $source->name }}"
    subtitle="{{ \App\Models\WebhookSource::platformLabel($source->platform) }} · {{ __('Triggers & Logs') }}"
    :breadcrumb="[__('Webhooks'), $source->name]">
    <a href="{{ route('webhooks.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>{{ __('All Sources') }}
    </a>
</x-page-header>

<div class="container-fluid px-4 pb-5">

{{-- Webhook URL card --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <p class="fw-semibold mb-2"><i class="bi bi-link-45deg me-1"></i>{{ __('Your Webhook Endpoint') }}</p>
        <div class="input-group mb-2">
            <input type="text" class="form-control font-monospace" id="whUrl"
                   value="{{ $source->inboundUrl() }}" readonly>
            <button class="btn btn-outline-secondary" onclick="copyUrl()">
                <i class="bi bi-clipboard me-1"></i>{{ __('Copy') }}
            </button>
        </div>
        <p class="text-muted small mb-0">
            {{ __('Point your') }} <strong>{{ \App\Models\WebhookSource::platformLabel($source->platform) }}</strong>
            {{ __('webhook to this URL using HTTP POST. The response is always') }} <code>{"received": true}</code>.
        </p>
        @if($source->platform === 'shopify')
        <p class="small text-muted mt-1 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            {{ __('In Shopify: Settings → Notifications → Webhooks → Create webhook. Set format to JSON.') }}
        </p>
        @elseif($source->platform === 'woocommerce')
        <p class="small text-muted mt-1 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            {{ __('In WooCommerce: Settings → Advanced → Webhooks → Add webhook. Set delivery URL above.') }}
        </p>
        @elseif($source->platform === 'razorpay')
        <p class="small text-muted mt-1 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            {{ __('In Razorpay: Settings → Webhooks → Add new webhook. Paste URL above and select events.') }}
        </p>
        @endif
    </div>
</div>

<div class="row g-4">

{{-- Left: Triggers --}}
<div class="col-lg-7">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0"><i class="bi bi-lightning-charge me-1"></i>{{ __('Triggers') }}</h6>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addTriggerModal">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add Trigger') }}
        </button>
    </div>

    @if($source->triggers->isEmpty())
    <div class="text-center py-4 text-muted border rounded-3 bg-light">
        <i class="bi bi-lightning display-5 d-block mb-2"></i>
        {{ __('No triggers yet. Add one to define what action to take when a webhook arrives.') }}
    </div>
    @else
    <div class="list-group shadow-sm" id="triggerList">
    @foreach($source->triggers as $tr)
    <div class="list-group-item list-group-item-action d-flex gap-3 align-items-start py-3" id="trigger-{{ $tr->id }}">
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-semibold">{{ $tr->name }}</span>
                <span class="badge {{ $tr->action_type === 'send_template' ? 'bg-primary' : 'bg-warning text-dark' }} ms-auto">
                    {{ $tr->action_type === 'send_template' ? __('Template') : __('Flow') }}
                </span>
                <span class="badge {{ $tr->active ? 'bg-success' : 'bg-secondary' }}">
                    {{ $tr->active ? __('On') : __('Off') }}
                </span>
            </div>

            @if($tr->match_field)
            <p class="small text-muted mb-1">
                <i class="bi bi-funnel me-1"></i>{{ __('Match') }}
                <code>{{ $tr->match_field }}</code> = <code>{{ $tr->match_value }}</code>
            </p>
            @else
            <p class="small text-muted mb-1"><i class="bi bi-funnel me-1"></i>{{ __('Match all payloads') }}</p>
            @endif

            @if($tr->action_type === 'send_template')
            <p class="small text-muted mb-0">
                <i class="bi bi-chat-text me-1"></i>
                {{ __('Send template') }} <code>{{ $tr->template_name }}</code>
                {{ __('via') }} {{ $tr->device?->meta_profile['verified_name'] ?? $tr->device?->body ?? '—' }}
            </p>
            @else
            <p class="small text-muted mb-0">
                <i class="bi bi-diagram-2 me-1"></i>
                {{ __('Start flow') }} <strong>{{ $tr->flow?->name ?? '—' }}</strong>
            </p>
            @endif
        </div>
        <div class="d-flex flex-column gap-1 flex-shrink-0">
            <button class="btn btn-xs btn-outline-secondary toggle-trigger"
                    data-id="{{ $tr->id }}" title="{{ $tr->active ? __('Pause') : __('Activate') }}">
                <i class="bi bi-{{ $tr->active ? 'pause' : 'play' }}-fill"></i>
            </button>
            <button class="btn btn-xs btn-outline-danger delete-trigger"
                    data-id="{{ $tr->id }}" data-name="{{ $tr->name }}">
                <i class="bi bi-trash3"></i>
            </button>
        </div>
    </div>
    @endforeach
    </div>
    @endif
</div>

{{-- Right: Recent Logs --}}
<div class="col-lg-5">
    <h6 class="mb-3"><i class="bi bi-journal-text me-1"></i>{{ __('Recent Logs') }}
        <span class="badge bg-light text-muted border ms-1">{{ $logs->count() }}</span>
    </h6>

    @if($logs->isEmpty())
    <p class="text-muted small">{{ __('No webhooks received yet.') }}</p>
    @else
    <div class="list-group shadow-sm small" style="max-height:500px;overflow-y:auto">
    @foreach($logs as $log)
    @php
        $statusColor = match($log->status) {
            'processed' => 'success',
            'failed'    => 'danger',
            'skipped'   => 'warning',
            default     => 'secondary',
        };
    @endphp
    <div class="list-group-item py-2 px-3">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
            <span class="badge bg-{{ $statusColor }}">{{ strtoupper($log->status) }}</span>
            <span class="text-muted" style="font-size:11px">{{ $log->created_at?->diffForHumans() }}</span>
        </div>
        @if($log->resolved_phone)
            <div><i class="bi bi-telephone me-1"></i>{{ $log->resolved_phone }}</div>
        @endif
        @if($log->trigger)
            <div class="text-muted"><i class="bi bi-lightning me-1"></i>{{ $log->trigger->name }}</div>
        @endif
        @if($log->error)
            <div class="text-danger mt-1" style="font-size:11px;word-break:break-word">{{ $log->error }}</div>
        @endif
        <details class="mt-1">
            <summary class="text-muted" style="cursor:pointer;font-size:11px">{{ __('View payload') }}</summary>
            <pre class="mt-1 p-2 rounded bg-light" style="font-size:10px;max-height:120px;overflow:auto">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </div>
    @endforeach
    </div>
    @endif
</div>

</div>
</div>

{{-- Add Trigger Modal --}}
<div class="modal fade" id="addTriggerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ __('Add Trigger') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Trigger Name') }}</label>
                    <input type="text" id="t_name" class="form-control" placeholder="{{ __('e.g. New Order → Order Confirmation') }}">
                </div>

                <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                    <p class="fw-semibold mb-2 small"><i class="bi bi-funnel me-1"></i>{{ __('Event Filter') }} <span class="text-muted">({{ __('optional') }})</span></p>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label form-label-sm">{{ __('Payload Field') }}</label>
                            <input type="text" id="t_match_field" class="form-control form-control-sm font-monospace"
                                   placeholder="event  or  financial_status">
                            <div class="form-text">{{ __('Dot-notation path. Leave blank to fire on every webhook.') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label form-label-sm">{{ __('Expected Value') }}</label>
                            <input type="text" id="t_match_value" class="form-control form-control-sm font-monospace"
                                   placeholder="orders/create  or  paid">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Action') }}</label>
                    <select id="t_action_type" class="form-select" onchange="actionTypeChanged()">
                        <option value="send_template">{{ __('Send Template Message') }}</option>
                        <option value="start_flow">{{ __('Start Flow / Chatbot') }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Device (WhatsApp Number)') }}</label>
                    <select id="t_device_id" class="form-select">
                        <option value="">{{ __('Select device…') }}</option>
                        @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->meta_profile['verified_name'] ?? $d->body }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Template fields --}}
                <div id="templateFields">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Template') }}</label>
                        <select id="t_template_name" class="form-select" onchange="templateSelected()">
                            <option value="">{{ __('Select approved template…') }}</option>
                            @foreach($templates as $tpl)
                            <option value="{{ $tpl->name }}" data-lang="{{ $tpl->language }}">
                                {{ $tpl->name }} ({{ $tpl->language }})
                            </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="t_template_language" value="en">
                    </div>

                    <div class="card bg-light border-0 rounded-3 p-3 mb-3" id="varMapSection" style="display:none">
                        <p class="fw-semibold mb-2 small">
                            <i class="bi bi-arrow-left-right me-1"></i>{{ __('Variable Mapping') }}
                        </p>
                        <p class="text-muted small mb-2">
                            {{ __('Map each template variable to a field from the webhook payload (dot-notation).') }}
                        </p>
                        <div id="varMapRows"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addVarRow()">
                            <i class="bi bi-plus me-1"></i>{{ __('Add Variable') }}
                        </button>
                    </div>
                </div>

                {{-- Flow fields --}}
                <div id="flowFields" style="display:none">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Flow') }}</label>
                        <select id="t_flow_id" class="form-select">
                            <option value="">{{ __('Select flow…') }}</option>
                            @foreach($flows as $flow)
                            <option value="{{ $flow->id }}">{{ $flow->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-success" id="saveTriggerBtn" onclick="saveTrigger()">
                    <i class="bi bi-check-lg me-1"></i>{{ __('Save Trigger') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const SOURCE_ID   = {{ $source->id }};
const CSRF        = '{{ csrf_token() }}';
const LOCALE      = '{{ app()->getLocale() }}';
const TRIGGER_URL = `/${LOCALE}/webhooks/${SOURCE_ID}/triggers`;

function copyUrl() {
    const v = document.getElementById('whUrl').value;
    navigator.clipboard.writeText(v).then(() => toastr.success('{{ __("URL copied!") }}'));
}

function actionTypeChanged() {
    const v = document.getElementById('t_action_type').value;
    document.getElementById('templateFields').style.display = v === 'send_template' ? '' : 'none';
    document.getElementById('flowFields').style.display     = v === 'start_flow'    ? '' : 'none';
}

function templateSelected() {
    const sel  = document.getElementById('t_template_name');
    const opt  = sel.options[sel.selectedIndex];
    document.getElementById('t_template_language').value = opt?.dataset?.lang || 'en';
}

let varRowCount = 0;
function addVarRow(varNum) {
    varRowCount++;
    const n = varNum || varRowCount;
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center mb-2';
    row.innerHTML = `
        <div class="col-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text font-monospace">{{</span>
                <input type="number" class="form-control form-control-sm var-num text-center" value="${n}" min="1">
                <span class="input-group-text font-monospace">}}</span>
            </div>
        </div>
        <div class="col-1 text-center text-muted">→</div>
        <div class="col-7">
            <input type="text" class="form-control form-control-sm font-monospace var-path"
                   placeholder="order.name  or  customer.first_name">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.row').remove()">
                <i class="bi bi-x"></i>
            </button>
        </div>`;
    document.getElementById('varMapRows').appendChild(row);
    document.getElementById('varMapSection').style.display = '';
}

function buildVarMap() {
    const map = {};
    document.querySelectorAll('#varMapRows .row').forEach(row => {
        const num  = row.querySelector('.var-num').value;
        const path = row.querySelector('.var-path').value.trim();
        if (num && path) map[num] = path;
    });
    return Object.keys(map).length ? JSON.stringify(map) : null;
}

function saveTrigger() {
    const name        = document.getElementById('t_name').value.trim();
    const match_field = document.getElementById('t_match_field').value.trim();
    const match_value = document.getElementById('t_match_value').value.trim();
    const action_type = document.getElementById('t_action_type').value;
    const device_id   = document.getElementById('t_device_id').value;

    if (!name || !device_id) {
        toastr.warning('{{ __("Trigger name and device are required.") }}');
        return;
    }

    const body = { name, match_field: match_field || null, match_value: match_value || null,
                   action_type, device_id };

    if (action_type === 'send_template') {
        const tname = document.getElementById('t_template_name').value;
        if (!tname) { toastr.warning('{{ __("Select a template.") }}'); return; }
        body.template_name     = tname;
        body.template_language = document.getElementById('t_template_language').value;
        body.variable_map      = buildVarMap();
    } else {
        const flow_id = document.getElementById('t_flow_id').value;
        if (!flow_id) { toastr.warning('{{ __("Select a flow.") }}'); return; }
        body.flow_id = flow_id;
    }

    const btn = document.getElementById('saveTriggerBtn');
    btn.disabled = true;

    fetch(TRIGGER_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { toastr.error(d.message); return; }
        toastr.success(d.message);
        bootstrap.Modal.getInstance(document.getElementById('addTriggerModal')).hide();
        setTimeout(() => location.reload(), 700);
    })
    .catch(() => toastr.error('{{ __("Request failed.") }}'))
    .finally(() => btn.disabled = false);
}

// Toggle trigger
document.querySelectorAll('.toggle-trigger').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        fetch(`/${LOCALE}/webhooks/${SOURCE_ID}/triggers/${id}/toggle`, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF },
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) { toastr.error('Failed'); return; }
            toastr.success(d.active ? '{{ __("Trigger activated.") }}' : '{{ __("Trigger paused.") }}');
            setTimeout(() => location.reload(), 500);
        });
    });
});

// Delete trigger
document.querySelectorAll('.delete-trigger').forEach(btn => {
    btn.addEventListener('click', function () {
        const id   = this.dataset.id;
        const name = this.dataset.name;
        if (!confirm(`{{ __("Delete trigger") }} "${name}"?`)) return;
        fetch(`/${LOCALE}/webhooks/${SOURCE_ID}/triggers/${id}`, {
            method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) { toastr.error('Failed'); return; }
            toastr.success(d.message);
            document.getElementById(`trigger-${id}`)?.remove();
        });
    });
});
</script>
@endpush

</x-layout-dashboard>
