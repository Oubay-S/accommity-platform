<?php

namespace App\Repositories\Interfaces;

use App\Models\Reservations\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ReservationRepositoryInterface
{
    public function findById(string $id): ?Reservation;

    public function create(array $data): Reservation;

    public function update(string $id, array $data): Reservation;

    public function updateStatut(string $id, string $statut): Reservation;

    /**
     * Vérifie si une réservation active chevauche la plage demandée (RG12).
     */
    public function existeChevauchement(string $annonceId, string $dateArrivee, string $dateDepart, ?string $excludeId = null): bool;

    /**
     * Récupère les demandes dont la minuterie de 24h est arrivée à échéance.
     *
     * @return Collection<int, Reservation>
     */
    public function getDemandesExpirees(): Collection;

    /**
     * Historique paginé des réservations d'un voyageur.
     */
    public function getHistoriqueVoyageur(string $voyageurId, int $perPage = 10, ?string $statut = null): LengthAwarePaginator;

    /**
     * Demandes de réservations reçues par un hôte.
     */
    public function getDemandesHote(string $hoteId, int $perPage = 10, ?string $statut = null): LengthAwarePaginator;
}
