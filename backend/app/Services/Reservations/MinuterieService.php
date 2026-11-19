<?php

namespace App\Services\Reservations;

use App\Models\Reservations\Minuterie;
use Illuminate\Support\Str;

/**
 * Gestion du délai d'expiration de 24h pour les demandes de réservation (RG15).
 */
class MinuterieService
{
    /**
     * Démarre la minuterie de 24h pour une réservation soumise sur demande.
     */
    public function demarrerMinuterie24h(string $reservationId): Minuterie
    {
        $delaiHeures = (int) config('hebergement.delai_reponse_hote_heures', 24);

        return Minuterie::create([
            'id_minuterie'   => Str::uuid()->toString(),
            'id_reservation' => $reservationId,
            'type_minuterie' => 'expiration_demande',
            'date_echeance'  => now()->addHours($delaiHeures),
            'est_active'     => true,
        ]);
    }

    /**
     * Désactive les minuteries actives rattachées à une réservation.
     */
    public function annulerMinuterie(string $reservationId): int
    {
        return Minuterie::where('id_reservation', $reservationId)
            ->where('est_active', true)
            ->update(['est_active' => false]);
    }

    /**
     * Récupère la minuterie active d'une réservation.
     */
    public function getMinuterieActive(string $reservationId): ?Minuterie
    {
        return Minuterie::where('id_reservation', $reservationId)
            ->where('est_active', true)
            ->first();
    }
}
