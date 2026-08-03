<x-layout-dashboard title="{{ $abTest->name }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ $abTest->name }}"
    subtitle="{{ $abTest->campaign?->name }} · {{ $abTest->winnerMetricLabel() }} · {{ ucfirst($abTest->status) }}"
    :breadcrumb="[__('Ads Manager'), __('A/B Tests'), $abTest->name]" />

{{-- ── Action bar ─────────────────────────────────────────────────────────────── --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    @if($abTest->status === 'draft')
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#launchModal">
        <i class="bi bi-play-fill me-1"></i>{{ __('Launch Test') }}
    </button>
    @elseif($abTest->status === 'running')
    <button class="btn btn-warning" onclick="decideNow()">
        <i class="bi bi-trophy me-1"></i>{{ __('Decide Winner Now') }}
    </button>
    @endif
    <a href="{{ route('ads.ab-tests.index') }}" class="btn btn-outline-secondary ms-auto">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
    </a>
    @if(in_array($abTest->status, ['draft', 'running']))
    <form method="POST" action="{{ route('ads.ab-tests.destroy', $abTest) }}"
          onsubmit="return confirm('{{ __('Cancel this test?') }}')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>{{ __('Cancel') }}</button>
    </form>
    @endif
</div>

{{-- ── Winner banner ──────────────────────────────────────────────────────────── --}}
@if($abTest->status === 'completed' && $abTest->winner)
<div class="alert alert-success d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-trophy-fill fs-3 text-warning"></i>
    <div>
        <div class="fw-bold">{{ __('Winner decided!') }}</div>
        <div class="small">
            {{ __('Winning creative:') }} <strong>{{ $abTest->winner->name }}</strong>
            · {{ __('Decided') }} {{ $abTest->decided_at?->diffForHumans() }}
        </div>
    </div>
</div>
@endif

{{-- ── Variant comparison cards ───────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @php $variantColors = ['A' => 'primary', 'B' => 'success', 'C' => 'warning', 'D' => 'info']; @endphp
    @foreach($abTest->variants as $variant)
    @php
        $stats  = $variantStats[$variant->id] ?? [];
        $impr   = $stats['impressions'] ?? 0;
        $clicks = $stats['clicks'] ?? 0;
        $spend  = $stats['spend'] ?? 0;
        $ctr    = $stats['ctr'] ?? 0;
        $convs  = $stats['conversions'] ?? 0;
        $isWinner = $abTest->winner_creative_id === $variant->ad_creative_id;
        $color = $variantColors[$variant->label] ?? 'secondary';
    @endphp
    <div class="col-md-6 col-xl">
        <div class="card border-0 shadow-sm h-100 {{ $isWinner ? 'border-success border-2' : '' }}" style="{{ $isWinner ? 'border:2px solid #198754!important' : '' }}">
            <div class="card-header bg-{{ $color }} bg-opacity-10 border-0 d-flex align-items-center gap-2">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $color }} text-white fw-bold flex-shrink-0"
                    style="width:28px;height:28px;font-size:13px">{{ $variant->label }}</div>
                <div class="fw-semibold text-truncate small flex-fill">{{ $variant->creative?->name ?? '—' }}</div>
                @if($isWinner)
                <i class="bi bi-trophy-fill text-warning" title="{{ __('Winner') }}"></i>
                @endif
                <span class="badge bg-{{ $color }} bg-opacity-20 text-{{ $color }}">{{ $variant->budget_split }}%</span>
            </div>
            <div class="card-body">
                @if($variant->creative?->firstMediaUrl())
                <img src="{{ $variant->creative->firstMediaUrl() }}" class="img-fluid rounded mb-3" style="max-height:120px;width:100%;object-fit:cover" alt="">
                @endif
                @if($variant->creative?->headline)
                <div class="small fw-semibold mb-1">{{ $variant->creative->headline }}</div>
                @endif
                @if($variant->creative?->body)
                <div class="small text-muted mb-3" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">{{ $variant->creative->body }}</div>
                @endif
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="fw-bold small">{{ number_format($impr) }}</div>
                        <div class="text-muted" style="font-size:10px">{{ __('Impr.') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold small {{ $ctr > 2 ? 'text-success' : '' }}">{{ $ctr }}%</div>
                        <div class="text-muted" style="font-size:10px">CTR</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold small">{{ number_format($clicks) }}</div>
                        <div class="text-muted" style="font-size:10px">{{ __('Clicks') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold small">${{ number_format($spend, 2) }}</div>
                        <div class="text-muted" style="font-size:10px">{{ __('Spend') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold small">{{ number_format($convs) }}</div>
                        <div class="text-muted" style="font-size:10px">{{ __('Conv.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Performance chart ──────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
        <span class="fw-semibold">{{ __('14-Day Performance') }}</span>
        <div class="btn-group btn-group-sm" id="chartToggle">
            <button class="btn btn-primary active" data-metric="clicks">{{ __('Clicks') }}</button>
            <button class="btn btn-outline-primary" data-metric="impressions">{{ __('Impressions') }}</button>
            <button class="btn btn-outline-primary" data-metric="spend">{{ __('Spend ($)') }}</button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="abChart" height="80"></canvas>
    </div>
</div>

{{-- ── Test details ───────────────────────────────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0"><span class="fw-semibold">{{ __('Test Details') }}</span></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>{{ __('Status') }}</th><td><span class="badge bg-{{ $abTest->statusColor() }}">{{ ucfirst($abTest->status) }}</span></td></tr>
                    <tr><th>{{ __('Campaign') }}</th><td>{{ $abTest->campaign?->name }}</td></tr>
                    <tr><th>{{ __('Winner Metric') }}</th><td>{{ $abTest->winnerMetricLabel() }}</td></tr>
                    <tr><th>{{ __('Decide After') }}</th><td>{{ $abTest->decide_after_hours }} {{ __('hours') }}</td></tr>
                    @if($abTest->launched_at)
                    <tr><th>{{ __('Launched') }}</th><td>{{ $abTest->launched_at->format('d M Y, H:i') }}</td></tr>
                    <tr><th>{{ __('Expected decision') }}</th><td>{{ $abTest->launched_at->addHours($abTest->decide_after_hours)->format('d M Y, H:i') }}</td></tr>
                    @endif
                    @if($abTest->decided_at)
                    <tr><th>{{ __('Decided') }}</th><td>{{ $abTest->decided_at->format('d M Y, H:i') }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0"><span class="fw-semibold">{{ __('Variant Placements') }}</span></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th class="ps-3">{{ __('Variant') }}</th><th>{{ __('Channel') }}</th><th>{{ __('Status') }}</th><th class="pe-3">{{ __('Ext. ID') }}</th></tr>
                    </thead>
                    <tbody>
                    @foreach($abTest->variants as $variant)
                        @php
                        $varPlacements = \App\Models\AdPlacement::where('ad_ab_variant_id', $variant->id)->with('channel')->get();
                        @endphp
                        @foreach($varPlacements as $pl)
                        <tr>
                            <td class="ps-3">
                                <span class="badge bg-{{ $variantColors[$variant->label] ?? 'secondary' }}">{{ $variant->label }}</span>
                            </td>
                            <td class="small">
                                @if($pl->channel)
                                <i class="bi {{ $pl->channel->typeIcon() }} text-{{ $pl->channel->typeColor() }}"></i>
                                {{ $pl->channel->name }}
                                @else —
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $pl->statusColor() }} bg-opacity-15 text-{{ $pl->statusColor() }}" style="font-size:10px">{{ ucfirst($pl->status) }}</span>
                            </td>
                            <td class="pe-3 small font-monospace text-muted" style="max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $pl->external_ad_id ?? '—' }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Launch modal --}}
@if($abTest->status === 'draft')
<div class="modal fade" id="launchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Launch A/B Test') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Channels') }} <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        @php
                        $channels = \App\Models\AdChannel::where('user_id', auth()->id())->where('status', 'active')->get();
                        @endphp
                        @foreach($channels as $ch)
                        <div class="col-6">
                            <label class="d-flex align-items-center gap-2 border rounded p-2" style="cursor:pointer">
                                <input type="checkbox" class="launch-channel-cb" value="{{ $ch->id }}">
                                <i class="bi {{ $ch->typeIcon() }} text-{{ $ch->typeColor() }}"></i>
                                <span class="small">{{ $ch->name }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Placement Type') }}</label>
                    <select class="form-select" id="launchPlacementType">
                        <option value="feed">Feed</option>
                        <option value="stories">Stories</option>
                        <option value="reels">Reels</option>
                        <option value="sponsored">Sponsored</option>
                        <option value="direct_message">Direct Message</option>
                    </select>
                </div>
                <div class="alert alert-info small py-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ __('Each variant will be launched as a separate placement per channel. Winner decided after :h hours by :m.', ['h' => $abTest->decide_after_hours, 'm' => $abTest->winnerMetricLabel()]) }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-success" onclick="submitLaunch()">
                    <i class="bi bi-play-fill me-1"></i>{{ __('Launch') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

</x-layout-dashboard>

<script>
// ── Performance chart ────────────────────────────────────────────────────────
const variantSeries = @json($variantTimeSeries);
const variantMeta   = @json($abTest->variants->map(fn($v) => ['id' => $v->id, 'label' => $v->label]));
const palette = { A: '#0d6efd', B: '#198754', C: '#ffc107', D: '#0dcaf0' };

const allDates = Object.values(variantSeries)[0]?.map(r => r.date) ?? [];

const chartDatasets = variantMeta.map(v => ({
    label: 'Variant ' + v.label,
    data: (variantSeries[v.id] ?? []).map(r => r.clicks),
    borderColor: palette[v.label] || '#6c757d',
    backgroundColor: (palette[v.label] || '#6c757d') + '22',
    tension: 0.3, fill: true, pointRadius: 2,
}));

const abChart = new Chart(document.getElementById('abChart').getContext('2d'), {
    type: 'line',
    data: { labels: allDates, datasets: chartDatasets },
    options: {
        responsive: true,
        interaction: { mode: 'index' },
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { ticks: { maxTicksLimit: 14, maxRotation: 0 } }
        }
    }
});

document.querySelectorAll('#chartToggle button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#chartToggle button').forEach(b => {
            b.classList.remove('active','btn-primary'); b.classList.add('btn-outline-primary');
        });
        this.classList.remove('btn-outline-primary'); this.classList.add('active','btn-primary');
        const m = this.dataset.metric;
        abChart.data.datasets.forEach((ds, i) => {
            ds.data = (variantSeries[variantMeta[i].id] ?? []).map(r => parseFloat(r[m]) || 0);
        });
        abChart.update();
    });
});

// ── Launch ───────────────────────────────────────────────────────────────────
function submitLaunch() {
    const channelIds = [...document.querySelectorAll('.launch-channel-cb:checked')].map(c => c.value);
    if (!channelIds.length) { toastr.warning('{{ __("Select at least one channel.") }}'); return; }
    const placementType = document.getElementById('launchPlacementType').value;

    $.ajax({
        url: '{{ route("ads.ab-tests.launch", $abTest) }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}', channel_ids: channelIds, placement_type: placementType },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1500);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}

// ── Decide now ───────────────────────────────────────────────────────────────
function decideNow() {
    if (!confirm('{{ __("Force-decide the winner now based on current metrics?") }}')) return;
    $.ajax({
        url: '{{ route("ads.ab-tests.decide", $abTest) }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1500);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}
</script>
