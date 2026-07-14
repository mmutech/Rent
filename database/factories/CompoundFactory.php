<?php

namespace Database\Factories;

use App\Models\Compound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Compound>
 */
class CompoundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(2),
            'description' => $this->faker->paragraph(),
            'address' => $this->faker->streetAddress(),
            'fence_walled' => $this->faker->boolean(),
            'gated' => $this->faker->boolean(),
            'security_guard' => $this->faker->boolean(),
            'cctv' => $this->faker->boolean(),
            'street_lights' => $this->faker->boolean(),
            'playground' => $this->faker->boolean(),
            'total_properties' => $this->faker->numberBetween(1, 1000),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'google_map_url' => $this->faker->url(),
            'landmark' => $this->faker->word(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip_code' => $this->faker->postcode(),
            'created_by' => $this->faker->numberBetween(1, 10),
            'updated_by' => $this->faker->numberBetween(1, 10),
        ];
    }
}
