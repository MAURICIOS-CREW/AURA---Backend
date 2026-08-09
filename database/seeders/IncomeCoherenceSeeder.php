<?php

namespace Database\Seeders;

use App\Models\FinancialCharge;
use App\Models\Payment;
use App\Models\Residence;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder aditivo (no destructivo) que garantiza coherencia entre financial_charges
 * y payments: todo cargo 'paid' debe tener un pago 'approved' asociado, y agrega
 * historial de cuotas de mantenimiento mensual ($200, precio real del servicio
 * recurrente "Mantenimiento Mensual") para varios residentes.
 */
class IncomeCoherenceSeeder extends Seeder
{
    private const MONTHLY_FEE = 200.00;

    public function run(): void
    {
        $this->createExtraResidents();

        // Reutiliza ResidenceSeeder: solo crea residencia para residentes que aún no tengan una.
        $this->call(ResidenceSeeder::class);

        $this->backfillMissingPayments();
        $this->seedMonthlyQuotaHistory();
    }

    /**
     * Crea residentes adicionales para tener una base más amplia de cuotas
     * de mantenimiento y así respaldar los egresos registrados.
     */
    private function createExtraResidents(): void
    {
        $residentRole = Role::where('name', 'resident')->first();

        if (!$residentRole) {
            $this->command->error("El rol 'resident' no existe. Ejecuta RoleSeeder primero.");
            return;
        }

        $extraResidents = [
            ['name' => 'María López', 'username' => 'maria_lopez', 'email' => 'maria.lopez@example.com', 'phone' => '4421230001'],
            ['name' => 'Roberto Sánchez', 'username' => 'roberto_sanchez', 'email' => 'roberto.sanchez@example.com', 'phone' => '4421230002'],
            ['name' => 'Ana Torres', 'username' => 'ana_torres', 'email' => 'ana.torres@example.com', 'phone' => '4421230003'],
            ['name' => 'Diego Ramírez', 'username' => 'diego_ramirez', 'email' => 'diego.ramirez@example.com', 'phone' => '4421230004'],
            ['name' => 'Lucía Fernández', 'username' => 'lucia_fernandez', 'email' => 'lucia.fernandez@example.com', 'phone' => '4421230005'],
            ['name' => 'Pedro Castillo', 'username' => 'pedro_castillo', 'email' => 'pedro.castillo@example.com', 'phone' => '4421230006'],
        ];

        foreach ($extraResidents as $data) {
            $existing = User::where('email', $data['email'])->orWhere('username', $data['username'])->first();

            if ($existing) {
                continue;
            }

            User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'phone' => $data['phone'],
                'role_id' => $residentRole->id,
            ]);

            $this->command->info("Residente adicional '{$data['username']}' creado.");
        }
    }

    /**
     * Crea un payment 'approved' para cada financial_charge 'paid' que aún no tenga uno,
     * usando el residente contratante del servicio o el propietario principal de la residencia.
     */
    private function backfillMissingPayments(): void
    {
        $adminId = User::where('username', 'admin_dev')->value('id')
            ?? User::whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'superadmin']))->value('id');

        $chargesWithoutPayment = FinancialCharge::where('status', 'paid')
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'approved'))
            ->with('contractedService')
            ->get();

        foreach ($chargesWithoutPayment as $charge) {
            $payerId = $charge->contractedService?->user_id;
            $paymentMethod = $charge->contractedService?->payment_method ?: 'transfer';

            if (!$payerId) {
                $residence = Residence::find($charge->residence_id);
                $owner = $residence?->users()->wherePivot('is_primary_owner', true)->first()
                    ?? $residence?->users()->first();
                $payerId = $owner?->id;
            }

            if (!$payerId) {
                continue;
            }

            $paidAt = $charge->updated_at ?? $charge->created_at ?? Carbon::now();

            $payment = Payment::create([
                'charge_id' => $charge->id,
                'user_id' => $payerId,
                'amount' => $charge->amount,
                'payment_method' => $paymentMethod,
                'validator_admin_id' => $paymentMethod === 'stripe' ? null : $adminId,
                'status' => 'approved',
            ]);

            $payment->created_at = $paidAt;
            $payment->updated_at = $paidAt;
            $payment->saveQuietly();
        }

        $this->command->info("Backfill de pagos completado: {$chargesWithoutPayment->count()} cargo(s) 'paid' ahora tienen su payment 'approved'.");
    }

    /**
     * Genera historial de 12 meses de cuota de mantenimiento ($200) por residencia,
     * evitando meses que ya tengan un cargo registrado (p. ej. los de FinancialChargeSeeder).
     */
    private function seedMonthlyQuotaHistory(): void
    {
        $residences = Residence::with('users')->get();
        $now = Carbon::now()->startOfMonth();
        $created = 0;

        foreach ($residences as $residence) {
            $owner = $residence->users->firstWhere('pivot.is_primary_owner', true) ?? $residence->users->first();

            if (!$owner) {
                continue;
            }

            // Determina el primer mes (hacia atrás) libre de cargos existentes para esta residencia.
            $existingMonths = FinancialCharge::where('residence_id', $residence->id)
                ->get(['month', 'year'])
                ->map(fn ($c) => "{$c->year}-{$c->month}")
                ->flip();

            $monthsToCreate = [];
            $cursor = $now->copy()->subMonth();

            while (count($monthsToCreate) < 12) {
                $key = "{$cursor->year}-{$cursor->month}";

                if (!isset($existingMonths[$key])) {
                    $monthsToCreate[] = $cursor->copy();
                }

                $cursor->subMonth();
            }

            foreach ($monthsToCreate as $monthDate) {
                $charge = FinancialCharge::updateOrCreate(
                    [
                        'residence_id' => $residence->id,
                        'month' => $monthDate->month,
                        'year' => $monthDate->year,
                    ],
                    [
                        'amount' => self::MONTHLY_FEE,
                        'status' => 'paid',
                    ]
                );

                $paidAt = $monthDate->copy()->addDays(4);
                $charge->created_at = $monthDate;
                $charge->updated_at = $paidAt;
                $charge->saveQuietly();

                $payment = Payment::updateOrCreate(
                    [
                        'charge_id' => $charge->id,
                        'user_id' => $owner->id,
                    ],
                    [
                        'amount' => self::MONTHLY_FEE,
                        'payment_method' => 'transfer',
                        'status' => 'approved',
                    ]
                );

                $payment->created_at = $paidAt;
                $payment->updated_at = $paidAt;
                $payment->saveQuietly();

                $created++;
            }
        }

        $this->command->info("Historial de cuotas de mantenimiento generado: {$created} cargo(s) mensuales de \$200 con su pago aprobado.");
    }
}
