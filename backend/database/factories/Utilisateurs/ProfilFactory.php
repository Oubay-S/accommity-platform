<?php

namespace Database\Factories\Utilisateurs;

use App\Models\Utilisateurs\Profil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profil>
 */
class ProfilFactory extends Factory
{
    protected $model = Profil::class;

    /**
     * `profils.id_utilisateur` est UNIQUE : un utilisateur = un profil.
     */
    public function definition(): array
    {
        return [
            'id_utilisateur' => UserFactory::new(),
            'photo_url' => null,
            'description' => $this->faker->sentence(12),
            'note_moyenne' => 0,
        ];
    }
}
