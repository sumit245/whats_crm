<x-layout-dashboard title="{{ __('Create Segment') }}">

<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="material-icons-two-tone me-1">filter_list</i> {{ __('Build Audience Segment') }}</h5>
                </div>

                <div class="card-body">
                    <form id="segment-form">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-semibold">{{ __('Segment Name') }}</label>
                            <input type="text" id="seg-name" class="form-control" placeholder="{{ __('e.g. VIP Customers') }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">{{ __('Match') }}</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <select id="seg-operator" class="form-select w-auto">
                                    <option value="AND">{{ __('ALL conditions (AND)') }}</option>
                                    <option value="OR">{{ __('ANY condition (OR)') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Condition rows --}}
                        <div id="conditions-container" class="mb-3">
                            {{-- Injected by JS --}}
                        </div>

                        <button type="button" id="add-condition" class="btn btn-outline-secondary btn-sm mb-4">
                            <i class="material-icons" style="font-size:16px;">add</i> {{ __('Add Condition') }}
                        </button>

                        <div class="d-flex align-items-center gap-3">
                            <button type="button" id="preview-btn" class="btn btn-outline-primary">
                                <i class="material-icons me-1" style="font-size:16px;">visibility</i> {{ __('Preview Count') }}
                            </button>
                            <span id="preview-result" class="text-muted"></span>
                        </div>
                    </form>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('segments.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <button type="button" id="save-btn" class="btn btn-primary">
                        <i class="material-icons me-1" style="font-size:16px;">save</i> {{ __('Save Segment') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.condition-row { background: #f8f9fa; border-radius: 8px; padding: 12px 16px; margin-bottom: 10px; }
.condition-row select, .condition-row input { font-size: 0.9rem; }
</style>

<script>
(function () {
    const FIELDS = [
        { value: 'name',             label: '{{ __('Contact Name') }}' },
        { value: 'number',           label: '{{ __('Phone Number') }}' },
        { value: 'tag_name',         label: '{{ __('Phonebook Name') }}' },
        { value: 'created_at',       label: '{{ __('Date Added') }}' },
        { value: 'delivery_status',  label: '{{ __('Message Delivery (Behavioral)') }}' },
    ];

    const STRING_OPS = [
        { value: 'contains',    label: '{{ __('contains') }}' },
        { value: 'equals',      label: '{{ __('equals') }}' },
        { value: 'starts_with', label: '{{ __('starts with') }}' },
        { value: 'ends_with',   label: '{{ __('ends with') }}' },
        { value: 'not_equals',  label: '{{ __('is not') }}' },
    ];

    const DATE_OPS = [
        { value: 'older_than', label: '{{ __('older than') }}' },
        { value: 'newer_than', label: '{{ __('newer than') }}' },
    ];

    const DATE_VALUES = [
        { value: '7_days',  label: '{{ __('7 days') }}' },
        { value: '30_days', label: '{{ __('30 days') }}' },
        { value: '60_days', label: '{{ __('60 days') }}' },
        { value: '90_days', label: '{{ __('90 days') }}' },
    ];

    const DELIVERY_VALUES = [
        { value: 'sent',           label: '{{ __('Was Sent') }}' },
        { value: 'delivered',      label: '{{ __('Was Delivered') }}' },
        { value: 'read',           label: '{{ __('Was Read') }}' },
        { value: 'not_delivered',  label: '{{ __('Was NOT Delivered') }}' },
        { value: 'not_read',       label: '{{ __('Was NOT Read (but delivered)') }}' },
    ];

    let conditionCount = 0;
    const container = document.getElementById('conditions-container');

    function addCondition() {
        conditionCount++;
        const id = conditionCount;

        const row = document.createElement('div');
        row.className = 'condition-row d-flex align-items-center flex-wrap gap-2';
        row.id = 'condition-' + id;

        row.innerHTML = `
            <select class="form-select w-auto cond-field" data-id="${id}" onchange="updateOpsAndValue(${id})">
                ${FIELDS.map(f => `<option value="${f.value}">${f.label}</option>`).join('')}
            </select>
            <select class="form-select w-auto cond-op d-none" id="op-${id}">
                ${STRING_OPS.map(o => `<option value="${o.value}">${o.label}</option>`).join('')}
            </select>
            <div class="cond-value-wrap flex-grow-1" id="val-wrap-${id}">
                <input type="text" class="form-control cond-value" id="val-${id}" placeholder="{{ __('value...') }}">
            </div>
            <div class="d-none" id="campaign-wrap-${id}">
                <input type="number" class="form-control cond-campaign-id" id="camp-${id}" placeholder="{{ __('Campaign ID (optional)') }}">
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('condition-${id}').remove()">
                <i class="material-icons" style="font-size:16px;">close</i>
            </button>
        `;

        container.appendChild(row);
        updateOpsAndValue(id);
    }

    window.updateOpsAndValue = function (id) {
        const field = document.querySelector(`#condition-${id} .cond-field`).value;
        const opSelect  = document.getElementById('op-' + id);
        const valWrap   = document.getElementById('val-wrap-' + id);
        const campWrap  = document.getElementById('campaign-wrap-' + id);

        campWrap.classList.add('d-none');

        if (field === 'created_at') {
            opSelect.classList.remove('d-none');
            opSelect.innerHTML = DATE_OPS.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
            valWrap.innerHTML = `<select class="form-select cond-value" id="val-${id}">
                ${DATE_VALUES.map(v => `<option value="${v.value}">${v.label}</option>`).join('')}
            </select>`;
        } else if (field === 'delivery_status') {
            opSelect.classList.add('d-none');
            valWrap.innerHTML = `<select class="form-select cond-value" id="val-${id}">
                ${DELIVERY_VALUES.map(v => `<option value="${v.value}">${v.label}</option>`).join('')}
            </select>`;
            campWrap.classList.remove('d-none');
        } else {
            opSelect.classList.remove('d-none');
            opSelect.innerHTML = STRING_OPS.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
            valWrap.innerHTML = `<input type="text" class="form-control cond-value" id="val-${id}" placeholder="{{ __('value...') }}">`;
        }
    };

    document.getElementById('add-condition').addEventListener('click', addCondition);

    function buildRules() {
        const conditions = [];
        container.querySelectorAll('.condition-row').forEach(function (row) {
            const field = row.querySelector('.cond-field').value;
            const opEl  = row.querySelector('.cond-op');
            const op    = opEl && !opEl.classList.contains('d-none') ? opEl.value : 'equals';
            const value = row.querySelector('.cond-value').value;
            if (value === '') return;
            const cond = { field, op, value };
            if (field === 'delivery_status') {
                const campEl = row.querySelector('.cond-campaign-id');
                if (campEl && campEl.value) cond.campaign_id = parseInt(campEl.value);
            }
            conditions.push(cond);
        });
        return {
            operator: document.getElementById('seg-operator').value,
            conditions,
        };
    }

    document.getElementById('preview-btn').addEventListener('click', function () {
        const rules = buildRules();
        const resultEl = document.getElementById('preview-result');
        resultEl.textContent = '{{ __('Counting...') }}';

        fetch('{{ route('segments.preview') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ rules }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.error) {
                resultEl.textContent = d.message;
            } else {
                resultEl.textContent = d.count + ' {{ __('contacts match') }}';
            }
        });
    });

    document.getElementById('save-btn').addEventListener('click', function () {
        const name  = document.getElementById('seg-name').value.trim();
        const rules = buildRules();

        if (!name) { alert('{{ __('Please enter a segment name.') }}'); return; }
        if (!rules.conditions.length) { alert('{{ __('Please add at least one condition.') }}'); return; }

        fetch('{{ route('segments.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name, rules }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.error) {
                alert(d.message);
            } else {
                window.location.href = d.redirect;
            }
        });
    });

    // Start with one condition
    addCondition();
})();
</script>

</x-layout-dashboard>
