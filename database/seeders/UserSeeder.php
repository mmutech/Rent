<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Muhammad Usman',
            'email' => 'admin@rent.com',
            'nin' => '12345678901',
            'address' => 'Test Address',
            'phone' => '00000000000',
            'password' => Hash::make('password')
        ]);
        $admin->assignRole('Admin');

        User::factory()->admin()->create();

        User::factory()->landlord()->count(5)->create();

        User::factory()->tenant()->count(20)->create();

        User::factory()->agent()->count(10)->create();
        
    }
}
