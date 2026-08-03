<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h5 class="fw-bold mb-1">Send Text Message</h5>
        <p class="text-muted small mb-3">Send a free-form text message. Only works within Meta's 24-hour customer service window (i.e. the contact messaged you first within the last 24 hours). To initiate conversations, use <strong>Send Template</strong> instead.</p>

        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-success">POST</span>
            <span class="badge bg-primary">GET</span>
            <code id="url-sendmessage">{{ $baseUrl }}/send-message</code>
            <button class="btn btn-xs btn-outline-secondary ms-auto" onclick="copyBlock('url-sendmessage', this)"><i class="bi bi-clipboard"></i> Copy</button>
        </div>

        <table class="table table-sm table-bordered small">
            <thead class="table-light">
                <tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr>
            </thead>
            <tbody>
                <tr><td><code>api_key</code></td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>Your platform API key</td></tr>
                <tr><td><code>sender</code></td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>Your device identifier (the <code>body</code> field of the device — may be a name, not a phone number)</td></tr>
                <tr><td><code>number</code></td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>Recipient number in E.164 format without <code>+</code> (e.g. <code>918521384918</code>). Separate multiple with <code>|</code>.</td></tr>
                <tr><td><code>message</code></td><td>string</td><td><span class="badge bg-danger">Yes</span></td><td>Text body to send</td></tr>
            </tbody>
        </table>

        <div class="position-relative mt-3">
            <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2" onclick="copyBlock('ex-sendmessage', this)"><i class="bi bi-clipboard"></i> Copy</button>
            <pre class="bg-dark text-light rounded p-3 small" id="ex-sendmessage">curl -X POST {{ $baseUrl }}/send-message \
  -d "api_key={{ $apiKey }}" \
  -d "sender={{ $sender }}" \
  -d "number=918521384918" \
  -d "message=Hello from the API"</pre>
        </div>

        <p class="small text-muted mt-2 mb-1">Response</p>
        <pre class="bg-dark text-light rounded p-3 small">{ "status": true, "msg": "Message sent successfully!" }</pre>
    </div>
</div>
