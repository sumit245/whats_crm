<x-layout-dashboard title="WhatsApp Link Generator">

<x-page-header title="{{ __('WhatsApp Link Generator') }}"
    subtitle="{{ __('Create shareable wa.me links with optional pre-filled messages, and generate QR codes.') }}"
    :breadcrumb="[__('Tools'), __('Link Generator')]" />

<div class="row g-4">

    {{-- ── Builder ──────────────────────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-link-45deg me-2"></i>{{ __('Configure Link') }}</h6>
            </div>
            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" id="phoneInput" class="form-control"
                               placeholder="{{ __('Country code + number, e.g. 918521384918') }}"
                               value="{{ preg_replace('/\D/', '', $phone) }}"
                               oninput="generate()">
                    </div>
                    <div class="form-text">{{ __('Include country code, no + or spaces. E.g.') }} <code>918521384918</code></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Pre-filled Message') }} <small class="text-muted">({{ __('optional') }})</small></label>
                    <textarea id="messageInput" class="form-control" rows="3"
                              placeholder="{{ __('Hello! I am interested in...') }}"
                              oninput="generate()"></textarea>
                    <div class="form-text d-flex justify-content-between">
                        <span>{{ __('Appears in the recipient\'s chat box when they open the link.') }}</span>
                        <span id="charCount" class="text-muted">0</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">{{ __('Link Type') }}</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="linkType" id="typeWaMe" value="wa.me" checked onchange="generate()">
                            <label class="form-check-label small" for="typeWaMe">wa.me <span class="text-muted">({{ __('universal') }})</span></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="linkType" id="typeApi" value="api.whatsapp.com" onchange="generate()">
                            <label class="form-check-label small" for="typeApi">api.whatsapp.com <span class="text-muted">({{ __('business') }})</span></label>
                        </div>
                    </div>
                </div>

                {{-- Generated link --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Generated Link') }}</label>
                    <div class="input-group">
                        <input type="text" id="generatedLink" class="form-control font-monospace small" readonly
                               placeholder="{{ __('Enter a phone number above…') }}">
                        <button class="btn btn-outline-primary" onclick="copyLink()" id="copyBtn" title="{{ __('Copy') }}">
                            <i class="bi bi-clipboard"></i>
                        </button>
                        <a id="testLink" href="#" target="_blank" class="btn btn-outline-success" title="{{ __('Open') }}">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                </div>

                {{-- Short link note --}}
                <div class="alert alert-light border small py-2 mb-0">
                    <i class="bi bi-lightbulb me-1 text-warning"></i>
                    {{ __('Tip: Use this link in your email signature, Instagram bio, website, or business card.') }}
                </div>
            </div>
        </div>

        {{-- ── HTML Button snippet ──────────────────────────────────────────── --}}
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-code-slash me-2"></i>{{ __('HTML Button Snippet') }}</h6>
                <button class="btn btn-xs btn-outline-secondary" onclick="copyHtml()"><i class="bi bi-clipboard me-1"></i>{{ __('Copy') }}</button>
            </div>
            <div class="card-body p-0">
                <pre id="htmlSnippet" class="bg-dark text-light rounded-bottom p-3 small mb-0" style="white-space:pre-wrap;word-break:break-all"></pre>
            </div>
        </div>
    </div>

    {{-- ── QR Code ──────────────────────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-qr-code me-2"></i>{{ __('QR Code') }}</h6>
            </div>
            <div class="card-body text-center">

                <div id="qrPlaceholder" class="text-muted py-5">
                    <i class="bi bi-qr-code fs-1 d-block mb-3 opacity-25"></i>
                    <p class="small mb-0">{{ __('Enter a phone number to generate the QR code.') }}</p>
                </div>

                <div id="qrContainer" style="display:none">
                    <div class="d-flex justify-content-center mb-3">
                        <div id="qrCode" class="p-3 bg-white border rounded d-inline-block"></div>
                    </div>

                    {{-- QR size --}}
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
                        <label class="small fw-semibold">{{ __('Size') }}</label>
                        <input type="range" id="qrSize" min="128" max="512" step="32" value="200" class="form-range" style="width:160px" oninput="renderQr()">
                        <span id="qrSizeLabel" class="small text-muted">200px</span>
                    </div>

                    {{-- QR color --}}
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
                        <label class="small fw-semibold">{{ __('Color') }}</label>
                        <input type="color" id="qrColor" value="#000000" class="form-control form-control-color" style="width:40px;height:32px" oninput="renderQr()">
                        <label class="small fw-semibold ms-2">{{ __('Background') }}</label>
                        <input type="color" id="qrBg" value="#ffffff" class="form-control form-control-color" style="width:40px;height:32px" oninput="renderQr()">
                    </div>

                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-primary" onclick="downloadQr('png')">
                            <i class="bi bi-download me-1"></i>{{ __('Download PNG') }}
                        </button>
                        <button class="btn btn-outline-secondary" onclick="downloadQr('svg')">
                            <i class="bi bi-filetype-svg me-1"></i>{{ __('Download SVG') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Use cases ────────────────────────────────────────────────────── --}}
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-semibold">{{ __('Common Use Cases') }}</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-2 small"><i class="bi bi-instagram me-2 text-danger"></i>{{ __('Instagram / Facebook bio link') }}</li>
                    <li class="list-group-item px-4 py-2 small"><i class="bi bi-envelope me-2 text-primary"></i>{{ __('Email signature CTA button') }}</li>
                    <li class="list-group-item px-4 py-2 small"><i class="bi bi-printer me-2 text-secondary"></i>{{ __('Business card / flyer QR code') }}</li>
                    <li class="list-group-item px-4 py-2 small"><i class="bi bi-globe me-2 text-success"></i>{{ __('Website "Chat with us" button') }}</li>
                    <li class="list-group-item px-4 py-2 small"><i class="bi bi-shop me-2 text-warning"></i>{{ __('Physical store QR code at counter') }}</li>
                </ul>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
let qrInstance = null;
let currentLink = '';

function generate() {
    const phone   = document.getElementById('phoneInput').value.replace(/\D/g, '');
    const message = document.getElementById('messageInput').value;
    const type    = document.querySelector('input[name="linkType"]:checked').value;

    document.getElementById('charCount').textContent = message.length;

    if (!phone) {
        document.getElementById('generatedLink').value = '';
        document.getElementById('testLink').href = '#';
        document.getElementById('qrPlaceholder').style.display = '';
        document.getElementById('qrContainer').style.display   = 'none';
        document.getElementById('htmlSnippet').textContent = '';
        return;
    }

    const base = `https://${type}/${phone}`;
    const link = message ? `${base}?text=${encodeURIComponent(message)}` : base;
    currentLink = link;

    document.getElementById('generatedLink').value = link;
    document.getElementById('testLink').href       = link;

    // HTML snippet
    document.getElementById('htmlSnippet').textContent =
        `<a href="${link}" target="_blank" rel="noopener"\n   style="display:inline-flex;align-items:center;gap:8px;\n          background:#25D366;color:#fff;padding:10px 20px;\n          border-radius:6px;text-decoration:none;font-family:sans-serif">\n  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="white">\n    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>\n    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.122 1.533 5.852L0 24l6.335-1.51A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.371l-.36-.214-3.72.888.934-3.617-.235-.372A9.818 9.818 0 1112 21.818z"/>\n  </svg>\n  Chat on WhatsApp\n</a>`;

    // QR
    document.getElementById('qrPlaceholder').style.display = 'none';
    document.getElementById('qrContainer').style.display   = '';
    renderQr();
}

function renderQr() {
    if (!currentLink) return;
    const size  = parseInt(document.getElementById('qrSize').value);
    const color = document.getElementById('qrColor').value;
    const bg    = document.getElementById('qrBg').value;
    document.getElementById('qrSizeLabel').textContent = `${size}px`;

    const container = document.getElementById('qrCode');
    container.innerHTML = '';

    qrInstance = new QRCode(container, {
        text:            currentLink,
        width:           size,
        height:          size,
        colorDark:       color,
        colorLight:      bg,
        correctLevel:    QRCode.CorrectLevel.H,
    });
}

function copyLink() {
    const val = document.getElementById('generatedLink').value;
    if (!val) return;
    const btn = document.getElementById('copyBtn');
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(val).then(() => flashBtn(btn));
    } else {
        const ta = document.createElement('textarea');
        ta.value = val; ta.style.cssText = 'position:fixed;top:-9999px;opacity:0';
        document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        flashBtn(btn);
    }
}

function flashBtn(btn) {
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check2"></i>';
    setTimeout(() => btn.innerHTML = orig, 2000);
}

function copyHtml() {
    const text = document.getElementById('htmlSnippet').textContent;
    if (!text) { toastr.warning('{{ __("Generate a link first.") }}'); return; }
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => toastr.success('{{ __("HTML copied!") }}'));
    } else {
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.cssText = 'position:fixed;top:-9999px;opacity:0';
        document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        toastr.success('{{ __("HTML copied!") }}');
    }
}

function downloadQr(format) {
    if (!currentLink) { toastr.warning('{{ __("Generate a link first.") }}'); return; }
    const phone = document.getElementById('phoneInput').value.replace(/\D/g,'');

    if (format === 'png') {
        const canvas = document.querySelector('#qrCode canvas');
        if (!canvas) { toastr.error('QR not ready yet.'); return; }
        const a = document.createElement('a');
        a.download = `wa-link-${phone}.png`;
        a.href = canvas.toDataURL('image/png');
        a.click();
    } else {
        // SVG fallback: wrap QR canvas as inline image in SVG
        const canvas = document.querySelector('#qrCode canvas');
        const size   = parseInt(document.getElementById('qrSize').value);
        const svg    = `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}"><image href="${canvas.toDataURL()}" width="${size}" height="${size}"/></svg>`;
        const blob   = new Blob([svg], { type: 'image/svg+xml' });
        const a = document.createElement('a');
        a.download = `wa-link-${phone}.svg`;
        a.href = URL.createObjectURL(blob);
        a.click();
        URL.revokeObjectURL(a.href);
    }
}

// Auto-generate if phone pre-filled from device
window.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('phoneInput').value) generate();
});
</script>
@endpush

</x-layout-dashboard>
