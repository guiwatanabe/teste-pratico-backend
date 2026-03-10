<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => \App\Models\Client::factory(),
            'gateway_id' => \App\Models\Gateway::factory(),
            'external_id' => $this->faker->unique()->uuid(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'amount' => $this->faker->numberBetween(100, 10000),
            'card_last_numbers' => $this->faker->numberBetween(1000, 9999),
        ];
    }
}
