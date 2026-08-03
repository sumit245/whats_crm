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
        static::push('conv-' . $conversationId, $event, $payload);
    }

    /**
     * Push a real-time event to all Socket.io clients in a user's inbox room.
     * Used to update the conversation list sidebar without a page refresh.
     */
    public static function pushToInbox(int $userId, string $event, array $payload): void
    {
        static::push('inbox-' . $userId, $event, $payload);
    }

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
