<?php

namespace Database\Factories\Annonces;

use App\Models\Annonces\PolitiqueAnnulation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolitiqueAnnulation>
 */
class PolitiqueAnnulationFactory extends Factory
{
    protected $model = PolitiqueAnnulation::class;

    public function definition(): array
    {
        return [
            'type_politique' => $this->faker->randomElement(['flexible', 'moderee', 'stricte']),
            'delai_remb_total' => 24,
            'delai_remb_partiel' => 0,
            'taux_remb_partiel' => 0,
            'description' => 'Politique de test.',
        ];
    }
}
