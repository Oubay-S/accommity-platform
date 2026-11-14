<?php

namespace Database\Factories\Utilisateurs;

use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationIdentite>
 */
class VerificationIdentiteFactory extends Factory
{
    protected $model = VerificationIdentite::class;

    public function definition(): array
    {
        return [
            'id_utilisateur' => UserFactory::new()->hote(),
            'type_piece' => 'cin',
            'statut' => VerificationIdentite::STATUT_EN_COURS,
            'motif_rejet' => null,
            'chemin_document' => 'verifications/'.$this->faker->uuid().'.jpg',
            'id_admin_traitant' => null,
            'date_traitement' => null,
        ];
    }

    public function enCours(): static
    {
        return $this->state(fn () => [
            'statut' => VerificationIdentite::STATUT_EN_COURS,
            'motif_rejet' => null,
            'date_traitement' => null,
        ]);
    }

    public function validee(): static
    {
        return $this->state(fn () => [
            'statut' => VerificationIdentite::STATUT_VALIDEE,
            'motif_rejet' => null,
            'date_traitement' => now(),
        ]);
    }

    public function rejetee(): static
    {
        return $this->state(fn () => [
            'statut' => VerificationIdentite::STATUT_REJETEE,
            'motif_rejet' => 'Document illisible.',
            'date_traitement' => now(),
        ]);
    }
}
