<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            CompoundSeeder::class,
            PropertySeeder::class,
            UnitSeeder::class,
            PropertyImageSeeder::class,
            NextOfKinSeeder::class,
            BookingSeeder::class,
            InvoiceSeeder::class,
            PaymentSeeder::class,
            MaintenanceSeeder::class,
            RenewalSeeder::class,
            ReviewSeeder::class,
        ]);

    }
}
