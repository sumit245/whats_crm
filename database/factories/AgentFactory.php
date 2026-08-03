<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'              => \App\Models\User::factory(),
            'team_id'              => null,
            'name'                 => $this->faker->name(),
            'email'                => $this->faker->safeEmail(),
            'role'                 => 'agent',
            'status'               => 'online',
            'max_concurrent_chats' => 5,
            'active_chat_count'    => 0,
            'last_seen_at'         => now(),
        ];
    }
}
