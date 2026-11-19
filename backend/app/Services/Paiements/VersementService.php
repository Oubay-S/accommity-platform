<?php

namespace App\Services\Paiements;

use App\Models\Paiements\Versement;
use App\Models\Reservations\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Gestion du séquestre et des versements aux hôtes (24h après le check-in — RG21, RG22).
 */
class VersementService
{
    /**
     * Planifie le versement en séquestre pour l'hôte lors du paiement réussi.
     */
    public function planifierVersement(string $reservationId): Versement
    {
        $reservation = Reservation::findOrFail($reservationId);

        $delaiHeures = (int) config('hebergement.delai_versement_hote_heures', 24);
        $dateLiberationPrevue = Carbon::parse($reservation->date_arrivee)->addHours($delaiHeures);

        $montantHote = round((float) $reservation->montant_total - (float) $reservation->frais_service, 2);

        return Versement::create([
            'id_versement'           => Str::uuid()->toString(),
            'id_reservation'         => $reservationId,
            'id_hote'                => $reservation->id_hote,
            'montant'                => $montantHote,
            'date_versement'         => null,
            'reference_bancaire'     => null,
            'date_liberation_prevue' => $dateLiberationPrevue,
            'statut'                 => 'en_attente',
        ]);
    }

    /**
     * Déclenche la libération des fonds pour l'hôte.
     */
    public function declencherVersementHote(string $reservationId): Versement
    {
        $versement = Versement::where('id_reservation', $reservationId)->firstOrFail();

        $versement->update([
            'statut'             => 'traite',
            'date_versement'     => now(),
            'reference_bancaire' => 'VIR-' . strtoupper(Str::random(10)),
        ]);

        return $versement->fresh();
    }
}
