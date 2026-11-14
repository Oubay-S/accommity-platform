<?php

namespace Database\Factories\Administration;

use App\Models\Administration\Administrateur;
use Database\Factories\Utilisateurs\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Administrateur>
 */
class AdministrateurFactory extends Factory
{
    protected $model = Administrateur::class;

    /**
     * Depuis le schéma v1, les identifiants sont normalisés dans
     * `utilisateurs` : cette table ne porte plus email / mot_de_passe.
     */
    public function definition(): array
    {
        return [
            'id_utilisateur' => UserFactory::new(),
            'derniere_connexion' => now(),
        ];
    }
}
