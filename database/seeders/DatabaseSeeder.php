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
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            RoleSeeder::class,
            DevUserSeeder::class,
            ResidenceSeeder::class,
            VehicleSeeder::class,
            ServiceSeeder::class,
            FinancialChargeSeeder::class,
            PaymentSeeder::class,
            ContractedServiceSeeder::class,
            AccessCodeSeeder::class,
            AccessLogSeeder::class,
            IncidentSeeder::class,
            BannedUserSeeder::class,
        ]);
    }
}
