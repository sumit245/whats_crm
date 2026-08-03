<x-layout-dashboard title="{{ __('Ad Analytics') }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ __('Ad Analytics') }}"
    subtitle="{{ __('Cross-channel performance dashboard') }}"
    :breadcrumb="[__('Ads Manager'), __('Analytics')]" />

{{-- ── Filters ───────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('ads.analytics') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('From') }}</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('To') }}</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Channel') }}</label>
                <select name="channel_type" class="form-select form-select-sm">
                    <option value="">{{ __('All Channels') }}</option>
                    @foreach(['meta','facebook','instagram','telegram','email','linkedin'] as $ct)
                    <option value="{{ $ct }}" {{ $channelType === $ct ? 'selected' : '' }}>{{ ucfirst($ct) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Campaign') }}</label>
                <select name="campaign_id" class="form-select form-select-sm">
                    <option value="">{{ __('All Campaigns') }}</option>
                    @foreach($campaigns as $c)
                    <option value="{{ $c->id }}" {{ $campaignId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-1 align-items-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>{{ __('Filter') }}
                </button>
                <a href="{{ route('ads.analytics') }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Clear') }}">
                    <i class="bi bi-x-lg"></i>
                </a>
                <div class="ms-auto d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="syncAll()">
                        <i class="bi bi-arrow-repeat me-1"></i>{{ __('Sync All') }}
                    </button>
                    <a href="{{ route('ads.analytics.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>{{ __('Export CSV') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── KPI Cards ─────────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @php
        $kpis = [
            ['label' => __('Impressions'),  'value' => number_format($totals->impressions ?? 0),           'icon' => 'eye-fill',     'color' => 'primary'],
            ['label' => __('Reach'),        'value' => number_format($totals->reach ?? 0),                 'icon' => 'people-fill',  'color' => 'info'],
            ['label' => __('Clicks'),       'value' => number_format($totals->clicks ?? 0),                'icon' => 'cursor-fill',  'color' => 'success'],
            ['label' => __('CTR'),          'value' => $avgCtr . '%',                                      'icon' => 'percent',      'color' => 'secondary'],
            ['label' => __('Spend'),        'value' => '$' . number_format($totals->spend ?? 0, 2),        'icon' => 'cash-stack',   'color' => 'warning'],
            ['label' => __('CPM'),          'value' => '$' . number_format($avgCpm, 2),                    'icon' => 'broadcast',    'color' => 'secondary'],
            ['label' => __('CPC'),          'value' => '$' . number_format($avgCpc, 2),                    'icon' => 'cursor',       'color' => 'info'],
            ['label' => __('Conversions'),  'value' => number_format($totals->conversions ?? 0),           'icon' => 'check2-circle','color' => 'success'],
            ['label' => __('ROAS'),         'value' => $roas . 'x',                                        'icon' => 'graph-up-arrow','color' => 'danger'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm text-center py-3 h-100">
            <i class="bi bi-{{ $kpi['icon'] }} text-{{ $kpi['color'] }} fs-4 mb-1"></i>
            <div class="fw-bold">{{ $kpi['value'] }}</div>
            <div class="text-muted small">{{ $kpi['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
{{-- ── Performance over time chart ───────────────────────────────────────────── --}}
<div class="col-lg-8">
<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
        <span class="fw-semibold">{{ __('Performance Over Time') }}</span>
        <div class="btn-group btn-group-sm" id="chartToggle">
            <button class="btn btn-primary active" data-metric="clicks">{{ __('Clicks') }}</button>
            <button class="btn btn-outline-primary" data-metric="impressions">{{ __('Impressions') }}</button>
            <button class="btn btn-outline-primary" data-metric="spend">{{ __('Spend ($)') }}</button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="analyticsChart" height="100"></canvas>
    </div>
</div>
</div>

{{-- ── Channel breakdown ──────────────────────────────────────────────────────── --}}
<div class="col-lg-4">
<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-0">
        <span class="fw-semibold">{{ __('By Channel') }}</span>
    </div>
    <div class="card-body">
        @if($channelBreakdown->isEmpty())
        <div class="text-muted text-center small py-4">{{ __('No data') }}</div>
        @else
        <canvas id="channelChart" height="200"></canvas>
        <div class="mt-3">
            @php
            $chColors = ['meta'=>'#1877f2','facebook'=>'#1877f2','instagram'=>'#e1306c','telegram'=>'#0088cc','email'=>'#198754','linkedin'=>'#0a66c2'];
            @endphp
            @foreach($channelBreakdown as $row)
            @php $total = $channelBreakdown->sum('spend') ?: 1; $pct = round($row->spend / $total * 100); @endphp
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:10px;height:10px;border-radius:50%;background:{{ $chColors[$row->type] ?? '#6c757d' }};flex-shrink:0"></div>
                <div class="small fw-semibold flex-fill text-capitalize">{{ $row->type }}</div>
                <div class="small text-muted">${{ number_format($row->spend, 2) }}</div>
                <div class="badge bg-secondary bg-opacity-15 text-secondary" style="font-size:11px">{{ $pct }}%</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</div>
</div>

{{-- ── Per-placement breakdown ────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
        <span class="fw-semibold">{{ __('Placement Breakdown') }}</span>
        <span class="text-muted small">{{ $placements->total() }} {{ __('placements') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ __('Campaign') }}</th>
                        <th>{{ __('Channel') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Impr.') }}</th>
                        <th class="text-end">{{ __('Clicks') }}</th>
                        <th class="text-end">{{ __('CTR') }}</th>
                        <th class="text-end">{{ __('Spend') }}</th>
                        <th class="text-end">{{ __('CPM') }}</th>
                        <th class="text-end">{{ __('CPC') }}</th>
                        <th class="text-end">{{ __('Conv.') }}</th>
                        <th class="pe-3 text-center">{{ __('Drill') }}</th>
                    </tr>
                </thead>
                <tbody>
                @if($placements->isEmpty())
                    <x-no-data colspan="12" text="{{ __('No data for selected filters') }}" />
                @else
                    @foreach($placements as $pl)
                    @php
                        $impr   = (int)   ($pl->total_impressions ?? 0);
                        $clicks = (int)   ($pl->total_clicks      ?? 0);
                        $spend  = (float) ($pl->total_spend       ?? 0);
                        $convs  = (int)   ($pl->total_conversions ?? 0);
                        $ctr    = $impr   > 0 ? round($clicks / $impr * 100, 2)    : 0;
                        $cpm    = $impr   > 0 ? round($spend  / $impr * 1000, 2)   : 0;
                        $cpc    = $clicks > 0 ? round($spend  / $clicks, 2)         : 0;
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('ads.campaigns.show', $pl->ad_campaign_id) }}" class="small fw-semibold text-decoration-none">
                                {{ $pl->campaign?->name ?? '—' }}
                            </a>
                        </td>
                        <td>
                            @if($pl->channel)
                            <i class="bi {{ $pl->channel->typeIcon() }} text-{{ $pl->channel->typeColor() }}"></i>
                            <span class="small ms-1">{{ $pl->channel->typeLabel() }}</span>
                            @else —
                            @endif
                        </td>
                        <td class="small text-muted">{{ ucfirst(str_replace('_', ' ', $pl->placement_type)) }}</td>
                        <td>
                            <span class="badge bg-{{ $pl->statusColor() }} bg-opacity-15 text-{{ $pl->statusColor() }}" style="font-size:11px">
                                {{ ucfirst($pl->status) }}
                            </span>
                        </td>
                        <td class="small text-end">{{ number_format($impr) }}</td>
                        <td class="small text-end">{{ number_format($clicks) }}</td>
                        <td class="small text-end {{ $ctr > 2 ? 'text-success fw-semibold' : '' }}">{{ $ctr }}%</td>
                        <td class="small text-end fw-semibold">${{ number_format($spend, 2) }}</td>
                        <td class="small text-end text-muted">${{ $cpm }}</td>
                        <td class="small text-end text-muted">${{ $cpc }}</td>
                        <td class="small text-end">{{ number_format($convs) }}</td>
                        <td class="pe-3 text-center">
                            <button class="btn btn-xs btn-outline-primary"
                                style="font-size:11px;padding:2px 8px"
                                onclick="drillDown({{ $pl->id }}, '{{ addslashes($pl->campaign?->name) }}', '{{ addslashes($pl->channel?->typeLabel()) }}')"
                                title="{{ __('View daily breakdown') }}">
                                <i class="bi bi-graph-up"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
    @if($placements->hasPages())
    <div class="card-footer bg-transparent">{{ $placements->links() }}</div>
    @endif
</div>

{{-- ── Drill-down modal ───────────────────────────────────────────────────────── --}}
<div class="modal fade" id="drillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="drillTitle">{{ __('Placement Analytics') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="drillKpis" class="row g-2 mb-3"></div>
                <div class="btn-group btn-group-sm mb-3" id="drillToggle">
                    <button class="btn btn-primary active" data-metric="clicks">{{ __('Clicks') }}</button>
                    <button class="btn btn-outline-primary" data-metric="impressions">{{ __('Impressions') }}</button>
                    <button class="btn btn-outline-primary" data-metric="spend">{{ __('Spend ($)') }}</button>
                    <button class="btn btn-outline-primary" data-metric="ctr">{{ __('CTR (%)') }}</button>
                </div>
                <div id="drillLoading" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm me-2"></div>{{ __('Loading…') }}
                </div>
                <canvas id="drillChart" height="100" class="d-none"></canvas>
                <div id="drillTableWrap" class="mt-3 d-none">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th class="text-end">{{ __('Impressions') }}</th>
                                <th class="text-end">{{ __('Clicks') }}</th>
                                <th class="text-end">{{ __('CTR') }}</th>
                                <th class="text-end">{{ __('Spend') }}</th>
                                <th class="text-end">{{ __('CPM') }}</th>
                                <th class="text-end">{{ __('CPC') }}</th>
                            </tr>
                        </thead>
                        <tbody id="drillTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layout-dashboard>

<script>
// ── Main performance chart ──────────────────────────────────────────────────
const chartData = @json($chartDates);

const ctx = document.getElementById('analyticsChart').getContext('2d');
const mainChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.map(d => d.date),
        datasets: [{
            label: '{{ __("Clicks") }}',
            data: chartData.map(d => d.clicks),
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.08)',
            tension: 0.3, fill: true, pointRadius: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { ticks: { maxTicksLimit: 12, maxRotation: 0 } }
        }
    }
});

document.querySelectorAll('#chartToggle button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#chartToggle button').forEach(b => {
            b.classList.remove('active','btn-primary');
            b.classList.add('btn-outline-primary');
        });
        this.classList.remove('btn-outline-primary');
        this.classList.add('active','btn-primary');
        mainChart.data.datasets[0].data  = chartData.map(d => d[this.dataset.metric]);
        mainChart.data.datasets[0].label = this.textContent.trim();
        mainChart.update();
    });
});

// ── Channel breakdown doughnut ─────────────────────────────────────────────
@if(!$channelBreakdown->isEmpty())
const channelData = @json($channelBreakdown);
const chColors = {meta:'#1877f2',facebook:'#1877f2',instagram:'#e1306c',telegram:'#0088cc',email:'#198754',linkedin:'#0a66c2'};
new Chart(document.getElementById('channelChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: channelData.map(d => d.type.charAt(0).toUpperCase() + d.type.slice(1)),
        datasets: [{
            data: channelData.map(d => parseFloat(d.spend) || 0),
            backgroundColor: channelData.map(d => chColors[d.type] || '#6c757d'),
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});
@endif

// ── Drill-down ──────────────────────────────────────────────────────────────
let drillChartInstance = null;
let drillMetrics = [];

function drillDown(placementId, campaign, channel) {
    document.getElementById('drillTitle').textContent = campaign + ' — ' + channel;
    document.getElementById('drillLoading').classList.remove('d-none');
    document.getElementById('drillChart').classList.add('d-none');
    document.getElementById('drillTableWrap').classList.add('d-none');
    document.getElementById('drillKpis').innerHTML = '';
    new bootstrap.Modal(document.getElementById('drillModal')).show();

    $.getJSON('{{ url("") }}/{{ app()->getLocale() }}/ads/analytics/' + placementId, function (res) {
        drillMetrics = res.metrics || [];
        document.getElementById('drillLoading').classList.add('d-none');

        // KPIs
        const totImpr  = drillMetrics.reduce((s, r) => s + (r.impressions || 0), 0);
        const totClick = drillMetrics.reduce((s, r) => s + (r.clicks || 0), 0);
        const totSpend = drillMetrics.reduce((s, r) => s + parseFloat(r.spend || 0), 0);
        const avgCtr   = totImpr > 0 ? (totClick / totImpr * 100).toFixed(2) : '0.00';
        const kpiDefs  = [
            {label:'Impressions', value: totImpr.toLocaleString()},
            {label:'Clicks',      value: totClick.toLocaleString()},
            {label:'CTR',         value: avgCtr + '%'},
            {label:'Spend',       value: '$' + totSpend.toFixed(2)},
        ];
        document.getElementById('drillKpis').innerHTML = kpiDefs.map(k => `
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light text-center py-2">
                    <div class="fw-bold">${k.value}</div>
                    <div class="text-muted small">${k.label}</div>
                </div>
            </div>`).join('');

        // Chart
        document.getElementById('drillChart').classList.remove('d-none');
        if (drillChartInstance) drillChartInstance.destroy();
        drillChartInstance = new Chart(document.getElementById('drillChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: drillMetrics.map(r => r.date),
                datasets: [{
                    label: 'Clicks',
                    data: drillMetrics.map(r => r.clicks),
                    backgroundColor: 'rgba(13,110,253,0.7)',
                    borderRadius: 3,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true }, x: { ticks: { maxRotation: 0, maxTicksLimit: 14 } } }
            }
        });

        // Daily table
        document.getElementById('drillTableWrap').classList.remove('d-none');
        document.getElementById('drillTableBody').innerHTML = drillMetrics.map(r => {
            const ctr = r.impressions > 0 ? (r.clicks / r.impressions * 100).toFixed(2) : '—';
            const cpm = r.impressions > 0 ? (r.spend / r.impressions * 1000).toFixed(2) : '—';
            const cpc = r.clicks > 0 ? (r.spend / r.clicks).toFixed(2) : '—';
            return `<tr>
                <td class="small">${r.date}</td>
                <td class="small text-end">${(r.impressions||0).toLocaleString()}</td>
                <td class="small text-end">${(r.clicks||0).toLocaleString()}</td>
                <td class="small text-end">${ctr}%</td>
                <td class="small text-end">$${parseFloat(r.spend||0).toFixed(2)}</td>
                <td class="small text-end text-muted">${cpm !== '—' ? '$' + cpm : '—'}</td>
                <td class="small text-end text-muted">${cpc !== '—' ? '$' + cpc : '—'}</td>
            </tr>`;
        }).join('');
    }).fail(() => {
        document.getElementById('drillLoading').textContent = '{{ __("Failed to load data.") }}';
    });
}

// Drill chart metric toggle
document.querySelectorAll('#drillToggle button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#drillToggle button').forEach(b => {
            b.classList.remove('active','btn-primary');
            b.classList.add('btn-outline-primary');
        });
        this.classList.remove('btn-outline-primary');
        this.classList.add('active','btn-primary');
        const metric = this.dataset.metric;
        drillChartInstance.data.datasets[0].data  = drillMetrics.map(r => parseFloat(r[metric]) || 0);
        drillChartInstance.data.datasets[0].label = this.textContent.trim();
        drillChartInstance.update();
    });
});

// ── Sync all ────────────────────────────────────────────────────────────────
function syncAll() {
    $.ajax({
        url: '{{ route("ads.analytics.sync-all") }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => res.error ? toastr.error(res.msg) : toastr.success(res.msg),
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}
</script>
