<?php

namespace Database\Seeders;

use App\Models\FinancialCharge;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FinancialChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $juan = User::where('username', 'juan')->first();

        if (!$juan) {
            $this->command->error("No se encontró el usuario 'juan'. Ejecuta DevUserSeeder primero.");
            return;
        }

        $residence = $juan->residences()->first() ?: Residence::first();

        if (!$residence) {
            $this->command->error("No se encontró residencia para 'juan'. Ejecuta ResidenceSeeder primero.");
            return;
        }

        $now = Carbon::now();

        $chargesData = [
            [
                'month' => $now->copy()->subMonths(4)->month,
                'year' => $now->copy()->subMonths(4)->year,
                'amount' => 1500.00,
                'status' => 'refunded',
            ],
            [
                'month' => $now->copy()->subMonths(3)->month,
                'year' => $now->copy()->subMonths(3)->year,
                'amount' => 1500.00,
                'status' => 'cancelled',
            ],
            [
                'month' => $now->copy()->subMonths(2)->month,
                'year' => $now->copy()->subMonths(2)->year,
                'amount' => 1500.00,
                'status' => 'paid',
            ],
            [
                'month' => $now->copy()->subMonth()->month,
                'year' => $now->copy()->subMonth()->year,
                'amount' => 1500.00,
                'status' => 'pending',
            ],
            [
                'month' => $now->month,
                'year' => $now->year,
                'amount' => 1500.00,
                'status' => 'pending',
            ],
        ];

        foreach ($chargesData as $data) {
            FinancialCharge::updateOrCreate(
                [
                    'residence_id' => $residence->id,
                    'month' => $data['month'],
                    'year' => $data['year'],
                ],
                [
                    'amount' => $data['amount'],
                    'status' => $data['status'],
                ]
            );
        }

        $this->command->info('FinancialChargeSeeder ejecutado correctamente con estados paid, pending, cancelled y refunded.');
    }
}
