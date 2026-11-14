<?php

namespace App\Services\Paiements;

use App\Repositories\Interfaces\PaiementRepositoryInterface;

class PaiementService
{
    public function __construct(protected PaiementRepositoryInterface $paiementRepository) {}

    public function traiterPaiementSimule(string $reservationId, array $carteInfo) { /* TODO */ }
    public function genererRecu(string $paiementId) { /* TODO */ }
}
