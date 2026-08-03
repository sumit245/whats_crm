<x-layout-dashboard title="{{ __('Webhooks') }}">
<x-page-header title="{{ __('Webhook Sources') }}"
    subtitle="{{ __('Receive HTTP webhooks from Shopify, WooCommerce, Razorpay, or any custom platform and trigger automated WhatsApp messages.') }}"
    :breadcrumb="[__('Webhooks')]">
    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg me-1"></i>{{ __('Add Webhook Source') }}
    </button>
</x-page-header>

<div class="container-fluid px-4 pb-5">

{{-- Empty state --}}
@if($sources->isEmpty())
<div class="text-center py-5">
    <i class="bi bi-plug display-3 text-muted"></i>
    <p class="mt-3 text-muted">{{ __('No webhook sources yet. Create one to start receiving events.') }}</p>
    <button class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg me-1"></i>{{ __('Add Webhook Source') }}
    </button>
</div>
@else

{{-- How it works banner --}}
<div class="alert alert-info border-0 shadow-sm mb-4 d-flex gap-3 align-items-start">
    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
    <div>
        <strong>{{ __('How it works:') }}</strong>
        {{ __('Each source has a unique URL. Point your Shopify / WooCommerce / Razorpay webhook to that URL. Add triggers below each source to define what action to take when a payload arrives.') }}
    </div>
</div>

<div class="row g-3">
@foreach($sources as $src)
@php
    $icons = ['shopify'=>'bi-bag-check','woocommerce'=>'bi-cart4','razorpay'=>'bi-credit-card-2-front','generic'=>'bi-plug'];
    $icon  = $icons[$src->platform] ?? 'bi-plug';
@endphp
<div class="col-md-6 col-xl-4" id="src-card-{{ $src->id }}">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-2 flex-shrink-0">
                    <i class="bi {{ $icon }} fs-4 text-success"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h6 class="mb-0 text-truncate">{{ $src->name }}</h6>
                        <span class="badge {{ $src->active ? 'bg-success' : 'bg-secondary' }} ms-auto flex-shrink-0">
                            {{ $src->active ? __('Active') : __('Paused') }}
                        </span>
                    </div>
                    <p class="small text-muted mb-2">{{ \App\Models\WebhookSource::platformLabel($src->platform) }}</p>

                    {{-- Webhook URL --}}
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control form-control-sm font-monospace"
                               value="{{ $src->inboundUrl() }}" readonly id="url-{{ $src->id }}">
                        <button class="btn btn-outline-secondary" onclick="copyUrl('url-{{ $src->id }}')" title="{{ __('Copy') }}">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>

                    <div class="text-muted small">
                        <i class="bi bi-lightning me-1"></i>{{ $src->triggers_count }} {{ __('trigger(s)') }}
                        &nbsp;·&nbsp;
                        <i class="bi bi-key me-1"></i>{{ __('Phone field:') }} <code>{{ $src->phone_field }}</code>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-transparent d-flex gap-2">
            <a href="{{ route('webhooks.show', $src->id) }}" class="btn btn-sm btn-outline-primary flex-fill">
                <i class="bi bi-gear me-1"></i>{{ __('Triggers & Logs') }}
            </a>
            <button class="btn btn-sm btn-outline-secondary toggle-btn" data-id="{{ $src->id }}"
                    title="{{ $src->active ? __('Pause') : __('Activate') }}">
                <i class="bi bi-{{ $src->active ? 'pause' : 'play' }}-fill"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="{{ $src->id }}"
                    data-name="{{ $src->name }}" title="{{ __('Delete') }}">
                <i class="bi bi-trash3"></i>
            </button>
        </div>
    </div>
</div>
@endforeach
</div>
@endif

</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ __('Add Webhook Source') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Name') }}</label>
                    <input type="text" id="c_name" class="form-control" placeholder="{{ __('e.g. My Shopify Store') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Platform') }}</label>
                    <select id="c_platform" class="form-select" onchange="platformChanged()">
                        <option value="generic">{{ __('Generic / Custom') }}</option>
                        <option value="shopify">Shopify</option>
                        <option value="woocommerce">WooCommerce</option>
                        <option value="razorpay">Razorpay</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Phone Number Field') }}
                        <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                           title="{{ __('Dot-notation path to the phone number inside the webhook payload. E.g. customer.phone') }}"></i>
                    </label>
                    <input type="text" id="c_phone_field" class="form-control font-monospace"
                           value="phone" placeholder="customer.phone">
                    <div class="form-text">{{ __('Use dot notation for nested fields, e.g.') }} <code>customer.phone</code></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Contact Name Field') }} <span class="text-muted small">({{ __('optional') }})</span></label>
                    <input type="text" id="c_name_field" class="form-control font-monospace" placeholder="customer.first_name">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">{{ __('Signing Secret') }} <span class="text-muted small">({{ __('optional') }})</span></label>
                    <input type="text" id="c_secret" class="form-control font-monospace" placeholder="{{ __('Leave blank if not needed') }}">
                    <div class="form-text">{{ __('Used to verify HMAC signatures from Shopify / WooCommerce.') }}</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-success btn-sm" id="createBtn" onclick="createSource()">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Create') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const STORE_URL  = '{{ route("webhooks.store") }}';
const CSRF       = '{{ csrf_token() }}';

const PHONE_DEFAULTS = {
    shopify:     'customer.phone',
    woocommerce: 'billing.phone',
    razorpay:    'payload.payment.entity.contact',
    generic:     'phone',
};

function platformChanged() {
    const p = document.getElementById('c_platform').value;
    document.getElementById('c_phone_field').value = PHONE_DEFAULTS[p] || 'phone';
}

function copyUrl(inputId) {
    const el = document.getElementById(inputId);
    navigator.clipboard.writeText(el.value).then(() => toastr.success('{{ __("URL copied!") }}'));
}

function createSource() {
    const name       = document.getElementById('c_name').value.trim();
    const platform   = document.getElementById('c_platform').value;
    const phone_field = document.getElementById('c_phone_field').value.trim();
    const name_field  = document.getElementById('c_name_field').value.trim();
    const secret     = document.getElementById('c_secret').value.trim();

    if (!name || !phone_field) {
        toastr.warning('{{ __("Name and phone field are required.") }}');
        return;
    }

    const btn = document.getElementById('createBtn');
    btn.disabled = true;

    fetch(STORE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, platform, phone_field, name_field: name_field || null, secret: secret || null }),
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { toastr.error(d.message); return; }
        toastr.success(d.message);
        bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
        setTimeout(() => location.reload(), 800);
    })
    .catch(() => toastr.error('{{ __("Request failed.") }}'))
    .finally(() => btn.disabled = false);
}

// Toggle active
document.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        fetch(`/{{ app()->getLocale() }}/webhooks/${id}/toggle`, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF },
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) { toastr.error('Failed'); return; }
            toastr.success(d.active ? '{{ __("Source activated.") }}' : '{{ __("Source paused.") }}');
            setTimeout(() => location.reload(), 600);
        });
    });
});

// Delete
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const id   = this.dataset.id;
        const name = this.dataset.name;
        if (!confirm(`{{ __("Delete webhook source") }} "${name}"?`)) return;
        fetch(`/{{ app()->getLocale() }}/webhooks/${id}`, {
            method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) { toastr.error('Failed'); return; }
            toastr.success(d.message);
            document.getElementById(`src-card-${id}`)?.closest('.col-md-6, .col-xl-4')?.remove();
        });
    });
});

// Init tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endpush

</x-layout-dashboard>
