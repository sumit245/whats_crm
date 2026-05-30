<?php

namespace App\Console\Commands;

use App\Models\ChatbotSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireStaleSessionsCommand extends Command
{
    protected $signature   = 'sessions:expire';
    protected $description = 'Mark expired awaiting_input chatbot sessions as completed';

    public function handle(): int
    {
        $expired = ChatbotSession::where('state', 'awaiting_input')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = $expired->count();

        foreach ($expired as $session) {
            $session->update([
                'state'            => 'completed',
                'last_executed_at' => now(),
                'expires_at'       => null,
            ]);
        }

        if ($count > 0) {
            Log::info("sessions:expire: expired {$count} stale chatbot sessions.");
            $this->info("Expired {$count} stale sessions.");
        }

        return 0;
    }
}
