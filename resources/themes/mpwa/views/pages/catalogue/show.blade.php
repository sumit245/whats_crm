<x-layout-dashboard title="Products">

<x-page-header title="{{ $catalogue->name }}"
    subtitle="{{ __('Manage products in this catalogue.') }} <code class='small'>{{ $catalogue->meta_catalog_id }}</code>"
    :breadcrumb="[__('Catalogue'), $catalogue->name]" />

{{-- ── Toolbar ──────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3 d-flex flex-wrap align-items-center gap-3">
        <div class="d-flex gap-2 align-items-center">
            @if($catalogue->is_linked)
                <span class="badge bg-success"><i class="bi bi-link-45deg me-1"></i>{{ __('Linked to device') }}</span>
            @else
                <span class="badge bg-light text-muted border">{{ __('Not linked') }}</span>
                <button class="btn btn-xs btn-outline-success" onclick="linkCatalogue()">
                    <i class="bi bi-link-45deg me-1"></i>{{ __('Link to Device') }}
                </button>
            @endif
        </div>
        <div class="ms-auto d-flex gap-2">
            <input type="text" id="productSearch" class="form-control form-control-sm" placeholder="{{ __('Search products…') }}" style="width:200px" oninput="filterProducts(this.value)">
            <button class="btn btn-sm btn-outline-primary" onclick="syncProducts()">
                <i class="bi bi-arrow-repeat me-1"></i>{{ __('Sync Products') }}
            </button>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Product') }}
            </button>
            <a href="{{ route('catalogue.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
            </a>
        </div>
    </div>
</div>

{{-- ── Products grid ────────────────────────────────────────────────────────── --}}
@if($products->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-25"></i>
            <p class="fw-semibold mb-1">{{ __('No products yet') }}</p>
            <p class="small mb-3">{{ __('Sync from Meta or add products manually.') }}</p>
        </div>
    </div>
@else
    <div class="row g-3" id="productGrid">
        @foreach($products as $product)
        <div class="col-md-4 col-lg-3 product-card" data-name="{{ strtolower($product->name) }}">
            <div class="card border-0 shadow-sm h-100">
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" class="card-img-top" style="height:160px;object-fit:cover" alt="{{ $product->name }}"
                         onerror="this.style.display='none'">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height:160px">
                        <i class="bi bi-image text-muted fs-2"></i>
                    </div>
                @endif
                <div class="card-body p-3">
                    <p class="fw-semibold mb-1 small">{{ $product->name }}</p>
                    <p class="text-muted small mb-2" style="font-size:11px">{{ __('SKU') }}: <code>{{ $product->retailer_id }}</code></p>
                    @if($product->price)
                        <p class="fw-bold text-success mb-1 small">
                            {{ $product->currency }} {{ number_format($product->price_decimal, 2) }}
                            @if($product->sale_price)
                                <span class="text-muted text-decoration-line-through small">{{ number_format($product->sale_price_decimal, 2) }}</span>
                            @endif
                        </p>
                    @endif
                    <span class="badge {{ $product->availability === 'in stock' ? 'bg-success' : 'bg-secondary' }} small">
                        {{ $product->availability }}
                    </span>
                </div>
                <div class="card-footer bg-transparent border-top-0 pt-0 px-3 pb-3 d-flex gap-1">
                    <button class="btn btn-xs btn-outline-primary flex-fill" onclick="editProduct({{ $product->id }})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-danger" onclick="deleteProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-3">{{ $products->links() }}</div>
@endif

{{-- ── Add Product Modal ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-box-seam me-2"></i>{{ __('Add Product') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">{{ __('Product Name') }} <span class="text-danger">*</span></label>
                        <input type="text" id="pName" class="form-control form-control-sm" maxlength="200">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">{{ __('Retailer ID / SKU') }} <span class="text-danger">*</span></label>
                        <input type="text" id="pRetailerId" class="form-control form-control-sm" maxlength="100">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ __('Description') }}</label>
                        <textarea id="pDescription" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Price') }} <span class="text-danger">*</span></label>
                        <input type="number" id="pPrice" class="form-control form-control-sm" step="0.01" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Sale Price') }}</label>
                        <input type="number" id="pSalePrice" class="form-control form-control-sm" step="0.01" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Currency') }} <span class="text-danger">*</span></label>
                        <input type="text" id="pCurrency" class="form-control form-control-sm" maxlength="3" placeholder="INR" value="INR">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ __('Image URL') }} <span class="text-danger">*</span></label>
                        <input type="url" id="pImageUrl" class="form-control form-control-sm" placeholder="https://...">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ __('Product URL') }}</label>
                        <input type="url" id="pProductUrl" class="form-control form-control-sm" placeholder="https://...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Availability') }}</label>
                        <select id="pAvailability" class="form-select form-select-sm">
                            <option value="in stock">In Stock</option>
                            <option value="out of stock">Out of Stock</option>
                            <option value="preorder">Preorder</option>
                            <option value="available for order">Available for Order</option>
                            <option value="discontinued">Discontinued</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Condition') }}</label>
                        <select id="pCondition" class="form-select form-select-sm">
                            <option value="new">New</option>
                            <option value="refurbished">Refurbished</option>
                            <option value="used">Used</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Brand') }}</label>
                        <input type="text" id="pBrand" class="form-control form-control-sm" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">{{ __('Category') }}</label>
                        <input type="text" id="pCategory" class="form-control form-control-sm" maxlength="100">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-primary" id="addProductBtn" onclick="submitProduct()">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Add Product') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Edit Product Modal ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-pencil me-2"></i>{{ __('Edit Product') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editProductId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">{{ __('Product Name') }}</label>
                        <input type="text" id="eName" class="form-control form-control-sm" maxlength="200">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">{{ __('Retailer ID / SKU') }}</label>
                        <input type="text" id="eRetailerId" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ __('Description') }}</label>
                        <textarea id="eDescription" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Price') }}</label>
                        <input type="number" id="ePrice" class="form-control form-control-sm" step="0.01" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Sale Price') }}</label>
                        <input type="number" id="eSalePrice" class="form-control form-control-sm" step="0.01" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Currency') }}</label>
                        <input type="text" id="eCurrency" class="form-control form-control-sm" maxlength="3">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ __('Image URL') }}</label>
                        <input type="url" id="eImageUrl" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ __('Product URL') }}</label>
                        <input type="url" id="eProductUrl" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Availability') }}</label>
                        <select id="eAvailability" class="form-select form-select-sm">
                            <option value="in stock">In Stock</option>
                            <option value="out of stock">Out of Stock</option>
                            <option value="preorder">Preorder</option>
                            <option value="available for order">Available for Order</option>
                            <option value="discontinued">Discontinued</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Condition') }}</label>
                        <select id="eCondition" class="form-select form-select-sm">
                            <option value="new">New</option>
                            <option value="refurbished">Refurbished</option>
                            <option value="used">Used</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ __('Brand') }}</label>
                        <input type="text" id="eBrand" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-sm btn-primary" id="saveProductBtn" onclick="saveProduct()">
                    <i class="bi bi-check2 me-1"></i>{{ __('Save Changes') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CATALOGUE_ID   = {{ $catalogue->id }};
const LOCALE         = '{{ app()->getLocale() }}';

// Stored products for edit modal
const PRODUCTS = @json($products->keyBy('id')->map(fn($p) => [
    'id'           => $p->id,
    'retailer_id'  => $p->retailer_id,
    'name'         => $p->name,
    'description'  => $p->description,
    'price'        => $p->price_decimal,
    'sale_price'   => $p->sale_price_decimal,
    'currency'     => $p->currency,
    'image_url'    => $p->image_url,
    'product_url'  => $p->product_url,
    'availability' => $p->availability,
    'condition'    => $p->condition,
    'brand'        => $p->brand,
]));

function filterProducts(q) {
    document.querySelectorAll('.product-card').forEach(el => {
        el.style.display = el.dataset.name.includes(q.toLowerCase()) ? '' : 'none';
    });
}

function syncProducts() {
    toastr.info('Syncing products…');
    fetch(`/${LOCALE}/catalogue/${CATALOGUE_ID}/sync-products`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status) { toastr.success(d.msg); setTimeout(() => location.reload(), 800); }
        else toastr.error(d.msg);
    });
}

function linkCatalogue() {
    if (!confirm('{{ __("Link this catalogue to your device?") }}')) return;
    fetch(`/${LOCALE}/catalogue/${CATALOGUE_ID}/link`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status) { toastr.success(d.msg); setTimeout(() => location.reload(), 800); }
        else toastr.error(d.msg);
    });
}

function submitProduct() {
    const name = document.getElementById('pName').value.trim();
    const rid  = document.getElementById('pRetailerId').value.trim();
    const price = document.getElementById('pPrice').value;
    const currency = document.getElementById('pCurrency').value.trim();
    const imageUrl = document.getElementById('pImageUrl').value.trim();
    if (!name || !rid || !price || !currency || !imageUrl) {
        toastr.warning('{{ __("Please fill all required fields.") }}'); return;
    }
    const btn = document.getElementById('addProductBtn');
    btn.disabled = true;
    fetch(`/${LOCALE}/catalogue/${CATALOGUE_ID}/products`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            retailer_id:  rid,
            name:         name,
            description:  document.getElementById('pDescription').value,
            price:        price,
            sale_price:   document.getElementById('pSalePrice').value || null,
            currency:     currency,
            image_url:    imageUrl,
            product_url:  document.getElementById('pProductUrl').value,
            availability: document.getElementById('pAvailability').value,
            condition:    document.getElementById('pCondition').value,
            brand:        document.getElementById('pBrand').value,
            category:     document.getElementById('pCategory').value,
        })
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        if (d.status) {
            toastr.success(d.msg);
            bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
            setTimeout(() => location.reload(), 800);
        } else toastr.error(d.msg);
    })
    .catch(() => { btn.disabled = false; toastr.error('Request failed.'); });
}

function editProduct(id) {
    const p = PRODUCTS[id];
    if (!p) return;
    document.getElementById('editProductId').value   = p.id;
    document.getElementById('eName').value           = p.name;
    document.getElementById('eRetailerId').value     = p.retailer_id;
    document.getElementById('eDescription').value    = p.description || '';
    document.getElementById('ePrice').value          = p.price || '';
    document.getElementById('eSalePrice').value      = p.sale_price || '';
    document.getElementById('eCurrency').value       = p.currency || '';
    document.getElementById('eImageUrl').value       = p.image_url || '';
    document.getElementById('eProductUrl').value     = p.product_url || '';
    document.getElementById('eAvailability').value   = p.availability || 'in stock';
    document.getElementById('eCondition').value      = p.condition || 'new';
    document.getElementById('eBrand').value          = p.brand || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editProductModal')).show();
}

function saveProduct() {
    const id  = document.getElementById('editProductId').value;
    const btn = document.getElementById('saveProductBtn');
    btn.disabled = true;
    fetch(`/${LOCALE}/catalogue/products/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            name:         document.getElementById('eName').value,
            description:  document.getElementById('eDescription').value,
            price:        document.getElementById('ePrice').value,
            sale_price:   document.getElementById('eSalePrice').value || null,
            currency:     document.getElementById('eCurrency').value,
            image_url:    document.getElementById('eImageUrl').value,
            product_url:  document.getElementById('eProductUrl').value,
            availability: document.getElementById('eAvailability').value,
            condition:    document.getElementById('eCondition').value,
            brand:        document.getElementById('eBrand').value,
        })
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        if (d.status) {
            toastr.success(d.msg);
            bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
            setTimeout(() => location.reload(), 600);
        } else toastr.error(d.msg);
    })
    .catch(() => { btn.disabled = false; toastr.error('Request failed.'); });
}

function deleteProduct(id, name) {
    if (!confirm(`Delete "${name}"? This removes it from Meta too.`)) return;
    fetch(`/${LOCALE}/catalogue/products/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status) { toastr.success(d.msg); setTimeout(() => location.reload(), 600); }
        else toastr.error(d.msg);
    });
}
</script>
@endpush

</x-layout-dashboard>
