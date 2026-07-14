<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'compound_id' => $this->faker->numberBetween(1, 5),
            'category_id' => $this->faker->numberBetween(1, 5),
            'title' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, 100, 1000),
            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->numberBetween(1, 3),
            'kitchens' => $this->faker->numberBetween(1, 2),
            'living_rooms' => $this->faker->numberBetween(1, 2),
            'parking_spaces' => $this->faker->numberBetween(0, 2),
            'status' => $this->faker->randomElement(['Available', 'Reserved', 'Occupied', 'Under_Maintenance']),
            'created_by' => $this->faker->numberBetween(1, 10),
        ];
    }
}
