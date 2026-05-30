<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class ChatRouter
{
    /**
     * Assign a conversation to the best available agent.
     * Uses team routing rules first, then round-robin within the matched team.
     * Falls back to any online agent if no team rule matches.
     */
    public function assignConversation(Conversation $conversation): ?Agent
    {
        // Skip if already assigned and still open
        if ($conversation->assigned_agent_id && $conversation->conversation_status === 'open') {
            return $conversation->assignedAgent;
        }

        $userId = $conversation->user_id;

        // 1. Check team routing rules
        $targetTeam = $this->resolveTeam($userId, $conversation);

        // 2. Find available agent within target team (or globally if no team matched)
        $agent = $this->findBestAgent($userId, $targetTeam?->id);

        if (!$agent) {
            // Mark as pending (no available agent) — stays in queue
            $conversation->update(['conversation_status' => 'pending']);
            Log::info("ChatRouter: no available agent for conv #{$conversation->id}, queued as pending.");
            return null;
        }

        $this->doAssign($conversation, $agent, 'auto');

        return $agent;
    }

    /**
     * Manually reassign a conversation to a specific agent (supervisor take-over or manual).
     */
    public function manualAssign(Conversation $conversation, Agent $agent): void
    {
        // Decrement old agent count
        if ($conversation->assigned_agent_id && $conversation->assigned_agent_id !== $agent->id) {
            $old = Agent::find($conversation->assigned_agent_id);
            $old?->decrement('active_chat_count');
        }

        $this->doAssign($conversation, $agent, 'manual');
    }

    private function doAssign(Conversation $conversation, Agent $agent, string $source): void
    {
        $conversation->update([
            'assigned_agent_id'   => $agent->id,
            'assignment_source'   => $source,
            'assigned_at'         => $conversation->assigned_at ?? now(),
            'conversation_status' => 'open',
        ]);

        $agent->increment('active_chat_count');

        // Broadcast assignment event via Socket.io
        SocketPushService::pushToConversation($conversation->id, 'conversation_updated', [
            'conversation_id'  => $conversation->id,
            'event'            => 'assigned',
            'agent_id'         => $agent->id,
            'agent_name'       => $agent->name,
            'assignment_source'=> $source,
        ]);

        Log::info("ChatRouter: conv #{$conversation->id} assigned to agent #{$agent->id} ({$agent->name}) via {$source}.");
    }

    /**
     * Resolve which team should handle this conversation based on routing rules.
     * Rules are simple JSON: [{"field":"keyword","value":"billing"}]
     * If contact_name or last_message contains the keyword → route to that team.
     */
    private function resolveTeam(int $userId, Conversation $conversation): ?Team
    {
        $teams = Team::where('user_id', $userId)
            ->whereNotNull('routing_rules')
            ->get();

        $text = strtolower(($conversation->last_message ?? '') . ' ' . ($conversation->contact_name ?? ''));

        foreach ($teams as $team) {
            foreach ($team->routing_rules ?? [] as $rule) {
                $keyword = strtolower($rule['value'] ?? '');
                if ($keyword && str_contains($text, $keyword)) {
                    return $team;
                }
            }
        }

        // Check if there are any teams at all — use first team as default
        return Team::where('user_id', $userId)->first();
    }

    /**
     * Round-robin within a team: pick the online agent with the lowest active_chat_count
     * under their max_concurrent_chats limit.
     */
    private function findBestAgent(int $userId, ?int $teamId): ?Agent
    {
        $query = Agent::where('user_id', $userId)
            ->where('status', 'online')
            ->whereRaw('active_chat_count < max_concurrent_chats')
            ->orderBy('active_chat_count')
            ->orderBy('last_seen_at', 'desc');

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        return $query->first();
    }
}
