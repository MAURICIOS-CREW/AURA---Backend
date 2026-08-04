<?php

namespace Database\Seeders;

use App\Models\AccessCode;
use App\Models\ContractedService;
use App\Models\FinancialCharge;
use App\Models\Residence;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContractedServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residentRole = Role::where('name', 'resident')->first();
        $residentUser = User::where('role_id', $residentRole?->id)->first();

        if (!$residentUser) {
            $this->command->error('No se encontró un usuario residente. Ejecuta DevUserSeeder y ResidenceSeeder primero.');
            return;
        }

        $residence = $residentUser->residences()->first();

        if (!$residence) {
            $residence = Residence::first();
        }

        if (!$residence) {
            $this->command->error('No se encontró ninguna residencia en la base de datos.');
            return;
        }

        $services = Service::all();
        if ($services->isEmpty()) {
            $this->command->error('No hay servicios disponibles. Ejecuta ServiceSeeder primero.');
            return;
        }

        $now = Carbon::now();

        // Mapeo de casos de prueba para cada uno de los estados del enum
        $testCases = [
            [
                'status' => 'created',
                'charge_status' => 'paid',
                'service_index' => 0,
                'preferred_date' => $now->copy()->addDays(2)->format('Y-m-d'),
                'visit_time_from' => '09:00',
                'visit_time_to' => '12:00',
                'exact_scheduled_at' => null,
                'notes' => 'El timbre no funciona bien, favor de llamar al llegar.',
                'has_qr' => false,
            ],
            [
                'status' => 'scheduled',
                'charge_status' => 'paid',
                'service_index' => 1,
                'preferred_date' => $now->copy()->addDays(1)->format('Y-m-d'),
                'visit_time_from' => '10:00',
                'visit_time_to' => '14:00',
                'exact_scheduled_at' => $now->copy()->addDays(1)->setTime(11, 0, 0),
                'notes' => 'Técnico asignado: Carlos Gómez (Servicio programado con QR).',
                'has_qr' => true,
            ],
            [
                'status' => 'in_progress',
                'charge_status' => 'paid',
                'service_index' => 2,
                'preferred_date' => $now->format('Y-m-d'),
                'visit_time_from' => '08:00',
                'visit_time_to' => '12:00',
                'exact_scheduled_at' => $now->copy()->setTime(8, 30, 0),
                'notes' => 'El personal ya se encuentra realizando la limpieza de cisterna.',
                'has_qr' => true,
            ],
            [
                'status' => 'completed',
                'charge_status' => 'paid',
                'service_index' => 3,
                'preferred_date' => $now->copy()->subDays(2)->format('Y-m-d'),
                'visit_time_from' => '14:00',
                'visit_time_to' => '17:00',
                'exact_scheduled_at' => $now->copy()->subDays(2)->setTime(14, 30, 0),
                'notes' => 'Servicio concluido satisfactoriamente por el residente.',
                'has_qr' => true,
            ],
            [
                'status' => 'refunded',
                'charge_status' => 'refunded',
                'service_index' => 4,
                'preferred_date' => $now->copy()->subDays(5)->format('Y-m-d'),
                'visit_time_from' => '11:00',
                'visit_time_to' => '13:00',
                'exact_scheduled_at' => null,
                'notes' => 'Cancelado a solicitud del cliente por falta de material. Reembolso acreditado.',
                'has_qr' => false,
            ],
            [
                'status' => 'cancelled',
                'charge_status' => 'cancelled',
                'service_index' => 5,
                'preferred_date' => $now->copy()->subDays(7)->format('Y-m-d'),
                'visit_time_from' => '15:00',
                'visit_time_to' => '18:00',
                'exact_scheduled_at' => null,
                'notes' => 'Cancelado por el usuario por inconveniente de horario.',
                'has_qr' => false,
            ],
        ];

        foreach ($testCases as $case) {
            $service = $services->get($case['service_index'] % $services->count());

            // 1. Crear el cargo financiero asociado
            $financialCharge = FinancialCharge::create([
                'residence_id' => $residence->id,
                'amount' => $service->price,
                'month' => $now->month,
                'year' => $now->year,
                'status' => $case['charge_status'],
            ]);

            // 2. Crear la contratación del servicio
            $contractedService = ContractedService::create([
                'service_id' => $service->id,
                'user_id' => $residentUser->id,
                'residence_id' => $residence->id,
                'charge_id' => $financialCharge->id,
                'preferred_date' => $case['preferred_date'],
                'visit_time_from' => $case['visit_time_from'],
                'visit_time_to' => $case['visit_time_to'],
                'exact_scheduled_at' => $case['exact_scheduled_at'],
                'amount' => $service->price,
                'status' => $case['status'],
                'notes' => $case['notes'],
                'payment_method' => 'stripe',
                'stripe_payment_id' => 'ch_' . Str::random(16),
            ]);

            // 3. Crear el código de acceso QR si aplica
            if ($case['has_qr'] && $case['exact_scheduled_at']) {
                $randomData = Str::random(40) . $residentUser->id . $contractedService->id . uniqid('qr_', true);
                $codeHash = hash('sha256', $randomData);

                $validFrom = $case['exact_scheduled_at']->copy()->subHour();
                $validUntil = $case['exact_scheduled_at']->copy()->addHours(6);

                AccessCode::create([
                    'residence_id' => $residence->id,
                    'contracted_service_id' => $contractedService->id,
                    'user_id' => $residentUser->id,
                    'guest_name' => 'Servicio: ' . $service->title,
                    'code' => $codeHash,
                    'type' => 'service',
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'uses' => 0,
                    'max_uses' => 10,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Seeder de Servicios Contratados ejecutado correctamente conteniendo todos los estados posibles (created, scheduled, in_progress, completed, refunded, cancelled) con sus cargos financieros y códigos QR.');
    }
}
