<?php

namespace App\Services\Paiements;

class RemboursementService
{
    public function calculerRemboursement(int $reservationId): float { /* TODO: selon politique (RG17, RG18, RG19) */ return 0.0; }
    public function executerRemboursement(int $reservationId) { /* TODO */ }
}
