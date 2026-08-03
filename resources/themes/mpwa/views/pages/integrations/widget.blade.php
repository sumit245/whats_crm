<x-layout-dashboard title="{{ __('Website Chat Widget') }}">

    <x-page-header title="{{ __('Website Chat Widget') }}"
        subtitle="{{ __('Generate a floating WhatsApp button for any website. One script tag — no backend required.') }}"
        :breadcrumb="[__('Integrations'), __('Website Widget')]" />

    @if($devices->isEmpty())
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ __('You need to connect a WhatsApp device first.') }}
            <a href="{{ route('home') }}" class="alert-link">{{ __('Go to Dashboard') }}</a>
        </div>
    @else

    <div class="row g-4">

        {{-- Config Panel --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-primary"></i> {{ __('Widget Settings') }}
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Device (WhatsApp Number)') }}</label>
                        <select id="deviceSelect" class="form-select form-select-sm">
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}"
                                    data-token="{{ $device->widget_token }}"
                                    data-config="{{ json_encode($device->widget_config ?? []) }}">
                                    {{ $device->verified_name }} ({{ $device->body }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Button Text') }}</label>
                        <input type="text" id="cfgButtonText" class="form-control form-control-sm"
                               value="Chat with us" maxlength="60">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Pre-filled Message') }}</label>
                        <input type="text" id="cfgPrefill" class="form-control form-control-sm"
                               placeholder="{{ __('Hello, I need help') }}" maxlength="200">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">{{ __('Button Color') }}</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text p-1">
                                    <input type="color" id="cfgColor" value="#25D366" class="form-control form-control-color border-0 p-0" style="width:28px;height:28px">
                                </span>
                                <input type="text" id="cfgColorText" class="form-control font-monospace" value="#25D366" maxlength="7">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">{{ __('Position') }}</label>
                            <select id="cfgPosition" class="form-select form-select-sm">
                                <option value="bottom-right">{{ __('Bottom Right') }}</option>
                                <option value="bottom-left">{{ __('Bottom Left') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cfgTooltip" checked>
                            <label class="form-check-label small" for="cfgTooltip">{{ __('Show Tooltip') }}</label>
                        </div>
                    </div>
                    <div class="mb-4" id="tooltipTextRow">
                        <label class="form-label small fw-semibold">{{ __('Tooltip Text') }}</label>
                        <input type="text" id="cfgTooltipText" class="form-control form-control-sm"
                               value="{{ __('Chat with us on WhatsApp') }}" maxlength="80">
                    </div>
                    <div class="d-flex gap-2">
                        <button id="saveConfigBtn" class="btn btn-primary btn-sm" onclick="saveConfig()">
                            <i class="bi bi-save"></i> {{ __('Save & Get Embed Code') }}
                        </button>
                        <button id="activateBtn" class="btn btn-outline-success btn-sm d-none" onclick="activateWidget()">
                            <i class="bi bi-link-45deg"></i> {{ __('Generate Token') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Preview + Embed Code --}}
        <div class="col-lg-6">

            {{-- Live Preview --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-eye-fill text-success"></i> {{ __('Live Preview') }}
                </div>
                <div class="card-body" style="min-height:220px;background:#f0f4f8;border-radius:0 0 .5rem .5rem;position:relative">
                    <div id="previewTooltip" class="position-absolute bg-white rounded-pill px-3 py-1 shadow-sm small"
                         style="bottom:80px;right:24px;white-space:nowrap">
                        {{ __('Chat with us on WhatsApp') }}
                    </div>
                    <button id="previewBtn" class="position-absolute d-flex align-items-center gap-2 rounded-pill px-4 py-2 border-0 text-white fw-semibold"
                            style="bottom:24px;right:24px;background:#25D366;box-shadow:0 4px 12px rgba(0,0,0,.2)">
                        <svg width="20" height="20" viewBox="0 0 32 32" fill="currentColor">
                            <path d="M16 3C8.82 3 3 8.82 3 16c0 2.28.61 4.52 1.76 6.49L3 29l6.72-1.74A13 13 0 0 0 16 29c7.18 0 13-5.82 13-13S23.18 3 16 3zm6.45 18.55c-.27.76-1.58 1.46-2.17 1.5-.55.04-1.08.27-3.63-.75-3.05-1.22-5-4.33-5.15-4.53-.15-.2-1.22-1.62-1.22-3.1s.77-2.2 1.04-2.5c.27-.3.6-.37.8-.37.2 0 .4.01.57.01.19 0 .44-.07.69.52.27.62.9 2.2.98 2.36.08.16.13.35.03.56-.1.2-.15.33-.3.5-.15.17-.31.38-.44.51-.15.15-.3.3-.13.6.17.3.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.3.15.47.12.64-.07.17-.2.74-.86.94-1.15.2-.3.4-.24.67-.14.27.1 1.73.82 2.03.97.3.15.5.22.57.34.07.12.07.7-.2 1.47z"/>
                        </svg>
                        <span id="previewLabel">Chat with us</span>
                    </button>
                    <div class="position-absolute top-50 start-50 translate-middle text-muted small opacity-50 text-center" style="pointer-events:none">
                        {{ __('Your website') }}<br>{{ __('(widget shown in corner)') }}
                    </div>
                </div>
            </div>

            {{-- Embed Code --}}
            <div class="card border-0 shadow-sm" id="embedCard">
                <div class="card-header fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-code-slash text-secondary"></i> {{ __('Embed Code') }}
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        {{ __('Paste this anywhere in your website\'s') }} <code>&lt;body&gt;</code>{{ __('. Works on PHP, WordPress, React, plain HTML — anywhere.') }}
                    </p>
                    <div id="embedPlaceholder" class="text-muted small fst-italic">
                        {{ __('Save your settings above to generate the embed code.') }}
                    </div>
                    <div id="embedCodeBox" class="d-none">
                        <div class="position-relative">
                            <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2"
                                    onclick="copyEmbed(this)">
                                <i class="bi bi-clipboard"></i> {{ __('Copy') }}
                            </button>
                            <pre id="embedCode" class="bg-dark text-light rounded p-3 small overflow-auto mb-2"></pre>
                        </div>
                        <div class="alert alert-success small py-2 mb-0">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            {{ __('The widget script is served directly from your platform — no CDN or external dependency needed.') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    @endif

@push('scripts')
<script>
var ACTIVATE_URL = '{{ route('integrations.widget.activate') }}';
var CONFIGURE_URL = '{{ route('integrations.widget.configure') }}';
var CSRF = document.querySelector('meta[name="csrf-token"]').content;

// Live preview sync
function syncPreview() {
    var color  = document.getElementById('cfgColor').value;
    var label  = document.getElementById('cfgButtonText').value || 'Chat with us';
    var pos    = document.getElementById('cfgPosition').value;
    var tipTxt = document.getElementById('cfgTooltipText').value;
    var showTip = document.getElementById('cfgTooltip').checked;
    var btn    = document.getElementById('previewBtn');
    var tip    = document.getElementById('previewTooltip');

    btn.style.background = color;
    document.getElementById('previewLabel').textContent = label;
    tip.textContent = tipTxt;
    tip.style.display = showTip ? '' : 'none';

    if (pos === 'bottom-left') {
        btn.style.right = 'auto'; btn.style.left = '24px';
        tip.style.right = 'auto'; tip.style.left = '24px';
    } else {
        btn.style.left = 'auto'; btn.style.right = '24px';
        tip.style.left = 'auto'; tip.style.right = '24px';
    }
}

// Color picker sync
document.getElementById('cfgColor').addEventListener('input', function () {
    document.getElementById('cfgColorText').value = this.value;
    syncPreview();
});
document.getElementById('cfgColorText').addEventListener('input', function () {
    if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
        document.getElementById('cfgColor').value = this.value;
        syncPreview();
    }
});
['cfgButtonText', 'cfgPrefill', 'cfgPosition', 'cfgTooltipText'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', syncPreview);
});
document.getElementById('cfgTooltip').addEventListener('change', function () {
    document.getElementById('tooltipTextRow').style.display = this.checked ? '' : 'none';
    syncPreview();
});

// Load config when device changes
document.getElementById('deviceSelect').addEventListener('change', loadDeviceConfig);

function loadDeviceConfig() {
    var opt = document.getElementById('deviceSelect').selectedOptions[0];
    var cfg = {};
    try { cfg = JSON.parse(opt.dataset.config || '{}'); } catch(e) {}

    if (cfg.color)        { document.getElementById('cfgColor').value = cfg.color; document.getElementById('cfgColorText').value = cfg.color; }
    if (cfg.position)     document.getElementById('cfgPosition').value = cfg.position;
    if (cfg.button_text)  document.getElementById('cfgButtonText').value = cfg.button_text;
    if (cfg.prefill !== undefined) document.getElementById('cfgPrefill').value = cfg.prefill;
    document.getElementById('cfgTooltip').checked = cfg.show_tooltip !== false;
    if (cfg.tooltip_text) document.getElementById('cfgTooltipText').value = cfg.tooltip_text;

    var token = opt.dataset.token;
    if (token) showEmbedCode(token);
    else hideEmbedCode();

    syncPreview();
}

function showEmbedCode(token) {
    var url = window.location.origin + '/w/' + token + '.js';
    var code = '<script src="' + url + '" async><\/script>';
    document.getElementById('embedCode').textContent = code;
    document.getElementById('embedCodeBox').classList.remove('d-none');
    document.getElementById('embedPlaceholder').classList.add('d-none');
}

function hideEmbedCode() {
    document.getElementById('embedCodeBox').classList.add('d-none');
    document.getElementById('embedPlaceholder').classList.remove('d-none');
}

async function saveConfig() {
    var btn = document.getElementById('saveConfigBtn');
    var deviceId = document.getElementById('deviceSelect').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

    // Step 1: ensure token exists
    var tokenRes = await fetch(ACTIVATE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ device_id: deviceId }),
    });
    var tokenData = await tokenRes.json();
    if (!tokenData.token) {
        alert('{{ __("Could not generate widget token. Try again.") }}');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save"></i> {{ __("Save & Get Embed Code") }}';
        return;
    }

    // Step 2: save config
    await fetch(CONFIGURE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({
            device_id:    deviceId,
            color:        document.getElementById('cfgColor').value,
            position:     document.getElementById('cfgPosition').value,
            button_text:  document.getElementById('cfgButtonText').value,
            prefill:      document.getElementById('cfgPrefill').value,
            show_tooltip: document.getElementById('cfgTooltip').checked ? 1 : 0,
            tooltip_text: document.getElementById('cfgTooltipText').value,
        }),
    });

    // Update option dataset so embed code persists
    var opt = document.getElementById('deviceSelect').selectedOptions[0];
    opt.dataset.token = tokenData.token;

    showEmbedCode(tokenData.token);
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2"></i> {{ __("Saved!") }}';
    setTimeout(function () {
        btn.innerHTML = '<i class="bi bi-save"></i> {{ __("Save & Get Embed Code") }}';
    }, 3000);
}

function copyToClipboard(text, onSuccess) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(function () { fallbackCopy(text, onSuccess); });
    } else {
        fallbackCopy(text, onSuccess);
    }
}
function fallbackCopy(text, onSuccess) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try { document.execCommand('copy'); onSuccess(); } catch(e) { alert('Copy failed. Please copy manually.'); }
    document.body.removeChild(ta);
}
function copyEmbed(btn) {
    var code = document.getElementById('embedCode').textContent;
    copyToClipboard(code, function () {
        btn.innerHTML = '<i class="bi bi-check2"></i> {{ __("Copied!") }}';
        setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i> {{ __("Copy") }}'; }, 2500);
    });
}

// Init
loadDeviceConfig();
</script>
@endpush

</x-layout-dashboard>
