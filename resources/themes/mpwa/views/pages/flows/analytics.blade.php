<x-layout-dashboard title="{{ $flow->name }} — {{ __('Analytics') }}">

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('flows.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>{{ $flow->name }}</h5>
        <span class="text-muted small">{{ __('Flow Analytics') }}</span>
    </div>
    <span class="badge {{ $flow->status === 'active' ? 'bg-success' : 'bg-secondary' }} fs-6">
        {{ ucfirst($flow->status) }}
    </span>
    <a href="{{ route('flows.edit', $flow->id) }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-pencil-fill me-1"></i>{{ __('Edit Flow') }}
    </a>
</div>

{{-- ── Summary stat cards ───────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 lh-1">{{ number_format($totalSessions) }}</div>
                    <div class="text-muted" style="font-size:0.75rem">{{ __('Total Sessions') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 lh-1">{{ number_format($completedCount) }}</div>
                    <div class="text-muted" style="font-size:0.75rem">{{ __('Completed') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0">
                    <i class="bi bi-person-fill-check"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 lh-1">{{ number_format($handoffCount) }}</div>
                    <div class="text-muted" style="font-size:0.75rem">{{ __('Human Handoffs') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0">
                    <i class="bi bi-percent"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 lh-1">{{ $completionRate }}%</div>
                    <div class="text-muted" style="font-size:0.75rem">{{ __('Completion Rate') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- ── Session volume chart ─────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-transparent border-bottom d-flex align-items-center justify-content-between">
                <span class="small fw-semibold"><i class="bi bi-graph-up me-1"></i>{{ __('Sessions (Last 30 Days)') }}</span>
                <span class="text-muted small">{{ __('Daily sessions started') }}</span>
            </div>
            <div class="card-body">
                @if($totalSessions === 0)
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-graph-up d-block mb-2" style="font-size:2.5rem"></i>
                        {{ __('No sessions recorded yet. Activate the flow and wait for contacts to trigger it.') }}
                    </div>
                @else
                <canvas id="sessionChart" height="100"></canvas>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Completion funnel ────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-transparent border-bottom">
                <span class="small fw-semibold"><i class="bi bi-funnel-fill me-1"></i>{{ __('Session Outcomes') }}</span>
            </div>
            <div class="card-body">
                @if($totalSessions === 0)
                    <div class="text-center text-muted py-4 small">{{ __('No data yet') }}</div>
                @else
                <canvas id="outcomeChart" height="160"></canvas>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Node funnel table ─────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header py-2 bg-transparent border-bottom d-flex align-items-center justify-content-between">
        <span class="small fw-semibold"><i class="bi bi-diagram-3-fill me-1"></i>{{ __('Node Visit Funnel') }}</span>
        <span class="text-muted small">{{ $nodeVisits->count() }} {{ __('nodes touched') }}</span>
    </div>
    <div class="card-body p-0">
        @if($nodeVisits->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-diagram-3 d-block mb-2" style="font-size:2.5rem"></i>
                {{ __('No node events recorded yet.') }}
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Node') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th class="text-end">{{ __('Total Visits') }}</th>
                        <th class="text-end">{{ __('Unique Sessions') }}</th>
                        <th style="min-width:160px">{{ __('Reach Rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($nodeVisits as $row)
                    <tr>
                        <td>
                            <span class="fw-semibold" style="font-size:0.88rem">{{ $row->label }}</span>
                            <span class="text-muted ms-1 font-monospace" style="font-size:0.72rem">#{{ $row->node_id }}</span>
                        </td>
                        <td>
                            @php
                                $typeColor = match(true) {
                                    str_starts_with($row->node_type, 'trigger') => 'bg-primary-subtle text-primary',
                                    str_starts_with($row->node_type, 'send')    => 'bg-info-subtle text-info',
                                    $row->node_type === 'ask_input'             => 'bg-warning-subtle text-warning',
                                    $row->node_type === 'condition'             => 'bg-secondary-subtle text-secondary',
                                    $row->node_type === 'human_handoff'         => 'bg-danger-subtle text-danger',
                                    $row->node_type === 'end_flow'              => 'bg-success-subtle text-success',
                                    default                                     => 'bg-light text-dark',
                                };
                            @endphp
                            <span class="badge {{ $typeColor }}" style="font-size:0.72rem">{{ str_replace('_', ' ', $row->node_type) }}</span>
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($row->visits) }}</td>
                        <td class="text-end">{{ number_format($row->unique_sessions) }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px">
                                    <div class="progress-bar bg-primary" style="width:{{ $row->reach_rate }}%"></div>
                                </div>
                                <span class="text-muted" style="font-size:0.78rem;min-width:38px">{{ $row->reach_rate }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@if($totalSessions > 0)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const dayLabels = @json($dayLabels);
    const dayCounts = @json($days->values());

    // Session volume line chart
    new Chart(document.getElementById('sessionChart'), {
        type: 'bar',
        data: {
            labels: dayLabels,
            datasets: [{
                label: '{{ __("Sessions") }}',
                data: dayCounts,
                backgroundColor: 'rgba(99,102,241,0.6)',
                borderColor: 'rgba(99,102,241,1)',
                borderWidth: 1,
                borderRadius: 3,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { ticks: { maxTicksLimit: 10, maxRotation: 30 } }
            }
        }
    });

    // Outcomes doughnut
    const total     = {{ $totalSessions }};
    const completed = {{ $completedCount }};
    const handoff   = {{ $handoffCount }};
    const other     = total - completed - handoff;

    new Chart(document.getElementById('outcomeChart'), {
        type: 'doughnut',
        data: {
            labels: ['{{ __("Completed") }}', '{{ __("Human Handoff") }}', '{{ __("Other/Expired") }}'],
            datasets: [{
                data: [completed, handoff, other < 0 ? 0 : other],
                backgroundColor: ['#198754','#0dcaf0','#adb5bd'],
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 } } }
            }
        }
    });
})();
</script>
@endpush
@endif

</x-layout-dashboard>
