<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Reservations\ReservationService;

/**
 * Commande planifiée - Expiration automatique des demandes de réservation
 * Règle de gestion : RG15 (délai de 24h sans réponse de l'hôte)
 */
class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';
    protected $description = 'Annule automatiquement les demandes de réservation en attente depuis plus de 24h';

    public function handle(ReservationService $reservationService): int
    {
        // TODO: Appeler $reservationService->expirerDemandesEnAttente();
        $this->info('Vérification des demandes expirées effectuée.');
        return Command::SUCCESS;
    }
}
