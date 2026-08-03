<x-layout-dashboard title="{{ __('New A/B Test') }}">

<x-page-header title="{{ __('New A/B Test') }}"
    subtitle="{{ __('Compare creative variants across channels — auto-promote the winner') }}"
    :breadcrumb="[__('Ads Manager'), __('A/B Tests'), __('Create')]" />

<div class="row justify-content-center">
<div class="col-xl-8">

<form method="POST" action="{{ route('ads.ab-tests.store') }}">
@csrf

{{-- Basics --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">{{ __('Test Settings') }}</h6>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Test Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="{{ __('e.g. Summer Hero Image vs Video') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Campaign') }} <span class="text-danger">*</span></label>
                <select name="ad_campaign_id" class="form-select" required>
                    <option value="">{{ __('Select campaign…') }}</option>
                    @foreach($campaigns as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} ({{ ucfirst($c->status) }})</option>
                    @endforeach
                </select>
                <div class="form-text">{{ __('Budget & audience are inherited from the campaign.') }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">{{ __('Winner Metric') }} <span class="text-danger">*</span></label>
                <select name="winner_metric" class="form-select" required>
                    <option value="ctr">CTR — Click-through Rate</option>
                    <option value="clicks">Total Clicks</option>
                    <option value="conversions">Conversions</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">{{ __('Decide After') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="decide_after_hours" class="form-control" value="24" min="1" max="720" required>
                    <span class="input-group-text">{{ __('hours') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Variants --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">{{ __('Creative Variants') }}</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addVariantBtn" onclick="addVariant()">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Variant') }}
            </button>
        </div>
        <div class="alert alert-info small py-2">
            <i class="bi bi-info-circle me-1"></i>
            {{ __('Each variant uses a different creative. Budget is split evenly across variants (adjustable).') }}
        </div>

        <div id="variantsContainer">
            @foreach(['A', 'B'] as $label)
            <div class="card border mb-3 variant-row">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold flex-shrink-0"
                            style="width:32px;height:32px;font-size:14px">{{ $label }}</div>
                        <div class="flex-fill">
                            <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Creative') }} <span class="text-danger">*</span></label>
                            <select name="variant_creatives[]" class="form-select form-select-sm" required>
                                <option value="">{{ __('Select creative…') }}</option>
                                @foreach($creatives as $cr)
                                <option value="{{ $cr->id }}">{{ $cr->name }} ({{ ucfirst($cr->format) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="width:90px">
                            <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Budget %') }}</label>
                            <input type="number" class="form-control form-control-sm split-input" value="{{ $label === 'A' ? 50 : 50 }}" min="1" max="100" readonly>
                        </div>
                        @if($label !== 'A')
                        <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removeVariant(this)">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @else
                        <div style="width:36px"></div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-end text-muted small">
            {{ __('Total split:') }} <span id="totalSplit" class="fw-semibold">100</span>%
        </div>
    </div>
</div>

{{-- Submit --}}
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check2 me-1"></i>{{ __('Create A/B Test') }}
    </button>
    <a href="{{ route('ads.ab-tests.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
</div>

</form>
</div>
</div>

</x-layout-dashboard>

<script>
const variantLabels = ['A', 'B', 'C', 'D'];
const creativesOptions = `@foreach($creatives as $cr)<option value="{{ $cr->id }}">{{ addslashes($cr->name) }} ({{ ucfirst($cr->format) }})</option>@endforeach`;

function addVariant() {
    const rows = document.querySelectorAll('.variant-row');
    if (rows.length >= 4) { toastr.warning('{{ __("Maximum 4 variants supported.") }}'); return; }

    const label = variantLabels[rows.length];
    const div = document.createElement('div');
    div.className = 'card border mb-3 variant-row';
    div.innerHTML = `<div class="card-body p-3"><div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold flex-shrink-0" style="width:32px;height:32px;font-size:14px">${label}</div>
        <div class="flex-fill">
            <label class="form-label form-label-sm fw-semibold mb-1">{{ __("Creative") }} <span class="text-danger">*</span></label>
            <select name="variant_creatives[]" class="form-select form-select-sm" required>
                <option value="">{{ __("Select creative…") }}</option>
                ${creativesOptions}
            </select>
        </div>
        <div style="width:90px">
            <label class="form-label form-label-sm fw-semibold mb-1">{{ __("Budget %") }}</label>
            <input type="number" class="form-control form-control-sm split-input" value="0" min="1" max="100" readonly>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removeVariant(this)"><i class="bi bi-trash3"></i></button>
    </div></div>`;
    document.getElementById('variantsContainer').appendChild(div);
    rebalanceSplits();
}

function removeVariant(btn) {
    btn.closest('.variant-row').remove();
    rebalanceSplits();
    // Re-label
    document.querySelectorAll('.variant-row').forEach((row, i) => {
        const circle = row.querySelector('.rounded-circle');
        if (circle) circle.textContent = variantLabels[i];
    });
}

function rebalanceSplits() {
    const inputs = document.querySelectorAll('.split-input');
    const n = inputs.length;
    const base = Math.floor(100 / n);
    const rem  = 100 - base * n;
    inputs.forEach((inp, i) => { inp.value = base + (i === 0 ? rem : 0); });
    document.getElementById('totalSplit').textContent = 100;
}
</script>
