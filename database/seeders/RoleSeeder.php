<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'superadmin', 'hierarchy_level' => 0],
            ['name' => 'admin', 'hierarchy_level' => 1],
            ['name' => 'resident', 'hierarchy_level' => 2],
            ['name' => 'guard', 'hierarchy_level' => 3],
        ];

        foreach ($roles as $roleData) {
            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                ['hierarchy_level' => $roleData['hierarchy_level']]
            );

            if ($role->wasRecentlyCreated) {
                $this->command->info("Rol '{$roleData['name']}' creado exitosamente.");
            } else {
                $this->command->info("Rol '{$roleData['name']}' actualizado/verificado exitosamente.");
            }
        }
    }
}
