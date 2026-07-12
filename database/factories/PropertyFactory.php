<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
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
            'name' => $this->faker->randomElement([
                'Plaza',
                'Studio Apartment',
                'Duplex',
                'Self Contain',
                'Flat',
                'Mini Flat',
                'Penthouse',
                'Bungalow',
                'Terrace House',
                'Semi-Detached Duplex',
                'Detached Duplex',
                'Mansion',
                'Villa',
                'Apartment',
                'Block of Flats',
                'Office Complex',
                'Shopping Complex',
                'Warehouse',
                'Hostel',
                'Guest House',
                'Serviced Apartment',
                'Commercial Building',
                'Mixed-Use Building',
            ]),
            'description' => $this->faker->sentence(),
            'created_by' => 1, // Assuming the admin user has ID 1
        ];
    }
}
