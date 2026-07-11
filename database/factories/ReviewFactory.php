<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
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
            'property_id' => $this->faker->numberBetween(1, 10),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional()->paragraph(),
        ];
    }
}
