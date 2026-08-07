<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener los roles correspondientes de la base de datos
        $superadminRole = Role::where('name', 'superadmin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $residentRole = Role::where('name', 'resident')->first();
        $guardRole = Role::where('name', 'guard')->first();

        // Configuración de los usuarios para desarrollo
        $superadminEmail = env('DEV_USER_EMAIL') ?: 'admin@example.com';
        $superadminUsername = env('DEV_USER_USERNAME') ?: 'admin';

        $devUsers = [
            [
                'name' => 'Juan Pérez',
                'username' => 'juan',
                'email' => 'juan@example.com',
                'password' => '12345',
                'phone' => '4421234567',
                'role_id' => $residentRole?->id,
            ],
            [
                'name' => env('DEV_USER_NAME', 'Super Admin Dev'),
                'username' => $superadminUsername,
                'email' => $superadminEmail,
                'password' => env('DEV_USER_PASSWORD', 'admin'),
                'phone' => env('DEV_USER_PHONE', '1234567890'),
                'role_id' => $superadminRole?->id,
            ],
            [
                'name' => 'Admin Dev',
                'username' => 'admin_dev',
                'email' => 'admin_dev@example.com',
                'password' => 'password',
                'phone' => '1234567891',
                'role_id' => $adminRole?->id,
            ],
            [
                'name' => 'Resident Dev',
                'username' => 'resident_dev',
                'email' => 'resident_dev@example.com',
                'password' => 'password',
                'phone' => '1234567892',
                'role_id' => $residentRole?->id,
            ],
            [
                'name' => 'Guard Dev',
                'username' => 'guard_dev',
                'email' => 'guard_dev@example.com',
                'password' => 'password',
                'phone' => '1234567893',
                'role_id' => $guardRole?->id,
            ],
        ];

        foreach ($devUsers as $userData) {
            // Verificar si el usuario ya existe por email o username para evitar duplicar
            $existingUser = User::where('email', $userData['email'])
                ->orWhere('username', $userData['username'])
                ->first();

            if ($existingUser) {
                // Si ya existe, nos aseguramos de que tenga el rol asignado correctamente si es necesario
                if ($userData['role_id'] && $existingUser->role_id !== $userData['role_id']) {
                    $existingUser->role_id = $userData['role_id'];
                    $existingUser->save();
                    $this->command->info("El usuario '{$userData['username']}' ya existía, pero se le actualizó el rol.");
                } else {
                    $this->command->info("El usuario '{$userData['username']}' ya existe. Saltando creación.");
                }
                continue;
            }

            $user = new User();
            $user->name = $userData['name'];
            $user->username = $userData['username'];
            $user->email = $userData['email'];
            $user->password = Hash::make($userData['password']);
            $user->phone = $userData['phone'];
            $user->role_id = $userData['role_id'];
            $user->save();

            $this->command->info("Usuario dev para el rol '{$userData['username']}' creado exitosamente.");
        }
    }
}

