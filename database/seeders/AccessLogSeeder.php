<?php

namespace Database\Seeders;

use App\Models\AccessCode;
use App\Models\AccessLog;
use App\Models\Residence;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccessLogSeeder extends Seeder
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

        $guardRole = Role::where('name', 'guard')->first();
        $guardUser = User::where('role_id', $guardRole?->id)->first() ?: User::where('username', 'guard_dev')->first();

        $residence = $juan->residences()->first() ?: Residence::first();

        if (!$residence) {
            $this->command->error("No se encontró residencia para 'juan'.");
            return;
        }

        $vehicle = Vehicle::where('residence_id', $residence->id)->first();
        $qrCode = AccessCode::where('user_id', $juan->id)->where('type', 'multiple_use')->first();
        $expiredQrCode = AccessCode::where('user_id', $juan->id)->where('guest_name', 'like', '%Expirado%')->first();

        $now = Carbon::now();

        $logs = [
            [
                'access_type' => 'entry',
                'method' => 'license_plate',
                'residence_id' => $residence->id,
                'vehicle_id' => $vehicle?->id,
                'access_code_id' => null,
                'guard_user_id' => null,
                'status' => 'granted',
                'ai_confidence' => 0.98,
                'timestamp' => $now->copy()->subHours(2),
                'message' => 'Acceso automático concedido por lectura de placa ' . ($vehicle?->plate ?? 'ABC-1234'),
            ],
            [
                'access_type' => 'entry',
                'method' => 'qr',
                'residence_id' => $residence->id,
                'vehicle_id' => null,
                'access_code_id' => $qrCode?->id,
                'guard_user_id' => null,
                'status' => 'granted',
                'ai_confidence' => null,
                'timestamp' => $now->copy()->subHours(5),
                'message' => 'Acceso verificado correctamente vía Código QR ' . ($qrCode?->guest_name ?? ''),
            ],
            [
                'access_type' => 'entry',
                'method' => 'qr',
                'residence_id' => $residence->id,
                'vehicle_id' => null,
                'access_code_id' => $expiredQrCode?->id,
                'guard_user_id' => $guardUser?->id,
                'status' => 'denied',
                'ai_confidence' => null,
                'timestamp' => $now->copy()->subDays(1),
                'message' => 'Acceso denegado: Código QR expirado o fuera de vigencia.',
            ],
            [
                'access_type' => 'exit',
                'method' => 'manual_guard',
                'residence_id' => $residence->id,
                'vehicle_id' => $vehicle?->id,
                'access_code_id' => null,
                'guard_user_id' => $guardUser?->id,
                'status' => 'granted',
                'ai_confidence' => null,
                'timestamp' => $now->copy()->subDays(2),
                'message' => 'Salida registrada manualmente por caseta de guardia.',
            ],
        ];

        foreach ($logs as $logData) {
            AccessLog::create($logData);
        }

        $this->command->info('AccessLogSeeder ejecutado correctamente con historial de accesos por placa, QR y caseta.');
    }
}
