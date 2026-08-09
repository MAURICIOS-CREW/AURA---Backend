<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::where('role_id', $adminRole?->id)->first() ?: User::where('username', 'admin_dev')->first();

        if (!$admin) {
            $this->command->error("No se encontró un usuario administrador. Ejecuta DevUserSeeder primero.");
            return;
        }

        $expenses = [
            [
                'concept' => 'Mantenimiento de jardines',
                'description' => 'Poda y mantenimiento de áreas verdes comunes.',
                'category' => 'maintenance',
                'amount' => 3200.00,
                'expense_date' => now()->subDay()->format('Y-m-d'),
                'provider' => 'Jardinería El Sol',
                'payment_method' => 'transfer',
                'status' => 'paid',
            ],
            [
                'concept' => 'Reparación de portón principal',
                'description' => 'Cambio de motor y ajuste de brazo hidráulico.',
                'category' => 'maintenance',
                'amount' => 8500.00,
                'expense_date' => now()->subDays(25)->format('Y-m-d'),
                'provider' => 'Herrería Hnos.',
                'payment_method' => 'cash',
                'status' => 'pending',
            ],
            [
                'concept' => 'Pago de vigilancia',
                'description' => 'Servicio de vigilancia mensual.',
                'category' => 'security',
                'amount' => 18500.00,
                'expense_date' => now()->subDays(28)->format('Y-m-d'),
                'provider' => 'Seguridad Integral S.A.',
                'payment_method' => 'transfer',
                'status' => 'paid',
            ],
            [
                'concept' => 'Recibo de energía eléctrica de áreas comunes',
                'description' => 'Consumo del mes en curso.',
                'category' => 'utilities',
                'amount' => 4750.00,
                'expense_date' => now()->subDays(10)->format('Y-m-d'),
                'provider' => 'CFE',
                'payment_method' => 'transfer',
                'status' => 'paid',
            ],
            [
                'concept' => 'Compra de material de limpieza',
                'description' => 'Insumos para el personal de intendencia.',
                'category' => 'supplies',
                'amount' => 1150.00,
                'expense_date' => now()->subDays(5)->format('Y-m-d'),
                'provider' => 'Proveedora de Limpieza del Centro',
                'payment_method' => 'card',
                'status' => 'paid',
            ],
        ];

        foreach ($expenses as $expenseData) {
            Expense::updateOrCreate(
                [
                    'concept' => $expenseData['concept'],
                    'expense_date' => $expenseData['expense_date'],
                ],
                array_merge($expenseData, ['registered_by_id' => $admin->id])
            );
        }

        $this->command->info('ExpenseSeeder ejecutado correctamente con egresos de ejemplo.');
    }
}
