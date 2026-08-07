<?php

namespace Database\Seeders;

use App\Models\Residence;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
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

        $juanResidence = $juan->residences()->first() ?: Residence::first();

        if (!$juanResidence) {
            $this->command->error("No se encontró ninguna residencia para el usuario 'juan'. Ejecuta ResidenceSeeder primero.");
            return;
        }

        // Vehículos del residente Juan (colores en formato Hexadecimal, evitando el blanco)
        $juanVehicles = [
            [
                'plate' => 'ABC-1234',
                'brand' => 'Toyota Corolla',
                'color' => '#808080', // Gris
            ],
            [
                'plate' => 'XYZ-9876',
                'brand' => 'Honda CR-V',
                'color' => '#000000', // Negro
            ],
            [
                'plate' => 'JRN-5544',
                'brand' => 'Mazda 3',
                'color' => '#E53E3E', // Rojo
            ],
        ];

        foreach ($juanVehicles as $vData) {
            Vehicle::updateOrCreate(
                ['plate' => $vData['plate']],
                [
                    'residence_id' => $juanResidence->id,
                    'brand' => $vData['brand'],
                    'color' => $vData['color'],
                ]
            );
        }

        // Vehículos para otras residencias
        $otherResidences = Residence::where('id', '!=', $juanResidence->id)->get();
        $colors = ['#1E90FF', '#38A169', '#D69E2E', '#800080']; // Azul, Verde, Dorado, Púrpura
        $i = 0;

        foreach ($otherResidences as $residence) {
            Vehicle::updateOrCreate(
                ['plate' => 'OTH-' . (1000 + $residence->id)],
                [
                    'residence_id' => $residence->id,
                    'brand' => 'Nissan Versa',
                    'color' => $colors[$i % count($colors)],
                ]
            );
            $i++;
        }

        $this->command->info('VehicleSeeder ejecutado correctamente con colores hexadecimales.');
    }
}
