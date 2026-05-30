<x-layout-dashboard title="{{ __('Analytics') }}">

    <x-page-header title="{{ __('Campaign Analytics') }}"
        subtitle="{{ __('Real-time delivery and engagement metrics from Meta Cloud API webhooks') }}"
        :breadcrumb="[__('Reports'), __('Analytics')]" />

            {{-- Summary Cards --}}
            <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-4 g-3 mb-4">
                <div class="col">
                    <div class="card rounded-4 h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Total Campaigns') }}</p>
                                    <h3 class="mb-0">{{ number_format($totalCampaigns) }}</h3>
                                </div>
                                <div class="ms-auto widget-icon bg-primary text-white rounded-3">
                                    <i class="bi bi-broadcast"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded-4 h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Messages Tracked') }}</p>
                                    <h3 class="mb-0">{{ number_format($totalSent) }}</h3>
                                </div>
                                <div class="ms-auto widget-icon bg-info text-white rounded-3">
                                    <i class="bi bi-chat-left-text"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded-4 h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Delivery Rate') }}</p>
                                    <h3 class="mb-0">{{ $deliveryRate }}%</h3>
                                    <small class="text-muted">{{ __('Delivered / Sent') }}</small>
                                </div>
                                <div class="ms-auto widget-icon bg-success text-white rounded-3">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded-4 h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">{{ __('Read Rate') }}</p>
                                    <h3 class="mb-0">{{ $readRate }}%</h3>
                                    <small class="text-muted">{{ __('Read / Sent') }}</small>
                                </div>
                                <div class="ms-auto widget-icon bg-warning text-white rounded-3">
                                    <i class="bi bi-eye"></i>
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
                                    <th>{{ __('Failed') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($campaigns as $campaign)
                                    @php
                                        $total = $campaign->blasts_count ?: 1;
                                        $delivPct = round($campaign->blasts_success / $total * 100);
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
                                        <td colspan="7" class="text-center text-muted py-4">{{ __('No campaigns yet.') }}</td>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="campaignDetailTitle">{{ __('Campaign Detail') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="campaignDetailChart" style="min-height:250px"></div>
            </div>
        </div>
    </div>
</div>

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
    $.get('{{ url("analytics/campaign") }}/' + id, function (res) {
        $('#campaignDetailTitle').text(res.campaign);
        if (detailChart) detailChart.destroy();
        detailChart = new ApexCharts(document.querySelector('#campaignDetailChart'), {
            chart: { type: 'bar', height: 250, toolbar: { show: false } },
            series: [{ name: '{{ __("Messages") }}', data: res.data }],
            xaxis: { categories: res.labels },
            colors: ['#0d6efd', '#198754', '#ffc107', '#dc3545'],
            dataLabels: { enabled: true },
            plotOptions: { bar: { borderRadius: 4, distributed: true } },
            legend: { show: false },
        });
        detailChart.render();
    });
});
</script>

</x-layout-dashboard>
