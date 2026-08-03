<x-layout-dashboard title="{{ $campaign->name }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ $campaign->name }}"
    subtitle="{{ $campaign->objectiveLabel() }} • {{ ucfirst($campaign->status) }}"
    :breadcrumb="[__('Ads Manager'), __('Campaigns'), $campaign->name]" />

{{-- ── Action bar ────────────────────────────────────────────────────────────── --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    @if($campaign->status === 'draft')
    <button class="btn btn-success" onclick="launchCampaign()">
        <i class="bi bi-play-fill me-1"></i>{{ __('Launch Campaign') }}
    </button>
    @elseif($campaign->status === 'active')
    <button class="btn btn-warning" onclick="pauseCampaign()">
        <i class="bi bi-pause-fill me-1"></i>{{ __('Pause') }}
    </button>
    @endif
    <button class="btn btn-outline-primary" onclick="syncMetrics()">
        <i class="bi bi-arrow-repeat me-1"></i>{{ __('Sync Metrics') }}
    </button>
    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addPlacementModal">
        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Channel') }}
    </button>
    <a href="{{ route('ads.campaigns.index') }}" class="btn btn-outline-secondary ms-auto">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
    </a>
</div>

{{-- ── KPI cards ─────────────────────────────────────────────────────────────── --}}
@php
    $allImpr   = $campaign->placements->sum(fn($p) => $p->cachedImpressions());
    $allClicks = $campaign->placements->sum(fn($p) => $p->cachedClicks());
    $allSpend  = $campaign->placements->sum(fn($p) => $p->cachedSpend());
    $avgCtr    = $allImpr > 0 ? round($allClicks / $allImpr * 100, 2) : 0;
@endphp
<div class="row g-3 mb-4">
    @foreach([
        ['label' => __('Impressions'), 'value' => number_format($allImpr),             'icon' => 'eye-fill',    'color' => 'primary'],
        ['label' => __('Clicks'),      'value' => number_format($allClicks),            'icon' => 'cursor-fill', 'color' => 'success'],
        ['label' => __('CTR'),         'value' => $avgCtr . '%',                        'icon' => 'percent',     'color' => 'info'],
        ['label' => __('Spend'),       'value' => '$' . number_format($allSpend, 2),    'icon' => 'cash-stack',  'color' => 'warning'],
        ['label' => __('Placements'),  'value' => $campaign->placements->count(),       'icon' => 'broadcast',   'color' => 'secondary'],
    ] as $kpi)
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm text-center py-3 h-100">
            <i class="bi bi-{{ $kpi['icon'] }} text-{{ $kpi['color'] }} fs-4 mb-1"></i>
            <div class="fw-bold fs-5">{{ $kpi['value'] }}</div>
            <div class="text-muted small">{{ $kpi['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Chart ─────────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0">
        <span class="fw-semibold">{{ __('30-Day Performance') }}</span>
    </div>
    <div class="card-body">
        <canvas id="campChart" height="80"></canvas>
    </div>
</div>

{{-- ── Placements ────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
        <span class="fw-semibold">{{ __('Placements') }}</span>
        <span class="text-muted small">{{ $campaign->placements->count() }} {{ __('total') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ __('Channel') }}</th>
                        <th>{{ __('Creative') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Impr.') }}</th>
                        <th>{{ __('Clicks') }}</th>
                        <th>{{ __('CTR') }}</th>
                        <th>{{ __('Spend') }}</th>
                        <th>{{ __('Synced') }}</th>
                        <th class="pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($campaign->placements as $pl)
                <tr id="plRow{{ $pl->id }}">
                    <td class="ps-3">
                        @if($pl->channel)
                        <i class="bi {{ $pl->channel->typeIcon() }} text-{{ $pl->channel->typeColor() }} me-1"></i>
                        <div class="small fw-semibold d-inline">{{ $pl->channel->name }}</div>
                        <div class="text-muted d-block" style="font-size:11px">{{ ucfirst(str_replace('_', ' ', $pl->placement_type)) }}</div>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @if($pl->creative)
                        <span class="small text-truncate d-block" style="max-width:100px" title="{{ $pl->creative->name }}">{{ $pl->creative->name }}</span>
                        @else
                        <button class="btn btn-xs btn-outline-warning" style="font-size:11px;padding:2px 8px"
                            onclick="openAssignCreative({{ $pl->id }})">
                            <i class="bi bi-plus-lg"></i> {{ __('Assign') }}
                        </button>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $pl->statusColor() }} bg-opacity-15 text-{{ $pl->statusColor() }} border" style="border-color:currentColor!important">
                            {{ ucfirst($pl->status) }}
                        </span>
                        @if($pl->external_ad_id)
                        <div class="text-muted font-monospace d-block" style="font-size:10px;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $pl->external_ad_id }}">
                            {{ $pl->external_ad_id }}
                        </div>
                        @endif
                    </td>
                    <td class="small">{{ number_format($pl->cachedImpressions()) }}</td>
                    <td class="small">{{ number_format($pl->cachedClicks()) }}</td>
                    <td class="small">{{ $pl->cachedCtr() }}%</td>
                    <td class="small">${{ number_format($pl->cachedSpend(), 2) }}</td>
                    <td class="small text-muted">{{ $pl->last_synced_at?->diffForHumans() ?? '—' }}</td>
                    <td class="pe-3">
                        <div class="d-flex gap-1">
                            @if($pl->status === 'failed' || $pl->status === 'pending')
                            <button class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 8px"
                                onclick="retryPlacement({{ $pl->id }})" title="{{ __('Retry') }}">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                            @endif
                            @if($pl->status === 'active' && $pl->external_ad_id)
                            <span class="badge bg-success bg-opacity-15 text-success" style="font-size:10px">Live</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Placement Modal --}}
<div class="modal fade" id="addPlacementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Channel') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Channel') }} <span class="text-danger">*</span></label>
                    <select class="form-select" id="newPlacementChannel">
                        <option value="">{{ __('Select…') }}</option>
                        @foreach($availableChannels as $ch)
                        <option value="{{ $ch->id }}" data-type="{{ $ch->type }}">
                            {{ $ch->name }} ({{ $ch->typeLabel() }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Placement Type') }}</label>
                    <select class="form-select" id="newPlacementType">
                        <option value="feed">Feed</option>
                        <option value="stories">Stories</option>
                        <option value="reels">Reels</option>
                        <option value="sponsored">Sponsored</option>
                        <option value="ctwa">CTWA</option>
                        <option value="direct_message">Direct Message</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Creative') }}</label>
                    <select class="form-select" id="newPlacementCreative">
                        <option value="">{{ __('Use campaign default') }}</option>
                        @foreach($availableCreatives as $cr)
                        <option value="{{ $cr->id }}">{{ $cr->name }} ({{ ucfirst($cr->format) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Daily Budget Override') }} <span class="text-muted small">({{ __('optional') }})</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="newPlacementBudget" step="0.01" min="0" placeholder="{{ __('Leave blank to use campaign budget') }}">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="submitAddPlacement()">{{ __('Add & Queue') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Assign Creative Modal --}}
<div class="modal fade" id="assignCreativeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Assign Creative') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignPlacementId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Creative') }} <span class="text-danger">*</span></label>
                    <select class="form-select" id="assignCreativeSelect">
                        @foreach($availableCreatives as $cr)
                        <option value="{{ $cr->id }}">{{ $cr->name }} ({{ ucfirst($cr->format) }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="submitAssignCreative()">{{ __('Assign') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Campaign details ──────────────────────────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0"><span class="fw-semibold">{{ __('Campaign Details') }}</span></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>{{ __('Objective') }}</th><td>{{ $campaign->objectiveLabel() }}</td></tr>
                    <tr><th>{{ __('Status') }}</th><td><span class="badge bg-{{ $campaign->statusColor() }}">{{ ucfirst($campaign->status) }}</span></td></tr>
                    <tr><th>{{ __('Daily Budget') }}</th><td>{{ $campaign->budget_daily ? '$' . $campaign->budget_daily . ' ' . $campaign->currency : '—' }}</td></tr>
                    <tr><th>{{ __('Total Budget') }}</th><td>{{ $campaign->budget_total ? '$' . $campaign->budget_total . ' ' . $campaign->currency : '—' }}</td></tr>
                    <tr><th>{{ __('Bid Strategy') }}</th><td>{{ ucfirst(str_replace('_', ' ', $campaign->bid_strategy)) }}</td></tr>
                    <tr><th>{{ __('Start') }}</th><td>{{ $campaign->start_at?->format('d M Y, H:i') ?? '—' }}</td></tr>
                    <tr><th>{{ __('End') }}</th><td>{{ $campaign->end_at?->format('d M Y, H:i') ?? '—' }}</td></tr>
                    <tr><th>{{ __('Segment') }}</th><td>{{ $campaign->segment?->name ?? '—' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0"><span class="fw-semibold">{{ __('Audience') }}</span></div>
            <div class="card-body">
                @php $aud = $campaign->audience_settings ?? []; @endphp
                <table class="table table-sm mb-0">
                    <tr><th>{{ __('Age Range') }}</th><td>{{ ($aud['age_min'] ?? 18) }} – {{ ($aud['age_max'] ?? 65) }}</td></tr>
                    <tr><th>{{ __('Genders') }}</th><td>{{ empty($aud['genders']) ? __('All') : implode(', ', array_map(fn($g) => $g == 1 ? __('Male') : __('Female'), $aud['genders'])) }}</td></tr>
                    <tr><th>{{ __('Countries') }}</th><td>{{ empty($aud['locations']) ? __('Worldwide') : implode(', ', (array)$aud['locations']) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

</x-layout-dashboard>

<script>
const chartData = @json($chartDates);

new Chart(document.getElementById('campChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: chartData.map(d => d.date),
        datasets: [
            {
                label: '{{ __("Impressions") }}',
                data: chartData.map(d => d.impressions),
                backgroundColor: 'rgba(13,110,253,0.15)',
                borderColor: '#0d6efd',
                type: 'line',
                yAxisID: 'y1',
                tension: 0.3,
                fill: true,
                pointRadius: 2,
            },
            {
                label: '{{ __("Clicks") }}',
                data: chartData.map(d => d.clicks),
                backgroundColor: 'rgba(25,135,84,0.7)',
                yAxisID: 'y',
            },
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index' },
        plugins: { legend: { position: 'top' } },
        scales: {
            y:  { beginAtZero: true, position: 'left',  ticks: { precision: 0 } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } },
            x:  { ticks: { maxTicksLimit: 10, maxRotation: 0 } },
        }
    }
});

function launchCampaign() {
    if (!confirm('{{ __("Launch this campaign on all pending placements?") }}')) return;
    $.ajax({
        url: '{{ route("ads.campaigns.launch", $campaign) }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1500);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}

function pauseCampaign() {
    $.ajax({
        url: '{{ route("ads.campaigns.pause", $campaign) }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1200);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}

function syncMetrics() {
    toastr.info('{{ __("Syncing metrics...") }}');
    $.ajax({
        url: '{{ route("ads.campaigns.sync-metrics", $campaign) }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => res.error ? toastr.error(res.msg) : toastr.success(res.msg),
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}

function retryPlacement(placementId) {
    $.ajax({
        url: '{{ url("") }}/{{ app()->getLocale() }}/ads/campaigns/{{ $campaign->id }}/placements/' + placementId + '/retry',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1500);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}

function openAssignCreative(placementId) {
    document.getElementById('assignPlacementId').value = placementId;
    new bootstrap.Modal(document.getElementById('assignCreativeModal')).show();
}

function submitAssignCreative() {
    const placementId = document.getElementById('assignPlacementId').value;
    const creativeId  = document.getElementById('assignCreativeSelect').value;
    $.ajax({
        url: '{{ url("") }}/{{ app()->getLocale() }}/ads/campaigns/{{ $campaign->id }}/placements/' + placementId + '/creative',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}', ad_creative_id: creativeId },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1200);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}

function submitAddPlacement() {
    $.ajax({
        url: '{{ route("ads.campaigns.add-placement", $campaign) }}',
        type: 'POST',
        data: {
            _token:          '{{ csrf_token() }}',
            ad_channel_id:   document.getElementById('newPlacementChannel').value,
            placement_type:  document.getElementById('newPlacementType').value,
            ad_creative_id:  document.getElementById('newPlacementCreative').value || null,
            budget_override: document.getElementById('newPlacementBudget').value || null,
        },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1200);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}
</script>
