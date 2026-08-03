<x-layout-dashboard title="Conversation Labels">

<x-page-header title="{{ __('Conversation Labels') }}"
    subtitle="{{ __('Create and manage labels to organise conversations in Live Chat.') }}"
    :breadcrumb="[__('Live Chat'), __('Labels')]" />

<div class="row g-4">

    {{-- ── Labels list ──────────────────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                <h6 class="mb-0 fw-semibold">{{ __('Your Labels') }}</h6>
                <span class="badge bg-secondary" id="labelCount">{{ $labels->count() }}</span>
            </div>

            @if($labels->isEmpty())
                <div class="card-body text-center py-5 text-muted" id="emptyState">
                    <i class="bi bi-tags fs-1 d-block mb-3 opacity-25"></i>
                    <p class="fw-semibold mb-1">{{ __('No labels yet') }}</p>
                    <p class="small">{{ __('Create your first label to start organising conversations.') }}</p>
                </div>
            @endif

            <ul class="list-group list-group-flush" id="labelList">
                @foreach($labels as $label)
                <li class="list-group-item d-flex align-items-center gap-3 px-4 py-3 label-item" data-id="{{ $label->id }}">
                    <i class="bi bi-grip-vertical text-muted" style="cursor:grab" title="{{ __('Drag to reorder') }}"></i>
                    <span class="rounded-circle flex-shrink-0" style="width:14px;height:14px;background:{{ $label->color }};display:inline-block"></span>
                    <span class="fw-semibold flex-grow-1 label-name">{{ $label->name }}</span>
                    <span class="badge bg-light text-muted border small">
                        {{ $label->conversations()->count() }} {{ __('conversations') }}
                    </span>
                    <button class="btn btn-xs btn-outline-secondary" onclick="openEdit({{ $label->id }}, '{{ addslashes($label->name) }}', '{{ $label->color }}')" title="{{ __('Edit') }}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-danger" onclick="deleteLabel({{ $label->id }}, '{{ addslashes($label->name) }}')" title="{{ __('Delete') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- ── Create / Edit form ───────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-semibold" id="formTitle">{{ __('New Label') }}</h6>
            </div>
            <div class="card-body">
                <input type="hidden" id="editId" value="">

                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Label Name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="labelName" class="form-control" placeholder="{{ __('e.g. VIP, Follow-up, Support') }}" maxlength="64">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small d-block">{{ __('Color') }}</label>
                    <div class="d-flex flex-wrap gap-2 mb-2" id="colorSwatches">
                        @foreach(['#ef4444','#f97316','#eab308','#22c55e','#14b8a6','#3b82f6','#8b5cf6','#ec4899','#64748b','#000000'] as $c)
                        <span class="color-swatch rounded-circle" data-color="{{ $c }}"
                              style="width:28px;height:28px;background:{{ $c }};cursor:pointer;border:2px solid transparent;display:inline-block"
                              onclick="pickColor('{{ $c }}')"></span>
                        @endforeach
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" id="labelColor" class="form-control form-control-color" value="#3b82f6" style="width:48px;height:38px">
                        <span class="small text-muted">{{ __('or pick custom') }}</span>
                    </div>
                </div>

                {{-- Preview --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold small">{{ __('Preview') }}</label>
                    <div>
                        <span id="labelPreview" class="badge" style="background:#3b82f6;font-size:13px">Label</span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" id="saveBtn" onclick="saveLabel()">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Create Label') }}
                    </button>
                    <button class="btn btn-outline-secondary d-none" id="cancelBtn" onclick="cancelEdit()">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Usage tip ─────────────────────────────────────────────────────── --}}
        <div class="alert alert-info small mt-3 d-flex gap-2">
            <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
            <div>
                {{ __('Labels appear in Live Chat. Click the tag icon on any conversation to attach or remove labels. Drag rows to reorder.') }}
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const CSRF        = '{{ csrf_token() }}';
const STORE_URL   = '{{ route('chat.labels.store') }}';
const REORDER_URL = '{{ route('chat.labels.reorder') }}';

function labelUrl(id) {
    return '{{ url(app()->getLocale() . '/chat/labels') }}/' + id;
}

// Live preview
document.getElementById('labelName').addEventListener('input', updatePreview);
document.getElementById('labelColor').addEventListener('input', function() {
    updatePreview();
    highlightSwatch(this.value);
});

function updatePreview() {
    const name  = document.getElementById('labelName').value.trim() || 'Label';
    const color = document.getElementById('labelColor').value;
    const el    = document.getElementById('labelPreview');
    el.textContent      = name;
    el.style.background = color;
}

function pickColor(hex) {
    document.getElementById('labelColor').value = hex;
    highlightSwatch(hex);
    updatePreview();
}

function highlightSwatch(hex) {
    document.querySelectorAll('.color-swatch').forEach(s => {
        s.style.borderColor = s.dataset.color.toLowerCase() === hex.toLowerCase() ? '#1e293b' : 'transparent';
    });
}

highlightSwatch(document.getElementById('labelColor').value);

// Save (create or update)
function saveLabel() {
    const name  = document.getElementById('labelName').value.trim();
    const color = document.getElementById('labelColor').value;
    const id    = document.getElementById('editId').value;
    if (!name) { toastr.warning('{{ __("Label name is required.") }}'); return; }

    const method = id ? 'PUT' : 'POST';
    const url    = id ? labelUrl(id) : STORE_URL;

    fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, color })
    })
    .then(r => {
        if (!r.ok) return r.json().then(e => Promise.reject(e));
        return r.json();
    })
    .then(() => {
        toastr.success(id ? '{{ __("Label updated.") }}' : '{{ __("Label created.") }}');
        setTimeout(() => location.reload(), 600);
    })
    .catch(e => {
        const msg = e?.message ?? (e?.errors ? Object.values(e.errors).flat().join(' ') : '{{ __("Failed to save label.") }}');
        toastr.error(msg);
    });
}

// Edit
function openEdit(id, name, color) {
    document.getElementById('editId').value      = id;
    document.getElementById('labelName').value   = name;
    document.getElementById('labelColor').value  = color;
    document.getElementById('formTitle').textContent = '{{ __("Edit Label") }}';
    document.getElementById('saveBtn').innerHTML = '<i class="bi bi-check2 me-1"></i>{{ __("Save Changes") }}';
    document.getElementById('cancelBtn').classList.remove('d-none');
    highlightSwatch(color);
    updatePreview();
    document.getElementById('labelName').focus();
}

function cancelEdit() {
    document.getElementById('editId').value      = '';
    document.getElementById('labelName').value   = '';
    document.getElementById('labelColor').value  = '#3b82f6';
    document.getElementById('formTitle').textContent = '{{ __("New Label") }}';
    document.getElementById('saveBtn').innerHTML = '<i class="bi bi-plus-lg me-1"></i>{{ __("Create Label") }}';
    document.getElementById('cancelBtn').classList.add('d-none');
    highlightSwatch('#3b82f6');
    updatePreview();
}

// Delete
function deleteLabel(id, name) {
    if (!confirm(`{{ __("Delete label") }} "${name}"? {{ __("It will be removed from all conversations.") }}`)) return;
    fetch(labelUrl(id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF }
    })
    .then(r => r.json())
    .then(() => {
        toastr.success('{{ __("Label deleted.") }}');
        document.querySelector(`[data-id="${id}"]`)?.remove();
        const count = document.querySelectorAll('.label-item').length;
        document.getElementById('labelCount').textContent = count;
        if (count === 0) document.getElementById('emptyState')?.classList.remove('d-none');
    })
    .catch(() => toastr.error('{{ __("Delete failed.") }}'));
}

// Drag-to-reorder (graceful if SortableJS CDN fails)
if (typeof Sortable !== 'undefined') {
    Sortable.create(document.getElementById('labelList'), {
        handle: '.bi-grip-vertical',
        animation: 150,
        onEnd: function() {
            const order = [...document.querySelectorAll('.label-item')].map(el => el.dataset.id);
            fetch(REORDER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ order })
            });
        }
    });
}
</script>
@endpush

</x-layout-dashboard>
