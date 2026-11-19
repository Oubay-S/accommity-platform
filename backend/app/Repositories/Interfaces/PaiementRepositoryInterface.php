<?php

namespace App\Repositories\Interfaces;

use App\Models\Paiements\Paiement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaiementRepositoryInterface
{
    public function findById(string $id): ?Paiement;

    public function findByReservationId(string $reservationId): ?Paiement;

    public function create(array $data): Paiement;

    public function getHistoriqueUtilisateur(string $userId, int $perPage = 10): LengthAwarePaginator;
}
