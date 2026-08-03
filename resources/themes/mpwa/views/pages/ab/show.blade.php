<x-layout-dashboard title="{{ $test->name }} — {{ __('A/B Results') }}">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('ab.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>{{ $test->name }}</h5>
        <span class="text-muted small">{{ $test->phonebook?->name }} &middot; {{ $contactCount }} {{ __('contacts') }}</span>
    </div>
    @php
        $sc = match($test->status) {
            'running'   => 'bg-primary',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger',
            default     => 'bg-secondary',
        };
    @endphp
    <span class="badge {{ $sc }} fs-6">{{ ucfirst($test->status) }}</span>
    @if($test->status === 'draft')
    <form method="POST" action="{{ route('ab.launch', $test->id) }}">
        @csrf
        <button class="btn btn-sm btn-success">
            <i class="bi bi-play-fill me-1"></i>{{ __('Launch Test') }}
        </button>
    </form>
    @endif
</div>

{{-- ── Test summary strip ────────────────────────────────────────────────── --}}
<div class="row g-2 mb-4">
    @php
        $metricLabel = match($test->winner_metric) {
            'read_rate'     => __('Read rate'),
            'delivery_rate' => __('Delivery rate'),
            'click_rate'    => __('Click rate'),
            default         => $test->winner_metric,
        };
    @endphp
    @foreach([
        ['label' => __('Winner metric'), 'value' => $metricLabel,                          'icon' => 'bi-trophy-fill',    'color' => 'warning'],
        ['label' => __('Decision window'), 'value' => $test->decide_after_hours . ' ' . __('hours'), 'icon' => 'bi-clock-fill',     'color' => 'info'],
        ['label' => __('Holdout'),         'value' => $test->holdout_percent . '%',         'icon' => 'bi-people-fill',    'color' => 'secondary'],
        ['label' => __('Decide at'),       'value' => $test->decide_at ? $test->decide_at->format('M d, H:i') : '—', 'icon' => 'bi-calendar-check', 'color' => 'primary'],
    ] as $s)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-{{ $s['color'] }} bg-opacity-10 text-{{ $s['color'] }} d-flex align-items-center justify-content-center" style="width:38px;height:38px;flex-shrink:0">
                    <i class="bi {{ $s['icon'] }}"></i>
                </div>
                <div>
                    <div class="fw-bold lh-1">{{ $s['value'] }}</div>
                    <div class="text-muted" style="font-size:0.72rem">{{ $s['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Winner banner ────────────────────────────────────────────────────── --}}
@if($test->status === 'completed' && $test->winner_variant_id)
@php $winner = $test->winnerVariant; @endphp
<div class="alert alert-success d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-trophy-fill fs-4 text-warning"></i>
    <div>
        <strong>{{ __('Winner: Variant') }} {{ $winner?->label }}</strong> —
        {{ $metricLabel }}: <strong>{{ $variantMetrics[$winner?->id][$test->winner_metric] ?? 0 }}%</strong>
        @if($test->winner_campaign_id)
        &middot; {{ __('Winner message sent to holdout contacts.') }}
        @endif
    </div>
</div>
@endif

{{-- ── Variant comparison ───────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @foreach($test->variants as $variant)
    @php
        $m          = $variantMetrics[$variant->id] ?? [];
        $isWinner   = $test->winner_variant_id == $variant->id;
        $metricVal  = $m[$test->winner_metric] ?? 0;
    @endphp
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm {{ $isWinner ? 'border-success border-2' : '' }} h-100">
            <div class="card-header py-2 bg-transparent border-bottom d-flex align-items-center gap-2">
                <span class="badge bg-primary px-3 py-1 fs-6">{{ __('Variant') }} {{ $variant->label }}</span>
                @if($isWinner)
                <span class="badge bg-success"><i class="bi bi-trophy-fill me-1"></i>{{ __('Winner') }}</span>
                @endif
                <span class="ms-auto text-muted small">
                    {{ $variant->message_type === 'template' ? ($variant->template?->name ?? __('Template')) : __('Text') }}
                </span>
            </div>
            <div class="card-body">
                {{-- Message preview --}}
                <div class="bg-light rounded p-2 mb-3" style="font-size:0.82rem;min-height:50px">
                    @if($variant->message_type === 'template')
                        <i class="bi bi-layout-text-sidebar-reverse me-1 text-info"></i>
                        <strong>{{ $variant->template?->name }}</strong>
                        <span class="text-muted">({{ $variant->template?->language }})</span>
                    @else
                        {{ Str::limit($variant->message_body, 160) }}
                    @endif
                </div>

                {{-- Metrics --}}
                @php
                    $statsRow = [
                        ['label' => __('Sent'),       'value' => $m['sent']      ?? 0, 'color' => 'secondary'],
                        ['label' => __('Delivered'),  'value' => $m['delivered'] ?? 0, 'color' => 'info'],
                        ['label' => __('Read'),       'value' => $m['read']      ?? 0, 'color' => 'success'],
                        ['label' => __('Clicked'),    'value' => $m['clicks']    ?? 0, 'color' => 'danger'],
                    ];
                @endphp
                <div class="row g-2 text-center mb-3">
                    @foreach($statsRow as $st)
                    <div class="col-3">
                        <div class="fw-bold text-{{ $st['color'] }}">{{ number_format($st['value']) }}</div>
                        <div class="text-muted" style="font-size:0.7rem">{{ $st['label'] }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Rate bars --}}
                @php
                    $rates = [
                        ['label' => __('Delivery rate'), 'val' => $m['delivery_rate'] ?? 0, 'color' => 'info'],
                        ['label' => __('Read rate'),     'val' => $m['read_rate']     ?? 0, 'color' => 'success'],
                        ['label' => __('Click rate'),    'val' => $m['click_rate']    ?? 0, 'color' => 'danger'],
                    ];
                @endphp
                @foreach($rates as $r)
                <div class="d-flex align-items-center gap-2 mb-1" style="font-size:0.8rem">
                    <span class="text-muted" style="min-width:90px">{{ $r['label'] }}</span>
                    <div class="progress flex-grow-1" style="height:8px">
                        <div class="progress-bar bg-{{ $r['color'] }}" style="width:{{ $r['val'] }}%"></div>
                    </div>
                    <span class="fw-semibold {{ $test->winner_metric === str_replace(' ', '_', strtolower($r['label'])) ? 'text-primary' : '' }}"
                          style="min-width:42px;text-align:right">{{ $r['val'] }}%</span>
                </div>
                @endforeach

                {{-- Total audience --}}
                <div class="text-muted mt-2" style="font-size:0.75rem">
                    <i class="bi bi-people me-1"></i>{{ $m['total'] ?? 0 }} {{ __('contacts in this variant') }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Holdout info ─────────────────────────────────────────────────────── --}}
@if($test->holdout_percent > 0)
<div class="card border-0 shadow-sm">
    <div class="card-body d-flex align-items-center gap-3 py-3">
        <i class="bi bi-people-fill text-secondary fs-4"></i>
        <div>
            <div class="fw-semibold small">{{ $test->holdout_contacts_count }} {{ __('holdout contacts') }}</div>
            <div class="text-muted" style="font-size:0.78rem">
                @if($test->status === 'completed' && $test->winner_campaign_id)
                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Winner message sent to holdout.') }}</span>
                @elseif($test->status === 'running')
                    {{ __('Will receive the winning variant after') }} {{ $test->decide_at?->diffForHumans() }}.
                @else
                    {{ __('Awaiting test launch.') }}
                @endif
            </div>
        </div>
    </div>
</div>
@endif

</x-layout-dashboard>
