<?php

namespace App\Http\Controllers;

use App\Models\Blast;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Device;
use App\Models\MessageDeliveryEvent;
use App\Models\SuppressionEntry;
use App\Models\TemplateStatusNotification;
use App\Models\WabaTemplate;
use App\Services\ChatRouter;
use App\Services\FlowEngine;
use App\Services\SocketPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    private const STATUS_RANK = ['sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 0];

    // Meta error codes that indicate a permanent opt-out / unreachable number
    private const SUPPRESSION_CODES = [131026, 131047, 131048, 131049, 131051];

    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.meta.webhook_verify_token');

        if ($mode === 'subscribe' && $verifyToken !== '' && hash_equals($verifyToken, (string) $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        $signature = $request->header('X-Hub-Signature-256');
        if ($signature) {
            $appSecret = config('services.meta.app_secret', '');
            $rawBody   = $request->getContent();
            $expected  = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
            if (!hash_equals($expected, $signature)) {
                Log::warning('Meta webhook signature mismatch');
                return response('Forbidden', 403);
            }
        }

        $payload = $request->json()->all();

        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return response('ok', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $wabaId = $entry['id'] ?? null;

            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                // ── Template status updates (Phase A) ────────────────────────
                if ($field === 'message_template_status_update') {
                    $this->handleTemplateStatusUpdate($value, $wabaId);
                    continue;
                }

                // ── Message delivery status updates ───────────────────────────
                foreach ($value['statuses'] ?? [] as $status) {
                    $this->handleStatus($status, $value['metadata']['phone_number_id'] ?? null);
                }

                // ── Inbound messages ──────────────────────────────────────────
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                foreach ($value['messages'] ?? [] as $message) {
                    $device = $this->findDevice($phoneNumberId);
                    if (!$device) {
                        Log::warning('Meta webhook: no device for inbound message', [
                            'phone_number_id' => $phoneNumberId,
                            'from'            => $message['from'] ?? null,
                        ]);
                        continue;
                    }
                    $conversation = $this->storeInboundMessage($message, $value['contacts'] ?? [], $device);
                    if ($conversation) {
                        // Push inbound message to chat UI via Socket.io
                        SocketPushService::pushToConversation($conversation->id, 'new_message', [
                            'conversation_id' => $conversation->id,
                            'direction'       => 'inbound',
                            'type'            => $message['type'] ?? 'text',
                            'body'            => $this->extractText($message),
                            'created_at'      => now()->toISOString(),
                        ]);

                        // Push inbox sidebar update so agents see new/updated conversations without refresh
                        SocketPushService::pushToInbox($conversation->user_id, 'inbox_update', [
                            'conversation_id'  => $conversation->id,
                            'contact_name'     => $conversation->contact_name ?? $conversation->contact_number,
                            'contact_number'   => $conversation->contact_number,
                            'last_message'     => $this->extractText($message),
                            'last_message_at'  => now()->toISOString(),
                            'unread_count'     => $conversation->unread_count,
                            'assigned_agent_id'=> $conversation->assigned_agent_id,
                        ]);

                        // Feature 3: Auto-route new conversations to an available agent
                        if (!$conversation->assigned_agent_id) {
                            try {
                                (new ChatRouter())->assignConversation($conversation);
                            } catch (\Throwable $e) {
                                Log::warning('ChatRouter failed: ' . $e->getMessage());
                            }
                        }

                        // Feature 2: Run flow engine on inbound messages
                        try {
                            $engine = new FlowEngine();
                            $engine->handleInbound($conversation, $this->extractText($message), $message);
                        } catch (\Throwable $e) {
                            Log::error('FlowEngine error: ' . $e->getMessage());
                        }
                    }
                    $this->forwardToWebhook($message, $value['metadata'] ?? [], $value['contacts'] ?? [], $device);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    // ── Template status update handler (Phase A) ────────────────────────────

    private function handleTemplateStatusUpdate(array $value, ?string $wabaId): void
    {
        try {
            $metaTemplateId = $value['message_template_id'] ?? null;
            $newStatus      = strtoupper($value['event'] ?? '');
            $reason         = $value['reason'] ?? null;

            if (!$metaTemplateId || !$newStatus) return;

            // Find the template — try by waba_id first, then by meta_template_id alone
            $template = WabaTemplate::when($wabaId,
                fn ($q) => $q->whereHas('device', fn ($d) => $d->where('waba_id', $wabaId))
            )
            ->where('meta_template_id', $metaTemplateId)
            ->first();

            if (!$template) {
                // May not be synced yet — log and skip
                Log::info('Template status update for unknown template', [
                    'meta_template_id' => $metaTemplateId,
                    'new_status'       => $newStatus,
                ]);
                return;
            }

            $oldStatus = $template->status;

            $template->update([
                'status'           => $newStatus,
                'rejection_reason' => $newStatus === 'REJECTED' ? ($reason ?? $template->rejection_reason) : null,
                'meta_synced_at'   => now(),
            ]);

            // Create notification for the template owner
            TemplateStatusNotification::create([
                'user_id'          => $template->user_id,
                'template_id'      => $template->id,
                'template_name'    => $template->name,
                'old_status'       => $oldStatus,
                'new_status'       => $newStatus,
                'rejection_reason' => $reason,
            ]);

            Log::info("Template {$template->name} status updated: {$oldStatus} → {$newStatus}");

        } catch (\Throwable $e) {
            Log::error('handleTemplateStatusUpdate failed', ['error' => $e->getMessage()]);
        }
    }

    // ── Delivery status handler ─────────────────────────────────────────────

    private function findDevice(?string $phoneNumberId): ?Device
    {
        if (!$phoneNumberId) return null;
        return Device::where('phone_number_id', $phoneNumberId)->first();
    }

    private function handleStatus(array $status, ?string $phoneNumberId): void
    {
        $metaMessageId = $status['id'] ?? null;
        $statusValue   = $status['status'] ?? null;
        $timestamp     = $status['timestamp'] ?? now()->timestamp;
        $errorCode     = (int) ($status['errors'][0]['code'] ?? 0);

        if (!$metaMessageId || !$statusValue) return;

        $device = $this->findDevice($phoneNumberId);

        // Upsert delivery event
        try {
            MessageDeliveryEvent::updateOrCreate(
                ['meta_message_id' => $metaMessageId, 'status' => $statusValue],
                [
                    'blast_id'        => Blast::where('meta_message_id', $metaMessageId)->value('id'),
                    'device_id'       => $device?->id ?? 0,
                    'error_code'      => $errorCode ?: null,
                    'error_title'     => $status['errors'][0]['title'] ?? null,
                    'event_timestamp' => date('Y-m-d H:i:s', (int) $timestamp),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('MessageDeliveryEvent upsert failed', ['error' => $e->getMessage()]);
        }

        // Auto-suppress on permanent delivery failures (Phase C)
        if ($statusValue === 'failed' && in_array($errorCode, self::SUPPRESSION_CODES)) {
            $blast = Blast::where('meta_message_id', $metaMessageId)->first();
            if ($blast) {
                SuppressionEntry::suppress(
                    $blast->user_id,
                    $blast->receiver,
                    'meta_block',
                    "Auto-suppressed via webhook: error {$errorCode}"
                );
                Log::info("Auto-suppressed {$blast->receiver} due to Meta error {$errorCode}.");
            }
        }

        // Update blast status (upgrade-only, except failed)
        $blast = Blast::where('meta_message_id', $metaMessageId)->first();
        if ($blast) {
            $currentRank = self::STATUS_RANK[$blast->status] ?? -1;
            $newRank     = self::STATUS_RANK[$statusValue] ?? -1;
            if ($statusValue === 'failed') {
                $blast->update(['status' => 'failed']);
            } elseif ($newRank > $currentRank) {
                $blast->update(['status' => 'success']);
            }
        }

        // Update chat message status (upgrade-only)
        $chatMsg = ChatMessage::where('meta_message_id', $metaMessageId)->first();
        if ($chatMsg) {
            $currentRank = self::STATUS_RANK[$chatMsg->status] ?? -1;
            $newRank     = self::STATUS_RANK[$statusValue] ?? -1;
            if ($statusValue === 'failed' || $newRank > $currentRank) {
                $chatMsg->update(['status' => $statusValue]);
                if ($statusValue === 'failed') {
                    Log::warning('Chat message delivery failed', [
                        'chat_message_id' => $chatMsg->id,
                        'conversation_id' => $chatMsg->conversation_id,
                        'meta_message_id' => $metaMessageId,
                        'error_code'      => $errorCode ?: null,
                        'error_title'     => $status['errors'][0]['title'] ?? null,
                    ]);
                }
            }
        }
    }

    // ── Inbound message handler ─────────────────────────────────────────────

    private function extractText(array $message): string
    {
        $type = $message['type'] ?? 'text';
        return match ($type) {
            'text'        => $message['text']['body'] ?? '',
            'interactive' => $message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title']
                ?? '',
            'button'      => $message['button']['text'] ?? '',
            default       => '',
        };
    }

    private function storeInboundMessage(array $message, array $contacts, Device $device): ?Conversation
    {
        try {
            $senderNumber = $message['from'] ?? null;
            if (!$senderNumber || !$device->user_id) return null;

            $contactName = $contacts[0]['profile']['name'] ?? null;
            $type        = $message['type'] ?? 'text';

            $body = match ($type) {
                'text'        => $message['text']['body'] ?? null,
                'image'       => $message['image']['caption'] ?? '[Image]',
                'video'       => '[Video]',
                'audio'       => '[Audio]',
                'document'    => $message['document']['filename'] ?? '[Document]',
                'sticker'     => '[Sticker]',
                'location'    => '[Location]',
                'contacts'    => '[Contact]',
                'interactive' => $message['interactive']['button_reply']['title']
                    ?? $message['interactive']['list_reply']['title']
                    ?? '[Interactive]',
                'button'      => $message['button']['text'] ?? '[Button]',
                default       => '[' . strtoupper($type) . ']',
            };

            $mediaUrl = $message[$type]['link'] ?? ($message[$type]['id'] ?? null);

            $conversation = Conversation::firstOrCreate(
                [
                    'user_id'        => $device->user_id,
                    'device_id'      => $device->id,
                    'contact_number' => $senderNumber,
                ],
                ['contact_name' => $contactName]
            );

            $conversation->update([
                'contact_name'    => $contactName ?? $conversation->contact_name,
                'last_message'    => $body,
                'last_message_at' => now(),
                'unread_count'    => $conversation->unread_count + 1,
            ]);

            $metaMessageId = $message['id'] ?? null;
            $attributes = [
                'conversation_id' => $conversation->id,
                'direction'       => 'inbound',
                'type'            => $type,
                'body'            => $body,
                'media_url'       => is_string($mediaUrl) ? $mediaUrl : null,
                'meta_message_id' => $metaMessageId,
                'status'          => 'delivered',
                'payload'         => $message,
            ];

            if ($metaMessageId) {
                ChatMessage::updateOrCreate(['meta_message_id' => $metaMessageId], $attributes);
            } else {
                ChatMessage::create($attributes);
            }

            return $conversation;
        } catch (\Throwable $e) {
            Log::error('storeInboundMessage failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function forwardToWebhook(array $message, array $metadata, array $contacts, Device $device): void
    {
        if (!$device->webhook) return;
        try {
            Http::timeout(5)->post($device->webhook, [
                'event'    => 'message',
                'message'  => $message,
                'contacts' => $contacts,
                'metadata' => $metadata,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Webhook forwarding failed', ['device' => $device->id, 'error' => $e->getMessage()]);
        }
    }
}
