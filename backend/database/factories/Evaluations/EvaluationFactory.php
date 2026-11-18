<?php

namespace Database\Factories\Evaluations;

use App\Models\Evaluations\Evaluation;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    public function definition(): array
    {
        return [
            'id_reservation' => Reservation::factory(),
            'id_auteur' => User::factory()->voyageur(),
            'id_cible' => User::factory()->hote(),
            'id_annonce' => null,
            'type_auteur' => 'voyageur',
            'note' => 5,
            'commentaire' => $this->faker->paragraph(),
            'est_signale' => false,
            'motif_signalement' => null,
        ];
    }

    public function voyageur(): static
    {
        return $this->state(fn () => ['type_auteur' => 'voyageur']);
    }

    public function hote(): static
    {
        return $this->state(fn () => ['type_auteur' => 'hote']);
    }

    public function signalee(): static
    {
        return $this->state(fn () => [
            'est_signale' => true,
            'motif_signalement' => 'Contenu inapproprié.',
        ]);
    }
}
