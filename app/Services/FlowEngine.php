<?php

namespace App\Services;

use App\Jobs\DelayedFlowResumeJob;
use App\Models\Blast;
use App\Models\ChatbotFlow;
use App\Models\ChatbotSession;
use App\Models\Conversation;
use App\Models\Device;
use App\Models\WabaTemplate;
use App\Services\Impl\MetaCloudApiService;
use Illuminate\Support\Facades\Log;

class FlowEngine
{
    private const MAX_NODES_PER_TURN = 20;

    /** After this many consecutive no-match messages, send fallback then escalate */
    private const FALLBACK_WARN_AT      = 2;
    private const FALLBACK_HANDOFF_AT   = 3;

    /** awaiting_input sessions expire after this many hours of silence */
    private const SESSION_TTL_HOURS = 24;

    private MetaCloudApiService $api;
    private Conversation        $conversation;
    private ChatbotSession      $session;

    /**
     * Entry point: called from MetaWebhookController on every inbound message.
     * Returns true if the bot handled the message, false if it should be ignored.
     */
    public function handleInbound(Conversation $conversation, string $messageBody, array $payload = []): bool
    {
        $this->conversation = $conversation;

        // ── 1. Check for an existing active session ────────────────────────
        $session = ChatbotSession::activeFor($conversation->id);

        if ($session) {
            // A human has the chat — bot stays silent
            if ($session->state === 'human_assigned') return false;

            $this->session = $session;
            $this->api     = new MetaCloudApiService($conversation->device);

            if ($session->state === 'awaiting_input') {
                return $this->processInput($messageBody);
            }

            // bot_active but somehow we got here without a node — restart
            return $this->executeFromNode($session->current_node_id, $messageBody);
        }

        // ── 2. No active session — find a matching flow ────────────────────
        $flow = $this->findMatchingFlow($conversation, $messageBody, $payload);

        if (!$flow) {
            // No flow matched — handle with fallback logic
            return $this->handleNoFlowMatch($conversation, $messageBody);
        }

        // Reset fallback counter on successful match
        if ($conversation->fallback_count > 0) {
            $conversation->update(['fallback_count' => 0]);
        }

        $this->api = new MetaCloudApiService($conversation->device);

        $triggerNodeId = $flow->findTriggerNodeId();
        if (!$triggerNodeId) {
            Log::warning("FlowEngine: flow {$flow->id} has no trigger node.");
            return false;
        }

        $this->session = ChatbotSession::create([
            'conversation_id'  => $conversation->id,
            'flow_id'          => $flow->id,
            'current_node_id'  => $triggerNodeId,
            'state'            => 'bot_active',
            'variables'        => [],
            'fallback_count'   => 0,
            'last_executed_at' => now(),
        ]);

        // Start execution from the node AFTER the trigger
        $firstActionNodeId = $this->getNextNodeId($triggerNodeId, 'output_1');
        if (!$firstActionNodeId) {
            $this->completeSession();
            return true;
        }

        return $this->executeFromNode($firstActionNodeId, $messageBody);
    }

    /**
     * Phase A: Handle the case where no flow matches and no active session exists.
     * Increments fallback_count on the conversation, sends fallback_message,
     * and triggers human handoff after repeated failures.
     */
    private function handleNoFlowMatch(Conversation $conversation, string $messageBody): bool
    {
        $count = ($conversation->fallback_count ?? 0) + 1;
        $conversation->update(['fallback_count' => $count]);

        $this->api = new MetaCloudApiService($conversation->device);

        // Grab a fallback message from any active flow on this device
        $fallbackFlow = ChatbotFlow::where('user_id', $conversation->user_id)
            ->where('device_id', $conversation->device_id)
            ->active()
            ->whereNotNull('fallback_message')
            ->latest()
            ->first();

        if ($count >= self::FALLBACK_HANDOFF_AT) {
            // Create a human_assigned session so the bot mutes itself
            $session = ChatbotSession::create([
                'conversation_id'  => $conversation->id,
                'flow_id'          => $fallbackFlow?->id ?? ChatbotFlow::where('device_id', $conversation->device_id)->value('id'),
                'current_node_id'  => null,
                'state'            => 'human_assigned',
                'variables'        => [],
                'fallback_count'   => $count,
                'last_executed_at' => now(),
            ]);

            $msg = $fallbackFlow?->fallback_message ?? __('We are connecting you to a human agent. Please hold on.');
            $this->sendRawText($msg, $conversation);
            $conversation->update(['fallback_count' => 0]);

            Log::info("FlowEngine: conversation {$conversation->id} escalated to human after {$count} fallbacks.");
            return true;
        }

        if ($count >= self::FALLBACK_WARN_AT && $fallbackFlow?->fallback_message) {
            $this->sendRawText($fallbackFlow->fallback_message, $conversation);
            return true;
        }

        return false;
    }

    // ── Input processing (awaiting_input state) ─────────────────────────────

    private function processInput(string $value): bool
    {
        $varName = $this->session->awaiting_variable;

        if ($varName) {
            $this->session->setVariable($varName, $value);
        }

        // Find the next node after the ask_input node
        $nextNodeId = $this->getNextNodeId($this->session->current_node_id, 'output_1');

        $this->session->update([
            'state'             => 'bot_active',
            'awaiting_variable' => null,
            'expires_at'        => null, // clear expiry when input received
        ]);

        if (!$nextNodeId) {
            $this->completeSession();
            return true;
        }

        return $this->executeFromNode($nextNodeId, $value);
    }

    // ── Node execution loop ─────────────────────────────────────────────────

    private function executeFromNode(string $nodeId, string $inputText = ''): bool
    {
        $nodes   = $this->session->flow->getNodes();
        $visited = 0;

        while ($nodeId && $visited < self::MAX_NODES_PER_TURN) {
            $visited++;
            $node = $nodes[$nodeId] ?? null;

            if (!$node) {
                Log::warning("FlowEngine: node {$nodeId} not found in flow {$this->session->flow_id}");
                $this->completeSession();
                return true;
            }

            $this->session->update([
                'current_node_id'  => $nodeId,
                'last_executed_at' => now(),
            ]);

            $result = $this->executeNode($node, $nodeId, $inputText);
            $nodeId = $result['next_node_id'] ?? null;

            $action = $result['action'] ?? '';

            // Phase B: non-blocking delay dispatched a job — stop loop
            if ($action === 'delayed') break;

            // Other execution pause points
            if (in_array($action, ['stop', 'pause', 'handoff', 'complete'])) break;
        }

        return true;
    }

    /**
     * Phase B & C: Resume execution after a queued delay.
     * Called by DelayedFlowResumeJob.
     */
    public function resumeFromNode(Conversation $conversation, ChatbotSession $session, string $afterNodeId): void
    {
        $this->conversation = $conversation;
        $this->session      = $session;
        $this->api          = new MetaCloudApiService($conversation->device);

        if (!$session->isActive()) {
            Log::info("FlowEngine::resumeFromNode: session {$session->id} no longer active, skipping.");
            return;
        }

        $this->executeFromNode($afterNodeId);
    }

    // ── Individual node handlers ────────────────────────────────────────────

    private function executeNode(array $node, string $nodeId, string $inputText): array
    {
        $type = $node['name'] ?? 'unknown';
        $data = $node['data'] ?? [];

        Log::debug("FlowEngine: executing node {$nodeId} type={$type}");

        return match ($type) {
            'trigger_keyword', 'trigger_all', 'trigger_referral', 'trigger_api'
                                       => $this->nodePassthrough($node, $nodeId),
            'send_text'                => $this->nodeSendText($node, $nodeId, $data),
            'send_image'               => $this->nodeSendImage($node, $nodeId, $data),
            'send_buttons'             => $this->nodeSendButtons($node, $nodeId, $data),
            'send_template'            => $this->nodeSendTemplate($node, $nodeId, $data),
            'ask_input'                => $this->nodeAskInput($node, $nodeId, $data),
            'condition'                => $this->nodeCondition($node, $nodeId, $data, $inputText),
            'delay'                    => $this->nodeDelay($node, $nodeId, $data),
            'human_handoff'            => $this->nodeHumanHandoff($node, $nodeId, $data),
            'end_flow'                 => $this->nodeEndFlow(),
            default => [
                'action'       => 'next',
                'next_node_id' => $this->getNextNodeId($nodeId, 'output_1'),
            ],
        };
    }

    private function nodePassthrough(array $node, string $nodeId): array
    {
        return ['action' => 'next', 'next_node_id' => $this->getNextNodeId($nodeId, 'output_1')];
    }

    private function nodeSendText(array $node, string $nodeId, array $data): array
    {
        $message = $this->session->resolveVariables($data['message'] ?? '');
        if ($message) {
            $this->sendText($message);
        }
        return ['action' => 'next', 'next_node_id' => $this->getNextNodeId($nodeId, 'output_1')];
    }

    private function nodeSendImage(array $node, string $nodeId, array $data): array
    {
        $url     = $data['url'] ?? '';
        $caption = $this->session->resolveVariables($data['caption'] ?? '');
        if ($url) {
            $req = (object) ['url' => $url, 'caption' => $caption, 'media_type' => 'image'];
            $this->api->sendMedia($req, $this->conversation->contact_number);
        }
        return ['action' => 'next', 'next_node_id' => $this->getNextNodeId($nodeId, 'output_1')];
    }

    private function nodeSendButtons(array $node, string $nodeId, array $data): array
    {
        $message = $this->session->resolveVariables($data['message'] ?? '');
        $buttons = array_filter(array_map('trim', explode("\n", $data['buttons'] ?? '')));
        if ($message && $buttons) {
            $req = (object) ['message' => $message, 'button' => array_values($buttons)];
            $this->api->sendButton($req, $this->conversation->contact_number);
        }
        return ['action' => 'next', 'next_node_id' => $this->getNextNodeId($nodeId, 'output_1')];
    }

    private function nodeSendTemplate(array $node, string $nodeId, array $data): array
    {
        $templateId = $data['template_id'] ?? null;
        if ($templateId) {
            $template = WabaTemplate::find($templateId);
            if ($template) {
                $fakeBlast = new Blast([
                    'receiver'           => $this->conversation->contact_number,
                    'template_variables' => [],
                ]);
                $this->api->sendBlastTemplate($fakeBlast, [
                    'name'     => $template->name,
                    'language' => $template->language,
                ]);
            }
        }
        return ['action' => 'next', 'next_node_id' => $this->getNextNodeId($nodeId, 'output_1')];
    }

    private function nodeAskInput(array $node, string $nodeId, array $data): array
    {
        $question = $this->session->resolveVariables($data['question'] ?? '');
        $varName  = $data['variable'] ?? 'input';

        if ($question) {
            $this->sendText($question);
        }

        // Phase C: set expiry when entering awaiting_input state
        $this->session->update([
            'state'             => 'awaiting_input',
            'current_node_id'   => $nodeId,
            'awaiting_variable' => $varName,
            'expires_at'        => now()->addHours(self::SESSION_TTL_HOURS),
        ]);

        return ['action' => 'pause'];
    }

    private function nodeCondition(array $node, string $nodeId, array $data, string $inputText): array
    {
        $variable = $data['variable'] ?? '';
        $operator = $data['operator'] ?? 'contains';
        $value    = mb_strtolower(trim($data['value'] ?? ''));
        $actual   = mb_strtolower(trim((string) ($variable
            ? $this->session->getVariable($variable, $inputText)
            : $inputText
        )));

        $matches = match ($operator) {
            'equals'       => $actual === $value,
            'contains'     => str_contains($actual, $value),
            'starts_with'  => str_starts_with($actual, $value),
            'not_equals'   => $actual !== $value,
            'not_contains' => !str_contains($actual, $value),
            default        => false,
        };

        $outputKey = $matches ? 'output_1' : 'output_2';
        return ['action' => 'next', 'next_node_id' => $this->getNextNodeId($nodeId, $outputKey)];
    }

    /**
     * Phase B: Non-blocking delay node.
     * Dispatches a queued job instead of sleeping synchronously.
     */
    private function nodeDelay(array $node, string $nodeId, array $data): array
    {
        $seconds    = max(1, (int) ($data['seconds'] ?? 2));
        $nextNodeId = $this->getNextNodeId($nodeId, 'output_1');

        if ($nextNodeId) {
            DelayedFlowResumeJob::dispatch(
                $this->conversation->id,
                $this->session->id,
                $nextNodeId,
            )->delay(now()->addSeconds($seconds))->onQueue('broadcasts');

            Log::info("FlowEngine: queued delay {$seconds}s for session {$this->session->id}, resuming at node {$nextNodeId}");
        }

        // 'delayed' signals executeFromNode() to stop the current loop
        return ['action' => 'delayed'];
    }

    private function nodeHumanHandoff(array $node, string $nodeId, array $data): array
    {
        $handoffMessage = $data['message'] ?? null;
        if ($handoffMessage) {
            $this->sendText($this->session->resolveVariables($handoffMessage));
        }

        $this->session->update(['state' => 'human_assigned']);

        Log::info("FlowEngine: conversation {$this->conversation->id} handed off to human.");

        return ['action' => 'handoff'];
    }

    private function nodeEndFlow(): array
    {
        $this->completeSession();
        return ['action' => 'complete'];
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function getNextNodeId(string $currentNodeId, string $outputKey = 'output_1'): ?string
    {
        $nodes = $this->session->flow->getNodes();
        $node  = $nodes[$currentNodeId] ?? null;
        if (!$node) return null;

        $connections = $node['outputs'][$outputKey]['connections'] ?? [];
        return isset($connections[0]['node']) ? (string) $connections[0]['node'] : null;
    }

    private function findMatchingFlow(Conversation $conversation, string $message, array $payload = []): ?ChatbotFlow
    {
        $flows = ChatbotFlow::where('user_id', $conversation->user_id)
            ->where('device_id', $conversation->device_id)
            ->active()
            ->get();

        $keywordFlow  = null;
        $referralFlow = null;
        $allFlow      = null;
        $apiFlow      = null;

        $referral = $payload['referral'] ?? null;
        $isApi    = $payload['api_trigger'] ?? false;

        foreach ($flows as $flow) {
            if ($flow->matchesTrigger($message, $referral, $isApi)) {
                if ($flow->trigger_type === 'keyword')  $keywordFlow  ??= $flow;
                if ($flow->trigger_type === 'referral') $referralFlow ??= $flow;
                if ($flow->trigger_type === 'all')      $allFlow      ??= $flow;
                if ($flow->trigger_type === 'api')      $apiFlow      ??= $flow;
            }
        }

        // Priority: api > keyword > referral > all
        return $apiFlow ?? $keywordFlow ?? $referralFlow ?? $allFlow;
    }

    private function sendText(string $message): void
    {
        $this->sendRawText($message, $this->conversation);
    }

    private function sendRawText(string $message, Conversation $conversation): void
    {
        try {
            $req    = (object) ['message' => $message, 'text' => $message];
            $result = $this->api->sendText($req, $conversation->contact_number);

            \App\Models\ChatMessage::create([
                'conversation_id' => $conversation->id,
                'direction'       => 'outbound',
                'type'            => 'text',
                'body'            => $message,
                'meta_message_id' => $result->message_id ?? null,
                'status'          => $result->status ? 'sent' : 'failed',
            ]);

            $conversation->update([
                'last_message'    => $message,
                'last_message_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("FlowEngine sendText failed: " . $e->getMessage());
        }
    }

    private function completeSession(): void
    {
        $this->session->update([
            'state'            => 'completed',
            'last_executed_at' => now(),
            'expires_at'       => null,
        ]);
    }

    /**
     * Reactivate bot after human resolves a chat.
     */
    public static function reactivateBot(int $conversationId): void
    {
        ChatbotSession::where('conversation_id', $conversationId)
            ->where('state', 'human_assigned')
            ->update(['state' => 'completed']);
    }
}
