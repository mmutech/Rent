<?php

namespace Database\Factories;

use App\Models\NextOfKin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NextOfKin>
 */
class NextOfKinFactory extends Factory
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
            'name' => $this->faker->name(),
            'relationship' => $this->faker->randomElement(['Spouse', 'Child', 'Parent', 'Sibling', 'Friend', 'Guardian', 'Other']),
            'phone_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'identification_number' => $this->faker->unique()->numerify('ID-########'),
            'identification_type' => $this->faker->randomElement(['National_ID', 'Passport', 'Driver_License', 'Other']),
            'is_verified' => $this->faker->boolean(50), // 50% chance of being verified
        ];
    }
}
