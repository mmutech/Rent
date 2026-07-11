<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_number' => MaintenanceRequest::generateRequestNumber(),
            'property_id' => $this->faker->numberBetween(1, 10),
            'user_id' => $this->faker->numberBetween(1, 10),
            'request_type' => $this->faker->randomElement(['Plumbing', 'Electrical', 'General', 'Other']),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['Pending', 'In Progress', 'Completed']),
            'priority' => $this->faker->randomElement(['Low', 'Medium', 'High']),
            'request_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'completion_date' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
