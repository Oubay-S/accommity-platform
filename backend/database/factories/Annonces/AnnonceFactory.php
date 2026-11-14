<?php

namespace Database\Factories\Annonces;

use App\Enums\ModeReservation;
use App\Enums\StatutAnnonce;
use App\Models\Annonces\Annonce;
use App\Models\Annonces\CategorieGeographique;
use App\Models\Annonces\PolitiqueAnnulation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Annonce>
 */
class AnnonceFactory extends Factory
{
    protected $model = Annonce::class;

    public function definition(): array
    {
        return [
            'id_hote' => User::factory()->hote(),
            'id_categorie' => CategorieGeographique::factory(),
            'id_politique' => PolitiqueAnnulation::factory(),
            'titre' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            // Cast `array` côté modèle : la colonne texte stocke un tableau JSON.
            'photo_url' => ['https://picsum.photos/seed/'.$this->faker->word().'/800/600'],
            'type_logement' => 'appartement',
            'adresse' => $this->faker->address(),
            'capacite' => 4,
            'tarif_nuit' => 500,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'statut' => StatutAnnonce::PUBLIEE,
            'equipements' => 'wifi, cuisine, parking',
            'reglement_interieur' => null,
        ];
    }

    public function suspendue(): static
    {
        return $this->state(fn () => ['statut' => StatutAnnonce::SUSPENDUE]);
    }
}
