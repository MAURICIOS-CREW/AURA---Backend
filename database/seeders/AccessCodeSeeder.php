<?php

namespace Database\Seeders;

use App\Models\AccessCode;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccessCodeSeeder extends Seeder
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
            $this->command->error("No se encontró residencia para 'juan'.");
            return;
        }

        // Limpiar códigos de acceso 'custom' existentes previamente para realizar el reemplazo completo
        AccessCode::withTrashed()->where('type', 'custom')->forceDelete();

        $now = Carbon::now();

        $qrCases = [
            [
                'guest_name' => 'Invitado Frecuente - Familia',
                'type' => 'custom',
                'valid_from' => null,
                'valid_until' => null,
                'uses' => 12,
                'max_uses' => null,
                'active_days' => null,
                'start_time' => null,
                'end_time' => null,
                'is_active' => true,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Trabajador Doméstico (L-M-V)',
                'type' => 'custom',
                'valid_from' => $now->copy()->subMonth(),
                'valid_until' => $now->copy()->addMonths(6),
                'uses' => 15,
                'max_uses' => null,
                'active_days' => [1, 3, 5], // 1=Lunes, 3=Miércoles, 5=Viernes
                'start_time' => '08:00',
                'end_time' => '17:00',
                'is_active' => true,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Tutor Particular (Tardes)',
                'type' => 'custom',
                'valid_from' => $now->copy()->subWeeks(2),
                'valid_until' => $now->copy()->addWeeks(4),
                'uses' => 4,
                'max_uses' => 20,
                'active_days' => [2, 4], // 2=Martes, 4=Jueves
                'start_time' => '14:00',
                'end_time' => '20:00',
                'is_active' => true,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Proveedor Paquete Express',
                'type' => 'custom',
                'valid_from' => $now->copy()->subDays(5),
                'valid_until' => $now->copy()->addDays(5),
                'uses' => 3,
                'max_uses' => 3,
                'active_days' => null,
                'start_time' => null,
                'end_time' => null,
                'is_active' => true,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Visita Pedro Gómez',
                'type' => 'custom',
                'valid_from' => $now->copy()->subHours(2),
                'valid_until' => $now->copy()->addHours(10),
                'uses' => 0,
                'max_uses' => 1,
                'active_days' => null,
                'start_time' => null,
                'end_time' => null,
                'is_active' => true,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Entrega de Muebles',
                'type' => 'custom',
                'valid_from' => $now->copy()->subDays(1),
                'valid_until' => $now->copy()->addDays(1),
                'uses' => 1,
                'max_uses' => 1,
                'active_days' => null,
                'start_time' => null,
                'end_time' => null,
                'is_active' => true,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Fiesta Cumpleaños del 15 de Julio',
                'type' => 'custom',
                'valid_from' => $now->copy()->subDays(10),
                'valid_until' => $now->copy()->subDays(3),
                'uses' => 6,
                'max_uses' => 10,
                'active_days' => null,
                'start_time' => null,
                'end_time' => null,
                'is_active' => true,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Reunión Familiar del 20 de Agosto',
                'type' => 'custom',
                'valid_from' => $now->copy()->addDays(5),
                'valid_until' => $now->copy()->addDays(12),
                'uses' => 0,
                'max_uses' => 50,
                'active_days' => null,
                'start_time' => null,
                'end_time' => null,
                'is_active' => true,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Técnico de Servicio de Internet',
                'type' => 'custom',
                'valid_from' => $now->copy()->subDays(2),
                'valid_until' => $now->copy()->addDays(5),
                'uses' => 1,
                'max_uses' => 5,
                'active_days' => null,
                'start_time' => null,
                'end_time' => null,
                'is_active' => false,
                'deleted_at' => null,
            ],
            [
                'guest_name' => 'Mantenimiento de Pintura',
                'type' => 'custom',
                'valid_from' => $now->copy()->subDays(3),
                'valid_until' => $now->copy()->addDays(3),
                'uses' => 0,
                'max_uses' => 1,
                'active_days' => null,
                'start_time' => null,
                'end_time' => null,
                'is_active' => false,
                'deleted_at' => $now->copy()->subDays(1),
            ],
        ];

        foreach ($qrCases as $case) {
            // Hash estándar idéntico a AccessCodeController::store
            $randomData = Str::random(40) . $juan->id . uniqid('', true) . $case['guest_name'];
            $codeHash = hash('sha256', $randomData);

            $accessCode = AccessCode::create([
                'residence_id' => $residence->id,
                'user_id' => $juan->id,
                'guest_name' => $case['guest_name'],
                'code' => $codeHash,
                'type' => $case['type'],
                'valid_from' => $case['valid_from'],
                'valid_until' => $case['valid_until'],
                'uses' => $case['uses'],
                'max_uses' => $case['max_uses'],
                'active_days' => $case['active_days'],
                'start_time' => $case['start_time'],
                'end_time' => $case['end_time'],
                'is_active' => $case['is_active'],
                'deleted_at' => $case['deleted_at'],
            ]);
        }

        $this->command->info('AccessCodeSeeder actualizado eliminando indicadores de estado del guest_name.');
    }
}
