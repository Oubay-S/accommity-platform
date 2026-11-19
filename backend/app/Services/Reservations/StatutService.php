<?php

namespace App\Services\Reservations;

use App\Enums\StatutReservation;
use App\Exceptions\ApiException;
use App\Models\Reservations\Reservation;

/**
 * Machine à états pour les réservations (RG13, RG14, RG15, RG17).
 */
class StatutService
{
    /**
     * @var array<string, array<string>>
     */
    protected array $transitionsValides = [
        'en_attente' => ['confirmee', 'refusee', 'annulee', 'expiree'],
        'confirmee'  => ['en_cours', 'annulee'],
        'en_cours'   => ['terminee'],
    ];

    /**
     * Valide et applique une transition de statut sur une réservation.
     *
     * @throws ApiException
     */
    public function transitionnerStatut(string $reservationId, string|StatutReservation $nouveauStatut): Reservation
    {
        $reservation = Reservation::findOrFail($reservationId);

        $valeurNouveau = $nouveauStatut instanceof StatutReservation
            ? $nouveauStatut->value
            : $nouveauStatut;

        $valeurActuelle = $reservation->statut instanceof StatutReservation
            ? $reservation->statut->value
            : (string) $reservation->statut;

        if ($valeurActuelle === $valeurNouveau) {
            return $reservation;
        }

        if (
            !isset($this->transitionsValides[$valeurActuelle]) ||
            !in_array($valeurNouveau, $this->transitionsValides[$valeurActuelle], true)
        ) {
            throw new ApiException(
                "Transition de statut invalide : impossible de passer de '{$valeurActuelle}' à '{$valeurNouveau}'.",
                409,
                'TRANSITION_INVALIDE'
            );
        }

        $reservation->update(['statut' => $valeurNouveau]);

        return $reservation->fresh();
    }

    /**
     * Vérifie si une transition est permise.
     */
    public function transitionPermise(string|StatutReservation $statutActuel, string|StatutReservation $nouveauStatut): bool
    {
        $actuel = $statutActuel instanceof StatutReservation ? $statutActuel->value : $statutActuel;
        $nouveau = $nouveauStatut instanceof StatutReservation ? $nouveauStatut->value : $nouveauStatut;

        return isset($this->transitionsValides[$actuel]) && in_array($nouveau, $this->transitionsValides[$actuel], true);
    }
}
