<x-layout-dashboard title="{{ __('New A/B Test') }}">

<form method="POST" action="{{ route('ab.store') }}">
@csrf

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('ab.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold flex-grow-1"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>{{ __('New A/B Test') }}</h5>
    <a href="{{ route('ab.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Cancel') }}</a>
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="bi bi-check-lg me-1"></i>{{ __('Create Test') }}
    </button>
</div>

<div class="row g-3">

{{-- ── Left: test settings ──────────────────────────────────────────────── --}}
<div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 bg-transparent border-bottom">
            <span class="small fw-semibold"><i class="bi bi-sliders me-1"></i>{{ __('Test Settings') }}</span>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label small fw-semibold">{{ __('Test name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}"
                       required placeholder="{{ __('e.g. Welcome message test') }}">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">{{ __('Audience (Phonebook)') }} <span class="text-danger">*</span></label>
                <select name="phonebook_id" class="form-select form-select-sm" required>
                    <option value="">{{ __('— select —') }}</option>
                    @foreach($phonebooks as $pb)
                    <option value="{{ $pb->id }}" {{ old('phonebook_id') == $pb->id ? 'selected' : '' }}>{{ $pb->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">{{ __('Sending device') }} <span class="text-danger">*</span></label>
                <select name="device_id" class="form-select form-select-sm" required>
                    <option value="">{{ __('— select —') }}</option>
                    @foreach($devices as $d)
                    <option value="{{ $d->id }}" {{ old('device_id') == $d->id ? 'selected' : '' }}>
                        {{ $d->meta_profile['verified_name'] ?? $d->body }}
                    </option>
                    @endforeach
                </select>
            </div>

            <hr class="my-3">
            <p class="small fw-semibold mb-2"><i class="bi bi-trophy-fill me-1 text-warning"></i>{{ __('Winner Settings') }}</p>

            <div class="mb-3">
                <label class="form-label small">{{ __('Holdout audience') }}</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="holdout_percent" class="form-control"
                           value="{{ old('holdout_percent', 20) }}" min="0" max="50">
                    <span class="input-group-text">%</span>
                </div>
                <div class="form-text">{{ __('Held back to receive the winning variant. Set 0 to disable.') }}</div>
            </div>

            <div class="mb-3">
                <label class="form-label small">{{ __('Pick winner by') }}</label>
                <select name="winner_metric" class="form-select form-select-sm">
                    @php
                        $metrics = [
                            'read_rate'     => __('Read rate'),
                            'delivery_rate' => __('Delivery rate'),
                            'click_rate'    => __('Click rate'),
                        ];
                    @endphp
                    @foreach($metrics as $val => $label)
                    <option value="{{ $val }}" {{ old('winner_metric', 'read_rate') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small">{{ __('Decide winner after') }}</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="decide_after_hours" class="form-control"
                           value="{{ old('decide_after_hours', 24) }}" min="1" max="720">
                    <span class="input-group-text">{{ __('hours') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Right: variant messages ───────────────────────────────────────────── --}}
<div class="col-12 col-lg-8">

    @foreach(['a' => 'A', 'b' => 'B'] as $key => $label)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 bg-transparent border-bottom d-flex align-items-center gap-2">
            <span class="badge bg-primary px-3 py-2">{{ __('Variant') }} {{ $label }}</span>
            <span class="text-muted small">{{ __('50% of non-holdout audience') }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-sm-4">
                    <label class="form-label small fw-semibold">{{ __('Message type') }}</label>
                    <select name="variant_{{ $key }}_type" class="form-select form-select-sm msg-type-select"
                            data-variant="{{ $key }}" onchange="toggleVariantType(this)">
                        <option value="text"     {{ old("variant_{$key}_type", 'text') === 'text'     ? 'selected' : '' }}>{{ __('Text') }}</option>
                        <option value="template" {{ old("variant_{$key}_type", 'text') === 'template' ? 'selected' : '' }}>{{ __('HSM Template') }}</option>
                    </select>
                </div>
            </div>

            @php $isTemplate = old("variant_{$key}_type", 'text') === 'template'; @endphp

            <div id="var-text-{{ $key }}" class="mt-3" style="{{ $isTemplate ? 'display:none' : '' }}">
                <label class="form-label small fw-semibold">{{ __('Message') }}</label>
                <textarea name="variant_{{ $key }}_body" class="form-control form-control-sm" rows="4"
                          placeholder="{{ __('Enter your message for Variant') }} {{ $label }}…">{{ old("variant_{$key}_body") }}</textarea>
            </div>

            <div id="var-tmpl-{{ $key }}" class="mt-3" style="{{ $isTemplate ? '' : 'display:none' }}">
                <label class="form-label small fw-semibold">{{ __('Template') }}</label>
                <select name="variant_{{ $key }}_template" id="tmpl-select-{{ $key }}"
                        class="form-select form-select-sm" onchange="previewTemplate('{{ $key }}', this.value)">
                    <option value="">{{ __('— select template —') }}</option>
                    @foreach($templates as $tmpl)
                    <option value="{{ $tmpl->id }}" {{ old("variant_{$key}_template") == $tmpl->id ? 'selected' : '' }}>
                        {{ $tmpl->name }} ({{ $tmpl->language }})
                    </option>
                    @endforeach
                </select>

                {{-- preview bubble --}}
                <div id="tmpl-preview-{{ $key }}" class="mt-3 d-none">
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <i class="bi bi-eye-fill text-muted" style="font-size:0.75rem"></i>
                        <span class="text-muted" style="font-size:0.75rem">{{ __('Preview') }}</span>
                    </div>
                    <div class="p-3 rounded" style="background:#dcf8c6;max-width:340px;font-size:0.85rem;white-space:pre-wrap;word-break:break-word;box-shadow:0 1px 2px rgba(0,0,0,.15)"
                         id="tmpl-bubble-{{ $key }}"></div>
                    <div id="tmpl-footer-{{ $key }}" class="text-muted mt-1" style="font-size:0.75rem"></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>

</div>
</form>

<script>
var AB_TEMPLATES = @json($templates->keyBy('id'));

function toggleVariantType(select) {
    var v = select.getAttribute('data-variant');
    var isTemplate = select.value === 'template';
    document.getElementById('var-text-' + v).style.display = isTemplate ? 'none' : '';
    document.getElementById('var-tmpl-' + v).style.display = isTemplate ? '' : 'none';
    if (!isTemplate) {
        document.getElementById('tmpl-preview-' + v).classList.add('d-none');
    }
}

function previewTemplate(v, id) {
    var previewEl = document.getElementById('tmpl-preview-' + v);
    var bubbleEl  = document.getElementById('tmpl-bubble-' + v);
    var footerEl  = document.getElementById('tmpl-footer-' + v);

    if (!id) { previewEl.classList.add('d-none'); return; }

    var tmpl = AB_TEMPLATES[id];
    if (!tmpl) { previewEl.classList.add('d-none'); return; }

    var comps = tmpl.components || [];

    // Body text
    var bodyComp = comps.find(function(c){ return (c.type||'').toUpperCase() === 'BODY'; });
    var bodyText = bodyComp ? (bodyComp.text || '') : tmpl.name;

    // Footer text
    var footerComp = comps.find(function(c){ return (c.type||'').toUpperCase() === 'FOOTER'; });
    var footerText = footerComp ? (footerComp.text || '') : '';

    // Header indicator
    var headerComp = comps.find(function(c){ return (c.type||'').toUpperCase() === 'HEADER'; });
    var headerHtml = '';
    if (headerComp) {
        var fmt = (headerComp.format || '').toUpperCase();
        if (fmt === 'TEXT' && headerComp.text) {
            headerHtml = '<div style="font-weight:600;margin-bottom:4px">' + esc(headerComp.text) + '</div>';
        } else if (['IMAGE','VIDEO','DOCUMENT'].includes(fmt)) {
            var icons = { IMAGE: 'bi-image', VIDEO: 'bi-camera-video-fill', DOCUMENT: 'bi-file-earmark-fill' };
            headerHtml = '<div class="mb-2 text-muted" style="font-size:0.8rem"><i class="bi ' + (icons[fmt]||'bi-paperclip') + ' me-1"></i>' + fmt + ' attachment</div>';
        }
    }

    bubbleEl.innerHTML = headerHtml + esc(bodyText);
    footerEl.textContent = footerText;
    previewEl.classList.remove('d-none');
}

function esc(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

</x-layout-dashboard>
