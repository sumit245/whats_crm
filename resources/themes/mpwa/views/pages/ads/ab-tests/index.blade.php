<x-layout-dashboard title="{{ __('Ad A/B Tests') }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ __('Ad A/B Tests') }}"
    subtitle="{{ __('Split-test creatives across channels — auto-promote the winner') }}"
    :breadcrumb="[__('Ads Manager'), __('A/B Tests')]" />

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('ads.ab-tests.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New A/B Test') }}
    </a>
</div>

@if($tests->isEmpty())
<div class="card border-0 shadow-sm">
    <x-no-data colspan="1" text="{{ __('No A/B tests yet') }}" />
</div>
@else
<div class="row g-3">
@foreach($tests as $test)
<div class="col-12">
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-start gap-3">

            {{-- Status icon --}}
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-{{ $test->statusColor() }} bg-opacity-10"
                style="width:48px;height:48px;flex-shrink:0">
                <i class="bi bi-{{ $test->status === 'running' ? 'play-circle-fill' : ($test->status === 'completed' ? 'trophy-fill' : 'pause-circle') }} text-{{ $test->statusColor() }} fs-4"></i>
            </div>

            {{-- Info --}}
            <div class="flex-fill">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="{{ route('ads.ab-tests.show', $test) }}" class="fw-semibold text-decoration-none">{{ $test->name }}</a>
                    <span class="badge bg-{{ $test->statusColor() }} bg-opacity-15 text-{{ $test->statusColor() }}">{{ ucfirst($test->status) }}</span>
                </div>
                <div class="text-muted small mb-2">
                    {{ $test->campaign?->name }} &nbsp;·&nbsp;
                    {{ $test->winnerMetricLabel() }} &nbsp;·&nbsp;
                    {{ __('Decide after') }} {{ $test->decide_after_hours }}h
                    @if($test->launched_at)
                    &nbsp;·&nbsp; {{ __('Launched') }} {{ $test->launched_at->diffForHumans() }}
                    @endif
                </div>

                {{-- Variants row --}}
                <div class="d-flex flex-wrap gap-2">
                @foreach($test->variants as $variant)
                <div class="d-flex align-items-center gap-2 border rounded px-3 py-1"
                    style="{{ $test->winner_creative_id === $variant->ad_creative_id ? 'border-color:#198754!important;background:#f0faf5' : '' }}">
                    <div class="fw-bold text-primary" style="font-size:13px">{{ $variant->label }}</div>
                    <div class="small text-truncate" style="max-width:150px">{{ $variant->creative?->name ?? '—' }}</div>
                    @if($test->winner_creative_id === $variant->ad_creative_id)
                    <i class="bi bi-trophy-fill text-warning" title="{{ __('Winner') }}"></i>
                    @endif
                    <span class="badge bg-light text-muted border">{{ $variant->budget_split }}%</span>
                </div>
                @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-1 flex-shrink-0">
                <a href="{{ route('ads.ab-tests.show', $test) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-bar-chart-line"></i>
                </a>
                @if($test->status === 'draft')
                <form method="POST" action="{{ route('ads.ab-tests.destroy', $test) }}"
                      onsubmit="return confirm('{{ __('Cancel this test?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endforeach
</div>

@if($tests->hasPages())
<div class="mt-3">{{ $tests->links() }}</div>
@endif
@endif

</x-layout-dashboard>
