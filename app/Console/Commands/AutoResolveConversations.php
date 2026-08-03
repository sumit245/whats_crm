<?php

namespace App\Console\Commands;

use App\Models\ChatSetting;
use App\Models\Conversation;
use App\Services\SocketPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoResolveConversations extends Command
{
    protected $signature   = 'chat:auto-resolve';
    protected $description = 'Auto-resolve conversations idle beyond the configured threshold';

    public function handle(): void
    {
        // Load all users with auto-resolve enabled
        $settings = ChatSetting::where('auto_resolve_enabled', true)->get();

        foreach ($settings as $setting) {
            $cutoff = now()->subHours($setting->auto_resolve_hours);

            $conversations = Conversation::where('user_id', $setting->user_id)
                ->whereNull('resolved_at')
                ->where('conversation_status', '!=', 'resolved')
                ->where('last_message_at', '<', $cutoff)
                ->get();

            foreach ($conversations as $conv) {
                $conv->update([
                    'resolved_at'         => now(),
                    'conversation_status' => 'resolved',
                ]);

                SocketPushService::pushToConversation($conv->id, 'conversation_resolved', [
                    'conversation_id' => $conv->id,
                    'resolved_by'     => 'auto',
                ]);

                SocketPushService::pushToInbox($conv->user_id, 'inbox_update', [
                    'conversation_id'  => $conv->id,
                    'contact_number'   => $conv->contact_number,
                    'conversation_status' => 'resolved',
                ]);
            }

            if ($conversations->count() > 0) {
                Log::info("AutoResolve: resolved {$conversations->count()} conversations for user {$setting->user_id}");
            }
        }
    }
}
