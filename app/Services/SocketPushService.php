<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketPushService
{
    /**
     * Push a real-time event to all Socket.io clients in a conversation room.
     * Fire-and-forget — never throws; failures are logged only.
     */
    public static function pushToConversation(int $conversationId, string $event, array $payload): void
    {
        $url    = rtrim(env('SOCKET_URL', 'http://127.0.0.1:3100'), '/') . '/push';
        $secret = env('SOCKET_SECRET', '');

        try {
            Http::timeout(2)->post($url, [
                'secret'  => $secret,
                'room'    => 'conv-' . $conversationId,
                'event'   => $event,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::debug("SocketPushService: failed to push event '{$event}' to conv-{$conversationId}: " . $e->getMessage());
        }
    }
}
