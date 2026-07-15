<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Residence;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResidenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residentRole = Role::where('name', 'resident')->first();

        if (!$residentRole) {
            $this->command->error("El rol 'resident' no existe. Ejecuta RoleSeeder primero.");
            return;
        }

        $residents = User::where('role_id', $residentRole->id)->get();

        // Crear una dirección de prueba para asignar a las residencias
        $address = Address::create([
            'name' => 'Calle Principal',
            'cp' => '76000',
        ]);

        $blockCounter = 1;
        $numberCounter = 1;

        foreach ($residents as $resident) {
            // Verificar si el usuario ya tiene una residencia asignada
            if ($resident->residences()->exists()) {
                $this->command->info("El residente '{$resident->username}' ya tiene una residencia. Saltando.");
                continue;
            }

            // Crear una nueva residencia ficticia
            $residence = Residence::create([
                'address_id' => $address->id,
                'block' => $blockCounter,
                'number' => str_pad((string)$numberCounter, 3, '0', STR_PAD_LEFT),
                'intercom_number' => "{$blockCounter}{$numberCounter}",
            ]);

            // Asignar la residencia al usuario con is_primary_owner = true
            $resident->residences()->attach($residence->id, [
                'is_primary_owner' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("Residencia bloque {$blockCounter}, número {$numberCounter} creada y asignada a '{$resident->username}'.");

            $numberCounter++;
            if ($numberCounter > 10) {
                $numberCounter = 1;
                $blockCounter++;
            }
        }
    }
}
