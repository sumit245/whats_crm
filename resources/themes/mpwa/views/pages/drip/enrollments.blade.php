<x-layout-dashboard title="{{ $sequence->name }} — {{ __('Enrollments') }}">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('drip.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <h5 class="mb-0 fw-semibold"><i class="bi bi-people-fill me-2 text-primary"></i>{{ $sequence->name }}</h5>
        <span class="text-muted small">{{ __('Enrollments') }} &mdash; {{ $sequence->steps->count() }} {{ __('steps') }}</span>
    </div>
    <span class="badge {{ $sequence->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($sequence->status) }}</span>
    <a href="{{ route('drip.edit', $sequence->id) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-pencil-fill me-1"></i>{{ __('Edit') }}
    </a>
</div>

<div class="row g-3">

    {{-- ── Enroll contacts form ──────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-transparent border-bottom">
                <span class="small fw-semibold"><i class="bi bi-person-plus-fill me-1"></i>{{ __('Enroll Contacts') }}</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('drip.enroll', $sequence->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Contact numbers') }}</label>
                        <textarea name="contact_numbers" class="form-control form-control-sm @error('contact_numbers') is-invalid @enderror"
                                  rows="4" placeholder="919876543210&#10;919812345678&#10;..."></textarea>
                        <div class="form-text">{{ __('One number per line, or comma-separated.') }}</div>
                        @error('contact_numbers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Sending device') }}</label>
                        <select name="device_id" class="form-select form-select-sm @error('device_id') is-invalid @enderror">
                            <option value="">{{ __('— select —') }}</option>
                            @foreach($devices as $d)
                            <option value="{{ $d->id }}" {{ $sequence->default_device_id == $d->id ? 'selected' : '' }}>
                                {{ $d->meta_profile['verified_name'] ?? $d->body }}
                            </option>
                            @endforeach
                        </select>
                        @error('device_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-person-plus-fill me-1"></i>{{ __('Enroll') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Step summary --}}
        @if($sequence->steps->isNotEmpty())
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header py-2 bg-transparent border-bottom">
                <span class="small fw-semibold"><i class="bi bi-list-ol me-1"></i>{{ __('Sequence Steps') }}</span>
            </div>
            <div class="list-group list-group-flush" style="font-size:0.82rem">
                @foreach($sequence->steps as $step)
                <div class="list-group-item py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary" style="min-width:28px">{{ $step->step_order }}</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">
                                @if($step->message_type === 'template')
                                    <i class="bi bi-layout-text-sidebar-reverse me-1 text-info"></i>{{ $step->template?->name ?? __('Template') }}
                                @else
                                    <i class="bi bi-chat-left-text me-1 text-primary"></i>{{ Str::limit($step->message_body, 40) }}
                                @endif
                            </div>
                            <div class="text-muted" style="font-size:0.72rem">
                                @if($step->delay_value > 0)
                                    {{ __('Wait') }} {{ $step->delay_value }} {{ $step->delay_unit }}
                                @else
                                    {{ __('Immediately') }}
                                @endif
                                @if($step->skip_if_replied)
                                    &middot; <span class="text-warning">{{ __('Skip if replied') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ── Enrollments table ────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-transparent border-bottom d-flex align-items-center justify-content-between">
                <span class="small fw-semibold"><i class="bi bi-people-fill me-1"></i>{{ __('All Enrollments') }}</span>
                <span class="text-muted small">{{ $enrollments->total() }} {{ __('total') }}</span>
            </div>
            @if($enrollments->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-people d-block mb-2" style="font-size:2.5rem"></i>
                {{ __('No enrollments yet. Use the form to add contacts.') }}
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Contact') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Step') }}</th>
                            <th>{{ __('Enrolled') }}</th>
                            <th>{{ __('Next Send') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enr)
                        <tr>
                            <td class="font-monospace small">{{ $enr->contact_number }}</td>
                            <td>
                                @php
                                    $sc = match($enr->status) {
                                        'active'    => 'bg-success',
                                        'completed' => 'bg-primary',
                                        'paused'    => 'bg-warning text-dark',
                                        'cancelled' => 'bg-secondary',
                                        'failed'    => 'bg-danger',
                                        default     => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $sc }}" style="font-size:0.7rem">{{ ucfirst($enr->status) }}</span>
                            </td>
                            <td class="small">
                                @if($enr->status === 'completed')
                                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Done') }}</span>
                                @elseif($enr->status === 'active')
                                    {{ $enr->current_step }} / {{ $sequence->steps->count() }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small text-muted">{{ $enr->enrolled_at?->diffForHumans() }}</td>
                            <td class="small">
                                @if($enr->next_run_at && $enr->status === 'active')
                                    <span class="{{ $enr->next_run_at->isPast() ? 'text-warning' : 'text-muted' }}">
                                        {{ $enr->next_run_at->diffForHumans() }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($enr->status === 'active')
                                <form method="POST" action="{{ route('drip.cancel', $enr->id) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-xs btn-outline-danger" style="font-size:0.72rem;padding:2px 7px"
                                            onclick="return confirm('{{ __('Cancel this enrollment?') }}')">
                                        {{ __('Cancel') }}
                                    </button>
                                </form>
                                @endif
                                @if($enr->last_error)
                                <span title="{{ $enr->last_error }}" class="text-danger ms-1" style="cursor:help">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2">
                {{ $enrollments->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

</x-layout-dashboard>
