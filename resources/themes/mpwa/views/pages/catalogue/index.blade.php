<x-layout-dashboard title="Catalogue">

<x-page-header title="{{ __('Catalogue Management') }}"
    subtitle="{{ __('Sync and manage your WhatsApp product catalogues from Facebook Commerce Manager.') }}"
    :breadcrumb="[__('Catalogue')]" />

<div class="row g-4">

    {{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3 d-flex flex-wrap align-items-center gap-3">

                {{-- Device selector --}}
                @if($devices->count() > 1)
                <div>
                    <select id="deviceSelector" class="form-select form-select-sm" style="min-width:200px" onchange="switchDevice(this.value)">
                        @foreach($devices as $d)
                            <option value="{{ $d->id }}" {{ $device?->id == $d->id ? 'selected' : '' }}>
                                {{ $d->body }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="ms-auto d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="syncCatalogues()">
                        <i class="bi bi-arrow-repeat me-1"></i>{{ __('Sync from Meta') }}
                    </button>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCatalogueModal">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('New Catalogue') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Catalogues table ─────────────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($catalogues->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-shop fs-1 d-block mb-3 opacity-25"></i>
                        <p class="mb-1 fw-semibold">{{ __('No catalogues yet') }}</p>
                        <p class="small mb-3">{{ __('Sync your existing catalogues from Meta or create a new one.') }}</p>
                        <a href="https://business.facebook.com/commerce" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('Open Commerce Manager') }}
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Meta Catalog ID') }}</th>
                                    <th>{{ __('Vertical') }}</th>
                                    <th class="text-center">{{ __('Products') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th>{{ __('Last Synced') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($catalogues as $cat)
                                <tr id="cat-row-{{ $cat->id }}">
                                    <td class="fw-semibold">{{ $cat->name }}</td>
                                    <td><code class="small">{{ $cat->meta_catalog_id }}</code></td>
                                    <td><span class="badge bg-light text-dark border">{{ $cat->vertical }}</span></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary" id="product-count-{{ $cat->id }}">{{ $cat->product_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($cat->is_linked)
                                            <span class="badge bg-success"><i class="bi bi-link-45deg me-1"></i>{{ __('Linked') }}</span>
                                        @else
                                            <span class="badge bg-light text-muted border">{{ __('Not linked') }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">
                                        {{ $cat->synced_at ? $cat->synced_at->diffForHumans() : '—' }}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('catalogue.show', $cat->id) }}" class="btn btn-xs btn-outline-secondary" title="{{ __('View Products') }}">
                                                <i class="bi bi-grid"></i>
                                            </a>
                                            <button class="btn btn-xs btn-outline-primary" onclick="syncProducts({{ $cat->id }})" title="{{ __('Sync Products') }}">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                            @if(!$cat->is_linked)
                                            <button class="btn btn-xs btn-outline-success" onclick="linkCatalogue({{ $cat->id }})" title="{{ __('Link to Device') }}">
                                                <i class="bi bi-link-45deg"></i>
                                            </button>
                                            @endif
                                            <button class="btn btn-xs btn-outline-danger" onclick="deleteCatalogue({{ $cat->id }}, '{{ addslashes($cat->name) }}')" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Info card ────────────────────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="alert alert-info small mb-0 d-flex gap-2">
            <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
            <div>
                <strong>{{ __('About WhatsApp Catalogues') }}</strong><br>
                {{ __('Catalogues are managed in') }}
                <a href="https://business.facebook.com/commerce" target="_blank">Facebook Commerce Manager</a>
                {{ __('and linked to your WhatsApp Business Account. Use') }} <strong>{{ __('Sync from Meta') }}</strong>
                {{ __('to import existing catalogues, or create new ones directly from here. The') }}
                <strong>{{ __('Linked') }}</strong>
                {{ __('catalogue is the one currently active on your WhatsApp device for cart and shopping features.') }}
            </div>
        </div>
    </div>

</div>

{{-- ── Create Catalogue Modal ───────────────────────────────────────────────── --}}
<div class="modal fade" id="createCatalogueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-shop me-2"></i>{{ __('New Catalogue') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Catalogue Name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="newCatName" class="form-control form-control-sm" placeholder="{{ __('e.g. My Shop Products') }}" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Description') }}</label>
                    <input type="text" id="newCatDesc" class="form-control form-control-sm" placeholder="{{ __('Optional description') }}" maxlength="255">
                </div>
                <div>
                    <label class="form-label fw-semibold small">{{ __('Vertical') }}</label>
                    <select id="newCatVertical" class="form-select form-select-sm">
                        <option value="COMMERCE">Commerce (General)</option>
                        <option value="AUTOMOTIVE">Automotive</option>
                        <option value="BABY_PRODUCTS">Baby Products</option>
                        <option value="BEAUTY">Beauty</option>
                        <option value="BOOKS">Books</option>
                        <option value="CLOTHING">Clothing & Apparel</option>
                        <option value="CONSUMER_ELECTRONICS">Consumer Electronics</option>
                        <option value="EDUCATION">Education</option>
                        <option value="FOOD_GROCERY">Food & Grocery</option>
                        <option value="FURNITURE">Furniture</option>
                        <option value="HEALTH">Health</option>
                        <option value="HOME_GARDEN">Home & Garden</option>
                        <option value="HOTEL_RENTAL">Hotels & Rentals</option>
                        <option value="MEDIA">Media</option>
                        <option value="PHARMACY">Pharmacy</option>
                        <option value="SPORTS">Sports</option>
                        <option value="TOYS">Toys</option>
                        <option value="TRAVEL">Travel</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-primary" id="createCatBtn" onclick="createCatalogue()">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Create Catalogue') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CURRENT_DEVICE_ID = {{ $device?->id ?? 'null' }};
const SYNC_URL          = '{{ route('catalogue.sync') }}';
const CREATE_URL        = '{{ route('catalogue.create-catalogue') }}';

function switchDevice(deviceId) {
    // Reload with the selected device — reuse the selectedDevice session
    fetch('{{ route('home.setSessionSelectedDevice') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ device_id: deviceId })
    }).then(() => location.reload());
}

function syncCatalogues() {
    toastr.info('{{ __("Syncing catalogues from Meta…") }}');
    fetch(SYNC_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ device_id: CURRENT_DEVICE_ID || null })
    })
    .then(r => r.json())
    .then(d => {
        if (d.status) { toastr.success(d.msg); setTimeout(() => location.reload(), 1000); }
        else toastr.error(d.msg);
    });
}

function createCatalogue() {
    const name = document.getElementById('newCatName').value.trim();
    if (!name) { toastr.warning('{{ __("Catalogue name is required.") }}'); return; }
    const btn = document.getElementById('createCatBtn');
    btn.disabled = true;
    fetch(CREATE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            device_id:   CURRENT_DEVICE_ID,
            name:        name,
            description: document.getElementById('newCatDesc').value.trim(),
            vertical:    document.getElementById('newCatVertical').value,
        })
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        if (d.status) { toastr.success(d.msg); bootstrap.Modal.getInstance(document.getElementById('createCatalogueModal')).hide(); setTimeout(() => location.reload(), 800); }
        else toastr.error(d.msg);
    })
    .catch(() => { btn.disabled = false; toastr.error('{{ __("Request failed.") }}'); });
}

function syncProducts(catId) {
    toastr.info('{{ __("Syncing products…") }}');
    fetch(`/{{ app()->getLocale() }}/catalogue/${catId}/sync-products`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status) {
            toastr.success(d.msg);
            const el = document.getElementById(`product-count-${catId}`);
            if (el) el.textContent = d.count;
        } else toastr.error(d.msg);
    });
}

function linkCatalogue(catId) {
    if (!confirm('{{ __("Link this catalogue to your device? It will become the active catalogue for WhatsApp shopping.") }}')) return;
    fetch(`/{{ app()->getLocale() }}/catalogue/${catId}/link`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status) { toastr.success(d.msg); setTimeout(() => location.reload(), 800); }
        else toastr.error(d.msg);
    });
}

function deleteCatalogue(catId, name) {
    if (!confirm(`{{ __("Delete catalogue") }} "${name}"? {{ __("This cannot be undone.") }}`)) return;
    fetch(`/{{ app()->getLocale() }}/catalogue/${catId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status) { toastr.success(d.msg); document.getElementById(`cat-row-${catId}`)?.remove(); }
        else toastr.error(d.msg);
    });
}
</script>
@endpush

</x-layout-dashboard>
