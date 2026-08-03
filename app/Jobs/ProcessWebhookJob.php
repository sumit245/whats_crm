<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\WebhookLog;
use App\Models\WebhookTrigger;
use App\Services\Impl\MetaCloudApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int   $logId,
        public readonly int   $triggerId,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $log     = WebhookLog::find($this->logId);
        $trigger = WebhookTrigger::with(['source.user', 'device'])->find($this->triggerId);

        if (!$log || !$trigger) return;

        try {
            $source = $trigger->source;
            $phone  = $this->extractPhone($this->payload, $source->phone_field);

            if (!$phone) {
                $this->fail($log, 'Could not resolve phone number from payload field "' . $source->phone_field . '"');
                return;
            }

            $contactName = $source->name_field
                ? (string) (data_get($this->payload, $source->name_field) ?? $phone)
                : $phone;

            match($trigger->action_type) {
                'send_template' => $this->sendTemplate($trigger, $phone, $contactName),
                'start_flow'    => $this->startFlow($trigger, $phone, $contactName),
                default         => throw new \RuntimeException("Unknown action_type: {$trigger->action_type}"),
            };

            $log->update([
                'status'         => 'processed',
                'resolved_phone' => $phone,
                'processed_at'   => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('ProcessWebhookJob failed', ['log_id' => $this->logId, 'error' => $e->getMessage()]);
            $this->fail($log, $e->getMessage());
        }
    }

    private function sendTemplate(WebhookTrigger $trigger, string $phone, string $contactName): void
    {
        $device = $trigger->device;
        if (!$device) throw new \RuntimeException('Trigger has no device assigned.');

        $components = [];
        $varMap = $trigger->variable_map ?? [];

        if (!empty($varMap)) {
            $params = [];
            ksort($varMap);
            foreach ($varMap as $varIndex => $payloadPath) {
                $value    = (string) (data_get($this->payload, $payloadPath) ?? '');
                $params[] = ['type' => 'text', 'text' => $value ?: '-'];
            }
            $components[] = ['type' => 'body', 'parameters' => $params];
        }

        $api = new MetaCloudApiService($device);

        $req = (object) [
            'template_name' => $trigger->template_name,
            'language'      => $trigger->template_language ?? 'en',
            'components'    => $components,
        ];

        $result = $api->sendTemplate($req, $phone);

        if (!$result) {
            throw new \RuntimeException('Meta API returned false for sendTemplate.');
        }

        // Upsert conversation record so the message appears in chat
        $user = $trigger->source->user;
        $conv = Conversation::firstOrCreate(
            ['user_id' => $user->id, 'device_id' => $device->id, 'contact_number' => $phone],
            ['contact_name' => $contactName, 'last_message' => '[webhook template]', 'last_message_at' => now()]
        );
        $conv->update(['last_message' => $trigger->template_name, 'last_message_at' => now()]);
    }

    private function startFlow(WebhookTrigger $trigger, string $phone, string $contactName): void
    {
        if (!$trigger->flow_id) throw new \RuntimeException('Trigger has no flow assigned.');

        $device = $trigger->device;
        if (!$device) throw new \RuntimeException('Trigger has no device assigned.');

        $user = $trigger->source->user;

        $conv = Conversation::firstOrCreate(
            ['user_id' => $user->id, 'device_id' => $device->id, 'contact_number' => $phone],
            ['contact_name' => $contactName, 'last_message' => '[flow started]', 'last_message_at' => now()]
        );

        // Hand off to FlowEngine as if the contact sent a trigger message
        $engine = new \App\Services\FlowEngine($device);
        $engine->handleInbound($conv, '__webhook_trigger__', ['_webhook_payload' => $this->payload]);
    }

    private function extractPhone(array $payload, string $field): ?string
    {
        $raw = data_get($payload, $field);
        if (!$raw) return null;

        // Normalize: strip spaces, dashes, parentheses; ensure leading +
        $phone = preg_replace('/[\s\-\(\)]+/', '', (string) $raw);
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . ltrim($phone, '0');
        }
        // Must be at least 7 digits after +
        if (!preg_match('/^\+\d{7,15}$/', $phone)) return null;

        return $phone;
    }

    private function fail(WebhookLog $log, string $error): void
    {
        $log->update(['status' => 'failed', 'error' => $error, 'processed_at' => now()]);
    }
}
