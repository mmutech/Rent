<?php

namespace Database\Factories;

use App\Models\PropertyImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyImage>
 */
class PropertyImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => $this->faker->numberBetween(1, 10),
            'image_path' => $this->faker->imageUrl(640, 480, 'real-estate'),
            'is_primary' => $this->faker->boolean(20),
        ];
    }
}
