<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->admin()->create();

        User::factory()->landlord()->count(5)->create();

        User::factory()->tenant()->count(20)->create();

        User::factory()->agent()->count(5)->create();
        
    }
}
