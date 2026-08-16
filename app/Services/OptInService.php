<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Device;
use App\Models\DripSequence;
use App\Models\OptInSetting;
use App\Models\SuppressionEntry;
use App\Services\Impl\MetaCloudApiService;
use Illuminate\Support\Facades\Log;

class OptInService
{
    /**
     * Process an inbound message for opt-in/opt-out keywords.
     * Returns 'opt_out', 'opt_in', or null if no keyword matched.
     */
    public function handle(Conversation $conversation, string $messageText, Device $device): ?string
    {
        if (!$messageText || !$device->user_id) return null;

        $settings = OptInSetting::forUser($device->user_id);

        if ($settings->matchesOptOut($messageText)) {
            $this->processOptOut($conversation, $device, $settings);
            return 'opt_out';
        }

        if ($settings->matchesOptIn($messageText)) {
            $this->processOptIn($conversation, $device, $settings);
            return 'opt_in';
        }

        return null;
    }

    private function processOptOut(Conversation $conversation, Device $device, OptInSetting $settings): void
    {
        try {
            SuppressionEntry::suppress(
                $device->user_id,
                $conversation->contact_number,
                'user_optout',
                'Auto-suppressed: contact sent opt-out keyword'
            );

            Log::info("OptIn: {$conversation->contact_number} opted out, suppressed for user #{$device->user_id}.");

            if ($settings->opt_out_reply) {
                $this->sendReply($device, $conversation->contact_number, $settings->opt_out_reply);
            }
        } catch (\Throwable $e) {
            Log::error('OptInService::processOptOut failed', ['error' => $e->getMessage()]);
        }
    }

    private function processOptIn(Conversation $conversation, Device $device, OptInSetting $settings): void
    {
        try {
            // Remove from suppression list if present
            SuppressionEntry::where('user_id', $device->user_id)
                ->where('number', $conversation->contact_number)
                ->delete();

            // Add to target phonebook if configured
            if ($settings->opt_in_phonebook_id) {
                $contact = Contact::updateOrCreate(
                    [
                        'user_id' => $device->user_id,
                        'number'  => $conversation->contact_number,
                    ],
                    [
                        'name' => $conversation->contact_name ?? $conversation->contact_number,
                    ]
                );
                $contact->tags()->syncWithoutDetaching([$settings->opt_in_phonebook_id]);
            }

            Log::info("OptIn: {$conversation->contact_number} opted in for user #{$device->user_id}.");

            if ($settings->opt_in_reply) {
                $this->sendReply($device, $conversation->contact_number, $settings->opt_in_reply);
            }

            // Auto-enroll in any active opt_in-triggered sequences
            $this->enrollInDripSequences($device, $conversation->contact_number);
        } catch (\Throwable $e) {
            Log::error('OptInService::processOptIn failed', ['error' => $e->getMessage()]);
        }
    }

    private function enrollInDripSequences(Device $device, string $contactNumber): void
    {
        try {
            DripSequence::where('user_id', $device->user_id)
                ->where('status', 'active')
                ->where('trigger_type', 'opt_in')
                ->with('steps')
                ->get()
                ->each(function ($seq) use ($device, $contactNumber) {
                    $deviceId = $seq->default_device_id ?? $device->id;
                    $seq->enroll($contactNumber, $device->user_id, $deviceId);
                });
        } catch (\Throwable $e) {
            Log::warning('OptInService: drip enrollment failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendReply(Device $device, string $to, string $message): void
    {
        try {
            $api = new MetaCloudApiService($device);
            $api->sendText((object) ['message' => $message], $to);
        } catch (\Throwable $e) {
            Log::warning('OptInService: failed to send reply', ['error' => $e->getMessage()]);
        }
    }
}
