<x-layout-dashboard title="{{ __('Campaign Detail') }}">

@php
    $sc = match($campaign->status) {
        'completed' => 'success', 'processing' => 'primary',
        'paused'    => 'warning', 'failed'      => 'danger',
        default     => 'secondary'
    };
@endphp

<x-page-header title="{{ $campaign->name }}"
    :breadcrumb="[__('Reports'), __('Campaign'), __('Detail')]">
    <span class="badge bg-{{ $sc }}">{{ ucfirst($campaign->status) }}</span>
    @if($campaign->category)
        <span class="badge bg-primary">{{ $campaign->category }}</span>
    @endif
    <a href="{{ route('campaigns') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('Back') }}
    </a>
</x-page-header>

{{-- ── Summary stats ──────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @foreach([
        ['total',      'Total',      'secondary', 'groups'],
        ['sent',       'Sent',       'success',   'send'],
        ['delivered',  'Delivered',  'info',      'done_all'],
        ['read',       'Read',       'primary',   'visibility'],
        ['failed',     'Failed',     'danger',    'error'],
        ['suppressed', 'Suppressed', 'warning',   'block'],
    ] as [$key, $label, $color, $icon])
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <i class="material-icons-two-tone text-{{ $color }} mb-1">{{ $icon }}</i>
                <h4 class="mb-0 text-{{ $color }}">{{ number_format($funnel[$key] ?? 0) }}</h4>
                <p class="text-muted mb-0 small">{{ __($label) }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Live batch progress (only while processing) ─────────────────────────── --}}
@if(in_array($campaign->status, ['waiting', 'processing']))
<div class="card border-0 shadow-sm mb-4" id="live-progress-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-semibold mb-0"><i class="material-icons-two-tone me-1">sync</i> {{ __('Sending in Progress') }}</h6>
            <span id="live-pct-label" class="badge bg-primary">0%</span>
        </div>
        <div class="progress" style="height:12px">
            <div id="live-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:0%"></div>
        </div>
        <small class="text-muted mt-1 d-block" id="live-progress-detail"></small>
    </div>
</div>
<script>
(function () {
    var pollUrl = '{{ route('campaign.progress', $campaign->id) }}';
    var bar = document.getElementById('live-progress-bar');
    var label = document.getElementById('live-pct-label');
    var detail = document.getElementById('live-progress-detail');
    var card = document.getElementById('live-progress-card');

    function poll() {
        fetch(pollUrl, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(d => {
                var pct = d.progress || 0;
                bar.style.width = pct + '%';
                label.textContent = pct + '%';
                if (d.total) {
                    detail.textContent = (d.total - d.pending) + ' / ' + d.total + ' {{ __('messages sent') }}';
                }
                if (d.status === 'completed' || d.status === 'failed' || d.finished) {
                    bar.classList.remove('progress-bar-animated');
                    label.textContent = '100%';
                    bar.style.width = '100%';
                    setTimeout(function() { window.location.reload(); }, 1500);
                    return;
                }
                setTimeout(poll, 5000);
            })
            .catch(function() { setTimeout(poll, 10000); });
    }
    setTimeout(poll, 3000);
})();
</script>
@endif

{{-- ── Delivery funnel bar ─────────────────────────────────────────────────── --}}
@if(($funnel['total'] ?? 0) > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">{{ __('Delivery Funnel') }}</h6>
        @php
            $total = max(1, $funnel['total']);
            $bars  = [
                ['label' => __('Sent'),      'value' => $funnel['sent']      ?? 0, 'color' => 'success'],
                ['label' => __('Delivered'), 'value' => $funnel['delivered'] ?? 0, 'color' => 'info'],
                ['label' => __('Read'),      'value' => $funnel['read']      ?? 0, 'color' => 'primary'],
            ];
        @endphp
        @foreach($bars as $bar)
        @php $pct = round(($bar['value'] / $total) * 100, 1); @endphp
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="text-muted small" style="width:80px">{{ $bar['label'] }}</span>
            <div class="progress flex-grow-1" style="height:10px">
                <div class="progress-bar bg-{{ $bar['color'] }}" style="width:{{ $pct }}%"></div>
            </div>
            <span class="text-muted small" style="width:60px; text-align:right">
                {{ number_format($bar['value']) }} ({{ $pct }}%)
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Retarget panel (Phase D) ────────────────────────────────────────────── --}}
@if(($funnel['sent'] ?? 0) > 0)
<div class="card border-0 shadow-sm mb-4 border-start border-4 border-primary">
    <div class="card-body">
        <h6 class="fw-semibold mb-1"><i class="material-icons-two-tone me-1">replay</i> {{ __('Retarget Audience') }}</h6>
        <p class="text-muted small mb-3">
            {{ __('Create a new campaign targeting a sub-segment of this campaign\'s recipients based on their delivery status.') }}
        </p>
        <div class="d-flex flex-wrap gap-2">
            @php
                $notDelivered = ($funnel['sent'] ?? 0) - ($funnel['delivered'] ?? 0);
                $deliveredNotRead = ($funnel['delivered'] ?? 0) - ($funnel['read'] ?? 0);
                $readCount = $funnel['read'] ?? 0;
            @endphp

            @if($notDelivered > 0)
            <button class="btn btn-outline-danger btn-retarget" data-filter="not_delivered">
                <i class="material-icons me-1" style="font-size:16px">error_outline</i>
                {{ __('Not Delivered') }} ({{ number_format($notDelivered) }})
            </button>
            @endif

            @if($deliveredNotRead > 0)
            <button class="btn btn-outline-warning btn-retarget" data-filter="delivered_not_read">
                <i class="material-icons me-1" style="font-size:16px">mark_email_unread</i>
                {{ __('Delivered, Not Read') }} ({{ number_format($deliveredNotRead) }})
            </button>
            @endif

            @if($readCount > 0)
            <button class="btn btn-outline-primary btn-retarget" data-filter="read">
                <i class="material-icons me-1" style="font-size:16px">mark_email_read</i>
                {{ __('Read') }} ({{ number_format($readCount) }})
            </button>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── Blast list ──────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">{{ __('Recipients') }}</h6>
        <a href="{{ route('campaign.blasts', $campaign->id) }}" class="btn btn-sm btn-outline-secondary">
            {{ __('Full List') }}
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Recipient') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Meta WAMID') }}</th>
                        <th>{{ __('Queued At') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaign->blasts()->latest()->paginate(20) as $blast)
                    <tr>
                        <td class="font-monospace">{{ $blast->receiver }}</td>
                        <td>
                            @php
                                $bc = match($blast->status) {
                                    'success'    => 'success',
                                    'failed'     => 'danger',
                                    'pending'    => 'warning',
                                    'suppressed' => 'secondary',
                                    default      => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $bc }}">{{ ucfirst($blast->status) }}</span>
                        </td>
                        <td><small class="font-monospace text-muted">{{ $blast->meta_message_id ?? '—' }}</small></td>
                        <td><small>{{ $blast->created_at->format('M d, H:i') }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Retarget confirmation modal --}}
<div class="modal fade" id="retargetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Create Retarget Campaign') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="retarget-desc" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" id="retarget-confirm" class="btn btn-primary">
                    {{ __('Create Campaign') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    let activeFilter = null;

    document.querySelectorAll('.btn-retarget').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeFilter = this.dataset.filter;
            const labels = {
                not_delivered:    '{{ __('contacts who did not receive this message') }}',
                delivered_not_read:'{{ __('contacts who received but have not read this message') }}',
                read:             '{{ __('contacts who read this message') }}',
            };
            document.getElementById('retarget-desc').textContent =
                '{{ __('A new phonebook and campaign draft will be created for') }} ' + labels[activeFilter] + '.';
            new bootstrap.Modal(document.getElementById('retargetModal')).show();
        });
    });

    document.getElementById('retarget-confirm')?.addEventListener('click', function () {
        if (!activeFilter) return;
        this.disabled = true;
        this.textContent = '{{ __('Creating...') }}';

        fetch('{{ route('campaign.retarget', $campaign->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ filter: activeFilter }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.error) {
                alert(d.message);
                this.disabled = false;
                this.textContent = '{{ __('Create Campaign') }}';
            } else {
                window.location.href = d.redirect;
            }
        });
    });
})();
</script>

</x-layout-dashboard>
