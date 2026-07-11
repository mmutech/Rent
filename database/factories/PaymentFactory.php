<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => Payment::generateReferenceNumber(),
            'booking_id' => $this->faker->numberBetween(1, 10),
            'user_id' => $this->faker->numberBetween(1, 10),
            'amount' => $this->faker->randomFloat(2, 100, 1000),
            'payment_method' => $this->faker->randomElement(['Cash', 'Card', 'Bank Transfer']),
            'status' => $this->faker->randomElement(['Pending', 'Completed', 'Failed']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
