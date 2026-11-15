<?php

namespace Database\Factories\Annonces;

use App\Models\Annonces\CategorieGeographique;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategorieGeographique>
 */
class CategorieGeographiqueFactory extends Factory
{
    protected $model = CategorieGeographique::class;

    public function definition(): array
    {
        return [
            'ville' => $this->faker->city(),
            'region' => $this->faker->randomElement([
                'Tanger-Tétouan-Al Hoceïma',
                'Marrakech-Safi',
                'Fès-Meknès',
                'Casablanca-Settat',
            ]),
            'pays' => 'Maroc',
        ];
    }
}
