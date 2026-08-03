<x-layout-dashboard title="{{ __('Drip Sequences') }}">

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-semibold"><i class="bi bi-send-check-fill me-2 text-primary"></i>{{ __('Drip Sequences') }}</h5>
        <p class="text-muted small mb-0">{{ __('Automate multi-step message sequences triggered by contact events.') }}</p>
    </div>
    <a href="{{ route('drip.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New Sequence') }}
    </a>
</div>

@if($sequences->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-send-check d-block mb-2 text-muted" style="font-size:3rem"></i>
        <h6 class="fw-semibold">{{ __('No sequences yet') }}</h6>
        <p class="text-muted small mb-3">{{ __('Build your first drip campaign — schedule welcome messages, follow-ups, and offers automatically.') }}</p>
        <a href="{{ route('drip.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Create First Sequence') }}
        </a>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($sequences as $seq)
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body pb-2">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-semibold">{{ $seq->name }}</h6>
                        @if($seq->description)
                        <p class="text-muted small mb-0 mt-1">{{ Str::limit($seq->description, 70) }}</p>
                        @endif
                    </div>
                    <span class="badge {{ $seq->status === 'active' ? 'bg-success' : ($seq->status === 'paused' ? 'bg-warning text-dark' : 'bg-secondary') }} flex-shrink-0">
                        {{ ucfirst($seq->status) }}
                    </span>
                </div>

                {{-- Stats row --}}
                <div class="row g-2 mt-1 mb-3">
                    <div class="col-4 text-center">
                        <div class="fw-bold">{{ $seq->steps_count }}</div>
                        <div class="text-muted" style="font-size:0.72rem">{{ __('Steps') }}</div>
                    </div>
                    <div class="col-4 text-center border-start border-end">
                        <div class="fw-bold text-primary">{{ $seq->active_count }}</div>
                        <div class="text-muted" style="font-size:0.72rem">{{ __('Active') }}</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-bold text-success">{{ $seq->completed_count }}</div>
                        <div class="text-muted" style="font-size:0.72rem">{{ __('Completed') }}</div>
                    </div>
                </div>

                {{-- Trigger badge --}}
                <div class="mb-3">
                    @php
                        $triggerLabel = match($seq->trigger_type) {
                            'opt_in'        => __('Opt-in keyword'),
                            'phonebook_add' => __('Phonebook add'),
                            default         => __('Manual'),
                        };
                        $triggerColor = match($seq->trigger_type) {
                            'opt_in'        => 'bg-info-subtle text-info',
                            'phonebook_add' => 'bg-warning-subtle text-warning',
                            default         => 'bg-secondary-subtle text-secondary',
                        };
                    @endphp
                    <span class="badge {{ $triggerColor }}">
                        <i class="bi bi-lightning-charge-fill me-1"></i>{{ $triggerLabel }}
                    </span>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0 d-flex gap-2">
                <a href="{{ route('drip.enrollments', $seq->id) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                    <i class="bi bi-people-fill me-1"></i>{{ __('Enrollments') }}
                </a>
                <a href="{{ route('drip.edit', $seq->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil-fill"></i>
                </a>
                <form method="POST" action="{{ route('drip.destroy', $seq->id) }}"
                      onsubmit="return confirm('{{ __('Delete this sequence and all enrollments?') }}')">
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
