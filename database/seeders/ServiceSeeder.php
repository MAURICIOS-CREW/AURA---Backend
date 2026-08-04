<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'title' => 'Jardinería y Poda de Césped',
                'description' => 'Servicio completo de corte de pasto, nivelación de jardín, corte de maleza y retiro de residuos verdes.',
                'price' => 250.00,
                'is_active' => true,
                'images' => [
                    'services/jardineria_1.jpg',
                    'services/jardineria_2.jpg',
                ],
            ],
            [
                'title' => 'Mantenimiento de Aire Acondicionado',
                'description' => 'Revisión general, limpieza de filtros, desinfección de serpentín y recarga de gas refrigerante.',
                'price' => 450.00,
                'is_active' => true,
                'images' => [
                    'services/aire_acondicionado.jpg',
                ],
            ],
            [
                'title' => 'Limpieza Profunda de Cisterna y Aljibe',
                'description' => 'Vaciado, cepillado de paredes, desinfección con cloro de grado alimenticio y extracción de lodos.',
                'price' => 600.00,
                'is_active' => true,
                'images' => [
                    'services/cisterna.jpg',
                ],
            ],
            [
                'title' => 'Lavado de Alfombras y Muebles',
                'description' => 'Limpieza a vapor con inyección y succión profunda para eliminación de manchas y ácaros.',
                'price' => 350.00,
                'is_active' => true,
                'images' => [
                    'services/muebles.jpg',
                ],
            ],
            [
                'title' => 'Plomería y Reparación de Fugas',
                'description' => 'Servicio técnico especializado para reparación de tuberías, cambio de llaves, sanitarios y tinacos.',
                'price' => 300.00,
                'is_active' => true,
                'images' => [
                    'services/plomeria.jpg',
                ],
            ],
            [
                'title' => 'Pintura y Retoque de Fachada',
                'description' => 'Resane de grietas superficiales y aplicación de pintura vinílica impermeabilizante exterior.',
                'price' => 1200.00,
                'is_active' => true,
                'images' => [
                    'services/pintura.jpg',
                ],
            ],
        ];

        foreach ($services as $serviceData) {
            Service::updateOrCreate(
                ['title' => $serviceData['title']],
                $serviceData
            );
        }

        $this->command->info('Seeder de Servicios ejecutado correctamente con 6 servicios del catálogo.');
    }
}
