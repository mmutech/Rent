<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'booking_id' => $this->faker->numberBetween(1, 10),
            'amount' => $this->faker->randomFloat(2, 100, 1000),
            'status' => $this->faker->randomElement(['Pending', 'Paid', 'Overdue']),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'invoice_date' => $this->faker->optional()->dateTimeBetween('+1 week', '+1 month'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
