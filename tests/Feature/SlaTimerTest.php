<?php

namespace Tests\Feature;

use App\Console\Commands\CheckSlaTimers;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlaTimerTest extends TestCase
{
    use RefreshDatabase;

    private function makeOpenConversation(array $attrs = []): Conversation
    {
        $user   = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $agent  = Agent::factory()->create(['user_id' => $user->id]);

        return Conversation::factory()->create(array_merge([
            'user_id'              => $user->id,
            'device_id'            => $device->id,
            'assigned_agent_id'    => $agent->id,
            'conversation_status'  => 'open',
            'sla_breached'         => false,
            'first_response_at'    => null,
            'resolved_at'          => null,
            'assigned_at'          => now()->subMinutes(16),
        ], $attrs));
    }

    public function test_sla_breach_detected_after_15_minutes(): void
    {
        Http::fake(); // prevent real HTTP calls to socket server

        $conv = $this->makeOpenConversation();

        $this->artisan('chat:check-sla')->assertSuccessful();

        $this->assertTrue($conv->fresh()->sla_breached, 'Conversation assigned 16 min ago with no response should be flagged');
    }

    public function test_sla_not_breached_within_15_minutes(): void
    {
        Http::fake();

        $conv = $this->makeOpenConversation(['assigned_at' => now()->subMinutes(10)]);

        $this->artisan('chat:check-sla')->assertSuccessful();

        $this->assertFalse($conv->fresh()->sla_breached, 'Conversation assigned 10 min ago should not be flagged yet');
    }

    public function test_sla_not_re_flagged_if_already_breached(): void
    {
        Http::fake();

        $conv = $this->makeOpenConversation(['sla_breached' => true]);

        // Call twice — should not error or double-flag
        $this->artisan('chat:check-sla')->assertSuccessful();
        $this->artisan('chat:check-sla')->assertSuccessful();

        $this->assertTrue($conv->fresh()->sla_breached);
    }

    public function test_resolved_conversation_not_checked(): void
    {
        Http::fake();

        $conv = $this->makeOpenConversation([
            'conversation_status' => 'resolved',
            'resolved_at'         => now(),
        ]);

        $this->artisan('chat:check-sla')->assertSuccessful();

        $this->assertFalse($conv->fresh()->sla_breached);
    }

    public function test_conversation_with_first_response_not_flagged(): void
    {
        Http::fake();

        $conv = $this->makeOpenConversation([
            'first_response_at' => now()->subMinutes(5),
        ]);

        $this->artisan('chat:check-sla')->assertSuccessful();

        $this->assertFalse($conv->fresh()->sla_breached, 'Conversation with a first response should not be breached');
    }
}
