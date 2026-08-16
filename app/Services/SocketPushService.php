<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketPushService
{
    /**
     * Parsed .env contents for this request only — reset on every new
     * request/CLI run since it's a plain PHP static, not a putenv() call.
     */
    private static ?array $freshEnv = null;

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
        $url    = rtrim(self::freshEnv('SOCKET_URL', 'http://127.0.0.1:3100'), '/') . '/push';
        $secret = self::freshEnv('SOCKET_SECRET', '');

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

    /**
     * Read a value straight from the .env file on disk instead of env().
     *
     * Laravel's env() loader (Dotenv::createImmutable) sets each variable via
     * putenv() only once per OS process, and won't overwrite it if already
     * set. On this host, PHP workers are long-lived and reused across many
     * requests — so an .env edit is invisible to already-running workers
     * until they happen to recycle, which can take hours. Parsing the file
     * directly here sidesteps that cache entirely: every push always reflects
     * the current .env, with no PHP restart required after a config change.
     */
    private static function freshEnv(string $key, string $default = ''): string
    {
        if (self::$freshEnv === null) {
            $path = base_path('.env');
            self::$freshEnv = is_readable($path)
                ? \Dotenv\Dotenv::parse(file_get_contents($path))
                : [];
        }

        return self::$freshEnv[$key] ?? $default;
    }
}
