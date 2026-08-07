<?php

namespace Database\Seeders;

use App\Models\FinancialCharge;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
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

        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::where('role_id', $adminRole?->id)->first() ?: User::where('username', 'admin_dev')->first();

        $residence = $juan->residences()->first();

        if (!$residence) {
            $this->command->error("No se encontró residencia para 'juan'.");
            return;
        }

        // Cargos de Juan
        $paidCharge = FinancialCharge::where('residence_id', $residence->id)
            ->where('status', 'paid')
            ->first();

        if ($paidCharge) {
            Payment::updateOrCreate(
                [
                    'charge_id' => $paidCharge->id,
                    'user_id' => $juan->id,
                ],
                [
                    'amount' => $paidCharge->amount,
                    'payment_method' => 'transfer',
                    'receipt' => 'receipts/comprobante_pagado.pdf',
                    'validator_admin_id' => $admin?->id,
                    'status' => 'approved',
                ]
            );
        }

        $pendingCharge = FinancialCharge::where('residence_id', $residence->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingCharge) {
            Payment::updateOrCreate(
                [
                    'charge_id' => $pendingCharge->id,
                    'user_id' => $juan->id,
                ],
                [
                    'amount' => $pendingCharge->amount,
                    'payment_method' => 'stripe',
                    'receipt' => 'receipts/comprobante_revision.pdf',
                    'validator_admin_id' => null,
                    'status' => 'pending',
                ]
            );
        }

        $this->command->info('PaymentSeeder ejecutado correctamente con pagos aprobados y pendientes de revisión.');
    }
}
