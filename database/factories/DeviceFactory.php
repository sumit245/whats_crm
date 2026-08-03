<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'        => \App\Models\User::factory(),
            'body'           => $this->faker->phoneNumber(),
            'status'         => 'Connected',
            'phone_number_id'=> (string) $this->faker->numerify('##########'),
            'waba_id'        => (string) $this->faker->numerify('##########'),
            'meta_profile'   => null,
        ];
    }
}
