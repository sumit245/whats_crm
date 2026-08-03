<?php

namespace App\Services\Impl;

use App\Models\Blast;
use App\Models\Device;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCloudApiService implements WhatsappService
{
    protected string $graphBase = 'https://graph.facebook.com/v20.0';
    protected ?Device $device;

    public function __construct(mixed $device = null)
    {
        $this->device = $device instanceof Device ? $device : null;
    }

    // ─── Core HTTP helper ────────────────────────────────────────────────────

    private function post(string $path, array $payload): object
    {
        if (!$this->device?->phone_number_id || !$this->device?->access_token) {
            return (object) ['status' => false, 'error' => 'Device is not configured for Meta Cloud API'];
        }

        try {
            $response = Http::withToken($this->device->access_token)
                ->post("{$this->graphBase}{$path}", $payload);

            if ($response->failed()) {
                $errorBody = $response->json();
                $errorCode = $errorBody['error']['code'] ?? 'unknown';
                $errorMsg  = $errorBody['error']['message'] ?? $response->body();
                Log::error('Meta API error', ['path' => $path, 'code' => $errorCode, 'message' => $errorMsg]);
                return (object) ['status' => false, 'error' => "Meta error {$errorCode}: {$errorMsg}", 'error_code' => $errorCode];
            }

            $data = $response->json();
            Log::info('Meta API success', ['path' => $path, 'wamid' => $data['messages'][0]['id'] ?? null]);
            return (object) [
                'status'     => true,
                'message_id' => $data['messages'][0]['id'] ?? null,
                'data'       => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('Meta API exception', ['message' => $e->getMessage()]);
            return (object) ['status' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── Verify / connect device ─────────────────────────────────────────────

    public function connectDevice(Device $device): object
    {
        try {
            $response = Http::withToken($device->access_token)
                ->get("{$this->graphBase}/{$device->phone_number_id}", [
                    'fields' => 'display_phone_number,verified_name,quality_rating,platform_type,throughput,messaging_limit_tier',
                ]);

            if ($response->failed()) {
                return (object) ['status' => false, 'error' => $response->json('error.message', 'Invalid credentials')];
            }

            return (object) ['status' => true, 'data' => $response->json()];
        } catch (\Throwable $e) {
            return (object) ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Subscribe our Meta app to this WhatsApp Business Account so that inbound
     * messages and delivery/read statuses are delivered to our webhook.
     *
     * Without this call Meta never sends webhooks for the WABA — sending still
     * works (that's a direct Graph API call) but nothing is ever received.
     * Requires an access token with the `whatsapp_business_management` permission.
     */
    public function subscribeToWaba(?Device $device = null): object
    {
        $device = $device ?? $this->device;

        if (!$device?->waba_id || !$device?->access_token) {
            return (object) ['status' => false, 'error' => 'Device is missing a WABA ID or access token'];
        }

        try {
            $response = Http::withToken($device->access_token)
                ->post("{$this->graphBase}/{$device->waba_id}/subscribed_apps");

            if ($response->failed()) {
                $error = $response->json('error.message', 'Subscription failed');
                Log::error('WABA subscribe_apps failed', ['waba_id' => $device->waba_id, 'error' => $error]);
                return (object) ['status' => false, 'error' => $error];
            }

            Log::info('WABA subscribed to app', ['waba_id' => $device->waba_id]);
            return (object) ['status' => true, 'data' => $response->json()];
        } catch (\Throwable $e) {
            return (object) ['status' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── WhatsappService interface ───────────────────────────────────────────

    public function sendText($request, $receiver): object|bool
    {
        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'text',
            'text'              => ['body' => $request->message ?? $request->text ?? ''],
        ]);
    }

    public function sendMedia($request, $receiver): object|bool
    {
        $typeMap = ['image' => 'image', 'video' => 'video', 'document' => 'document', 'audio' => 'audio'];
        $mediaType = $typeMap[$request->media_type] ?? 'document';

        $mediaPayload = ['link' => $request->url];
        if ($request->caption) {
            $mediaPayload['caption'] = $request->caption;
        }

        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => $mediaType,
            $mediaType          => $mediaPayload,
        ]);
    }

    public function sendTemplate($request, $receiver): object|bool
    {
        // $request can be an object with template_name, language, components
        // or a Blast-style object with message (JSON components) and template_variables
        if (is_object($request) && isset($request->template_name)) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $receiver,
                'type'              => 'template',
                'template'          => [
                    'name'       => $request->template_name,
                    'language'   => ['code' => $request->language ?? 'en'],
                    'components' => $request->components ?? [],
                ],
            ];
        } else {
            // Legacy / direct call with components array
            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $receiver,
                'type'              => 'template',
                'template'          => $request->template ?? [],
            ];
        }

        return $this->post("/{$this->device->phone_number_id}/messages", $payload);
    }

    /**
     * Send a template message for a blast record with resolved variables.
     *
     * Supported keys in template_variables JSON:
     *   1, 2, 3 … (or body_1, body_2 …) → body text parameters
     *   header_url                        → image/document/video link for HEADER component
     *   header_type                       → "image" | "document" | "video" (default: image)
     *   button_0_url_suffix               → URL suffix for first URL button (index 0)
     *   button_1_url_suffix               → URL suffix for second URL button (index 1), etc.
     */
    public function sendBlastTemplate(Blast $blast, array $templateMeta): object
    {
        $variables  = $blast->template_variables ?? [];
        $components = [];

        // ── Body parameters (numeric keys 1,2,3… OR body_1, body_2…) ────────
        $bodyParams = [];
        foreach ($variables as $key => $val) {
            if (is_numeric($key)) {
                $bodyParams[(int) $key] = $val;
            } elseif (preg_match('/^body_(\d+)$/', (string) $key, $m)) {
                $bodyParams[(int) $m[1]] = $val;
            }
        }
        if (!empty($bodyParams)) {
            ksort($bodyParams);
            $components[] = [
                'type'       => 'body',
                'parameters' => array_values(array_map(
                    fn ($v) => ['type' => 'text', 'text' => (string) $v],
                    $bodyParams
                )),
            ];
        }

        // ── Header component (image / document / video) ──────────────────────
        if (!empty($variables['header_url'])) {
            $headerType = $variables['header_type'] ?? 'image';
            $components[] = [
                'type'       => 'header',
                'parameters' => [
                    [
                        'type'      => $headerType,
                        $headerType => ['link' => (string) $variables['header_url']],
                    ],
                ],
            ];
        }

        // ── Button URL suffix parameters ──────────────────────────────────────
        foreach ($variables as $key => $val) {
            if (preg_match('/^button_(\d+)_url_suffix$/', (string) $key, $m) && $val !== '') {
                $components[] = [
                    'type'       => 'button',
                    'sub_type'   => 'url',
                    'index'      => $m[1],
                    'parameters' => [['type' => 'text', 'text' => (string) $val]],
                ];
            }
        }

        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $blast->receiver,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateMeta['name'],
                'language'   => ['code' => $templateMeta['language'] ?? 'en'],
                'components' => $components,
            ],
        ]);
    }

    public function startBlast($data): object
    {
        // This is called from StartBlast cron for non-device-specific blasts.
        // The real per-device blast is done in StartBlast::processMetaBlast().
        return (object) ['status' => true];
    }

    public function logoutDevice($device): object|bool
    {
        // Meta Cloud API numbers don't "disconnect" — just mark DB status
        if ($device instanceof Device) {
            $device->update(['status' => 'Disconnect']);
        }
        return (object) ['status' => true];
    }

    public function fetchGroups($device): object
    {
        // Not supported by Meta Cloud API
        return (object) ['status' => false, 'groups' => []];
    }

    public function checkNumber($device, $number): object|bool
    {
        // Meta Cloud API has no bulk number validation endpoint; return stub true
        return (object) ['status' => true, 'active' => true];
    }

    // ─── Stubs for Baileys-only message types ────────────────────────────────
    // These types don't map to Meta Cloud API in the same way

    public function sendButton($request, $receiver): object|bool
    {
        // Map to interactive reply buttons in Cloud API
        $buttons = [];
        foreach (array_slice((array) $request->button, 0, 3) as $i => $label) {
            $buttons[] = ['type' => 'reply', 'reply' => ['id' => "btn_{$i}", 'title' => substr($label, 0, 20)]];
        }

        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'button',
                'body'   => ['text' => $request->message ?? ''],
                'action' => ['buttons' => $buttons],
            ],
        ]);
    }

    public function sendList($request, $receiver): object|bool
    {
        $rows = [];
        foreach ((array) $request->list as $i => $item) {
            $rows[] = ['id' => "row_{$i}", 'title' => substr((string) $item, 0, 24)];
        }

        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => $request->message ?? ''],
                'action' => [
                    'button'   => $request->buttontext ?? 'Options',
                    'sections' => [['title' => $request->title ?? 'Options', 'rows' => $rows]],
                ],
            ],
        ]);
    }

    public function sendSticker($request, $receiver): object|bool
    {
        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'sticker',
            'sticker'           => ['link' => $request->url],
        ]);
    }

    public function sendLocation($request, $receiver): object|bool
    {
        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'location',
            'location'          => [
                'latitude'  => $request->latitude,
                'longitude' => $request->longitude,
            ],
        ]);
    }

    public function sendVcard($request, $receiver): object|bool
    {
        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'contacts',
            'contacts'          => [[
                'name'  => ['formatted_name' => $request->name ?? ''],
                'phones' => [['phone' => $request->phone ?? '', 'type' => 'CELL']],
            ]],
        ]);
    }

    public function sendCatalog(string $receiver, string $catalogId, string $body = '', string $footer = ''): object
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'catalog_message',
                'body'   => ['text' => $body ?: 'Browse our catalog'],
                'action' => [
                    'name'       => 'catalog_message',
                    'parameters' => ['thumbnail_product_retailer_id' => $catalogId],
                ],
            ],
        ];

        if ($footer) {
            $payload['interactive']['footer'] = ['text' => $footer];
        }

        return $this->post("/{$this->device->phone_number_id}/messages", $payload);
    }

    public function sendPoll($request, $receiver): object|bool
    {
        // Meta Cloud API has no native poll type; we simulate with an interactive list.
        // List rows: max 10 total, title max 24 chars.
        $options = array_slice((array) $request->option, 0, 10);
        $rows    = array_values(array_map(fn ($opt, $i) => [
            'id'    => 'vote_' . $i,
            'title' => mb_substr((string) $opt, 0, 24),
        ], $options, array_keys($options)));

        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => $request->name ?? 'Poll'],
                'action' => [
                    'button'   => 'Vote',
                    'sections' => [[
                        'title' => mb_substr((string) ($request->name ?? 'Options'), 0, 24),
                        'rows'  => $rows,
                    ]],
                ],
            ],
        ]);
    }

    public function sendCtaUrl(string $receiver, string $url, string $displayText = 'Open Link', string $body = ''): object
    {
        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'cta_url',
                'body'   => ['text' => $body ?: $displayText],
                'action' => [
                    'name'       => 'cta_url',
                    'parameters' => [
                        'display_text' => $displayText,
                        'url'          => $url,
                    ],
                ],
            ],
        ]);
    }
}
