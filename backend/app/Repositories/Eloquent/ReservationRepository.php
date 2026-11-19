<?php

namespace App\Repositories\Eloquent;

use App\Models\Reservations\Reservation;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function findById(string $id): ?Reservation
    {
        return Reservation::with(['annonce', 'voyageur', 'hote', 'politique', 'paiement', 'minuterie'])
            ->find($id);
    }

    public function create(array $data): Reservation
    {
        return Reservation::create($data);
    }

    public function update(string $id, array $data): Reservation
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update($data);

        return $reservation->fresh();
    }

    public function updateStatut(string $id, string $statut): Reservation
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update(['statut' => $statut]);

        return $reservation->fresh();
    }

    public function existeChevauchement(string $annonceId, string $dateArrivee, string $dateDepart, ?string $excludeId = null): bool
    {
        return Reservation::where('id_annonce', $annonceId)
            ->whereNotIn('statut', ['annulee', 'refusee', 'expiree'])
            ->when($excludeId, fn ($q) => $q->where('id_reservation', '!=', $excludeId))
            ->where(function ($q) use ($dateArrivee, $dateDepart) {
                $q->where('date_arrivee', '<', $dateDepart)
                    ->where('date_depart', '>', $dateArrivee);
            })
            ->exists();
    }

    public function getDemandesExpirees(): Collection
    {
        return Reservation::where('statut', 'en_attente')
            ->whereHas('minuterie', fn ($q) => $q->where('est_active', true)->where('date_echeance', '<=', now())
            )
            ->get();
    }

    public function getHistoriqueVoyageur(string $voyageurId, int $perPage = 10, ?string $statut = null): LengthAwarePaginator
    {
        return Reservation::where('id_voyageur', $voyageurId)
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->with(['annonce', 'paiement', 'politique'])
            ->orderBy('date_creation', 'desc')
            ->paginate($perPage);
    }

    public function getDemandesHote(string $hoteId, int $perPage = 10, ?string $statut = null): LengthAwarePaginator
    {
        return Reservation::where('id_hote', $hoteId)
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->with(['annonce', 'voyageur', 'minuterie'])
            ->orderBy('date_creation', 'desc')
            ->paginate($perPage);
    }
}
