<x-layout-dashboard title="{{ __('Custom App Integration') }}">

    <x-page-header title="{{ __('Custom App / API Integration') }}"
        subtitle="{{ __('Connect your React Native, mobile, or server-side application to WhatsApp.') }}"
        :breadcrumb="[__('Integrations'), __('Custom App')]" />

    <div class="row g-4">

        {{-- API Key --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-key-fill text-warning"></i> {{ __('Your API Key') }}
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        {{ __('Include this key in every API request as the') }} <code>api_key</code> {{ __('parameter. Keep it secret — it authorises all message sends on your account.') }}
                    </p>
                    <div class="input-group" style="max-width:600px">
                        <input type="password" id="apiKeyInput" class="form-control font-monospace"
                               value="{{ $apiKey }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKey()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                        <button class="btn btn-outline-primary" type="button" onclick="copyFromInput('apiKeyInput', this)">
                            <i class="bi bi-clipboard"></i> {{ __('Copy') }}
                        </button>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ __('This is the same key shown in') }}
                            <a href="{{ route('user.settings') }}" target="_blank">{{ __('Settings → API Key') }}</a>.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Webhook (Receive Inbound Messages) --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-down-circle-fill text-success"></i> {{ __('Receive Inbound Messages (Webhook)') }}
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        {{ __('When a user sends a message to your WhatsApp number, we will POST the payload to this URL. Set one URL per connected device.') }}
                    </p>
                    @forelse($devices as $device)
                    <div class="mb-3 p-3 border rounded-2">
                        <div class="fw-semibold small mb-2">
                            <i class="bi bi-phone"></i> {{ $device->verified_name }} <span class="text-muted">({{ $device->body }})</span>
                        </div>
                        <form action="{{ route('setHook') }}" method="POST" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="number" value="{{ $device->body }}">
                            <input type="url" name="webhook" class="form-control form-control-sm font-monospace"
                                   placeholder="https://your-app.com/whatsapp/webhook"
                                   value="{{ $device->webhook ?? '' }}">
                            <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                                <i class="bi bi-save"></i> {{ __('Save') }}
                            </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-muted small">{{ __('No devices connected. Add a device from the Dashboard first.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Base URL & Sender --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill text-primary"></i> {{ __('Connection Details') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless small">
                        <tr>
                            <td class="text-muted">{{ __('Base URL') }}</td>
                            <td>
                                <code class="user-select-all">{{ $baseUrl }}</code>
                                <button class="btn btn-xs btn-link p-0 ms-1" onclick="copyText('{{ $baseUrl }}', this)">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </td>
                        </tr>
                        @foreach($devices as $device)
                        <tr>
                            <td class="text-muted">{{ __('Sender (device) #') }}{{ $loop->iteration }}</td>
                            <td><code class="user-select-all">{{ $device->body }}</code></td>
                        </tr>
                        @endforeach
                    </table>
                    <div class="alert alert-info small mb-0 mt-2 py-2">
                        <i class="bi bi-shield-lock-fill me-1"></i>
                        {{ __('The') }} <code>sender</code> {{ __('parameter identifies which device sends the message. It must belong to your account.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Code Examples --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-code-slash text-secondary"></i> {{ __('Code Examples') }}
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="codeTabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-otp">{{ __('Send OTP') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-text">{{ __('Send Text') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-enroll">{{ __('Enroll Contact') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-webhook">{{ __('Inbound Webhook') }}</a></li>
                    </ul>
                    <div class="tab-content pt-3">

                        {{-- OTP --}}
                        <div class="tab-pane active" id="tab-otp">
                            <p class="text-muted small">{{ __('Send a WhatsApp-approved OTP template to your user. The template must be pre-approved by Meta.') }}</p>
                            <div class="position-relative">
                                <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2"
                                        onclick="copyCode('code-otp', this)"><i class="bi bi-clipboard"></i></button>
                                <pre class="bg-dark text-light rounded p-3 small overflow-auto" id="code-otp">// React Native (fetch) — Send OTP Template
const response = await fetch('{{ $baseUrl }}/send-template', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    api_key:  '{{ $apiKey }}',
    sender:   '{{ $devices->first()?->body ?? 'YOUR_DEVICE_NUMBER' }}',
    number:   userPhoneNumber,        // e.g. "919876543210"
    type:     'template',
    message:  'your_otp_template_name',
    template: [
      { type: 'body', index: '1', value: otpCode },  // {{1}} in template body
    ],
  }),
});
const data = await response.json();
console.log(data.status, data.msg);</pre>
                            </div>
                        </div>

                        {{-- Text --}}
                        <div class="tab-pane" id="tab-text">
                            <p class="text-muted small">{{ __('Send a free-form text message. Only works within the 24-hour customer service window (after the user last messaged you).') }}</p>
                            <div class="position-relative">
                                <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2"
                                        onclick="copyCode('code-text', this)"><i class="bi bi-clipboard"></i></button>
                                <pre class="bg-dark text-light rounded p-3 small overflow-auto" id="code-text">// PHP (cURL) — Send Text Message
$ch = curl_init('{{ $baseUrl }}/send-message');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'api_key' => '{{ $apiKey }}',
        'sender'  => '{{ $devices->first()?->body ?? 'YOUR_DEVICE_NUMBER' }}',
        'number'  => $userPhone,   // E.164, no +
        'type'    => 'text',
        'message' => 'Your booking is confirmed! Ref: BUS-' . $bookingId,
    ]),
]);
$res = json_decode(curl_exec($ch), true);
// $res['status'] => true/false, $res['msg'] => description</pre>
                            </div>
                        </div>

                        {{-- Enroll --}}
                        <div class="tab-pane" id="tab-enroll">
                            <p class="text-muted small">{{ __('Add or update a contact in your CRM from your app. Useful when a user registers or updates their profile.') }}</p>
                            <div class="position-relative">
                                <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2"
                                        onclick="copyCode('code-enroll', this)"><i class="bi bi-clipboard"></i></button>
                                <pre class="bg-dark text-light rounded p-3 small overflow-auto" id="code-enroll">// Node.js (axios) — Enroll Contact
const axios = require('axios');
await axios.post('{{ $baseUrl }}/contacts/enroll', {
  api_key:      '{{ $apiKey }}',
  number:       '919876543210',   // E.164 without +
  name:         'Rahul Sharma',
  phonebook_id: 1,                // optional tag/group
  attributes: {
    '$Name':  'Rahul Sharma',
    'City':   'Mumbai',
    'Plan':   'Gold',
  },
});</pre>
                            </div>
                        </div>

                        {{-- Webhook --}}
                        <div class="tab-pane" id="tab-webhook">
                            <p class="text-muted small">{{ __('When a user messages your WhatsApp number, we POST to your webhook URL. Here is the shape of that payload.') }}</p>
                            <div class="position-relative">
                                <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2"
                                        onclick="copyCode('code-wh', this)"><i class="bi bi-clipboard"></i></button>
                                <pre class="bg-dark text-light rounded p-3 small overflow-auto" id="code-wh">// Inbound Webhook Payload (POST to your webhook URL)
{
  "event": "message",
  "message": {
    "from":      "919876543210",
    "type":      "text",
    "text":      { "body": "I need to book a bus to Mumbai" },
    "timestamp": "1717776000",
    "id":        "wamid.xxxx"
  },
  "contacts": [{ "profile": { "name": "Rahul Sharma" }, "wa_id": "919876543210" }],
  "metadata": { "phone_number_id": "123456789", "display_phone_number": "YOUR_NUMBER" }
}

// PHP handler example
$payload = json_decode(file_get_contents('php://input'), true);
if ($payload['event'] === 'message') {
    $from = $payload['message']['from'];
    $body = $payload['message']['text']['body'] ?? '';
    // route to your app logic…
}</pre>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

@push('scripts')
<script>
function toggleApiKey() {
    var inp = document.getElementById('apiKeyInput');
    var icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye';
    }
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
function copyFromInput(inputId, btn) {
    var text = document.getElementById(inputId).value;
    copyToClipboard(text, function () {
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied';
        setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 2000);
    });
}
function copyCode(id, btn) {
    var text = document.getElementById(id).textContent;
    copyToClipboard(text, function () {
        btn.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
    });
}
</script>
@endpush

</x-layout-dashboard>
