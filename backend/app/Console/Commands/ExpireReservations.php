<?php

namespace App\Console\Commands;

use App\Services\Reservations\ReservationService;
use Illuminate\Console\Command;

/**
 * Commande planifiée - Expiration automatique des demandes de réservation
 * Règle de gestion : RG15 (délai de 24h sans réponse de l'hôte).
 */
class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';

    protected $description = 'Annule automatiquement les demandes de réservation en attente depuis plus de 24h';

    public function handle(ReservationService $reservationService): int
    {
        $compte = $reservationService->expirerDemandesEnAttente();

        $this->info("{$compte} demande(s) de réservation expirée(s) traitée(s).");

        return Command::SUCCESS;
    }
}
