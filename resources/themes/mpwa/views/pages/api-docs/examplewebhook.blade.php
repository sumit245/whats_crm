<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h5 class="fw-bold mb-1">Inbound Webhook</h5>
        <p class="text-muted small mb-3">
            When a user sends a message to your WhatsApp number, the platform immediately forwards it as an HTTP <code>POST</code> to the webhook URL you configure on your device.
            Set the URL from <strong>Dashboard → your device → Webhook URL</strong> or via the
            <a href="{{ route('integrations.custom-app') }}">Custom App integration page</a>.
        </p>

        <h6 class="fw-semibold">Payload shape</h6>
        <div class="position-relative">
            <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2" onclick="copyBlock('ex-webhook-payload', this)"><i class="bi bi-clipboard"></i> Copy</button>
            <pre class="bg-dark text-light rounded p-3 small" id="ex-webhook-payload">{
  "event": "message",
  "message": {
    "id":        "wamid.xxxxxxxxxxxx",
    "from":      "918521384918",
    "timestamp": "1717776000",
    "type":      "text",
    "text": {
      "body": "Hello, I need help with my booking"
    }
  },
  "contacts": [
    {
      "profile": { "name": "Rahul Sharma" },
      "wa_id": "918521384918"
    }
  ],
  "metadata": {
    "phone_number_id":      "951974074661935",
    "display_phone_number": "+91 84488 17906"
  }
}</pre>
        </div>

        <h6 class="fw-semibold mt-4">Message types</h6>
        <p class="small text-muted">The <code>type</code> field and corresponding payload key vary by message type:</p>
        <table class="table table-sm table-bordered small">
            <thead class="table-light">
                <tr><th>type</th><th>Payload key</th><th>Content</th></tr>
            </thead>
            <tbody>
                <tr><td><code>text</code></td><td><code>message.text.body</code></td><td>Plain text string</td></tr>
                <tr><td><code>image</code></td><td><code>message.image</code></td><td><code>{ id, mime_type, sha256, caption }</code></td></tr>
                <tr><td><code>audio</code></td><td><code>message.audio</code></td><td><code>{ id, mime_type }</code></td></tr>
                <tr><td><code>video</code></td><td><code>message.video</code></td><td><code>{ id, mime_type, caption }</code></td></tr>
                <tr><td><code>document</code></td><td><code>message.document</code></td><td><code>{ id, filename, mime_type }</code></td></tr>
                <tr><td><code>interactive</code></td><td><code>message.interactive.button_reply</code> or <code>list_reply</code></td><td>User's button/list selection</td></tr>
                <tr><td><code>location</code></td><td><code>message.location</code></td><td><code>{ latitude, longitude, name, address }</code></td></tr>
            </tbody>
        </table>

        <h6 class="fw-semibold mt-3">PHP handler example</h6>
        <div class="position-relative">
            <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2" onclick="copyBlock('ex-webhook-php', this)"><i class="bi bi-clipboard"></i> Copy</button>
            <pre class="bg-dark text-light rounded p-3 small" id="ex-webhook-php">&lt;?php
$payload = json_decode(file_get_contents('php://input'), true);

if (($payload['event'] ?? '') === 'message') {
    $from = $payload['message']['from'];        // "918521384918"
    $name = $payload['contacts'][0]['profile']['name'] ?? 'Unknown';
    $type = $payload['message']['type'];        // "text", "image", etc.
    $body = $payload['message']['text']['body'] ?? '[media]';

    // route to your app logic
    error_log("Message from {$name} ({$from}): {$body}");
}

http_response_code(200);
echo 'OK';</pre>
        </div>

        <h6 class="fw-semibold mt-3">Node.js / Express example</h6>
        <div class="position-relative">
            <button class="btn btn-xs btn-outline-secondary position-absolute top-0 end-0 m-2" onclick="copyBlock('ex-webhook-node', this)"><i class="bi bi-clipboard"></i> Copy</button>
            <pre class="bg-dark text-light rounded p-3 small" id="ex-webhook-node">app.post('/whatsapp/webhook', express.json(), (req, res) => {
  const { event, message, contacts } = req.body;
  if (event === 'message') {
    const from = message.from;
    const text = message.text?.body ?? '[media]';
    const name = contacts?.[0]?.profile?.name ?? from;
    console.log(`[WA] ${name}: ${text}`);
    // trigger your booking logic, save to DB, etc.
  }
  res.sendStatus(200);
});</pre>
        </div>

        <div class="alert alert-warning small py-2 mt-3 mb-0">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Always return HTTP 200 quickly.</strong> If your webhook takes too long or returns an error, the platform will log the failure but still process the message internally.
        </div>
    </div>
</div>
