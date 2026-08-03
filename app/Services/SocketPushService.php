<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketPushService
{
    /**
     * Push a real-time event to all Socket.io clients in a conversation room
     * (room name "conv-{id}"). Fire-and-forget — never throws.
     */
    public static function pushToConversation(int $conversationId, string $event, array $payload): void
    {
        self::push('conv-' . $conversationId, $event, $payload);
    }

    /**
     * Push a real-time event to a user's inbox room ("inbox-{userId}") so the
     * conversation sidebar updates live. Fire-and-forget — never throws.
     */
    public static function pushToInbox(int $userId, string $event, array $payload): void
    {
        self::push('inbox-' . $userId, $event, $payload);
    }

    /**
     * Emit an event to a named Socket.io room via the Node push endpoint.
     * Failures are logged only, never thrown, so webhook/HTTP handling continues.
     */
    private static function push(string $room, string $event, array $payload): void
    {
        $url    = rtrim(env('SOCKET_URL', 'http://127.0.0.1:3100'), '/') . '/push';
        $secret = env('SOCKET_SECRET', '');

        try {
            Http::timeout(2)->post($url, [
                'secret'  => $secret,
                'room'    => $room,
                'event'   => $event,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::debug("SocketPushService: failed to push event '{$event}' to {$room}: " . $e->getMessage());
        }
    }
}
