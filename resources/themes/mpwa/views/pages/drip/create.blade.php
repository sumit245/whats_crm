<x-layout-dashboard title="{{ $sequence ? __('Edit Sequence') : __('New Sequence') }}">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('drip.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">
        <i class="bi bi-send-check-fill me-2 text-primary"></i>
        {{ $sequence ? __('Edit Sequence') : __('New Sequence') }}
    </h5>
</div>

<form method="POST" action="{{ $sequence ? route('drip.update', $sequence->id) : route('drip.store') }}" id="seqForm">
    @csrf
    @if($sequence) @method('PUT') @endif

    <div class="row g-3">

        {{-- ── Left: sequence settings ─────────────────────────────────────── --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2 bg-transparent border-bottom">
                    <span class="small fw-semibold"><i class="bi bi-sliders me-1"></i>{{ __('Sequence Settings') }}</span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                               value="{{ old('name', $sequence?->name) }}" required placeholder="{{ __('e.g. Welcome Series') }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2"
                                  placeholder="{{ __('Optional notes…') }}">{{ old('description', $sequence?->description) }}</textarea>
                    </div>

                    @php
                        $statusOptions  = ['draft' => __('Draft'), 'active' => __('Active'), 'paused' => __('Paused')];
                        $triggerOptions = [
                            'manual'        => __('Manual enrollment'),
                            'opt_in'        => __('Opt-in keyword — JOIN'),
                            'phonebook_add' => __('Phonebook add'),
                        ];
                    @endphp

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Status') }}</label>
                        <select name="status" class="form-select form-select-sm">
                            @foreach($statusOptions as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $sequence?->status ?? 'draft') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="my-3">
                    <p class="small fw-semibold mb-2"><i class="bi bi-lightning-charge-fill me-1 text-warning"></i>{{ __('Trigger') }}</p>

                    <div class="mb-3">
                        <label class="form-label small">{{ __('Trigger type') }}</label>
                        <select name="trigger_type" id="triggerType" class="form-select form-select-sm"
                                onchange="toggleTriggerOptions()">
                            @foreach($triggerOptions as $val => $label)
                            <option value="{{ $val }}" {{ old('trigger_type', $sequence?->trigger_type ?? 'manual') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('opt_in: auto-enroll when contact sends JOIN keyword. manual: use Enrollments page.') }}</div>
                    </div>

                    <div id="phonebookOption" class="mb-3" style="display:none">
                        <label class="form-label small">{{ __('Phonebook') }}</label>
                        <select name="trigger_phonebook_id" class="form-select form-select-sm">
                            <option value="">{{ __('— select phonebook —') }}</option>
                            @foreach($phonebooks as $pb)
                            <option value="{{ $pb->id }}" {{ old('trigger_phonebook_id', $sequence?->trigger_phonebook_id) == $pb->id ? 'selected' : '' }}>{{ $pb->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">{{ __('Default sending device') }}</label>
                        <select name="default_device_id" class="form-select form-select-sm">
                            <option value="">{{ __('— select device —') }}</option>
                            @foreach($devices as $d)
                            <option value="{{ $d->id }}" {{ old('default_device_id', $sequence?->default_device_id) == $d->id ? 'selected' : '' }}>
                                {{ $d->meta_profile['verified_name'] ?? $d->body }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('Used for opt-in / phonebook triggers. Can be overridden per enrollment.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Right: steps ────────────────────────────────────────────────── --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2 bg-transparent border-bottom d-flex align-items-center justify-content-between">
                    <span class="small fw-semibold"><i class="bi bi-list-ol me-1"></i>{{ __('Steps') }}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addStep()">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Add Step') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="stepsContainer" class="px-3 pt-3">
                        @if($steps->isEmpty())
                        <div id="emptyStepsHint" class="text-center text-muted py-4 small">
                            <i class="bi bi-list-ol d-block mb-1" style="font-size:2rem"></i>
                            {{ __('No steps yet. Click "Add Step" to build your sequence.') }}
                        </div>
                        @else
                        @foreach($steps as $i => $step)
                        @include('theme::pages.drip._step', ['step' => $step, 'index' => $i])
                        @endforeach
                        @endif
                    </div>
                    <div class="px-3 pb-3 mt-2" id="addStepBtnArea">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="addStep()">
                            <i class="bi bi-plus-lg me-1"></i>{{ __('Add Step') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3 mb-5 pb-4">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg me-1"></i>{{ $sequence ? __('Save Changes') : __('Create Sequence') }}
                </button>
                <a href="{{ route('drip.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Cancel') }}</a>
            </div>
        </div>

    </div>
</form>

{{-- Step template (hidden) --}}
<template id="stepTemplate">
    <div class="step-card border rounded mb-3 p-3" data-step-index="__IDX__">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="badge bg-primary-subtle text-primary fw-semibold">{{ __('Step') }} <span class="step-number">__NUM__</span></span>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-step">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-12 col-sm-5">
                <label class="form-label small">{{ __('Send after') }}</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="steps[__IDX__][delay_value]" class="form-control" value="0" min="0">
                    <select name="steps[__IDX__][delay_unit]" class="form-select" style="max-width:100px">
                        <option value="minutes">{{ __('min') }}</option>
                        <option value="hours" selected>{{ __('hours') }}</option>
                        <option value="days">{{ __('days') }}</option>
                    </select>
                </div>
                <div class="form-text">{{ __('Delay from previous step (0 = immediately)') }}</div>
            </div>
            <div class="col-12 col-sm-4">
                <label class="form-label small">{{ __('Message type') }}</label>
                <select name="steps[__IDX__][message_type]" class="form-select form-select-sm msg-type-select" onchange="toggleMsgType(this)">
                    <option value="text">{{ __('Text message') }}</option>
                    <option value="template">{{ __('HSM Template') }}</option>
                </select>
            </div>
            <div class="col-12 col-sm-3 d-flex align-items-end">
                <div class="form-check mb-1">
                    <input type="hidden" name="steps[__IDX__][skip_if_replied]" value="0">
                    <input class="form-check-input" type="checkbox" name="steps[__IDX__][skip_if_replied]" value="1" id="skip__IDX__">
                    <label class="form-check-label small" for="skip__IDX__">{{ __('Skip if replied') }}</label>
                </div>
            </div>
        </div>

        <div class="msg-text-area">
            <label class="form-label small">{{ __('Message') }} <span class="text-muted" style="font-size:0.75rem">({{ __('use') }} @{{contact_name}} {{ __('or') }} @{{contact_number}})</span></label>
            <textarea name="steps[__IDX__][message_body]" class="form-control form-control-sm" rows="3"
                      placeholder="Hi @{{contact_name}}, thanks for joining!"></textarea>
        </div>

        <div class="msg-template-area" style="display:none">
            <label class="form-label small">{{ __('Template') }}</label>
            <select name="steps[__IDX__][template_id]" class="form-select form-select-sm">
                <option value="">{{ __('— select template —') }}</option>
                @foreach($templates as $tmpl)
                <option value="{{ $tmpl->id }}">{{ $tmpl->name }} ({{ $tmpl->language }})</option>
                @endforeach
            </select>
            <div class="form-text">{{ __('Only APPROVED templates can be used.') }}</div>
        </div>
    </div>
</template>

<style>
.step-card { background: #f8f9fa; }
</style>

@push('scripts')
<script>
let stepCount = {{ $steps->count() }};

function toggleTriggerOptions() {
    const t = document.getElementById('triggerType').value;
    document.getElementById('phonebookOption').style.display = (t === 'phonebook_add') ? '' : 'none';
}

function addStep() {
    document.getElementById('emptyStepsHint')?.remove();

    const tmpl  = document.getElementById('stepTemplate').innerHTML;
    const idx   = stepCount;
    const num   = stepCount + 1;
    const html  = tmpl.replaceAll('__IDX__', idx).replaceAll('__NUM__', num);

    const wrap  = document.createElement('div');
    wrap.innerHTML = html;
    document.getElementById('stepsContainer').appendChild(wrap.firstElementChild);
    stepCount++;
    renumberSteps();

    // Remove btn
    wrap.firstElementChild?.querySelector('.btn-remove-step')?.addEventListener('click', function() {
        this.closest('.step-card').remove();
        renumberSteps();
    });
}

function renumberSteps() {
    document.querySelectorAll('#stepsContainer .step-card').forEach((card, i) => {
        card.querySelector('.step-number').textContent = i + 1;
    });
}

function toggleMsgType(select) {
    const card = select.closest('.step-card');
    const isTemplate = select.value === 'template';
    card.querySelector('.msg-text-area').style.display     = isTemplate ? 'none' : '';
    card.querySelector('.msg-template-area').style.display = isTemplate ? '' : 'none';
}

// Wire up existing remove buttons and type selects
document.querySelectorAll('.btn-remove-step').forEach(btn => {
    btn.addEventListener('click', function() {
        this.closest('.step-card').remove();
        renumberSteps();
    });
});

document.querySelectorAll('.msg-type-select').forEach(sel => {
    sel.addEventListener('change', function() { toggleMsgType(this); });
});

toggleTriggerOptions();
</script>
@endpush

</x-layout-dashboard>
