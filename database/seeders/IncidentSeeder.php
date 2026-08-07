<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
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

        $incidentsData = [
            [
                'title' => 'Fuga de agua en área de riego',
                'description' => 'Hay una fuga constante en el tubo principal de riego cerca de la palapa principal.',
                'status' => 'open',
                'comments' => [
                    [
                        'user_id' => $juan->id,
                        'content' => 'Noté que el pasto está completamente anegado desde la mañana.',
                    ],
                ],
            ],
            [
                'title' => 'Luz de pasillo fundida en torre A',
                'description' => 'La lámpara del pasillo exterior del departamento 101 no enciende por las noches.',
                'status' => 'viewed',
                'comments' => [
                    [
                        'user_id' => $juan->id,
                        'content' => 'Lleva 2 días apagada por las noches, dificulta la visibilidad.',
                    ],
                    [
                        'user_id' => $admin?->id ?: $juan->id,
                        'content' => 'Reporte revisado por administración. Se notificó al electricista de turno.',
                    ],
                ],
            ],
            [
                'title' => 'Falla en motor de portón vehicular principal',
                'description' => 'El portón izquierdo se atora a la mitad al abrir con control remoto.',
                'status' => 'in_progress',
                'comments' => [
                    [
                        'user_id' => $juan->id,
                        'content' => 'Genera demora al intentar ingresar en horas pico.',
                    ],
                    [
                        'user_id' => $admin?->id ?: $juan->id,
                        'content' => 'El equipo técnico de automatización ya se encuentra en caseta reemplazando el sensor.',
                    ],
                    [
                        'user_id' => $juan->id,
                        'content' => 'Enterado, muchas gracias por la atención.',
                    ],
                ],
            ],
            [
                'title' => 'Ruido en elevador al subir',
                'description' => 'Se percibe un vibrado y rechinido fuerte al elevarse entre el piso 1 y 3.',
                'status' => 'attended',
                'comments' => [
                    [
                        'user_id' => $juan->id,
                        'content' => 'Solicito revisión preventiva por seguridad de los residentes.',
                    ],
                    [
                        'user_id' => $admin?->id ?: $juan->id,
                        'content' => 'Mantenimiento preventivo completado. Se realizó ajuste de rieles y lubricación.',
                    ],
                    [
                        'user_id' => $juan->id,
                        'content' => 'Confirmado, el elevador opera nuevamente sin ruidos. Gracias.',
                    ],
                ],
            ],
            [
                'title' => 'Música con volumen alto en terraza',
                'description' => 'Reporte por ruido de reunión pasadas las 23:00 horas.',
                'status' => 'cancelled',
                'comments' => [
                    [
                        'user_id' => $juan->id,
                        'content' => 'Favor de cancelar la solicitud, se aclaró directamente entre vecinos y bajaron la música.',
                    ],
                    [
                        'user_id' => $admin?->id ?: $juan->id,
                        'content' => 'Entendido Juan, se procede con el cierre y cancelación del folio.',
                    ],
                ],
            ],
        ];

        foreach ($incidentsData as $data) {
            $incident = Incident::updateOrCreate(
                [
                    'reporter_user_id' => $juan->id,
                    'title' => $data['title'],
                ],
                [
                    'description' => $data['description'],
                    'status' => $data['status'],
                ]
            );

            // Recrear o asegurar comentarios
            foreach ($data['comments'] as $cData) {
                IncidentComment::firstOrCreate([
                    'incident_id' => $incident->id,
                    'user_id' => $cData['user_id'],
                    'content' => $cData['content'],
                ]);
            }
        }

        $this->command->info('IncidentSeeder ejecutado correctamente cubriendo todos los estados (open, viewed, in_progress, attended, cancelled) con diálogos entre Juan y la administración.');
    }
}
