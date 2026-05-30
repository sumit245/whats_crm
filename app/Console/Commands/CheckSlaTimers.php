<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Services\SocketPushService;
use Illuminate\Console\Command;

class CheckSlaTimers extends Command
{
    protected $signature   = 'chat:check-sla';
    protected $description = 'Flag conversations that have breached the 15-minute first-response SLA';

    public function handle(): void
    {
        // Find open assigned conversations with no first response, assigned > 15 min ago, not yet breached
        $breached = Conversation::where('conversation_status', 'open')
            ->whereNotNull('assigned_agent_id')
            ->whereNull('first_response_at')
            ->whereNull('resolved_at')
            ->where('sla_breached', false)
            ->where('assigned_at', '<=', now()->subMinutes(15))
            ->get();

        foreach ($breached as $conv) {
            $conv->update(['sla_breached' => true]);

            // Alert all connected supervisors / the inbox owner
            SocketPushService::pushToConversation($conv->id, 'sla_breach', [
                'conversation_id' => $conv->id,
                'contact_name'    => $conv->contact_name ?? $conv->contact_number,
                'agent_id'        => $conv->assigned_agent_id,
                'assigned_at'     => $conv->assigned_at?->toISOString(),
            ]);
        }

        if ($breached->count()) {
            $this->info("SLA breach flagged for {$breached->count()} conversation(s).");
        }
    }
}
