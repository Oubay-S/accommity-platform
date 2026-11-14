<?php

namespace Database\Factories\Utilisateurs;

use App\Models\Administration\Administrateur;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Par défaut : un voyageur actif.
     *
     * Le schéma impose `CHECK (est_hote OR est_voyageur)` : un utilisateur
     * ne peut donc jamais avoir les deux drapeaux à false.
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mot_de_passe' => 'Password123!',
            'telephone' => $this->faker->numerify('06########'),
            'est_hote' => false,
            'est_voyageur' => true,
            'est_actif' => true,
        ];
    }

    public function voyageur(): static
    {
        return $this->state(fn () => [
            'est_hote' => false,
            'est_voyageur' => true,
        ]);
    }

    public function hote(): static
    {
        return $this->state(fn () => [
            'est_hote' => true,
            'est_voyageur' => false,
        ]);
    }

    /** Hôte ET voyageur (RG02). */
    public function hoteEtVoyageur(): static
    {
        return $this->state(fn () => [
            'est_hote' => true,
            'est_voyageur' => true,
        ]);
    }

    public function inactif(): static
    {
        return $this->state(fn () => ['est_actif' => false]);
    }

    /** Crée la ligne `administrateurs` associée (back-office, RO09). */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            Administrateur::factory()->create([
                'id_utilisateur' => $user->getKey(),
            ]);
        });
    }
}
