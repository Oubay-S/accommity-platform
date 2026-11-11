<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Utilisateurs\User;
use App\Enums\RoleUtilisateur;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@accommity.ma'],
            [
                'nom' => 'Admin',
                'prenom' => 'Accommity',
                'password' => bcrypt('Admin1234!'),
                'role' => RoleUtilisateur::ADMIN,
                'is_actif' => true,
            ]
        );
    }
}
