<div class="step-card border rounded mb-3 p-3" data-step-index="{{ $index }}">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="badge bg-primary-subtle text-primary fw-semibold">{{ __('Step') }} <span class="step-number">{{ $index + 1 }}</span></span>
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-step">
            <i class="bi bi-trash3"></i>
        </button>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-12 col-sm-5">
            <label class="form-label small">{{ __('Send after') }}</label>
            <div class="input-group input-group-sm">
                <input type="number" name="steps[{{ $index }}][delay_value]" class="form-control"
                       value="{{ old("steps.{$index}.delay_value", $step->delay_value) }}" min="0">
                <select name="steps[{{ $index }}][delay_unit]" class="form-select" style="max-width:100px">
                    @foreach(['minutes' => __('min'), 'hours' => __('hours'), 'days' => __('days')] as $val => $label)
                    <option value="{{ $val }}" {{ old("steps.{$index}.delay_unit", $step->delay_unit) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-text">{{ __('Delay from previous step (0 = immediately)') }}</div>
        </div>
        <div class="col-12 col-sm-4">
            <label class="form-label small">{{ __('Message type') }}</label>
            <select name="steps[{{ $index }}][message_type]" class="form-select form-select-sm msg-type-select" onchange="toggleMsgType(this)">
                @foreach(['text' => __('Text message'), 'template' => __('HSM Template')] as $val => $label)
                <option value="{{ $val }}" {{ old("steps.{$index}.message_type", $step->message_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-3 d-flex align-items-end">
            <div class="form-check mb-1">
                <input type="hidden" name="steps[{{ $index }}][skip_if_replied]" value="0">
                <input class="form-check-input" type="checkbox" name="steps[{{ $index }}][skip_if_replied]" value="1"
                       id="skip{{ $index }}" {{ old("steps.{$index}.skip_if_replied", $step->skip_if_replied) ? 'checked' : '' }}>
                <label class="form-check-label small" for="skip{{ $index }}">{{ __('Skip if replied') }}</label>
            </div>
        </div>
    </div>

    @php $isTemplate = old("steps.{$index}.message_type", $step->message_type) === 'template'; @endphp

    <div class="msg-text-area" style="{{ $isTemplate ? 'display:none' : '' }}">
        <label class="form-label small">{{ __('Message') }} <span class="text-muted" style="font-size:0.75rem">({{ __('use') }} @{{contact_name}} {{ __('or') }} @{{contact_number}})</span></label>
        <textarea name="steps[{{ $index }}][message_body]" class="form-control form-control-sm" rows="3"
                  placeholder="Hi @{{contact_name}}, thanks for joining!">{{ old("steps.{$index}.message_body", $step->message_body) }}</textarea>
    </div>

    <div class="msg-template-area" style="{{ $isTemplate ? '' : 'display:none' }}">
        <label class="form-label small">{{ __('Template') }}</label>
        <select name="steps[{{ $index }}][template_id]" class="form-select form-select-sm">
            <option value="">{{ __('— select template —') }}</option>
            @foreach($templates as $tmpl)
            <option value="{{ $tmpl->id }}" {{ old("steps.{$index}.template_id", $step->template_id) == $tmpl->id ? 'selected' : '' }}>{{ $tmpl->name }} ({{ $tmpl->language }})</option>
            @endforeach
        </select>
        <div class="form-text">{{ __('Only APPROVED templates can be used.') }}</div>
    </div>
</div>
