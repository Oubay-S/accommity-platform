<?php

namespace App\Services\Reservations;

use App\Repositories\Interfaces\ReservationRepositoryInterface;

class ReservationService
{
    public function __construct(protected ReservationRepositoryInterface $reservationRepository) {}

    public function creerReservation(array $data) { /* TODO (instantanée vs sur demande) */ }
    public function repondreDemande(string $reservationId, bool $acceptee, ?string $motif = null) { /* TODO */ }
    public function annulerReservation(string $reservationId, string $userId) { /* TODO */ }
    public function expirerDemandesEnAttente() { /* TODO (24h - RG15) */ }
}
