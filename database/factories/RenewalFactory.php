<?php

namespace Database\Factories;

use App\Models\Renewal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Renewal>
 */
class RenewalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'renewal_number' => Renewal::generateRenewalNumber(),
            'booking_id' => $this->faker->numberBetween(1, 10),
            'user_id' => $this->faker->numberBetween(1, 10),
            'old_end_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'new_end_date' => $this->faker->dateTimeBetween('+3 months', '+6 months'),
            'new_rent_amount' => $this->faker->randomFloat(2, 100, 1000),
            'status' => $this->faker->randomElement(['Pending', 'Approved', 'Rejected']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
