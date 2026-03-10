<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gateway>
 */
class GatewayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'driver' => $this->faker->randomElement(['gateway_1', 'gateway_2']),
            'is_active' => $this->faker->boolean(),
            'priority' => $this->faker->numberBetween(0, 10),
        ];
    }
}
