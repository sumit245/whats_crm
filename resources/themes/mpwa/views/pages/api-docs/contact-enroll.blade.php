
    <h3>{{ __('Contact Enrollment API') }}</h3>
    <p class="text-muted">{{ __('Add contacts to phonebooks, set custom attributes, and enroll them into drip sequences — from any external system (CRM, web form, Zapier, Make).') }}</p>

    {{-- ── Your API Key ─────────────────────────────────────────────────────── --}}
    <div class="alert alert-info py-2 d-flex align-items-center gap-2" style="font-size:0.88rem">
        <i class="bi bi-key-fill"></i>
        <span>{{ __('Your API Key:') }} <code class="user-select-all">{{ auth()->user()->api_key }}</code></span>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         1. ENROLL
    ══════════════════════════════════════════════════════════════════════════ --}}
    <hr>
    <h5>1. {{ __('Enroll Contact') }}</h5>
    <p>
        <span class="badge bg-success">POST</span>
        <code>{{ $baseUrl }}/contacts/enroll</code>
    </p>
    <p>{{ __('Adds a contact to a phonebook, saves custom attributes, removes suppression, and optionally starts a drip sequence.') }}</p>

    <table class="table table-sm table-bordered" style="font-size:0.82rem">
        <thead class="table-light">
            <tr><th>{{ __('Parameter') }}</th><th>{{ __('Type') }}</th><th>{{ __('Required') }}</th><th>{{ __('Description') }}</th></tr>
        </thead>
        <tbody>
            <tr><td>api_key</td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>{{ __('Your API key') }}</td></tr>
            <tr><td>number</td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>{{ __('Contact WhatsApp number (digits only, with country code)') }}</td></tr>
            <tr><td>name</td><td>string</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Contact display name') }}</td></tr>
            <tr><td>phonebook_id</td><td>integer</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Add to this phonebook by ID') }}</td></tr>
            <tr><td>phonebook_name</td><td>string</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Add to phonebook by name (creates it if not found)') }}</td></tr>
            <tr><td>device_id</td><td>integer</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Device ID for drip sequence sending. Defaults to your first device.') }}</td></tr>
            <tr><td>drip_sequence_id</td><td>integer</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Enroll contact in this active drip sequence') }}</td></tr>
            <tr><td>attributes</td><td>object</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Key→value pairs to save as custom attributes') }}</td></tr>
            <tr><td>remove_suppression</td><td>boolean</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Remove from suppression list (default: true)') }}</td></tr>
        </tbody>
    </table>

    <p>{{ __('Example JSON request') }}</p>
    <pre class="bg-dark text-white p-3 rounded" style="font-size:0.8rem"><code>{
  "api_key": "{{ auth()->user()->api_key }}",
  "number": "919876543210",
  "name": "Rahul Sharma",
  "phonebook_name": "Website Leads",
  "drip_sequence_id": 1,
  "device_id": {{ auth()->user()->devices()->value('id') ?? 1 }},
  "attributes": {
    "source": "landing_page",
    "plan": "pro",
    "signup_date": "{{ now()->toDateString() }}"
  }
}</code></pre>

    <p>{{ __('Example success response') }}</p>
    <pre class="bg-dark text-white p-3 rounded" style="font-size:0.8rem"><code>{
  "status": true,
  "message": "Contact enrolled successfully.",
  "data": {
    "number": "919876543210",
    "name": "Rahul Sharma",
    "phonebook": "Website Leads",
    "suppression_removed": false,
    "drip_enrolled": true,
    "attributes_saved": 3
  }
}</code></pre>

    <p>{{ __('Example cURL') }}</p>
    <pre class="bg-dark text-white p-3 rounded" style="font-size:0.8rem"><code>curl -X POST {{ $baseUrl }}/contacts/enroll \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "{{ auth()->user()->api_key }}",
    "number": "919876543210",
    "name": "Rahul Sharma",
    "phonebook_name": "Website Leads"
  }'</code></pre>

    {{-- ══════════════════════════════════════════════════════════════════════════
         2. UNENROLL
    ══════════════════════════════════════════════════════════════════════════ --}}
    <hr>
    <h5>2. {{ __('Unenroll Contact') }}</h5>
    <p>
        <span class="badge bg-success">POST</span>
        <code>{{ $baseUrl }}/contacts/unenroll</code>
    </p>
    <p>{{ __('Remove a contact from a phonebook, cancel drip sequences, and optionally suppress them.') }}</p>

    <table class="table table-sm table-bordered" style="font-size:0.82rem">
        <thead class="table-light">
            <tr><th>{{ __('Parameter') }}</th><th>{{ __('Type') }}</th><th>{{ __('Required') }}</th><th>{{ __('Description') }}</th></tr>
        </thead>
        <tbody>
            <tr><td>api_key</td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>{{ __('Your API key') }}</td></tr>
            <tr><td>number</td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>{{ __('Contact WhatsApp number') }}</td></tr>
            <tr><td>phonebook_id</td><td>integer</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Remove from this specific phonebook only') }}</td></tr>
            <tr><td>remove_all_phonebooks</td><td>boolean</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Remove from all phonebooks') }}</td></tr>
            <tr><td>cancel_drip</td><td>boolean</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Cancel active drip enrollments (default: true)') }}</td></tr>
            <tr><td>suppress</td><td>boolean</td><td><span class="badge bg-secondary">No</span></td><td>{{ __('Add to suppression list — contact will not receive future campaigns') }}</td></tr>
        </tbody>
    </table>

    <pre class="bg-dark text-white p-3 rounded" style="font-size:0.8rem"><code>{
  "api_key": "{{ auth()->user()->api_key }}",
  "number": "919876543210",
  "cancel_drip": true,
  "suppress": false
}</code></pre>

    {{-- ══════════════════════════════════════════════════════════════════════════
         3. SET ATTRIBUTES
    ══════════════════════════════════════════════════════════════════════════ --}}
    <hr>
    <h5>3. {{ __('Set Contact Attributes') }}</h5>
    <p>
        <span class="badge bg-success">POST</span>
        <code>{{ $baseUrl }}/contacts/attributes</code>
    </p>
    <p>{{ __('Save or update custom attributes for a contact (visible in the 360° profile).') }}</p>

    <table class="table table-sm table-bordered" style="font-size:0.82rem">
        <thead class="table-light">
            <tr><th>{{ __('Parameter') }}</th><th>{{ __('Type') }}</th><th>{{ __('Required') }}</th><th>{{ __('Description') }}</th></tr>
        </thead>
        <tbody>
            <tr><td>api_key</td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>{{ __('Your API key') }}</td></tr>
            <tr><td>number</td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>{{ __('Contact WhatsApp number') }}</td></tr>
            <tr><td>attributes</td><td>object</td><td><span class="badge bg-danger">Yes</span></td><td>{{ __('Key→value pairs to save') }}</td></tr>
        </tbody>
    </table>

    <pre class="bg-dark text-white p-3 rounded" style="font-size:0.8rem"><code>{
  "api_key": "{{ auth()->user()->api_key }}",
  "number": "919876543210",
  "attributes": {
    "order_id": "ORD-8821",
    "product": "Pro Plan",
    "amount": "2999"
  }
}</code></pre>

    {{-- ══════════════════════════════════════════════════════════════════════════
         Zapier / Make note
    ══════════════════════════════════════════════════════════════════════════ --}}
    <hr>
    <h5><i class="bi bi-lightning-charge-fill text-warning me-1"></i>{{ __('Zapier / Make Integration') }}</h5>
    <p class="text-muted" style="font-size:0.88rem">
        {{ __('Use the "Webhooks" action in Zapier or Make and point it to the enroll endpoint above.') }}
        {{ __('Set method to POST, content type to JSON, and include api_key + number in the body.') }}
    </p>
    <ul style="font-size:0.85rem">
        <li>{{ __('Zapier: Use "Webhooks by Zapier" → Custom Request → POST') }}</li>
        <li>{{ __('Make: Use "HTTP" → Make a request module') }}</li>
        <li>{{ __('Postman: Import the curl commands above') }}</li>
    </ul>

