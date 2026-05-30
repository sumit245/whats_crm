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
     */
    public function sendBlastTemplate(Blast $blast, array $templateMeta): object
    {
        $variables = $blast->template_variables ?? [];
        $components = [];

        // Build body component with parameters
        if (!empty($variables)) {
            $parameters = array_map(fn ($val) => ['type' => 'text', 'text' => (string) $val], array_values($variables));
            $components[] = ['type' => 'body', 'parameters' => $parameters];
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

    public function sendPoll($request, $receiver): object|bool
    {
        return $this->post("/{$this->device->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => $receiver,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'nfm_reply',
                'body'   => ['text' => $request->name ?? 'Poll'],
                'action' => [
                    'name'       => 'vote',
                    'parameters' => [
                        'prompt_text' => $request->name ?? 'Vote',
                        'options'     => array_map(fn ($o) => ['name' => $o], (array) $request->option),
                        'max_choices' => $request->countable ?? 1,
                    ],
                ],
            ],
        ]);
    }
}
