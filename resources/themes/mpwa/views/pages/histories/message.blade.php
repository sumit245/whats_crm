<x-layout-dashboard title="{{__('Messages History')}}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ __('Messages History') }}"
    subtitle="{{ __('All messages sent through the web and API') }}"
    :breadcrumb="[__('Reports'), __('Messages History')]" />

{{-- ── Delivery Funnel ───────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @php
        $funnelSteps = [
            ['label' => __('Total Sent'),  'key' => 'total',     'icon' => 'send',          'color' => 'primary'],
            ['label' => __('Dispatched'),  'key' => 'sent',      'icon' => 'check',         'color' => 'info'],
            ['label' => __('Delivered'),   'key' => 'delivered', 'icon' => 'check2-all',    'color' => 'warning'],
            ['label' => __('Read'),        'key' => 'read',      'icon' => 'eye',           'color' => 'success'],
            ['label' => __('Failed'),      'key' => 'failed',    'icon' => 'x-circle',      'color' => 'danger'],
        ];
    @endphp
    @foreach($funnelSteps as $step)
    @php
        $val  = $funnel[$step['key']];
        $pct  = $funnel['total'] > 0 ? round($val / $funnel['total'] * 100) : 0;
    @endphp
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm text-center py-3 h-100">
            <div class="mb-1">
                <i class="bi bi-{{ $step['icon'] }} text-{{ $step['color'] }} fs-4"></i>
            </div>
            <div class="fw-bold fs-4">{{ number_format($val) }}</div>
            <div class="text-muted small">{{ $step['label'] }}</div>
            @if($step['key'] !== 'total')
            <div class="mt-1 mx-3">
                <div class="progress" style="height:4px">
                    <div class="progress-bar bg-{{ $step['color'] }}" style="width:{{ $pct }}%"></div>
                </div>
                <div class="text-muted" style="font-size:11px">{{ $pct }}%</div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- ── Filters ───────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('messages.history') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Type') }}</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">{{ __('All') }}</option>
                    @foreach(['text','media','template','button','list','location','vcard','sticker'] as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Source') }}</label>
                <select name="send_by" class="form-select form-select-sm">
                    <option value="">{{ __('All') }}</option>
                    <option value="api"  {{ request('send_by') === 'api'  ? 'selected' : '' }}>API</option>
                    <option value="web"  {{ request('send_by') === 'web'  ? 'selected' : '' }}>Web</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Delivery') }}</label>
                <select name="delivery" class="form-select form-select-sm">
                    <option value="">{{ __('All') }}</option>
                    @foreach(['sent','delivered','read','failed'] as $d)
                    <option value="{{ $d }}" {{ request('delivery') === $d ? 'selected' : '' }}>{{ ucfirst($d) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('From') }}</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('To') }}</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i>{{ __('Filter') }}
                </button>
                <a href="{{ route('messages.history') }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Clear') }}">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- ── Table ─────────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ __('ID') }}</th>
                        <th>{{ __('Device') }}</th>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Message') }}</th>
                        <th>{{ __('Delivery') }}</th>
                        <th>{{ __('Via') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="pe-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @if($messages->isEmpty())
                    <x-no-data colspan="8" text="{{ __('No Messages History') }}" />
                @else
                    @if($messages->total() > 0 && !request()->hasAny(['type','send_by','delivery','date_from','date_to']))
                    <tr>
                        <td colspan="7" class="ps-3 text-danger small">
                            {{ __('Delete all your message history?') }}
                        </td>
                        <td class="pe-3">
                            <a onclick="deleteAll({{ $userId }})" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash3"></i> {{ __('Delete All') }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    @foreach($messages as $msg)
                    @php
                        $ds = $msg->delivery_status;
                        $deliveryBadge = match($ds) {
                            'read'      => ['color' => 'success',   'icon' => 'eye',          'label' => __('Read')],
                            'delivered' => ['color' => 'info',      'icon' => 'check2-all',   'label' => __('Delivered')],
                            'sent'      => ['color' => 'primary',   'icon' => 'check2',       'label' => __('Sent')],
                            'failed'    => ['color' => 'danger',    'icon' => 'x-circle',     'label' => __('Failed')],
                            default     => $msg->status === 'success'
                                ? ['color' => 'secondary', 'icon' => 'check', 'label' => __('Queued')]
                                : ['color' => 'danger',    'icon' => 'x-circle', 'label' => __('Failed')],
                        };
                    @endphp
                    <tr>
                        <td class="ps-3 text-muted small">{{ $msg->id }}</td>
                        <td class="small">{{ $msg->device?->body ?? '—' }}</td>
                        <td class="small font-monospace">{{ $msg->number }}</td>
                        <td class="small">
                            <span class="badge bg-light text-muted border me-1">{{ $msg->type }}</span>
                            {{ Str::limit($msg->message, 40) }}
                        </td>
                        <td>
                            <span class="badge bg-{{ $deliveryBadge['color'] }} bg-opacity-15 text-{{ $deliveryBadge['color'] }} border border-{{ $deliveryBadge['color'] }}" style="border-color:currentColor!important">
                                <i class="bi bi-{{ $deliveryBadge['icon'] }} me-1"></i>{{ $deliveryBadge['label'] }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $msg->send_by === 'web' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                {{ strtoupper($msg->send_by) }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $msg->created_at->format('d M Y, H:i') }}</td>
                        <td class="pe-3">
                            <button onclick="resend({{ $msg->id }}, '{{ $msg->status }}')"
                                    class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-repeat me-1"></i>{{ __('Resend') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
    @if($messages->hasPages())
    <div class="card-footer bg-transparent">
        {{ $messages->links() }}
    </div>
    @endif
</div>

{{-- Delete confirm modal --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Confirm Deletion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">{{ __('Are you sure you want to delete all message history?') }}</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" id="confirmDeleteButton" class="btn btn-danger">{{ __('Delete All') }}</button>
            </div>
        </div>
    </div>
</div>

</x-layout-dashboard>

<script>
function resend(id, status) {
    if (status === 'failed') {
        $.ajax({
            url: '{{ url('/resend-message') }}',
            type: 'POST',
            data: { id, _token: '{{ csrf_token() }}' },
            success: res => res.error ? toastr.error(res.msg) : toastr.success(res.msg),
            error:   ()  => toastr.error('{{ __("Something went wrong") }}'),
        });
    } else {
        toastr.info('{{ __("Message already sent") }}');
    }
}

let deleteAllId = null;
function deleteAll(id) {
    deleteAllId = id;
    new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
}

document.getElementById('confirmDeleteButton').addEventListener('click', function () {
    $.ajax({
        url: '{{ url('/delete-messages') }}',
        type: 'POST',
        data: { id: deleteAllId, _token: '{{ csrf_token() }}' },
        success: res => {
            if (res.error) { toastr.error(res.msg); return; }
            toastr.success(res.msg);
            setTimeout(() => location.reload(), 1200);
        },
        error:    () => toastr.error('{{ __("Something went wrong") }}'),
        complete: () => bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal')).hide(),
    });
});
</script>
