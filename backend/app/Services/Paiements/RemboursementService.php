<?php

namespace App\Services\Paiements;

class RemboursementService
{
    public function calculerRemboursement(string $reservationId): float { /* TODO: selon politique (RG17, RG18, RG19) */ return 0.0; }
    public function executerRemboursement(string $reservationId) { /* TODO */ }
}
