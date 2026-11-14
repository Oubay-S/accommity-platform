<?php

namespace Database\Seeders;

use App\Models\Administration\Administrateur;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@accommity.ma';
        $motDePasse = 'Admin1234!';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'nom' => 'Admin',
                'prenom' => 'Accommity',
                'mot_de_passe' => $motDePasse,
                'est_hote' => false,
                'est_voyageur' => true,
                'est_actif' => true,
            ]
        );

        Administrateur::firstOrCreate(['id_utilisateur' => $user->getKey()]);
    }
}
