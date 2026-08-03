<x-layout-dashboard title="{{ __('Ads Manager') }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ __('Ads Manager') }}"
    subtitle="{{ __('Multi-channel campaigns across Meta, Instagram, Facebook, LinkedIn, Telegram & Email') }}"
    :breadcrumb="[__('Ads Manager'), __('Dashboard')]" />

{{-- ── KPI Cards ─────────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @php
        $kpis = [
            ['label' => __('Active Channels'),   'value' => $channelCount,
             'icon'  => 'broadcast-pin',         'color' => 'primary'],
            ['label' => __('Total Campaigns'),   'value' => $campaignCount,
             'icon'  => 'megaphone-fill',         'color' => 'info'],
            ['label' => __('Impressions (30d)'), 'value' => number_format($metrics30->total_impressions ?? 0),
             'icon'  => 'eye-fill',               'color' => 'success'],
            ['label' => __('Clicks (30d)'),      'value' => number_format($metrics30->total_clicks ?? 0),
             'icon'  => 'cursor-fill',            'color' => 'warning'],
            ['label' => __('Spend (30d)'),       'value' => '$' . number_format($metrics30->total_spend ?? 0, 2),
             'icon'  => 'cash-stack',             'color' => 'danger'],
            ['label' => __('Conversions (30d)'), 'value' => number_format($metrics30->total_conversions ?? 0),
             'icon'  => 'check2-circle',          'color' => 'secondary'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm text-center py-3 h-100">
            <div class="mb-1">
                <i class="bi bi-{{ $kpi['icon'] }} text-{{ $kpi['color'] }} fs-4"></i>
            </div>
            <div class="fw-bold fs-5">{{ $kpi['value'] }}</div>
            <div class="text-muted small">{{ $kpi['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── 30-day Chart ──────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
                <span class="fw-semibold">{{ __('30-Day Performance') }}</span>
                <div class="btn-group btn-group-sm" id="chartToggle">
                    <button class="btn btn-primary active" data-metric="clicks">{{ __('Clicks') }}</button>
                    <button class="btn btn-outline-primary" data-metric="impressions">{{ __('Impressions') }}</button>
                    <button class="btn btn-outline-primary" data-metric="spend">{{ __('Spend ($)') }}</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="adsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <span class="fw-semibold">{{ __('Connected Channels') }}</span>
            </div>
            <div class="card-body p-0">
                @if($connectedChannels->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-plug fs-2 d-block mb-2"></i>
                    {{ __('No channels connected yet') }}
                    <div class="mt-2">
                        <a href="{{ route('ads.channels.index') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>{{ __('Connect Channel') }}
                        </a>
                    </div>
                </div>
                @else
                <ul class="list-group list-group-flush">
                    @foreach($connectedChannels as $ch)
                    <li class="list-group-item d-flex align-items-center gap-2 py-2">
                        <i class="bi {{ $ch->typeIcon() }} text-{{ $ch->typeColor() }} fs-5"></i>
                        <div class="flex-fill min-w-0">
                            <div class="fw-semibold small text-truncate">{{ $ch->name }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $ch->typeLabel() }}</div>
                        </div>
                        <span class="badge bg-success bg-opacity-15 text-success border border-success" style="font-size:10px">{{ __('Active') }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Recent Campaigns ─────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
        <span class="fw-semibold">{{ __('Recent Campaigns') }}</span>
        <a href="{{ route('ads.campaigns.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('New Campaign') }}
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ __('Campaign') }}</th>
                        <th>{{ __('Channels') }}</th>
                        <th>{{ __('Objective') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Impressions') }}</th>
                        <th>{{ __('Clicks') }}</th>
                        <th class="pe-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @if($recentCampaigns->isEmpty())
                    <x-no-data colspan="7" text="{{ __('No campaigns yet — create your first!') }}" />
                @else
                    @foreach($recentCampaigns as $camp)
                    @php
                        $totalImpr  = $camp->placements->sum(fn($p) => $p->cachedImpressions());
                        $totalClicks = $camp->placements->sum(fn($p) => $p->cachedClicks());
                    @endphp
                    <tr>
                        <td class="ps-3 fw-semibold small">{{ $camp->name }}</td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @foreach($camp->placements->unique('ad_channel_id') as $pl)
                                    @if($pl->channel)
                                    <i class="bi {{ $pl->channel->typeIcon() }} text-{{ $pl->channel->typeColor() }}" title="{{ $pl->channel->typeLabel() }}"></i>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                        <td class="small">{{ $camp->objectiveLabel() }}</td>
                        <td>
                            <span class="badge bg-{{ $camp->statusColor() }} bg-opacity-15 text-{{ $camp->statusColor() }} border border-{{ $camp->statusColor() }}" style="border-color:currentColor!important">
                                {{ ucfirst($camp->status) }}
                            </span>
                        </td>
                        <td class="small">{{ number_format($totalImpr) }}</td>
                        <td class="small">{{ number_format($totalClicks) }}</td>
                        <td class="pe-3">
                            <a href="{{ route('ads.campaigns.show', $camp) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
    @if($recentCampaigns->count() >= 10)
    <div class="card-footer bg-transparent text-end">
        <a href="{{ route('ads.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('View All Campaigns') }}</a>
    </div>
    @endif
</div>

</x-layout-dashboard>

<script>
const chartData = @json($dates);
let activeMetric = 'clicks';

const ctx = document.getElementById('adsChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels:   chartData.map(d => d.date),
        datasets: [{
            label:           '{{ __("Clicks") }}',
            data:            chartData.map(d => d.clicks),
            borderColor:     '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.08)',
            tension:         0.3,
            fill:            true,
            pointRadius:     2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { ticks: { maxTicksLimit: 10, maxRotation: 0 } }
        }
    }
});

document.querySelectorAll('#chartToggle button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#chartToggle button').forEach(b => b.classList.remove('active','btn-primary'));
        document.querySelectorAll('#chartToggle button').forEach(b => { if (!b.classList.contains('active')) b.classList.add('btn-outline-primary'); });
        this.classList.remove('btn-outline-primary');
        this.classList.add('active','btn-primary');

        const metric = this.dataset.metric;
        chart.data.datasets[0].data  = chartData.map(d => d[metric]);
        chart.data.datasets[0].label = this.textContent.trim();
        chart.update();
    });
});
</script>
