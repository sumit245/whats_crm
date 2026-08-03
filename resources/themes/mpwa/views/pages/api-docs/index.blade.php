<x-layout-dashboard title="API Docs">

<x-page-header title="{{ __('API Documentation') }}"
    subtitle="{{ __('Complete reference for the REST API — send messages, manage contacts, and receive webhooks.') }}"
    :breadcrumb="[__('Developer'), __('API Docs')]" />

<div class="row g-4">

    {{-- ── Left nav ─────────────────────────────────────────────────────────── --}}
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm sticky-top" style="top:80px">
            <div class="card-body p-2">
                <div class="small text-muted fw-semibold px-2 pt-1 pb-2 text-uppercase" style="font-size:11px;letter-spacing:.5px">Getting Started</div>
                <ul class="nav nav-pills flex-column gap-1" id="apiNav">
                    <li class="nav-item"><a class="nav-link py-1 active" data-bs-toggle="pill" href="#tab-auth">Authentication</a></li>
                    <div class="small text-muted fw-semibold px-2 pt-2 pb-1 text-uppercase" style="font-size:11px;letter-spacing:.5px">Messaging</div>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendmessage">Send Text</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendmedia">Send Media</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendtemplate">Send Template</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendbutton">Send Buttons</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendlist">Send List</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendlocation">Send Location</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendvcard">Send vCard</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendsticker">Send Sticker</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-sendpoll">Send Poll</a></li>
                    <div class="small text-muted fw-semibold px-2 pt-2 pb-1 text-uppercase" style="font-size:11px;letter-spacing:.5px">Contacts</div>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-enroll">Enroll Contact</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-checknumber">Check Number</a></li>
                    <div class="small text-muted fw-semibold px-2 pt-2 pb-1 text-uppercase" style="font-size:11px;letter-spacing:.5px">Webhooks</div>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-webhook">Inbound Webhook</a></li>
                    <div class="small text-muted fw-semibold px-2 pt-2 pb-1 text-uppercase" style="font-size:11px;letter-spacing:.5px">Device</div>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-deviceinfo">Device Info</a></li>
                    <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="pill" href="#tab-disconnect">Disconnect</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ── Content ──────────────────────────────────────────────────────────── --}}
    <div class="col-lg-9">
        <div class="tab-content">

            {{-- AUTH ──────────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade show active" id="tab-auth">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">Authentication</h5>
                        <p class="text-muted small mb-3">Every request must include <code>api_key</code> and <code>sender</code>. Pass them as POST body fields or GET query parameters.</p>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Your API Key</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" id="docsApiKey" class="form-control font-monospace" value="{{ $apiKey }}" readonly>
                                    <button class="btn btn-outline-secondary" onclick="toggleKey()"><i class="bi bi-eye" id="keyEye"></i></button>
                                    <button class="btn btn-outline-primary" onclick="copyVal('docsApiKey', this)"><i class="bi bi-clipboard"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Your Sender (device name)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="docsSender" class="form-control font-monospace" value="{{ $sender }}" readonly>
                                    <button class="btn btn-outline-primary" onclick="copyVal('docsSender', this)"><i class="bi bi-clipboard"></i></button>
                                </div>
                                <div class="form-text">The <code>body</code> field of your connected device. Not necessarily a phone number.</div>
                            </div>
                        </div>

                        <label class="form-label small fw-semibold">Base URL</label>
                        <div class="input-group input-group-sm mb-3" style="max-width:480px">
                            <input type="text" id="docsBase" class="form-control font-monospace" value="{{ $baseUrl }}" readonly>
                            <button class="btn btn-outline-primary" onclick="copyVal('docsBase', this)"><i class="bi bi-clipboard"></i></button>
                        </div>

                        <div class="alert alert-info small py-2 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            All endpoints are relative to the base URL above. Example:
                            <code>{{ $baseUrl }}/send-message</code>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2">Response format</h6>
                        <p class="text-muted small">All endpoints return JSON.</p>
                        <pre class="bg-dark text-light rounded p-3 small">// Success
{ "status": true,  "msg": "Message sent successfully!" }

// Failure
{ "status": false, "msg": "Error description" }</pre>
                        <h6 class="fw-semibold mb-2 mt-3">Multiple recipients</h6>
                        <p class="text-muted small mb-1">Separate numbers with <code>|</code> in the <code>number</code> field:</p>
                        <pre class="bg-dark text-light rounded p-3 small">"number": "918521384918|919876543210"</pre>
                    </div>
                </div>
            </div>

            {{-- SEND TEXT ──────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendmessage">
                @include('theme::pages.api-docs.send-message')
            </div>

            {{-- SEND MEDIA ─────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendmedia">
                @include('theme::pages.api-docs.send-media')
            </div>

            {{-- SEND TEMPLATE ──────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendtemplate">
                @include('theme::pages.api-docs.send-template')
            </div>

            {{-- SEND BUTTON ────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendbutton">
                @include('theme::pages.api-docs.send-button')
            </div>

            {{-- SEND LIST ──────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendlist">
                @include('theme::pages.api-docs.send-list')
            </div>

            {{-- SEND LOCATION ──────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendlocation">
                @include('theme::pages.api-docs.send-location')
            </div>

            {{-- SEND VCARD ─────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendvcard">
                @include('theme::pages.api-docs.send-vcard')
            </div>

            {{-- SEND STICKER ───────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendsticker">
                @include('theme::pages.api-docs.send-sticker')
            </div>

            {{-- SEND POLL ──────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-sendpoll">
                @include('theme::pages.api-docs.send-poll')
            </div>

            {{-- ENROLL ─────────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-enroll">
                @include('theme::pages.api-docs.contact-enroll')
            </div>

            {{-- CHECK NUMBER ───────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-checknumber">
                @include('theme::pages.api-docs.check-number')
            </div>

            {{-- WEBHOOK ────────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-webhook">
                @include('theme::pages.api-docs.examplewebhook')
            </div>

            {{-- DEVICE INFO ─────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-deviceinfo">
                @include('theme::pages.api-docs.device-info')
            </div>

            {{-- DISCONNECT ──────────────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-disconnect">
                @include('theme::pages.api-docs.disconnectdevice')
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(text, onSuccess) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(function() { fallbackCopy(text, onSuccess); });
    } else { fallbackCopy(text, onSuccess); }
}
function fallbackCopy(text, onSuccess) {
    var ta = document.createElement('textarea');
    ta.value = text; ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
    document.body.appendChild(ta); ta.focus(); ta.select();
    try { document.execCommand('copy'); onSuccess(); } catch(e) {}
    document.body.removeChild(ta);
}
function copyVal(id, btn) {
    copyToClipboard(document.getElementById(id).value, function() {
        btn.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(function() { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
    });
}
function copyBlock(id, btn) {
    copyToClipboard(document.getElementById(id).textContent, function() {
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied';
        setTimeout(function() { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 2000);
    });
}
function toggleKey() {
    var inp = document.getElementById('docsApiKey'), eye = document.getElementById('keyEye');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    eye.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
@endpush

</x-layout-dashboard>
