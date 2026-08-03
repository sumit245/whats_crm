<x-layout-dashboard title="{{ __('A/B Tests') }}">

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-semibold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>{{ __('A/B Tests') }}</h5>
        <p class="text-muted small mb-0">{{ __('Split your audience and find the best-performing message.') }}</p>
    </div>
    <a href="{{ route('ab.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New A/B Test') }}
    </a>
</div>

@if($tests->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-graph-up-arrow d-block mb-2 text-muted" style="font-size:3rem"></i>
        <h6 class="fw-semibold">{{ __('No A/B tests yet') }}</h6>
        <p class="text-muted small mb-3">{{ __('Create your first test to compare message variants and let data pick the winner.') }}</p>
        <a href="{{ route('ab.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Create First Test') }}
        </a>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($tests as $test)
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-2 mb-3">
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-semibold">{{ $test->name }}</h6>
                        <span class="text-muted small">{{ $test->phonebook?->name ?? '—' }}</span>
                    </div>
                    @php
                        $statusColor = match($test->status) {
                            'running'   => 'bg-primary',
                            'completed' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default     => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $statusColor }}">{{ ucfirst($test->status) }}</span>
                </div>

                {{-- Variant labels --}}
                <div class="d-flex gap-2 mb-3">
                    @foreach($test->variants as $v)
                    <span class="badge {{ $test->winner_variant_id == $v->id ? 'bg-success' : 'bg-light text-dark border' }} px-3 py-2">
                        @if($test->winner_variant_id == $v->id)
                            <i class="bi bi-trophy-fill me-1"></i>
                        @endif
                        {{ __('Variant') }} {{ $v->label }}
                        <span class="text-muted ms-1" style="font-size:0.72rem">{{ $v->message_type === 'template' ? __('Template') : __('Text') }}</span>
                    </span>
                    @endforeach
                    @if($test->holdout_percent > 0)
                    <span class="badge bg-light text-dark border px-3 py-2">
                        {{ $test->holdout_percent }}% {{ __('Holdout') }}
                    </span>
                    @endif
                </div>

                <div class="row g-2 text-center mb-2" style="font-size:0.78rem">
                    <div class="col-4">
                        <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $test->winner_metric)) }}</div>
                        <div class="text-muted">{{ __('Metric') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-semibold">{{ $test->decide_after_hours }}h</div>
                        <div class="text-muted">{{ __('Decision window') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-semibold">{{ $test->holdout_contacts_count }}</div>
                        <div class="text-muted">{{ __('Holdout') }}</div>
                    </div>
                </div>

                @if($test->status === 'running' && $test->decide_at)
                <div class="progress mb-2" style="height:4px">
                    @php
                        $elapsed  = $test->created_at->diffInMinutes(now());
                        $total    = $test->created_at->diffInMinutes($test->decide_at);
                        $progress = $total > 0 ? min(100, round($elapsed / $total * 100)) : 0;
                    @endphp
                    <div class="progress-bar bg-primary" style="width:{{ $progress }}%"></div>
                </div>
                <div class="text-muted" style="font-size:0.72rem">
                    {{ __('Decide at') }} {{ $test->decide_at->format('M d, H:i') }}
                    ({{ $test->decide_at->diffForHumans() }})
                </div>
                @endif
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0 d-flex gap-2">
                <a href="{{ route('ab.show', $test->id) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                    <i class="bi bi-bar-chart-fill me-1"></i>{{ __('Results') }}
                </a>
                @if($test->status === 'draft')
                <form method="POST" action="{{ route('ab.launch', $test->id) }}">
                    @csrf
                    <button class="btn btn-sm btn-success">
                        <i class="bi bi-play-fill me-1"></i>{{ __('Launch') }}
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('ab.destroy', $test->id) }}"
                      onsubmit="return confirm('{{ __('Delete this test?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

</x-layout-dashboard>
