<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NextOfKin;

class NextOfKinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NextOfKin::factory()
            ->count(10)
            ->create();
    }
}
