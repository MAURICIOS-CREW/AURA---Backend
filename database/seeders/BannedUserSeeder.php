<?php

namespace Database\Seeders;

use App\Models\BannedUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class BannedUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residentRole = Role::where('name', 'resident')->first();
        
        // Seleccionar un usuario residente secundario para marcarlo como sancionado
        $suspendedUser = User::where('username', 'resident_dev')->first();

        if ($suspendedUser) {
            BannedUser::updateOrCreate(
                ['user_id' => $suspendedUser->id],
                ['reason' => 'Suspensión temporal por incumplimiento reiterado al reglamento de áreas comunes.']
            );
            $this->command->info("Usuario 'resident_dev' añadido a la lista de usuarios sancionados/baneados.");
        }
    }
}
