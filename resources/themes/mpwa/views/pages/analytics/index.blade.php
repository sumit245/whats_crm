<x-layout-dashboard title="{{ __('Analytics') }}">

    <x-page-header title="{{ __('Campaign Analytics') }}"
        subtitle="{{ __('Real-time delivery and engagement metrics from Meta Cloud API webhooks') }}"
        :breadcrumb="[__('Reports'), __('Analytics')]" />

            {{-- Summary Cards --}}
            <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-4 g-3 mb-4">
                <div class="col">
                    <div class="card rounded h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Total Campaigns') }}</p>
                                    <h3 class="mb-0">{{ number_format($totalCampaigns) }}</h3>
                                </div>
                                <div class="ms-auto widget-icon bg-primary text-white rounded">
                                    <i class="bi bi-broadcast"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Messages Tracked') }}</p>
                                    <h3 class="mb-0">{{ number_format($totalSent) }}</h3>
                                </div>
                                <div class="ms-auto widget-icon bg-info text-white rounded">
                                    <i class="bi bi-chat-left-text"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Delivery Rate') }}</p>
                                    <h3 class="mb-0">{{ $deliveryRate }}%</h3>
                                    <small class="text-muted">{{ __('Delivered / Sent') }}</small>
                                </div>
                                <div class="ms-auto widget-icon bg-success text-white rounded">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Read Rate') }}</p>
                                    <h3 class="mb-0">{{ $readRate }}%</h3>
                                    <small class="text-muted">{{ __('Read / Sent') }}</small>
                                </div>
                                <div class="ms-auto widget-icon bg-warning text-white rounded">
                                    <i class="bi bi-eye"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Click Rate') }}</p>
                                    <h3 class="mb-0">{{ $clickRate }}%</h3>
                                    <small class="text-muted">{{ number_format($uniqueClickers) }} {{ __('unique clickers') }}</small>
                                </div>
                                <div class="ms-auto widget-icon bg-danger text-white rounded">
                                    <i class="bi bi-cursor-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Time Series Chart --}}
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0">{{ __('30-Day Delivery Trend') }}</h6>
                </div>
                <div class="card-body">
                    <div id="deliveryChart" style="min-height:280px"></div>
                </div>
            </div>

            {{-- Campaign Breakdown Table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex align-items-center">
                    <h6 class="mb-0">{{ __('Campaign Breakdown') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Campaign') }}</th>
                                    <th>{{ __('Device') }}</th>
                                    <th>{{ __('Sent') }}</th>
                                    <th>{{ __('Delivered %') }}</th>
                                    <th>{{ __('Clicked') }}</th>
                                    <th>{{ __('Failed') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($campaigns as $campaign)
                                    @php
                                        $total    = $campaign->blasts_count ?: 1;
                                        $delivPct = round($campaign->blasts_success / $total * 100);
                                        $clicks   = $campaign->click_data;
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $campaign->name }}</strong></td>
                                        <td><small>{{ $campaign->device->meta_profile['verified_name'] ?? $campaign->device->body ?? '—' }}</small></td>
                                        <td>{{ number_format($campaign->blasts_count) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height:6px">
                                                    <div class="progress-bar bg-success" style="width:{{ $delivPct }}%"></div>
                                                </div>
                                                <small>{{ $delivPct }}%</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($clicks && $clicks->total_clicks > 0)
                                                <span class="badge bg-danger-subtle text-danger" title="{{ $clicks->total_clicks }} total clicks">
                                                    <i class="bi bi-cursor-fill me-1"></i>{{ number_format($clicks->unique_clickers) }}
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-danger-subtle text-danger">{{ number_format($campaign->blasts_failed) }}</span></td>
                                        <td>
                                            @php $sc = match($campaign->status) { 'completed' => 'success', 'processing' => 'primary', 'paused' => 'warning', 'failed' => 'danger', default => 'secondary' }; @endphp
                                            <span class="badge bg-{{ $sc }}">{{ ucfirst($campaign->status) }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary campaign-detail-btn"
                                                data-id="{{ $campaign->id }}"
                                                data-bs-toggle="modal" data-bs-target="#campaignDetailModal">
                                                <i class="bi bi-bar-chart"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">{{ __('No campaigns yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($campaigns->hasPages())
                    <div class="card-footer">{{ $campaigns->links() }}</div>
                @endif
            </div>

{{-- Campaign detail modal --}}
<div class="modal fade" id="campaignDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold" id="campaignDetailTitle">{{ __('Campaign Detail') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="campaignDetailChart" style="min-height:250px"></div>
                <div id="campaignLinksSection" class="d-none mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-semibold text-muted"><i class="bi bi-cursor-fill me-1"></i>{{ __('Tracked Links') }}</small>
                        <a id="campaignLinksBtn" href="#" class="btn btn-xs btn-outline-secondary btn-sm" target="_blank">{{ __('Full report') }}</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:0.82rem">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('URL') }}</th>
                                    <th class="text-end">{{ __('Clicks') }}</th>
                                    <th class="text-end">{{ __('Last click') }}</th>
                                </tr>
                            </thead>
                            <tbody id="campaignLinksBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Agent WFM Performance (Feature 3 §5.3) ─────────────────────────── --}}
@if(isset($agentMetrics) && $agentMetrics->isNotEmpty())
<div class="app-content px-4 pb-4">
    <div class="card">
        <div class="card-header fw-semibold small">
            <i class="bi bi-person-lines-fill me-2"></i>{{ __('Agent Performance (WFM)') }}
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Agent') }}</th>
                        <th class="text-center">{{ __('Resolved') }}</th>
                        <th class="text-center">{{ __('Avg FRT') }}<br><small class="text-muted fw-normal">({{ __('First Response Time') }})</small></th>
                        <th class="text-center">{{ __('Avg AHT') }}<br><small class="text-muted fw-normal">({{ __('Handling Time') }})</small></th>
                        <th class="text-center">{{ __('SLA Breaches') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agentMetrics as $m)
                    <tr>
                        <td class="fw-semibold">{{ $m->agent_name }}</td>
                        <td class="text-center">{{ number_format($m->total_resolved) }}</td>
                        <td class="text-center">
                            @if($m->avg_frt_minutes !== null)
                                <span class="{{ $m->avg_frt_minutes > 15 ? 'text-danger' : 'text-success' }} fw-semibold">
                                    {{ $m->avg_frt_minutes }} {{ __('min') }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($m->avg_aht_minutes !== null)
                                {{ $m->avg_aht_minutes }} {{ __('min') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($m->sla_breaches > 0)
                                <span class="badge bg-danger">{{ $m->sla_breaches }}</span>
                            @else
                                <span class="badge bg-success-subtle text-success">0</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<script src="{{ asset('assets/plugins/apexcharts-bundle/js/apexcharts.min.js') }}"></script>
<script>
// Build time-series data from PHP
const tsData = @json($timeSeries);
const dates = tsData.map(r => r.date);
const sentSeries = tsData.map(r => parseInt(r.sent || 0));
const delivSeries = tsData.map(r => parseInt(r.delivered || 0));
const readSeries = tsData.map(r => parseInt(r.read_count || 0));

const deliveryChart = new ApexCharts(document.querySelector('#deliveryChart'), {
    chart: { type: 'area', height: 280, toolbar: { show: false }, animations: { enabled: false } },
    series: [
        { name: '{{ __("Sent") }}',      data: sentSeries },
        { name: '{{ __("Delivered") }}', data: delivSeries },
        { name: '{{ __("Read") }}',      data: readSeries },
    ],
    xaxis: { categories: dates, labels: { rotate: -30 } },
    colors: ['#0d6efd', '#198754', '#ffc107'],
    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    legend: { position: 'top' },
    tooltip: { x: { format: 'MMM dd' } },
});
deliveryChart.render();

// Per-campaign detail chart
let detailChart = null;
$(document).on('click', '.campaign-detail-btn', function () {
    const id = $(this).data('id');
    $('#campaignLinksSection').addClass('d-none');
    $('#campaignLinksBody').empty();

    $.get('{{ url("analytics/campaign") }}/' + id, function (res) {
        $('#campaignDetailTitle').text(res.campaign);
        if (detailChart) detailChart.destroy();
        detailChart = new ApexCharts(document.querySelector('#campaignDetailChart'), {
            chart: { type: 'bar', height: 250, toolbar: { show: false } },
            series: [{ name: '{{ __("Messages") }}', data: res.data }],
            xaxis: { categories: res.labels },
            colors: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#fd7e14'],
            dataLabels: { enabled: true },
            plotOptions: { bar: { borderRadius: 4, distributed: true } },
            legend: { show: false },
        });
        detailChart.render();

        // Load link breakdown if campaign has tracked links
        if (res.has_links) {
            $.get(res.links_url, function (lr) {
                if (lr.links && lr.links.length) {
                    const tbody = $('#campaignLinksBody');
                    lr.links.forEach(function (l) {
                        const short = l.original_url.length > 60 ? l.original_url.substring(0, 57) + '…' : l.original_url;
                        const last  = l.last_click ? new Date(l.last_click).toLocaleString() : '—';
                        tbody.append(`<tr>
                            <td><a href="${l.original_url}" target="_blank" title="${l.original_url}" class="text-truncate d-inline-block" style="max-width:320px">${short}</a></td>
                            <td class="text-end fw-semibold">${l.total_clicks}</td>
                            <td class="text-end text-muted">${last}</td>
                        </tr>`);
                    });
                    $('#campaignLinksBtn').attr('href', res.links_url);
                    $('#campaignLinksSection').removeClass('d-none');
                }
            });
        }
    });
});
</script>

</x-layout-dashboard>
