<?php

namespace Database\Factories\Reservations;

use App\Enums\ModeReservation;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'id_annonce' => Annonce::factory(),
            'id_hote' => User::factory()->hote(),
            'id_voyageur' => User::factory()->voyageur(),
            'id_politique' => null,
            'date_arrivee' => now()->addDays(3)->toDateString(),
            'date_depart' => now()->addDays(6)->toDateString(),
            'nb_voyageurs' => 2,
            'statut' => StatutReservation::CONFIRMEE,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'montant_total' => 1500,
            'frais_service' => 180,
        ];
    }

    public function enAttente(): static
    {
        return $this->state(fn () => ['statut' => StatutReservation::EN_ATTENTE]);
    }

    public function annulee(): static
    {
        return $this->state(fn () => ['statut' => StatutReservation::ANNULEE]);
    }

    /** Arrivée dans N jours (utilisé par les tests de rappels RO07). */
    public function arrivantDans(int $jours): static
    {
        return $this->state(fn () => [
            'date_arrivee' => now()->startOfDay()->addDays($jours)->toDateString(),
            'date_depart' => now()->startOfDay()->addDays($jours + 3)->toDateString(),
        ]);
    }
}
