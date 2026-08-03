<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Device;
use App\Models\Team;
use App\Models\User;
use App\Services\ChatRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatRouterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->device = Device::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'Connected',
        ]);
    }

    private function makeConversation(array $attrs = []): Conversation
    {
        return Conversation::factory()->create(array_merge([
            'user_id'   => $this->user->id,
            'device_id' => $this->device->id,
        ], $attrs));
    }

    private function makeAgent(array $attrs = []): Agent
    {
        return Agent::factory()->create(array_merge([
            'user_id'              => $this->user->id,
            'status'               => 'online',
            'max_concurrent_chats' => 5,
            'active_chat_count'    => 0,
        ], $attrs));
    }

    public function test_round_robin_distributes_to_lowest_load_agent(): void
    {
        $busy  = $this->makeAgent(['active_chat_count' => 3]);
        $light = $this->makeAgent(['active_chat_count' => 1]);

        $conv = $this->makeConversation();
        $router = new ChatRouter();
        $assigned = $router->assignConversation($conv);

        $this->assertEquals($light->id, $assigned->id, 'Should assign to agent with lowest load');
    }

    public function test_offline_agents_are_skipped(): void
    {
        $this->makeAgent(['status' => 'offline']);
        $online = $this->makeAgent(['status' => 'online']);

        $conv     = $this->makeConversation();
        $assigned = (new ChatRouter())->assignConversation($conv);

        $this->assertEquals($online->id, $assigned->id);
    }

    public function test_full_agent_is_skipped(): void
    {
        $full    = $this->makeAgent(['active_chat_count' => 5, 'max_concurrent_chats' => 5]);
        $free    = $this->makeAgent(['active_chat_count' => 0, 'max_concurrent_chats' => 5]);

        $conv     = $this->makeConversation();
        $assigned = (new ChatRouter())->assignConversation($conv);

        $this->assertEquals($free->id, $assigned->id);
    }

    public function test_no_available_agent_marks_conversation_pending(): void
    {
        $this->makeAgent(['status' => 'offline']);

        $conv   = $this->makeConversation();
        $result = (new ChatRouter())->assignConversation($conv);

        $this->assertNull($result);
        $this->assertEquals('pending', $conv->fresh()->conversation_status);
    }

    public function test_team_routing_rules_respected(): void
    {
        $financeTeam = Team::factory()->create([
            'user_id'       => $this->user->id,
            'routing_rules' => [['field' => 'keyword', 'value' => 'billing']],
        ]);

        $supportAgent = $this->makeAgent(['team_id' => null]);
        $financeAgent = $this->makeAgent(['team_id' => $financeTeam->id]);

        $conv = $this->makeConversation(['last_message' => 'I have a billing question']);
        $assigned = (new ChatRouter())->assignConversation($conv);

        $this->assertEquals($financeAgent->id, $assigned->id, 'Billing keyword should route to finance team');
    }

    public function test_already_assigned_open_conversation_is_not_reassigned(): void
    {
        $agentA = $this->makeAgent();
        $agentB = $this->makeAgent();

        $conv = $this->makeConversation([
            'assigned_agent_id'   => $agentA->id,
            'conversation_status' => 'open',
        ]);

        $result = (new ChatRouter())->assignConversation($conv);

        $this->assertEquals($agentA->id, $result->id, 'Should not reassign an already-open conversation');
    }

    public function test_manual_assign_decrements_previous_agent(): void
    {
        $agentA = $this->makeAgent(['active_chat_count' => 2]);
        $agentB = $this->makeAgent(['active_chat_count' => 0]);

        $conv = $this->makeConversation(['assigned_agent_id' => $agentA->id]);

        (new ChatRouter())->manualAssign($conv, $agentB);

        $this->assertEquals(1, $agentA->fresh()->active_chat_count);
        $this->assertEquals(1, $agentB->fresh()->active_chat_count);
    }
}
