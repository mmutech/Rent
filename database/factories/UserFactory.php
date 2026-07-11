<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $isVerified = fake()->boolean(70);

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),

            // Nigerian phone number
            'phone' => fake()->unique()->regexify('0(70|80|81|90|91)[0-9]{8}'),

            'address' => fake()->address(),

            // Nigerian National Identification Number (11 digits)
            'nin' => fake()->unique()->numerify('###########'),

            'is_verified' => $isVerified,
            'is_active' => true,

            'email_verified_at' => $isVerified ? now() : null,

            'password' => static::$password ??= Hash::make('password'),

            'remember_token' => Str::random(10),

            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the user's email address is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'is_verified' => false,
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is verified.
     */
    public function verified(): static
    {
        return $this->state(fn () => [
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user has two-factor authentication enabled.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'recovery-code-1',
                'recovery-code-2',
            ])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Admin');
        });
    }

    public function landlord(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Landlord');
        });
    }

    public function tenant(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Tenant');
        });
    }

    public function agent(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Agent');
        });
    }
}