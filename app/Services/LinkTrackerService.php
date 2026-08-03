<?php

namespace App\Services;

use App\Models\TrackedLink;
use Illuminate\Support\Facades\Log;

class LinkTrackerService
{
    // Matches http:// and https:// URLs, stops at whitespace or common trailing punctuation
    private const URL_PATTERN = '/https?:\/\/[^\s\]\[<>"\']+/i';

    /**
     * Replace every URL in $text with a tracked redirect URL containing the contact number.
     * Only rewrites URLs that are NOT already pointing at this app (avoids double-wrapping).
     */
    public function wrapUrls(string $text, int $userId, int $campaignId, string $contactNumber): string
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        return preg_replace_callback(self::URL_PATTERN, function (array $matches) use ($userId, $campaignId, $contactNumber, $appHost) {
            $url     = $matches[0];
            $urlHost = parse_url($url, PHP_URL_HOST);

            // Skip links that already point to this app (e.g. previously wrapped)
            if ($urlHost === $appHost) {
                return $url;
            }

            try {
                $link = TrackedLink::findOrMake($userId, $campaignId, $url);
                return $link->redirectUrl($contactNumber);
            } catch (\Throwable $e) {
                Log::warning('LinkTrackerService: could not wrap URL', ['url' => $url, 'error' => $e->getMessage()]);
                return $url;
            }
        }, $text) ?? $text;
    }

    /**
     * Return true if the message text contains any http/https URL.
     */
    public function hasUrls(string $text): bool
    {
        return (bool) preg_match(self::URL_PATTERN, $text);
    }
}
