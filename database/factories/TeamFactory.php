<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => \App\Models\User::factory(),
            'name'          => $this->faker->word() . ' Team',
            'routing_rules' => null,
        ];
    }
}
