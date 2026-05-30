<?php

namespace App\Jobs;

use App\Models\ChatbotSession;
use App\Models\Conversation;
use App\Services\FlowEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DelayedFlowResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int    $conversationId,
        public readonly int    $sessionId,
        public readonly string $afterNodeId,
    ) {}

    public function handle(): void
    {
        $session      = ChatbotSession::with('flow')->find($this->sessionId);
        $conversation = Conversation::with('device')->find($this->conversationId);

        if (!$session || !$conversation) {
            Log::warning("DelayedFlowResumeJob: missing session {$this->sessionId} or conversation {$this->conversationId}");
            return;
        }

        // If session was completed/cancelled in the meantime, do nothing
        if (!$session->isActive()) {
            return;
        }

        (new FlowEngine())->resumeFromNode($conversation, $session, $this->afterNodeId);
    }
}
