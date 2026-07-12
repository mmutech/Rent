<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 10),
            'unit_id' => $this->faker->numberBetween(1, 10),
            'start_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'status' => $this->faker->randomElement(['Pending', 'Confirmed', 'Cancelled']),
            'booking_reference' => Booking::generateBookingReference(),
            'total_price' => $this->faker->randomFloat(2, 100, 1000),
        ];
    }
}
