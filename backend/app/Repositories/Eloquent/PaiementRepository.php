<?php

namespace App\Repositories\Eloquent;

use App\Models\Paiements\Paiement;
use App\Repositories\Interfaces\PaiementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaiementRepository implements PaiementRepositoryInterface
{
    public function findById(string $id): ?Paiement
    {
        return Paiement::with(['recu', 'reservation.annonce', 'reservation.voyageur', 'voyageur'])->find($id);
    }

    public function findByReservationId(string $reservationId): ?Paiement
    {
        return Paiement::with('recu')->where('id_reservation', $reservationId)->first();
    }

    public function create(array $data): Paiement
    {
        return Paiement::create($data);
    }

    public function getHistoriqueUtilisateur(string $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Paiement::where('id_voyageur', $userId)
            ->with(['recu', 'reservation.annonce'])
            ->orderBy('date_transaction', 'desc')
            ->paginate($perPage);
    }
}
