<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'             => \App\Models\User::factory(),
            'device_id'           => \App\Models\Device::factory(),
            'contact_number'      => $this->faker->numerify('601########'),
            'contact_name'        => $this->faker->name(),
            'last_message'        => $this->faker->sentence(),
            'last_message_at'     => now(),
            'unread_count'        => 0,
            'fallback_count'      => 0,
            'conversation_status' => 'open',
            'sla_breached'        => false,
            'assigned_agent_id'   => null,
            'assignment_source'   => null,
            'assigned_at'         => null,
            'first_response_at'   => null,
            'resolved_at'         => null,
        ];
    }
}
