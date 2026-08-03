<?php

namespace App\Services\Ads\Channels;

use App\Models\AdMetric;
use App\Models\AdPlacement;
use App\Services\Ads\AdChannelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram channel posts and bot blasts via Telegram Bot API.
 * Posts messages/media to a channel; metrics are tracked locally (no native ads analytics).
 * Docs: https://core.telegram.org/bots/api
 */
class TelegramService implements AdChannelInterface
{
    private const BASE_URL = 'https://api.telegram.org/bot';

    public function launch(AdPlacement $placement): object
    {
        $creds    = $placement->channel->credentials ?? [];
        $botToken = $creds['bot_token'] ?? null;
        $chatId   = $creds['channel_username'] ?? null;

        if (!$botToken || !$chatId) {
            return (object) ['status' => false, 'external_id' => null, 'error' => 'Missing Telegram credentials'];
        }

        $creative = $placement->creative;
        $caption  = trim(($creative?->headline ? "<b>{$creative->headline}</b>\n\n" : '') . ($creative?->body ?? ''));
        $ctaUrl   = $creative?->cta_url;

        // Inline keyboard with CTA button
        $replyMarkup = null;
        if ($ctaUrl) {
            $replyMarkup = json_encode(['inline_keyboard' => [[
                ['text' => $creative?->cta_text ?? 'Learn More', 'url' => $ctaUrl],
            ]]]);
        }

        try {
            $mediaUrl = $creative?->firstMediaUrl();

            if ($mediaUrl && in_array($creative?->format, ['image', 'story'])) {
                $endpoint = 'sendPhoto';
                $payload  = array_filter([
                    'chat_id'      => $chatId,
                    'photo'        => $mediaUrl,
                    'caption'      => $caption,
                    'parse_mode'   => 'HTML',
                    'reply_markup' => $replyMarkup,
                ]);
            } elseif ($mediaUrl && in_array($creative?->format, ['video', 'reel'])) {
                $endpoint = 'sendVideo';
                $payload  = array_filter([
                    'chat_id'      => $chatId,
                    'video'        => $mediaUrl,
                    'caption'      => $caption,
                    'parse_mode'   => 'HTML',
                    'reply_markup' => $replyMarkup,
                ]);
            } else {
                $endpoint = 'sendMessage';
                $payload  = array_filter([
                    'chat_id'      => $chatId,
                    'text'         => $caption,
                    'parse_mode'   => 'HTML',
                    'reply_markup' => $replyMarkup,
                ]);
            }

            $res = Http::post(self::BASE_URL . $botToken . '/' . $endpoint, $payload);

            if (!$res->successful() || !$res->json('ok')) {
                $error = $res->json('description') ?? 'Telegram API error';
                return (object) ['status' => false, 'external_id' => null, 'error' => $error];
            }

            $messageId = (string) $res->json('result.message_id');
            $placement->update(['external_ad_id' => $messageId, 'status' => 'active']);

            // Log a metric row for send time (Telegram has no time-series analytics API)
            AdMetric::updateOrCreate(
                ['ad_placement_id' => $placement->id, 'date' => now()->toDateString()],
                ['impressions' => 1, 'reach' => 1, 'clicks' => 0, 'spend' => 0, 'channel_raw' => $res->json()]
            );

            return (object) ['status' => true, 'external_id' => $messageId, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('TelegramService::launch failed', ['error' => $e->getMessage()]);
            return (object) ['status' => false, 'external_id' => null, 'error' => $e->getMessage()];
        }
    }

    public function pause(AdPlacement $placement): object
    {
        // Telegram does not support pausing; delete the message optionally
        $placement->update(['status' => 'paused']);
        return (object) ['status' => true, 'error' => null];
    }

    public function syncMetrics(AdPlacement $placement): void
    {
        // Telegram Bot API does not expose view/impression analytics for channel posts.
        // Metrics are updated from forward count when available via getChatMember or message info.
        $creds    = $placement->channel->credentials ?? [];
        $botToken = $creds['bot_token'] ?? null;
        if (!$botToken || !$placement->external_ad_id) return;

        try {
            // getForwardCount is not a real Telegram endpoint; forward count is available in
            // channel post views via getChatStatistics (only for channels with 500+ members via TDLib).
            // No-op here — Sprint 5 can integrate with Telegram Statistics API if needed.
            $placement->update(['last_synced_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('TelegramService::syncMetrics failed', ['error' => $e->getMessage()]);
        }
    }

    public function verify(array $credentials): bool
    {
        $botToken = $credentials['bot_token'] ?? null;
        if (!$botToken) return false;

        try {
            $res = Http::get(self::BASE_URL . $botToken . '/getMe');
            return $res->successful() && $res->json('ok');
        } catch (\Throwable) {
            return false;
        }
    }
}
